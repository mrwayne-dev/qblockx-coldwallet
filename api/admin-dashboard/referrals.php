<?php
/**
 * Project: qblockx
 * Created by: Wayne
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

try {
    $db = Database::getInstance()->getConnection();

    $page   = max(1, (int) ($_GET['page']  ?? 1));
    $limit  = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $total         = $db->query("SELECT COUNT(*) FROM referrals")->fetchColumn();
    $totalComm     = $db->query("SELECT COALESCE(SUM(total_earned), 0) FROM referrals")->fetchColumn();
    $activeRef     = $db->query("SELECT COUNT(DISTINCT referrer_id) FROM referrals")->fetchColumn();

    $stmt = $db->prepare(
        "SELECT r.id, r.commission_rate, r.total_earned, r.created_at,
                ref.email AS referrer_email, ref.full_name AS referrer_name,
                new_u.email AS referred_email, new_u.full_name AS referred_name
         FROM referrals r
         JOIN users ref   ON ref.id   = r.referrer_id
         JOIN users new_u ON new_u.id = r.referred_id
         ORDER BY r.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $referrals = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => [
            'referrals'       => $referrals,
            'total'           => (int) $total,
            'page'            => $page,
            'limit'           => $limit,
            'pages'           => (int) ceil($total / $limit),
            'summary'         => [
                'total_referrals'      => (int) $total,
                'total_commission_paid'=> number_format((float) $totalComm, 2, '.', ''),
                'active_referrers'     => (int) $activeRef,
            ],
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
