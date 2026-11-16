<?php
declare(strict_types=1);
define('ADMIN_ALLOW_GUEST', true);
require_once __DIR__ . '/includes/auth.php';

Auth::logoutAdmin();
Response::redirect('/admin/login.php');
