<?php
/**
 * Project: qblockx
 * Created by: Wayne
 * Generated: 2026-03-09
 * 
 */

// NOWPayments IPN webhook handler
header('Content-Type: application/json');

// Only POST requests carry a payload
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../../config/database.php';
require_once '../../config/env.php';

loadEnv(__DIR__ . '/../../.env');

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';

// Verify HMAC-SHA512 signature
$ipnSecret = getenv('NOWPAYMENTS_IPN_SECRET');
if ($ipnSecret) {
    $data_sorted = json_decode($payload, true);
    if (!is_array($data_sorted)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid payload']);
        exit;
    }
    ksort($data_sorted);
    $expected = hash_hmac('sha512', json_encode($data_sorted), $ipnSecret);
    if (!hash_equals($expected, $signature)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid signature']);
        exit;
    }
}

$data       = json_decode($payload, true);
$order_id   = (string) ($data['order_id']   ?? '');
// invoice_id is what we stored in transactions.payment_id; payment_id is the
// actual payment ID assigned when the user pays — try both as fallback.
$payment_id = (string) ($data['invoice_id'] ?? $data['payment_id'] ?? '');
$status     = $data['payment_status'] ?? '';

if (empty($order_id) && empty($payment_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing payment identifier']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // ── Two-step transaction lookup ─────────────────────────────────────
    // Primary: match our own order ID stored in transactions.notes
    $transaction = null;
    if (!empty($order_id)) {
        $s = $db->prepare(
            "SELECT * FROM transactions WHERE notes = :oid AND type = 'deposit' LIMIT 1"
        );
        $s->execute(['oid' => $order_id]);
        $transaction = $s->fetch() ?: null;
    }

    // Fallback: match invoice_id / payment_id stored in transactions.payment_id
    if (!$transaction && !empty($payment_id)) {
        $s = $db->prepare(
            "SELECT * FROM transactions WHERE payment_id = :pid AND type = 'deposit' LIMIT 1"
        );
        $s->execute(['pid' => $payment_id]);
        $transaction = $s->fetch() ?: null;
    }

    if (!$transaction) {
        // Unknown payment — log and acknowledge so NOWPayments stops retrying
        error_log('Webhook: no transaction found for order_id=' . $order_id . ' payment_id=' . $payment_id);
        http_response_code(200);
        echo json_encode(['success' => true]);
        exit;
    }

    // Map NOWPayments statuses
    // finished / confirmed = completed; failed / expired = failed; others = pending
    if (in_array($status, ['finished', 'confirmed'])) {
        if ($transaction['status'] !== 'completed') {
            $db->beginTransaction();

            $db->prepare("UPDATE transactions SET status = 'completed', updated_at = NOW() WHERE id = :id")
               ->execute(['id' => $transaction['id']]);

            // Ensure wallet row exists, then credit balance
            $db->prepare(
                "INSERT INTO wallets (user_id, balance) VALUES (:uid, :amt)
                 ON DUPLICATE KEY UPDATE balance = balance + :amt2, updated_at = NOW()"
            )->execute([
                'uid'  => $transaction['user_id'],
                'amt'  => $transaction['amount'],
                'amt2' => $transaction['amount'],
            ]);

            $db->commit();

            // Send deposit confirmed email (non-fatal — log errors, never abort)
            try {
                require_once __DIR__ . '/../../api/utilities/email_templates.php';
                $stmt2 = $db->prepare(
                    "SELECT email, full_name FROM users WHERE id = :uid LIMIT 1"
                );
                $stmt2->execute(['uid' => $transaction['user_id']]);
                $usr = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($usr) {
                    Mailer::sendDepositConfirmed(
                        $usr['email'],
                        $usr['full_name'] ?? '',
                        (string) $transaction['amount'],
                        $transaction['currency'] ?? 'crypto',
                        $payment_id
                    );
                }
            } catch (\Throwable $emailErr) {
                error_log('Webhook email error: ' . $emailErr->getMessage());
            }
        }
    } elseif (in_array($status, ['failed', 'expired', 'refunded'])) {
        $db->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = :id")
           ->execute(['id' => $transaction['id']]);
    }
    // For partially_paid, waiting, confirming — leave as pending

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Webhook DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}

http_response_code(200);
echo json_encode(['success' => true]);
