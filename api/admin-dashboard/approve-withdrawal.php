<?php
/**
 * Project: qblockx
 * API: Approve or reject a pending withdrawal request (admin only)
 */
ob_start();

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once __DIR__ . '/../../api/utilities/email_templates.php';
header('Content-Type: application/json');

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$id     = (int) ($input['id']     ?? 0);
$action = trim($input['action']   ?? '');  // 'approve' or 'reject'
$notes  = trim($input['notes']    ?? '');

if (!$id || !in_array($action, ['approve', 'reject'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT * FROM withdrawal_requests WHERE id = :id AND status = 'pending'");
    $stmt->execute(['id' => $id]);
    $request = $stmt->fetch();

    if (!$request) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Withdrawal request not found or already processed']);
        exit;
    }

    // Fetch user email + name for notification
    $userStmt = $db->prepare("SELECT email, full_name FROM users WHERE id = :uid");
    $userStmt->execute(['uid' => $request['user_id']]);
    $user = $userStmt->fetch();

    $db->beginTransaction();

    $newStatus = $action === 'approve' ? 'approved' : 'rejected';

    $db->prepare(
        "UPDATE withdrawal_requests SET status = :status, admin_notes = :notes, updated_at = NOW() WHERE id = :id"
    )->execute(['status' => $newStatus, 'notes' => $notes, 'id' => $id]);

    if ($action === 'reject') {
        // Refund balance back to wallet
        $db->prepare(
            "UPDATE wallets SET balance = balance + :amount, updated_at = NOW() WHERE user_id = :user_id"
        )->execute(['amount' => $request['amount'], 'user_id' => $request['user_id']]);

        // Create refund transaction record
        $db->prepare(
            "INSERT INTO transactions (user_id, type, amount, currency, status, notes)
             VALUES (:user_id, 'deposit', :amount, :currency, 'completed', 'Withdrawal rejected — funds returned')"
        )->execute([
            'user_id'  => $request['user_id'],
            'amount'   => $request['amount'],
            'currency' => $request['currency'],
        ]);
    }

    $db->commit();

    // ── Send notification email (non-fatal — DB operation already succeeded) ──
    if ($user) {
        $formattedAmount  = number_format((float) $request['amount'], 2) . ' ' . strtoupper($request['currency'] ?? 'USD');
        $walletAddress    = $request['wallet_address'] ?? 'N/A';

        if ($action === 'approve') {
            Mailer::sendWithdrawalConfirmed(
                $user['email'],
                $user['full_name'] ?? '',
                $formattedAmount,
                $walletAddress,
                $request['tx_hash'] ?? ''
            );
        } else {
            $submittedDate = isset($request['created_at'])
                ? date('F j, Y', strtotime($request['created_at']))
                : date('F j, Y');

            Mailer::sendWithdrawalRejected(
                $user['email'],
                $user['full_name'] ?? '',
                $formattedAmount,
                $walletAddress,
                $submittedDate,
                $notes
            );
        }
    }

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Withdrawal request ' . $newStatus]);

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        try { $db->rollBack(); } catch (\Throwable $re) {}
    }
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
