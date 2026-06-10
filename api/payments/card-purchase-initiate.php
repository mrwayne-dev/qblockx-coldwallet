<?php
/**
 * Project: Qblockx
 * API: payments/card-purchase-initiate.php
 *
 * Starts a QFS Card purchase via NOWPayments hosted invoice.
 * POST { tier: 'VirtuElevate' | 'VirtuElite' }
 * → creates a pending virtual_cards row, a NOWPayments invoice, and returns
 *   { invoice_id, invoice_url }. The card is activated when payment confirms
 *   (webhook IPN or the return status check).
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/card-tiers.php';   // CARD_TIERS

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$tier  = trim($input['tier'] ?? '');

if (!isset(CARD_TIERS[$tier])) {
    echo json_encode(['success' => false, 'message' => 'Invalid card tier']);
    exit;
}
$price = (float) CARD_TIERS[$tier]['price'];

$user = getAuthUser();

try {
    $db = Database::getInstance()->getConnection();

    // Already holds an active card?
    $active = $db->prepare("SELECT card_tier FROM virtual_cards WHERE user_id = :uid AND status = 'active' LIMIT 1");
    $active->execute(['uid' => $user['id']]);
    if ($active->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'You already have an active card.']);
        exit;
    }

    // Clear any stale pending cards for this user so we don't pile them up
    $db->prepare("DELETE FROM virtual_cards WHERE user_id = :uid AND status = 'pending'")
       ->execute(['uid' => $user['id']]);

    // Create the pending card row first so we can reference its id in the order
    $db->prepare(
        "INSERT INTO virtual_cards (user_id, card_tier, status, price_paid_usd)
         VALUES (:uid, :tier, 'pending', :price)"
    )->execute(['uid' => $user['id'], 'tier' => $tier, 'price' => $price]);
    $cardId   = (int) $db->lastInsertId();
    $order_id = 'CARD-' . $cardId . '-' . time();

    // ── Create NOWPayments invoice ───────────────────────────────────────────
    $appUrl     = rtrim(getenv('APP_URL') ?: 'https://qblockx.com', '/');
    $successUrl = $appUrl . '/dashboard#qfs-card';
    $cancelUrl  = $appUrl . '/dashboard#qfs-card';
    $isDev      = (getenv('APP_ENV') === 'development');

    $payload = json_encode([
        'price_amount'      => $price,
        'price_currency'    => 'usd',
        'order_id'          => $order_id,
        'order_description' => $tier . ' QFS Card — Qblockx',
        'ipn_callback_url'  => getenv('NOWPAYMENTS_IPN_CALLBACK_URL'),
        'success_url'       => $successUrl,
        'cancel_url'        => $cancelUrl,
    ]);

    $ch = curl_init('https://api.nowpayments.io/v1/invoice');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . getenv('NOWPAYMENTS_API_KEY'),
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => !$isDev,
        CURLOPT_SSL_VERIFYHOST => $isDev ? 0 : 2,
    ]);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError || ($httpCode !== 200 && $httpCode !== 201)) {
        // Roll back the pending card so the user can retry cleanly
        $db->prepare("DELETE FROM virtual_cards WHERE id = :id")->execute(['id' => $cardId]);
        error_log('Card invoice error (' . $httpCode . '): ' . ($curlError ?: $response));
        $detail = $isDev ? ' [' . $httpCode . ': ' . ($curlError ?: $response) . ']' : '';
        echo json_encode(['success' => false, 'message' => 'Payment gateway error. Please try again.' . $detail]);
        exit;
    }

    $nowData = json_decode($response, true);
    if (empty($nowData['id']) || empty($nowData['invoice_url'])) {
        $db->prepare("DELETE FROM virtual_cards WHERE id = :id")->execute(['id' => $cardId]);
        error_log('Card invoice missing id/url: ' . $response);
        echo json_encode(['success' => false, 'message' => 'Failed to create payment invoice.']);
        exit;
    }

    $invoiceId = (string) $nowData['id'];

    // Save invoice references on the card
    $db->prepare(
        "UPDATE virtual_cards SET payment_invoice_id = :inv, payment_order_id = :ord WHERE id = :id"
    )->execute(['inv' => $invoiceId, 'ord' => $order_id, 'id' => $cardId]);

    // Record the purchase in the activity feed (pending)
    $db->prepare(
        "INSERT INTO transactions (user_id, type, status, amount, amount_usd, currency_symbol, notes, tx_hash)
         VALUES (:uid, 'card_purchase', 'pending', :amt, :amt2, 'USD', :notes, :inv)"
    )->execute([
        'uid'   => $user['id'],
        'amt'   => $price,
        'amt2'  => $price,
        'notes' => $tier . ' QFS Card purchase',
        'inv'   => $invoiceId,
    ]);

    echo json_encode([
        'success' => true,
        'data'    => [
            'invoice_id'  => $invoiceId,
            'invoice_url' => $nowData['invoice_url'],
            'order_id'    => $order_id,
            'tier'        => $tier,
        ]
    ]);

} catch (PDOException $e) {
    error_log('card-purchase-initiate.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
