<?php
declare(strict_types=1);
/** @var array $currentUser @var string $generalError @var array $lineErrors @var array $results @var string $rawInput @var string $formIdemKey */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Mass / Bulk Order</h1>
  <p class="text-sm text-slate-500">Submit multiple orders in one batch using pipe-delimited lines.</p>
</div>

<?php if ($generalError !== ''): ?>
  <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"><?= e($generalError) ?></div>
<?php endif; ?>

<?php if (!empty($lineErrors)): ?>
  <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm space-y-1">
    <div class="font-bold">Validation / Processing Errors:</div>
    <ul class="list-disc pl-5 text-xs space-y-0.5">
      <?php foreach ($lineErrors as $le): ?><li><?= e($le) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if (!empty($results)): ?>
  <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm space-y-1">
    <div class="font-bold">Processed Orders:</div>
    <ul class="list-disc pl-5 text-xs space-y-0.5">
      <?php foreach ($results as $r): ?><li><?= e($r) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="smm-card p-6">
  <div class="mb-4 p-4 rounded-xl bg-violet-50 border border-violet-100 text-xs text-violet-900">
    <div class="font-bold mb-1">Supported Format (One Order Per Line):</div>
    <code class="block bg-white px-3 py-2 rounded-lg border border-violet-200 font-mono text-violet-800">service_id | https://example.com/post | 1000</code>
  </div>

  <form method="POST" class="space-y-4">
    <?= CSRF::field() ?>
    <input type="hidden" name="idempotency_key" value="<?= e($formIdemKey) ?>">
    <div>
      <label class="smm-label">Order Lines (Max 100 lines)</label>
      <textarea name="bulk_lines" rows="10" required class="smm-input font-mono text-xs" placeholder="1 | https://instagram.com/p/example | 500"><?= e($rawInput) ?></textarea>
    </div>
    <div class="flex items-center justify-between text-xs text-slate-500">
      <span>Available Balance: <strong class="text-slate-800"><?= e(format_money($currentUser['wallet_balance'] ?? 0)) ?></strong></span>
      <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Validate &amp; Submit Bulk Orders</button>
    </div>
  </form>
</div>
