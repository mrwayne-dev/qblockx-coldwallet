<?php
/**
 * Project: qblockx
 * Admin: Send direct email to a user
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once '../../api/utilities/email_templates.php';
header('Content-Type: application/json');

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$userId  = (int) ($input['user_id'] ?? 0);
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}
if ($subject === '') {
    echo json_encode(['success' => false, 'message' => 'Subject is required']);
    exit;
}
if ($message === '') {
    echo json_encode(['success' => false, 'message' => 'Message body is required']);
    exit;
}
if (mb_strlen($subject) > 200) {
    echo json_encode(['success' => false, 'message' => 'Subject is too long (200 characters max)']);
    exit;
}
if (mb_strlen($message) > 5000) {
    echo json_encode(['success' => false, 'message' => 'Message is too long (5000 characters max)']);
    exit;
}

try {
    $db   = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, email, full_name FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $sent = Mailer::sendAdminMessage(
        $user['email'],
        $user['full_name'] ?: $user['email'],
        $subject,
        $message
    );

    if ($sent) {
        echo json_encode(['success' => true, 'message' => 'Email sent to ' . $user['email']]);
    } else {
        $detail = Mailer::getLastError();
        echo json_encode(['success' => false, 'message' => 'Failed to send email. ' . ($detail ?: 'Check SMTP settings.')]);
    }

} catch (PDOException $e) {
    error_log('send-user-mail error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
