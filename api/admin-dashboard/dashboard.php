<?php
/**
 * Quantum BlocX — Admin API: dashboard.php
 * GET  → Platform stats, recent transactions, KYC queue, cards, tickets, currencies, fees
 * POST → Admin actions (approve_kyc, reject_kyc, activate_card, update_setting, credit_debit, reply_ticket)
 */

require_once '../../config/database.php';
require_once '../../api/utilities/auth-check.php';
header('Content-Type: application/json');

requireAdmin();

try {
    $db      = Database::getInstance()->getConnection();
    $section = $_GET['section'] ?? 'overview';

    // ── POST: Admin actions ──────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $input['action'] ?? '';

        if ($action === 'approve_kyc') {
            $kycId = (int) ($input['kyc_id'] ?? 0);
            $db->prepare("UPDATE kyc_applications SET status='approved', reviewed_by=:admin, reviewed_at=NOW() WHERE id=:id")
               ->execute(['admin' => getAuthUser()['id'], 'id' => $kycId]);
            $userId = $db->query("SELECT user_id FROM kyc_applications WHERE id=$kycId")->fetchColumn();
            if ($userId) $db->prepare("UPDATE users SET kyc_status='verified' WHERE id=:uid")->execute(['uid'=>$userId]);
            echo json_encode(['success'=>true,'message'=>'KYC approved']); exit;
        }

        if ($action === 'reject_kyc') {
            $kycId = (int) ($input['kyc_id'] ?? 0);
            $reason = trim($input['reason'] ?? 'Rejected by admin');
            $db->prepare("UPDATE kyc_applications SET status='rejected', rejection_reason=:r, reviewed_by=:admin, reviewed_at=NOW() WHERE id=:id")
               ->execute(['r'=>$reason,'admin'=>getAuthUser()['id'],'id'=>$kycId]);
            $userId = $db->query("SELECT user_id FROM kyc_applications WHERE id=$kycId")->fetchColumn();
            if ($userId) $db->prepare("UPDATE users SET kyc_status='rejected' WHERE id=:uid")->execute(['uid'=>$userId]);
            echo json_encode(['success'=>true,'message'=>'KYC rejected']); exit;
        }

        if ($action === 'activate_card') {
            $cardId = (int) ($input['card_id'] ?? 0);
            $db->prepare("UPDATE virtual_cards SET status='active', activated_at=NOW(), expires_at=DATE_ADD(NOW(),INTERVAL 5 YEAR) WHERE id=:id")
               ->execute(['id'=>$cardId]);
            $row = $db->query("SELECT user_id, card_tier FROM virtual_cards WHERE id=$cardId")->fetch(PDO::FETCH_ASSOC);
            if ($row) $db->prepare("UPDATE users SET card_tier=:tier WHERE id=:uid")->execute(['tier'=>$row['card_tier'],'uid'=>$row['user_id']]);
            echo json_encode(['success'=>true,'message'=>'Card activated']); exit;
        }

        if ($action === 'update_setting') {
            $key = trim($input['key'] ?? '');
            $val = trim($input['value'] ?? '');
            if ($key) {
                $db->prepare("UPDATE system_settings SET value=:v WHERE `key`=:k")->execute(['v'=>$val,'k'=>$key]);
                echo json_encode(['success'=>true,'message'=>'Setting updated']); exit;
            }
        }

        if ($action === 'credit_debit') {
            $userId    = (int) ($input['user_id'] ?? 0);
            $currencyId = (int) ($input['currency_id'] ?? 0);
            $amount    = (float) ($input['amount'] ?? 0);
            $type      = $input['type'] ?? 'admin_credit'; // admin_credit or admin_debit
            $notes     = trim($input['notes'] ?? '');

            if (!$userId || !$currencyId || $amount <= 0) {
                echo json_encode(['success'=>false,'message'=>'Invalid parameters']); exit;
            }

            // Get or create wallet
            $w = $db->prepare("SELECT id, balance FROM wallets WHERE user_id=:uid AND currency_id=:cid");
            $w->execute(['uid'=>$userId,'cid'=>$currencyId]);
            $wallet = $w->fetch(PDO::FETCH_ASSOC);

            if (!$wallet) {
                $cNet = $db->prepare("SELECT network, symbol FROM currencies WHERE id=:cid");
                $cNet->execute(['cid'=>$currencyId]);
                $cInfo = $cNet->fetch(PDO::FETCH_ASSOC);
                $addr = '0x'.bin2hex(random_bytes(20));
                $db->prepare("INSERT INTO wallets (user_id,currency_id,address,network) VALUES (:uid,:cid,:addr,:net)")
                   ->execute(['uid'=>$userId,'cid'=>$currencyId,'addr'=>$addr,'net'=>$cInfo['network']??'']);
                $walletId = (int)$db->lastInsertId();
                $balance = 0;
                $symbol = $cInfo['symbol'] ?? '';
            } else {
                $walletId = (int)$wallet['id'];
                $balance = (float)$wallet['balance'];
                $symStmt = $db->prepare("SELECT symbol FROM currencies WHERE id=:cid");
                $symStmt->execute(['cid'=>$currencyId]);
                $symbol = $symStmt->fetchColumn() ?: '';
            }

            if ($type === 'admin_debit' && $balance < $amount) {
                echo json_encode(['success'=>false,'message'=>'Insufficient balance to debit']); exit;
            }

            $op = ($type === 'admin_credit') ? '+' : '-';
            $db->prepare("UPDATE wallets SET balance = balance $op :amt WHERE id=:wid")
               ->execute(['amt'=>$amount,'wid'=>$walletId]);

            $db->prepare("INSERT INTO transactions (user_id,wallet_id,type,amount,currency_id,currency_symbol,status,notes,completed_at) VALUES (:uid,:wid,:type,:amt,:cid,:sym,'completed',:notes,NOW())")
               ->execute(['uid'=>$userId,'wid'=>$walletId,'type'=>$type,'amt'=>$amount,'cid'=>$currencyId,'sym'=>$symbol,'notes'=>$notes]);

            echo json_encode(['success'=>true,'message'=>ucfirst(str_replace('_',' ',$type)).' applied']); exit;
        }

        if ($action === 'reply_ticket') {
            $ticketId = (int) ($input['ticket_id'] ?? 0);
            $body     = trim($input['body'] ?? '');
            if (!$ticketId || !$body) { echo json_encode(['success'=>false,'message'=>'Missing fields']); exit; }
            $db->prepare("INSERT INTO support_ticket_replies (ticket_id,user_id,body,is_staff_reply) VALUES (:tid,:uid,:body,1)")
               ->execute(['tid'=>$ticketId,'uid'=>getAuthUser()['id'],'body'=>$body]);
            $db->prepare("UPDATE support_tickets SET status='in_progress' WHERE id=:tid AND status='open'")->execute(['tid'=>$ticketId]);
            echo json_encode(['success'=>true,'message'=>'Reply sent']); exit;
        }

        if ($action === 'close_ticket') {
            $ticketId = (int) ($input['ticket_id'] ?? 0);
            $db->prepare("UPDATE support_tickets SET status='closed', closed_at=NOW() WHERE id=:tid")->execute(['tid'=>$ticketId]);
            echo json_encode(['success'=>true,'message'=>'Ticket closed']); exit;
        }

        echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
    }

    // ── GET: Section data ────────────────────────────────────────

    if ($section === 'overview') {
        $stats = [];
        $stats['total_users']    = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
        $stats['kyc_pending']    = (int)$db->query("SELECT COUNT(*) FROM kyc_applications WHERE status IN ('pending','under_review')")->fetchColumn();
        $stats['active_cards']   = (int)$db->query("SELECT COUNT(*) FROM virtual_cards WHERE status='active'")->fetchColumn();
        $stats['tx_count_30d']   = (int)$db->query("SELECT COUNT(*) FROM transactions WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();
        $stats['swap_count_30d'] = (int)$db->query("SELECT COUNT(*) FROM swaps WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();
        $stats['open_tickets']   = (int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE status NOT IN ('closed','resolved')")->fetchColumn();

        $recentTx = $db->query("SELECT t.*, u.email, u.full_name FROM transactions t JOIN users u ON u.id=t.user_id ORDER BY t.created_at DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success'=>true,'data'=>['stats'=>$stats,'recent_transactions'=>$recentTx]]);
        exit;
    }

    if ($section === 'users') {
        $search = trim($_GET['search'] ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page-1)*$limit;

        $where = "WHERE role='user'";
        $params = [];
        if ($search) { $where .= " AND (full_name LIKE :s OR email LIKE :s2)"; $params['s']="%$search%"; $params['s2']="%$search%"; }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM users $where"); $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT id,email,full_name,username,kyc_status,card_tier,is_active,created_at FROM users $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);

        echo json_encode(['success'=>true,'data'=>['users'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'page'=>$page,'pages'=>ceil($total/$limit)]]);
        exit;
    }

    if ($section === 'kyc') {
        $stmt = $db->query("SELECT k.*, u.email AS user_email, u.full_name AS user_name FROM kyc_applications k JOIN users u ON u.id=k.user_id ORDER BY k.submitted_at DESC");
        echo json_encode(['success'=>true,'data'=>['applications'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]]);
        exit;
    }

    if ($section === 'transactions') {
        $type = trim($_GET['type'] ?? '');
        $page = max(1,(int)($_GET['page']??1)); $limit=25; $offset=($page-1)*$limit;
        $where=''; $params=[];
        if ($type) { $where="WHERE t.type=:type"; $params['type']=$type; }
        $total = (int)$db->prepare("SELECT COUNT(*) FROM transactions t $where")->execute($params) ? $db->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;
        // simpler approach:
        $cStmt=$db->prepare("SELECT COUNT(*) FROM transactions t $where"); $cStmt->execute($params); $total=(int)$cStmt->fetchColumn();
        $stmt=$db->prepare("SELECT t.*, u.email, u.full_name FROM transactions t JOIN users u ON u.id=t.user_id $where ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        echo json_encode(['success'=>true,'data'=>['transactions'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'page'=>$page,'pages'=>ceil($total/$limit)]]);
        exit;
    }

    if ($section === 'cards') {
        $stmt=$db->query("SELECT vc.*, u.email, u.full_name FROM virtual_cards vc JOIN users u ON u.id=vc.user_id ORDER BY vc.created_at DESC");
        echo json_encode(['success'=>true,'data'=>['cards'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]]);
        exit;
    }

    if ($section === 'wallets') {
        $search = trim($_GET['search'] ?? '');
        $page = max(1,(int)($_GET['page']??1)); $limit=30; $offset=($page-1)*$limit;
        $where=''; $params=[];
        if ($search) { $where="AND (u.full_name LIKE :s OR u.email LIKE :s2)"; $params['s']="%$search%"; $params['s2']="%$search%"; }
        $cStmt=$db->prepare("SELECT COUNT(*) FROM wallets w JOIN users u ON u.id=w.user_id WHERE w.is_active=1 $where"); $cStmt->execute($params); $total=(int)$cStmt->fetchColumn();
        $stmt=$db->prepare("SELECT w.*, c.symbol, c.name AS currency_name, c.network, u.email, u.full_name FROM wallets w JOIN currencies c ON c.id=w.currency_id JOIN users u ON u.id=w.user_id WHERE w.is_active=1 $where ORDER BY u.id, c.sort_order LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        echo json_encode(['success'=>true,'data'=>['wallets'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'page'=>$page,'pages'=>ceil($total/$limit)]]);
        exit;
    }

    if ($section === 'support') {
        $filter = trim($_GET['filter'] ?? '');
        $where=''; $params=[];
        if ($filter) { $where="WHERE t.status=:f"; $params['f']=$filter; }
        $stmt=$db->prepare("SELECT t.*, u.email, u.full_name FROM support_tickets t JOIN users u ON u.id=t.user_id $where ORDER BY t.created_at DESC");
        $stmt->execute($params);
        echo json_encode(['success'=>true,'data'=>['tickets'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]]);
        exit;
    }

    if ($section === 'mining') {
        $stmt=$db->query("SELECT ms.*, c.symbol, c.name AS currency_name, u.email, u.full_name FROM mining_sessions ms JOIN currencies c ON c.id=ms.currency_id JOIN users u ON u.id=ms.user_id ORDER BY ms.started_at DESC");
        echo json_encode(['success'=>true,'data'=>['sessions'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]]);
        exit;
    }

    if ($section === 'settings') {
        $settings   = $db->query("SELECT `key`, value FROM system_settings ORDER BY `key`")->fetchAll(PDO::FETCH_KEY_PAIR);
        $fees       = $db->query("SELECT * FROM fee_schedule ORDER BY card_tier, fee_type")->fetchAll(PDO::FETCH_ASSOC);
        $currencies = $db->query("SELECT * FROM currencies ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true,'data'=>['settings'=>$settings,'fees'=>$fees,'currencies'=>$currencies]]);
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown section']);

} catch (PDOException $e) {
    error_log('admin/dashboard.php: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
