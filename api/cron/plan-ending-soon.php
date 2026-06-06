<?php
/**
 * Project: Qblockx
 * Cron: Plan Ending Soon Reminder
 * Run daily. Finds active investments across all 3 types (plan, commodity, real estate)
 * that end within the next 24 hours and sends an ending-soon reminder email.
 *
 * Recommended cron: 0 9 * * * php /path/to/api/cron/plan-ending-soon.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Access denied');
}

define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/api/utilities/email_templates.php';

$sent   = 0;
$failed = 0;
$errors = [];

try {
    $db = Database::getInstance()->getConnection();

    // ── Investment Plans ──────────────────────────────────────────────────────
    $stmt = $db->prepare(
        "SELECT pi.id, pi.plan_name AS item_name, pi.amount, pi.expected_return,
                pi.starts_at, pi.ends_at,
                u.email, u.full_name
         FROM plan_investments pi
         JOIN users u ON u.id = pi.user_id
         WHERE pi.status = 'active'
           AND pi.ends_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)"
    );
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        try {
            Mailer::sendPlanEndingSoon(
                $row['email'],
                $row['full_name'],
                $row['item_name'],
                'Investment Plan',
                (float) $row['amount'],
                round((float) $row['expected_return'] - (float) $row['amount'], 2),
                $row['starts_at'],
                $row['ends_at'],
                (string) $row['id']
            );
            $sent++;
        } catch (Exception $e) {
            $failed++;
            $errors[] = 'Plan #' . $row['id'] . ': ' . $e->getMessage();
        }
    }

    // ── Commodity Investments ─────────────────────────────────────────────────
    $stmt = $db->prepare(
        "SELECT ci.id, ci.asset_name AS item_name, ci.amount, ci.expected_return,
                ci.starts_at, ci.ends_at,
                u.email, u.full_name
         FROM commodity_investments ci
         JOIN users u ON u.id = ci.user_id
         WHERE ci.status = 'active'
           AND ci.ends_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)"
    );
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        try {
            Mailer::sendPlanEndingSoon(
                $row['email'],
                $row['full_name'],
                $row['item_name'],
                'Commodity',
                (float) $row['amount'],
                round((float) $row['expected_return'] - (float) $row['amount'], 2),
                $row['starts_at'],
                $row['ends_at'],
                (string) $row['id']
            );
            $sent++;
        } catch (Exception $e) {
            $failed++;
            $errors[] = 'Commodity #' . $row['id'] . ': ' . $e->getMessage();
        }
    }

    // ── Real Estate Investments ───────────────────────────────────────────────
    $stmt = $db->prepare(
        "SELECT ri.id, ri.pool_name AS item_name, ri.amount, ri.expected_return,
                ri.starts_at, ri.ends_at,
                u.email, u.full_name
         FROM realestate_investments ri
         JOIN users u ON u.id = ri.user_id
         WHERE ri.status = 'active'
           AND ri.ends_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)"
    );
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        try {
            Mailer::sendPlanEndingSoon(
                $row['email'],
                $row['full_name'],
                $row['item_name'],
                'Real Estate',
                (float) $row['amount'],
                round((float) $row['expected_return'] - (float) $row['amount'], 2),
                $row['starts_at'],
                $row['ends_at'],
                (string) $row['id']
            );
            $sent++;
        } catch (Exception $e) {
            $failed++;
            $errors[] = 'RE #' . $row['id'] . ': ' . $e->getMessage();
        }
    }

    $status  = $failed === 0 ? 'success' : ($sent > 0 ? 'partial' : 'failed');
    $message = "Sent: $sent, Failed: $failed" . ($errors ? ' | ' . implode('; ', $errors) : '');

    $db->prepare(
        "INSERT INTO cron_logs (job_name, status, message) VALUES ('plan-ending-soon', :status, :msg)"
    )->execute(['status' => $status, 'msg' => $message]);

    echo $message . PHP_EOL;

} catch (PDOException $e) {
    $msg = 'Fatal error: ' . $e->getMessage();
    error_log('plan-ending-soon cron fatal: ' . $msg);
    try {
        $db->prepare(
            "INSERT INTO cron_logs (job_name, status, message) VALUES ('plan-ending-soon', 'failed', :msg)"
        )->execute(['msg' => $msg]);
    } catch (Exception $ignored) {}
    echo $msg . PHP_EOL;
    exit(1);
}
