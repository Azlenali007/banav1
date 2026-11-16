<?php
declare(strict_types=1);

$bootstrapContext = 'user';
require_once __DIR__ . '/includes/bootstrap.php';
enforce_maintenance_mode();

if (Auth::currentUser()) {
    Response::redirect('/user/dashboard.php');
}

$registrationEnabled = get_setting('registration_enabled', '1') === '1';
$error = '';
$fullName = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $username = (string)($_POST['username'] ?? '');
    $email = (string)($_POST['email'] ?? '');
    $fullName = (string)($_POST['full_name'] ?? '');

    if (!$registrationEnabled) {
        $error = 'New user registrations are currently disabled by the administrator.';
    } else {
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');

        if ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $res = Auth::registerUser($username, $email, $fullName, $password);
            if ($res['success']) {
                Session::setFlash('success', 'Account created successfully! Please sign in.');
                Response::redirect('/login.php');
            }
            $error = (string)$res['error'];
        }
    }
}

Theme::render('public/register', [
    'registrationEnabled' => $registrationEnabled,
    'error' => $error,
    'fullName' => $fullName,
    'username' => $username,
    'email' => $email,
], 'public', [
    'pageTitle' => 'Create Account',
]);
