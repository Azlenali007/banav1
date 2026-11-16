<?php
declare(strict_types=1);

$bootstrapContext = 'cron';
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$sec = require CONFIG_PATH . '/security.php';
if (PHP_SAPI !== 'cli') {
    $token = (string)($_GET['token'] ?? '');
    if ($token === '' || empty($sec['cron_token']) || !hash_equals((string)$sec['cron_token'], $token)) {
        Response::abort(403, 'Forbidden', 'Cron jobs cannot be executed publicly.');
    }
}

$lockFp = fopen(TEMP_PATH . '/cron_process_orders.lock', 'c');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    exit(0);
}

$processed = 0;
$failed = 0;

$orders = Database::fetchAll(
    "SELECT o.* FROM orders o
     INNER JOIN providers p ON p.id = o.provider_id AND p.status = 'active'
     WHERE o.status = 'pending' AND o.provider_id IS NOT NULL AND (o.provider_order_id IS NULL OR o.provider_order_id = '')
     ORDER BY o.id ASC LIMIT 50"
);

foreach ($orders as $ord) {
    $mapping = Database::fetch('SELECT provider_service_id FROM provider_service_mapping WHERE service_id = ? LIMIT 1', [$ord['service_id']]);
    $provider = Provider::getById((int)$ord['provider_id']);
    if (!$mapping || !$provider) {
        continue;
    }

    $res = Provider::createOrder($provider, (string)$mapping['provider_service_id'], (string)$ord['link'], (int)$ord['quantity']);
    if ($res['success'] && !empty($res['data']['order'])) {
        $pOrderId = (string)$res['data']['order'];
        Database::execute("UPDATE orders SET provider_order_id = ?, status = 'processing', updated_at = NOW() WHERE id = ?", [$pOrderId, $ord['id']]);
        Database::insert(
            'INSERT INTO order_logs (order_id, actor_type, old_status, new_status, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [$ord['id'], 'cron', 'pending', 'processing', "Cron forwarded to provider. Provider order ID: {$pOrderId}"]
        );
        $processed++;
    } else {
        Order::failAndRefundOrder((int)$ord['id'], (int)$ord['user_id'], (string)$ord['charge'], (string)$ord['order_uid'], (string)($res['error'] ?? 'Provider submission failed'));
        $failed++;
    }
}

Logger::file('cron', 'INFO', "process-orders completed: forwarded={$processed}, failed_refunded={$failed}");
flock($lockFp, LOCK_UN);
fclose($lockFp);
