<?php
/**
 * Project: Qblockx
 * API: Admin — Commodity Investments overview
 * GET  → paginated list of all commodity positions
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

    $where = $status ? "WHERE ci.status = " . $db->quote($status) : '';

    $total = $db->query("SELECT COUNT(*) FROM commodity_investments ci $where")->fetchColumn();

    $stmt = $db->prepare(
        "SELECT ci.id, u.email, u.full_name, ci.asset_name, ci.amount,
                ci.yield_rate, ci.starts_at, ci.ends_at,
                ci.expected_return, ci.actual_return, ci.status, ci.created_at
         FROM commodity_investments ci
         JOIN users u ON u.id = ci.user_id
         $where
         ORDER BY ci.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $metrics = $db->query(
        "SELECT COALESCE(SUM(amount), 0) AS total_value,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_count
         FROM commodity_investments"
    )->fetch();

    echo json_encode([
        'success' => true,
        'data'    => [
            'positions'    => $stmt->fetchAll(),
            'total'        => (int) $total,
            'page'         => $page,
            'pages'        => (int) ceil($total / $limit),
            'total_value'  => number_format((float) $metrics['total_value'],  2, '.', ''),
            'active_count' => (int) $metrics['active_count'],
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
