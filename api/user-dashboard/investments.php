<?php
/**
 * Quantum BlocX — API: user-dashboard/investments.php  (premium card holders only)
 *
 * GET                       → product catalog, active/closed positions (with live accrued return), summary
 * POST {action:'invest'}    → open a position (debits a crypto wallet by USD-equivalent)
 * POST {action:'withdraw'}  → close a position. Matured → principal+interest; early → principal only
 *
 * Positions are USD-denominated; funds are drawn from / returned to a chosen crypto wallet
 * at the coin's current price.
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once '../../api/utilities/http.php';
require_once '../../api/utilities/email_templates.php';
header('Content-Type: application/json');

requireAuth();
$auth = getAuthUser();

// Product catalog. tier 'any' = either premium card; 'VirtuElite' = Elite only.
const INVEST_PRODUCTS = [
    ['key' => 'staking', 'name' => 'Crypto Staking Vault', 'apr' => 8.0,  'days' => 30,  'min' => 500,  'tier' => 'any',         'risk' => 'Low',    'blurb' => 'Steady staking yield on blue-chip assets.'],
    ['key' => 'defi',    'name' => 'DeFi Yield Pool',      'apr' => 15.0, 'days' => 60,  'min' => 1000, 'tier' => 'any',         'risk' => 'Medium', 'blurb' => 'Diversified liquidity-pool returns.'],
    ['key' => 'btcfund', 'name' => 'BTC Growth Fund',       'apr' => 22.0, 'days' => 90,  'min' => 2500, 'tier' => 'any',         'risk' => 'Medium', 'blurb' => 'Managed Bitcoin accumulation strategy.'],
    ['key' => 'quantum', 'name' => 'Quantum Index',         'apr' => 35.0, 'days' => 180, 'min' => 5000, 'tier' => 'VirtuElite',  'risk' => 'High',   'blurb' => 'Elite-only high-yield quantitative index.'],
];

function productByKey(string $key): ?array {
    foreach (INVEST_PRODUCTS as $p) if ($p['key'] === $key) return $p;
    return null;
}

/** USD return accrued so far (linear to maturity, capped at full term).
 *  elapsed_sec comes from the DB to stay timezone-safe. */
function accruedReturnUsd(array $inv): float {
    $elapsed = max(0, (int) ($inv['elapsed_sec'] ?? 0));
    $termSec = (int) $inv['duration_days'] * 86400;
    $frac    = $termSec > 0 ? min(1.0, $elapsed / $termSec) : 1.0;
    return (float) $inv['principal_usd'] * ((float) $inv['apr'] / 100.0) * ((int) $inv['duration_days'] / 365.0) * $frac;
}

