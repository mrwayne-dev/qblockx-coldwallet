<?php
/**
 * Project: qblockx
 * API: user-dashboard/loans.php — Loans (GET list | POST apply | POST repay)
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAuth();
$user = getAuthUser();

try {
    $db  = Database::getInstance()->getConnection();
    $uid = $user['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $statsStmt = $db->prepare(
            "SELECT COALESCE(SUM(loan_amount), 0)       AS total_borrowed,
                    COALESCE(SUM(remaining_balance), 0)  AS remaining_balance
             FROM loans WHERE user_id = :uid AND status = 'active'"
        );
        $statsStmt->execute(['uid' => $uid]);
        $stats = $statsStmt->fetch();

        $activeStmt = $db->prepare(
            "SELECT id, loan_amount, remaining_balance, interest_rate,
                    duration_months, monthly_payment, purpose, status, created_at
             FROM loans WHERE user_id = :uid AND status = 'active'
             ORDER BY created_at DESC"
        );
        $activeStmt->execute(['uid' => $uid]);
        $active_loans = $activeStmt->fetchAll();

        $pendingStmt = $db->prepare(
            "SELECT id, loan_amount, duration_months, purpose, status, created_at
             FROM loans WHERE user_id = :uid AND status = 'pending'
             ORDER BY created_at DESC"
        );
        $pendingStmt->execute(['uid' => $uid]);
        $pending_loans = $pendingStmt->fetchAll();

        echo json_encode([
            'total_borrowed'    => number_format((float) $stats['total_borrowed'],    2, '.', ''),
            'remaining_balance' => number_format((float) $stats['remaining_balance'], 2, '.', ''),
            'active_loans'      => $active_loans,
            'pending_loans'     => $pending_loans,
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action  = $input['action'] ?? 'apply';

        if ($action === 'apply') {
            $loan_amount     = (float)  ($input['loan_amount']     ?? 0);
            $duration_months = (int)    ($input['duration_months'] ?? 0);
            $purpose         = trim($input['purpose'] ?? '');

            if ($loan_amount <= 0)     { echo json_encode(['success' => false, 'message' => 'Enter a valid loan amount']); exit; }
            if ($duration_months <= 0) { echo json_encode(['success' => false, 'message' => 'Select a loan duration']); exit; }

            // Check for existing pending/active loan
            $existsStmt = $db->prepare(
                "SELECT COUNT(*) FROM loans WHERE user_id = :uid AND status IN ('pending','active')"
            );
            $existsStmt->execute(['uid' => $uid]);
            if ((int) $existsStmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'You already have an active or pending loan']);
                exit;
            }

            // Fetch rate for the selected duration — defensive to handle schema variations
            $interest_rate = 0.0;
            try {
                $rateStmt = $db->prepare(
                    "SELECT rate FROM rates WHERE product = 'loan'
                     AND duration_months = :dur AND is_active = 1 LIMIT 1"
                );
                $rateStmt->execute(['dur' => $duration_months]);
                $rateRow = $rateStmt->fetch();
                if ($rateRow) {
                    $interest_rate = (float) $rateRow['rate'];
                }
            } catch (PDOException $rateErr) {
                // rates table query failed — continue with 0% (admin can configure later)
            }
            // If no rate configured, reject the application rather than give a free loan
            if ($interest_rate <= 0) {
                echo json_encode(['success' => false, 'message' => 'No active loan rate found for the selected duration. Please contact support.']);
                exit;
            }

            // Amortisation formula: P * (r(1+r)^n) / ((1+r)^n - 1)
            $monthly_rate = $interest_rate / 100 / 12;
            if ($monthly_rate > 0) {
                $factor          = pow(1 + $monthly_rate, $duration_months);
                $monthly_payment = round($loan_amount * ($monthly_rate * $factor) / ($factor - 1), 2);
            } else {
                $monthly_payment = round($loan_amount / $duration_months, 2);
            }

            // Total repayable = full amortized cost (principal + interest over term)
            $total_repayable = round($monthly_payment * $duration_months, 2);

            $db->prepare(
                "INSERT INTO loans (user_id, loan_amount, remaining_balance, interest_rate, duration_months, monthly_payment, purpose)
                 VALUES (:uid, :loan_amount, :remaining_balance, :rate, :dur, :monthly, :purpose)"
            )->execute([
                'uid'               => $uid,
                'loan_amount'       => $loan_amount,
                'remaining_balance' => $total_repayable,
                'rate'              => $interest_rate,
                'dur'               => $duration_months,
                'monthly'           => $monthly_payment,
                'purpose'           => $purpose ?: null,
            ]);

            echo json_encode(['success' => true, 'message' => 'Loan application submitted! We will review it within 24 hours.']);

        } elseif ($action === 'repay') {
            $loan_id = (int)   ($input['loan_id'] ?? 0);
            $amount  = (float) ($input['amount']  ?? 0);

            if ($loan_id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid loan']); exit; }
            if ($amount <= 0)  { echo json_encode(['success' => false, 'message' => 'Enter a valid repayment amount']); exit; }

            // Verify loan belongs to user and is active
            $loanStmt = $db->prepare(
                "SELECT id, remaining_balance FROM loans WHERE id = :lid AND user_id = :uid AND status = 'active'"
            );
            $loanStmt->execute(['lid' => $loan_id, 'uid' => $uid]);
            $loan = $loanStmt->fetch();
            if (!$loan) { echo json_encode(['success' => false, 'message' => 'Loan not found']); exit; }

            // Cap at remaining balance
            $amount = min($amount, (float) $loan['remaining_balance']);

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

            // Reduce remaining balance; close loan if fully repaid
            $new_balance = (float) $loan['remaining_balance'] - $amount;
            $new_status  = $new_balance <= 0 ? 'closed' : 'active';
            $db->prepare(
                "UPDATE loans SET remaining_balance = :bal, status = :status WHERE id = :lid"
            )->execute(['bal' => max(0, $new_balance), 'status' => $new_status, 'lid' => $loan_id]);

            // Log transaction
            $db->prepare(
                "INSERT INTO transactions (user_id, type, amount, status, notes)
                 VALUES (:uid, 'loan_repayment', :amount, 'completed', 'Loan repayment')"
            )->execute(['uid' => $uid, 'amount' => $amount]);

            $db->commit();
            $msg = $new_status === 'closed'
                ? 'Loan fully repaid! Congratulations.'
                : 'Repayment of $' . number_format($amount, 2) . ' recorded successfully.';
            echo json_encode(['success' => true, 'message' => $msg]);

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
