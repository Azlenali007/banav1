<?php
declare(strict_types=1);
/** @var array $currentUser @var ?array $razorpay @var ?array $manual @var bool $rzpEnabled @var bool $manualEnabled @var string $error @var ?array $razorpayOrderPayload */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Add Funds to Wallet</h1>
    <p class="text-sm text-slate-500">Current Balance: <strong class="text-violet-700"><?= e(format_money($currentUser['wallet_balance'] ?? 0)) ?></strong></p>
  </div>
  <a href="<?= e(app_url('/user/transactions.php')) ?>" class="btn-secondary px-4 py-2 text-sm">View Transaction Ledger</a>
</div>

<?php if ($error !== ''): ?>
  <div class="mb-5 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"><?= e($error) ?></div>
<?php endif; ?>
<div id="rzp-error-box" class="hidden mb-5 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"></div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  <!-- Razorpay Card -->
  <div class="smm-card p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="font-bold text-slate-900">Razorpay Instant Checkout</h2>
        <p class="text-xs text-slate-500">UPI, Credit/Debit Cards &amp; NetBanking</p>
      </div>
      <span class="badge <?= $rzpEnabled ? 'badge-success' : 'badge-neutral' ?>"><?= $rzpEnabled ? 'Available' : 'Unavailable' ?></span>
    </div>

    <?php if (!$rzpEnabled): ?>
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-600">
        Razorpay is currently not configured or disabled by the administrator. Please use Manual Payment or contact support.
      </div>
    <?php else: ?>
      <form method="POST" class="space-y-4">
        <?= CSRF::field() ?>
        <input type="hidden" name="gateway" value="razorpay">
        <div>
          <label class="smm-label">Deposit Amount (<?= e(get_setting('currency_code', DEFAULT_CURRENCY)) ?>)</label>
          <input type="number" step="0.01" min="<?= e((string)$razorpay['min_amount']) ?>" max="<?= e((string)$razorpay['max_amount']) ?>" name="amount" required class="smm-input" placeholder="100.00">
          <p class="text-[11px] text-slate-400 mt-1">Min: <?= e(format_money($razorpay['min_amount'], 2)) ?> &bull; Max: <?= e(format_money($razorpay['max_amount'], 2)) ?></p>
        </div>
        <button type="submit" class="btn-primary w-full py-2.5 text-sm">Proceed to Razorpay Checkout</button>
      </form>
    <?php endif; ?>
  </div>

  <!-- Manual Payment Card -->
  <div class="smm-card p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="font-bold text-slate-900">Manual Payment (Admin Approved)</h2>
        <p class="text-xs text-slate-500">Bank Transfer / UPI Reference Submission</p>
      </div>
      <span class="badge <?= $manualEnabled ? 'badge-purple' : 'badge-neutral' ?>"><?= $manualEnabled ? 'Active' : 'Disabled' ?></span>
    </div>

    <?php if (!$manualEnabled): ?>
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-600">
        Manual payment submissions are currently disabled.
      </div>
    <?php else: ?>
      <div class="mb-4 p-4 rounded-xl bg-violet-50 border border-violet-100 text-xs text-violet-900 whitespace-pre-line leading-relaxed">
        <div class="font-bold mb-1">Payment Instructions:</div>
        <?= e((string)$manual['instructions']) ?>
      </div>

      <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <?= CSRF::field() ?>
        <input type="hidden" name="gateway" value="manual">
        <div>
          <label class="smm-label">Amount Sent (<?= e(get_setting('currency_code', DEFAULT_CURRENCY)) ?>)</label>
          <input type="number" step="0.01" min="<?= e((string)$manual['min_amount']) ?>" max="<?= e((string)$manual['max_amount']) ?>" name="amount" required class="smm-input" placeholder="500.00">
          <p class="text-[11px] text-slate-400 mt-1">Min: <?= e(format_money($manual['min_amount'], 2)) ?> &bull; Max: <?= e(format_money($manual['max_amount'], 2)) ?></p>
        </div>
        <div>
          <label class="smm-label">Transaction ID / UTR / Reference Number</label>
          <input type="text" name="transaction_reference" required minlength="3" class="smm-input" placeholder="e.g. UTR1234567890">
        </div>
        <div>
          <label class="smm-label">Payment Screenshot (Optional, JPG/PNG/WEBP max 2MB)</label>
          <input type="file" name="proof_file" accept="image/jpeg,image/png,image/webp" class="smm-input text-xs">
        </div>
        <button type="submit" class="btn-primary w-full py-2.5 text-sm">Submit Deposit for Approval</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($razorpayOrderPayload !== null): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  launchRazorpayCheckout(<?= json_encode([
      'keyId' => $razorpayOrderPayload['key_id'],
      'amountSubunits' => (int)$razorpayOrderPayload['amount_subunits'],
      'currency' => $razorpayOrderPayload['currency'],
      'siteName' => get_setting('site_name', APP_DEFAULT_NAME),
      'paymentUid' => $razorpayOrderPayload['payment_uid'],
      'razorpayOrderId' => $razorpayOrderPayload['razorpay_order_id'],
      'customerName' => $currentUser['full_name'],
      'customerEmail' => $currentUser['email'],
      'themeColor' => Theme::get('primary_color', '#7C3AED'),
      'callbackUrl' => app_url('/api/callbacks/razorpay.php'),
  ]) ?>);
});
</script>
<?php endif; ?>
