<?php
/**
 * Project: Qblockx
 * Cron: Real Estate Periodic Payouts
 * Run daily. Two passes:
 *   1. Active investments with next_payout_at <= NOW() → credit periodic payout, advance next_payout_at
 *   2. Active investments with ends_at <= NOW() and not yet matured → finalize, mark matured
 *
 * Recommended cron: 0 2 * * * php /path/to/api/cron/realestate-payouts.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Access denied');
}

define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/api/utilities/email_templates.php';

$processed = 0;
$failed    = 0;
$errors    = [];

try {
    $db = Database::getInstance()->getConnection();

    // ── Pass 1: periodic payouts ───────────────────────────────────────────
    $payoutStmt = $db->prepare(
        "SELECT ri.id, ri.user_id, ri.pool_name, ri.amount,
                ri.yield_rate, ri.total_paid_out, ri.starts_at, ri.ends_at,
                rp.yield_min, rp.yield_max, rp.payout_frequency,
                u.email, u.full_name
         FROM realestate_investments ri
         JOIN realestate_pools rp ON rp.id = ri.pool_id
         JOIN users u ON u.id = ri.user_id
         WHERE ri.status = 'active' AND ri.next_payout_at <= NOW()"
    );
    $payoutStmt->execute();
    $due = $payoutStmt->fetchAll();

    foreach ($due as $inv) {
        try {
            // Assign yield_rate on first payout if not yet set
            $yield_rate = $inv['yield_rate'] !== null
                ? (float) $inv['yield_rate']
                : round((float) $inv['yield_min'] + mt_rand(0, 100) / 100 * ((float) $inv['yield_max'] - (float) $inv['yield_min']), 2);

            // Payout = amount * (yield_rate / 100) / periods_per_year
            $periods_per_year = $inv['payout_frequency'] === 'quarterly' ? 4 : 12;
            $payout           = round((float) $inv['amount'] * ($yield_rate / 100) / $periods_per_year, 2);
            $interval_days    = $inv['payout_frequency'] === 'quarterly' ? 90 : 30;
            $next_payout_at   = date('Y-m-d H:i:s', strtotime('+' . $interval_days . ' days'));
            $new_total        = round((float) $inv['total_paid_out'] + $payout, 2);
            $is_final         = strtotime($inv['ends_at']) <= time();

            $db->beginTransaction();

            $db->prepare(
                "UPDATE wallets SET balance = balance + :payout, updated_at = NOW() WHERE user_id = :uid"
            )->execute(['payout' => $payout, 'uid' => $inv['user_id']]);

            if ($is_final) {
                $db->prepare(
                    "UPDATE realestate_investments
                     SET status = 'matured', yield_rate = :rate, total_paid_out = :total,
                         actual_return = :total, next_payout_at = :next, updated_at = NOW()
                     WHERE id = :id"
                )->execute([
                    'rate'  => $yield_rate,
                    'total' => $new_total,
                    'next'  => $next_payout_at,
                    'id'    => $inv['id'],
                ]);
            } else {
                $db->prepare(
                    "UPDATE realestate_investments
                     SET yield_rate = :rate, total_paid_out = :total,
                         next_payout_at = :next, updated_at = NOW()
                     WHERE id = :id"
                )->execute([
                    'rate'  => $yield_rate,
                    'total' => $new_total,
                    'next'  => $next_payout_at,
                    'id'    => $inv['id'],
                ]);
            }

            $db->prepare(
                "INSERT INTO transactions (user_id, type, amount, currency, status, notes)
                 VALUES (:uid, 'realestate_return', :amount, 'USD', 'completed', :note)"
            )->execute([
                'uid'    => $inv['user_id'],
                'amount' => $payout,
                'note'   => $inv['pool_name'] . ' payout — ' . $yield_rate . '% annual rate',
            ]);

            $db->commit();
            $processed++;

            // Send payout email (non-fatal)
            try {
                Mailer::sendProfitCredited(
                    $inv['email'],
                    $inv['full_name'],
                    $inv['pool_name'],
                    'Real Estate',
                    (float) $inv['amount'],
                    $payout,
                    0.0,
                    $payout,
                    $inv['starts_at'],
                    $inv['ends_at']
                );
            } catch (Exception $mailErr) {
                error_log('realestate-payouts cron: mail error for user ' . $inv['user_id'] . ': ' . $mailErr->getMessage());
            }

        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $failed++;
            $errors[] = 'RE Investment #' . $inv['id'] . ': ' . $e->getMessage();
        }
    }

    // ── Pass 2: finalize any overdue investments that missed last payout ──
    $overdue = $db->prepare(
        "SELECT id, user_id, pool_name, total_paid_out
         FROM realestate_investments
         WHERE status = 'active' AND ends_at <= NOW()"
    );
    $overdue->execute();
    foreach ($overdue->fetchAll() as $inv) {
        try {
            $db->prepare(
                "UPDATE realestate_investments
                 SET status = 'matured', actual_return = total_paid_out, updated_at = NOW()
                 WHERE id = :id"
            )->execute(['id' => $inv['id']]);
            $processed++;
        } catch (Exception $e) {
            $failed++;
            $errors[] = 'RE finalize #' . $inv['id'] . ': ' . $e->getMessage();
        }
    }

    $status  = $failed === 0 ? 'success' : ($processed > 0 ? 'partial' : 'failed');
    $message = "Processed: $processed, Failed: $failed" . ($errors ? ' | ' . implode('; ', $errors) : '');

    $db->prepare(
        "INSERT INTO cron_logs (job_name, status, message) VALUES ('realestate-payouts', :status, :msg)"
    )->execute(['status' => $status, 'msg' => $message]);

    echo $message . PHP_EOL;

} catch (PDOException $e) {
    echo 'Fatal error: ' . $e->getMessage() . PHP_EOL;
    try {
        $db->prepare(
            "INSERT INTO cron_logs (job_name, status, message) VALUES ('realestate-payouts', 'failed', :msg)"
        )->execute(['msg' => $e->getMessage()]);
    } catch (Exception $ignored) {}
}
