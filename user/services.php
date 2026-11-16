<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$q = trim((string)($_GET['q'] ?? ''));
$catFilter = (int)($_GET['category'] ?? 0);

$categories = Database::fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY sort_order ASC, id ASC");

$sql = "SELECT s.*, c.name AS category_name
        FROM services s
        INNER JOIN categories c ON c.id = s.category_id
        WHERE s.status = 'active' AND c.status = 'active'";
$params = [];

if ($catFilter > 0) {
    $sql .= " AND s.category_id = ?";
    $params[] = $catFilter;
}
if ($q !== '') {
    $sql .= " AND (s.name LIKE ? OR s.description LIKE ? OR s.id = ?)";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = ctype_digit($q) ? (int)$q : 0;
}
$sql .= " ORDER BY c.sort_order ASC, s.sort_order ASC, s.id ASC";
$services = Database::fetchAll($sql, $params);

$grouped = [];
foreach ($services as $svc) {
    $grouped[$svc['category_name']][] = $svc;
}

$multiplier = (string)($currentUser['custom_rate_multiplier'] ?? '1.0000');

Theme::render('user/services', [
    'q' => $q,
    'catFilter' => $catFilter,
    'categories' => $categories,
    'grouped' => $grouped,
    'multiplier' => $multiplier,
], 'user', [
    'pageTitle' => 'Services Catalog',
    'currentUser' => $currentUser,
]);
