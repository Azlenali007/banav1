<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('orders.view');

$orderId = (int)($_GET['id'] ?? 0);
$order = Database::fetch(
    'SELECT o.*, u.username, u.email, s.name AS service_name, p.name AS provider_name
     FROM orders o
     INNER JOIN users u ON u.id = o.user_id
     INNER JOIN services s ON s.id = o.service_id
     LEFT JOIN providers p ON p.id = o.provider_id
     WHERE o.id = ? LIMIT 1',
    [$orderId]
);
if (!$order) {
    Response::abort(404, 'Order Not Found', 'The requested order does not exist.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_permission('orders.manage');
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'manual_update') {
        $newStatus = (string)($_POST['status'] ?? '');
        $startCount = max(0, (int)($_POST['start_count'] ?? 0));
        $remains = max(0, (int)($_POST['remains'] ?? 0));
        $reason = Validator::cleanString($_POST['reason'] ?? '', 300);

        if ($reason === '') {
            Session::setFlash('error', 'An audit reason is required for manual order updates.');
        } else {
            $res = Order::applyStatusTransition($orderId, $newStatus, $startCount, $remains, 'admin', (int)$currentAdmin['id'], "Admin manual update: {$reason}");
            if ($res['success']) {
                Logger::adminActivity((int)$currentAdmin['id'], 'order.manual_update', 'order', $orderId, "Status={$newStatus}, Start={$startCount}, Remains={$remains}. Reason: {$reason}");
                Session::setFlash('success', 'Order updated and logged.');
            } else {
                Session::setFlash('error', (string)$res['error']);
            }
        }
    } elseif ($action === 'sync_provider') {
        $res = Order::syncOrder($orderId);
        Session::setFlash($res['success'] ? 'success' : 'error', $res['success'] ? 'Synchronized order status from provider.' : (string)$res['error']);
    } elseif ($action === 'update_refill') {
        $rStatus = in_array($_POST['refill_status'] ?? '', ['none', 'requested', 'processing', 'completed', 'rejected'], true) ? $_POST['refill_status'] : 'none';
        Database::execute('UPDATE orders SET refill_status = ?, updated_at = NOW() WHERE id = ?', [$rStatus, $orderId]);
        Database::insert('INSERT INTO order_logs (order_id, actor_type, actor_id, old_status, new_status, message, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())', [$orderId, 'admin', (int)$currentAdmin['id'], $order['status'], $order['status'], "Admin updated refill status to {$rStatus}"]);
        Logger::adminActivity((int)$currentAdmin['id'], 'order.refill_update', 'order', $orderId, "Refill status set to {$rStatus}");
        Session::setFlash('success', 'Refill status updated.');
    }

    Response::redirect('/admin/order-view.php?id=' . $orderId);
}

$logs = Database::fetchAll('SELECT * FROM order_logs WHERE order_id = ? ORDER BY id DESC', [$orderId]);

Theme::render('admin/order-view', [
    'orderId' => $orderId,
    'order' => $order,
    'logs' => $logs,
], 'admin', [
    'pageTitle' => 'Manage Order ' . $order['order_uid'],
    'currentAdmin' => $currentAdmin,
]);
