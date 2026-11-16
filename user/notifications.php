<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    Notification::markAllRead($userId);
    Session::setFlash('success', 'All notifications marked as read.');
    Response::redirect('/user/notifications.php');
}

$items = Database::fetchAll(
    'SELECT * FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY id DESC LIMIT 50',
    [$userId]
);

Theme::render('user/notifications', [
    'items' => $items,
], 'user', [
    'pageTitle' => 'Notifications',
    'currentUser' => $currentUser,
]);
