<?php
/**
 * Project: Qblockx
 * API: Admin — Plan Investments overview
 * GET  → paginated list of all user plan investments
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

try {
    $db     = Database::getInstance()->getConnection();
    $page   = max(1, (int) ($_GET['page']   ?? 1));
    $limit  = 50;
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? '';

    $where  = $status ? "WHERE pi.status = " . $db->quote($status) : '';

    $total = $db->query("SELECT COUNT(*) FROM plan_investments pi $where")->fetchColumn();

    $stmt = $db->prepare(
        "SELECT pi.id, u.email, u.full_name, pi.plan_name, pi.amount,
                pi.yield_rate, pi.starts_at, pi.ends_at, pi.expected_return,
                pi.actual_return, pi.status, pi.created_at,
                ip.tier
         FROM plan_investments pi
         JOIN users u          ON u.id  = pi.user_id
         JOIN investment_plans ip ON ip.id = pi.plan_id
         $where
         ORDER BY pi.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $metrics = $db->query(
        "SELECT COALESCE(SUM(amount), 0) AS total_value,
                COALESCE(SUM(expected_return), 0) AS total_returns
         FROM plan_investments"
    )->fetch();

    echo json_encode([
        'success' => true,
        'data'    => [
            'investments'   => $stmt->fetchAll(),
            'total'         => (int) $total,
            'page'          => $page,
            'pages'         => (int) ceil($total / $limit),
            'total_value'   => number_format((float) $metrics['total_value'],   2, '.', ''),
            'total_returns' => number_format((float) $metrics['total_returns'], 2, '.', ''),
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
