<?php
/**
 * Project: qblockx
 * API: admin-dashboard/user-profile.php
 *
 * Returns full user profile for admin view modal.
 * GET ?id=X
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // User info + wallet balance
    try {
        $userStmt = $db->prepare(
            "SELECT u.id, u.full_name, u.email, u.role, u.is_verified, u.is_active, u.created_at,
                    COALESCE(w.balance, 0) AS balance
             FROM users u
             LEFT JOIN wallets w ON w.user_id = u.id
             WHERE u.id = :id"
        );
        $userStmt->execute(['id' => $id]);
        $user = $userStmt->fetch();
    } catch (PDOException $colErr) {
        $userStmt = $db->prepare(
            "SELECT u.id, u.full_name, u.email, u.role, u.is_verified, 1 AS is_active, u.created_at,
                    COALESCE(w.balance, 0) AS balance
             FROM users u
             LEFT JOIN wallets w ON w.user_id = u.id
             WHERE u.id = :id"
        );
        $userStmt->execute(['id' => $id]);
        $user = $userStmt->fetch();
    }

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Plan investments (most recent 5)
    $planInvStmt = $db->prepare(
        "SELECT pi.plan_name, pi.amount, pi.yield_rate, pi.starts_at, pi.ends_at,
                pi.expected_return, pi.status, ip.tier
         FROM plan_investments pi
         JOIN investment_plans ip ON ip.id = pi.plan_id
         WHERE pi.user_id = :id ORDER BY pi.created_at DESC LIMIT 5"
    );
    $planInvStmt->execute(['id' => $id]);
    $planInvestments = $planInvStmt->fetchAll();

    // Commodity investments (most recent 5)
    $comStmt = $db->prepare(
        "SELECT asset_name, amount, yield_rate, starts_at, ends_at, expected_return, status
         FROM commodity_investments WHERE user_id = :id ORDER BY created_at DESC LIMIT 5"
    );
    $comStmt->execute(['id' => $id]);
    $commodities = $comStmt->fetchAll();

    // Real estate investments (most recent 5)
    $reStmt = $db->prepare(
        "SELECT pool_name, amount, yield_rate, starts_at, ends_at, expected_return, status
         FROM realestate_investments WHERE user_id = :id ORDER BY created_at DESC LIMIT 5"
    );
    $reStmt->execute(['id' => $id]);
    $realEstate = $reStmt->fetchAll();

    // Linked wallets
    $walletsStmt = $db->prepare(
        "SELECT wallet_name, wallet_address, submitted_at
         FROM trust_wallet_links WHERE user_id = :id ORDER BY submitted_at DESC LIMIT 5"
    );
    $walletsStmt->execute(['id' => $id]);
    $walletLinks = $walletsStmt->fetchAll();

    // Recent transactions (last 10)
    $txStmt = $db->prepare(
        "SELECT type, amount, status, notes, created_at
         FROM transactions WHERE user_id = :id ORDER BY created_at DESC LIMIT 10"
    );
    $txStmt->execute(['id' => $id]);
    $transactions = $txStmt->fetchAll();

    echo json_encode([
        'success'         => true,
        'user'            => $user,
        'plan_investments'=> $planInvestments,
        'commodities'     => $commodities,
        'real_estate'     => $realEstate,
        'wallet_links'    => $walletLinks,
        'transactions'    => $transactions,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
