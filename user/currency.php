<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::redirect('/user/dashboard.php');
}

CSRF::verifyOrAbort();
$code = strtoupper(Validator::cleanString($_POST['currency'] ?? '', 10));
$res = Currency::setUserPreferred((int)$currentUser['id'], $code !== '' ? $code : null);

if (!$res['success']) {
    Session::setFlash('error', (string)$res['error']);
} else {
    Session::setFlash('success', 'Display currency changed to ' . $res['currency'] . '.');
}

$redirect = (string)($_POST['redirect'] ?? '/user/dashboard.php');
if (!preg_match('#^/user/[A-Za-z0-9_\-./?=&%]*$#', $redirect)) {
    $redirect = '/user/dashboard.php';
}
Response::redirect($redirect);
