<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
$code = Referral::ensureCode($userId);
$stats = Referral::stats($userId);
$referred = Referral::referredUsers($userId, 100);
$referralLink = Referral::link($code);

Theme::render('user/refer-earn', [
    'currentUser' => $currentUser,
    'code' => $code,
    'stats' => $stats,
    'referred' => $referred,
    'referralLink' => $referralLink,
    'enabled' => Referral::isEnabled(),
], 'user', [
    'pageTitle' => 'Refer & Earn',
    'currentUser' => $currentUser,
]);
