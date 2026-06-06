<?php
/**
 * Project: qblockx
 * Utility: api/utilities/contact.php
 *
 * Handles the public contact form submission.
 * Accepts native HTML form POST ($_POST) and sends an email to support.
 * Redirects back to /contact?success=1 on success or /contact?error=1 on failure.
 */

require_once '../../config/env.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

loadEnv(dirname(__DIR__, 2) . '/.env');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: /contact');
    exit;
}

// Read form fields — native HTML form sends $_POST
$firstName   = htmlspecialchars(trim($_POST['first_name']   ?? ''), ENT_QUOTES, 'UTF-8');
$lastName    = htmlspecialchars(trim($_POST['last_name']    ?? ''), ENT_QUOTES, 'UTF-8');
$email       = filter_var(trim($_POST['email']      ?? ''), FILTER_VALIDATE_EMAIL);
$problemType = htmlspecialchars(trim($_POST['problem_type'] ?? 'General Enquiry'), ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars(trim($_POST['description']  ?? ''), ENT_QUOTES, 'UTF-8');

$fullName = trim($firstName . ' ' . $lastName);

if (!$fullName || !$email || !$description) {
    header('Location: /contact?error=missing_fields');
    exit;
}

// Map problem_type to readable subject
$subjectMap = [
    'account-access' => 'Account Access Issue',
    'payment-issue'  => 'Payment / Transaction Issue',
    'data-correction'=> 'Incorrect or Missing Information',
    'password-issue' => 'Password Issue',
    'security'       => 'Security Concern',
    'other'          => 'General Enquiry',
];
$subjectLabel = $subjectMap[$problemType] ?? $problemType;

try {
    $port = (int) (getenv('SMTP_PORT') ?: 587);

    $encryption = ($port === 465)
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = getenv('SMTP_HOST') ?: '';
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('SMTP_USER') ?: '';
    $mail->Password   = getenv('SMTP_PASS') ?: '';
    $mail->SMTPSecure = $encryption;
    $mail->Port       = $port;
    $mail->CharSet    = 'UTF-8';

    $fromAddr = getenv('SMTP_FROM')      ?: 'noreply@crestvalebank.com';
    $fromName = getenv('SMTP_FROM_NAME') ?: (getenv('APP_NAME') ?: 'CrestVale Bank');
    $toAddr   = getenv('SMTP_USER')      ?: $fromAddr;

    $mail->setFrom($fromAddr, $fromName);
    $mail->addAddress($toAddr);
    $mail->addReplyTo($email, $fullName);

    $mail->isHTML(true);
    $mail->Subject = '[Contact Form] ' . $subjectLabel . ' — ' . $fullName;
    $mail->Body    = '<!DOCTYPE html><html><body style="font-family:sans-serif;color:#222;max-width:600px;margin:auto;padding:24px">'
                   . '<h2 style="border-bottom:2px solid #3FE0A1;padding-bottom:8px;">New Contact Form Submission</h2>'
                   . '<p><strong>Name:</strong> ' . $fullName . '</p>'
                   . '<p><strong>Email:</strong> <a href="mailto:' . $email . '">' . $email . '</a></p>'
                   . '<p><strong>Subject:</strong> ' . $subjectLabel . '</p>'
                   . '<hr style="border-color:#eee">'
                   . '<p><strong>Message:</strong></p>'
                   . '<p style="background:#f5f5f7;padding:16px;border-radius:8px;">' . nl2br($description) . '</p>'
                   . '</body></html>';
    $mail->AltBody = "Name: {$fullName}\nEmail: {$email}\nSubject: {$subjectLabel}\n\nMessage:\n{$description}";

    $mail->send();

    header('Location: /contact?success=1');
    exit;

} catch (\Throwable $e) {
    error_log('[Contact Form] Failed: ' . $e->getMessage());
    header('Location: /contact?error=send_failed');
    exit;
}
