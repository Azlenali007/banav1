<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
$newlyGeneratedKey = Session::get('new_api_key_once');
Session::remove('new_api_key_once');

$apiRecord = Database::fetch('SELECT * FROM api_keys WHERE user_id = ? LIMIT 1', [$userId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if (empty($currentUser['api_enabled'])) {
        Session::setFlash('error', 'API access is disabled for your account by the administrator.');
        Response::redirect('/user/api-access.php');
    }

    if ($action === 'generate') {
        $rawKey = 'smm_' . bin2hex(random_bytes(24));
        $hash = hash('sha256', $rawKey);
        $prefix = substr($rawKey, 0, 12) . '...';
        $enc = crypto_encrypt($rawKey);

        Database::execute(
            "INSERT INTO api_keys (user_id, api_key_hash, api_key_prefix, api_key_encrypted, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'active', NOW(), NOW())
             ON DUPLICATE KEY UPDATE api_key_hash = VALUES(api_key_hash), api_key_prefix = VALUES(api_key_prefix), api_key_encrypted = VALUES(api_key_encrypted), status = 'active', updated_at = NOW()",
            [$userId, $hash, $prefix, $enc]
        );
        Session::set('new_api_key_once', $rawKey);
        Session::setFlash('success', 'New API key generated. Copy and store it securely now.');
    } elseif ($action === 'revoke' && $apiRecord) {
        Database::execute("UPDATE api_keys SET status = 'revoked', updated_at = NOW() WHERE user_id = ?", [$userId]);
        Session::setFlash('success', 'API key revoked.');
    }
    Response::redirect('/user/api-access.php');
}

Theme::render('user/api-access', [
    'currentUser' => $currentUser,
    'apiRecord' => $apiRecord,
    'newlyGeneratedKey' => $newlyGeneratedKey,
    'endpointUrl' => app_url('/api/v1/'),
], 'user', [
    'pageTitle' => 'API Access & Documentation',
    'currentUser' => $currentUser,
]);
