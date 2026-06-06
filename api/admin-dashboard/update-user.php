<?php
/**
 * Project: qblockx
 * Created by: Wayne
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

$input  = json_decode(file_get_contents('php://input'), true);
$id     = (int) ($input['id'] ?? $input['user_id'] ?? 0);  // accept either key
$action = trim($input['action'] ?? '');  // 'verify', 'unverify', 'promote', 'demote', 'disable', 'enable', 'delete'

if (!$id || !in_array($action, ['verify', 'unverify', 'promote', 'demote', 'disable', 'enable', 'delete'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Prevent modifying another admin
    $stmt = $db->prepare("SELECT id, role FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    if (in_array($action, ['promote', 'demote']) && $user['role'] !== 'user' && $action === 'promote') {
        echo json_encode(['success' => false, 'message' => 'User is already an admin']);
        exit;
    }

    switch ($action) {
        case 'verify':
            $db->prepare("UPDATE users SET is_verified = 1 WHERE id = :id")->execute(['id' => $id]);
            $msg = 'User verified';
            break;
        case 'unverify':
            $db->prepare("UPDATE users SET is_verified = 0 WHERE id = :id")->execute(['id' => $id]);
            $msg = 'User unverified';
            break;
        case 'promote':
            $db->prepare("UPDATE users SET role = 'admin' WHERE id = :id")->execute(['id' => $id]);
            $msg = 'User promoted to admin';
            break;
        case 'demote':
            $db->prepare("UPDATE users SET role = 'user' WHERE id = :id")->execute(['id' => $id]);
            $msg = 'Admin demoted to user';
            break;
        case 'disable':
            $db->prepare("UPDATE users SET is_active = 0 WHERE id = :id")->execute(['id' => $id]);
            $msg = 'User account disabled';
            break;
        case 'enable':
            $db->prepare("UPDATE users SET is_active = 1 WHERE id = :id")->execute(['id' => $id]);
            $msg = 'User account enabled';
            break;
        case 'delete':
            if ($user['role'] === 'admin') {
                echo json_encode(['success' => false, 'message' => 'Cannot delete an admin account']);
                exit;
            }
            $db->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $id]);
            $msg = 'User deleted';
            break;
    }

    echo json_encode(['success' => true, 'message' => $msg]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
