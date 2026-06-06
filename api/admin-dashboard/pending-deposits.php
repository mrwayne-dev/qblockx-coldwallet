<?php
/**
 * Project: qblockx
 * Admin: Pending Deposits — list unresolved deposit transactions
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

try {
    $db = Database::getInstance()->getConnection();

    $page   = max(1, (int) ($_GET['page']  ?? 1));
    $limit  = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $total = $db->query(
        "SELECT COUNT(*) FROM transactions WHERE type = 'deposit' AND status = 'pending'"
    )->fetchColumn();

    $stmt = $db->prepare(
        "SELECT t.id, t.amount, t.currency, t.payment_id, t.notes, t.created_at,
                u.email AS user_email, u.full_name AS user_name
         FROM transactions t
         JOIN users u ON u.id = t.user_id
         WHERE t.type = 'deposit' AND t.status = 'pending'
         ORDER BY t.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $deposits = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => [
            'deposits' => $deposits,
            'total'    => (int) $total,
            'page'     => $page,
            'limit'    => $limit,
            'pages'    => (int) ceil($total / $limit),
        ]
    ]);

} catch (PDOException $e) {
    error_log('pending-deposits error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
