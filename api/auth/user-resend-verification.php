<?php
/**
 * Project: qblockx
 * API: Resend email verification code
 * Method: POST  { email }
 */
ob_start();

require_once '../../config/database.php';
require_once __DIR__ . '/../../api/utilities/email_templates.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (empty($email)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT id, full_name, is_verified FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    // Return success regardless to prevent enumeration
    if (!$user || (bool) $user['is_verified']) {
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'If your account exists and is unverified, a new code has been sent.']);
        exit;
    }

    $uid = (int) $user['id'];

    // Remove any existing code for this user
    $db->prepare("DELETE FROM email_verifications WHERE user_id = :uid")
       ->execute(['uid' => $uid]);

    // Generate new 6-digit code (15-minute expiry)
    $code       = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $db->prepare(
        "INSERT INTO email_verifications (user_id, token, expires_at) VALUES (:uid, :token, :expires_at)"
    )->execute(['uid' => $uid, 'token' => $code, 'expires_at' => $expires_at]);

    $resp = json_encode(['success' => true, 'message' => 'A new verification code has been sent to your email.']);
    ob_end_clean();
    header('Content-Type: application/json');
    header('Content-Encoding: identity');
    header('Content-Length: ' . strlen($resp));
    header('Connection: close');
    echo $resp;
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        ignore_user_abort(true);
        flush();
    }

    Mailer::sendVerification($email, $user['full_name'] ?? '', $code);

} catch (\Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
