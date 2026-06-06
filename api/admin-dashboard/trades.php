<?php
/**
 * Project: qblockx
 * Created by: Wayne
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

try {
    $db = Database::getInstance()->getConnection();

    $page   = max(1, (int) ($_GET['page']   ?? 1));
    $limit  = max(1, min(100, (int) ($_GET['limit']  ?? 20)));
    $status = $_GET['status'] ?? null;
    $offset = ($page - 1) * $limit;

    $where  = $status ? "WHERE i.status = :status" : "";
    $params = $status ? ['status' => $status] : [];

    $total = $db->prepare("SELECT COUNT(*) FROM investments i $where");
    $total->execute($params);
    $total = $total->fetchColumn();

    $stmt = $db->prepare(
        "SELECT i.id, i.plan_name, i.amount, i.daily_rate, i.duration_days,
                i.total_earned, i.status, i.starts_at, i.ends_at, i.created_at,
                u.email AS user_email, u.full_name AS user_name
         FROM investments i
         JOIN users u ON u.id = i.user_id
         $where
         ORDER BY i.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue(":$k", $v);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $trades = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => [
            'trades' => $trades,
            'total'  => (int) $total,
            'page'   => $page,
            'limit'  => $limit,
            'pages'  => (int) ceil($total / $limit),
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
