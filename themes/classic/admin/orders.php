<?php
declare(strict_types=1);
/** @var string $status @var string $q @var array $orders @var array $pg */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Order Management</h1>
  <p class="text-sm text-slate-500">Monitor, filter, synchronize, and manage orders across all users and providers.</p>
</div>

<div class="flex flex-wrap gap-2 mb-4">
  <?php foreach (array_merge(['all'], ORDER_STATUSES) as $t): ?>
    <a href="?status=<?= e($t) ?>&q=<?= e(urlencode($q)) ?>" class="px-3.5 py-1.5 rounded-full text-xs font-semibold border <?= $status === $t ? 'bg-violet-600 text-white border-violet-600' : 'bg-white text-slate-600 border-slate-200' ?>">
      <?= e(ucwords(str_replace('_', ' ', $t))) ?>
    </a>
  <?php endforeach; ?>
</div>

<form method="GET" class="smm-card p-4 mb-5 flex flex-col sm:flex-row gap-3">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search Order ID, UID, Provider Order ID, Username, or Link..." class="smm-input flex-1">
  <button type="submit" class="btn-primary px-5 py-2 text-sm">Search</button>
</form>

<form method="POST" class="smm-card overflow-hidden">
  <?= CSRF::field() ?>
  <input type="hidden" name="action" value="bulk_status">
  <?php if (admin_can('orders.manage')): ?>
    <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center gap-3 text-xs">
      <span class="font-semibold text-slate-600">Bulk Action:</span>
      <select name="bulk_status" class="border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white">
        <?php foreach (ORDER_STATUSES as $st): ?>
          <option value="<?= e($st) ?>"><?= e(ucwords(str_replace('_', ' ', $st))) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="bulk_reason" placeholder="Audit reason..." class="border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white">
      <button type="submit" class="btn-secondary px-3 py-1.5 text-xs">Apply to Selected</button>
    </div>
  <?php endif; ?>

  <div class="smm-table-wrap">
    <table class="smm-table">
      <thead>
        <tr>
          <th class="w-8"><input type="checkbox" onclick="SMMAdmin.toggleCheckboxes(this, '.ord-chk')"></th>
          <th>Order</th>
          <th>User</th>
          <th>Service</th>
          <th>Provider</th>
          <th>Qty / Rem</th>
          <th>Charge</th>
          <th>Status</th>
          <th class="text-right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="9" class="text-center text-slate-500 py-8">No orders found.</td></tr>
        <?php else: foreach ($orders as $o): ?>
          <tr>
            <td><input type="checkbox" name="order_ids[]" value="<?= (int)$o['id'] ?>" class="ord-chk"></td>
            <td>
              <div class="font-mono text-xs font-bold text-violet-700"><?= e($o['order_uid']) ?></div>
              <div class="text-[11px] text-slate-400">#<?= (int)$o['id'] ?> &bull; <?= e(format_datetime($o['created_at'])) ?></div>
            </td>
            <td><a href="<?= e(app_url('/admin/user-view.php?id=' . (int)$o['user_id'])) ?>" class="text-xs font-semibold hover:underline"><?= e($o['username']) ?></a></td>
            <td class="max-w-[180px] truncate text-xs"><?= e($o['service_name']) ?></td>
            <td class="text-xs text-slate-500"><?= e($o['provider_name'] ? $o['provider_name'] . ' #' . $o['provider_order_id'] : 'Manual') ?></td>
            <td class="text-xs"><?= number_format((int)$o['quantity']) ?> / <?= number_format((int)$o['remains']) ?></td>
            <td class="text-xs font-bold"><?= e(format_money($o['charge'], 2)) ?></td>
            <td><span class="badge <?= e(status_badge_class((string)$o['status'])) ?>"><?= e(str_replace('_', ' ', (string)$o['status'])) ?></span></td>
            <td class="text-right">
              <a href="<?= e(app_url('/admin/order-view.php?id=' . (int)$o['id'])) ?>" class="btn-secondary px-2.5 py-1 text-xs">Manage</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php Theme::component('shared/pagination', ['pagination' => $pg, 'baseQuery' => ['status' => $status, 'q' => $q], 'itemLabel' => 'orders']); ?>
</form>
