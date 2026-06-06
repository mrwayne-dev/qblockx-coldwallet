<?php
/**
 * Project: Qblockx
 * Cron: Commodity Investment Maturity
 * Run daily. Finds active commodity_investments whose ends_at has passed,
 * assigns a random yield within the asset's range, credits wallet, marks matured.
 *
 * Recommended cron: 0 1 * * * php /path/to/api/cron/commodity-maturity.php
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
        "SELECT ci.id, ci.user_id, ci.asset_name, ci.amount,
                ci.starts_at, ci.ends_at,
                ca.yield_min, ca.yield_max,
                u.email, u.full_name
         FROM commodity_investments ci
         JOIN commodity_assets ca ON ca.id = ci.asset_id
         JOIN users u ON u.id = ci.user_id
         WHERE ci.status = 'active' AND ci.ends_at <= NOW()"
    );
    $stmt->execute();
    $matured = $stmt->fetchAll();

    foreach ($matured as $pos) {
        try {
            $yield_rate    = round((float) $pos['yield_min'] + mt_rand(0, 100) / 100 * ((float) $pos['yield_max'] - (float) $pos['yield_min']), 2);
            $actual_return = round((float) $pos['amount'] + ((float) $pos['amount'] * $yield_rate / 100), 2);

            $db->beginTransaction();

            $db->prepare(
                "UPDATE wallets SET balance = balance + :amount, updated_at = NOW() WHERE user_id = :uid"
            )->execute(['amount' => $actual_return, 'uid' => $pos['user_id']]);

            $db->prepare(
                "UPDATE commodity_investments
                 SET status = 'matured', yield_rate = :rate, actual_return = :ret, updated_at = NOW()
                 WHERE id = :id"
            )->execute(['rate' => $yield_rate, 'ret' => $actual_return, 'id' => $pos['id']]);

            $db->prepare(
                "INSERT INTO transactions (user_id, type, amount, currency, status, notes)
                 VALUES (:uid, 'commodity_return', :amount, 'USD', 'completed', :note)"
            )->execute([
                'uid'    => $pos['user_id'],
                'amount' => $actual_return,
                'note'   => $pos['asset_name'] . ' commodity position matured — ' . $yield_rate . '% return',
            ]);

            $db->commit();
            $processed++;

            // Send profit credited email (non-fatal)
            try {
                Mailer::sendProfitCredited(
                    $pos['email'],
                    $pos['full_name'],
                    $pos['asset_name'],
                    'Commodity',
                    (float) $pos['amount'],
                    round($actual_return - (float) $pos['amount'], 2),
                    0.0,
                    $actual_return,
                    $pos['starts_at'],
                    $pos['ends_at']
                );
            } catch (Exception $mailErr) {
                error_log('commodity-maturity cron: mail error for user ' . $pos['user_id'] . ': ' . $mailErr->getMessage());
            }

        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $failed++;
            $errors[] = 'Position #' . $pos['id'] . ': ' . $e->getMessage();
        }
    }

    $status  = $failed === 0 ? 'success' : ($processed > 0 ? 'partial' : 'failed');
    $message = "Processed: $processed, Failed: $failed" . ($errors ? ' | ' . implode('; ', $errors) : '');

    $db->prepare(
        "INSERT INTO cron_logs (job_name, status, message) VALUES ('commodity-maturity', :status, :msg)"
    )->execute(['status' => $status, 'msg' => $message]);

    echo $message . PHP_EOL;

} catch (PDOException $e) {
    echo 'Fatal error: ' . $e->getMessage() . PHP_EOL;
    try {
        $db->prepare(
            "INSERT INTO cron_logs (job_name, status, message) VALUES ('commodity-maturity', 'failed', :msg)"
        )->execute(['msg' => $e->getMessage()]);
    } catch (Exception $ignored) {}
}
