<?php
declare(strict_types=1);
/** @var array $userCounts @var array $orderStats @var array $walletTotals @var string $totalDeposits */
/** @var int $pendingManual @var int $openTickets @var array $revenueSeries @var array $recentOrders @var array $recentUsers @var float $maxRev */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Admin Dashboard</h1>
    <p class="text-sm text-slate-500">Live platform metrics computed directly from MySQL records.</p>
  </div>
  <div class="flex flex-wrap gap-2">
    <?php if ($pendingManual > 0 && admin_can('payments.manage')): ?>
      <a href="<?= e(app_url('/admin/payments.php?status=pending')) ?>" class="px-4 py-2 rounded-xl bg-amber-100 text-amber-900 text-xs font-bold"><?= (int)$pendingManual ?> Pending Manual Deposits</a>
    <?php endif; ?>
    <?php if ($openTickets > 0 && admin_can('tickets.view')): ?>
      <a href="<?= e(app_url('/admin/tickets.php?status=open')) ?>" class="px-4 py-2 rounded-xl bg-violet-100 text-violet-900 text-xs font-bold"><?= (int)$openTickets ?> Open Tickets</a>
    <?php endif; ?>
  </div>
</div>

<!-- Top KPI Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="smm-card p-5 bg-gradient-to-br from-violet-600 to-purple-800 text-white border-0">
    <div class="text-xs text-violet-100">Total Revenue (Net)</div>
    <div class="text-2xl font-extrabold mt-1"><?= e(format_money($orderStats['net_revenue'] ?? 0, 2)) ?></div>
    <div class="text-[11px] text-violet-200 mt-2">Today: <?= e(format_money($orderStats['today_revenue'] ?? 0, 2)) ?></div>
  </div>
  <div class="smm-card p-5">
    <div class="text-xs text-slate-500">Total Completed Deposits</div>
    <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= e(format_money($totalDeposits, 2)) ?></div>
    <div class="text-[11px] text-slate-400 mt-2">User Wallets Held: <?= e(format_money($walletTotals['total_balance'] ?? 0, 2)) ?></div>
  </div>
  <div class="smm-card p-5">
    <div class="text-xs text-slate-500">Total Orders</div>
    <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)($orderStats['total'] ?? 0) ?></div>
    <div class="text-[11px] text-slate-400 mt-2">
      Pending: <?= (int)($orderStats['pending'] ?? 0) ?> &bull; Processing: <?= (int)($orderStats['processing'] ?? 0) + (int)($orderStats['in_progress'] ?? 0) ?>
    </div>
  </div>
  <div class="smm-card p-5">
    <div class="text-xs text-slate-500">Registered Users</div>
    <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$userCounts['total_users'] ?></div>
    <div class="text-[11px] text-slate-400 mt-2">Active: <?= (int)$userCounts['active_users'] ?> &bull; Suspended: <?= (int)$userCounts['suspended_users'] ?></div>
  </div>
</div>

