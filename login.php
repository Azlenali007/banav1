<?php
declare(strict_types=1);

$bootstrapContext = 'user';
require_once __DIR__ . '/includes/bootstrap.php';
enforce_maintenance_mode();

if (Auth::currentUser()) {
    Response::redirect('/user/dashboard.php');
}

$error = '';
$identity = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $identity = (string)($_POST['identity'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $res = Auth::loginUser($identity, $password);
    if ($res['success']) {
        Response::redirect('/user/dashboard.php');
    }
    $error = (string)$res['error'];
}

Theme::render('public/login', [
    'error' => $error,
    'identity' => $identity,
], 'public', [
    'pageTitle' => 'Sign In',
]);
