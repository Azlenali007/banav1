<?php
declare(strict_types=1);
/** @var array $razorpay @var array $manual @var string $statusFilter @var string $q @var array $payments @var array $pg */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Payment Gateways &amp; Deposits</h1>
  <p class="text-sm text-slate-500">Configure Razorpay credentials securely, edit manual payment instructions, and review user deposits.</p>
</div>

<?php if (admin_can('payments.manage')): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-7">
  <!-- Razorpay Config -->
  <div class="smm-card p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-bold text-slate-900">Razorpay Gateway</h2>
      <span class="badge <?= !empty($razorpay['is_enabled']) ? 'badge-success' : 'badge-neutral' ?>"><?= !empty($razorpay['is_enabled']) ? 'Enabled' : 'Disabled' ?></span>
    </div>
    <form method="POST" class="space-y-3.5">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="save_razorpay">
      <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
        <input type="checkbox" name="is_enabled" value="1" <?= !empty($razorpay['is_enabled']) ? 'checked' : '' ?>>
        <span>Enable Razorpay Checkout</span>
      </label>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="smm-label">Min Deposit</label><input type="number" step="0.01" name="min_amount" value="<?= e($razorpay['min_amount'] ?? '10.00') ?>" class="smm-input"></div>
        <div><label class="smm-label">Max Deposit</label><input type="number" step="0.01" name="max_amount" value="<?= e($razorpay['max_amount'] ?? '100000.00') ?>" class="smm-input"></div>
      </div>
      <div>
        <label class="smm-label">Razorpay Key ID</label>
        <input type="text" name="key_id" value="<?= e($razorpay['config']['key_id'] ?? '') ?>" class="smm-input" placeholder="rzp_live_...">
      </div>
      <div>
        <label class="smm-label">Razorpay Key Secret <?= !empty($razorpay['config']['key_secret']) ? '(Configured — leave blank to keep)' : '' ?></label>
        <input type="password" name="key_secret" value="" class="smm-input" autocomplete="new-password" placeholder="Encrypted at rest">
      </div>
      <input type="hidden" name="instructions" value="<?= e($razorpay['instructions'] ?? '') ?>">
      <button type="submit" class="btn-primary px-5 py-2 text-sm">Save Razorpay Config</button>
    </form>
  </div>

  <!-- Manual Payment Config -->
  <div class="smm-card p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-bold text-slate-900">Manual Payment (Admin Approved)</h2>
      <span class="badge <?= !empty($manual['is_enabled']) ? 'badge-purple' : 'badge-neutral' ?>"><?= !empty($manual['is_enabled']) ? 'Enabled' : 'Disabled' ?></span>
    </div>
    <form method="POST" class="space-y-3.5">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="save_manual">
      <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
        <input type="checkbox" name="is_enabled" value="1" <?= !empty($manual['is_enabled']) ? 'checked' : '' ?>>
        <span>Enable Manual Deposits</span>
      </label>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="smm-label">Min Deposit</label><input type="number" step="0.01" name="min_amount" value="<?= e($manual['min_amount'] ?? '50.00') ?>" class="smm-input"></div>
        <div><label class="smm-label">Max Deposit</label><input type="number" step="0.01" name="max_amount" value="<?= e($manual['max_amount'] ?? '100000.00') ?>" class="smm-input"></div>
      </div>
      <div>
        <label class="smm-label">Manual Payment Instructions (UPI ID / Bank Account)</label>
        <textarea name="instructions" rows="4" class="smm-input"><?= e($manual['instructions'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn-primary px-5 py-2 text-sm">Save Manual Settings</button>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Payment History Filters -->
<div class="flex flex-wrap gap-2 mb-4">
  <?php foreach (array_merge(['all'], PAYMENT_STATUSES) as $st): ?>
    <a href="?status=<?= e($st) ?>&q=<?= e(urlencode($q)) ?>" class="px-3.5 py-1.5 rounded-full text-xs font-semibold border <?= $statusFilter === $st ? 'bg-violet-600 text-white border-violet-600' : 'bg-white text-slate-600 border-slate-200' ?>">
      <?= e(ucfirst($st)) ?>
    </a>
  <?php endforeach; ?>
</div>

<form method="GET" class="smm-card p-4 mb-5 flex flex-col sm:flex-row gap-3">
  <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search Payment UID, username, transaction reference, or Razorpay ID..." class="smm-input flex-1">
  <button type="submit" class="btn-primary px-5 py-2 text-sm">Search</button>
</form>

<div class="smm-card overflow-hidden">
  <div class="smm-table-wrap">
    <table class="smm-table">
      <thead><tr><th>Payment ID</th><th>User</th><th>Gateway</th><th>Amount</th><th>Reference</th><th>Status</th><th>Date</th><th class="text-right">Action</th></tr></thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr><td colspan="8" class="text-center text-slate-500 py-8">No payment records found.</td></tr>
        <?php else: foreach ($payments as $p): ?>
          <tr>
            <td class="font-mono text-xs font-bold text-violet-700"><?= e($p['payment_uid']) ?></td>
            <td class="text-xs font-semibold"><?= e($p['username']) ?></td>
            <td class="uppercase text-xs font-bold"><?= e($p['method_code']) ?></td>
            <td class="font-bold"><?= e(format_money($p['amount'], 2)) ?></td>
            <td class="text-xs text-slate-500"><?= e($p['transaction_reference'] ?: ($p['gateway_payment_id'] ?: $p['gateway_order_id'] ?: '—')) ?></td>
            <td><span class="badge <?= e(status_badge_class((string)$p['status'])) ?>"><?= e($p['status']) ?></span></td>
            <td class="text-xs text-slate-500"><?= e(format_datetime($p['created_at'])) ?></td>
            <td class="text-right">
              <a href="<?= e(app_url('/admin/payment-view.php?id=' . (int)$p['id'])) ?>" class="btn-secondary px-3 py-1 text-xs">Review</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php Theme::component('shared/pagination', ['pagination' => $pg, 'baseQuery' => ['status' => $statusFilter, 'q' => $q], 'itemLabel' => 'payments']); ?>
</div>
