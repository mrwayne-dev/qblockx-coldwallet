<?php
/**
 * Project: qblockx
 * API: payments/now-payment-status.php
 *
 * Called by the frontend after the user returns from the NOWPayments
 * hosted page (success_url redirect). Checks whether the invoice has
 * been paid and, if so, credits the wallet — providing a fallback for
 * environments where the IPN webhook cannot reach the server (e.g. local dev).
 *
 * POST  { invoice_id: string }
 * Returns:
 *   { success: true,  status: 'completed', credited: true }   — newly credited
 *   { success: true,  status: 'completed', credited: false }  — already credited
 *   { success: true,  status: 'pending' }                     — not confirmed yet
 *   { success: false, message: '...' }                        — error
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

$input      = json_decode(file_get_contents('php://input'), true);
$invoice_id = trim($input['invoice_id'] ?? '');

if (empty($invoice_id)) {
    echo json_encode(['success' => false, 'message' => 'invoice_id is required']);
    exit;
}

$user = getAuthUser();

try {
    $db  = Database::getInstance()->getConnection();
    $uid = $user['id'];

    // ── 1. Check local transaction record ────────────────────────────────────
    // invoice_id was stored in transactions.payment_id when the invoice was created
    $txStmt = $db->prepare(
        "SELECT * FROM transactions
         WHERE payment_id = :pid AND user_id = :uid AND type = 'deposit'
         LIMIT 1"
    );
    $txStmt->execute(['pid' => $invoice_id, 'uid' => $uid]);
    $transaction = $txStmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    // Already credited — nothing to do
    if ($transaction['status'] === 'completed') {
        echo json_encode(['success' => true, 'status' => 'completed', 'credited' => false]);
        exit;
    }

    // Already failed — don't bother polling
    if ($transaction['status'] === 'failed') {
        echo json_encode(['success' => true, 'status' => 'failed', 'credited' => false]);
        exit;
    }

    // ── 2. Query NOWPayments invoice status ──────────────────────────────────
    $apiKey = getenv('NOWPAYMENTS_API_KEY');
    $isDev  = (getenv('APP_ENV') === 'development');

    $ch = curl_init('https://api.nowpayments.io/v1/invoice/' . urlencode($invoice_id));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . $apiKey,
        ],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => !$isDev,
        CURLOPT_SSL_VERIFYHOST => $isDev ? 0 : 2,
    ]);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError || ($httpCode !== 200 && $httpCode !== 201)) {
        // NOWPayments unreachable — report pending, frontend will retry
        error_log('NOWPayments status check error (' . $httpCode . '): ' . ($curlError ?: $response));
        echo json_encode(['success' => true, 'status' => 'pending']);
        exit;
    }

    $invoiceData = json_decode($response, true);

    // NOWPayments invoice statuses that mean the payment is confirmed:
    // "finished" = fully paid and confirmed
    // Some accounts also show intermediate: "partially_paid", "waiting", "confirming"
    $invoiceStatus = strtolower($invoiceData['status'] ?? '');

    // Also check payment_status field if present (returned by some API versions)
    $paymentStatus = strtolower($invoiceData['payment_status'] ?? $invoiceStatus);

    $isConfirmed = in_array($paymentStatus, ['finished', 'confirmed', 'complete', 'paid'], true)
                || in_array($invoiceStatus, ['finished', 'confirmed'], true);
    $isFailed    = in_array($paymentStatus, ['failed', 'expired', 'refunded'], true)
                || in_array($invoiceStatus, ['failed', 'expired'], true);

    if ($isFailed) {
        $db->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = :id")
           ->execute(['id' => $transaction['id']]);
        echo json_encode(['success' => true, 'status' => 'failed', 'credited' => false]);
        exit;
    }

    if (!$isConfirmed) {
        echo json_encode(['success' => true, 'status' => 'pending']);
        exit;
    }

    // ── 3. Credit wallet (idempotent — guarded by status check above) ────────
    $db->beginTransaction();

    // Re-check status under lock to prevent double-credit race condition
    $recheckStmt = $db->prepare(
        "SELECT status FROM transactions WHERE id = :id FOR UPDATE"
    );
    $recheckStmt->execute(['id' => $transaction['id']]);
    $currentStatus = $recheckStmt->fetchColumn();

    if ($currentStatus === 'completed') {
        $db->rollBack();
        echo json_encode(['success' => true, 'status' => 'completed', 'credited' => false]);
        exit;
    }

    // Mark transaction completed
    $db->prepare("UPDATE transactions SET status = 'completed', updated_at = NOW() WHERE id = :id")
       ->execute(['id' => $transaction['id']]);

    // Credit wallet (upsert)
    $db->prepare(
        "INSERT INTO wallets (user_id, balance) VALUES (:uid, :amt)
         ON DUPLICATE KEY UPDATE balance = balance + :amt2, updated_at = NOW()"
    )->execute([
        'uid'  => $uid,
        'amt'  => $transaction['amount'],
        'amt2' => $transaction['amount'],
    ]);

    $db->commit();

    // Send confirmation email (non-fatal)
    try {
        require_once __DIR__ . '/../../api/utilities/email_templates.php';
        $usrStmt = $db->prepare("SELECT email, full_name FROM users WHERE id = :uid LIMIT 1");
        $usrStmt->execute(['uid' => $uid]);
        $usr = $usrStmt->fetch(PDO::FETCH_ASSOC);
        if ($usr) {
            Mailer::sendDepositConfirmed(
                $usr['email'],
                $usr['full_name'] ?? '',
                (string) $transaction['amount'],
                $transaction['currency'] ?? 'crypto',
                $invoice_id
            );
        }
    } catch (\Throwable $emailErr) {
        error_log('Payment status email error: ' . $emailErr->getMessage());
    }

    echo json_encode(['success' => true, 'status' => 'completed', 'credited' => true]);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Payment status DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
