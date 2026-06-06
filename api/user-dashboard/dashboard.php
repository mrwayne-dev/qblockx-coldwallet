<?php
/**
 * Project: qblockx
 * API: user-dashboard/dashboard.php — Overview stats
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

try {
    $db  = Database::getInstance()->getConnection();
    $uid = $user['id'];

    // Wallet balance + currency
    $walletStmt = $db->prepare("SELECT balance, currency FROM wallets WHERE user_id = :uid");
    $walletStmt->execute(['uid' => $uid]);
    $wallet   = $walletStmt->fetch();
    $balance  = $wallet ? (float) $wallet['balance'] : 0.0;
    $currency = $wallet['currency'] ?? 'USD';

    // Aggregate investment stats across all 3 types (all-time total invested)
    // Named params must be unique per statement when emulated prepares are off
    $invAggStmt = $db->prepare(
        "SELECT
           (SELECT COALESCE(SUM(amount),0) FROM plan_investments       WHERE user_id = :uid1)
         + (SELECT COALESCE(SUM(amount),0) FROM commodity_investments  WHERE user_id = :uid2)
         + (SELECT COALESCE(SUM(amount),0) FROM realestate_investments WHERE user_id = :uid3)
         AS total_invested_all"
    );
    $invAggStmt->execute(['uid1' => $uid, 'uid2' => $uid, 'uid3' => $uid]);
    $total_invested_all = (float) $invAggStmt->fetchColumn();

    $activeAggStmt = $db->prepare(
        "SELECT
           (SELECT COUNT(*) FROM plan_investments       WHERE user_id = :uid4 AND status = 'active')
         + (SELECT COUNT(*) FROM commodity_investments  WHERE user_id = :uid5 AND status = 'active')
         + (SELECT COUNT(*) FROM realestate_investments WHERE user_id = :uid6 AND status = 'active')
         AS active_count_all"
    );
    $activeAggStmt->execute(['uid4' => $uid, 'uid5' => $uid, 'uid6' => $uid]);
    $active_count_all = (int) $activeAggStmt->fetchColumn();

    $expectedAggStmt = $db->prepare(
        "SELECT
           (SELECT COALESCE(SUM(expected_return),0) FROM plan_investments       WHERE user_id = :uid7 AND status = 'active')
         + (SELECT COALESCE(SUM(expected_return),0) FROM commodity_investments  WHERE user_id = :uid8 AND status = 'active')
         + (SELECT COALESCE(SUM(expected_return),0) FROM realestate_investments WHERE user_id = :uid9 AND status = 'active')
         AS total_expected_all"
    );
    $expectedAggStmt->execute(['uid7' => $uid, 'uid8' => $uid, 'uid9' => $uid]);
    $total_expected_all = (float) $expectedAggStmt->fetchColumn();

    // Recent transactions (last 5)
    $txStmt = $db->prepare(
        "SELECT type, amount, status, created_at
         FROM transactions WHERE user_id = :uid
         ORDER BY created_at DESC LIMIT 5"
    );
    $txStmt->execute(['uid' => $uid]);
    $recent_transactions = $txStmt->fetchAll();

    // User info
    $userStmt = $db->prepare("SELECT email, full_name, created_at FROM users WHERE id = :uid");
    $userStmt->execute(['uid' => $uid]);
    $userInfo = $userStmt->fetch();

    // Product rates — wrapped defensively; never let a missing column crash the whole dashboard
    $rates = [];
    try {
        $ratesStmt = $db->query(
            "SELECT product, label, duration_months, rate
             FROM rates WHERE is_active = 1
             ORDER BY product, duration_months"
        );
        $rates = $ratesStmt->fetchAll();
    } catch (PDOException $rateErr) {
        $rates = [];
    }

    echo json_encode([
        'success' => true,
        'data'    => [
            'user'                => [
                'email'        => $userInfo['email'],
                'full_name'    => $userInfo['full_name'],
                'member_since' => $userInfo['created_at'],
            ],
            'currency'            => $currency,
            'balance'             => number_format($balance,             2, '.', ''),
            'total_invested_all'  => number_format($total_invested_all, 2, '.', ''),
            'active_count_all'    => $active_count_all,
            'total_expected_all'  => number_format($total_expected_all, 2, '.', ''),
            'recent_transactions' => $recent_transactions,
            'rates'               => $rates,
        ]
    ]);
} catch (PDOException $e) {
    error_log('dashboard.php PDOException: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
