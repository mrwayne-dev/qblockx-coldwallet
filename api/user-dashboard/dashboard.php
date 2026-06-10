<?php
/**
 * Quantum BlocX — API: user-dashboard/dashboard.php
 * GET  → Overview data (user, card_tier, notifications)
 * POST → action=create_ticket
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAuth();
$auth = getAuthUser();

try {
    $db  = Database::getInstance()->getConnection();
    $uid = $auth['id'];

    // ── POST: action dispatcher ──────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $_GET['action'] ?? ($input['action'] ?? '');

        if ($action === 'create_ticket') {
            $subject = trim($input['subject'] ?? '');
            $body    = trim($input['body'] ?? '');
            if (!$subject || !$body) {
                echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
                exit;
            }
            // Generate ticket reference
            $countStmt = $db->query("SELECT COUNT(*) FROM support_tickets");
            $ticketNum = (int) $countStmt->fetchColumn() + 1;
            $ticketRef = 'TKT-' . str_pad($ticketNum, 6, '0', STR_PAD_LEFT);

            $stmt = $db->prepare(
                "INSERT INTO support_tickets (user_id, ticket_ref, subject, body)
                 VALUES (:uid, :ref, :subject, :body)"
            );
            $stmt->execute([
                'uid'     => $uid,
                'ref'     => $ticketRef,
                'subject' => $subject,
                'body'    => $body,
            ]);

            // Respond immediately; email admins after the response is flushed
            $resp = json_encode(['success' => true, 'message' => 'Ticket created — ' . $ticketRef]);
            header('Content-Length: ' . strlen($resp));
            header('Connection: close');
            echo $resp;
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                ignore_user_abort(true);
                flush();
            }

            // ── Notify admins (all role=admin + SMTP_FROM) ───────────────────
            try {
                require_once '../../api/utilities/email_templates.php';

                $recipients = $db->query("SELECT email FROM users WHERE role = 'admin' AND email IS NOT NULL AND email <> ''")
                                 ->fetchAll(PDO::FETCH_COLUMN);
                $smtpFrom = getenv('SMTP_FROM') ?: getenv('SMTP_USER') ?: '';
                if ($smtpFrom) $recipients[] = $smtpFrom;
                $recipients = array_values(array_unique(array_filter(array_map('strtolower', $recipients))));

                $uStmt = $db->prepare("SELECT email, full_name FROM users WHERE id = :uid LIMIT 1");
                $uStmt->execute(['uid' => $uid]);
                $usr = $uStmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $fromName  = $usr['full_name'] ?? 'User';
                $fromEmail = $usr['email'] ?? '';
                $appUrl    = rtrim(getenv('APP_URL') ?: 'https://qblockx.com', '/');

                $html = '<div style="font-family:Arial,Helvetica,sans-serif;background:#F7F8FC;padding:28px 16px;">'
                    . '<table align="center" width="520" cellpadding="0" cellspacing="0" style="background:#fff;border:1px solid #D9DEEA;border-radius:12px;">'
                    . '<tr><td style="padding:30px 34px;">'
                    . '<span style="font-size:19px;font-weight:700;color:#030B1D;">Qblockx Admin</span>'
                    . '<h1 style="font-size:20px;color:#030B1D;margin:20px 0 6px;">New support ticket ' . htmlspecialchars($ticketRef) . '</h1>'
                    . '<p style="font-size:14px;color:#4A4F5F;margin:0 0 4px;"><strong>From:</strong> ' . htmlspecialchars($fromName) . ' (' . htmlspecialchars($fromEmail) . ')</p>'
                    . '<p style="font-size:14px;color:#4A4F5F;margin:0 0 14px;"><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>'
                    . '<div style="background:#F2F4FF;border-radius:8px;padding:14px 16px;font-size:14px;color:#030B1D;line-height:1.6;white-space:pre-wrap;">' . htmlspecialchars($body) . '</div>'
                    . '<a href="' . $appUrl . '/admin/dashboard" style="display:inline-block;margin-top:20px;background:#2262FF;color:#fff;text-decoration:none;padding:11px 20px;border-radius:8px;font-weight:600;">Reply in admin panel</a>'
                    . '</td></tr></table></div>';

                foreach ($recipients as $to) {
                    Mailer::send($to, 'Admin', 'New support ticket ' . $ticketRef . ' — Qblockx', $html);
                }
            } catch (\Throwable $mailErr) {
                error_log('create_ticket admin email error: ' . $mailErr->getMessage());
            }
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
    }

    // ── GET: dashboard overview data ─────────────────────────────────

    // User info
    $userStmt = $db->prepare(
        "SELECT id, email, full_name, username, kyc_status, card_tier,
                two_fa_enabled, is_verified, is_active, current_ip,
                created_at, last_login_at
         FROM users WHERE id = :uid"
    );
    $userStmt->execute(['uid' => $uid]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Recent transactions (last 10)
    $txStmt = $db->prepare(
        "SELECT t.type, t.amount, t.amount_usd, t.currency_symbol, t.status,
                t.recipient_address, t.tx_hash, t.created_at
         FROM transactions t
         WHERE t.user_id = :uid
         ORDER BY t.created_at DESC LIMIT 10"
    );
    $txStmt->execute(['uid' => $uid]);
    $recentTx = $txStmt->fetchAll(PDO::FETCH_ASSOC);

    // Notifications (last 20, newest first)
    $notifStmt = $db->prepare(
        "SELECT id, type, title, message, action_url, is_read, created_at
         FROM notifications
         WHERE user_id = :uid
         ORDER BY created_at DESC LIMIT 20"
    );
    $notifStmt->execute(['uid' => $uid]);
    $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);

    // Unread count
    $unreadStmt = $db->prepare(
        "SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0"
    );
    $unreadStmt->execute(['uid' => $uid]);
    $unreadCount = (int) $unreadStmt->fetchColumn();

    // Support tickets count
    $ticketStmt = $db->prepare(
        "SELECT COUNT(*) FROM support_tickets WHERE user_id = :uid AND status NOT IN ('closed','resolved')"
    );
    $ticketStmt->execute(['uid' => $uid]);
    $openTickets = (int) $ticketStmt->fetchColumn();

    // Active QFS card (if any)
    $cardStmt = $db->prepare(
        "SELECT id, card_tier, card_number_masked, card_type, issuer, status,
                cashback_pct, activated_at, expires_at
         FROM virtual_cards
         WHERE user_id = :uid AND status = 'active'
         ORDER BY activated_at DESC LIMIT 1"
    );
    $cardStmt->execute(['uid' => $uid]);
    $card = $cardStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    echo json_encode([
        'success' => true,
        'data'    => [
            'user'                => $user,
            'card'                => $card,
            'recent_transactions' => $recentTx,
            'notifications'       => $notifications,
            'unread_notifications' => $unreadCount,
            'open_tickets'        => $openTickets,
        ]
    ]);

} catch (PDOException $e) {
    error_log('dashboard.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
