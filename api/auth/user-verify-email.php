<?php
/**
 * Project: qblockx
 * API: Verify email address via 6-digit code
 * Method: POST  { email, code }
 */
ob_start();

require_once '../../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$code  = trim($input['code']  ?? '');

if (empty($email) || empty($code)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and verification code are required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Look up user by email
    $stmt = $db->prepare("SELECT id, is_verified FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    // Generic message prevents email enumeration
    if (!$user) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid verification code.']);
        exit;
    }

    if ((bool) $user['is_verified']) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'This email is already verified. Please sign in.']);
        exit;
    }

    $uid = (int) $user['id'];

    // Look up the code scoped to this user
    $stmt = $db->prepare(
        "SELECT expires_at FROM email_verifications WHERE user_id = :uid AND token = :code"
    );
    $stmt->execute(['uid' => $uid, 'code' => $code]);
    $row = $stmt->fetch();

    if (!$row) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid or expired code.']);
        exit;
    }

    if (strtotime($row['expires_at']) < time()) {
        $db->prepare("DELETE FROM email_verifications WHERE user_id = :uid")
           ->execute(['uid' => $uid]);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Code has expired. Please request a new one.']);
        exit;
    }

    // Mark user as verified and clean up code
    $db->prepare("UPDATE users SET is_verified = TRUE WHERE id = :id")
       ->execute(['id' => $uid]);

    $db->prepare("DELETE FROM email_verifications WHERE user_id = :uid")
       ->execute(['uid' => $uid]);

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Email verified! Redirecting to sign in…']);

} catch (\Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