<!-- Order Breakdown & 14-Day Real Revenue Chart -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
  <div class="lg:col-span-2 smm-card p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="font-bold text-slate-900">14-Day Revenue &amp; Orders</h2>
        <p class="text-xs text-slate-500">Actual daily net order revenue from MySQL</p>
      </div>
      <a href="<?= e(app_url('/admin/reports.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">Full Reports &rarr;</a>
    </div>
    <?php if (empty($revenueSeries)): ?>
      <div class="h-44 flex items-center justify-center text-sm text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
        No non-cancelled order revenue recorded in the last 14 days yet.
      </div>
    <?php else: ?>
      <div class="h-48 flex items-end gap-2 pt-4">
        <?php foreach ($revenueSeries as $pt): ?>
          <?php $pct = max(6, (int)round(((float)$pt['revenue'] / $maxRev) * 100)); ?>
          <div class="flex-1 flex flex-col items-center gap-1.5 group">
            <div class="text-[10px] font-semibold text-violet-800 opacity-0 group-hover:opacity-100 transition"><?= e(format_money($pt['revenue'], 0)) ?></div>
            <div class="w-full max-w-[36px] bg-gradient-to-t from-violet-700 to-purple-400 rounded-t-lg transition-all" style="height: <?= $pct ?>%;" title="<?= e($pt['day']) ?>: <?= e(format_money($pt['revenue'], 2)) ?> (<?= (int)$pt['order_count'] ?> orders)"></div>
            <div class="text-[10px] text-slate-400 truncate max-w-full"><?= e(substr((string)$pt['day'], 5)) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="smm-card p-6 space-y-3">
    <h2 class="font-bold text-slate-900 mb-2">Order Status Summary</h2>
    <?php
    $statusSummary = [
        ['Pending', (int)($orderStats['pending'] ?? 0), 'badge-warning'],
        ['Processing', (int)($orderStats['processing'] ?? 0), 'badge-purple'],
        ['In Progress', (int)($orderStats['in_progress'] ?? 0), 'badge-purple'],
        ['Completed', (int)($orderStats['completed'] ?? 0), 'badge-success'],
        ['Partial', (int)($orderStats['partial'] ?? 0), 'badge-info'],
        ['Cancelled', (int)($orderStats['cancelled'] ?? 0), 'badge-danger'],
        ['Failed', (int)($orderStats['failed'] ?? 0), 'badge-danger'],
    ];
    foreach ($statusSummary as [$label, $cnt, $cls]):
    ?>
      <div class="flex items-center justify-between text-sm py-1 border-b border-slate-100 last:border-0">
        <span class="badge <?= $cls ?>"><?= e($label) ?></span>
        <span class="font-bold text-slate-800"><?= $cnt ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Recent Orders & Users -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  <div class="xl:col-span-2 smm-card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
      <h2 class="font-bold text-slate-900">Recent Orders</h2>
      <a href="<?= e(app_url('/admin/orders.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">Manage Orders</a>
    </div>
    <?php if (empty($recentOrders)): ?>
      <div class="p-8 text-center text-sm text-slate-500">No orders placed yet.</div>
    <?php else: ?>
      <div class="smm-table-wrap">
        <table class="smm-table">
          <thead>
            <tr>
              <th>Order</th>
              <th>User</th>
              <th>Service</th>
              <th>Charge</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $ord): ?>
              <tr>
                <td><a href="<?= e(app_url('/admin/order-view.php?id=' . (int)$ord['id'])) ?>" class="font-mono text-xs font-bold text-violet-700 hover:underline"><?= e($ord['order_uid']) ?></a></td>
                <td class="text-xs font-medium"><?= e($ord['username']) ?></td>
                <td class="max-w-[180px] truncate text-xs"><?= e($ord['service_name']) ?></td>
                <td class="font-semibold text-xs"><?= e(format_money($ord['charge'], 2)) ?></td>
                <td><span class="badge <?= e(status_badge_class((string)$ord['status'])) ?>"><?= e(str_replace('_', ' ', (string)$ord['status'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="smm-card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
      <h2 class="font-bold text-slate-900">Recent Users</h2>
      <a href="<?= e(app_url('/admin/users.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">All Users</a>
    </div>
    <?php if (empty($recentUsers)): ?>
      <div class="p-8 text-center text-sm text-slate-500">No registered users yet.</div>
    <?php else: ?>
      <div class="divide-y divide-slate-100">
        <?php foreach ($recentUsers as $ru): ?>
          <a href="<?= e(app_url('/admin/user-view.php?id=' . (int)$ru['id'])) ?>" class="px-5 py-3 flex items-center justify-between hover:bg-violet-50/40 transition">
            <div>
              <div class="text-sm font-semibold text-slate-900"><?= e($ru['username']) ?></div>
              <div class="text-xs text-slate-400"><?= e($ru['email']) ?></div>
            </div>
            <span class="badge <?= e(status_badge_class((string)$ru['status'])) ?>"><?= e($ru['status']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
