<?php
declare(strict_types=1);
define('ADMIN_ALLOW_GUEST', true);
require_once __DIR__ . '/includes/auth.php';

if (Auth::currentAdmin()) {
    Response::redirect('/admin/dashboard.php');
}
Response::redirect('/admin/login.php');
