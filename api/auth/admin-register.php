<?php
/**
 * Project: qblockx
 * API: Admin Registration — invite-code gated, creates role=admin user
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

$input       = json_decode(file_get_contents('php://input'), true);
$full_name   = trim($input['full_name']   ?? '');
$email       = trim($input['email']       ?? '');
$password    = $input['password']         ?? '';
$invite_code = trim($input['invite_code'] ?? '');

// ── Validate invite code ──────────────────────────────────────────────
$validCode = getenv('ADMIN_INVITE_CODE');
if (empty($validCode) || !hash_equals($validCode, $invite_code)) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid invite code.']);
    exit;
}

// ── Validate required fields ──────────────────────────────────────────
if (empty($email) || empty($password)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (strlen($password) < 8) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Check for duplicate email
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Email is already registered.']);
        exit;
    }

    // Create admin user
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $db->prepare(
        "INSERT INTO users (email, password, full_name, role, is_verified)
         VALUES (:email, :password, :full_name, 'admin', TRUE)"
    )->execute([
        'email'     => $email,
        'password'  => $hashed,
        'full_name' => $full_name ?: null,
    ]);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Admin account created successfully.',
    ]);

} catch (\Throwable $e) {
    ob_end_clean();
    error_log('Admin register error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
