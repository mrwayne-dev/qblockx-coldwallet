<?php
/**
 * Project: Qblockx
 * API: payments/card-purchase-status.php
 *
 * Called when the user returns from the NOWPayments hosted page. Checks the
 * invoice status and activates the card if paid — a fallback for environments
 * where the IPN webhook can't reach the server (e.g. local dev).
 *
 * POST { invoice_id }
 * → { success:true, status:'completed'|'pending'|'failed', tier? }
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once __DIR__ . '/card-tiers.php';
header('Content-Type: application/json');

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input      = json_decode(file_get_contents('php://input'), true) ?: [];
$invoice_id = trim($input['invoice_id'] ?? '');
if ($invoice_id === '') {
    echo json_encode(['success' => false, 'message' => 'invoice_id is required']);
    exit;
}

$user = getAuthUser();

try {
    $db = Database::getInstance()->getConnection();

    // The card must belong to this user
    $cardStmt = $db->prepare(
        "SELECT id, card_tier, status FROM virtual_cards
         WHERE payment_invoice_id = :inv AND user_id = :uid LIMIT 1"
    );
    $cardStmt->execute(['inv' => $invoice_id, 'uid' => $user['id']]);
    $card = $cardStmt->fetch(PDO::FETCH_ASSOC);

    if (!$card) {
        echo json_encode(['success' => false, 'message' => 'Card purchase not found']);
        exit;
    }
    if ($card['status'] === 'active') {
        echo json_encode(['success' => true, 'status' => 'completed', 'tier' => $card['card_tier']]);
        exit;
    }

    // ── Query NOWPayments invoice status ─────────────────────────────────────
    $apiKey = getenv('NOWPAYMENTS_API_KEY');
    $isDev  = (getenv('APP_ENV') === 'development');

    $ch = curl_init('https://api.nowpayments.io/v1/invoice/' . urlencode($invoice_id));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['x-api-key: ' . $apiKey],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => !$isDev,
        CURLOPT_SSL_VERIFYHOST => $isDev ? 0 : 2,
    ]);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError || ($httpCode !== 200 && $httpCode !== 201)) {
        error_log('card status check (' . $httpCode . '): ' . ($curlError ?: $response));
        echo json_encode(['success' => true, 'status' => 'pending']);
        exit;
    }

    $inv = json_decode($response, true) ?: [];
    $st  = strtolower($inv['payment_status'] ?? $inv['status'] ?? '');

    if (in_array($st, ['failed', 'expired', 'refunded'], true)) {
        $db->prepare("DELETE FROM virtual_cards WHERE id = :id AND status = 'pending'")->execute(['id' => $card['id']]);
        $db->prepare("UPDATE transactions SET status = 'failed' WHERE tx_hash = :inv AND type = 'card_purchase'")
           ->execute(['inv' => $invoice_id]);
        echo json_encode(['success' => true, 'status' => 'failed']);
        exit;
    }

    if (in_array($st, ['finished', 'confirmed', 'complete', 'paid'], true)) {
        activateCardByInvoice($db, $invoice_id);
        echo json_encode(['success' => true, 'status' => 'completed', 'tier' => $card['card_tier']]);
        exit;
    }

    echo json_encode(['success' => true, 'status' => 'pending']);

} catch (PDOException $e) {
    error_log('card-purchase-status.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
