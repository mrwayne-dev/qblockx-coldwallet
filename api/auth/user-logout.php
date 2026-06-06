<?php
/**
 * Project: qblockx
 * Created by: Wayne
 */

session_start();

$userId = $_SESSION['user_id'] ?? null;
$email  = $_SESSION['email']   ?? null;

session_destroy();

// Send logout notification before redirect (non-fatal)
if ($userId && $email) {
    try {
        require_once '../../config/database.php';
        require_once '../../api/utilities/email_templates.php';
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT full_name FROM users WHERE id = :uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        $row  = $stmt->fetch();
        Mailer::sendLogoutNotification($email, $row['full_name'] ?? 'User', date('d M Y, H:i T'));
    } catch (Exception $e) {
        error_log('user-logout email error: ' . $e->getMessage());
    }
}

header('Location: /login');
exit;
