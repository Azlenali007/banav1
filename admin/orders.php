<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('orders.view');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_permission('orders.manage');
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'bulk_status') {
        $ids = array_filter(array_map('intval', (array)($_POST['order_ids'] ?? [])));
        $newStatus = (string)($_POST['bulk_status'] ?? '');
        $reason = Validator::cleanString($_POST['bulk_reason'] ?? 'Bulk admin update', 200);
        if (empty($ids) || !in_array($newStatus, ORDER_STATUSES, true)) {
            Session::setFlash('error', 'Select at least one order and a valid status.');
        } else {
            $count = 0;
            foreach ($ids as $oid) {
                $ord = Database::fetch('SELECT start_count, remains FROM orders WHERE id = ? LIMIT 1', [$oid]);
                if ($ord) {
                    $r = Order::applyStatusTransition($oid, $newStatus, (int)$ord['start_count'], (int)$ord['remains'], 'admin', (int)$currentAdmin['id'], $reason);
                    if ($r['success']) {
                        $count++;
                    }
                }
            }
            Logger::adminActivity((int)$currentAdmin['id'], 'orders.bulk_update', 'orders', implode(',', $ids), "Bulk changed {$count} orders to {$newStatus}. Reason: {$reason}");
            Session::setFlash('success', "Updated {$count} orders to {$newStatus}.");
        }
    }
    Response::redirect('/admin/orders.php');
}

$status = strtolower(trim((string)($_GET['status'] ?? 'all')));
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];
if ($status !== 'all' && in_array($status, ORDER_STATUSES, true)) {
    $where[] = 'o.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[] = '(o.order_uid LIKE ? OR o.provider_order_id LIKE ? OR u.username LIKE ? OR o.link LIKE ? OR o.id = ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = ctype_digit($q) ? (int)$q : 0;
}
$whereSql = implode(' AND ', $where);

$total = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders o INNER JOIN users u ON u.id = o.user_id WHERE {$whereSql}", $params);
$pg = paginate_build($total, $page, 25);

$orders = Database::fetchAll(
    "SELECT o.*, u.username, s.name AS service_name, p.name AS provider_name
     FROM orders o
     INNER JOIN users u ON u.id = o.user_id
     INNER JOIN services s ON s.id = o.service_id
     LEFT JOIN providers p ON p.id = o.provider_id
     WHERE {$whereSql}
     ORDER BY o.id DESC
     LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
    $params
);

Theme::render('admin/orders', [
    'status' => $status,
    'q' => $q,
    'orders' => $orders,
    'pg' => $pg,
], 'admin', [
    'pageTitle' => 'Orders Management',
    'currentAdmin' => $currentAdmin,
]);
