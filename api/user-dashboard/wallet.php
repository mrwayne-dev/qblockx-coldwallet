<?php
/**
 * Project: qblockx
 * Created by: Wayne
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once '../../api/utilities/email_templates.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

try {
    $db = Database::getInstance()->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Wallet balance
        $walletStmt = $db->prepare("SELECT balance, currency FROM wallets WHERE user_id = :uid");
        $walletStmt->execute(['uid' => $user['id']]);
        $wallet = $walletStmt->fetch();
        $balance  = $wallet ? (float) $wallet['balance'] : 0.0;
        $currency = $wallet['currency'] ?? 'USD';

        // All transactions for paginated display (capped at 500)
        $txStmt = $db->prepare(
            "SELECT id, type, amount, status, payment_id, notes, created_at
             FROM transactions
             WHERE user_id = :uid
             ORDER BY created_at DESC
             LIMIT 500"
        );
        $txStmt->execute(['uid' => $user['id']]);
        $transactions = $txStmt->fetchAll();

        // Pending withdrawal requests
        $wdStmt = $db->prepare(
            "SELECT id, amount, currency, wallet_address, withdrawal_method, status, created_at
             FROM withdrawal_requests
             WHERE user_id = :uid
             ORDER BY created_at DESC
             LIMIT 5"
        );
        $wdStmt->execute(['uid' => $user['id']]);
        $withdrawals = $wdStmt->fetchAll();

        // Withdrawal fee from settings
        $feeRow = $db->query("SELECT value FROM system_settings WHERE `key` = 'withdrawal_fee' LIMIT 1")->fetch();
        $withdrawal_fee = $feeRow ? (float) $feeRow['value'] : 0.0;

        echo json_encode([
            'success' => true,
            'data'    => [
                'balance'         => number_format($balance, 2, '.', ''),
                'currency'        => $currency,
                'transactions'    => $transactions,
                'withdrawals'     => $withdrawals,
                'withdrawal_fee'  => $withdrawal_fee,
            ]
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true);
        $action = trim($input['action'] ?? 'withdraw');

        // ── Transfer ─────────────────────────────────────────────────────────
        if ($action === 'transfer') {
            $recipient_email = strtolower(trim($input['recipient_email'] ?? ''));
            $amount          = (float) ($input['amount'] ?? 0);

            if (empty($recipient_email)) {
                echo json_encode(['success' => false, 'message' => 'Recipient email is required']);
                exit;
            }
            if ($amount < 1) {
                echo json_encode(['success' => false, 'message' => 'Minimum transfer amount is $1.00']);
                exit;
            }

            // Get sender's email for notes
            $senderStmt = $db->prepare("SELECT email FROM users WHERE id = :uid");
            $senderStmt->execute(['uid' => $user['id']]);
            $senderRow = $senderStmt->fetch();
            $sender_email = $senderRow['email'] ?? '';

            // Prevent self-transfer
            if (strtolower($sender_email) === $recipient_email) {
                echo json_encode(['success' => false, 'message' => 'You cannot transfer funds to yourself']);
                exit;
            }

            // Look up recipient (generic error prevents enumeration)
            $recStmt = $db->prepare(
                "SELECT id FROM users WHERE email = :email AND is_verified = TRUE AND role = 'user'"
            );
            $recStmt->execute(['email' => $recipient_email]);
            $recipient = $recStmt->fetch();

            if (!$recipient) {
                echo json_encode(['success' => false, 'message' => 'Recipient account not found']);
                exit;
            }

            $recipient_id = (int) $recipient['id'];

            $db->beginTransaction();

            // Lock sender wallet
            $walletStmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = :uid FOR UPDATE");
            $walletStmt->execute(['uid' => $user['id']]);
            $wallet  = $walletStmt->fetch();
            $balance = $wallet ? (float) $wallet['balance'] : 0.0;

            if ($amount > $balance) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
                exit;
            }

            // Ensure recipient wallet exists (create if missing)
            $db->prepare(
                "INSERT IGNORE INTO wallets (user_id, balance) VALUES (:uid, 0)"
            )->execute(['uid' => $recipient_id]);

            // Debit sender
            $db->prepare(
                "UPDATE wallets SET balance = balance - :amount, updated_at = NOW() WHERE user_id = :uid"
            )->execute(['amount' => $amount, 'uid' => $user['id']]);

            // Credit recipient
            $db->prepare(
                "UPDATE wallets SET balance = balance + :amount, updated_at = NOW() WHERE user_id = :uid"
            )->execute(['amount' => $amount, 'uid' => $recipient_id]);

            // Transaction for sender
            $db->prepare(
                "INSERT INTO transactions (user_id, type, amount, status, notes)
                 VALUES (:uid, 'transfer', :amount, 'completed', :notes)"
            )->execute([
                'uid'    => $user['id'],
                'amount' => $amount,
                'notes'  => 'Transfer to ' . $recipient_email,
            ]);

            // Transaction for recipient
            $db->prepare(
                "INSERT INTO transactions (user_id, type, amount, status, notes)
                 VALUES (:uid, 'transfer', :amount, 'completed', :notes)"
            )->execute([
                'uid'    => $recipient_id,
                'amount' => $amount,
                'notes'  => 'Transfer from ' . $sender_email,
            ]);

            $db->commit();

            // Return updated balance
            $newBalStmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = :uid");
            $newBalStmt->execute(['uid' => $user['id']]);
            $newWallet = $newBalStmt->fetch();
            $new_balance = $newWallet ? number_format((float) $newWallet['balance'], 2, '.', '') : '0.00';

            echo json_encode([
                'success'     => true,
                'message'     => 'Transfer successful!',
                'new_balance' => $new_balance,
            ]);

        // ── Withdraw ─────────────────────────────────────────────────────────
        } else {
            $amount = (float) ($input['amount'] ?? 0);
            $method = trim($input['withdrawal_method'] ?? 'crypto');

            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid withdrawal amount']);
                exit;
            }

            // Fetch withdrawal fee
            $feeRow = $db->query("SELECT value FROM system_settings WHERE `key` = 'withdrawal_fee' LIMIT 1")->fetch();
            $fee = $feeRow ? (float) $feeRow['value'] : 0.0;
            $total_deduct = $amount + $fee;

            // Method-specific validation
            if ($method === 'bank') {
                $bank_country           = trim($input['bank_country']           ?? '');
                $bank_name              = trim($input['bank_name']              ?? '');
                $account_holder_name    = trim($input['account_holder_name']   ?? '');
                $iban                   = trim($input['iban']                   ?? '');
                $bic_swift              = trim($input['bic_swift']              ?? '');
                $sort_code              = trim($input['sort_code']              ?? '');
                $bank_currency          = strtoupper(trim($input['bank_currency'] ?? 'EUR'));
                $transaction_reference  = trim($input['transaction_reference']  ?? '');

                if (empty($bank_country) || empty($bank_name) || empty($account_holder_name) || empty($iban) || empty($bic_swift)) {
                    echo json_encode(['success' => false, 'message' => 'Please fill in all required bank details']);
                    exit;
                }
            } else {
                // Crypto
                $currency       = strtolower(trim($input['currency']       ?? 'usdttrc20'));
                $wallet_address = trim($input['wallet_address'] ?? '');

                if (empty($wallet_address)) {
                    echo json_encode(['success' => false, 'message' => 'Wallet address is required']);
                    exit;
                }
            }

            // Check balance (amount + fee)
            $db->beginTransaction();
            $walletStmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = :uid FOR UPDATE");
            $walletStmt->execute(['uid' => $user['id']]);
            $wallet  = $walletStmt->fetch();
            $balance = $wallet ? (float) $wallet['balance'] : 0.0;

            if ($total_deduct > $balance) {
                $db->rollBack();
                $msg = $fee > 0
                    ? 'Insufficient balance. This withdrawal requires $' . number_format($total_deduct, 2) . ' (amount + $' . number_format($fee, 2) . ' fee)'
                    : 'Insufficient balance';
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }

            // Debit wallet (amount + fee)
            $db->prepare(
                "UPDATE wallets SET balance = balance - :amount, updated_at = NOW() WHERE user_id = :uid"
            )->execute(['amount' => $total_deduct, 'uid' => $user['id']]);

            // Create withdrawal request
            if ($method === 'bank') {
                $db->prepare(
                    "INSERT INTO withdrawal_requests
                        (user_id, amount, currency, wallet_address, withdrawal_method, fee,
                         bank_country, bank_name, account_holder_name, iban, bic_swift,
                         sort_code, bank_currency, transaction_reference)
                     VALUES
                        (:uid, :amount, 'bank', NULL, 'bank', :fee,
                         :bank_country, :bank_name, :account_holder_name, :iban, :bic_swift,
                         :sort_code, :bank_currency, :transaction_reference)"
                )->execute([
                    'uid'                   => $user['id'],
                    'amount'                => $amount,
                    'fee'                   => $fee,
                    'bank_country'          => $bank_country,
                    'bank_name'             => $bank_name,
                    'account_holder_name'   => $account_holder_name,
                    'iban'                  => $iban,
                    'bic_swift'             => $bic_swift,
                    'sort_code'             => $sort_code ?: null,
                    'bank_currency'         => $bank_currency,
                    'transaction_reference' => $transaction_reference ?: null,
                ]);

                $txNotes = 'Bank transfer to ' . $bank_name . ', ' . $bank_country . ' — processing';
            } else {
                $db->prepare(
                    "INSERT INTO withdrawal_requests (user_id, amount, currency, wallet_address, withdrawal_method, fee)
                     VALUES (:uid, :amount, :currency, :address, 'crypto', :fee)"
                )->execute([
                    'uid'      => $user['id'],
                    'amount'   => $amount,
                    'currency' => $currency,
                    'address'  => $wallet_address,
                    'fee'      => $fee,
                ]);

                $txNotes = 'Withdrawal (' . strtoupper($currency) . ') — processing';
            }

            // Transaction record
            $db->prepare(
                "INSERT INTO transactions (user_id, type, amount, status, notes)
                 VALUES (:uid, 'withdrawal', :amount, 'pending', :notes)"
            )->execute([
                'uid'    => $user['id'],
                'amount' => $total_deduct,
                'notes'  => $txNotes,
            ]);

            $db->commit();

            $resp = json_encode(['success' => true, 'message' => 'Withdrawal request submitted. It will be processed within 24–48 hours.']);
            header('Content-Type: application/json');
            header('Content-Encoding: identity');
            header('Content-Length: ' . strlen($resp));
            header('Connection: close');
            echo $resp;
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                ignore_user_abort(true);
                flush();
            }

            // Send withdrawal notification emails (non-blocking)
            try {
                $nameStmt = $db->prepare("SELECT full_name FROM users WHERE id = :uid");
                $nameStmt->execute(['uid' => $user['id']]);
                $nameRow  = $nameStmt->fetch();
                $fullName = $nameRow['full_name'] ?? 'User';

                if ($method === 'bank') {
                    $displayAddress  = $account_holder_name . ' — ' . $bank_name . ', ' . $bank_country;
                    $displayCurrency = $bank_currency;
                    $displayNetwork  = 'Bank Transfer';
                } else {
                    $displayAddress  = $wallet_address;
                    $displayCurrency = strtoupper($currency);
                    $displayNetwork  = strtoupper($currency);
                }

                Mailer::sendWithdrawalPending(
                    $user['email'],
                    $fullName,
                    number_format($amount, 2),
                    $displayAddress,
                    24,
                    $displayCurrency,
                    $displayNetwork,
                    date('d M Y, H:i'),
                    ''
                );

                $adminEmail = getenv('SMTP_USER') ?: '';
                if ($adminEmail) {
                    Mailer::sendAdminNewWithdrawal(
                        $adminEmail,
                        $fullName,
                        $user['email'],
                        $amount,
                        $displayCurrency,
                        $displayNetwork,
                        $displayAddress,
                        '',
                        date('d M Y, H:i'),
                        '',
                        ''
                    );
                }
            } catch (Exception $mailErr) {
                error_log('wallet withdrawal: mail error for user ' . $user['id'] . ': ' . $mailErr->getMessage());
            }
        }

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
