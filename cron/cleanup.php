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

// 1. Clean expired reset tokens and old login rate-limit rows
Database::execute('DELETE FROM password_reset_tokens WHERE expires_at < NOW() OR used_at IS NOT NULL');
Database::execute('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 7 DAY)');
Database::execute('DELETE FROM error_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)');

// 2. Clean old temp files (> 24h, excluding .lock files currently used)
foreach (glob(TEMP_PATH . '/*') ?: [] as $f) {
    if (is_file($f) && !str_ends_with($f, '.lock') && basename($f) !== '.gitkeep' && (time() - filemtime($f)) > 86400) {
        @unlink($f);
    }
}

// 3. Clean old log files (> 30 days)
foreach (glob(LOGS_PATH . '/*.log') ?: [] as $lf) {
    if (is_file($lf) && (time() - filemtime($lf)) > 30 * 86400) {
        @unlink($lf);
    }
}

// 4. Enforce backup retention policy
$retentionDays = max(1, (int)get_setting('backup_retention_days', '14'));
foreach (glob(BACKUPS_PATH . '/backup-*.sql') ?: [] as $bf) {
    if (is_file($bf) && (time() - filemtime($bf)) > $retentionDays * 86400) {
        @unlink($bf);
    }
}

Logger::file('cron', 'INFO', 'cleanup completed');
