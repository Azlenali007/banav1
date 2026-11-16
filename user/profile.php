<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $fullName = Validator::cleanString($_POST['full_name'] ?? '', 120);
    if (mb_strlen($fullName) < 2) {
        Session::setFlash('error', 'Full name must be at least 2 characters.');
    } else {
        Database::execute('UPDATE users SET full_name = ?, updated_at = NOW() WHERE id = ?', [$fullName, $userId]);
        Session::setFlash('success', 'Profile updated successfully.');
    }
    Response::redirect('/user/profile.php');
}

Theme::render('user/profile', [
    'currentUser' => $currentUser,
], 'user', [
    'pageTitle' => 'My Profile',
    'currentUser' => $currentUser,
]);
