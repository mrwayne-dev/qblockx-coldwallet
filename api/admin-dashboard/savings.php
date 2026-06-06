<?php
/**
 * Project: qblockx
 * API: admin-dashboard/savings.php — Admin savings plans management
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

        $where = $filter ? "WHERE sp.status = " . $db->quote($filter) : '';

        $total = $db->query("SELECT COUNT(*) FROM savings_plans sp $where")->fetchColumn();

        $stmt = $db->prepare(
            "SELECT sp.id, sp.plan_name, sp.target_amount, sp.current_amount,
                    sp.interest_rate, sp.duration_months, sp.status, sp.created_at,
                    u.full_name AS user_name, u.email AS user_email
             FROM savings_plans sp
             JOIN users u ON u.id = sp.user_id
             $where
             ORDER BY sp.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $plans = $stmt->fetchAll();

        $totalSaved  = $db->query("SELECT COALESCE(SUM(current_amount), 0) FROM savings_plans")->fetchColumn();
        $activePlans = $db->query("SELECT COUNT(*) FROM savings_plans WHERE status = 'active'")->fetchColumn();

        echo json_encode([
            'success'       => true,
            'total'         => (int) $total,
            'page'          => $page,
            'pages'         => (int) ceil($total / $limit),
            'total_saved'   => number_format((float) $totalSaved, 2, '.', ''),
            'active_plans'  => (int) $activePlans,
            'plans'         => $plans,
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $input['action'] ?? '';
        $id     = (int) ($input['id'] ?? 0);

        if ($action === 'cancel' && $id > 0) {
            $db->prepare("UPDATE savings_plans SET status = 'cancelled' WHERE id = :id")
               ->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Savings plan cancelled']);

        } elseif ($action === 'adjust' && $id > 0) {
            $amount = (float) ($input['current_amount'] ?? 0);
            $db->prepare("UPDATE savings_plans SET current_amount = :amount WHERE id = :id")
               ->execute(['amount' => $amount, 'id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Balance adjusted']);

        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action or missing ID']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
