<?php
/**
 * Project: qblockx
 * Admin: Real Estate Pools — CRUD
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

function fetchRePools($db) {
    return $db->query("SELECT * FROM realestate_pools ORDER BY sort_order ASC, id ASC")->fetchAll();
}

try {
    $db = Database::getInstance()->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(['success' => true, 'data' => ['pools' => fetchRePools($db)]]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $input['action'] ?? '';

        if ($action === 'create') {
            $name             = trim($input['name'] ?? '');
            $property_type    = trim($input['property_type'] ?? '');
            $min_investment   = (float)($input['min_investment'] ?? 0);
            $duration_days    = (int)($input['duration_days'] ?? 90);
            $yield_min        = (float)($input['yield_min'] ?? 0);
            $yield_max        = (float)($input['yield_max'] ?? 0);
            $payout_frequency = in_array($input['payout_frequency'] ?? '', ['monthly','quarterly'])
                                ? $input['payout_frequency'] : 'monthly';
            $is_compounded    = isset($input['is_compounded']) ? (int)$input['is_compounded'] : 0;
            $is_active        = isset($input['is_active']) ? (int)$input['is_active'] : 1;
            $image_url        = trim($input['image_url'] ?? '') ?: null;
            $location_tag     = trim($input['location_tag'] ?? '') ?: null;
            $occupancy_pct    = (isset($input['occupancy_pct']) && is_numeric($input['occupancy_pct']))
                                ? (float)$input['occupancy_pct'] : null;
            $sort_order       = (int)($input['sort_order'] ?? 0);

            if (empty($name) || empty($property_type) || $min_investment <= 0) {
                echo json_encode(['success' => false, 'message' => 'Name, property type, and min investment are required']); exit;
            }

            $db->prepare(
                "INSERT INTO realestate_pools
                    (name, property_type, min_investment, duration_days, yield_min, yield_max,
                     payout_frequency, is_compounded, is_active, image_url, location_tag, occupancy_pct, sort_order)
                 VALUES (:name,:ptype,:min,:days,:ymin,:ymax,:pfreq,:comp,:active,:img,:loc,:occ,:sort)"
            )->execute([
                'name' => $name, 'ptype' => $property_type, 'min' => $min_investment,
                'days' => $duration_days, 'ymin' => $yield_min, 'ymax' => $yield_max,
                'pfreq' => $payout_frequency, 'comp' => $is_compounded, 'active' => $is_active,
                'img' => $image_url, 'loc' => $location_tag, 'occ' => $occupancy_pct, 'sort' => $sort_order
            ]);
            echo json_encode(['success' => true, 'message' => 'Pool created', 'data' => ['pools' => fetchRePools($db)]]);
            exit;
        }

        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid pool ID']); exit;
        }

        $check = $db->prepare("SELECT id FROM realestate_pools WHERE id = :id LIMIT 1");
        $check->execute(['id' => $id]);
        if (!$check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Pool not found']); exit;
        }

        if ($action === 'update') {
            $setParts = [];
            $params   = ['id' => $id];

            $strFields = ['name', 'property_type', 'image_url', 'location_tag'];
            foreach ($strFields as $f) {
                if (array_key_exists($f, $input)) {
                    $setParts[] = "$f = :$f";
                    $params[$f] = trim($input[$f]) ?: null;
                }
            }
            if (isset($input['payout_frequency']) && in_array($input['payout_frequency'], ['monthly','quarterly'])) {
                $setParts[] = 'payout_frequency = :payout_frequency';
                $params['payout_frequency'] = $input['payout_frequency'];
            }
            $numFields = ['min_investment', 'duration_days', 'yield_min', 'yield_max', 'sort_order'];
            foreach ($numFields as $f) {
                if (isset($input[$f]) && is_numeric($input[$f])) {
                    $setParts[] = "$f = :$f";
                    $params[$f] = in_array($f, ['duration_days', 'sort_order']) ? (int)$input[$f] : (float)$input[$f];
                }
            }
            if (array_key_exists('occupancy_pct', $input)) {
                $setParts[] = 'occupancy_pct = :occupancy_pct';
                $params['occupancy_pct'] = is_numeric($input['occupancy_pct']) ? (float)$input['occupancy_pct'] : null;
            }
            if (isset($input['is_compounded'])) {
                $setParts[] = 'is_compounded = :is_compounded'; $params['is_compounded'] = (int)$input['is_compounded'];
            }
            if (isset($input['is_active'])) {
                $setParts[] = 'is_active = :is_active'; $params['is_active'] = (int)$input['is_active'];
            }

            if ($setParts) {
                $db->prepare("UPDATE realestate_pools SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE id = :id")
                   ->execute($params);
            }
            echo json_encode(['success' => true, 'message' => 'Pool updated', 'data' => ['pools' => fetchRePools($db)]]);
            exit;

        } elseif ($action === 'toggle') {
            $db->prepare("UPDATE realestate_pools SET is_active = NOT is_active, updated_at = NOW() WHERE id = :id")
               ->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Pool toggled', 'data' => ['pools' => fetchRePools($db)]]);
            exit;

        } elseif ($action === 'delete') {
            $db->prepare("DELETE FROM realestate_pools WHERE id = :id")->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Pool deleted', 'data' => ['pools' => fetchRePools($db)]]);
            exit;

        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action']); exit;
        }
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);

} catch (PDOException $e) {
    error_log('realestate-pools error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
