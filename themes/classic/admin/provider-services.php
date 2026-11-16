<?php
declare(strict_types=1);
/** @var int $providerId @var array $provider @var array $categories @var array $remoteServices @var array $existingIds @var string $fetchError */
?>
<div class="mb-6">
  <a href="<?= e(app_url('/admin/providers.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">&larr; Back to Providers</a>
  <h1 class="text-2xl font-bold text-slate-900 mt-1">Import Services from <?= e($provider['name']) ?></h1>
</div>

<?php if ($fetchError !== ''): ?>
  <div class="smm-card p-6 border-red-200 bg-red-50 text-red-800 text-sm">
    <strong>Provider API Error:</strong> <?= e($fetchError) ?>
  </div>
<?php elseif (empty($categories)): ?>
  <div class="smm-card p-6 border-amber-200 bg-amber-50 text-amber-900 text-sm">
    Please create at least one <a href="<?= e(app_url('/admin/categories.php')) ?>" class="underline font-bold">category</a> before importing services.
  </div>
<?php else: ?>
  <form method="POST" class="space-y-5">
    <?= CSRF::field() ?>
    <input type="hidden" name="action" value="import_selected">
    <input type="hidden" name="provider_id" value="<?= $providerId ?>">
    <input type="hidden" name="services_payload" value="<?= e(json_encode(array_slice($remoteServices, 0, 500))) ?>">

    <div class="smm-card p-5 grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
      <div>
        <label class="smm-label">Destination Category</label>
        <select name="category_id" required class="smm-input">
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="smm-label">Price Adjustment Type</label>
        <select name="markup_type" class="smm-input">
          <option value="percent">Percentage Markup (%)</option>
          <option value="fixed">Fixed Markup (+Amount)</option>
        </select>
      </div>
      <div>
        <label class="smm-label">Markup Value</label>
        <input type="number" step="0.01" name="markup_value" value="25" required class="smm-input">
      </div>
      <button type="submit" class="btn-primary py-2.5 px-5 text-sm">Import Selected</button>
      <p class="sm:col-span-4 text-[11px] text-slate-500">
        Already-imported provider services are marked and skipped automatically. Duplicate detection uses provider + provider service ID, so re-importing is safe.
      </p>
    </div>

    <div class="smm-card overflow-hidden">
      <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600">
        Showing <?= min(500, count($remoteServices)) ?> services returned by provider API
        &mdash; <?= count($existingIds) ?> already imported
      </div>
      <div class="smm-table-wrap max-h-[600px] overflow-y-auto">
        <table class="smm-table">
          <thead>
            <tr>
              <th class="w-8"><input type="checkbox" onclick="SMMAdmin.toggleCheckboxes(this, '.psvc-chk')"></th>
              <th>Provider ID</th>
              <th>Name</th>
              <th>Provider Category</th>
              <th>Provider Rate</th>
              <th>Min / Max</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($remoteServices, 0, 500) as $rs): ?>
              <?php $isImported = isset($existingIds[(string)($rs['service'] ?? '')]); ?>
              <tr class="<?= $isImported ? 'opacity-60' : '' ?>">
                <td>
                  <input type="checkbox" name="selected_services[]" value="<?= e($rs['service'] ?? '') ?>" class="psvc-chk" <?= $isImported ? 'disabled' : '' ?>>
                </td>
                <td class="font-mono text-xs">
                  <?= e($rs['service'] ?? '') ?>
                  <?php if ($isImported): ?><span class="badge badge-neutral ml-1">Imported</span><?php endif; ?>
                </td>
                <td class="text-xs font-semibold"><?= e($rs['name'] ?? '') ?></td>
                <td class="text-xs text-slate-500"><?= e($rs['category'] ?? '') ?></td>
                <td class="text-xs font-bold text-violet-700"><?= e($rs['rate'] ?? '0') ?></td>
                <td class="text-xs text-slate-500"><?= e($rs['min'] ?? '') ?> / <?= e($rs['max'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </form>
<?php endif; ?>
