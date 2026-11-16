<?php
declare(strict_types=1);
/** @var int $userId @var array $user @var array $orders @var array $txs @var string $adjIdemKey */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <a href="<?= e(app_url('/admin/users.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">&larr; Back to Users</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-1"><?= e($user['full_name']) ?> (@<?= e($user['username']) ?>)</h1>
    <p class="text-sm text-slate-500"><?= e($user['email']) ?> &bull; Status: <span class="badge <?= e(status_badge_class((string)$user['status'])) ?>"><?= e($user['status']) ?></span></p>
  </div>
  <?php if (admin_can('users.manage')): ?>
    <a href="<?= e(app_url('/admin/user-edit.php?id=' . $userId)) ?>" class="btn-secondary px-4 py-2 text-sm">Edit Profile &amp; Password</a>
  <?php endif; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
  <div class="smm-card p-6 space-y-3">
    <div class="text-xs text-slate-400 uppercase font-bold">Wallet Summary</div>
    <div class="text-3xl font-extrabold text-violet-700"><?= e(format_money($user['balance'] ?? 0, 2)) ?></div>
    <div class="grid grid-cols-2 gap-3 pt-2 text-xs">
      <div class="p-3 rounded-xl bg-slate-50"><div class="text-slate-400">Total Deposited</div><div class="font-bold text-slate-800 mt-0.5"><?= e(format_money($user['total_deposited'] ?? 0, 2)) ?></div></div>
      <div class="p-3 rounded-xl bg-slate-50"><div class="text-slate-400">Total Spent</div><div class="font-bold text-slate-800 mt-0.5"><?= e(format_money($user['total_spent'] ?? 0, 2)) ?></div></div>
    </div>
    <div class="text-xs text-slate-500 pt-2">Rate Multiplier: <strong><?= e($user['custom_rate_multiplier']) ?>x</strong> &bull; API: <strong><?= !empty($user['api_enabled']) ? 'Enabled' : 'Disabled' ?></strong></div>
  </div>

  <?php if (admin_can('users.manage')): ?>
  <div class="lg:col-span-2 smm-card p-6">
    <h2 class="font-bold text-slate-900 mb-4">Wallet Balance Adjustment (Immutable Ledger)</h2>
    <form method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="idempotency_key" value="<?= e($adjIdemKey) ?>">
      <div>
        <label class="smm-label">Operation</label>
        <select name="adj_type" class="smm-input">
          <option value="credit">Add Balance (Credit)</option>
          <option value="debit">Deduct Balance (Debit)</option>
          <option value="refund">Issue Refund (Refund)</option>
        </select>
      </div>
      <div>
        <label class="smm-label">Amount</label>
        <input type="number" step="0.01" min="0.01" name="amount" required class="smm-input" placeholder="100.00">
      </div>
      <div>
        <label class="smm-label">Reason (Required for Audit)</label>
        <input type="text" name="reason" required class="smm-input" placeholder="Manual adjustment note...">
      </div>
      <div class="sm:col-span-3 flex justify-end">
        <button type="submit" class="btn-primary px-5 py-2 text-sm">Apply Wallet Adjustment</button>
      </div>
    </form>
  </div>
  <?php endif; ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
  <div class="smm-card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-900">Recent Orders</div>
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead><tr><th>Order</th><th>Service</th><th>Charge</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (empty($orders)): ?>
            <tr><td colspan="4" class="text-center text-slate-500">No orders.</td></tr>
          <?php else: foreach ($orders as $o): ?>
            <tr>
              <td><a href="<?= e(app_url('/admin/order-view.php?id=' . (int)$o['id'])) ?>" class="font-mono text-xs font-bold text-violet-700 hover:underline"><?= e($o['order_uid']) ?></a></td>
              <td class="text-xs max-w-[180px] truncate"><?= e($o['service_name']) ?></td>
              <td class="text-xs font-semibold"><?= e(format_money($o['charge'], 2)) ?></td>
              <td><span class="badge <?= e(status_badge_class((string)$o['status'])) ?>"><?= e($o['status']) ?></span></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="smm-card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-900">Recent Wallet Ledger</div>
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead><tr><th>Type</th><th>Description</th><th>Amount</th><th>After</th></tr></thead>
        <tbody>
          <?php if (empty($txs)): ?>
            <tr><td colspan="4" class="text-center text-slate-500">No transactions.</td></tr>
          <?php else: foreach ($txs as $t): ?>
            <?php $isCredit = in_array($t['type'], ['credit', 'refund'], true) || ($t['type'] === 'adjustment' && money_cmp((string)$t['balance_after'], (string)$t['balance_before']) >= 0); ?>
            <tr>
              <td><span class="badge <?= $isCredit ? 'badge-success' : 'badge-neutral' ?>"><?= e($t['type']) ?></span></td>
              <td class="text-xs"><?= e($t['description']) ?></td>
              <td class="text-xs font-bold <?= $isCredit ? 'text-emerald-600' : 'text-red-600' ?>"><?= $isCredit ? '+' : '-' ?><?= e(format_money($t['amount'], 2)) ?></td>
              <td class="text-xs"><?= e(format_money($t['balance_after'], 2)) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
