<?php
/**
 * Quantum BlocX — API: utilities/crypto-prices.php
 * Returns all active currencies from the database with prices.
 * Optionally refreshes prices from CoinGecko if stale (> 5 min).
 *
 * GET → { success: true, data: { currencies: [...] } }
 */

require_once '../../config/database.php';
header('Content-Type: application/json');

// Allow both authenticated and unauthenticated access (prices are public)
try {
    $db = Database::getInstance()->getConnection();

    // Check if prices need refreshing (older than 5 minutes)
    $staleStmt = $db->query(
        "SELECT COUNT(*) FROM currencies
         WHERE is_active = 1 AND (price_updated_at IS NULL OR price_updated_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE))"
    );
    $staleCount = (int) $staleStmt->fetchColumn();

    if ($staleCount > 0) {
        refreshPricesFromCoinGecko($db);
    }

    // Return all active currencies
    $stmt = $db->query(
        "SELECT id, symbol, name, network, icon_url, decimals,
                is_active, is_new, is_popular,
                expected_arrival_confirmations, expected_unlock_confirmations,
                current_price_usd, price_change_24h_pct,
                min_send_amount, send_fee, sort_order, price_updated_at
         FROM currencies
         WHERE is_active = 1
         ORDER BY sort_order ASC"
    );
    $currencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data'    => ['currencies' => $currencies]
    ]);

} catch (PDOException $e) {
    error_log('crypto-prices.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

/**
 * Fetch prices from CoinGecko and update the currencies table.
 * Uses the free /simple/price endpoint.
 */
function refreshPricesFromCoinGecko(PDO $db): void {
    // Map our symbols to CoinGecko IDs
    $geckoMap = [
        'BTC'   => 'bitcoin',
        'ETH'   => 'ethereum',
        'LTC'   => 'litecoin',
        'XRP'   => 'ripple',
        'ADA'   => 'cardano',
        'SOL'   => 'solana',
        'DOGE'  => 'dogecoin',
        'TRX'   => 'tron',
        'LINK'  => 'chainlink',
        'BNB'   => 'binancecoin',
        'AAVE'  => 'aave',
        'USDT'  => 'tether',
        'USDC'  => 'usd-coin',
        'BCH'   => 'bitcoin-cash',
        'XLM'   => 'stellar',
        'QNT'   => 'quant-network',
        'ALGO'  => 'algorand',
        'SUI'   => 'sui',
        'XAUT'  => 'tether-gold',
        'PAXG'  => 'pax-gold',
        'KAG'   => 'kinesis-silver',
        'TRUMP' => 'official-trump',
        'RLUSD' => 'ripple-usd',
        'SFP'   => 'safepal',
        'SFP'   => 'safepal',
    ];

    $geckoIds = implode(',', array_unique(array_values($geckoMap)));
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=' . $geckoIds
         . '&vs_currencies=usd&include_24hr_change=true';

    // CoinGecko rejects requests without a User-Agent, so use cURL (more reliable
    // than file_get_contents and consistent with the payment endpoints).
    $isDev = (getenv('APP_ENV') === 'development');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_USERAGENT      => 'Qblockx/1.0 (+https://qblockx.com)',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => !$isDev,
        CURLOPT_SSL_VERIFYHOST => $isDev ? 0 : 2,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $httpCode !== 200) {
        error_log('crypto-prices: CoinGecko fetch failed (HTTP ' . $httpCode . ')');
        return;
    }

    $data = json_decode($response, true);
    if (!$data) return;

    // Reverse map: geckoId → price data
    $priceByGeckoId = [];
    foreach ($data as $geckoId => $info) {
        $priceByGeckoId[$geckoId] = [
            'price'    => (float) ($info['usd'] ?? 0),
            'change'   => (float) ($info['usd_24h_change'] ?? 0),
        ];
    }

    // Update each currency
    $updateStmt = $db->prepare(
        "UPDATE currencies SET current_price_usd = :price, price_change_24h_pct = :change,
                price_updated_at = NOW()
         WHERE symbol = :symbol AND is_active = 1"
    );

    foreach ($geckoMap as $symbol => $geckoId) {
        if (isset($priceByGeckoId[$geckoId])) {
            $updateStmt->execute([
                'price'  => $priceByGeckoId[$geckoId]['price'],
                'change' => round($priceByGeckoId[$geckoId]['change'], 4),
                'symbol' => $symbol,
            ]);
        }
    }

    // Log the refresh
    try {
        $db->prepare("INSERT INTO cron_logs (job_name, status, message) VALUES ('price_refresh', 'success', :msg)")
           ->execute(['msg' => 'Updated ' . count($priceByGeckoId) . ' prices from CoinGecko']);
    } catch (\Exception $e) {
        // Non-critical — don't fail on log
    }
}
