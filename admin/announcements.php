<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('website.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create') {
        $title = Validator::cleanString($_POST['title'] ?? '', 200);
        $content = trim((string)($_POST['content'] ?? ''));
        $type = in_array($_POST['type'] ?? '', ['info', 'warning', 'success', 'urgent'], true) ? $_POST['type'] : 'info';
        $active = !empty($_POST['is_active']) ? 1 : 0;

        if ($title !== '' && $content !== '') {
            $id = Database::insert(
                'INSERT INTO announcements (title, content, type, is_active, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
                [$title, $content, $type, $active, (int)$currentAdmin['id']]
            );
            if ($active && !empty($_POST['notify_users'])) {
                Notification::send(null, 'announcement', $title, $content, '/user/dashboard.php', false);
            }
            Logger::adminActivity((int)$currentAdmin['id'], 'announcement.create', 'announcement', $id, "Created announcement: {$title}");
            Session::setFlash('success', 'Announcement published.');
        } else {
            Session::setFlash('error', 'Title and content are required.');
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        Database::execute('UPDATE announcements SET is_active = 1 - is_active, updated_at = NOW() WHERE id = ?', [$id]);
        Session::setFlash('success', 'Announcement status toggled.');
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        Database::execute('DELETE FROM announcements WHERE id = ?', [$id]);
        Session::setFlash('success', 'Announcement deleted.');
    }
    Response::redirect('/admin/announcements.php');
}

$items = Database::fetchAll('SELECT * FROM announcements ORDER BY id DESC');

Theme::render('admin/announcements', [
    'items' => $items,
], 'admin', [
    'pageTitle' => 'Announcements',
    'currentAdmin' => $currentAdmin,
]);
