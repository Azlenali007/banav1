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

if (get_setting('currency_auto_update', '1') !== '1') {
    echo "Currency auto-update is disabled.\n";
    exit(0);
}

$lockFp = fopen(TEMP_PATH . '/cron_sync_currency_rates.lock', 'c');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo "Currency rate sync already running.\n";
    exit(0);
}

$result = CurrencyRateProvider::updateAll();
echo $result['message'] . "\n";
if (!$result['success']) {
    fwrite(STDERR, $result['message'] . "\n");
}

flock($lockFp, LOCK_UN);
fclose($lockFp);
