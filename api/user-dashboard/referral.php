<?php
/**
 * Project: qblockx
 * Created by: Wayne
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

try {
    $db = Database::getInstance()->getConnection();

    // Get or create referral code
    $codeStmt = $db->prepare("SELECT code, uses FROM referral_codes WHERE user_id = :uid");
    $codeStmt->execute(['uid' => $user['id']]);
    $codeRow = $codeStmt->fetch();

    if (!$codeRow) {
        $code = strtoupper(substr(base_convert(bin2hex(random_bytes(4)), 16, 36), 0, 8));
        $db->prepare("INSERT INTO referral_codes (user_id, code) VALUES (:uid, :code)")
           ->execute(['uid' => $user['id'], 'code' => $code]);
        $codeRow = ['code' => $code, 'uses' => 0];
    }

    // Referred users
    $referredStmt = $db->prepare(
        "SELECT u.email, u.full_name, u.created_at AS joined_at, r.total_earned AS commission_earned
         FROM referrals r
         JOIN users u ON u.id = r.referred_id
         WHERE r.referrer_id = :uid
         ORDER BY r.created_at DESC"
    );
    $referredStmt->execute(['uid' => $user['id']]);
    $referred = $referredStmt->fetchAll();

    // Total referral earnings
    $totalStmt = $db->prepare(
        "SELECT COALESCE(SUM(total_earned), 0) FROM referrals WHERE referrer_id = :uid"
    );
    $totalStmt->execute(['uid' => $user['id']]);
    $total_commission = (float) $totalStmt->fetchColumn();

    $app_url   = getenv('APP_URL') ?: '';
    $ref_link  = $app_url . '/register?ref=' . $codeRow['code'];

    echo json_encode([
        'success' => true,
        'data'    => [
            'referral_code'    => $codeRow['code'],
            'referral_link'    => $ref_link,
            'uses'             => (int) $codeRow['uses'],
            'total_commission' => number_format($total_commission, 2, '.', ''),
            'referred_users'   => $referred,
        ]
    ]);
} catch (PDOException $e) {
    error_log('Referral API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
