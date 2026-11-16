<?php
declare(strict_types=1);

define('API_LIB_ONLY', true);
require_once dirname(__DIR__) . '/index.php';

$in = api_get_input();
$action = strtolower(trim((string)($in['action'] ?? '')));

switch ($action) {
    case 'balance':
        require __DIR__ . '/balance.php';
        break;
    case 'services':
        require __DIR__ . '/services.php';
        break;
    case 'add':
        require __DIR__ . '/add-order.php';
        break;
    case 'status':
        require __DIR__ . '/order-status.php';
        break;
    case 'refill':
        require __DIR__ . '/refill.php';
        break;
    default:
        Response::json(['error' => 'Invalid or missing API action. Supported: balance, services, add, status, refill.'], 400);
}
