<?php
/**
 * Quantum BlocX — shared NOWPayments deposit (receive) helpers.
 *  - NP_CURRENCY_MAP: our currency (SYMBOL|NETWORK) → NOWPayments pay_currency code
 *  - npCodeForCurrency(): look up the code for a currency row
 *  - npFinalStatuses / npActiveStatuses: status groups
 *  - creditDeposit(): idempotently credit a confirmed deposit to the user's wallet
 */

// Only NOWPayments-supported networks we actually offer on Receive.
const NP_CURRENCY_MAP = [
    'BTC|Bitcoin'       => 'btc',
    'ETH|ERC-20'        => 'eth',
    'LTC|Litecoin'      => 'ltc',
    'BNB|BEP-20'        => 'bnbbsc',
    'USDT|ERC-20'       => 'usdterc20',
    'USDT|TRC-20'       => 'usdttrc20',
    'USDT|BEP-20'       => 'usdtbsc',
    'USDT|SOL'          => 'usdtsol',
    'USDC|ERC-20'       => 'usdc',
    'USDC|BEP-20'       => 'usdcbsc',
    'USDC|SOL'          => 'usdcsol',
    'BCH|Bitcoin Cash'  => 'bch',
    'XRP|XRP Ledger'    => 'xrp',
    'XLM|Stellar'       => 'xlm',
    'ADA|Cardano'       => 'ada',
    'TRX|TRC-20'        => 'trx',
    'SOL|SOL'           => 'sol',
    'DOGE|Dogecoin'     => 'doge',
    'LINK|ERC-20'       => 'link',
    'AAVE|ERC-20'       => 'aave',
    'ALGO|Algorand'     => 'algo',
    'XAUT|ERC-20'       => 'xaut',
    'SUI|Sui'           => 'sui',
];

function npCodeForCurrency(string $symbol, string $network): ?string
{
    return NP_CURRENCY_MAP[$symbol . '|' . $network] ?? null;
}

/** Statuses that mean the funds have arrived and should be credited. */
function npIsPaidStatus(string $s): bool
{
    return in_array(strtolower($s), ['confirmed', 'sending', 'finished'], true);
}

/** Statuses that end the deposit lifecycle without crediting. */
function npIsDeadStatus(string $s): bool
{
    return in_array(strtolower($s), ['failed', 'refunded', 'expired'], true);
}

/**
 * Idempotently credit a confirmed deposit. Locks the row, re-checks `credited`,
 * tops up the wallet, logs a `receive` transaction, marks the deposit, and
 * (non-blocking) emails the user. Safe to call from the webhook AND the poller.
 *
 * @return string '' = no-op, 'credited' = just credited, 'already' = already done
 */
function creditDeposit(PDO $db, int $depositId, string $status, float $actuallyPaid): string
{
    $status = strtolower($status);

    $db->beginTransaction();
    try {
        $sel = $db->prepare("SELECT * FROM deposits WHERE id = :id FOR UPDATE");
        $sel->execute(['id' => $depositId]);
        $dep = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$dep) { $db->commit(); return ''; }

        // Always keep the latest status/actually_paid recorded
        $db->prepare("UPDATE deposits SET status = :st, actually_paid = :ap WHERE id = :id")
           ->execute(['st' => $status, 'ap' => $actuallyPaid > 0 ? $actuallyPaid : $dep['actually_paid'], 'id' => $depositId]);

        if ((int) $dep['credited'] === 1) { $db->commit(); return 'already'; }
        if (!npIsPaidStatus($status)) { $db->commit(); return ''; }

        $amount = $actuallyPaid > 0 ? $actuallyPaid : (float) $dep['pay_amount'];
        if ($amount <= 0) { $db->commit(); return ''; }

        // Currency info for USD value + symbol + network
        $cur = $db->prepare("SELECT symbol, network, current_price_usd FROM currencies WHERE id = :c");
        $cur->execute(['c' => $dep['currency_id']]);
        $c = $cur->fetch(PDO::FETCH_ASSOC) ?: ['symbol' => '', 'network' => '', 'current_price_usd' => 0];
        $usd = $amount * (float) $c['current_price_usd'];

        // Ensure the user has a wallet for this currency
        $w = $db->prepare("SELECT id FROM wallets WHERE user_id = :u AND currency_id = :c");
        $w->execute(['u' => $dep['user_id'], 'c' => $dep['currency_id']]);
        $wid = $w->fetchColumn();
        if (!$wid) {
            $addr = '0x' . bin2hex(random_bytes(20));
            $db->prepare("INSERT INTO wallets (user_id, currency_id, address, network) VALUES (:u, :c, :a, :n)")
               ->execute(['u' => $dep['user_id'], 'c' => $dep['currency_id'], 'a' => $addr, 'n' => $c['network'] ?: '']);
            $wid = (int) $db->lastInsertId();
        }

        $db->prepare("UPDATE wallets SET balance = balance + :amt WHERE id = :wid")
           ->execute(['amt' => $amount, 'wid' => $wid]);

        $db->prepare(
            "INSERT INTO transactions (user_id, wallet_id, type, amount, amount_usd, currency_id, currency_symbol, status, notes, completed_at)
             VALUES (:u, :wid, 'receive', :amt, :usd, :cid, :sym, 'completed', :notes, NOW())"
        )->execute([
            'u' => $dep['user_id'], 'wid' => $wid, 'amt' => $amount, 'usd' => round($usd, 2),
            'cid' => $dep['currency_id'], 'sym' => $c['symbol'], 'notes' => 'Deposit via NOWPayments',
        ]);

        $db->prepare("UPDATE deposits SET status = 'finished', credited = 1, actually_paid = :ap WHERE id = :id")
           ->execute(['ap' => $amount, 'id' => $depositId]);

        $db->commit();
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }

    // Notify the user (non-fatal)
    try {
        require_once __DIR__ . '/../utilities/email_templates.php';
        $u = $db->prepare("SELECT email, full_name FROM users WHERE id = :u");
        $u->execute(['u' => $dep['user_id']]);
        $usr = $u->fetch(PDO::FETCH_ASSOC);
        if ($usr && !empty($usr['email'])) {
            $amtStr = rtrim(rtrim(number_format($amount, 8), '0'), '.');
            $usdStr = $usd > 0 ? number_format($usd, 2) : '';
            Mailer::sendAssetReceived($usr['email'], $usr['full_name'] ?? '', $amtStr, $c['symbol'], 'NOWPayments deposit', $usdStr);
        }
    } catch (\Throwable $mailErr) {
        error_log('creditDeposit email error: ' . $mailErr->getMessage());
    }

    return 'credited';
}
