<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('users.view');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_permission('users.manage');
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create_user') {
        $res = Auth::registerUser(
            (string)($_POST['username'] ?? ''),
            (string)($_POST['email'] ?? ''),
            (string)($_POST['full_name'] ?? ''),
            (string)($_POST['password'] ?? '')
        );
        if ($res['success']) {
            Logger::adminActivity((int)$currentAdmin['id'], 'user.create', 'user', (int)$res['user_id'], 'Created user ' . ($_POST['username'] ?? ''));
            Session::setFlash('success', 'User created successfully.');
        } else {
            Session::setFlash('error', (string)$res['error']);
        }
    } elseif ($action === 'toggle_status') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $u = Database::fetch('SELECT id, username, status FROM users WHERE id = ? LIMIT 1', [$uid]);
        if ($u) {
            $newStatus = $u['status'] === 'active' ? 'suspended' : 'active';
            Database::execute('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?', [$newStatus, $uid]);
            Logger::adminActivity((int)$currentAdmin['id'], 'user.status', 'user', $uid, "Changed status of {$u['username']} to {$newStatus}");
            Session::setFlash('success', "User {$u['username']} is now {$newStatus}.");
        }
    }
    Response::redirect('/admin/users.php');
}

$q = trim((string)($_GET['q'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? 'all'));
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];
if ($statusFilter !== 'all' && in_array($statusFilter, ['active', 'suspended', 'pending'], true)) {
    $where[] = 'u.status = ?';
    $params[] = $statusFilter;
}
if ($q !== '') {
    $where[] = '(u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
$whereSql = implode(' AND ', $where);

$total = (int)Database::fetchColumn("SELECT COUNT(*) FROM users u WHERE {$whereSql}", $params);
$pg = paginate_build($total, $page, 20);

$users = Database::fetchAll(
    "SELECT u.*, COALESCE(w.balance, 0) AS balance,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count
     FROM users u
     LEFT JOIN wallets w ON w.user_id = u.id
     WHERE {$whereSql}
     ORDER BY u.id DESC
     LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
    $params
);

Theme::render('admin/users', [
    'q' => $q,
    'statusFilter' => $statusFilter,
    'users' => $users,
    'pg' => $pg,
], 'admin', [
    'pageTitle' => 'Users',
    'currentAdmin' => $currentAdmin,
]);
