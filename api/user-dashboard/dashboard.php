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
            echo json_encode(['success' => true, 'message' => 'Ticket created — ' . $ticketRef]);
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

    echo json_encode([
        'success' => true,
        'data'    => [
            'user'                => $user,
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
