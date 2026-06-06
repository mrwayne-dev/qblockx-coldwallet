<?php
/**
 * Project: qblockx
 * Central Mailer: loads HTML templates, fills {{placeholders}}, sends via PHPMailer SMTP
 *
 * Templates live in assets/email-templates/ and are numbered 01–20.
 * Do not modify the HTML template files — only this class is updated.
 *
 * Usage:
 *   require_once '/path/to/api/utilities/email_templates.php';
 *   Mailer::sendVerification($email, $name, $code);
 */

class Mailer
{
    private static string $lastError = '';

    public static function getLastError(): string { return self::$lastError; }

    // ── Template root ────────────────────────────────────────────────────────
    private static function templateDir(): string
    {
        return dirname(__DIR__, 2) . '/assets/email-templates/';
    }

    // ── Render ───────────────────────────────────────────────────────────────
    // $rawVars bypasses htmlspecialchars — pass pre-escaped HTML content here
    public static function render(string $templateFile, array $vars = [], array $rawVars = []): string
    {
        $path = self::templateDir() . $templateFile;
        if (!file_exists($path)) {
            error_log('[Mailer] Template not found: ' . $templateFile);
            return '';
        }

        $html    = file_get_contents($path);
        $appName = getenv('APP_NAME') ?: 'Qblockx';
        $appUrl  = rtrim(getenv('APP_URL') ?: 'https://qblockx.com', '/');
        $appHost = parse_url($appUrl, PHP_URL_HOST) ?: 'qblockx.com';

        $allVars = array_merge([
            'year'      => date('Y'),
            'app_url'   => $appUrl,
            'app_name'  => $appName,
            'app_host'  => $appHost,
            'admin_url' => $appUrl . '/admin',
            'logo_url'  => $appUrl . '/assets/images/logo/logoblue.png',
        ], $vars);

        foreach ($allVars as $key => $value) {
            $html = str_replace(
                '{{' . $key . '}}',
                htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $html
            );
        }

        // Raw vars — inserted as-is (caller is responsible for escaping)
        foreach ($rawVars as $key => $value) {
            $html = str_replace('{{' . $key . '}}', (string) $value, $html);
        }

        // Blank any unfilled placeholders
        $html = preg_replace('/\{\{[^}]+\}\}/', '', $html);

        return $html;
    }

