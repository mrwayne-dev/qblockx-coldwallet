<?php
/**
 * Quantum BlocX — API: user-dashboard/mining.php  (premium card holders only)
 *
 * GET                      → active rigs (with live pending rewards), summary, mineable coins
 * POST {action:'start'}    → start a mining rig for a currency
 * POST {action:'claim'}    → credit a rig's pending rewards to the wallet
 * POST {action:'stop'}     → claim then stop a rig
 *
 * Rewards model: each active rig accrues a fixed USD/day (by card tier), converted to
 * the coin at its current price. Pending = accrued-since-start − already-claimed.
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
require_once '../../api/utilities/http.php';
require_once '../../api/utilities/email_templates.php';
header('Content-Type: application/json');

requireAuth();
$auth = getAuthUser();

// Coins users can mine (matched against active currencies by symbol)
const MINEABLE = ['BTC','ETH','LTC','DOGE','BCH','ETC','XMR','ZEC','RVN','KAS','DASH','XRP'];
const MAX_RIGS = 6;

function miningDailyUsd(string $tier): float { return $tier === 'VirtuElite' ? 75.0 : 40.0; }
function tierHashrate(string $tier): float   { return $tier === 'VirtuElite' ? 120.0 : 65.0; }

/** pending coin amount for a rig given the coin price. Elapsed seconds come from the
 *  DB (TIMESTAMPDIFF) to stay timezone-safe — never mix PHP time() with DB timestamps. */
function rigPending(array $s, float $price, float $dailyUsd): float {
    if ($price <= 0) return 0.0;
    $ratePerSec = ($dailyUsd / 86400.0) / $price;
    $elapsed    = max(0, (int) ($s['elapsed_sec'] ?? 0));
    $pending    = ($elapsed * $ratePerSec) - (float) $s['total_earned'];
    return $pending > 0 ? $pending : 0.0;
}

