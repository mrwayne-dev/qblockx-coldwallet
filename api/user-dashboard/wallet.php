<?php
/**
 * Quantum BlocX — API: user-dashboard/wallet.php
 * GET           → All user wallets with balances + currency info
 * POST          → Send crypto (external address or internal user)
 * POST ?action=swap → Swap tokens
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAuth();
$auth = getAuthUser();

try {
    $db  = Database::getInstance()->getConnection();
    $uid = $auth['id'];

    // ── POST: Send or Swap ───────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $_GET['action'] ?? '';

        // ── SWAP ─────────────────────────────────────────────────────
        if ($action === 'swap') {
            $fromId = (int) ($input['from_currency'] ?? 0);
            $toId   = (int) ($input['to_currency'] ?? 0);
            $amount = (float) ($input['from_amount'] ?? 0);

            if (!$fromId || !$toId || $amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid swap parameters']);
                exit;
            }
            if ($fromId === $toId) {
                echo json_encode(['success' => false, 'message' => 'Cannot swap same currency']);
                exit;
            }

            // Get user's from-wallet
            $fromWallet = $db->prepare("SELECT * FROM wallets WHERE user_id = :uid AND currency_id = :cid");
            $fromWallet->execute(['uid' => $uid, 'cid' => $fromId]);
            $fw = $fromWallet->fetch(PDO::FETCH_ASSOC);

            if (!$fw || (float) $fw['balance'] < $amount) {
                echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
                exit;
            }

            // Get prices for rate calculation
            $priceStmt = $db->prepare("SELECT id, current_price_usd FROM currencies WHERE id IN (:fid, :tid)");
            $priceStmt->execute(['fid' => $fromId, 'tid' => $toId]);
            $prices = $priceStmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

            $fromPrice = (float) ($prices[$fromId]['current_price_usd'] ?? 0);
            $toPrice   = (float) ($prices[$toId]['current_price_usd'] ?? 0);

            if ($fromPrice <= 0 || $toPrice <= 0) {
                echo json_encode(['success' => false, 'message' => 'Price data unavailable']);
                exit;
            }

            // Calculate fee based on card tier
            $userStmt = $db->prepare("SELECT card_tier FROM users WHERE id = :uid");
            $userStmt->execute(['uid' => $uid]);
            $cardTier = $userStmt->fetchColumn() ?: 'none';

            $feeStmt = $db->prepare(
                "SELECT fee_pct FROM fee_schedule WHERE card_tier = :tier AND fee_type = 'swap' AND is_active = 1"
            );
            $feeStmt->execute(['tier' => $cardTier]);
            $feePct = (float) ($feeStmt->fetchColumn() ?: 2.5);

            $feeAmount  = $amount * ($feePct / 100);
            $netAmount  = $amount - $feeAmount;
            $rate       = $fromPrice / $toPrice;
            $toAmount   = $netAmount * $rate;

            // Ensure to-wallet exists
            $toWallet = $db->prepare("SELECT id FROM wallets WHERE user_id = :uid AND currency_id = :cid");
            $toWallet->execute(['uid' => $uid, 'cid' => $toId]);
            $tw = $toWallet->fetch(PDO::FETCH_ASSOC);

            if (!$tw) {
                // Auto-create wallet for this currency
                $cInfo = $db->prepare("SELECT network FROM currencies WHERE id = :cid");
                $cInfo->execute(['cid' => $toId]);
                $network = $cInfo->fetchColumn() ?: '';
                $addr = '0x' . bin2hex(random_bytes(20)); // placeholder address

                $createW = $db->prepare(
                    "INSERT INTO wallets (user_id, currency_id, address, network) VALUES (:uid, :cid, :addr, :net)"
                );
                $createW->execute(['uid' => $uid, 'cid' => $toId, 'addr' => $addr, 'net' => $network]);
                $twId = (int) $db->lastInsertId();
            } else {
                $twId = (int) $tw['id'];
            }

            $db->beginTransaction();
            try {
                // Deduct from source wallet
                $db->prepare("UPDATE wallets SET balance = balance - :amt WHERE id = :wid")
                   ->execute(['amt' => $amount, 'wid' => $fw['id']]);

                // Credit to destination wallet
                $db->prepare("UPDATE wallets SET balance = balance + :amt WHERE id = :wid")
                   ->execute(['amt' => $toAmount, 'wid' => $twId]);

                // Log swap
                $db->prepare(
                    "INSERT INTO swaps (user_id, from_currency_id, to_currency_id, from_amount, to_amount, exchange_rate, fee, fee_pct, status, completed_at)
                     VALUES (:uid, :fid, :tid, :famt, :tamt, :rate, :fee, :fpct, 'completed', NOW())"
                )->execute([
                    'uid'  => $uid, 'fid' => $fromId, 'tid' => $toId,
                    'famt' => $amount, 'tamt' => $toAmount, 'rate' => $rate,
                    'fee'  => $feeAmount, 'fpct' => $feePct,
                ]);

                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Swap completed', 'data' => [
                    'from_amount' => $amount, 'to_amount' => round($toAmount, 8),
                    'rate' => $rate, 'fee' => $feeAmount,
                ]]);
            } catch (\Exception $e) {
                $db->rollBack();
                throw $e;
            }
            exit;
        }

        // ── SEND CRYPTO ──────────────────────────────────────────────
        $currencyId = (int) ($input['currency_id'] ?? 0);
        $amount     = (float) ($input['amount'] ?? 0);
        $sendType   = $input['send_type'] ?? 'address';
        $address    = trim($input['address'] ?? '');
        $recipient  = trim($input['recipient'] ?? '');

        if (!$currencyId || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid amount or currency']);
            exit;
        }

        // Check card requirement
        $userStmt = $db->prepare("SELECT card_tier FROM users WHERE id = :uid");
        $userStmt->execute(['uid' => $uid]);
        $cardTier = $userStmt->fetchColumn() ?: 'none';

        if ($cardTier === 'none') {
            echo json_encode(['success' => false, 'message' => 'QFS Card activation required to send assets']);
            exit;
        }

        // Get wallet
        $wStmt = $db->prepare("SELECT * FROM wallets WHERE user_id = :uid AND currency_id = :cid");
        $wStmt->execute(['uid' => $uid, 'cid' => $currencyId]);
        $wallet = $wStmt->fetch(PDO::FETCH_ASSOC);

        if (!$wallet || (float) $wallet['balance'] < $amount) {
            echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
            exit;
        }

        // Calculate fee
        $feeStmt = $db->prepare(
            "SELECT fee_pct FROM fee_schedule WHERE card_tier = :tier AND fee_type = 'send' AND is_active = 1"
        );
        $feeStmt->execute(['tier' => $cardTier]);
        $feePct = (float) ($feeStmt->fetchColumn() ?: 1.5);
        $fee = $amount * ($feePct / 100);

        // Get currency symbol
        $symStmt = $db->prepare("SELECT symbol FROM currencies WHERE id = :cid");
        $symStmt->execute(['cid' => $currencyId]);
        $symbol = $symStmt->fetchColumn() ?: '';

        $recipientUserId = null;
        $recipientAddr   = $address;

        // Internal transfer — look up user
        if ($sendType === 'internal' && $recipient) {
            $recipientAddr = null;
            $lookupStmt = $db->prepare(
                "SELECT id FROM users WHERE (username = :r OR email = :r2) AND id != :uid"
            );
            $lookupStmt->execute(['r' => $recipient, 'r2' => $recipient, 'uid' => $uid]);
            $recipientUserId = $lookupStmt->fetchColumn();
            if (!$recipientUserId) {
                echo json_encode(['success' => false, 'message' => 'Recipient user not found']);
                exit;
            }
        } elseif ($sendType === 'address' && !$address) {
            echo json_encode(['success' => false, 'message' => 'Recipient address is required']);
            exit;
        }

        $db->beginTransaction();
        try {
            // Deduct from sender
            $db->prepare("UPDATE wallets SET balance = balance - :amt WHERE id = :wid")
               ->execute(['amt' => $amount, 'wid' => $wallet['id']]);

            // Log transaction
            $db->prepare(
                "INSERT INTO transactions (user_id, wallet_id, type, amount, currency_id, currency_symbol,
                    recipient_address, recipient_user_id, fee, status, ip_address)
                 VALUES (:uid, :wid, 'send', :amt, :cid, :sym, :addr, :ruid, :fee, 'pending', :ip)"
            )->execute([
                'uid' => $uid, 'wid' => $wallet['id'], 'amt' => $amount,
                'cid' => $currencyId, 'sym' => $symbol,
                'addr' => $recipientAddr, 'ruid' => $recipientUserId,
                'fee' => $fee, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            // Internal transfer: credit recipient immediately
            if ($recipientUserId) {
                // Ensure recipient has a wallet for this currency
                $rw = $db->prepare("SELECT id FROM wallets WHERE user_id = :uid AND currency_id = :cid");
                $rw->execute(['uid' => $recipientUserId, 'cid' => $currencyId]);
                $rwRow = $rw->fetch(PDO::FETCH_ASSOC);

                if (!$rwRow) {
                    $cInfo = $db->prepare("SELECT network FROM currencies WHERE id = :cid");
                    $cInfo->execute(['cid' => $currencyId]);
                    $network = $cInfo->fetchColumn() ?: '';
                    $addr = '0x' . bin2hex(random_bytes(20));
                    $db->prepare("INSERT INTO wallets (user_id, currency_id, address, network) VALUES (:uid, :cid, :addr, :net)")
                       ->execute(['uid' => $recipientUserId, 'cid' => $currencyId, 'addr' => $addr, 'net' => $network]);
                    $rwId = (int) $db->lastInsertId();
                } else {
                    $rwId = (int) $rwRow['id'];
                }

                $netAmount = $amount - $fee;
                $db->prepare("UPDATE wallets SET balance = balance + :amt WHERE id = :wid")
                   ->execute(['amt' => $netAmount, 'wid' => $rwId]);

                // Log receive transaction for recipient
                $db->prepare(
                    "INSERT INTO transactions (user_id, wallet_id, type, amount, currency_id, currency_symbol, status)
                     VALUES (:uid, :wid, 'receive', :amt, :cid, :sym, 'completed')"
                )->execute([
                    'uid' => $recipientUserId, 'wid' => $rwId, 'amt' => $netAmount,
                    'cid' => $currencyId, 'sym' => $symbol,
                ]);
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Transaction submitted']);
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
        exit;
    }

    // ── GET: User wallets ────────────────────────────────────────────

    $walletsStmt = $db->prepare(
        "SELECT w.id, w.currency_id, w.address, w.balance, w.locked_balance, w.network,
                c.symbol, c.name, c.current_price_usd, c.price_change_24h_pct,
                c.is_new, c.is_popular, c.expected_arrival_confirmations, c.expected_unlock_confirmations
         FROM wallets w
         JOIN currencies c ON c.id = w.currency_id
         WHERE w.user_id = :uid AND w.is_active = 1
         ORDER BY c.sort_order ASC"
    );
    $walletsStmt->execute(['uid' => $uid]);
    $wallets = $walletsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent swaps
    $swapStmt = $db->prepare(
        "SELECT s.id, fc.symbol AS from_symbol, tc.symbol AS to_symbol,
                s.from_amount, s.to_amount, s.exchange_rate, s.fee, s.status, s.created_at
         FROM swaps s
         JOIN currencies fc ON fc.id = s.from_currency_id
         JOIN currencies tc ON tc.id = s.to_currency_id
         WHERE s.user_id = :uid
         ORDER BY s.created_at DESC LIMIT 10"
    );
    $swapStmt->execute(['uid' => $uid]);
    $recentSwaps = $swapStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data'    => [
            'wallets'      => $wallets,
            'recent_swaps' => $recentSwaps,
        ]
    ]);

} catch (PDOException $e) {
    error_log('wallet.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
