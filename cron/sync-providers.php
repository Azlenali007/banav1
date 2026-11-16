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

$lockFp = fopen(TEMP_PATH . '/cron_sync_providers.lock', 'c');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    exit(0);
}

$providers = Database::fetchAll("SELECT id FROM providers WHERE status = 'active'");
$ok = 0;
$fail = 0;
foreach ($providers as $p) {
    $res = Provider::syncBalance((int)$p['id']);
    if ($res['success']) {
        $ok++;
    } else {
        $fail++;
    }
}

Logger::file('cron', 'INFO', "sync-providers completed: ok={$ok}, fail={$fail}");
flock($lockFp, LOCK_UN);
fclose($lockFp);
