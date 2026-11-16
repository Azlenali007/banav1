<?php
declare(strict_types=1);
/** @var array $currentUser @var array $stats @var array $recentOrders @var array $recentTx @var array $announcements */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Welcome, <?= e($currentUser['full_name']) ?></h1>
    <p class="text-sm text-slate-500">Live overview of your wallet balance, orders, and account activity.</p>
  </div>
  <div class="flex flex-wrap gap-2.5">
    <a href="<?= e(app_url('/user/add-funds.php')) ?>" class="btn-secondary px-4 py-2 text-sm">+ Add Funds</a>
    <a href="<?= e(app_url('/user/new-order.php')) ?>" class="btn-primary px-4 py-2 text-sm">+ New Order</a>
  </div>
</div>

<?php Theme::component('shared/notifications', ['announcements' => $announcements]); ?>

<!-- KPI Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-7">
  <div class="smm-card p-5 user-kpi-gradient border-0">
    <div class="text-xs font-medium text-violet-100">Wallet Balance</div>
    <div class="text-2xl font-extrabold mt-1"><?= e(format_money($currentUser['wallet_balance'] ?? 0)) ?></div>
    <div class="text-[11px] text-violet-200 mt-2">Total Spent: <?= e(format_money($currentUser['total_spent'] ?? 0)) ?></div>
  </div>
  <div class="smm-card p-5">
    <div class="text-xs font-medium text-slate-500">Total Orders</div>
    <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$stats['total'] ?></div>
    <div class="text-[11px] text-slate-400 mt-2">Completed: <?= (int)$stats['completed'] ?></div>
  </div>
  <div class="smm-card p-5">
    <div class="text-xs font-medium text-slate-500">Active / In Progress</div>
    <div class="text-2xl font-extrabold text-violet-700 mt-1"><?= (int)($stats['pending'] + $stats['processing'] + $stats['in_progress']) ?></div>
    <div class="text-[11px] text-slate-400 mt-2">Pending: <?= (int)$stats['pending'] ?> &bull; Processing: <?= (int)$stats['processing'] ?></div>
  </div>
  <div class="smm-card p-5">
    <div class="text-xs font-medium text-slate-500">Partial / Cancelled</div>
    <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)($stats['partial'] + $stats['cancelled']) ?></div>
    <div class="text-[11px] text-slate-400 mt-2">Partial: <?= (int)$stats['partial'] ?> &bull; Cancelled: <?= (int)$stats['cancelled'] ?></div>
  </div>
</div>

<!-- Quick Action Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-7">
  <a href="<?= e(app_url('/user/new-order.php')) ?>" class="smm-card smm-card-hover p-4 flex items-center gap-3.5">
    <div class="w-10 h-10 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center shrink-0">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    </div>
    <div>
      <div class="font-bold text-sm text-slate-900">Place Single Order</div>
      <div class="text-xs text-slate-500">Choose category, service &amp; quantity</div>
    </div>
  </a>
  <a href="<?= e(app_url('/user/bulk-order.php')) ?>" class="smm-card smm-card-hover p-4 flex items-center gap-3.5">
    <div class="w-10 h-10 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center shrink-0">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
    </div>
    <div>
      <div class="font-bold text-sm text-slate-900">Mass / Bulk Order</div>
      <div class="text-xs text-slate-500">Submit multiple orders at once</div>
    </div>
  </a>
  <a href="<?= e(app_url('/user/tickets.php')) ?>" class="smm-card smm-card-hover p-4 flex items-center gap-3.5">
    <div class="w-10 h-10 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center shrink-0">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
    </div>
    <div>
      <div class="font-bold text-sm text-slate-900">Support Desk</div>
      <div class="text-xs text-slate-500">Need help? Open a ticket</div>
    </div>
  </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  <!-- Recent Orders -->
  <div class="xl:col-span-2 smm-card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
      <h2 class="font-bold text-slate-900">Recent Orders</h2>
      <a href="<?= e(app_url('/user/orders.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">View All</a>
    </div>
    <?php if (empty($recentOrders)): ?>
      <div class="p-8 text-center text-sm text-slate-500">You have not placed any orders yet.</div>
    <?php else: ?>
      <div class="smm-table-wrap">
        <table class="smm-table">
          <thead>
            <tr>
              <th>Order</th>
              <th>Service</th>
              <th>Qty</th>
              <th>Charge</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $ord): ?>
              <tr>
                <td>
                  <a href="<?= e(app_url('/user/order-view.php?id=' . (int)$ord['id'])) ?>" class="font-semibold text-violet-700 hover:underline"><?= e($ord['order_uid']) ?></a>
                </td>
                <td class="max-w-[200px] truncate"><?= e($ord['service_name']) ?></td>
                <td><?= number_format((int)$ord['quantity']) ?></td>
                <td class="font-semibold"><?= e(format_money($ord['charge'], 2)) ?></td>
                <td><span class="badge <?= e(status_badge_class((string)$ord['status'])) ?>"><?= e(str_replace('_', ' ', (string)$ord['status'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Recent Wallet Transactions -->
  <div class="smm-card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
      <h2 class="font-bold text-slate-900">Recent Transactions</h2>
      <a href="<?= e(app_url('/user/transactions.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">Ledger</a>
    </div>
    <?php if (empty($recentTx)): ?>
      <div class="p-8 text-center text-sm text-slate-500">No wallet transactions recorded yet.</div>
    <?php else: ?>
      <div class="divide-y divide-slate-100">
        <?php foreach ($recentTx as $tx): ?>
          <?php $isCredit = in_array($tx['type'], ['credit', 'refund'], true) || ($tx['type'] === 'adjustment' && money_cmp((string)$tx['balance_after'], (string)$tx['balance_before']) >= 0); ?>
          <div class="px-5 py-3.5 flex items-center justify-between gap-3">
            <div class="min-w-0">
              <div class="text-xs font-semibold text-slate-800 truncate"><?= e($tx['description']) ?></div>
              <div class="text-[11px] text-slate-400"><?= e(format_datetime($tx['created_at'])) ?></div>
            </div>
            <div class="text-right shrink-0">
              <div class="text-xs font-bold <?= $isCredit ? 'text-emerald-600' : 'text-red-600' ?>">
                <?= $isCredit ? '+' : '-' ?><?= e(format_money($tx['amount'], 2)) ?>
              </div>
              <div class="text-[10px] text-slate-400">Bal: <?= e(format_money($tx['balance_after'], 2)) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
