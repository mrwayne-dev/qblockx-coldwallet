<?php
/**
 * Project: qblockx
 * API: User login — checks credentials and is_verified before creating session
 */
ob_start();

require_once '../../config/database.php';
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

try {
    $db   = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }

    // Block login if email is not yet verified
    if (!(bool) $user['is_verified']) {
        ob_end_clean();
        echo json_encode([
            'success'    => false,
            'message'    => 'Please verify your email address before signing in. Check your inbox for the verification link.',
            'unverified' => true
        ]);
        exit;
    }

    // Block login if account has been disabled by admin
    if (isset($user['is_active']) && !(bool) $user['is_active']) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Your account has been disabled. Please contact support.']);
        exit;
    }

    // All good — regenerate session ID to prevent session fixation, then start session
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];

    $resp = json_encode(['success' => true, 'message' => 'Login successful']);
    ob_end_clean();
    header('Content-Type: application/json');
    header('Content-Encoding: identity');
    header('Content-Length: ' . strlen($resp));
    header('Connection: close');
    echo $resp;
    session_write_close();
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        ignore_user_abort(true);
        flush();
    }

    // Send sign-in notification (non-blocking)
    require_once '../../api/utilities/email_templates.php';
    $loginTime = date('D, d M Y \a\t H:i T');
    $firstName = explode(' ', trim($user['full_name'] ?? ''))[0] ?: 'there';
    Mailer::sendUserSignIn($user['email'], $firstName, $loginTime);

} catch (\Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
