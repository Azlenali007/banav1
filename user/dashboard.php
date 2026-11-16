<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
$stats = OrderModule::userStats($userId);
$recentOrders = Database::fetchAll(
    'SELECT o.*, s.name AS service_name
     FROM orders o
     INNER JOIN services s ON s.id = o.service_id
     WHERE o.user_id = ?
     ORDER BY o.id DESC LIMIT 6',
    [$userId]
);
$recentTx = WalletModule::recentUserTransactions($userId, 6);
$announcements = active_announcements();

Theme::render('user/dashboard', [
    'currentUser' => $currentUser,
    'stats' => $stats,
    'recentOrders' => $recentOrders,
    'recentTx' => $recentTx,
    'announcements' => $announcements,
], 'user', [
    'pageTitle' => 'Dashboard',
    'currentUser' => $currentUser,
]);