try {
    $db  = Database::getInstance()->getConnection();
    $uid = $auth['id'];

    // ── Premium gate ─────────────────────────────────────────────────
    $t = $db->prepare("SELECT card_tier FROM users WHERE id = :u");
    $t->execute(['u' => $uid]);
    $cardTier = $t->fetchColumn() ?: 'none';
    if ($cardTier === 'none') {
        echo json_encode(['success' => false, 'message' => 'A premium QFS card is required for investments']);
        exit;
    }

    // ════════════════════════ POST ════════════════════════
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $input['action'] ?? '';

        // ── Open a position ──────────────────────────────────────────
        if ($action === 'invest') {
            $product = productByKey(trim($input['product_key'] ?? ''));
            $amount  = (float) ($input['amount_usd'] ?? 0);
            $cid     = (int) ($input['currency_id'] ?? 0);

            if (!$product) { echo json_encode(['success' => false, 'message' => 'Invalid product']); exit; }
            if ($product['tier'] === 'VirtuElite' && $cardTier !== 'VirtuElite') {
                echo json_encode(['success' => false, 'message' => 'This product requires a VirtuElite card']);
                exit;
            }
            if ($amount < $product['min']) {
                echo json_encode(['success' => false, 'message' => 'Minimum for this product is $' . number_format($product['min'])]);
                exit;
            }

            $cur = $db->prepare("SELECT id, symbol, current_price_usd FROM currencies WHERE id = :c AND is_active = 1");
            $cur->execute(['c' => $cid]);
            $c = $cur->fetch(PDO::FETCH_ASSOC);
            $price = (float) ($c['current_price_usd'] ?? 0);
            if (!$c || $price <= 0) { echo json_encode(['success' => false, 'message' => 'Choose a funding asset']); exit; }

            $crypto = $amount / $price;
            $w = $db->prepare("SELECT id, balance FROM wallets WHERE user_id = :u AND currency_id = :c");
            $w->execute(['u' => $uid, 'c' => $cid]);
            $wallet = $w->fetch(PDO::FETCH_ASSOC);
            if (!$wallet || (float) $wallet['balance'] < $crypto) {
                echo json_encode(['success' => false, 'message' => 'Insufficient ' . $c['symbol'] . ' balance to fund this investment']);
                exit;
            }

            $db->beginTransaction();
            try {
                $db->prepare("UPDATE wallets SET balance = balance - :amt WHERE id = :wid")
                   ->execute(['amt' => $crypto, 'wid' => $wallet['id']]);
                $db->prepare(
                    "INSERT INTO investments
                        (user_id, product_key, product_name, currency_id, currency_symbol,
                         principal_usd, principal_crypto, apr, duration_days, status, matures_at)
                     VALUES (:u, :pk, :pn, :cid, :sym, :pusd, :pc, :apr, :days, 'active',
                             DATE_ADD(NOW(), INTERVAL :days2 DAY))"
                )->execute([
                    'u' => $uid, 'pk' => $product['key'], 'pn' => $product['name'],
                    'cid' => $cid, 'sym' => $c['symbol'], 'pusd' => round($amount, 2),
                    'pc' => $crypto, 'apr' => $product['apr'], 'days' => $product['days'], 'days2' => $product['days'],
                ]);
                $db->prepare(
                    "INSERT INTO transactions (user_id, wallet_id, type, amount, amount_usd, currency_id, currency_symbol, status, notes, completed_at)
                     VALUES (:u, :wid, 'admin_debit', :amt, :usd, :cid, :sym, 'completed', :notes, NOW())"
                )->execute(['u' => $uid, 'wid' => $wallet['id'], 'amt' => $crypto, 'usd' => round($amount, 2), 'cid' => $cid, 'sym' => $c['symbol'], 'notes' => 'Invested in ' . $product['name']]);
                $db->commit();
            } catch (\Throwable $e) { $db->rollBack(); throw $e; }

            $proj = $amount * ($product['apr'] / 100.0) * ($product['days'] / 365.0);
            finish_response(['success' => true, 'message' => 'Invested $' . number_format($amount, 2) . ' in ' . $product['name']]);
            try {
                $un = $db->prepare("SELECT full_name FROM users WHERE id = :u");
                $un->execute(['u' => $uid]);
                Mailer::sendInvestmentOpened($auth['email'] ?? '', $un->fetchColumn() ?: '',
                    $product['name'], number_format($amount, 2), (int) $product['days'], number_format($proj, 2));
            } catch (\Throwable $me) { error_log('invest email error: ' . $me->getMessage()); }
            exit;
        }

        // ── Close a position ─────────────────────────────────────────
        if ($action === 'withdraw') {
            $iid = (int) ($input['investment_id'] ?? 0);
            $sel = $db->prepare(
                "SELECT i.*, c.current_price_usd, (NOW() >= i.matures_at) AS is_matured
                   FROM investments i LEFT JOIN currencies c ON c.id = i.currency_id
                  WHERE i.id = :id AND i.user_id = :u AND i.status = 'active' LIMIT 1"
            );
            $sel->execute(['id' => $iid, 'u' => $uid]);
            $inv = $sel->fetch(PDO::FETCH_ASSOC);
            if (!$inv) { echo json_encode(['success' => false, 'message' => 'Investment not found']); exit; }

            $matured = (bool) $inv['is_matured'];
            $price   = (float) $inv['current_price_usd'];
            if ($price <= 0) { echo json_encode(['success' => false, 'message' => 'Price unavailable — try again shortly']); exit; }

            // Matured → principal + full interest; early → principal only (forfeit interest)
            $returnUsd  = $matured
                ? (float) $inv['principal_usd'] * (1 + ((float) $inv['apr'] / 100.0) * ((int) $inv['duration_days'] / 365.0))
                : (float) $inv['principal_usd'];
            $returnCrypto = $returnUsd / $price;
            $interestUsd  = $returnUsd - (float) $inv['principal_usd'];

            $db->beginTransaction();
            try {
                // Credit back to the funding wallet (recreate if missing)
                $w = $db->prepare("SELECT id FROM wallets WHERE user_id = :u AND currency_id = :c");
                $w->execute(['u' => $uid, 'c' => $inv['currency_id']]);
                $wid = $w->fetchColumn();
                if (!$wid) {
                    $net = $db->prepare("SELECT network FROM currencies WHERE id = :c");
                    $net->execute(['c' => $inv['currency_id']]);
                    $addr = '0x' . bin2hex(random_bytes(20));
                    $db->prepare("INSERT INTO wallets (user_id, currency_id, address, network) VALUES (:u, :c, :a, :n)")
                       ->execute(['u' => $uid, 'c' => $inv['currency_id'], 'a' => $addr, 'n' => $net->fetchColumn() ?: '']);
                    $wid = (int) $db->lastInsertId();
                }
                $db->prepare("UPDATE wallets SET balance = balance + :amt WHERE id = :wid")
                   ->execute(['amt' => $returnCrypto, 'wid' => $wid]);
                $db->prepare(
                    "UPDATE investments SET status = :st, total_return_usd = :ret, withdrawn_at = NOW() WHERE id = :id"
                )->execute(['st' => $matured ? 'withdrawn' : 'cancelled', 'ret' => round($interestUsd, 2), 'id' => $iid]);
                $db->prepare(
                    "INSERT INTO transactions (user_id, wallet_id, type, amount, amount_usd, currency_id, currency_symbol, status, notes, completed_at)
                     VALUES (:u, :wid, 'investment_return', :amt, :usd, :cid, :sym, 'completed', :notes, NOW())"
                )->execute([
                    'u' => $uid, 'wid' => $wid, 'amt' => $returnCrypto, 'usd' => round($returnUsd, 2),
                    'cid' => $inv['currency_id'], 'sym' => $inv['currency_symbol'],
                    'notes' => $matured ? ('Matured: ' . $inv['product_name']) : ('Early exit: ' . $inv['product_name']),
                ]);
                $db->commit();
            } catch (\Throwable $e) { $db->rollBack(); throw $e; }

            $outMsg = $matured
                ? 'Withdrawn $' . number_format($returnUsd, 2) . ' (incl. $' . number_format($interestUsd, 2) . ' return)'
                : 'Early exit — $' . number_format($returnUsd, 2) . ' principal returned (interest forfeited)';
            finish_response(['success' => true, 'message' => $outMsg]);
            try {
                $un = $db->prepare("SELECT full_name FROM users WHERE id = :u");
                $un->execute(['u' => $uid]);
                Mailer::sendInvestmentClosed($auth['email'] ?? '', $un->fetchColumn() ?: '',
                    $inv['product_name'], number_format($returnUsd, 2), $matured);
            } catch (\Throwable $me) { error_log('invest email error: ' . $me->getMessage()); }
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
    }

    // ════════════════════════ GET ════════════════════════
    $products = [];
    foreach (INVEST_PRODUCTS as $p) {
        $p['locked'] = ($p['tier'] === 'VirtuElite' && $cardTier !== 'VirtuElite');
        $products[] = $p;
    }

    $stmt = $db->prepare(
        "SELECT *, TIMESTAMPDIFF(SECOND, started_at, NOW()) AS elapsed_sec,
                (NOW() >= matures_at) AS is_matured
           FROM investments WHERE user_id = :u
          ORDER BY (status='active') DESC, started_at DESC"
    );
    $stmt->execute(['u' => $uid]);
    $positions = [];
    $totalActive = 0.0; $totalAccrued = 0.0;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $inv) {
        $isActive = $inv['status'] === 'active';
        $accrued  = $isActive ? accruedReturnUsd($inv) : (float) $inv['total_return_usd'];
        $elapsed  = max(0, (int) $inv['elapsed_sec']);
        $termSec  = (int) $inv['duration_days'] * 86400;
        $progress = $termSec > 0 ? min(100, round($elapsed / $termSec * 100)) : 100;
        if ($isActive) { $totalActive += (float) $inv['principal_usd']; $totalAccrued += $accrued; }
        $positions[] = [
            'id'            => (int) $inv['id'],
            'product_name'  => $inv['product_name'],
            'symbol'        => $inv['currency_symbol'],
            'principal_usd' => (float) $inv['principal_usd'],
            'apr'           => (float) $inv['apr'],
            'duration_days' => (int) $inv['duration_days'],
            'status'        => $inv['status'],
            'accrued_usd'   => round($accrued, 2),
            'progress'      => $progress,
            'matured'       => $isActive && (bool) $inv['is_matured'],
            'started_at'    => $inv['started_at'],
            'matures_at'    => $inv['matures_at'],
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => [
            'tier'      => $cardTier,
            'products'  => $products,
            'positions' => $positions,
            'summary'   => [
                'active_usd'  => round($totalActive, 2),
                'accrued_usd' => round($totalAccrued, 2),
                'count'       => count(array_filter($positions, fn($p) => $p['status'] === 'active')),
            ],
        ],
    ]);

} catch (PDOException $e) {
    error_log('investments.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
