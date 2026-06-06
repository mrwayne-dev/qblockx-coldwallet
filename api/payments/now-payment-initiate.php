<?php
/**
 * Project: qblockx
 * Created by: Wayne
 *
 * NOWPayments hosted-invoice deposit flow.
 * Creates an invoice on NOWPayments and returns the invoice_url for
 * client-side redirect. The user completes payment on NOWPayments'
 * hosted page; the webhook then credits the wallet.
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once '../../api/utilities/email_templates.php';
header('Content-Type: application/json');

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$amount   = (float) ($input['amount'] ?? 0);
$currency = strtolower(trim($input['currency'] ?? 'usdttrc20'));

// Sanitise to known currencies
$allowed = ['btc', 'eth', 'usdttrc20', 'usdterc20', 'bnbbsc'];
if (!in_array($currency, $allowed, true)) {
    $currency = 'usdttrc20';
}

$currencyMap = [
    'btc'       => ['BTC',  'Bitcoin'],
    'eth'       => ['ETH',  'Ethereum'],
    'usdttrc20' => ['USDT', 'TRC20'],
    'usdterc20' => ['USDT', 'ERC20'],
    'bnbbsc'    => ['BNB',  'BSC'],
];
[$currencyLabel, $networkLabel] = $currencyMap[$currency] ?? [strtoupper($currency), strtoupper($currency)];

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

$user = getAuthUser();

try {
    $db = Database::getInstance()->getConnection();

    $order_id = 'DEP-' . $user['id'] . '-' . time();

    $payload = json_encode([
        'price_amount'      => $amount,
        'price_currency'    => 'usd',
        'pay_currency'      => $currency,
        'order_id'          => $order_id,
        'order_description' => 'Deposit for Qblockx account',
        'ipn_callback_url'  => getenv('NOWPAYMENTS_IPN_CALLBACK_URL'),
        'success_url'       => getenv('NOWPAYMENTS_SUCCESS_URL'),
        'cancel_url'        => getenv('NOWPAYMENTS_CANCEL_URL'),
    ]);

    $isDev = (getenv('APP_ENV') === 'development');

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
        // Disable SSL peer verification on local dev (Windows cURL CA bundle issues)
        CURLOPT_SSL_VERIFYPEER => !$isDev,
        CURLOPT_SSL_VERIFYHOST => $isDev ? 0 : 2,
    ]);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        error_log('NOWPayments CURL error: ' . $curlError);
        $devDetail = $isDev ? ' [curl: ' . $curlError . ']' : '';
        echo json_encode(['success' => false, 'message' => 'Connection error. Please try again.' . $devDetail]);
        exit;
    }

    if ($httpCode !== 200 && $httpCode !== 201) {
        error_log('NOWPayments API error ' . $httpCode . ': ' . $response);
        $devDetail = $isDev ? ' [HTTP ' . $httpCode . ': ' . $response . ']' : '';
        echo json_encode(['success' => false, 'message' => 'Payment gateway error. Please try again.' . $devDetail]);
        exit;
    }

    $nowData = json_decode($response, true);
    if (empty($nowData['id']) || empty($nowData['invoice_url'])) {
        error_log('NOWPayments invoice missing id/invoice_url: ' . $response);
        $devDetail = $isDev ? ' [response: ' . $response . ']' : '';
        echo json_encode(['success' => false, 'message' => 'Failed to create payment invoice.' . $devDetail]);
        exit;
    }

    // Store the pending transaction (invoice_id stored in payment_id column)
    $db->prepare(
        "INSERT INTO transactions (user_id, type, amount, currency, status, payment_id, notes)
         VALUES (:user_id, 'deposit', :amount, :currency, 'pending', :payment_id, :notes)"
    )->execute([
        'user_id'    => $user['id'],
        'amount'     => $amount,
        'currency'   => $currency,
        'payment_id' => (string) $nowData['id'],
        'notes'      => $order_id,
    ]);

    $resp = json_encode([
        'success' => true,
        'data'    => [
            'invoice_id'  => $nowData['id'],
            'invoice_url' => $nowData['invoice_url'],
            'order_id'    => $order_id,
        ]
    ]);
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

    // Send deposit pending email (non-blocking)
    try {
        $nameStmt = $db->prepare("SELECT full_name FROM users WHERE id = :uid");
        $nameStmt->execute(['uid' => $user['id']]);
        $nameRow  = $nameStmt->fetch();
        $fullName = $nameRow['full_name'] ?? 'User';

        Mailer::sendDepositPending(
            $user['email'],
            $fullName,
            $amount,
            $currencyLabel,
            $networkLabel,
            '',
            '',
            date('d M Y, H:i'),
            (string) $nowData['id']
        );
    } catch (Exception $mailErr) {
        error_log('now-payment-initiate: mail error for user ' . $user['id'] . ': ' . $mailErr->getMessage());
    }
} catch (PDOException $e) {
    error_log('NOWPayments DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
