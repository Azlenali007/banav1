<?php
declare(strict_types=1);
/** @var array $order @var array $logs */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <a href="<?= e(app_url('/user/orders.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">&larr; Back to Order History</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-1">Order <?= e($order['order_uid']) ?></h1>
  </div>
  <div class="flex flex-wrap gap-2">
    <?php if (!empty($order['refill_supported']) && $order['status'] === 'completed' && !in_array($order['refill_status'], ['requested', 'processing'], true)): ?>
      <form method="POST">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="refill">
        <button type="submit" class="btn-primary px-4 py-2 text-xs">Request Refill</button>
      </form>
    <?php endif; ?>
    <?php if (!empty($order['cancel_supported']) && in_array($order['status'], ['pending', 'processing', 'in_progress'], true) && empty($order['cancel_requested'])): ?>
      <form method="POST">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="cancel">
        <button type="submit" class="btn-secondary px-4 py-2 text-xs text-red-700 border-red-200 hover:bg-red-50">Request Cancellation</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 smm-card p-6 space-y-4">
    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
      <div>
        <div class="text-xs text-slate-400">Service</div>
        <div class="font-bold text-slate-900"><?= e($order['service_name']) ?></div>
      </div>
      <span class="badge <?= e(status_badge_class((string)$order['status'])) ?>"><?= e(str_replace('_', ' ', (string)$order['status'])) ?></span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
      <div><div class="text-xs text-slate-400">Quantity</div><div class="font-bold"><?= number_format((int)$order['quantity']) ?></div></div>
      <div><div class="text-xs text-slate-400">Charge</div><div class="font-bold"><?= e(format_money($order['charge'], 2)) ?></div></div>
      <div><div class="text-xs text-slate-400">Refunded</div><div class="font-bold text-emerald-700"><?= e(format_money($order['refunded_amount'], 2)) ?></div></div>
      <div><div class="text-xs text-slate-400">Start Count</div><div class="font-bold"><?= number_format((int)$order['start_count']) ?></div></div>
      <div><div class="text-xs text-slate-400">Remains</div><div class="font-bold"><?= number_format((int)$order['remains']) ?></div></div>
      <div><div class="text-xs text-slate-400">Source</div><div class="font-bold uppercase"><?= e($order['source']) ?></div></div>
    </div>

    <div class="pt-3 border-t border-slate-100 text-sm">
      <div class="text-xs text-slate-400 mb-1">Target Link</div>
      <a href="<?= e($order['link']) ?>" target="_blank" rel="noopener noreferrer" class="text-violet-700 break-all hover:underline"><?= e($order['link']) ?></a>
    </div>

    <?php if ($order['refill_status'] !== 'none'): ?>
      <div class="p-3 rounded-xl bg-violet-50 border border-violet-100 text-xs text-violet-900">
        Refill Status: <strong class="uppercase"><?= e($order['refill_status']) ?></strong>
      </div>
    <?php endif; ?>
  </div>

  <div class="smm-card p-6">
    <h2 class="font-bold text-slate-900 mb-4">Status Timeline</h2>
    <div class="space-y-3">
      <?php foreach ($logs as $log): ?>
        <div class="text-xs border-l-2 border-violet-300 pl-3 py-0.5">
          <div class="font-semibold text-slate-800"><?= e($log['message']) ?></div>
          <div class="text-[11px] text-slate-400 mt-0.5"><?= e(format_datetime($log['created_at'])) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
