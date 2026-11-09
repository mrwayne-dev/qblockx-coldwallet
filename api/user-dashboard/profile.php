<?php
/**
 * Quantum BlocX — API: user-dashboard/profile.php
 * GET                      → Full user profile
 * GET  ?action=recovery_phrase → Recovery phrase (authenticated)
 * POST                     → Update profile (name)
 * POST {action: change_password} → Change password
 * POST ?action=kyc         → Submit KYC application (multipart/form-data)
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once '../../api/utilities/email_templates.php';
header('Content-Type: application/json');

requireAuth();
$auth = getAuthUser();

try {
    $db  = Database::getInstance()->getConnection();
    $uid = $auth['id'];

    // ── POST ─────────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_GET['action'] ?? '';

        // ── KYC Submission ───────────────────────────────────────────
        if ($action === 'kyc') {
            // Check if already submitted
            $existStmt = $db->prepare("SELECT status FROM kyc_applications WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
            $existStmt->execute(['uid' => $uid]);
            $existing = $existStmt->fetchColumn();

            if ($existing && in_array($existing, ['pending', 'under_review', 'approved'])) {
                echo json_encode(['success' => false, 'message' => 'KYC application already submitted']);
                exit;
            }

            $firstName   = trim($_POST['first_name'] ?? '');
            $lastName    = trim($_POST['last_name'] ?? '');
            $email       = trim($_POST['email'] ?? '');
            $phone       = trim($_POST['phone'] ?? '');
            $dob         = trim($_POST['date_of_birth'] ?? '');
            $social      = trim($_POST['social_handle'] ?? '');
            $addressLine = trim($_POST['address_line'] ?? '');
            $city        = trim($_POST['city'] ?? '');
            $state       = trim($_POST['state'] ?? '');
            $nationality = trim($_POST['nationality'] ?? '');
            $docType     = trim($_POST['document_type'] ?? '');

            if (!$firstName || !$lastName || !$email || !$phone || !$dob || !$addressLine || !$city || !$state || !$nationality || !$docType) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit;
            }

            // Handle file uploads
            $uploadDir = __DIR__ . '/../../uploads/kyc/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // Validated save: allow-listed type by real MIME (never the client's
            // extension), size limit, random name. Returns [url|null, error|null].
            $saveKycFile = function (string $field, string $side) use ($uploadDir, $uid): array {
                if (empty($_FILES[$field]['tmp_name'])) return [null, null];
                $f = $_FILES[$field];
                if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                    return [null, 'Upload failed for the document ' . $side . ' (error ' . (int)$f['error'] . ')'];
                }
                if (!is_uploaded_file($f['tmp_name'])) return [null, 'Invalid upload'];
                if ($f['size'] > 5 * 1024 * 1024) return [null, 'Document ' . $side . ' must be 5MB or smaller'];

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($f['tmp_name']);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
                if (!isset($allowed[$mime])) {
                    return [null, 'Document ' . $side . ' must be a JPG, PNG or PDF file'];
                }

                $name = 'kyc_' . $uid . '_' . $side . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
                if (!move_uploaded_file($f['tmp_name'], $uploadDir . $name)) {
                    error_log('profile.php KYC: move_uploaded_file failed for ' . $uploadDir . $name);
                    return [null, 'Could not store the document ' . $side . ', please try again'];
                }
                return ['/uploads/kyc/' . $name, null];
            };

            [$frontUrl, $frontErr] = $saveKycFile('document_front', 'front');
            [$backUrl,  $backErr]  = $saveKycFile('document_back',  'back');

            if ($frontErr || $backErr) {
                echo json_encode(['success' => false, 'message' => $frontErr ?: $backErr]);
                exit;
            }
            if (!$frontUrl) {
                echo json_encode(['success' => false, 'message' => 'Document front photo is required']);
                exit;
            }

            $stmt = $db->prepare(
                "INSERT INTO kyc_applications
                    (user_id, first_name, last_name, email, phone_number, date_of_birth,
                     social_handle, address_line, city, state, nationality,
                     document_type, document_front_url, document_back_url, terms_accepted)
                 VALUES (:uid, :fn, :ln, :em, :ph, :dob, :soc, :addr, :city, :state, :nat, :doc, :front, :back, 1)"
            );
            $stmt->execute([
                'uid'   => $uid,   'fn'    => $firstName, 'ln'   => $lastName,
                'em'    => $email, 'ph'    => $phone,     'dob'  => $dob,
                'soc'   => $social ?: null, 'addr' => $addressLine,
                'city'  => $city,  'state' => $state,     'nat'  => $nationality,
                'doc'   => $docType, 'front' => $frontUrl, 'back' => $backUrl ?: null,
            ]);

            // Update user kyc_status
            $db->prepare("UPDATE users SET kyc_status = 'pending' WHERE id = :uid")->execute(['uid' => $uid]);

            // Notify the user that their application was received and is under review
            $acct = $db->prepare("SELECT email, full_name FROM users WHERE id = :uid");
            $acct->execute(['uid' => $uid]);
            $acctRow = $acct->fetch(PDO::FETCH_ASSOC) ?: [];
            $notifyEmail = $acctRow['email'] ?: $email;
            $notifyName  = trim($acctRow['full_name'] ?: ($firstName . ' ' . $lastName)) ?: 'there';
            $body = "We've received your identity verification (KYC) application and it is now under review.\n\n"
                  . "Our compliance team will review your details and documents shortly. You'll receive another "
                  . "email once your verification has been approved or if we need any additional information.\n\n"
                  . "No further action is required from you at this time.";
            try {
                Mailer::sendAdminMessage($notifyEmail, $notifyName, 'KYC application submitted — Quantum BlocX', $body);
            } catch (\Throwable $e) {
                error_log('KYC submit email failed: ' . $e->getMessage());
            }

            echo json_encode(['success' => true, 'message' => 'KYC application submitted successfully']);
            exit;
        }

        // ── Profile update / password change (JSON body) ─────────────
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $inputAction = $input['action'] ?? '';

        // ── Change Password ──────────────────────────────────────────
        if ($inputAction === 'change_password') {
            $curPass = $input['current_password'] ?? '';
            $newPass = $input['new_password'] ?? '';

            if (!$curPass || !$newPass) {
                echo json_encode(['success' => false, 'message' => 'Both current and new password are required']);
                exit;
            }
            if (strlen($newPass) < 8) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters']);
                exit;
            }

            $passStmt = $db->prepare("SELECT password FROM users WHERE id = :uid");
            $passStmt->execute(['uid' => $uid]);
            $hash = $passStmt->fetchColumn();

            if (!password_verify($curPass, $hash)) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }

            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password = :pw WHERE id = :uid")
               ->execute(['pw' => $newHash, 'uid' => $uid]);

            echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
            exit;
        }

        // ── Update Profile (name) ────────────────────────────────────
        $fullName = trim($input['full_name'] ?? '');
        if ($fullName) {
            $db->prepare("UPDATE users SET full_name = :name WHERE id = :uid")
               ->execute(['name' => $fullName, 'uid' => $uid]);
        }

        echo json_encode(['success' => true, 'message' => 'Profile updated']);
        exit;
    }

    // ── GET ──────────────────────────────────────────────────────────
    $action = $_GET['action'] ?? '';

    // ── Recovery Phrase ──────────────────────────────────────────────
    if ($action === 'recovery_phrase') {
        $stmt = $db->prepare("SELECT recovery_phrase FROM users WHERE id = :uid");
        $stmt->execute(['uid' => $uid]);
        $phrase = $stmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'data'    => ['phrase' => $phrase ?: 'No recovery phrase generated']
        ]);
        exit;
    }

    // ── Full Profile ─────────────────────────────────────────────────
    $stmt = $db->prepare(
        "SELECT id, email, full_name, username, avatar_url, current_ip,
                kyc_status, card_tier, two_fa_enabled, is_verified, is_active,
                created_at, updated_at, last_login_at
         FROM users WHERE id = :uid"
    );
    $stmt->execute(['uid' => $uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Add email_verified_at for JS badge logic
    $user['email_verified_at'] = $user['is_verified'] ? $user['created_at'] : null;

    echo json_encode(['success' => true, 'data' => $user]);

} catch (PDOException $e) {
    error_log('profile.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
