<?php
/**
 * Project: qblockx
 * Proxy: api/utilities/crypto-prices.php
 *
 * Server-side proxy for CoinGecko v3 public API.
 * Fetches asset prices and caches for 60 seconds to avoid rate limits.
 * Normalises response to { data: [...] } with CoinCap-compatible field names.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$cacheFile = sys_get_temp_dir() . '/qblockx_crypto_cache.json';
$cacheTtl  = 60;

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
    echo file_get_contents($cacheFile);
    exit;
}

// CoinGecko coin IDs (free API, no key required)
$geckoIds = 'bitcoin,ethereum,binancecoin,solana,ripple,tether,usd-coin';
$url = 'https://api.coingecko.com/api/v3/coins/markets'
     . '?vs_currency=usd&ids=' . $geckoIds
     . '&order=market_cap_desc&per_page=7&page=1&sparkline=false';

$ctx = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'header'  => "Accept: application/json\r\nUser-Agent: Qblockx/1.0\r\n",
        'timeout' => 8,
    ],
    'ssl' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ],
]);

$response = @file_get_contents($url, false, $ctx);

if ($response === false) {
    if (file_exists($cacheFile)) {
        echo file_get_contents($cacheFile);
    } else {
        echo json_encode(['data' => []]);
    }
    exit;
}

$decoded = json_decode($response, true);
if (!$decoded || !is_array($decoded)) {
    if (file_exists($cacheFile)) {
        echo file_get_contents($cacheFile);
    } else {
        echo json_encode(['data' => []]);
    }
    exit;
}

// Map CoinGecko IDs to friendly IDs used in frontend _coinMeta
$idMap = [
    'bitcoin'     => 'bitcoin',
    'ethereum'    => 'ethereum',
    'binancecoin' => 'binance-coin',
    'solana'      => 'solana',
    'ripple'      => 'xrp',
    'tether'      => 'tether',
    'usd-coin'    => 'usd-coin',
];

$normalised = array_map(function ($coin) use ($idMap) {
    $id = $idMap[$coin['id']] ?? $coin['id'];
    return [
        'id'               => $id,
        'name'             => $coin['name']               ?? $id,
        'symbol'           => strtoupper($coin['symbol']  ?? $id),
        'priceUsd'         => (string) ($coin['current_price']              ?? 0),
        'changePercent24Hr'=> (string) ($coin['price_change_percentage_24h'] ?? 0),
        'marketCapUsd'     => (string) ($coin['market_cap']                 ?? 0),
        'volumeUsd24Hr'    => (string) ($coin['total_volume']               ?? 0),
    ];
}, $decoded);

$output = json_encode(['data' => $normalised]);
file_put_contents($cacheFile, $output);
echo $output;
