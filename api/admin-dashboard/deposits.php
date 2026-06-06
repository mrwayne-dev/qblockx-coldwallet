<?php
/**
 * Project: qblockx
 * API: admin-dashboard/deposits.php — Admin fixed deposits management
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

try {
    $db = Database::getInstance()->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $filter = $_GET['status'] ?? '';
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $where = $filter ? "WHERE fd.status = " . $db->quote($filter) : '';

        $total = $db->query("SELECT COUNT(*) FROM fixed_deposits fd $where")->fetchColumn();

        $stmt = $db->prepare(
            "SELECT fd.id, fd.amount, fd.interest_rate, fd.duration_months,
                    fd.start_date, fd.maturity_date, fd.expected_return, fd.status, fd.created_at,
                    u.full_name AS user_name, u.email AS user_email
             FROM fixed_deposits fd
             JOIN users u ON u.id = fd.user_id
             $where
             ORDER BY fd.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $deposits = $stmt->fetchAll();

        $totalValue   = $db->query("SELECT COALESCE(SUM(amount), 0) FROM fixed_deposits WHERE status = 'active'")->fetchColumn();
        $totalReturns = $db->query("SELECT COALESCE(SUM(expected_return), 0) FROM fixed_deposits WHERE status = 'active'")->fetchColumn();
        $totalCount   = $db->query("SELECT COUNT(*) FROM fixed_deposits")->fetchColumn();

        echo json_encode([
            'success'        => true,
            'total'          => (int) $total,
            'page'           => $page,
            'pages'          => (int) ceil($total / $limit),
            'total_count'    => (int) $totalCount,
            'total_value'    => number_format((float) $totalValue,   2, '.', ''),
            'total_returns'  => number_format((float) $totalReturns, 2, '.', ''),
            'deposits'       => $deposits,
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $input['action'] ?? '';
        $id     = (int) ($input['id'] ?? 0);

        if ($action === 'mature' && $id > 0) {
            // Mark deposit matured and credit user wallet with expected_return
            $depStmt = $db->prepare("SELECT user_id, expected_return FROM fixed_deposits WHERE id = :id AND status = 'active'");
            $depStmt->execute(['id' => $id]);
            $dep = $depStmt->fetch();

            if (!$dep) { echo json_encode(['success' => false, 'message' => 'Deposit not found or already closed']); exit; }

            $db->beginTransaction();
            $db->prepare("UPDATE fixed_deposits SET status = 'matured' WHERE id = :id")->execute(['id' => $id]);
            $db->prepare("UPDATE wallets SET balance = balance + :amount WHERE user_id = :uid")
               ->execute(['amount' => $dep['expected_return'], 'uid' => $dep['user_id']]);
            $db->prepare("INSERT INTO transactions (user_id, type, amount, status, notes) VALUES (:uid, 'deposit_return', :amount, 'completed', 'Fixed deposit matured')")
               ->execute(['uid' => $dep['user_id'], 'amount' => $dep['expected_return']]);
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Deposit marked matured and return credited']);

        } elseif ($action === 'cancel' && $id > 0) {
            $db->prepare("UPDATE fixed_deposits SET status = 'cancelled' WHERE id = :id")->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Deposit cancelled']);

        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action or missing ID']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
