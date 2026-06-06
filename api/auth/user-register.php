<?php
/**
 * Project: qblockx
 * API: User registration — creates account + sends email verification code
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

$input     = json_decode(file_get_contents('php://input'), true);
$email     = trim($input['email'] ?? '');
$password  = $input['password'] ?? '';
$full_name = trim($input['full_name'] ?? '');
$currency  = strtoupper(trim($input['currency'] ?? 'USD'));
if (!preg_match('/^[A-Z]{3,5}$/', $currency)) $currency = 'USD';

if (empty($email) || empty($password)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

if (strlen($password) < 8) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit;
}

$db = null;

try {
    $db = Database::getInstance()->getConnection();

    // Check for duplicate email
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }

    $db->beginTransaction();

    // Create user (is_verified defaults to FALSE)
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO users (email, password, full_name) VALUES (:email, :password, :full_name)")
       ->execute(['email' => $email, 'password' => $hashed, 'full_name' => $full_name]);
    $new_user_id = (int) $db->lastInsertId();

    // Create wallet for new user
    $db->prepare("INSERT INTO wallets (user_id, currency) VALUES (:uid, :currency)")
       ->execute(['uid' => $new_user_id, 'currency' => $currency]);

    // Generate 6-digit verification code (15-minute expiry)
    $code       = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $db->prepare(
        "INSERT INTO email_verifications (user_id, token, expires_at) VALUES (:uid, :token, :expires_at)"
    )->execute(['uid' => $new_user_id, 'token' => $code, 'expires_at' => $expires_at]);

    $db->commit();

    // ── Respond immediately — email sends happen after response is flushed ──
    $resp = json_encode([
        'success'    => true,
        'email_sent' => true,
        'message'    => 'Account created! Check your email for your verification code.',
    ]);
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

    // ── Non-blocking email sends ─────────────────────────────────────────────
    Mailer::sendWelcome($email, $full_name);
    Mailer::sendVerification($email, $full_name, $code);

    $adminEmail = getenv('SMTP_FROM') ?: getenv('SMTP_USER') ?: '';
    if ($adminEmail) {
        Mailer::sendAdminNewUser(
            $adminEmail,
            (string) $new_user_id,
            $full_name,
            $email,
            date('F j, Y H:i T'),
            '',
            $_SERVER['REMOTE_ADDR'] ?? '',
            'No'
        );
    }

} catch (\Throwable $e) {
    if ($db !== null && $db->inTransaction()) {
        try { $db->rollBack(); } catch (\Throwable $re) {}
    }
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
