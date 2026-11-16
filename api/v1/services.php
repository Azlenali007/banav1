<?php
declare(strict_types=1);

if (!defined('API_LIB_ONLY')) {
    define('API_LIB_ONLY', true);
    require_once dirname(__DIR__) . '/index.php';
}

$apiUser = api_authenticate_user();
$multiplier = (string)($apiUser['custom_rate_multiplier'] ?? '1.0000');

$rows = Database::fetchAll(
    "SELECT s.id, s.name, s.service_type, s.rate_per_1000, s.min_qty, s.max_qty, s.refill_supported, s.cancel_supported, s.dripfeed_supported, c.name AS category_name
     FROM services s
     INNER JOIN categories c ON c.id = s.category_id
     WHERE s.status = 'active' AND c.status = 'active'
     ORDER BY c.sort_order ASC, s.sort_order ASC, s.id ASC"
);

$out = [];
foreach ($rows as $r) {
    $out[] = [
        'service' => (int)$r['id'],
        'name' => $r['name'],
        'type' => $r['service_type'],
        'category' => $r['category_name'],
        'rate' => money_mul((string)$r['rate_per_1000'], $multiplier, 4),
        'min' => (int)$r['min_qty'],
        'max' => (int)$r['max_qty'],
        'refill' => (bool)$r['refill_supported'],
        'cancel' => (bool)$r['cancel_supported'],
        'dripfeed' => (bool)$r['dripfeed_supported'],
    ];
}

Response::json($out);
