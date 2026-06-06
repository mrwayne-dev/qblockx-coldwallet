<?php
/**
 * Project: qblockx
 * SMTP mail test — DELETE or restrict access before going live
 *
 * Usage: visit  /api/utilities/mail-test.php?to=you@example.com
 */
ob_start();

require_once __DIR__ . '/../../config/database.php'; // loads env.php
require_once __DIR__ . '/../../vendor/autoload.php';
header('Content-Type: application/json');

$to = trim($_GET['to'] ?? '');
if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Provide ?to=valid@email.com']);
    exit;
}

$port = (int) (getenv('SMTP_PORT') ?: 587);

$config = [
    'SMTP_HOST'      => getenv('SMTP_HOST'),
    'SMTP_PORT'      => $port,
    'SMTP_SECURE'    => $port === 465 ? 'ssl (SMTPS)' : 'tls (STARTTLS)',
    'SMTP_USER'      => getenv('SMTP_USER'),
    'SMTP_FROM'      => getenv('SMTP_FROM'),
    'SMTP_FROM_NAME' => getenv('SMTP_FROM_NAME'),
    'APP_URL'        => getenv('APP_URL'),
    'APP_NAME'       => getenv('APP_NAME'),
];

$error = null;

try {
    $encryption = ($port === 465)
        ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

    $mail           = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = getenv('SMTP_HOST') ?: '';
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('SMTP_USER') ?: '';
    $mail->Password   = getenv('SMTP_PASS') ?: '';
    $mail->SMTPSecure = $encryption;
    $mail->Port       = $port;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(
        getenv('SMTP_FROM')      ?: 'support@qblockx.com',
        getenv('SMTP_FROM_NAME') ?: 'Qblockx'
    );
    $mail->addAddress($to, 'Test User');
    $mail->isHTML(false);
    $mail->Subject = 'Qblockx — SMTP Test';
    $mail->Body    = 'This is a test email from Qblockx. If you received this, SMTP is working correctly.';

    $mail->send();
    $ok = true;

} catch (\Throwable $e) {
    $ok    = false;
    $error = $e->getMessage();
}

ob_end_clean();
echo json_encode([
    'success'     => $ok,
    'message'     => $ok ? 'Email sent successfully — check your inbox!' : 'Send failed',
    'error'       => $error,
    'smtp_config' => $config,
], JSON_PRETTY_PRINT);
