<?php
/**
 * Project: qblockx
 * API: admin-dashboard/dashboard.php — Overview stats
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

try {
    $db = Database::getInstance()->getConnection();

    $totalUsers         = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    $newToday           = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE() AND role = 'user'")->fetchColumn();
    $totalDeposits      = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'deposit' AND status = 'completed'")->fetchColumn();
    $pendingDeposits    = $db->query("SELECT COUNT(*) FROM transactions WHERE type = 'deposit' AND status = 'pending'")->fetchColumn();

    // Investment stats (plan investments)
    $activePlanInv      = $db->query("SELECT COUNT(*) FROM plan_investments WHERE status = 'active'")->fetchColumn();
    $totalPlanValue     = $db->query("SELECT COALESCE(SUM(amount), 0) FROM plan_investments WHERE status = 'active'")->fetchColumn();

    // Commodity investments
    $activeCommodityInv = $db->query("SELECT COUNT(*) FROM commodity_investments WHERE status = 'active'")->fetchColumn();
    $totalCommodityVal  = $db->query("SELECT COALESCE(SUM(amount), 0) FROM commodity_investments WHERE status = 'active'")->fetchColumn();

    // Real estate investments
    $activeRealEstateInv = $db->query("SELECT COUNT(*) FROM realestate_investments WHERE status = 'active'")->fetchColumn();
    $totalRealEstateVal  = $db->query("SELECT COALESCE(SUM(amount), 0) FROM realestate_investments WHERE status = 'active'")->fetchColumn();

    // Total portfolio value across all investment types
    $totalPortfolioValue = (float)$totalPlanValue + (float)$totalCommodityVal + (float)$totalRealEstateVal;
    $totalActiveInv      = (int)$activePlanInv + (int)$activeCommodityInv + (int)$activeRealEstateInv;

    // Wallet links
    $walletLinksCount   = $db->query("SELECT COUNT(DISTINCT user_id) FROM trust_wallet_links")->fetchColumn();

    // Pending withdrawals
    $pendingWithdrawals = $db->query("SELECT COUNT(*) FROM withdrawal_requests WHERE status = 'pending'")->fetchColumn();

    // Last 5 transactions for overview
    $recentStmt = $db->query(
        "SELECT t.type, t.amount, t.status, t.created_at,
                u.full_name AS user_name, u.email AS user_email
         FROM transactions t
         JOIN users u ON u.id = t.user_id
         ORDER BY t.created_at DESC
         LIMIT 5"
    );
    $recentTransactions = $recentStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => [
            'total_users'              => (int) $totalUsers,
            'new_today'                => (int) $newToday,
            'total_deposits'           => number_format((float) $totalDeposits, 2, '.', ''),
            'pending_deposits'         => (int) $pendingDeposits,
            'active_plan_investments'  => (int) $activePlanInv,
            'active_commodity_inv'     => (int) $activeCommodityInv,
            'active_realestate_inv'    => (int) $activeRealEstateInv,
            'total_active_investments' => (int) $totalActiveInv,
            'total_portfolio_value'    => number_format($totalPortfolioValue, 2, '.', ''),
            'wallet_links_count'       => (int) $walletLinksCount,
            'pending_withdrawals'      => (int) $pendingWithdrawals,
            'recent_transactions'      => $recentTransactions,
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
