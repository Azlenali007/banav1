<?php
declare(strict_types=1);

$bootstrapContext = 'api';
require_once dirname(__DIR__) . '/includes/bootstrap.php';

function api_get_input(): array
{
    static $input = null;
    if ($input !== null) {
        return $input;
    }
    $input = $_POST;
    $raw = file_get_contents('php://input');
    if (is_string($raw) && $raw !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = array_merge($input, $json);
        }
    }
    return $input;
}

function api_authenticate_user(): array
{
    $in = api_get_input();
    $rawKey = trim((string)($in['key'] ?? ($_SERVER['HTTP_X_API_KEY'] ?? '')));
    if ($rawKey === '') {
        Response::json(['error' => 'API key is required.'], 401);
    }

    // Rate limit per IP / key prefix
    $sec = require CONFIG_PATH . '/security.php';
    $limitPerMin = (int)($sec['api_rate_limit_per_minute'] ?? 120);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $recentCount = (int)Database::fetchColumn(
        "SELECT COUNT(*) FROM login_attempts WHERE scope = 'api' AND ip_address = ? AND attempted_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)",
        [$ip]
    );
    if ($recentCount >= $limitPerMin) {
        Response::json(['error' => 'API rate limit exceeded. Please slow down.'], 429);
    }
    Database::insert("INSERT INTO login_attempts (scope, identifier, ip_address, attempted_at) VALUES ('api', ?, ?, NOW())", [substr($rawKey, 0, 16), $ip]);

    $hash = hash('sha256', $rawKey);
    $row = Database::fetch(
        "SELECT u.*, ak.id AS api_key_id, ak.status AS key_status
         FROM api_keys ak
         INNER JOIN users u ON u.id = ak.user_id
         WHERE ak.api_key_hash = ? LIMIT 1",
        [$hash]
    );

    if (!$row || $row['key_status'] !== 'active') {
        Response::json(['error' => 'Invalid or revoked API key.'], 401);
    }
    if ($row['status'] !== 'active' || empty($row['api_enabled'])) {
        Response::json(['error' => 'API access is disabled for this account.'], 403);
    }

    Database::execute('UPDATE api_keys SET last_used_at = NOW() WHERE id = ?', [$row['api_key_id']]);
    return $row;
}

if (!defined('API_LIB_ONLY')) {
    Response::json([
        'name' => get_setting('site_name', APP_DEFAULT_NAME) . ' API',
        'version' => 'v1',
        'endpoint' => app_url('/api/v1/'),
        'actions' => ['balance', 'services', 'add', 'status', 'refill'],
    ]);
}
