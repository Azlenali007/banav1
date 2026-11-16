<?php
declare(strict_types=1);
/** @var string $status @var string $q @var array $orders @var array $pg */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Order History</h1>
    <p class="text-sm text-slate-500">Track delivery progress, provider status, and refill eligibility.</p>
  </div>
  <a href="<?= e(app_url('/user/new-order.php')) ?>" class="btn-primary px-4 py-2 text-sm">+ New Order</a>
</div>

<!-- Status Tabs -->
<div class="flex flex-wrap gap-2 mb-4">
  <?php foreach (array_merge(['all'], ORDER_STATUSES) as $t): $active = $status === $t; ?>
    <a href="<?= e(app_url('/user/orders.php?status=' . urlencode($t) . ($q !== '' ? '&q=' . urlencode($q) : ''))) ?>"
       class="px-3.5 py-1.5 rounded-full text-xs font-semibold border transition <?= $active ? 'bg-violet-600 text-white border-violet-600' : 'bg-white text-slate-600 border-slate-200 hover:border-violet-300' ?>">
      <?= e(ucwords(str_replace('_', ' ', $t))) ?>
    </a>
  <?php endforeach; ?>
</div>

<form method="GET" class="smm-card p-4 mb-5 flex flex-col sm:flex-row gap-3">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search by Order UID, target link, or service..." class="smm-input flex-1">
  <button type="submit" class="btn-primary px-5 py-2 text-sm">Search</button>
</form>

<div class="smm-card overflow-hidden">
  <?php if (empty($orders)): ?>
    <div class="p-10 text-center text-sm text-slate-500">No orders found matching this filter.</div>
  <?php else: ?>
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Service</th>
            <th>Link</th>
            <th>Qty</th>
            <th>Charge</th>
            <th>Remains</th>
            <th>Status</th>
            <th class="text-right">Details</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $ord): ?>
            <tr>
              <td class="font-mono text-xs font-bold text-violet-700"><?= e($ord['order_uid']) ?></td>
              <td class="text-xs text-slate-500 whitespace-nowrap"><?= e(format_datetime($ord['created_at'])) ?></td>
              <td class="max-w-[200px] truncate font-medium"><?= e($ord['service_name']) ?></td>
              <td class="max-w-[160px] truncate text-xs text-slate-500"><a href="<?= e($ord['link']) ?>" target="_blank" rel="noopener noreferrer" class="hover:underline"><?= e($ord['link']) ?></a></td>
              <td><?= number_format((int)$ord['quantity']) ?></td>
              <td class="font-semibold"><?= e(format_money($ord['charge'], 2)) ?></td>
              <td class="text-xs"><?= number_format((int)$ord['remains']) ?></td>
              <td><span class="badge <?= e(status_badge_class((string)$ord['status'])) ?>"><?= e(str_replace('_', ' ', (string)$ord['status'])) ?></span></td>
              <td class="text-right">
                <a href="<?= e(app_url('/user/order-view.php?id=' . (int)$ord['id'])) ?>" class="btn-secondary px-3 py-1 text-xs">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php Theme::component('shared/pagination', ['pagination' => $pg, 'baseQuery' => ['status' => $status, 'q' => $q], 'itemLabel' => 'orders']); ?>
  <?php endif; ?>
</div>
