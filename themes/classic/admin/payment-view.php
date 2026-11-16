<?php
declare(strict_types=1);
/** @var int $paymentId @var array $payment */
?>
<div class="mb-6">
  <a href="<?= e(app_url('/admin/payments.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">&larr; Back to Payments</a>
  <h1 class="text-2xl font-bold text-slate-900 mt-1">Payment <?= e($payment['payment_uid']) ?></h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  <div class="smm-card p-6 space-y-4">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
      <div>
        <div class="text-xs text-slate-400">User</div>
        <div class="font-bold text-slate-900"><?= e($payment['username']) ?> (<?= e($payment['email']) ?>)</div>
      </div>
      <span class="badge <?= e(status_badge_class((string)$payment['status'])) ?>"><?= e($payment['status']) ?></span>
    </div>
    <div class="grid grid-cols-2 gap-4 text-sm">
      <div><div class="text-xs text-slate-400">Method</div><div class="font-bold uppercase"><?= e($payment['method_code']) ?></div></div>
      <div><div class="text-xs text-slate-400">Amount</div><div class="font-bold text-violet-700"><?= e(format_money($payment['amount'], 2)) ?> <?= e($payment['currency']) ?></div></div>
      <div><div class="text-xs text-slate-400">Transaction Reference</div><div class="font-mono text-xs"><?= e($payment['transaction_reference'] ?: '—') ?></div></div>
      <div><div class="text-xs text-slate-400">Gateway Order / Payment ID</div><div class="font-mono text-xs break-all"><?= e($payment['gateway_payment_id'] ?: ($payment['gateway_order_id'] ?: '—')) ?></div></div>
      <div><div class="text-xs text-slate-400">Created At</div><div class="text-xs"><?= e(format_datetime($payment['created_at'])) ?></div></div>
      <div><div class="text-xs text-slate-400">Credited At</div><div class="text-xs"><?= e(format_datetime($payment['credited_at'])) ?></div></div>
    </div>

    <?php if (!empty($payment['proof_file'])): ?>
      <div class="pt-3 border-t border-slate-100">
        <div class="text-xs font-semibold text-slate-600 mb-2">Uploaded Payment Proof</div>
        <img src="?id=<?= $paymentId ?>&view_proof=1" alt="Payment Proof" class="max-h-80 rounded-xl border border-slate-200 object-contain">
      </div>
    <?php endif; ?>
  </div>

  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-4">Verification Action</h2>
    <?php if ($payment['status'] === 'pending' && $payment['method_code'] === 'manual' && admin_can('payments.manage')): ?>
      <form method="POST" class="space-y-4">
        <?= CSRF::field() ?>
        <div>
          <label class="smm-label">Admin Note / Reason</label>
          <textarea name="admin_note" rows="3" class="smm-input" placeholder="Verified bank/UPI reference..."></textarea>
        </div>
        <div class="flex gap-3">
          <button type="submit" name="action" value="approve" class="btn-primary flex-1 py-2.5 text-sm">Approve &amp; Credit Wallet</button>
          <button type="submit" name="action" value="reject" class="btn-secondary px-5 py-2.5 text-sm text-red-700 border-red-200 hover:bg-red-50">Reject</button>
        </div>
      </form>
    <?php else: ?>
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-600">
        Status: <strong class="uppercase"><?= e($payment['status']) ?></strong><br>
        <?php if (!empty($payment['admin_note'])): ?>Admin Note: <?= e($payment['admin_note']) ?><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
