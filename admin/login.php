<?php
declare(strict_types=1);
define('ADMIN_ALLOW_GUEST', true);
require_once __DIR__ . '/includes/auth.php';

if (Auth::currentAdmin()) {
    Response::redirect('/admin/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $res = Auth::loginAdmin((string)($_POST['identity'] ?? ''), (string)($_POST['password'] ?? ''));
    if ($res['success']) {
        Response::redirect('/admin/dashboard.php');
    }
    $error = (string)$res['error'];
}

Theme::render('admin/login', [
    'siteName' => get_setting('site_name', APP_DEFAULT_NAME),
    'error' => $error,
    'identity' => (string)($_POST['identity'] ?? ''),
]);
