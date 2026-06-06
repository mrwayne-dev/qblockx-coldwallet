<?php
/**
 * Project: qblockx
 * Admin: Resolve Deposit — manually complete or fail a pending deposit
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once '../../api/utilities/email_templates.php';
header('Content-Type: application/json');

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$id     = (int) ($input['id']     ?? 0);
$action = trim($input['action']   ?? '');

if (!$id || !in_array($action, ['complete', 'fail'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare(
        "SELECT t.*, u.email AS user_email, u.full_name AS user_name
         FROM transactions t
         JOIN users u ON u.id = t.user_id
         WHERE t.id = :id AND t.type = 'deposit' AND t.status = 'pending' LIMIT 1"
    );
    $stmt->execute(['id' => $id]);
    $tx = $stmt->fetch();

    if (!$tx) {
        echo json_encode(['success' => false, 'message' => 'Pending deposit not found']);
        exit;
    }

    $db->beginTransaction();

    if ($action === 'complete') {
        // Mark transaction completed
        $db->prepare("UPDATE transactions SET status = 'completed', updated_at = NOW() WHERE id = :id")
           ->execute(['id' => $id]);

        // Credit wallet
        $db->prepare(
            "INSERT INTO wallets (user_id, balance) VALUES (:uid, :amt)
             ON DUPLICATE KEY UPDATE balance = balance + :amt2, updated_at = NOW()"
        )->execute([
            'uid'  => $tx['user_id'],
            'amt'  => $tx['amount'],
            'amt2' => $tx['amount'],
        ]);

        $msg = 'Deposit completed and wallet credited';

    } else {
        // Mark failed
        $db->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = :id")
           ->execute(['id' => $id]);
        $msg = 'Deposit marked as failed';
    }

    $db->commit();

    $resp = json_encode(['success' => true, 'message' => $msg]);
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

    // Send user notification email (non-blocking)
    try {
        if ($action === 'complete') {
            Mailer::sendDepositApproved(
                $tx['user_email'],
                $tx['user_name'],
                (float) $tx['amount'],
                $tx['currency'] ?: 'USD',
                '',
                $tx['payment_id'] ?? '',
                date('d M Y, H:i T'),
                '',
                (string) $tx['id']
            );
        } else {
            Mailer::sendDepositRejected(
                $tx['user_email'],
                $tx['user_name'],
                (float) $tx['amount'],
                $tx['currency'] ?: 'USD',
                $tx['payment_id'] ?? '',
                date('d M Y, H:i T'),
                'Manual review — deposit could not be verified',
                (string) $tx['id']
            );
        }
    } catch (Exception $mailErr) {
        error_log('resolve-deposit email error for tx ' . $id . ': ' . $mailErr->getMessage());
    }

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('resolve-deposit error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
