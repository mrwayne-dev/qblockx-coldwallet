<?php
/**
 * Project: qblockx
 * API: user-dashboard/deposits.php — Fixed deposits (GET list | POST open)
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once '../../api/utilities/email_templates.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

try {
    $db  = Database::getInstance()->getConnection();
    $uid = $user['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $statsStmt = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) AS total_deposited,
                    COALESCE(SUM(expected_return), 0) AS total_expected_return
             FROM fixed_deposits WHERE user_id = :uid AND status = 'active'"
        );
        $statsStmt->execute(['uid' => $uid]);
        $stats = $statsStmt->fetch();

        $depositsStmt = $db->prepare(
            "SELECT id, amount, interest_rate, duration_months, start_date,
                    maturity_date, expected_return, status, created_at
             FROM fixed_deposits WHERE user_id = :uid
             ORDER BY created_at DESC"
        );
        $depositsStmt->execute(['uid' => $uid]);
        $deposits = $depositsStmt->fetchAll();

        echo json_encode([
            'total_deposited'       => number_format((float) $stats['total_deposited'],       2, '.', ''),
            'total_expected_return' => number_format((float) $stats['total_expected_return'], 2, '.', ''),
            'deposits'              => $deposits,
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $input           = json_decode(file_get_contents('php://input'), true) ?? [];
        $amount          = (float) ($input['amount']          ?? 0);
        $duration_months = (int)   ($input['duration_months'] ?? 0);

        if ($amount <= 0)          { echo json_encode(['success' => false, 'message' => 'Enter a valid deposit amount']); exit; }
        if ($duration_months <= 0) { echo json_encode(['success' => false, 'message' => 'Select a duration']); exit; }

        // Fetch matching rate
        $rateStmt = $db->prepare(
            "SELECT rate FROM rates WHERE product = 'fixed_deposit'
             AND duration_months = :dur AND is_active = 1 LIMIT 1"
        );
        $rateStmt->execute(['dur' => $duration_months]);
        $rateRow = $rateStmt->fetch();
        $rate = $rateRow ? (float) $rateRow['rate'] : 10.00;

        // Compute dates and return
        $start_date     = date('Y-m-d');
        $maturity_date  = date('Y-m-d', strtotime("+{$duration_months} months"));
        $expected_return = $amount + ($amount * ($rate / 100) * ($duration_months / 12));

        // Check wallet balance
        $walletStmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = :uid FOR UPDATE");
        $db->beginTransaction();
        $walletStmt->execute(['uid' => $uid]);
        $wallet = $walletStmt->fetch();
        if (!$wallet || (float) $wallet['balance'] < $amount) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance']);
            exit;
        }

        // Debit wallet
        $db->prepare("UPDATE wallets SET balance = balance - :amount WHERE user_id = :uid")
           ->execute(['amount' => $amount, 'uid' => $uid]);

        // Create deposit
        $db->prepare(
            "INSERT INTO fixed_deposits (user_id, amount, interest_rate, duration_months, start_date, maturity_date, expected_return)
             VALUES (:uid, :amount, :rate, :dur, :start, :maturity, :expected)"
        )->execute([
            'uid'      => $uid,
            'amount'   => $amount,
            'rate'     => $rate,
            'dur'      => $duration_months,
            'start'    => $start_date,
            'maturity' => $maturity_date,
            'expected' => round($expected_return, 2),
        ]);

        // Log transaction — use 'withdrawal' since funds leave the wallet into a fixed deposit product
        $db->prepare(
            "INSERT INTO transactions (user_id, type, amount, status, notes)
             VALUES (:uid, 'withdrawal', :amount, 'completed', 'Fixed deposit opened')"
        )->execute(['uid' => $uid, 'amount' => $amount]);

        $db->commit();

        // Send confirmation email (non-fatal)
        try {
            $userRow = $db->prepare("SELECT full_name, email FROM users WHERE id = :uid LIMIT 1");
            $userRow->execute(['uid' => $uid]);
            $userInfo = $userRow->fetch();
            if ($userInfo) {
                Mailer::sendFixedDepositOpened(
                    $userInfo['email'],
                    $userInfo['full_name'],
                    number_format($amount, 2),
                    (string) $rate,
                    $duration_months,
                    $maturity_date,
                    number_format(round($expected_return, 2), 2)
                );
            }
        } catch (Exception $mailErr) {}

        echo json_encode(['success' => true, 'message' => 'Fixed deposit opened successfully!']);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
