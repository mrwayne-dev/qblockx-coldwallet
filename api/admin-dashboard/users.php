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

    $page  = max(1, (int) ($_GET['page']  ?? 1));
    $limit = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $total = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

    $stmt = $db->prepare(
        "SELECT u.id, u.email, u.full_name, u.role, u.is_verified, u.created_at,
                COALESCE(w.balance, 0) AS balance
         FROM users u
         LEFT JOIN wallets w ON w.user_id = u.id
         WHERE u.role = 'user'
         ORDER BY u.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => [
            'users'      => $users,
            'total'      => (int) $total,
            'page'       => $page,
            'limit'      => $limit,
            'pages'      => (int) ceil($total / $limit),
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
