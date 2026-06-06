<?php
/**
 * Project: qblockx
 * Admin: Edit User — update personal info, balance, referral commission
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
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Verify user exists
    $stmt = $db->prepare("SELECT id, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $db->beginTransaction();

    // ── Update user core fields ─────────────────────────────────────
    $setParts = [];
    $params   = ['id' => $id];

    if (isset($input['full_name'])) {
        $setParts[]          = 'full_name = :full_name';
        $params['full_name'] = trim($input['full_name']);
    }
    if (isset($input['is_verified'])) {
        $setParts[]             = 'is_verified = :is_verified';
        $params['is_verified']  = $input['is_verified'] ? 1 : 0;
    }
    if (isset($input['role']) && in_array($input['role'], ['user', 'admin'], true)) {
        $setParts[]     = 'role = :role';
        $params['role'] = $input['role'];
    }

    if ($setParts) {
        $db->prepare("UPDATE users SET " . implode(', ', $setParts) . " WHERE id = :id")
           ->execute($params);
    }

    // ── Direct balance override ─────────────────────────────────────
    if (isset($input['balance_override']) && is_numeric($input['balance_override'])) {
        $newBalance = max(0, (float) $input['balance_override']);

        // Upsert wallet
        $db->prepare(
            "INSERT INTO wallets (user_id, balance) VALUES (:uid, :bal)
             ON DUPLICATE KEY UPDATE balance = :bal2, updated_at = NOW()"
        )->execute(['uid' => $id, 'bal' => $newBalance, 'bal2' => $newBalance]);

        // Record admin adjustment transaction for audit trail
        $db->prepare(
            "INSERT INTO transactions (user_id, type, amount, currency, status, notes)
             VALUES (:uid, 'deposit', :amt, 'USD', 'completed', 'Qblockx Fund')"
        )->execute(['uid' => $id, 'amt' => $newBalance]);
    }

    $db->commit();

    // Return updated user info
    $updated = $db->prepare(
        "SELECT u.id, u.email, u.full_name, u.is_verified, u.role, u.created_at,
                COALESCE(w.balance, 0) AS balance
         FROM users u
         LEFT JOIN wallets w ON w.user_id = u.id
         WHERE u.id = :id LIMIT 1"
    );
    $updated->execute(['id' => $id]);
    $updatedUser = $updated->fetch();

    echo json_encode(['success' => true, 'message' => 'User updated', 'data' => $updatedUser]);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('edit-user error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
