<?php
declare(strict_types=1);

if (!defined('API_LIB_ONLY')) {
    define('API_LIB_ONLY', true);
    require_once dirname(__DIR__) . '/index.php';
}

$apiUser = api_authenticate_user();
$in = api_get_input();
$orderParam = trim((string)($in['order'] ?? ''));

if ($orderParam === '') {
    Response::json(['error' => 'Order ID is required.'], 400);
}

$order = Database::fetch(
    'SELECT id FROM orders WHERE user_id = ? AND (id = ? OR order_uid = ?) LIMIT 1',
    [(int)$apiUser['id'], ctype_digit($orderParam) ? (int)$orderParam : 0, $orderParam]
);

if (!$order) {
    Response::json(['error' => 'Order not found.'], 404);
}

$res = Order::requestRefill((int)$order['id'], (int)$apiUser['id']);
if (!$res['success']) {
    Response::json(['error' => $res['error'] ?? 'Refill failed.'], 400);
}

Response::json([
    'refill' => (int)$order['id'],
    'status' => $res['refill_status'],
]);
