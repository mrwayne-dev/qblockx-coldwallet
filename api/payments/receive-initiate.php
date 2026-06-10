<?php
/**
 * Quantum BlocX — API: payments/receive-initiate.php
 *
 * GET  → list of currencies that can be received (mapped to NOWPayments codes)
 * POST {currency_id, amount_usd} → create a NOWPayments payment and return the
 *        deposit address + exact pay amount to display on the Receive page.
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once '../../api/utilities/http.php';
require_once __DIR__ . '/np-deposits.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

try {
    $db = Database::getInstance()->getConnection();

    // ── GET: supported receive currencies ────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $rows = $db->query("SELECT id, symbol, name, network FROM currencies WHERE is_active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $code = npCodeForCurrency($r['symbol'], $r['network']);
            if (!$code) continue;
            $out[] = [
                'id'      => (int) $r['id'],
                'symbol'  => $r['symbol'],
                'name'    => $r['name'],
                'network' => $r['network'],
                'np_code' => $code,
            ];
        }
        echo json_encode(['success' => true, 'data' => ['currencies' => $out]]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // ── POST: create a deposit payment ───────────────────────────────
    $input      = json_decode(file_get_contents('php://input'), true) ?: [];
    $currencyId = (int) ($input['currency_id'] ?? 0);
    $amountUsd  = (float) ($input['amount_usd'] ?? 0);

    if ($amountUsd <= 0) {
        echo json_encode(['success' => false, 'message' => 'Enter a deposit amount']);
        exit;
    }

    $cur = $db->prepare("SELECT id, symbol, name, network FROM currencies WHERE id = :c AND is_active = 1");
    $cur->execute(['c' => $currencyId]);
    $c = $cur->fetch(PDO::FETCH_ASSOC);
    $code = $c ? npCodeForCurrency($c['symbol'], $c['network']) : null;
    if (!$c || !$code) {
        echo json_encode(['success' => false, 'message' => 'This asset is not available to receive']);
        exit;
    }

    $orderId = 'RCV-' . $user['id'] . '-' . time();
    $isDev   = (getenv('APP_ENV') === 'development');

    $payload = json_encode([
        'price_amount'      => $amountUsd,
        'price_currency'    => 'usd',
        'pay_currency'      => $code,
        'order_id'          => $orderId,
        'order_description' => 'Deposit to Quantum BlocX',
        'ipn_callback_url'  => getenv('NOWPAYMENTS_IPN_CALLBACK_URL'),
    ]);

    $ch = curl_init('https://api.nowpayments.io/v1/payment');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-api-key: ' . getenv('NOWPAYMENTS_API_KEY')],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => !$isDev,
        CURLOPT_SSL_VERIFYHOST => $isDev ? 0 : 2,
    ]);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        error_log('receive-initiate curl: ' . $curlError);
        echo json_encode(['success' => false, 'message' => 'Connection error. Please try again.']);
        exit;
    }

    $np = json_decode($response, true) ?: [];

    if ($httpCode !== 200 && $httpCode !== 201) {
        // Surface NOWPayments' own message (e.g. amount below minimum)
        $msg = $np['message'] ?? 'Payment gateway error. Please try again.';
        error_log('receive-initiate NP error ' . $httpCode . ': ' . $response);
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    if (empty($np['payment_id']) || empty($np['pay_address'])) {
        error_log('receive-initiate missing fields: ' . $response);
        echo json_encode(['success' => false, 'message' => 'Could not generate a deposit address. Please try again.']);
        exit;
    }

    $payAmount = (float) ($np['pay_amount'] ?? 0);
    $expires   = $np['expiration_estimate_date'] ?? null;

    $db->prepare(
        "INSERT INTO deposits
            (user_id, currency_id, pay_currency, price_amount_usd, pay_amount, pay_address, payment_id, order_id, status, expires_at)
         VALUES (:u, :cid, :pc, :usd, :pa, :addr, :pid, :oid, 'waiting', :exp)"
    )->execute([
        'u' => $user['id'], 'cid' => $currencyId, 'pc' => $code, 'usd' => round($amountUsd, 2),
        'pa' => $payAmount, 'addr' => $np['pay_address'], 'pid' => (string) $np['payment_id'],
        'oid' => $orderId, 'exp' => $expires ? date('Y-m-d H:i:s', strtotime($expires)) : null,
    ]);

    finish_response([
        'success' => true,
        'data'    => [
            'payment_id'   => (string) $np['payment_id'],
            'pay_address'  => $np['pay_address'],
            'pay_amount'   => $payAmount,
            'pay_currency' => strtoupper($code),
            'symbol'       => $c['symbol'],
            'name'         => $c['name'],
            'network'      => $c['network'],
            'amount_usd'   => round($amountUsd, 2),
            'expires_at'   => $expires,
        ],
    ]);

    // Awaiting-deposit notice (after response is sent)
    try {
        require_once '../../api/utilities/email_templates.php';
        $nameStmt = $db->prepare("SELECT full_name FROM users WHERE id = :u");
        $nameStmt->execute(['u' => $user['id']]);
        $fullName = $nameStmt->fetchColumn() ?: '';
        $body = "A deposit address has been generated for your Quantum BlocX account.\n\n"
              . "Asset: " . $c['symbol'] . " (" . $c['network'] . ")\n"
              . "Send exactly: " . rtrim(rtrim(number_format($payAmount, 8), '0'), '.') . " " . strtoupper($code) . "\n"
              . "Value: about $" . number_format($amountUsd, 2) . "\n\n"
              . "Your balance will update automatically once the network confirms the transfer. "
              . "If you didn't request this, you can ignore this email.";
        Mailer::sendNotice($user['email'] ?? '', $fullName, 'Your deposit address is ready — Qblockx', $body);
    } catch (\Throwable $mailErr) {
        error_log('receive-initiate email: ' . $mailErr->getMessage());
    }

} catch (PDOException $e) {
    error_log('receive-initiate.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
