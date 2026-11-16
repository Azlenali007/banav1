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

$lockFp = fopen(TEMP_PATH . '/cron_sync_orders.lock', 'c');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    exit(0);
}

$synced = 0;
$errors = 0;

$orders = Database::fetchAll(
    "SELECT id FROM orders
     WHERE provider_id IS NOT NULL AND provider_order_id IS NOT NULL AND provider_order_id != ''
       AND status IN ('pending', 'processing', 'in_progress')
     ORDER BY updated_at ASC LIMIT 80"
);

foreach ($orders as $o) {
    $res = Order::syncOrder((int)$o['id']);
    if ($res['success']) {
        $synced++;
    } else {
        $errors++;
    }
}

Logger::file('cron', 'INFO', "sync-orders completed: synced={$synced}, errors={$errors}");
flock($lockFp, LOCK_UN);
fclose($lockFp);
