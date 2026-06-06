<?php
/**
 * Project: qblockx
 * API: admin-dashboard/credit-debit.php
 *
 * Admin credits or debits a user wallet directly.
 * Creates a transaction record for the adjustment.
 *
 * POST { user_email, amount, type ("credit"|"debit"), notes }
 * Returns { success, message, new_balance }
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$user_email = strtolower(trim($input['user_email'] ?? ''));
$amount     = (float) ($input['amount'] ?? 0);
$type       = trim($input['type']       ?? '');
$notes      = trim($input['notes']      ?? '');

// Validation
if (empty($user_email)) {
    echo json_encode(['success' => false, 'message' => 'User email is required']);
    exit;
}
if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
    exit;
}
if (!in_array($type, ['credit', 'debit'])) {
    echo json_encode(['success' => false, 'message' => 'Type must be credit or debit']);
    exit;
}
// Notes are optional — stored for admin audit only, not exposed to the user

try {
    $db = Database::getInstance()->getConnection();

    // Look up user
    $userStmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $userStmt->execute(['email' => $user_email]);
    $user = $userStmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User account not found']);
        exit;
    }

    $user_id = (int) $user['id'];

    $db->beginTransaction();

    if ($type === 'credit') {
        // Ensure wallet exists
        $db->prepare("INSERT IGNORE INTO wallets (user_id, balance) VALUES (:uid, 0)")
           ->execute(['uid' => $user_id]);
        $db->prepare("UPDATE wallets SET balance = balance + :amount, updated_at = NOW() WHERE user_id = :uid")
           ->execute(['amount' => $amount, 'uid' => $user_id]);
    } else {
        // Debit — check sufficient balance
        $walletStmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = :uid FOR UPDATE");
        $walletStmt->execute(['uid' => $user_id]);
        $wallet = $walletStmt->fetch();
        $balance = $wallet ? (float) $wallet['balance'] : 0.0;

        if ($amount > $balance) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance for this debit']);
            exit;
        }

        $db->prepare("UPDATE wallets SET balance = balance - :amount, updated_at = NOW() WHERE user_id = :uid")
           ->execute(['amount' => $amount, 'uid' => $user_id]);
    }

    // Create transaction record — use valid ENUM types: deposit / withdrawal
    // Notes appear as natural bank transaction descriptions to the user
    $txType  = ($type === 'credit') ? 'deposit' : 'withdrawal';
    $txNotes = ($type === 'credit') ? 'Incoming bank transfer' : 'Bank transfer';
    $db->prepare(
        "INSERT INTO transactions (user_id, type, amount, status, notes)
         VALUES (:uid, :txtype, :amount, 'completed', :notes)"
    )->execute(['uid' => $user_id, 'txtype' => $txType, 'amount' => $amount, 'notes' => $txNotes]);

    // Fetch updated balance
    $newBalRow = $db->prepare("SELECT balance FROM wallets WHERE user_id = :uid");
    $newBalRow->execute(['uid' => $user_id]);
    $newBal = (float) ($newBalRow->fetchColumn() ?? 0);

    $db->commit();

    echo json_encode([
        'success'     => true,
        'message'     => ucfirst($type) . ' of $' . number_format($amount, 2) . ' applied successfully',
        'new_balance' => number_format($newBal, 2, '.', ''),
    ]);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