try {
    $db  = Database::getInstance()->getConnection();
    $uid = $auth['id'];

    // ── Premium gate ─────────────────────────────────────────────────
    $t = $db->prepare("SELECT card_tier FROM users WHERE id = :u");
    $t->execute(['u' => $uid]);
    $cardTier = $t->fetchColumn() ?: 'none';
    if ($cardTier === 'none') {
        echo json_encode(['success' => false, 'message' => 'A premium QFS card is required for mining']);
        exit;
    }
    $dailyUsd = miningDailyUsd($cardTier);

    // ════════════════════════ POST ════════════════════════
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $input['action'] ?? '';

        // ── Start a rig ──────────────────────────────────────────────
        if ($action === 'start') {
            $cid = (int) ($input['currency_id'] ?? 0);
            $cur = $db->prepare("SELECT id, symbol FROM currencies WHERE id = :c AND is_active = 1");
            $cur->execute(['c' => $cid]);
            $c = $cur->fetch(PDO::FETCH_ASSOC);
            if (!$c || !in_array($c['symbol'], MINEABLE, true)) {
                echo json_encode(['success' => false, 'message' => 'That coin cannot be mined']);
                exit;
            }
            $ex = $db->prepare("SELECT id FROM mining_sessions WHERE user_id = :u AND currency_id = :c AND status = 'active'");
            $ex->execute(['u' => $uid, 'c' => $cid]);
            if ($ex->fetchColumn()) {
                echo json_encode(['success' => false, 'message' => 'You already have an active ' . $c['symbol'] . ' rig']);
                exit;
            }
            $cnt = $db->prepare("SELECT COUNT(*) FROM mining_sessions WHERE user_id = :u AND status = 'active'");
            $cnt->execute(['u' => $uid]);
            if ((int) $cnt->fetchColumn() >= MAX_RIGS) {
                echo json_encode(['success' => false, 'message' => 'Maximum of ' . MAX_RIGS . ' active rigs reached']);
                exit;
            }
            $hr = tierHashrate($cardTier) * (0.85 + mt_rand(0, 30) / 100.0);
            $db->prepare("INSERT INTO mining_sessions (user_id, currency_id, status, hashrate) VALUES (:u, :c, 'active', :h)")
               ->execute(['u' => $uid, 'c' => $cid, 'h' => round($hr, 2)]);
            echo json_encode(['success' => true, 'message' => 'Mining started for ' . $c['symbol']]);
            exit;
        }

        // ── Claim / Stop ─────────────────────────────────────────────
        if ($action === 'claim' || $action === 'stop') {
            $sid = (int) ($input['session_id'] ?? 0);
            $sel = $db->prepare(
                "SELECT ms.*, c.symbol, c.network, c.current_price_usd,
                        TIMESTAMPDIFF(SECOND, ms.started_at, NOW()) AS elapsed_sec
                   FROM mining_sessions ms JOIN currencies c ON c.id = ms.currency_id
                  WHERE ms.id = :sid AND ms.user_id = :u AND ms.status = 'active' LIMIT 1"
            );
            $sel->execute(['sid' => $sid, 'u' => $uid]);
            $s = $sel->fetch(PDO::FETCH_ASSOC);
            if (!$s) { echo json_encode(['success' => false, 'message' => 'Rig not found']); exit; }

            $price   = (float) $s['current_price_usd'];
            $pending = rigPending($s, $price, $dailyUsd);

            $db->beginTransaction();
            try {
                if ($pending > 0) {
                    // Ensure a wallet exists for this coin
                    $w = $db->prepare("SELECT id FROM wallets WHERE user_id = :u AND currency_id = :c");
                    $w->execute(['u' => $uid, 'c' => $s['currency_id']]);
                    $wid = $w->fetchColumn();
                    if (!$wid) {
                        $addr = '0x' . bin2hex(random_bytes(20));
                        $db->prepare("INSERT INTO wallets (user_id, currency_id, address, network) VALUES (:u, :c, :a, :n)")
                           ->execute(['u' => $uid, 'c' => $s['currency_id'], 'a' => $addr, 'n' => $s['network'] ?: '']);
                        $wid = (int) $db->lastInsertId();
                    }
                    $usd = $pending * $price;
                    $db->prepare("UPDATE wallets SET balance = balance + :amt WHERE id = :wid")
                       ->execute(['amt' => $pending, 'wid' => $wid]);
                    $db->prepare("UPDATE mining_sessions SET total_earned = total_earned + :amt WHERE id = :sid")
                       ->execute(['amt' => $pending, 'sid' => $sid]);
                    $db->prepare(
                        "INSERT INTO mining_rewards (session_id, user_id, wallet_id, currency_id, amount, amount_usd, status, credited_at)
                         VALUES (:sid, :u, :wid, :cid, :amt, :usd, 'credited', NOW())"
                    )->execute(['sid' => $sid, 'u' => $uid, 'wid' => $wid, 'cid' => $s['currency_id'], 'amt' => $pending, 'usd' => round($usd, 2)]);
                    $db->prepare(
                        "INSERT INTO transactions (user_id, wallet_id, type, amount, amount_usd, currency_id, currency_symbol, status, notes, completed_at)
                         VALUES (:u, :wid, 'mining_reward', :amt, :usd, :cid, :sym, 'completed', 'Mining reward', NOW())"
                    )->execute(['u' => $uid, 'wid' => $wid, 'amt' => $pending, 'usd' => round($usd, 2), 'cid' => $s['currency_id'], 'sym' => $s['symbol']]);
                }
                if ($action === 'stop') {
                    $db->prepare("UPDATE mining_sessions SET status = 'completed', ended_at = NOW() WHERE id = :sid")
                       ->execute(['sid' => $sid]);
                }
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e;
            }

            $amtStr = rtrim(rtrim(number_format($pending, 8), '0'), '.');
            $msg = $action === 'stop'
                ? ($pending > 0 ? 'Rig stopped — ' . $amtStr . ' ' . $s['symbol'] . ' claimed' : 'Rig stopped')
                : ($pending > 0 ? 'Claimed ' . $amtStr . ' ' . $s['symbol'] : 'Nothing to claim yet');

            finish_response(['success' => true, 'message' => $msg, 'claimed' => round($pending, 8)]);

            if ($pending > 0) {
                try {
                    $un = $db->prepare("SELECT full_name FROM users WHERE id = :u");
                    $un->execute(['u' => $uid]);
                    Mailer::sendMiningReward($auth['email'] ?? '', $un->fetchColumn() ?: '',
                        $amtStr, $s['symbol'], number_format($pending * $price, 2));
                } catch (\Throwable $me) { error_log('mining email error: ' . $me->getMessage()); }
            }
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
    }

    // ════════════════════════ GET ════════════════════════
    $stmt = $db->prepare(
        "SELECT ms.id, ms.currency_id, ms.hashrate, ms.total_earned, ms.started_at,
                TIMESTAMPDIFF(SECOND, ms.started_at, NOW()) AS elapsed_sec,
                c.symbol, c.name, c.current_price_usd
           FROM mining_sessions ms JOIN currencies c ON c.id = ms.currency_id
          WHERE ms.user_id = :u AND ms.status = 'active'
          ORDER BY ms.started_at DESC"
    );
    $stmt->execute(['u' => $uid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rigs = [];
    $totalPendingUsd = 0.0;
    $totalEarnedUsd  = 0.0;
    foreach ($rows as $s) {
        $price   = (float) $s['current_price_usd'];
        $pending = rigPending($s, $price, $dailyUsd);
        $totalPendingUsd += $pending * $price;
        $totalEarnedUsd  += ((float) $s['total_earned']) * $price;
        $rigs[] = [
            'id'           => (int) $s['id'],
            'currency_id'  => (int) $s['currency_id'],
            'symbol'       => $s['symbol'],
            'name'         => $s['name'],
            'hashrate'     => (float) $s['hashrate'],
            'price_usd'    => $price,
            'total_earned' => (float) $s['total_earned'],
            'pending'      => round($pending, 8),
            'pending_usd'  => round($pending * $price, 2),
            'started_at'   => $s['started_at'],
        ];
    }

    // Mineable coins not already running
    $activeIds = array_column($rigs, 'currency_id');
    $place = implode(',', array_fill(0, count(MINEABLE), '?'));
    $cs = $db->prepare("SELECT id, symbol, name, current_price_usd FROM currencies WHERE is_active = 1 AND symbol IN ($place) ORDER BY sort_order");
    $cs->execute(MINEABLE);
    $available = [];
    foreach ($cs->fetchAll(PDO::FETCH_ASSOC) as $c) {
        if (in_array((int) $c['id'], $activeIds, true)) continue;
        $available[] = ['id' => (int) $c['id'], 'symbol' => $c['symbol'], 'name' => $c['name'], 'price_usd' => (float) $c['current_price_usd']];
    }

    // Lifetime credited rewards
    $life = $db->prepare("SELECT COALESCE(SUM(amount_usd),0) FROM mining_rewards WHERE user_id = :u AND status = 'credited'");
    $life->execute(['u' => $uid]);

    echo json_encode([
        'success' => true,
        'data'    => [
            'tier'              => $cardTier,
            'daily_usd'         => $dailyUsd,
            'rigs'              => $rigs,
            'available'         => $available,
            'summary'           => [
                'active_rigs'       => count($rigs),
                'pending_usd'       => round($totalPendingUsd, 2),
                'lifetime_usd'      => round((float) $life->fetchColumn(), 2),
            ],
        ],
    ]);

} catch (PDOException $e) {
    error_log('mining.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
