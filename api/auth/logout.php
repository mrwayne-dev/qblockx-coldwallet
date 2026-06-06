<?php
/**
 * Project: qblockx
 * Created by: Wayne
 */

session_start();
$userId = $_SESSION['user_id'] ?? null;
$role   = $_SESSION['role']    ?? 'user';

// Send logout notification email (non-fatal, before session_destroy so we still have the user ID)
if ($userId) {
    try {
        require_once '../../config/database.php';
        require_once '../../api/utilities/email_templates.php';
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT email, full_name FROM users WHERE id = :uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        $u = $stmt->fetch();
        if ($u) {
            Mailer::sendLogoutNotification($u['email'], $u['full_name'], date('d M Y, H:i T'));
        }
    } catch (\Throwable $ignored) {}
}

session_destroy();

if ($role === 'admin') {
    header('Location: /admin/login');
} else {
    header('Location: /login?loggedout=1');
}
exit;
