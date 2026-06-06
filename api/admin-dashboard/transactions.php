<?php
/**
 * Project: qblockx
 * Created by: Wayne
 * Generated: 2026-03-09
 * 
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

try {
    $db = Database::getInstance()->getConnection();

    $page   = max(1, (int) ($_GET['page']  ?? 1));
    $limit  = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
    $type   = $_GET['type']   ?? null;
    $status = $_GET['status'] ?? null;
    $offset = ($page - 1) * $limit;

    $conditions = [];
    $params     = [];
    if ($type)   { $conditions[] = "t.type = :type";     $params['type']   = $type; }
    if ($status) { $conditions[] = "t.status = :status"; $params['status'] = $status; }
    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $countStmt = $db->prepare("SELECT COUNT(*) FROM transactions t $where");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Summary metrics (computed on every call — cheap queries)
    $totalVolume     = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'completed'")->fetchColumn();
    $totalDeposits   = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'deposit'   AND status = 'completed'")->fetchColumn();
    $totalWithdrawals= $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'withdrawal' AND status = 'completed'")->fetchColumn();

    $stmt = $db->prepare(
        "SELECT t.id, t.type, t.amount, t.status, t.notes, t.created_at,
                u.email AS user_email, u.full_name AS user_name
         FROM transactions t
         JOIN users u ON u.id = t.user_id
         $where
         ORDER BY t.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue(":$k", $v);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => [
            'transactions' => $transactions,
            'total'        => (int) $total,
            'page'         => $page,
            'limit'        => $limit,
            'pages'        => (int) ceil($total / $limit),
            'summary'      => [
                'total_volume'      => number_format((float) $totalVolume,      2, '.', ''),
                'total_deposits'    => number_format((float) $totalDeposits,    2, '.', ''),
                'total_withdrawals' => number_format((float) $totalWithdrawals, 2, '.', ''),
            ],
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
