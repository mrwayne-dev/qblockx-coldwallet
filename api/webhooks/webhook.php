<?php
/**
 * Project: Qblockx
 * NOWPayments IPN webhook — activates a QFS Card when its invoice is paid.
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../../config/database.php';
require_once '../../api/payments/card-tiers.php';

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';

// ── Verify HMAC-SHA512 signature ────────────────────────────────────────────
$ipnSecret = getenv('NOWPAYMENTS_IPN_SECRET');
$data = json_decode($payload, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}
if ($ipnSecret) {
    $sorted = $data;
    ksort($sorted);
    $expected = hash_hmac('sha512', json_encode($sorted), $ipnSecret);
    if (!hash_equals($expected, $signature)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid signature']);
        exit;
    }
}

$invoiceId = (string) ($data['invoice_id'] ?? '');
$orderId   = (string) ($data['order_id'] ?? '');
$status    = strtolower($data['payment_status'] ?? '');

// Fallback: derive invoice id from the order ref if needed (CARD-<cardId>-<ts>)
try {
    $db = Database::getInstance()->getConnection();

    // ── Deposit (Receive) IPN? Match by payment_id or RCV-* order ref ─────────
    $paymentId = (string) ($data['payment_id'] ?? '');
    if ($paymentId !== '' || strncmp($orderId, 'RCV-', 4) === 0) {
        $depStmt = $db->prepare("SELECT id FROM deposits WHERE payment_id = :pid OR order_id = :oid LIMIT 1");
        $depStmt->execute(['pid' => $paymentId, 'oid' => $orderId]);
        $depId = (int) ($depStmt->fetchColumn() ?: 0);
        if ($depId > 0) {
            require_once __DIR__ . '/../payments/np-deposits.php';
            $actuallyPaid = (float) ($data['actually_paid'] ?? 0);
            if (npIsPaidStatus($status)) {
                creditDeposit($db, $depId, $status, $actuallyPaid);
            } else {
                $db->prepare("UPDATE deposits SET status = :st, actually_paid = :ap WHERE id = :id")
                   ->execute(['st' => $status ?: 'waiting', 'ap' => $actuallyPaid, 'id' => $depId]);
            }
            http_response_code(200);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    if ($invoiceId === '' && $orderId !== '') {
        $s = $db->prepare("SELECT payment_invoice_id FROM virtual_cards WHERE payment_order_id = :ord LIMIT 1");
        $s->execute(['ord' => $orderId]);
        $invoiceId = (string) ($s->fetchColumn() ?: '');
    }

    if ($invoiceId === '') {
        // Unknown — acknowledge so NOWPayments stops retrying
        http_response_code(200);
        echo json_encode(['success' => true]);
        exit;
    }

    if (in_array($status, ['finished', 'confirmed', 'complete', 'paid'], true)) {
        activateCardByInvoice($db, $invoiceId);
    } elseif (in_array($status, ['failed', 'expired', 'refunded'], true)) {
        $db->prepare("DELETE FROM virtual_cards WHERE payment_invoice_id = :inv AND status = 'pending'")
           ->execute(['inv' => $invoiceId]);
        $db->prepare("UPDATE transactions SET status = 'failed' WHERE tx_hash = :inv AND type = 'card_purchase'")
           ->execute(['inv' => $invoiceId]);
    }
    // waiting / confirming / partially_paid → leave pending

    http_response_code(200);
    echo json_encode(['success' => true]);

} catch (\Throwable $e) {
    error_log('webhook.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
