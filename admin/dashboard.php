<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('dashboard.view');

$userCounts = UserModule::summaryCounts();
$orderStats = OrderModule::adminStats();
$walletTotals = WalletModule::totals();
$totalDeposits = PaymentModule::totalCompletedDeposits();
$pendingManual = PaymentModule::pendingManualCount();
$openTickets = (int)Database::fetchColumn("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open','answered')");
$revenueSeries = ReportModule::revenueSeries(14);

$recentOrders = Database::fetchAll(
    'SELECT o.*, u.username, s.name AS service_name
     FROM orders o
     INNER JOIN users u ON u.id = o.user_id
     INNER JOIN services s ON s.id = o.service_id
     ORDER BY o.id DESC LIMIT 8'
);
$recentUsers = Database::fetchAll('SELECT id, username, email, status, created_at FROM users ORDER BY id DESC LIMIT 6');

$maxRev = 1.0;
foreach ($revenueSeries as $pt) {
    if ((float)$pt['revenue'] > $maxRev) {
        $maxRev = (float)$pt['revenue'];
    }
}

Theme::render('admin/dashboard', [
    'userCounts' => $userCounts,
    'orderStats' => $orderStats,
    'walletTotals' => $walletTotals,
    'totalDeposits' => $totalDeposits,
    'pendingManual' => $pendingManual,
    'openTickets' => $openTickets,
    'revenueSeries' => $revenueSeries,
    'recentOrders' => $recentOrders,
    'recentUsers' => $recentUsers,
    'maxRev' => $maxRev,
], 'admin', [
    'pageTitle' => 'Dashboard',
    'currentAdmin' => $currentAdmin,
]);
