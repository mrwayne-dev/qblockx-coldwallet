<?php
/**
 * Project: Qblockx
 * Cron: Investment Plan Maturity
 * Run daily. Finds active plan_investments whose ends_at has passed,
 * assigns a random yield within the plan's range, credits wallet, marks matured.
 *
 * Recommended cron: 0 0 * * * php /path/to/api/cron/investment-maturity.php
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

    $stmt = $db->prepare(
        "SELECT pi.id, pi.user_id, pi.plan_id, pi.plan_name, pi.amount,
                pi.starts_at, pi.ends_at,
                ip.yield_min, ip.yield_max, ip.commission_pct,
                u.email, u.full_name
         FROM plan_investments pi
         JOIN investment_plans ip ON ip.id = pi.plan_id
         JOIN users u ON u.id = pi.user_id
         WHERE pi.status = 'active' AND pi.ends_at <= NOW()"
    );
    $stmt->execute();
    $matured = $stmt->fetchAll();

    foreach ($matured as $inv) {
        try {
            // Random yield within plan range (two decimal places)
            $yield_rate    = round((float) $inv['yield_min'] + mt_rand(0, 100) / 100 * ((float) $inv['yield_max'] - (float) $inv['yield_min']), 2);
            $actual_return = round((float) $inv['amount'] + ((float) $inv['amount'] * $yield_rate / 100), 2);

            $db->beginTransaction();

            $db->prepare(
                "UPDATE wallets SET balance = balance + :amount, updated_at = NOW() WHERE user_id = :uid"
            )->execute(['amount' => $actual_return, 'uid' => $inv['user_id']]);

            $db->prepare(
                "UPDATE plan_investments
                 SET status = 'matured', yield_rate = :rate, actual_return = :ret, updated_at = NOW()
                 WHERE id = :id"
            )->execute(['rate' => $yield_rate, 'ret' => $actual_return, 'id' => $inv['id']]);

            $db->prepare(
                "INSERT INTO transactions (user_id, type, amount, currency, status, notes)
                 VALUES (:uid, 'investment_return', :amount, 'USD', 'completed', :note)"
            )->execute([
                'uid'    => $inv['user_id'],
                'amount' => $actual_return,
                'note'   => $inv['plan_name'] . ' plan matured — ' . $yield_rate . '% return',
            ]);

            $db->commit();
            $processed++;

            // Send profit credited email (non-fatal)
            try {
                Mailer::sendProfitCredited(
                    $inv['email'],
                    $inv['full_name'],
                    $inv['plan_name'],
                    'Investment Plan',
                    (float) $inv['amount'],
                    round($actual_return - (float) $inv['amount'], 2),
                    (float) $inv['commission_pct'],
                    $actual_return,
                    $inv['starts_at'],
                    $inv['ends_at']
                );
            } catch (Exception $mailErr) {
                error_log('investment-maturity cron: mail error for user ' . $inv['user_id'] . ': ' . $mailErr->getMessage());
            }

        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $failed++;
            $errors[] = 'Investment #' . $inv['id'] . ': ' . $e->getMessage();
        }
    }

    $status  = $failed === 0 ? 'success' : ($processed > 0 ? 'partial' : 'failed');
    $message = "Processed: $processed, Failed: $failed" . ($errors ? ' | ' . implode('; ', $errors) : '');

    $db->prepare(
        "INSERT INTO cron_logs (job_name, status, message) VALUES ('investment-maturity', :status, :msg)"
    )->execute(['status' => $status, 'msg' => $message]);

    echo $message . PHP_EOL;

} catch (PDOException $e) {
    echo 'Fatal error: ' . $e->getMessage() . PHP_EOL;
    try {
        $db->prepare(
            "INSERT INTO cron_logs (job_name, status, message) VALUES ('investment-maturity', 'failed', :msg)"
        )->execute(['msg' => $e->getMessage()]);
    } catch (Exception $ignored) {}
}
