<?php
declare(strict_types=1);

/**
 * Security configuration.
 * Secrets are generated per installation in config/security.local.php by /install/.
 */
$localSecurity = [];
if (defined('SECURITY_LOCAL_CONFIG_FILE') && is_file(SECURITY_LOCAL_CONFIG_FILE)) {
    $loaded = require SECURITY_LOCAL_CONFIG_FILE;
    if (is_array($loaded)) {
        $localSecurity = $loaded;
    }
}

return [
    'app_key' => $localSecurity['app_key'] ?? (getenv('APP_KEY') ?: ''),
    'cron_token' => $localSecurity['cron_token'] ?? (getenv('CRON_TOKEN') ?: ''),
    'session_lifetime' => 7200, // 2 hours
    'session_name_user' => 'SMM_USER_SESS',
    'session_name_admin' => 'SMM_ADMIN_SESS',
    'csrf_ttl' => 7200,
    'login_max_attempts' => 5,
    'login_lockout_seconds' => 900, // 15 minutes
    'api_rate_limit_per_minute' => 120,
    'password_reset_ttl_minutes' => 60,
    'max_upload_size_bytes' => 2 * 1024 * 1024, // 2MB
    'allowed_upload_mimes' => [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ],
];
