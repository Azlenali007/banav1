<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('payments.view');

$q = trim((string)($_GET['q'] ?? ''));
$typeFilter = trim((string)($_GET['type'] ?? 'all'));
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];
if ($typeFilter !== 'all' && in_array($typeFilter, ['credit', 'debit', 'refund', 'adjustment'], true)) {
    $where[] = 'wt.type = ?';
    $params[] = $typeFilter;
}
if ($q !== '') {
    $where[] = '(u.username LIKE ? OR wt.description LIKE ? OR wt.reference_id LIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
$whereSql = implode(' AND ', $where);

$total = (int)Database::fetchColumn("SELECT COUNT(*) FROM wallet_transactions wt INNER JOIN users u ON u.id = wt.user_id WHERE {$whereSql}", $params);
$pg = paginate_build($total, $page, 25);

$rows = Database::fetchAll(
    "SELECT wt.*, u.username FROM wallet_transactions wt INNER JOIN users u ON u.id = wt.user_id WHERE {$whereSql} ORDER BY wt.id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
    $params
);

Theme::render('admin/transactions', [
    'q' => $q,
    'typeFilter' => $typeFilter,
    'rows' => $rows,
    'pg' => $pg,
], 'admin', [
    'pageTitle' => 'Transactions Ledger',
    'currentAdmin' => $currentAdmin,
]);
