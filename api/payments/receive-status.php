<?php
/**
 * Quantum BlocX — API: payments/receive-status.php
 *
 * POST {payment_id} → check a deposit's status with NOWPayments and credit it if
 * paid. This is the fallback path for environments the IPN webhook can't reach
 * (e.g. local dev, where the callback points at the production domain).
 *
 * → { success, status, credited, pay_amount, actually_paid, symbol }
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once __DIR__ . '/np-deposits.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true) ?: [];
$paymentId = trim($input['payment_id'] ?? '');
if ($paymentId === '') {
    echo json_encode(['success' => false, 'message' => 'payment_id is required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    $sel = $db->prepare("SELECT * FROM deposits WHERE payment_id = :pid AND user_id = :u LIMIT 1");
    $sel->execute(['pid' => $paymentId, 'u' => $user['id']]);
    $dep = $sel->fetch(PDO::FETCH_ASSOC);
    if (!$dep) { echo json_encode(['success' => false, 'message' => 'Deposit not found']); exit; }

    $symStmt = $db->prepare("SELECT symbol FROM currencies WHERE id = :c");
    $symStmt->execute(['c' => $dep['currency_id']]);
    $symbol = $symStmt->fetchColumn() ?: '';

    if ((int) $dep['credited'] === 1) {
        echo json_encode(['success' => true, 'status' => 'finished', 'credited' => true,
            'pay_amount' => (float) $dep['pay_amount'], 'actually_paid' => (float) $dep['actually_paid'], 'symbol' => $symbol]);
        exit;
    }

    // Query NOWPayments
    $isDev = (getenv('APP_ENV') === 'development');
    $ch = curl_init('https://api.nowpayments.io/v1/payment/' . urlencode($paymentId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['x-api-key: ' . getenv('NOWPAYMENTS_API_KEY')],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => !$isDev,
        CURLOPT_SSL_VERIFYHOST => $isDev ? 0 : 2,
    ]);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError || ($httpCode !== 200 && $httpCode !== 201)) {
        error_log('receive-status NP (' . $httpCode . '): ' . ($curlError ?: $response));
        // Don't fail the UI — just report the last known status
        echo json_encode(['success' => true, 'status' => $dep['status'], 'credited' => false,
            'pay_amount' => (float) $dep['pay_amount'], 'actually_paid' => (float) $dep['actually_paid'], 'symbol' => $symbol]);
        exit;
    }

    $np            = json_decode($response, true) ?: [];
    $status        = strtolower($np['payment_status'] ?? $dep['status']);
    $actuallyPaid  = (float) ($np['actually_paid'] ?? 0);

    $credited = false;
    if (npIsPaidStatus($status)) {
        $res = creditDeposit($db, (int) $dep['id'], $status, $actuallyPaid);
        $credited = in_array($res, ['credited', 'already'], true);
        if ($credited) $status = 'finished';
    } else {
        // keep the latest status recorded
        $db->prepare("UPDATE deposits SET status = :st, actually_paid = :ap WHERE id = :id")
           ->execute(['st' => $status, 'ap' => $actuallyPaid > 0 ? $actuallyPaid : $dep['actually_paid'], 'id' => $dep['id']]);
    }

    echo json_encode([
        'success'       => true,
        'status'        => $status,
        'credited'      => $credited,
        'pay_amount'    => (float) $dep['pay_amount'],
        'actually_paid' => $actuallyPaid ?: (float) $dep['actually_paid'],
        'symbol'        => $symbol,
    ]);

} catch (PDOException $e) {
    error_log('receive-status.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
