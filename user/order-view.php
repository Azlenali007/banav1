<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
$orderId = (int)($_GET['id'] ?? 0);

$order = Database::fetch(
    'SELECT o.*, s.name AS service_name, s.refill_supported, s.cancel_supported
     FROM orders o
     INNER JOIN services s ON s.id = o.service_id
     WHERE o.id = ? AND o.user_id = ? LIMIT 1',
    [$orderId, $userId]
);

if (!$order) {
    Response::abort(404, 'Order Not Found', 'This order does not exist or does not belong to your account.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'refill') {
        $res = Order::requestRefill($orderId, $userId);
        Session::setFlash($res['success'] ? 'success' : 'error', $res['success'] ? 'Refill request submitted.' : (string)$res['error']);
    } elseif ($action === 'cancel') {
        $res = Order::requestCancel($orderId, $userId);
        Session::setFlash($res['success'] ? 'success' : 'error', $res['success'] ? 'Cancellation request submitted.' : (string)$res['error']);
    }
    Response::redirect('/user/order-view.php?id=' . $orderId);
}

$logs = Database::fetchAll('SELECT * FROM order_logs WHERE order_id = ? ORDER BY id DESC', [$orderId]);

Theme::render('user/order-view', [
    'order' => $order,
    'logs' => $logs,
], 'user', [
    'pageTitle' => 'Order ' . $order['order_uid'],
    'currentUser' => $currentUser,
]);
