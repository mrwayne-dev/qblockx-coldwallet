<?php
/**
 * Project: Qblockx
 * API: Admin — Real Estate Investments overview
 * GET  → paginated list of all real estate investments
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

    $where = $status ? "WHERE ri.status = " . $db->quote($status) : '';

    $total = $db->query("SELECT COUNT(*) FROM realestate_investments ri $where")->fetchColumn();

    $stmt = $db->prepare(
        "SELECT ri.id, u.email, u.full_name, ri.pool_name, ri.amount,
                ri.yield_rate, ri.starts_at, ri.ends_at, ri.next_payout_at,
                ri.total_paid_out, ri.expected_return, ri.actual_return,
                ri.status, ri.created_at
         FROM realestate_investments ri
         JOIN users u ON u.id = ri.user_id
         $where
         ORDER BY ri.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $metrics = $db->query(
        "SELECT COALESCE(SUM(amount), 0) AS total_value,
                COALESCE(SUM(total_paid_out), 0) AS total_paid_out
         FROM realestate_investments"
    )->fetch();

    echo json_encode([
        'success' => true,
        'data'    => [
            'investments'    => $stmt->fetchAll(),
            'total'          => (int) $total,
            'page'           => $page,
            'pages'          => (int) ceil($total / $limit),
            'total_value'    => number_format((float) $metrics['total_value'],    2, '.', ''),
            'total_paid_out' => number_format((float) $metrics['total_paid_out'], 2, '.', ''),
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
