<?php
/**
 * Project: qblockx
 * API: admin-dashboard/loans.php — Admin loans management (approve/reject/disburse)
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once '../../api/utilities/email_templates.php';
header('Content-Type: application/json');

requireAdmin();

try {
    $db = Database::getInstance()->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        // Pending applications
        $pendingStmt = $db->prepare(
            "SELECT l.id, l.loan_amount, l.duration_months, l.monthly_payment,
                    l.interest_rate, l.purpose, l.created_at,
                    u.full_name AS user_name, u.email AS user_email
             FROM loans l JOIN users u ON u.id = l.user_id
             WHERE l.status = 'pending'
             ORDER BY l.created_at ASC"
        );
        $pendingStmt->execute();
        $pending = $pendingStmt->fetchAll();

        // Active loans (paginated)
        $total = $db->query("SELECT COUNT(*) FROM loans WHERE status = 'active'")->fetchColumn();
        $activeStmt = $db->prepare(
            "SELECT l.id, l.loan_amount, l.remaining_balance, l.monthly_payment,
                    l.interest_rate, l.duration_months, l.status, l.created_at,
                    u.full_name AS user_name, u.email AS user_email
             FROM loans l JOIN users u ON u.id = l.user_id
             WHERE l.status = 'active'
             ORDER BY l.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $activeStmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $activeStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $activeStmt->execute();
        $active = $activeStmt->fetchAll();

        $totalDisbursed   = $db->query("SELECT COALESCE(SUM(loan_amount), 0) FROM loans WHERE status IN ('active','closed')")->fetchColumn();
        $totalOutstanding = $db->query("SELECT COALESCE(SUM(remaining_balance), 0) FROM loans WHERE status = 'active'")->fetchColumn();

        echo json_encode([
            'success'          => true,
            'total_disbursed'  => number_format((float) $totalDisbursed,   2, '.', ''),
            'total_outstanding'=> number_format((float) $totalOutstanding, 2, '.', ''),
            'pending_count'    => count($pending),
            'total'            => (int) $total,
            'page'             => $page,
            'pages'            => (int) ceil($total / $limit),
            'pending'          => $pending,
            'active'           => $active,
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action  = $input['action'] ?? '';
        $id      = (int) ($input['id'] ?? 0);

        if ($action === 'approve' && $id > 0) {
            // Fetch loan + user details
            $loanStmt = $db->prepare(
                "SELECT l.user_id, l.loan_amount, l.monthly_payment, l.duration_months,
                        u.email, u.full_name
                 FROM loans l JOIN users u ON u.id = l.user_id
                 WHERE l.id = :id AND l.status = 'pending'"
            );
            $loanStmt->execute(['id' => $id]);
            $loan = $loanStmt->fetch();
            if (!$loan) { echo json_encode(['success' => false, 'message' => 'Loan not found']); exit; }

            $db->beginTransaction();
            // Activate loan
            $db->prepare("UPDATE loans SET status = 'active' WHERE id = :id")->execute(['id' => $id]);
            // Credit wallet
            $db->prepare("UPDATE wallets SET balance = balance + :amount WHERE user_id = :uid")
               ->execute(['amount' => $loan['loan_amount'], 'uid' => $loan['user_id']]);
            // Log transaction
            $db->prepare("INSERT INTO transactions (user_id, type, amount, status, notes) VALUES (:uid, 'loan_disbursement', :amount, 'completed', 'Loan approved and disbursed')")
               ->execute(['uid' => $loan['user_id'], 'amount' => $loan['loan_amount']]);
            $db->commit();

            // Send approval email (non-fatal)
            try {
                Mailer::sendLoanApproved(
                    $loan['email'],
                    $loan['full_name'],
                    number_format((float) $loan['loan_amount'], 2),
                    number_format((float) $loan['monthly_payment'], 2),
                    (int) $loan['duration_months']
                );
            } catch (Exception $mailErr) {}

            echo json_encode(['success' => true, 'message' => 'Loan approved and disbursed to user wallet']);

        } elseif ($action === 'reject' && $id > 0) {
            $notes = trim($input['notes'] ?? 'Application rejected');

            // Fetch user + loan details before update for email
            $rejectInfo = $db->prepare(
                "SELECT l.loan_amount, u.email, u.full_name
                 FROM loans l JOIN users u ON u.id = l.user_id
                 WHERE l.id = :id LIMIT 1"
            );
            $rejectInfo->execute(['id' => $id]);
            $rejectLoan = $rejectInfo->fetch();

            $db->prepare("UPDATE loans SET status = 'rejected', admin_notes = :notes WHERE id = :id")
               ->execute(['notes' => $notes, 'id' => $id]);

            // Send rejection email (non-fatal)
            if ($rejectLoan) {
                try {
                    Mailer::sendLoanRejected(
                        $rejectLoan['email'],
                        $rejectLoan['full_name'],
                        number_format((float) $rejectLoan['loan_amount'], 2),
                        $notes !== 'Application rejected' ? $notes : ''
                    );
                } catch (Exception $mailErr) {}
            }

            echo json_encode(['success' => true, 'message' => 'Loan application rejected']);

        } elseif ($action === 'close' && $id > 0) {
            $db->prepare("UPDATE loans SET status = 'closed', remaining_balance = 0 WHERE id = :id")->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Loan closed']);

        } elseif ($action === 'record_repayment' && $id > 0) {
            $amount = (float) ($input['amount'] ?? 0);
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid repayment amount']);
                exit;
            }

            $loanStmt = $db->prepare(
                "SELECT user_id, remaining_balance, status FROM loans WHERE id = :id"
            );
            $loanStmt->execute(['id' => $id]);
            $loan = $loanStmt->fetch();

            if (!$loan) {
                echo json_encode(['success' => false, 'message' => 'Loan not found']);
                exit;
            }
            if ($loan['status'] !== 'active') {
                echo json_encode(['success' => false, 'message' => 'Only active loans can receive repayments']);
                exit;
            }
            if ($amount > (float) $loan['remaining_balance']) {
                echo json_encode(['success' => false, 'message' => 'Repayment exceeds remaining balance']);
                exit;
            }

            $newBalance  = max(0, (float) $loan['remaining_balance'] - $amount);
            $newStatus   = ($newBalance <= 0) ? 'closed' : 'active';

            $db->beginTransaction();
            $db->prepare(
                "UPDATE loans SET remaining_balance = :bal, status = :status WHERE id = :id"
            )->execute(['bal' => $newBalance, 'status' => $newStatus, 'id' => $id]);
            $db->prepare(
                "INSERT INTO transactions (user_id, type, amount, status, notes)
                 VALUES (:uid, 'loan_repayment', :amount, 'completed', 'Loan repayment (admin recorded)')"
            )->execute(['uid' => $loan['user_id'], 'amount' => $amount]);
            $db->commit();

            echo json_encode([
                'success'           => true,
                'message'           => $newStatus === 'closed' ? 'Loan fully repaid and closed' : 'Repayment recorded',
                'remaining_balance' => number_format($newBalance, 2, '.', ''),
                'new_status'        => $newStatus,
            ]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action or missing ID']);
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
