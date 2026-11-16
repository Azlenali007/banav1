<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('tickets.view');

$statusFilter = strtolower(trim((string)($_GET['status'] ?? 'all')));
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];
if ($statusFilter !== 'all' && in_array($statusFilter, TICKET_STATUSES, true)) {
    $where[] = 't.status = ?';
    $params[] = $statusFilter;
}
if ($q !== '') {
    $where[] = '(t.ticket_uid LIKE ? OR t.subject LIKE ? OR u.username LIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
$whereSql = implode(' AND ', $where);

$total = (int)Database::fetchColumn("SELECT COUNT(*) FROM support_tickets t INNER JOIN users u ON u.id = t.user_id WHERE {$whereSql}", $params);
$pg = paginate_build($total, $page, 25);

$tickets = Database::fetchAll(
    "SELECT t.*, u.username FROM support_tickets t INNER JOIN users u ON u.id = t.user_id WHERE {$whereSql} ORDER BY t.last_reply_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
    $params
);

Theme::render('admin/tickets', [
    'statusFilter' => $statusFilter,
    'q' => $q,
    'tickets' => $tickets,
    'pg' => $pg,
], 'admin', [
    'pageTitle' => 'Support Tickets',
    'currentAdmin' => $currentAdmin,
]);
