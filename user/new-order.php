<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
$multiplier = (string)($currentUser['custom_rate_multiplier'] ?? '1.0000');

$categories = Database::fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY sort_order ASC, id ASC");
$services = Database::fetchAll(
    "SELECT s.id, s.category_id, s.name, s.description, s.rate_per_1000, s.min_qty, s.max_qty, s.average_time, s.refill_supported, s.cancel_supported
     FROM services s
     INNER JOIN categories c ON c.id = s.category_id
     WHERE s.status = 'active' AND c.status = 'active'
     ORDER BY c.sort_order ASC, s.sort_order ASC, s.id ASC"
);

foreach ($services as &$svc) {
    $svc['effective_rate'] = (float)money_mul((string)$svc['rate_per_1000'], $multiplier, 4);
}
unset($svc);

$preselectServiceId = (int)($_GET['service_id'] ?? $_POST['service_id'] ?? 0);
$initialQuantity = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? max(0, (int)$_POST['quantity']) : 0;
$linkValue = (string)($_POST['link'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $link = (string)($_POST['link'] ?? '');
    $qtyRaw = $_POST['quantity'] ?? '';
    $idemKey = Validator::cleanString($_POST['idempotency_key'] ?? '', 120);

    if (!Validator::isPositiveInt($qtyRaw)) {
        $error = 'Quantity must be a valid positive integer.';
    } else {
        $res = Order::placeOrder($userId, $serviceId, $link, (int)$qtyRaw, 'web', $idemKey !== '' ? $idemKey : null);
        if ($res['success']) {
            Session::setFlash('success', "Order {$res['order_uid']} placed successfully! Charged " . format_money($res['charge'], 2));
            Response::redirect('/user/order-view.php?id=' . (int)$res['order_id']);
        }
        $error = (string)$res['error'];
    }
}

$formIdemKey = bin2hex(random_bytes(16));
$currencySymbol = (string)get_setting('currency_symbol', DEFAULT_CURRENCY_SYMBOL);

Theme::render('user/new-order', [
    'currentUser' => $currentUser,
    'categories' => $categories,
    'services' => $services,
    'preselectServiceId' => $preselectServiceId,
    'initialQuantity' => $initialQuantity,
    'linkValue' => $linkValue,
    'formIdemKey' => $formIdemKey,
    'currencySymbol' => $currencySymbol,
    'error' => $error,
], 'user', [
    'pageTitle' => 'New Order',
    'currentUser' => $currentUser,
]);
