<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $current = (string)($_POST['current_password'] ?? '');
    $newPass = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (!password_verify($current, (string)$currentUser['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPass) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($newPass !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        Database::execute('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', [password_hash($newPass, PASSWORD_DEFAULT), $userId]);
        Session::regenerate();
        Session::setFlash('success', 'Your password has been changed.');
        Response::redirect('/user/change-password.php');
    }
}

Theme::render('user/change-password', [
    'error' => $error,
], 'user', [
    'pageTitle' => 'Change Password',
    'currentUser' => $currentUser,
]);