    // ── Core send ────────────────────────────────────────────────────────────
    public static function send(string $to, string $toName, string $subject, string $html): bool
    {
        if (empty($html)) return false;

        try {
            require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

            $port       = (int) (getenv('SMTP_PORT') ?: 587);
            $encryption = ($port === 465)
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

            $mail             = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = getenv('SMTP_HOST') ?: '';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USER') ?: '';
            $mail->Password   = getenv('SMTP_PASS') ?: '';
            $mail->SMTPSecure = $encryption;
            $mail->Port       = $port;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 5;

            $mail->setFrom(
                getenv('SMTP_FROM')      ?: 'noreply@qblockx.com',
                getenv('SMTP_FROM_NAME') ?: (getenv('APP_NAME') ?: 'Qblockx')
            );
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;

            // Embed logo so it renders without external-image blocking
            $logoPath = dirname(__DIR__, 2) . '/assets/images/logo/logoblue.png';
            if (file_exists($logoPath)) {
                $appUrl  = rtrim(getenv('APP_URL') ?: 'https://qblockx.com', '/');
                $logoUrl = $appUrl . '/assets/images/logo/logoblue.png';
                $mail->addEmbeddedImage($logoPath, 'qblockx_logo', 'logoblue.png', 'base64', 'image/png');
                $html = str_replace($logoUrl, 'cid:qblockx_logo', $html);
            }

            $mail->Body    = $html;
            $mail->AltBody = strip_tags($html);

            $mail->send();
            return true;

        } catch (\Throwable $e) {
            self::$lastError = $e->getMessage();
            error_log('[Mailer] Failed to send to ' . $to . ' | Subject: ' . $subject . ' | Error: ' . $e->getMessage());
            return false;
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // 01 — Welcome
    // ────────────────────────────────────────────────────────────────────────
    public static function sendWelcome(string $email, string $name): bool
    {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('01_welcome.html', [
            'first_name' => $firstName ?: 'there',
        ]);

        return self::send($email, $name, 'Welcome to ' . $appName . '!', $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 02 — Email Verification
    // ────────────────────────────────────────────────────────────────────────
    public static function sendVerification(string $email, string $name, string $code): bool
    {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('02_email_verification.html', [
            'first_name'        => $firstName ?: 'there',
            'verification_code' => $code,
        ]);

        return self::send(
            $email,
            $name,
            'Your verification code is ' . $code . ' — ' . $appName,
            $html
        );
    }

    // ────────────────────────────────────────────────────────────────────────
    // 03 — Password Reset
    // ────────────────────────────────────────────────────────────────────────
    public static function sendPasswordReset(string $email, string $name, string $resetUrl): bool
    {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('03_password_reset.html', [
            'first_name' => $firstName ?: 'there',
            'reset_url'  => $resetUrl,
        ]);

        return self::send($email, $name, 'Reset your password — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 04 — Logout / Security Notification
    // ────────────────────────────────────────────────────────────────────────
    public static function sendLogoutNotification(
        string $email,
        string $name,
        string $logoutTime,
        string $device   = '',
        string $browser  = '',
        string $ip       = '',
        string $location = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('04_logout_notification.html', [
            'first_name'  => $firstName ?: 'there',
            'logout_time' => $logoutTime,
            'device'      => $device,
            'browser'     => $browser,
            'ip_address'  => $ip,
            'location'    => $location,
        ]);

        return self::send($email, $name, 'Session ended — ' . $appName, $html);
    }

    // Backward-compat: sign-in alerts map to the logout/security notification template
    public static function sendUserSignIn(string $email, string $name, string $loginTime): bool
    {
        return self::sendLogoutNotification($email, $name, $loginTime);
    }

    public static function sendAdminSignIn(string $email, string $name, string $loginTime): bool
    {
        return self::sendLogoutNotification($email, $name, $loginTime);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 05 — Investment Plan Activated
    // ────────────────────────────────────────────────────────────────────────
    public static function sendPlanActivated(
        string $email,
        string $name,
        string $planName,
        string $planTier,
        float  $amount,
        string $duration,
        string $startDate,
        string $endDate,
        float  $expectedReturn,
        string $investmentId  = '',
        string $transactionId = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('05_plan_activated.html', [
            'first_name'      => $firstName ?: 'there',
            'plan_name'       => $planName,
            'plan_tier'       => $planTier,
            'amount'          => number_format($amount, 2),
            'duration'        => $duration,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'expected_return' => number_format($expectedReturn, 2),
            'investment_id'   => $investmentId,
            'transaction_id'  => $transactionId,
        ]);

        return self::send($email, $name, 'Investment activated: ' . $planName . ' — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 06 — Commodity Investment Activated
    // ────────────────────────────────────────────────────────────────────────
    public static function sendCommodityActivated(
        string $email,
        string $name,
        string $commodityName,
        string $entryPrice,
        string $projectedYield,
        float  $amount,
        string $duration,
        string $startDate,
        string $endDate,
        string $investmentId  = '',
        string $transactionId = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('06_commodity_activated.html', [
            'first_name'      => $firstName ?: 'there',
            'commodity_name'  => $commodityName,
            'entry_price'     => $entryPrice,
            'projected_yield' => $projectedYield,
            'amount'          => number_format($amount, 2),
            'duration'        => $duration,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'investment_id'   => $investmentId,
            'transaction_id'  => $transactionId,
        ]);

        return self::send($email, $name, 'Commodity position opened: ' . $commodityName . ' — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 07 — Real Estate Investment Activated
    // ────────────────────────────────────────────────────────────────────────
    public static function sendRealestateActivated(
        string $email,
        string $name,
        string $poolType,
        string $propertyName,
        string $location,
        string $returnStructure,
        string $payoutFrequency,
        float  $amount,
        string $duration,
        string $startDate,
        string $endDate,
        string $investmentId  = '',
        string $transactionId = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('07_realestate_activated.html', [
            'first_name'       => $firstName ?: 'there',
            'pool_type'        => $poolType,
            'property_name'    => $propertyName,
            'location'         => $location,
            'return_structure' => $returnStructure,
            'payout_frequency' => $payoutFrequency,
            'amount'           => number_format($amount, 2),
            'duration'         => $duration,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'investment_id'    => $investmentId,
            'transaction_id'   => $transactionId,
        ]);

        return self::send($email, $name, 'Real estate investment confirmed: ' . $propertyName . ' — ' . $appName, $html);
    }

    // Backward-compat: generic investment started (routes to plan activated template)
    public static function sendInvestmentStarted(
        string $email,
        string $name,
        string $planName,
        float  $amount,
        float  $yieldMin,
        float  $yieldMax,
        int    $durationDays
    ): bool {
        return self::sendPlanActivated(
            $email, $name,
            $planName,
            'Standard',
            $amount,
            $durationDays . ' days',
            date('F j, Y'),
            date('F j, Y', strtotime('+' . $durationDays . ' days')),
            $amount * ($yieldMin + $yieldMax) / 2 / 100 * $durationDays
        );
    }

    // ────────────────────────────────────────────────────────────────────────
    // 08 — Investment Plan Ending Soon
    // ────────────────────────────────────────────────────────────────────────
    public static function sendPlanEndingSoon(
        string $email,
        string $name,
        string $planName,
        string $investmentType,
        float  $amount,
        float  $expectedProfit,
        string $startDate,
        string $endDate,
        string $investmentId  = '',
        string $transactionId = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('08_plan_ending_soon.html', [
            'first_name'      => $firstName ?: 'there',
            'plan_name'       => $planName,
            'investment_type' => $investmentType,
            'amount'          => number_format($amount, 2),
            'expected_profit' => number_format($expectedProfit, 2),
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'investment_id'   => $investmentId,
            'transaction_id'  => $transactionId,
        ]);

        return self::send($email, $name, 'Your investment matures tomorrow — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 09 — Profit Credited
    // ────────────────────────────────────────────────────────────────────────
    public static function sendProfitCredited(
        string $email,
        string $name,
        string $planName,
        string $investmentType,
        float  $principal,
        float  $profitAmount,
        float  $commissionPct,
        float  $netAmount,
        string $startDate,
        string $endDate,
        string $transactionId = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('09_profit_credited.html', [
            'first_name'      => $firstName ?: 'there',
            'plan_name'       => $planName,
            'investment_type' => $investmentType,
            'principal'       => number_format($principal, 2),
            'profit_amount'   => number_format($profitAmount, 2),
            'commission_pct'  => number_format($commissionPct, 2),
            'net_amount'      => number_format($netAmount, 2),
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'transaction_id'  => $transactionId,
        ]);

        return self::send($email, $name, 'Profit credited to your wallet — ' . $appName, $html);
    }

    // Backward-compat aliases that map to sendProfitCredited
    public static function sendDepositMatured(
        string $email,
        string $name,
        string $amount,
        string $totalReturn,
        string $maturityDate
    ): bool {
        return self::sendProfitCredited(
            $email, $name, 'Fixed Deposit', 'Fixed Deposit',
            (float) str_replace(',', '', $amount),
            (float) str_replace(',', '', $totalReturn) - (float) str_replace(',', '', $amount),
            0.0,
            (float) str_replace(',', '', $totalReturn),
            '', $maturityDate
        );
    }

    public static function sendSavingsInterestCredited(
        string $email,
        string $name,
        string $interestAmount,
        string $planName,
        string $creditDate
    ): bool {
        return self::sendProfitCredited(
            $email, $name, $planName, 'Savings',
            0.0,
            (float) str_replace(',', '', $interestAmount),
            0.0,
            (float) str_replace(',', '', $interestAmount),
            '', $creditDate
        );
    }

    // ────────────────────────────────────────────────────────────────────────
    // 10 — Deposit Pending
    // ────────────────────────────────────────────────────────────────────────
    public static function sendDepositPending(
        string $email,
        string $name,
        float  $amount       = 0,
        string $currency     = '',
        string $network      = '',
        string $address      = '',
        string $txHash       = '',
        string $submittedAt  = '',
        string $transactionId = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('10_deposit_pending.html', [
            'first_name'      => $firstName ?: 'there',
            'amount'          => $amount > 0 ? number_format($amount, 2) : '',
            'crypto_currency' => strtoupper($currency),
            'network'         => $network,
            'crypto_address'  => $address,
            'tx_hash'         => $txHash,
            'submitted_at'    => $submittedAt ?: date('F j, Y H:i T'),
            'transaction_id'  => $transactionId,
        ]);

        return self::send($email, $name, 'Deposit received — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 11 — Deposit Approved
    // ────────────────────────────────────────────────────────────────────────
    public static function sendDepositApproved(
        string $email,
        string $name,
        float  $amount,
        string $currency,
        string $network      = '',
        string $txHash       = '',
        string $confirmedAt  = '',
        string $newBalance   = '',
        string $transactionId = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('11_deposit_approved.html', [
            'first_name'      => $firstName ?: 'there',
            'amount'          => number_format($amount, 2),
            'crypto_currency' => strtoupper($currency),
            'network'         => $network,
            'tx_hash'         => $txHash,
            'confirmed_at'    => $confirmedAt ?: date('F j, Y H:i T'),
            'new_balance'     => $newBalance,
            'transaction_id'  => $transactionId,
        ]);

        return self::send($email, $name, 'Deposit confirmed — ' . $appName, $html);
    }

    // Backward-compat alias
    public static function sendDepositConfirmed(
        string $email,
        string $name,
        string $amount   = '',
        string $currency = '',
        string $paymentId = '',
        string $network  = '',
        string $txHash   = '',
        string $confirmedAt = '',
        string $newBalance  = ''
    ): bool {
        return self::sendDepositApproved(
            $email, $name,
            (float) str_replace(',', '', $amount),
            $currency, $network, $txHash, $confirmedAt, $newBalance, $paymentId
        );
    }

    // ────────────────────────────────────────────────────────────────────────
    // 12 — Deposit Rejected
    // ────────────────────────────────────────────────────────────────────────
    public static function sendDepositRejected(
        string $email,
        string $name,
        float  $amount,
        string $currency,
        string $txHash          = '',
        string $attemptedAt     = '',
        string $rejectionReason = '',
        string $transactionId   = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('12_deposit_rejected.html', [
            'first_name'       => $firstName ?: 'there',
            'amount'           => number_format($amount, 2),
            'crypto_currency'  => strtoupper($currency),
            'tx_hash'          => $txHash,
            'attempted_at'     => $attemptedAt ?: date('F j, Y H:i T'),
            'rejection_reason' => $rejectionReason ?: 'Could not verify the transaction.',
            'transaction_id'   => $transactionId,
        ]);

        return self::send($email, $name, 'Deposit could not be processed — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 13 — Withdrawal Pending
    // ────────────────────────────────────────────────────────────────────────
    public static function sendWithdrawalPending(
        string $email,
        string $name,
        string $amount,
        string $walletAddress,
        int    $processingHours = 24,
        string $currency        = '',
        string $network         = '',
        string $requestedAt     = '',
        string $transactionId   = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('13_withdrawal_pending.html', [
            'first_name'      => $firstName ?: 'there',
            'amount'          => $amount,
            'crypto_currency' => strtoupper($currency),
            'network'         => $network,
            'wallet_address'  => $walletAddress,
            'requested_at'    => $requestedAt ?: date('F j, Y H:i T'),
            'transaction_id'  => $transactionId,
        ]);

        return self::send($email, $name, 'Withdrawal submitted — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 14 — Withdrawal Approved
    // ────────────────────────────────────────────────────────────────────────
    public static function sendWithdrawalConfirmed(
        string $email,
        string $name,
        string $amount,
        string $walletAddress,
        string $txHash       = '',
        string $currency     = '',
        string $network      = '',
        string $explorerUrl  = '',
        string $processedAt  = '',
        string $newBalance   = '',
        string $transactionId = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('14_withdrawal_approved.html', [
            'first_name'      => $firstName ?: 'there',
            'amount'          => $amount,
            'crypto_currency' => strtoupper($currency),
            'network'         => $network,
            'wallet_address'  => $walletAddress,
            'tx_hash'         => $txHash,
            'explorer_url'    => $explorerUrl,
            'processed_at'    => $processedAt ?: date('F j, Y H:i T'),
            'new_balance'     => $newBalance,
            'transaction_id'  => $transactionId,
        ]);

        return self::send($email, $name, 'Withdrawal sent — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 15 — Withdrawal Rejected
    // ────────────────────────────────────────────────────────────────────────
    public static function sendWithdrawalRejected(
        string $email,
        string $name,
        string $amount,
        string $walletAddress,
        string $requestedAt     = '',
        string $rejectionReason = '',
        string $transactionId   = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('15_withdrawal_rejected.html', [
            'first_name'       => $firstName ?: 'there',
            'amount'           => $amount,
            'wallet_address'   => $walletAddress,
            'requested_at'     => $requestedAt ?: date('F j, Y H:i T'),
            'rejection_reason' => $rejectionReason ?: 'No reason provided.',
            'transaction_id'   => $transactionId,
        ]);

        return self::send($email, $name, 'Withdrawal not processed — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 16 — Admin: New User Registration
    // ────────────────────────────────────────────────────────────────────────
    public static function sendAdminNewUser(
        string $adminEmail,
        string $userId,
        string $userFullName,
        string $userEmail,
        string $registeredAt,
        string $userCountry   = '',
        string $userIp        = '',
        string $emailVerified = 'No'
    ): bool {
        $appName = getenv('APP_NAME') ?: 'Qblockx';

        $html = self::render('16_admin_new_user.html', [
            'user_id'        => $userId,
            'user_full_name' => $userFullName,
            'user_email'     => $userEmail,
            'registered_at'  => $registeredAt,
            'user_country'   => $userCountry,
            'user_ip'        => $userIp,
            'email_verified' => $emailVerified,
        ]);

        return self::send($adminEmail, 'Admin', '[Admin] New user registered — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 17 — Admin: New Deposit Detected
    // ────────────────────────────────────────────────────────────────────────
    public static function sendAdminNewDeposit(
        string $adminEmail,
        string $userFullName,
        string $userEmail,
        float  $amount,
        string $currency,
        string $network       = '',
        string $txHash        = '',
        string $transactionId = '',
        string $depositStatus = 'pending',
        string $detectedAt    = ''
    ): bool {
        $appName = getenv('APP_NAME') ?: 'Qblockx';

        $html = self::render('17_admin_new_deposit.html', [
            'user_full_name'  => $userFullName,
            'user_email'      => $userEmail,
            'amount'          => number_format($amount, 2),
            'crypto_currency' => strtoupper($currency),
            'network'         => $network,
            'tx_hash'         => $txHash,
            'transaction_id'  => $transactionId,
            'deposit_status'  => $depositStatus,
            'detected_at'     => $detectedAt ?: date('F j, Y H:i T'),
        ]);

        return self::send($adminEmail, 'Admin', '[Admin] New deposit — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 18 — Admin: New Withdrawal Request
    // ────────────────────────────────────────────────────────────────────────
    public static function sendAdminNewWithdrawal(
        string $adminEmail,
        string $userFullName,
        string $userEmail,
        float  $amount,
        string $currency,
        string $network          = '',
        string $walletAddress    = '',
        string $transactionId    = '',
        string $requestedAt      = '',
        string $userBalance      = '0',
        string $userTotalInvested = '0'
    ): bool {
        $appName = getenv('APP_NAME') ?: 'Qblockx';

        $html = self::render('18_admin_new_withdrawal.html', [
            'user_full_name'    => $userFullName,
            'user_email'        => $userEmail,
            'amount'            => number_format($amount, 2),
            'crypto_currency'   => strtoupper($currency),
            'network'           => $network,
            'wallet_address'    => $walletAddress,
            'transaction_id'    => $transactionId,
            'requested_at'      => $requestedAt ?: date('F j, Y H:i T'),
            'user_balance'      => $userBalance,
            'user_total_invested' => $userTotalInvested,
        ]);

        return self::send($adminEmail, 'Admin', '[Admin] Withdrawal request — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 19 — Admin: New Investment
    // ────────────────────────────────────────────────────────────────────────
    public static function sendAdminNewInvestment(
        string $adminEmail,
        string $userFullName,
        string $userEmail,
        string $investmentType,
        string $planName,
        float  $amount,
        string $duration      = '',
        string $startDate     = '',
        string $endDate       = '',
        float  $expectedReturn = 0,
        string $investmentId  = '',
        string $transactionId = ''
    ): bool {
        $appName = getenv('APP_NAME') ?: 'Qblockx';

        $html = self::render('19_admin_new_investment.html', [
            'user_full_name'  => $userFullName,
            'user_email'      => $userEmail,
            'investment_type' => $investmentType,
            'plan_name'       => $planName,
            'amount'          => number_format($amount, 2),
            'duration'        => $duration,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'expected_return' => number_format($expectedReturn, 2),
            'investment_id'   => $investmentId,
            'transaction_id'  => $transactionId,
        ]);

        return self::send($adminEmail, 'Admin', '[Admin] New investment — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 20 — Admin: Trust Wallet Submitted
    // ────────────────────────────────────────────────────────────────────────
    public static function sendAdminWalletSubmitted(
        string $adminEmail,
        string $userId,
        string $userFullName,
        string $userEmail,
        string $userIp      = '',
        string $submittedAt = ''
    ): bool {
        $appName = getenv('APP_NAME') ?: 'Qblockx';

        $html = self::render('20_admin_wallet_submitted.html', [
            'user_id'        => $userId,
            'user_full_name' => $userFullName,
            'user_email'     => $userEmail,
            'user_ip'        => $userIp,
            'submitted_at'   => $submittedAt ?: date('F j, Y H:i T'),
        ]);

        return self::send($adminEmail, 'Admin', '[Admin] Trust Wallet submitted — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 21 — Password Changed
    // ────────────────────────────────────────────────────────────────────────
    public static function sendPasswordChanged(
        string $email,
        string $name,
        string $changedAt = '',
        string $userEmail = ''
    ): bool {
        $appName   = getenv('APP_NAME') ?: 'Qblockx';
        $firstName = explode(' ', trim($name))[0] ?: $name;

        $html = self::render('21_password_changed.html', [
            'first_name' => $firstName ?: 'there',
            'changed_at' => $changedAt ?: date('F j, Y H:i T'),
            'email'      => $userEmail ?: $email,
        ]);

        return self::send($email, $name, 'Your password was changed — ' . $appName, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 22 — Admin: Direct Message to User
    // ────────────────────────────────────────────────────────────────────────
    public static function sendAdminMessage(
        string $email,
        string $name,
        string $subject,
        string $body
    ): bool {
        $firstName = explode(' ', trim($name))[0] ?: $name;

        // Escape body then preserve line breaks as <br> for HTML rendering
        $safeBody = nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        $html = self::render(
            '22_admin_message.html',
            [
                'subject'    => $subject,
                'first_name' => $firstName ?: 'there',
            ],
            ['message_body' => $safeBody]
        );

        return self::send($email, $name, $subject, $html);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Legacy stubs — no matching templates; silently skip sending
    // ────────────────────────────────────────────────────────────────────────
    public static function sendSavingsPlanCreated(string $email, string $name, ...$rest): bool    { return false; }
    public static function sendFixedDepositOpened(string $email, string $name, ...$rest): bool    { return false; }
    public static function sendInterestCredited(string $email, string $name, ...$rest): bool      { return false; }
    public static function sendLoanApproved(string $email, string $name, ...$rest): bool          { return false; }
    public static function sendLoanRejected(string $email, string $name, ...$rest): bool          { return false; }
    public static function sendLoanPaymentDue(string $email, string $name, ...$rest): bool        { return false; }
    public static function sendReferralBonus(string $email, string $name, ...$rest): bool         { return false; }
}
