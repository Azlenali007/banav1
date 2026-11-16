<?php
declare(strict_types=1);

$bootstrapContext = 'user';
require_once __DIR__ . '/includes/bootstrap.php';
enforce_maintenance_mode();

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $pass = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['password_confirm'] ?? '');
    if ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $res = Auth::resetPasswordWithToken($token, $pass);
        if ($res['success']) {
            Session::setFlash('success', 'Your password has been updated. You can now sign in.');
            Response::redirect('/login.php');
        }
        $error = (string)$res['error'];
    }
}

Theme::render('public/reset-password', [
    'token' => $token,
    'error' => $error,
], 'public', [
    'pageTitle' => 'Reset Password',
]);
