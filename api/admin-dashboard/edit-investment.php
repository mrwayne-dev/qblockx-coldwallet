<?php
/**
 * Project: qblockx
 * Admin: Edit Investment — update trade record details
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id    = (int) ($input['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid investment ID']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT id FROM investments WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Investment not found']);
        exit;
    }

    $setParts = [];
    $params   = ['id' => $id];

    if (isset($input['amount']) && is_numeric($input['amount'])) {
        $setParts[]       = 'amount = :amount';
        $params['amount'] = max(0, (float) $input['amount']);
    }
    if (isset($input['daily_rate']) && is_numeric($input['daily_rate'])) {
        $setParts[]            = 'daily_rate = :daily_rate';
        $params['daily_rate']  = min(1, max(0, (float) $input['daily_rate']));
    }
    if (isset($input['total_earned']) && is_numeric($input['total_earned'])) {
        $setParts[]              = 'total_earned = :total_earned';
        $params['total_earned']  = max(0, (float) $input['total_earned']);
    }
    if (isset($input['status']) && in_array($input['status'], ['active', 'completed', 'cancelled'], true)) {
        $setParts[]       = 'status = :status';
        $params['status'] = $input['status'];
    }
    if (!empty($input['starts_at'])) {
        $setParts[]          = 'starts_at = :starts_at';
        $params['starts_at'] = $input['starts_at'];
    }
    if (!empty($input['ends_at'])) {
        $setParts[]        = 'ends_at = :ends_at';
        $params['ends_at'] = $input['ends_at'];
    }

    if (!$setParts) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }

    $db->prepare("UPDATE investments SET " . implode(', ', $setParts) . " WHERE id = :id")
       ->execute($params);

    // Return updated record
    $row = $db->prepare(
        "SELECT i.*, u.email AS user_email, u.full_name AS user_name
         FROM investments i JOIN users u ON u.id = i.user_id
         WHERE i.id = :id LIMIT 1"
    );
    $row->execute(['id' => $id]);
    $updated = $row->fetch();

    echo json_encode(['success' => true, 'message' => 'Investment updated', 'data' => $updated]);

} catch (PDOException $e) {
    error_log('edit-investment error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
