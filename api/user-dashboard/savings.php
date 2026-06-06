<?php
/**
 * Project: qblockx
 * API: user-dashboard/savings.php — Savings plans (GET list | POST create | POST add-funds)
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once '../../api/utilities/email_templates.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

try {
    $db  = Database::getInstance()->getConnection();
    $uid = $user['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Aggregate stats
        $statsStmt = $db->prepare(
            "SELECT COALESCE(SUM(current_amount), 0) AS total_saved,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_count
             FROM savings_plans WHERE user_id = :uid"
        );
        $statsStmt->execute(['uid' => $uid]);
        $stats = $statsStmt->fetch();

        // All plans
        $plansStmt = $db->prepare(
            "SELECT id, plan_name, target_amount, current_amount, interest_rate,
                    duration_months, status, created_at
             FROM savings_plans WHERE user_id = :uid
             ORDER BY created_at DESC"
        );
        $plansStmt->execute(['uid' => $uid]);
        $plans = $plansStmt->fetchAll();

        echo json_encode([
            'total_saved'  => number_format((float) $stats['total_saved'], 2, '.', ''),
            'active_count' => (int) $stats['active_count'],
            'plans'        => $plans,
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $input['action'] ?? 'create';

        if ($action === 'create') {
            $plan_name       = trim($input['plan_name']     ?? '');
            $target_amount   = (float) ($input['target_amount']   ?? 0);
            $duration_months = (int)   ($input['duration_months'] ?? 0);

            if (empty($plan_name))     { echo json_encode(['success' => false, 'message' => 'Plan name is required']); exit; }
            if ($target_amount <= 0)   { echo json_encode(['success' => false, 'message' => 'Target amount must be greater than 0']); exit; }
            if ($duration_months <= 0) { echo json_encode(['success' => false, 'message' => 'Please select a savings plan']); exit; }

            // Always look up rate from DB — never trust client-provided rate
            $rateStmt = $db->prepare(
                "SELECT rate FROM rates WHERE product = 'savings'
                 AND duration_months = :dur AND is_active = 1 LIMIT 1"
            );
            $rateStmt->execute(['dur' => $duration_months]);
            $rateRow = $rateStmt->fetch();
            if (!$rateRow) {
                echo json_encode(['success' => false, 'message' => 'No active rate found for the selected plan']);
                exit;
            }
            $interest_rate = (float) $rateRow['rate'];

            // Deduct target_amount from wallet and lock it in the savings plan immediately
            $db->beginTransaction();

            $walletStmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = :uid FOR UPDATE");
            $walletStmt->execute(['uid' => $uid]);
            $wallet = $walletStmt->fetch();

            if (!$wallet || (float) $wallet['balance'] < $target_amount) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance to start this savings plan']);
                exit;
            }

            // Debit wallet
            $db->prepare("UPDATE wallets SET balance = balance - :amount WHERE user_id = :uid")
               ->execute(['amount' => $target_amount, 'uid' => $uid]);

            // Create savings plan with current_amount already set to target_amount
            $db->prepare(
                "INSERT INTO savings_plans (user_id, plan_name, target_amount, current_amount, interest_rate, duration_months)
                 VALUES (:uid, :name, :target, :current, :rate, :duration)"
            )->execute([
                'uid'      => $uid,
                'name'     => $plan_name,
                'target'   => $target_amount,
                'current'  => $target_amount,
                'rate'     => $interest_rate,
                'duration' => $duration_months,
            ]);

            // Log the deduction as a savings_contribution transaction
            $db->prepare(
                "INSERT INTO transactions (user_id, type, amount, status, notes)
                 VALUES (:uid, 'savings_contribution', :amount, 'completed', :notes)"
            )->execute([
                'uid'    => $uid,
                'amount' => $target_amount,
                'notes'  => 'Savings plan created: ' . $plan_name,
            ]);

            $db->commit();

            // Send confirmation email (non-fatal)
            try {
                $userRow = $db->prepare("SELECT full_name, email FROM users WHERE id = :uid LIMIT 1");
                $userRow->execute(['uid' => $uid]);
                $userInfo = $userRow->fetch();
                if ($userInfo) {
                    Mailer::sendSavingsPlanCreated(
                        $userInfo['email'],
                        $userInfo['full_name'],
                        $plan_name,
                        number_format($target_amount, 2),
                        (string) $interest_rate,
                        $duration_months
                    );
                }
            } catch (Exception $mailErr) {}

            echo json_encode(['success' => true, 'message' => 'Savings plan created successfully!']);

        } elseif ($action === 'add_funds') {
            $plan_id = (int)   ($input['plan_id'] ?? 0);
            $amount  = (float) ($input['amount']  ?? 0);

            if ($plan_id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid plan']); exit; }
            if ($amount <= 0)  { echo json_encode(['success' => false, 'message' => 'Enter a valid amount']); exit; }

            // Verify plan belongs to user
            $checkStmt = $db->prepare("SELECT id FROM savings_plans WHERE id = :pid AND user_id = :uid AND status = 'active'");
            $checkStmt->execute(['pid' => $plan_id, 'uid' => $uid]);
            if (!$checkStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Savings plan not found']);
                exit;
            }

            // Check wallet balance
            $walletStmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = :uid FOR UPDATE");
            $db->beginTransaction();
            $walletStmt->execute(['uid' => $uid]);
            $wallet = $walletStmt->fetch();
            if (!$wallet || (float) $wallet['balance'] < $amount) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance']);
                exit;
            }

            // Debit wallet
            $db->prepare("UPDATE wallets SET balance = balance - :amount WHERE user_id = :uid")
               ->execute(['amount' => $amount, 'uid' => $uid]);

            // Credit savings plan
            $db->prepare("UPDATE savings_plans SET current_amount = current_amount + :amount WHERE id = :pid")
               ->execute(['amount' => $amount, 'pid' => $plan_id]);

            // Log transaction
            $db->prepare(
                "INSERT INTO transactions (user_id, type, amount, status, notes)
                 VALUES (:uid, 'savings_contribution', :amount, 'completed', 'Savings plan contribution')"
            )->execute(['uid' => $uid, 'amount' => $amount]);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Funds added to savings plan!']);

        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
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
