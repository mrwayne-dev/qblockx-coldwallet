<?php
/**
 * Project: qblockx
 * Created by: Wayne
 */
ob_start();

require_once '../../config/database.php';
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

try {
    $db   = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND role = 'admin'");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password']) && (!isset($user['is_active']) || (bool) $user['is_active'])) {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];
        $resp = json_encode(['success' => true, 'message' => 'Login successful']);
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

        // Send admin sign-in security alert (non-blocking)
        require_once '../../api/utilities/email_templates.php';
        $loginTime = date('D, d M Y \a\t H:i T');
        $firstName = explode(' ', trim($user['full_name'] ?? ''))[0] ?: 'Admin';
        Mailer::sendAdminSignIn($user['email'], $firstName, $loginTime);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
