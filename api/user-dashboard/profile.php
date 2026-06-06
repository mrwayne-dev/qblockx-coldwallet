<?php
/**
 * Project: qblockx
 * Created by: Wayne
 */
ob_start();

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once '../../api/utilities/email_templates.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

try {
    $db = Database::getInstance()->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->prepare("SELECT id, email, full_name, is_verified, is_active, role, created_at FROM users WHERE id = :uid");
        $stmt->execute(['uid' => $user['id']]);
        $profile = $stmt->fetch();

        echo json_encode(['success' => true, 'data' => $profile]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input     = json_decode(file_get_contents('php://input'), true);
        $full_name = trim($input['full_name']    ?? '');
        $password  = $input['password']          ?? '';
        $new_pass  = $input['new_password']      ?? '';

        $updates = [];
        $params  = ['uid' => $user['id']];

        if (!empty($full_name)) {
            $updates[]         = 'full_name = :full_name';
            $params['full_name'] = $full_name;
        }

        if (!empty($new_pass)) {
            if (strlen($new_pass) < 8) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters']);
                exit;
            }
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM users WHERE id = :uid");
            $stmt->execute(['uid' => $user['id']]);
            $row = $stmt->fetch();
            if (!password_verify($password, $row['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            $updates[]          = 'password = :password';
            $params['password'] = password_hash($new_pass, PASSWORD_DEFAULT);
        }

        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No changes provided']);
            exit;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :uid';
        $db->prepare($sql)->execute($params);

        $resp = json_encode(['success' => true, 'message' => 'Profile updated']);
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

        // Send password changed security notification (non-blocking)
        if (!empty($new_pass)) {
            try {
                $infoStmt = $db->prepare("SELECT email, full_name FROM users WHERE id = :uid LIMIT 1");
                $infoStmt->execute(['uid' => $user['id']]);
                $info = $infoStmt->fetch();
                if ($info) {
                    Mailer::sendPasswordChanged(
                        $info['email'],
                        $info['full_name'],
                        date('d M Y, H:i T'),
                        $info['email']
                    );
                }
            } catch (Exception $mailErr) {
                error_log('profile password-change email error: ' . $mailErr->getMessage());
            }
        }

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
