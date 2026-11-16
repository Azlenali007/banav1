<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
$status = strtolower(trim((string)($_GET['status'] ?? 'all')));
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ['o.user_id = ?'];
$params = [$userId];

if ($status !== 'all' && in_array($status, ORDER_STATUSES, true)) {
    $where[] = 'o.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[] = '(o.order_uid LIKE ? OR o.link LIKE ? OR s.name LIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
$whereSql = implode(' AND ', $where);

$total = (int)Database::fetchColumn(
    "SELECT COUNT(*) FROM orders o INNER JOIN services s ON s.id = o.service_id WHERE {$whereSql}",
    $params
);
$pg = paginate_build($total, $page, 20);

$orders = Database::fetchAll(
    "SELECT o.*, s.name AS service_name, s.refill_supported, s.cancel_supported
     FROM orders o
     INNER JOIN services s ON s.id = o.service_id
     WHERE {$whereSql}
     ORDER BY o.id DESC
     LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
    $params
);

Theme::render('user/orders', [
    'status' => $status,
    'q' => $q,
    'orders' => $orders,
    'pg' => $pg,
], 'user', [
    'pageTitle' => 'Order History',
    'currentUser' => $currentUser,
]);
