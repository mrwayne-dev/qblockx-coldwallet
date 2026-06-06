<?php
/**
 * Project: qblockx
 * API: User — Trade Investments
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

// Investment plan definitions
const PLANS = [
    'starter'  => ['min' => 100,  'max' => 499,       'daily_rate' => 0.02],
    'bronze'   => ['min' => 500,  'max' => 2999,       'daily_rate' => 0.04],
    'silver'   => ['min' => 3000, 'max' => 4999,       'daily_rate' => 0.06],
    'platinum' => ['min' => 5000, 'max' => PHP_INT_MAX, 'daily_rate' => 0.08],
];
const DURATION_DAYS = 5;

try {
    $db = Database::getInstance()->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // List user's investments
        $stmt = $db->prepare(
            "SELECT id, plan_name, amount, daily_rate, duration_days, total_earned, status,
                    starts_at, ends_at, created_at
             FROM investments
             WHERE user_id = :uid
             ORDER BY created_at DESC"
        );
        $stmt->execute(['uid' => $user['id']]);
        $investments = $stmt->fetchAll();

        // Active investment summary
        $activeStmt = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) AS total_invested,
                    COUNT(*) AS count
             FROM investments
             WHERE user_id = :uid AND status = 'active'"
        );
        $activeStmt->execute(['uid' => $user['id']]);
        $active = $activeStmt->fetch();

        echo json_encode([
            'success' => true,
            'data'    => [
                'investments'    => $investments,
                'total_invested' => number_format((float) $active['total_invested'], 2, '.', ''),
                'active_count'   => (int) $active['count'],
            ]
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input     = json_decode(file_get_contents('php://input'), true);
        $plan_name = strtolower(trim($input['plan'] ?? ''));
        $amount    = (float) ($input['amount'] ?? 0);

        if (!isset(PLANS[$plan_name])) {
            echo json_encode(['success' => false, 'message' => 'Invalid investment plan']);
            exit;
        }

        $plan = PLANS[$plan_name];

        if ($amount < $plan['min'] || $amount > $plan['max']) {
            echo json_encode([
                'success' => false,
                'message' => "Amount must be between \${$plan['min']} and " .
                             ($plan['max'] === PHP_INT_MAX ? 'unlimited' : "\${$plan['max']}")
            ]);
            exit;
        }

        $db->beginTransaction();

        // Check wallet balance
        $walletStmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = :uid FOR UPDATE");
        $walletStmt->execute(['uid' => $user['id']]);
        $wallet = $walletStmt->fetch();
        $balance = $wallet ? (float) $wallet['balance'] : 0.0;

        if ($amount > $balance) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
            exit;
        }

        $starts_at = date('Y-m-d H:i:s');
        $ends_at   = date('Y-m-d H:i:s', strtotime('+' . DURATION_DAYS . ' days'));

        // Debit wallet
        $db->prepare(
            "UPDATE wallets SET balance = balance - :amount, updated_at = NOW() WHERE user_id = :uid"
        )->execute(['amount' => $amount, 'uid' => $user['id']]);

        // Create investment
        $db->prepare(
            "INSERT INTO investments (user_id, plan_name, amount, daily_rate, duration_days, starts_at, ends_at)
             VALUES (:uid, :plan, :amount, :rate, :days, :starts, :ends)"
        )->execute([
            'uid'    => $user['id'],
            'plan'   => $plan_name,
            'amount' => $amount,
            'rate'   => $plan['daily_rate'],
            'days'   => DURATION_DAYS,
            'starts' => $starts_at,
            'ends'   => $ends_at,
        ]);

        // Record transaction
        $db->prepare(
            "INSERT INTO transactions (user_id, type, amount, currency, status, notes)
             VALUES (:uid, 'investment', :amount, 'USD', 'completed', :note)"
        )->execute([
            'uid'    => $user['id'],
            'amount' => $amount,
            'note'   => "Investment: {$plan_name} plan",
        ]);

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => ucfirst($plan_name) . ' investment started. Daily earnings will begin processing.',
            'data'    => [
                'plan'       => $plan_name,
                'amount'     => $amount,
                'daily_rate' => ($plan['daily_rate'] * 100) . '%',
                'ends_at'    => $ends_at,
            ]
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
