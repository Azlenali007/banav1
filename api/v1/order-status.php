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
    'SELECT * FROM orders WHERE user_id = ? AND (id = ? OR order_uid = ?) LIMIT 1',
    [(int)$apiUser['id'], ctype_digit($orderParam) ? (int)$orderParam : 0, $orderParam]
);

if (!$order) {
    Response::json(['error' => 'Order not found.'], 404);
}

Response::json([
    'order' => (int)$order['id'],
    'order_uid' => $order['order_uid'],
    'charge' => $order['charge'],
    'start_count' => (int)$order['start_count'],
    'status' => $order['status'],
    'remains' => (int)$order['remains'],
    'currency' => get_setting('currency_code', DEFAULT_CURRENCY),
]);
