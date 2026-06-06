<?php
/**
 * Project: Qblockx
 * API: User — Investment Plans
 * GET  → available plans + user's investment history + portfolio summary
 * POST → create a new investment (deduct wallet, record plan_investment)
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

        // All active plans
        $planStmt = $db->query(
            "SELECT id, name, tier, min_amount, max_amount, duration_days,
                    yield_min, yield_max, commission_pct, is_compounded
             FROM investment_plans
             WHERE is_active = 1
             ORDER BY sort_order ASC"
        );
        $plans = $planStmt->fetchAll();

        // User's investments
        $invStmt = $db->prepare(
            "SELECT pi.id, pi.plan_name, pi.amount, pi.yield_rate,
                    pi.starts_at, pi.ends_at, pi.expected_return,
                    pi.actual_return, pi.status, pi.created_at,
                    ip.tier
             FROM plan_investments pi
             JOIN investment_plans ip ON ip.id = pi.plan_id
             WHERE pi.user_id = :uid
             ORDER BY pi.created_at DESC"
        );
        $invStmt->execute(['uid' => $user['id']]);
        $myInvestments = $invStmt->fetchAll();

        // Portfolio summary
        $summaryStmt = $db->prepare(
            "SELECT
               COALESCE(SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END), 0)                  AS total_invested,
               COALESCE(SUM(CASE WHEN status = 'active' THEN expected_return ELSE 0 END), 0)          AS total_expected,
               COALESCE(SUM(CASE WHEN status = 'matured' THEN actual_return ELSE 0 END), 0)           AS total_returned,
               COUNT(CASE WHEN status = 'active' THEN 1 END)                                          AS active_count
             FROM plan_investments
             WHERE user_id = :uid"
        );
        $summaryStmt->execute(['uid' => $user['id']]);
        $summary = $summaryStmt->fetch();

        echo json_encode([
            'success'        => true,
            'data'           => [
                'plans'          => $plans,
                'my_investments' => $myInvestments,
                'portfolio'      => [
                    'total_invested' => number_format((float) $summary['total_invested'], 2, '.', ''),
                    'total_expected' => number_format((float) $summary['total_expected'], 2, '.', ''),
                    'total_returned' => number_format((float) $summary['total_returned'], 2, '.', ''),
                    'active_count'   => (int) $summary['active_count'],
                ],
            ],
        ]);

    // ── POST ─────────────────────────────────────────────────────────────────
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input   = json_decode(file_get_contents('php://input'), true);
        $plan_id = (int) ($input['plan_id'] ?? 0);
        $amount  = (float) ($input['amount']  ?? 0);

        if ($plan_id <= 0 || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }

        // Load plan
        $planStmt = $db->prepare(
            "SELECT * FROM investment_plans WHERE id = :id AND is_active = 1"
        );
        $planStmt->execute(['id' => $plan_id]);
        $plan = $planStmt->fetch();

        if (!$plan) {
            echo json_encode(['success' => false, 'message' => 'Investment plan not found or inactive']);
            exit;
        }

        if ($amount < (float) $plan['min_amount'] || $amount > (float) $plan['max_amount']) {
            echo json_encode([
                'success' => false,
                'message' => 'Amount must be between $' . number_format($plan['min_amount'], 0) .
                             ' and $' . number_format($plan['max_amount'], 0),
            ]);
            exit;
        }

        $db->beginTransaction();

        // Lock and check wallet
        $walletStmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = :uid FOR UPDATE");
        $walletStmt->execute(['uid' => $user['id']]);
        $wallet  = $walletStmt->fetch();
        $balance = $wallet ? (float) $wallet['balance'] : 0.0;

        if ($amount > $balance) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance']);
            exit;
        }

        $starts_at       = date('Y-m-d H:i:s');
        $ends_at         = date('Y-m-d H:i:s', strtotime('+' . (int) $plan['duration_days'] . ' days'));
        $yield_rate      = round((float) $plan['yield_max'], 2);
        $expected_return = round($amount + ($amount * $yield_rate / 100), 2);

        // Debit wallet
        $db->prepare(
            "UPDATE wallets SET balance = balance - :amount, updated_at = NOW() WHERE user_id = :uid"
        )->execute(['amount' => $amount, 'uid' => $user['id']]);

        // Create investment record (yield_rate now stored)
        $db->prepare(
            "INSERT INTO plan_investments
               (user_id, plan_id, plan_name, amount, yield_rate, starts_at, ends_at, expected_return)
             VALUES (:uid, :pid, :pname, :amount, :yield, :starts, :ends, :expected)"
        )->execute([
            'uid'      => $user['id'],
            'pid'      => $plan['id'],
            'pname'    => $plan['name'],
            'amount'   => $amount,
            'yield'    => $yield_rate,
            'starts'   => $starts_at,
            'ends'     => $ends_at,
            'expected' => $expected_return,
        ]);

        // Transaction log
        $db->prepare(
            "INSERT INTO transactions (user_id, type, amount, currency, status, notes)
             VALUES (:uid, 'investment', :amount, 'USD', 'completed', :note)"
        )->execute([
            'uid'    => $user['id'],
            'amount' => $amount,
            'note'   => $plan['name'] . ' investment plan — ' . $plan['duration_days'] . ' days',
        ]);

        $db->commit();

        $resp = json_encode([
            'success' => true,
            'message' => $plan['name'] . ' plan activated. Returns credited at maturity.',
            'data'    => [
                'plan_name'       => $plan['name'],
                'amount'          => $amount,
                'expected_return' => $expected_return,
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
                Mailer::sendPlanActivated(
                    $u['email'], $u['full_name'],
                    $plan['name'],
                    $plan['tier'] ?? 'Standard',
                    $amount,
                    $plan['duration_days'] . ' days',
                    date('F j, Y', strtotime($starts_at)),
                    date('F j, Y', strtotime($ends_at)),
                    $expected_return
                );
                $adminEmail = getenv('SMTP_USER') ?: '';
                if ($adminEmail) {
                    Mailer::sendAdminNewInvestment(
                        $adminEmail,
                        $u['full_name'], $u['email'],
                        'Investment Plan',
                        $plan['name'],
                        $amount,
                        $plan['duration_days'] . ' days',
                        date('F j, Y', strtotime($starts_at)),
                        date('F j, Y', strtotime($ends_at)),
                        $expected_return
                    );
                }
            }
        } catch (Exception $emailErr) {
            error_log('Investment email error: ' . $emailErr->getMessage());
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
