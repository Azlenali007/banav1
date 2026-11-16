<?php
declare(strict_types=1);

if (!defined('API_LIB_ONLY')) {
    define('API_LIB_ONLY', true);
    require_once dirname(__DIR__) . '/index.php';
}

$apiUser = api_authenticate_user();
$wallet = Wallet::ensureWallet((int)$apiUser['id']);

Response::json([
    'balance' => money_format_dec((string)$wallet['balance'], 4),
    'currency' => (string)$wallet['currency'],
]);
