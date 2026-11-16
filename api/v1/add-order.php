<?php
declare(strict_types=1);

if (!defined('API_LIB_ONLY')) {
    define('API_LIB_ONLY', true);
    require_once dirname(__DIR__) . '/index.php';
}

$apiUser = api_authenticate_user();
$in = api_get_input();

$serviceId = (int)($in['service'] ?? 0);
$link = (string)($in['link'] ?? '');
$qtyRaw = $in['quantity'] ?? '';
$idemKey = Validator::cleanString($in['idempotency_key'] ?? '', 120);

if ($serviceId <= 0 || !Validator::isPositiveInt($qtyRaw)) {
    Response::json(['error' => 'Valid service ID and positive integer quantity are required.'], 400);
}

$res = Order::placeOrder(
    (int)$apiUser['id'],
    $serviceId,
    $link,
    (int)$qtyRaw,
    'api',
    $idemKey !== '' ? 'api_' . (int)$apiUser['id'] . '_' . $idemKey : null
);

if (!$res['success']) {
    Response::json(['error' => $res['error'] ?? 'Unable to place order.'], 400);
}

Response::json([
    'order' => (int)$res['order_id'],
    'order_uid' => $res['order_uid'],
    'charge' => $res['charge'],
]);
