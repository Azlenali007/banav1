<?php
declare(strict_types=1);
/** @var int $orderId @var array $order @var array $logs */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <a href="<?= e(app_url('/admin/orders.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">&larr; Back to Orders</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-1">Order <?= e($order['order_uid']) ?> (#<?= (int)$order['id'] ?>)</h1>
  </div>
  <?php if (!empty($order['provider_id']) && !empty($order['provider_order_id']) && admin_can('orders.manage')): ?>
    <form method="POST">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="sync_provider">
      <button type="submit" class="btn-secondary px-4 py-2 text-xs">Sync Provider Status Now</button>
    </form>
  <?php endif; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 space-y-6">
    <div class="smm-card p-6 space-y-4">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100">
        <div>
          <div class="text-xs text-slate-400">Customer: <a href="<?= e(app_url('/admin/user-view.php?id=' . (int)$order['user_id'])) ?>" class="text-violet-700 font-semibold hover:underline"><?= e($order['username']) ?></a> (<?= e($order['email']) ?>)</div>
          <div class="font-bold text-slate-900 mt-0.5"><?= e($order['service_name']) ?></div>
        </div>
        <span class="badge <?= e(status_badge_class((string)$order['status'])) ?>"><?= e(str_replace('_', ' ', (string)$order['status'])) ?></span>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
        <div><div class="text-xs text-slate-400">Quantity</div><div class="font-bold"><?= number_format((int)$order['quantity']) ?></div></div>
        <div><div class="text-xs text-slate-400">User Charge</div><div class="font-bold"><?= e(format_money($order['charge'], 2)) ?></div></div>
        <div><div class="text-xs text-slate-400">Provider Cost</div><div class="font-bold"><?= e(format_money($order['provider_cost'], 2)) ?></div></div>
        <div><div class="text-xs text-slate-400">Refunded</div><div class="font-bold text-emerald-700"><?= e(format_money($order['refunded_amount'], 2)) ?></div></div>
        <div><div class="text-xs text-slate-400">Start Count</div><div class="font-bold"><?= number_format((int)$order['start_count']) ?></div></div>
        <div><div class="text-xs text-slate-400">Remains</div><div class="font-bold"><?= number_format((int)$order['remains']) ?></div></div>
        <div><div class="text-xs text-slate-400">Provider</div><div class="font-bold"><?= e($order['provider_name'] ?: 'Manual') ?></div></div>
        <div><div class="text-xs text-slate-400">Provider Order ID</div><div class="font-mono text-xs font-bold"><?= e($order['provider_order_id'] ?: '—') ?></div></div>
      </div>

      <div class="pt-3 border-t border-slate-100 text-sm">
        <div class="text-xs text-slate-400 mb-1">Target Link</div>
        <a href="<?= e($order['link']) ?>" target="_blank" rel="noopener noreferrer" class="text-violet-700 break-all hover:underline"><?= e($order['link']) ?></a>
      </div>
    </div>

    <?php if (admin_can('orders.manage')): ?>
    <div class="smm-card p-6 space-y-5">
      <h2 class="font-bold text-slate-900">Manual Status / Refund Control</h2>
      <p class="text-xs text-slate-500">Changing status to <strong>Cancelled</strong> or <strong>Failed</strong> automatically refunds full charge once. Changing to <strong>Partial</strong> refunds proportionally based on <strong>Remains</strong>.</p>
      <form method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="manual_update">
        <div>
          <label class="smm-label">New Status</label>
          <select name="status" class="smm-input">
            <?php foreach (ORDER_STATUSES as $st): ?>
              <option value="<?= e($st) ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $st))) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="smm-label">Start Count</label>
          <input type="number" min="0" name="start_count" value="<?= (int)$order['start_count'] ?>" class="smm-input">
        </div>
        <div>
          <label class="smm-label">Remains</label>
          <input type="number" min="0" max="<?= (int)$order['quantity'] ?>" name="remains" value="<?= (int)$order['remains'] ?>" class="smm-input">
        </div>
        <div class="sm:col-span-3">
          <label class="smm-label">Reason (Logged with Admin ID &amp; Timestamp)</label>
          <input type="text" name="reason" required class="smm-input" placeholder="e.g. Confirmed completion / provider cancelled...">
        </div>
        <div class="sm:col-span-3 flex justify-end">
          <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Apply Order Update</button>
        </div>
      </form>

      <hr class="border-slate-100">
      <form method="POST" class="flex flex-wrap items-end gap-3">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="update_refill">
        <div>
          <label class="smm-label">Refill Status</label>
          <select name="refill_status" class="smm-input">
            <?php foreach (['none', 'requested', 'processing', 'completed', 'rejected'] as $rs): ?>
              <option value="<?= e($rs) ?>" <?= $order['refill_status'] === $rs ? 'selected' : '' ?>><?= e(ucfirst($rs)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-secondary px-4 py-2.5 text-xs">Update Refill Status</button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-4">Audit &amp; Status Logs</h2>
    <div class="space-y-3">
      <?php foreach ($logs as $log): ?>
        <div class="text-xs border-l-2 border-violet-400 pl-3 py-0.5">
          <div class="font-semibold text-slate-800"><?= e($log['message']) ?></div>
          <div class="text-[11px] text-slate-400 mt-0.5">
            Actor: <?= e($log['actor_type']) ?><?= $log['actor_id'] ? ' #' . (int)$log['actor_id'] : '' ?> &bull; <?= e(format_datetime($log['created_at'])) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
