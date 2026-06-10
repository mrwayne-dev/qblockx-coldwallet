<?php
/**
 * Project: Qblockx
 * Shared: QFS Card tier config + activation logic.
 * Used by card-purchase-initiate.php, now-payment / webhook handlers.
 */

if (!defined('CARD_TIERS')) {
    define('CARD_TIERS', [
        'VirtuElevate' => ['price' => 25000.0, 'cashback' => 3.00, 'daily' => 50000.0,  'monthly' => 500000.0],
        'VirtuElite'   => ['price' => 35000.0, 'cashback' => 4.00, 'daily' => 100000.0, 'monthly' => 1000000.0],
    ]);
}

/**
 * Activate a paid card by its NOWPayments invoice id. Idempotent — safe to call
 * from both the IPN webhook and the return status check.
 *
 * @return string '' on no-op/not-found, 'activated' if it was just activated,
 *                 'already' if it was already active.
 */
function activateCardByInvoice(PDO $db, string $invoiceId): string
{
    if ($invoiceId === '') return '';

    $stmt = $db->prepare("SELECT * FROM virtual_cards WHERE payment_invoice_id = :inv LIMIT 1");
    $stmt->execute(['inv' => $invoiceId]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$card) return '';

    if ($card['status'] === 'active') return 'already';

    $tier   = $card['card_tier'];
    $cfg    = (defined('CARD_TIERS') && isset(CARD_TIERS[$tier])) ? CARD_TIERS[$tier] : ['cashback' => 4.00, 'daily' => null, 'monthly' => null];
    $last4  = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    $masked = '**** **** **** ' . $last4;

    $db->beginTransaction();
    try {
        // Lock + re-check to avoid double activation
        $lock = $db->prepare("SELECT status FROM virtual_cards WHERE id = :id FOR UPDATE");
        $lock->execute(['id' => $card['id']]);
        if ($lock->fetchColumn() === 'active') {
            $db->commit();
            return 'already';
        }

        $db->prepare(
            "UPDATE virtual_cards
                SET status = 'active', card_number_masked = :masked,
                    cashback_pct = :cb, daily_limit_usd = :daily, monthly_limit_usd = :monthly,
                    activated_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 5 YEAR)
              WHERE id = :id"
        )->execute([
            'masked'  => $masked,
            'cb'      => $cfg['cashback'],
            'daily'   => $cfg['daily'],
            'monthly' => $cfg['monthly'],
            'id'      => $card['id'],
        ]);

        // Unlock perks for the user
        $db->prepare("UPDATE users SET card_tier = :tier WHERE id = :uid")
           ->execute(['tier' => $tier, 'uid' => $card['user_id']]);

        // Mark the purchase transaction completed
        $db->prepare(
            "UPDATE transactions SET status = 'completed', completed_at = NOW()
              WHERE tx_hash = :inv AND type = 'card_purchase'"
        )->execute(['inv' => $invoiceId]);

        $db->commit();
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }

    // Confirmation email (non-fatal). Wording kept plain to avoid SMTP content filters.
    try {
        require_once __DIR__ . '/../utilities/email_templates.php';
        $u = $db->prepare("SELECT email, full_name FROM users WHERE id = :uid LIMIT 1");
        $u->execute(['uid' => $card['user_id']]);
        $usr = $u->fetch(PDO::FETCH_ASSOC);
        if ($usr && !empty($usr['email'])) {
            $first   = explode(' ', trim($usr['full_name'] ?? ''))[0] ?: 'there';
            $appUrl  = rtrim(getenv('APP_URL') ?: 'https://qblockx.com', '/');
            $html = '<div style="font-family:Arial,Helvetica,sans-serif;background:#F7F8FC;padding:32px 16px;">'
                . '<table align="center" width="480" cellpadding="0" cellspacing="0" style="background:#fff;border:1px solid #D9DEEA;border-radius:12px;">'
                . '<tr><td style="padding:32px 36px;">'
                . '<span style="font-size:20px;font-weight:700;color:#030B1D;">Qblockx</span>'
                . '<h1 style="font-size:22px;color:#030B1D;margin:22px 0 8px;">Your QFS Card is active</h1>'
                . '<p style="font-size:15px;color:#4A4F5F;line-height:1.6;margin:0 0 18px;">Hi ' . htmlspecialchars($first) . ', your <strong>' . htmlspecialchars($tier) . '</strong> card is now active. Card ending ' . $last4 . '.</p>'
                . '<p style="font-size:15px;color:#4A4F5F;line-height:1.6;margin:0 0 22px;">All your card perks are now unlocked.</p>'
                . '<a href="' . $appUrl . '/dashboard" style="display:inline-block;background:#2262FF;color:#fff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:600;">Open dashboard</a>'
                . '</td></tr></table></div>';
            Mailer::send($usr['email'], $usr['full_name'] ?? '', 'Your QFS Card is active — Qblockx', $html);
        }
    } catch (\Throwable $emailErr) {
        error_log('card activation email error: ' . $emailErr->getMessage());
    }

    return 'activated';
}
