<?php
/**
 * Project: Qblockx
 * API: User — Real Estate Investments
 * GET  → available pools + user's investments + portfolio summary
 * POST → invest in a pool (deduct wallet, record realestate_investment)
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

try {
    $db = Database::getInstance()->getConnection();

    // ── GET ──────────────────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $poolStmt = $db->query(
            "SELECT id, name, property_type, min_investment, duration_days,
                    yield_min, yield_max, payout_frequency, is_compounded,
                    image_url, location_tag, occupancy_pct
             FROM realestate_pools
             WHERE is_active = 1
             ORDER BY sort_order ASC"
        );
        $pools = $poolStmt->fetchAll();

        $invStmt = $db->prepare(
            "SELECT id, pool_name, amount, yield_rate, starts_at, ends_at,
                    next_payout_at, total_paid_out, expected_return,
                    actual_return, status, created_at
             FROM realestate_investments
             WHERE user_id = :uid
             ORDER BY created_at DESC"
        );
        $invStmt->execute(['uid' => $user['id']]);
        $myInvestments = $invStmt->fetchAll();

        $summaryStmt = $db->prepare(
            "SELECT
               COALESCE(SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END), 0)                 AS total_invested,
               COALESCE(SUM(CASE WHEN status = 'active' THEN expected_return ELSE 0 END), 0)         AS total_expected,
               COALESCE(SUM(total_paid_out), 0)                                                      AS total_paid_out,
               COUNT(CASE WHEN status = 'active' THEN 1 END)                                         AS active_count
             FROM realestate_investments
             WHERE user_id = :uid"
        );
        $summaryStmt->execute(['uid' => $user['id']]);
        $summary = $summaryStmt->fetch();

        echo json_encode([
            'success' => true,
            'data'    => [
                'pools'          => $pools,
                'my_investments' => $myInvestments,
                'portfolio'      => [
                    'total_invested' => number_format((float) $summary['total_invested'], 2, '.', ''),
                    'total_expected' => number_format((float) $summary['total_expected'], 2, '.', ''),
                    'total_paid_out' => number_format((float) $summary['total_paid_out'], 2, '.', ''),
                    'active_count'   => (int) $summary['active_count'],
                ],
            ],
        ]);

    // ── POST ─────────────────────────────────────────────────────────────────
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input   = json_decode(file_get_contents('php://input'), true);
        $pool_id = (int)   ($input['pool_id'] ?? 0);
        $amount  = (float) ($input['amount']  ?? 0);

        if ($pool_id <= 0 || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }

        $poolStmt = $db->prepare(
            "SELECT * FROM realestate_pools WHERE id = :id AND is_active = 1"
        );
        $poolStmt->execute(['id' => $pool_id]);
        $pool = $poolStmt->fetch();

        if (!$pool) {
            echo json_encode(['success' => false, 'message' => 'Pool not found or inactive']);
            exit;
        }

        if ($amount < (float) $pool['min_investment']) {
            echo json_encode([
                'success' => false,
                'message' => 'Minimum investment for ' . $pool['name'] . ' is $' .
                             number_format($pool['min_investment'], 0),
            ]);
            exit;
        }

        $db->beginTransaction();

        $walletStmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = :uid FOR UPDATE");
        $walletStmt->execute(['uid' => $user['id']]);
        $wallet  = $walletStmt->fetch();
        $balance = $wallet ? (float) $wallet['balance'] : 0.0;

        if ($amount > $balance) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance']);
            exit;
        }

        $starts_at = date('Y-m-d H:i:s');
        $ends_at   = date('Y-m-d H:i:s', strtotime('+' . (int) $pool['duration_days'] . ' days'));

        // First payout: 30 days for monthly, 90 days for quarterly
        $payout_interval_days = $pool['payout_frequency'] === 'quarterly' ? 90 : 30;
        $next_payout_at       = date('Y-m-d H:i:s', strtotime('+' . $payout_interval_days . ' days'));

        $yield_rate      = round((float) $pool['yield_max'], 2);
        $expected_return = round($amount + ($amount * $yield_rate / 100), 2);

        $db->prepare(
            "UPDATE wallets SET balance = balance - :amount, updated_at = NOW() WHERE user_id = :uid"
        )->execute(['amount' => $amount, 'uid' => $user['id']]);

        $db->prepare(
            "INSERT INTO realestate_investments
               (user_id, pool_id, pool_name, amount, yield_rate, starts_at, ends_at,
                next_payout_at, expected_return)
             VALUES (:uid, :pid, :pname, :amount, :yield, :starts, :ends, :next_payout, :expected)"
        )->execute([
            'uid'         => $user['id'],
            'pid'         => $pool['id'],
            'pname'       => $pool['name'],
            'amount'      => $amount,
            'yield'       => $yield_rate,
            'starts'      => $starts_at,
            'ends'        => $ends_at,
            'next_payout' => $next_payout_at,
            'expected'    => $expected_return,
        ]);

        $db->prepare(
            "INSERT INTO transactions (user_id, type, amount, currency, status, notes)
             VALUES (:uid, 'realestate_investment', :amount, 'USD', 'completed', :note)"
        )->execute([
            'uid'    => $user['id'],
            'amount' => $amount,
            'note'   => $pool['name'] . ' real estate pool — ' . $pool['duration_days'] . ' days',
        ]);

        $db->commit();

        $resp = json_encode([
            'success' => true,
            'message' => $pool['name'] . ' investment confirmed. First payout in ' .
                         $payout_interval_days . ' days.',
            'data'    => [
                'pool_name'       => $pool['name'],
                'amount'          => $amount,
                'expected_return' => $expected_return,
                'next_payout_at'  => $next_payout_at,
                'ends_at'         => $ends_at,
            ],
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

        // Send investment confirmation email (non-blocking)
        try {
            require_once '../../api/utilities/email_templates.php';
            $uStmt = $db->prepare("SELECT full_name, email FROM users WHERE id = :uid");
            $uStmt->execute(['uid' => $user['id']]);
            $u = $uStmt->fetch();
            if ($u) {
                $returnStructure = ($pool['is_compounded'] ?? false)
                    ? number_format((float) $pool['yield_max'], 2) . '% compounded'
                    : number_format((float) $pool['yield_max'], 2) . '% fixed return';
                Mailer::sendRealestateActivated(
                    $u['email'], $u['full_name'],
                    $pool['property_type'] ?? 'Real Estate',
                    $pool['name'],
                    $pool['location_tag'] ?? '',
                    $returnStructure,
                    ucfirst($pool['payout_frequency'] ?? 'monthly') . ' payouts',
                    $amount,
                    $pool['duration_days'] . ' days',
                    date('F j, Y', strtotime($starts_at)),
                    date('F j, Y', strtotime($ends_at))
                );
                $adminEmail = getenv('SMTP_USER') ?: '';
                if ($adminEmail) {
                    Mailer::sendAdminNewInvestment(
                        $adminEmail,
                        $u['full_name'], $u['email'],
                        'Real Estate',
                        $pool['name'],
                        $amount,
                        $pool['duration_days'] . ' days',
                        date('F j, Y', strtotime($starts_at)),
                        date('F j, Y', strtotime($ends_at)),
                        $expected_return
                    );
                }
            }
        } catch (Exception $emailErr) {
            error_log('Real estate investment email error: ' . $emailErr->getMessage());
        }

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
