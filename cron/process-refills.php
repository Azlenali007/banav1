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

$lockFp = fopen(TEMP_PATH . '/cron_process_refills.lock', 'c');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    exit(0);
}

$orders = Database::fetchAll(
    "SELECT * FROM orders WHERE refill_status = 'requested' AND provider_id IS NOT NULL AND provider_order_id IS NOT NULL LIMIT 30"
);

$submitted = 0;
foreach ($orders as $ord) {
    $provider = Provider::getById((int)$ord['provider_id']);
    if (!$provider || $provider['status'] !== 'active') {
        continue;
    }
    $res = Provider::refillOrder($provider, (string)$ord['provider_order_id']);
    if ($res['success']) {
        $refId = (string)($res['data']['refill'] ?? '');
        Database::execute("UPDATE orders SET refill_status = 'processing', refill_provider_id = ?, updated_at = NOW() WHERE id = ?", [$refId, $ord['id']]);
        Database::insert('INSERT INTO order_logs (order_id, actor_type, old_status, new_status, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())', [$ord['id'], 'cron', $ord['status'], $ord['status'], "Cron submitted refill to provider ({$refId})"]);
        $submitted++;
    }
}

Logger::file('cron', 'INFO', "process-refills completed: submitted={$submitted}");
flock($lockFp, LOCK_UN);
fclose($lockFp);
