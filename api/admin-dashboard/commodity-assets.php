<?php
/**
 * Project: qblockx
 * Admin: Commodity Assets — CRUD
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

function fetchComAssets($db) {
    return $db->query("SELECT * FROM commodity_assets ORDER BY sort_order ASC, id ASC")->fetchAll();
}

try {
    $db = Database::getInstance()->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(['success' => true, 'data' => ['assets' => fetchComAssets($db)]]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $input['action'] ?? '';

        if ($action === 'create') {
            $name            = trim($input['name'] ?? '');
            $symbol          = strtoupper(trim($input['symbol'] ?? ''));
            $tradingview_sym = trim($input['tradingview_sym'] ?? '');
            $min_investment  = (float)($input['min_investment'] ?? 0);
            $duration_days   = (int)($input['duration_days'] ?? 30);
            $yield_min       = (float)($input['yield_min'] ?? 0);
            $yield_max       = (float)($input['yield_max'] ?? 0);
            $is_active       = isset($input['is_active']) ? (int)$input['is_active'] : 1;
            $sort_order      = (int)($input['sort_order'] ?? 0);

            if (empty($name) || empty($symbol) || $min_investment <= 0) {
                echo json_encode(['success' => false, 'message' => 'Name, symbol, and min investment are required']); exit;
            }

            $db->prepare(
                "INSERT INTO commodity_assets
                    (name, symbol, tradingview_sym, min_investment, duration_days, yield_min, yield_max, is_active, sort_order)
                 VALUES (:name,:sym,:tv,:min,:days,:ymin,:ymax,:active,:sort)"
            )->execute([
                'name' => $name, 'sym' => $symbol, 'tv' => $tradingview_sym,
                'min' => $min_investment, 'days' => $duration_days,
                'ymin' => $yield_min, 'ymax' => $yield_max,
                'active' => $is_active, 'sort' => $sort_order
            ]);
            echo json_encode(['success' => true, 'message' => 'Asset created', 'data' => ['assets' => fetchComAssets($db)]]);
            exit;
        }

        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset ID']); exit;
        }

        $check = $db->prepare("SELECT id FROM commodity_assets WHERE id = :id LIMIT 1");
        $check->execute(['id' => $id]);
        if (!$check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Asset not found']); exit;
        }

        if ($action === 'update') {
            $setParts = [];
            $params   = ['id' => $id];

            $strFields = ['name', 'symbol', 'tradingview_sym'];
            foreach ($strFields as $f) {
                if (isset($input[$f]) && $input[$f] !== '') {
                    $setParts[] = "$f = :$f";
                    $params[$f] = $f === 'symbol' ? strtoupper(trim($input[$f])) : trim($input[$f]);
                }
            }
            $numFields = ['min_investment', 'duration_days', 'yield_min', 'yield_max', 'sort_order'];
            foreach ($numFields as $f) {
                if (isset($input[$f]) && is_numeric($input[$f])) {
                    $setParts[] = "$f = :$f";
                    $params[$f] = in_array($f, ['duration_days', 'sort_order']) ? (int)$input[$f] : (float)$input[$f];
                }
            }
            if (isset($input['is_active'])) {
                $setParts[] = 'is_active = :is_active'; $params['is_active'] = (int)$input['is_active'];
            }

            if ($setParts) {
                $db->prepare("UPDATE commodity_assets SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE id = :id")
                   ->execute($params);
            }
            echo json_encode(['success' => true, 'message' => 'Asset updated', 'data' => ['assets' => fetchComAssets($db)]]);
            exit;

        } elseif ($action === 'toggle') {
            $db->prepare("UPDATE commodity_assets SET is_active = NOT is_active, updated_at = NOW() WHERE id = :id")
               ->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Asset toggled', 'data' => ['assets' => fetchComAssets($db)]]);
            exit;

        } elseif ($action === 'delete') {
            $db->prepare("DELETE FROM commodity_assets WHERE id = :id")->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Asset deleted', 'data' => ['assets' => fetchComAssets($db)]]);
            exit;

        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action']); exit;
        }
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);

} catch (PDOException $e) {
    error_log('commodity-assets error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
