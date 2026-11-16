<?php
declare(strict_types=1);

/**
 * Database configuration loader.
 * Never hardcodes live credentials. The installer writes config/database.local.php.
 */
if (defined('DB_LOCAL_CONFIG_FILE') && is_file(DB_LOCAL_CONFIG_FILE)) {
    $localDb = require DB_LOCAL_CONFIG_FILE;
    if (is_array($localDb)) {
        return array_merge([
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => '',
            'username' => '',
            'password' => '',
            'charset' => 'utf8mb4',
            'installed' => true,
        ], $localDb);
    }
}

return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int)(getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_NAME') ?: '',
    'username' => getenv('DB_USER') ?: '',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
    'installed' => false,
];
