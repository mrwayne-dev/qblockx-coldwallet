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
    $status = $_GET['status'] ?? 'pending';
    $offset = ($page - 1) * $limit;

    $where  = $status !== 'all' ? "WHERE wr.status = :status" : "";
    $params = $status !== 'all' ? ['status' => $status] : [];

    $countStmt = $db->prepare("SELECT COUNT(*) FROM withdrawal_requests wr $where");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    $stmt = $db->prepare(
        "SELECT wr.id, wr.amount, wr.currency, wr.wallet_address,
                wr.withdrawal_method, wr.fee,
                wr.bank_country, wr.bank_name, wr.account_holder_name,
                wr.iban, wr.bic_swift, wr.sort_code, wr.bank_currency, wr.transaction_reference,
                wr.status, wr.admin_notes, wr.created_at, wr.updated_at,
                u.id AS user_id, u.email AS user_email, u.full_name AS user_name
         FROM withdrawal_requests wr
         JOIN users u ON u.id = wr.user_id
         $where
         ORDER BY wr.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue(":$k", $v);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => [
            'requests' => $requests,
            'total'    => (int) $total,
            'page'     => $page,
            'limit'    => $limit,
            'pages'    => (int) ceil($total / $limit),
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
