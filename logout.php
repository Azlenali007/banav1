<?php
declare(strict_types=1);

$bootstrapContext = 'user';
require_once __DIR__ . '/includes/bootstrap.php';

Auth::logoutUser();
Response::redirect('/login.php');
