<?php
/**
 * Project: qblockx
 * Admin: Investment Plans — CRUD
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

function fetchInvPlans($db) {
    return $db->query("SELECT * FROM investment_plans ORDER BY sort_order ASC, min_amount ASC")->fetchAll();
}

try {
    $db = Database::getInstance()->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(['success' => true, 'data' => ['plans' => fetchInvPlans($db)]]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $input['action'] ?? '';

        if ($action === 'create') {
            $name           = trim($input['name'] ?? '');
            $tier           = $input['tier'] ?? 'starter';
            $min_amount     = (float)($input['min_amount'] ?? 0);
            $max_amount     = (isset($input['max_amount']) && $input['max_amount'] !== '' && $input['max_amount'] !== null && is_numeric($input['max_amount']))
                              ? (float)$input['max_amount'] : 9999999999.99;
            $duration_days  = (int)($input['duration_days'] ?? 1);
            $yield_min      = (float)($input['yield_min'] ?? 0);
            $yield_max      = (float)($input['yield_max'] ?? 0);
            $commission_pct = (float)($input['commission_pct'] ?? 15.00);
            $is_compounded  = isset($input['is_compounded']) ? (int)$input['is_compounded'] : 0;
            $is_active      = isset($input['is_active']) ? (int)$input['is_active'] : 1;
            $sort_order     = (int)($input['sort_order'] ?? 0);

            if (empty($name) || $min_amount <= 0 || $duration_days < 1) {
                echo json_encode(['success' => false, 'message' => 'Name, min amount, and duration are required']); exit;
            }
            if (!in_array($tier, ['starter', 'elite'])) {
                echo json_encode(['success' => false, 'message' => 'Tier must be starter or elite']); exit;
            }

            $db->prepare(
                "INSERT INTO investment_plans
                    (name, tier, min_amount, max_amount, duration_days, yield_min, yield_max, commission_pct, is_compounded, is_active, sort_order)
                 VALUES (:name,:tier,:min,:max,:days,:ymin,:ymax,:comm,:comp,:active,:sort)"
            )->execute([
                'name'   => $name,   'tier'   => $tier,  'min'    => $min_amount,
                'max'    => $max_amount,  'days'   => $duration_days,
                'ymin'   => $yield_min,  'ymax'   => $yield_max,
                'comm'   => $commission_pct, 'comp' => $is_compounded,
                'active' => $is_active,  'sort'   => $sort_order
            ]);
            echo json_encode(['success' => true, 'message' => 'Plan created', 'data' => ['plans' => fetchInvPlans($db)]]);
            exit;
        }

        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid plan ID']); exit;
        }

        $check = $db->prepare("SELECT id FROM investment_plans WHERE id = :id LIMIT 1");
        $check->execute(['id' => $id]);
        if (!$check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Plan not found']); exit;
        }

        if ($action === 'update') {
            $setParts = [];
            $params   = ['id' => $id];

            if (!empty($input['name'])) {
                $setParts[] = 'name = :name'; $params['name'] = trim($input['name']);
            }
            if (isset($input['tier']) && in_array($input['tier'], ['starter', 'elite'])) {
                $setParts[] = 'tier = :tier'; $params['tier'] = $input['tier'];
            }
            $numFields = ['min_amount', 'duration_days', 'yield_min', 'yield_max', 'commission_pct', 'sort_order'];
            foreach ($numFields as $f) {
                if (isset($input[$f]) && is_numeric($input[$f])) {
                    $setParts[] = "$f = :$f";
                    $params[$f] = in_array($f, ['duration_days', 'sort_order']) ? (int)$input[$f] : (float)$input[$f];
                }
            }
            if (array_key_exists('max_amount', $input)) {
                $val = $input['max_amount'];
                $setParts[] = 'max_amount = :max_amount';
                $params['max_amount'] = ($val === null || $val === '' || !is_numeric($val))
                    ? 9999999999.99
                    : (float) $val;
            }
            if (isset($input['is_compounded'])) {
                $setParts[] = 'is_compounded = :is_compounded'; $params['is_compounded'] = (int)$input['is_compounded'];
            }

            if ($setParts) {
                $db->prepare("UPDATE investment_plans SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE id = :id")
                   ->execute($params);
            }
            echo json_encode(['success' => true, 'message' => 'Plan updated', 'data' => ['plans' => fetchInvPlans($db)]]);
            exit;

        } elseif ($action === 'toggle') {
            $db->prepare("UPDATE investment_plans SET is_active = NOT is_active, updated_at = NOW() WHERE id = :id")
               ->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Plan toggled', 'data' => ['plans' => fetchInvPlans($db)]]);
            exit;

        } elseif ($action === 'delete') {
            $usageCheck = $db->prepare("SELECT COUNT(*) FROM plan_investments WHERE plan_id = :id");
            $usageCheck->execute(['id' => $id]);
            if ((int) $usageCheck->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete a plan with existing investments. Deactivate it instead.']);
                exit;
            }
            $db->prepare("DELETE FROM investment_plans WHERE id = :id")->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Plan deleted', 'data' => ['plans' => fetchInvPlans($db)]]);
            exit;

        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action']); exit;
        }
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);

} catch (PDOException $e) {
    error_log('investment-plans error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
