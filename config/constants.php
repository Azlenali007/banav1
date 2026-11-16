<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

define('APP_VERSION', '1.0.0');
define('APP_DEFAULT_NAME', 'ApexSMM');
define('DEFAULT_THEME', 'classic');
define('DEFAULT_CURRENCY', 'INR');
define('DEFAULT_CURRENCY_SYMBOL', '₹');

define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('MODULES_PATH', BASE_PATH . '/modules');
define('THEMES_PATH', BASE_PATH . '/themes');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('LOGS_PATH', STORAGE_PATH . '/logs');
define('BACKUPS_PATH', STORAGE_PATH . '/backups');
define('CACHE_PATH', STORAGE_PATH . '/cache');
define('UPLOADS_PATH', STORAGE_PATH . '/uploads');
define('TEMP_PATH', STORAGE_PATH . '/temp');
define('DATABASE_PATH', BASE_PATH . '/database');

define('INSTALL_LOCK_FILE', STORAGE_PATH . '/installed.lock');
define('DB_LOCAL_CONFIG_FILE', CONFIG_PATH . '/database.local.php');
define('SECURITY_LOCAL_CONFIG_FILE', CONFIG_PATH . '/security.local.php');

define('ORDER_STATUSES', [
    'pending',
    'processing',
    'in_progress',
    'completed',
    'partial',
    'cancelled',
    'failed',
]);

define('PAYMENT_STATUSES', [
    'pending',
    'completed',
    'rejected',
    'failed',
]);

define('TICKET_STATUSES', [
    'open',
    'answered',
    'closed',
]);
