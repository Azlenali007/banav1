<?php
declare(strict_types=1);
/** @var string $q @var int $catFilter @var array $categories @var array $grouped @var string $multiplier */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Services Catalog</h1>
    <p class="text-sm text-slate-500">Browse all active categories, rates per 1,000, and quantity limits.</p>
  </div>
  <a href="<?= e(app_url('/user/new-order.php')) ?>" class="btn-primary px-4 py-2 text-sm">+ Place New Order</a>
</div>

<form method="GET" class="smm-card p-4 mb-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
  <div>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search by ID, service name, or keyword..." class="smm-input">
  </div>
  <div>
    <select name="category" class="smm-input">
      <option value="0">All Active Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $catFilter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="flex gap-2">
    <button type="submit" class="btn-primary px-5 py-2 text-sm flex-1">Filter Services</button>
    <?php if ($q !== '' || $catFilter > 0): ?>
      <a href="<?= e(app_url('/user/services.php')) ?>" class="btn-secondary px-4 py-2 text-sm">Reset</a>
    <?php endif; ?>
  </div>
</form>

<?php if (empty($grouped)): ?>
  <div class="smm-card p-10 text-center text-sm text-slate-500">No active services matched your criteria.</div>
<?php else: ?>
  <div class="space-y-6">
    <?php foreach ($grouped as $catName => $items): ?>
      <div class="smm-card overflow-hidden">
        <div class="px-5 py-3.5 bg-gradient-to-r from-violet-50 to-white border-b border-slate-200 flex items-center justify-between">
          <h2 class="font-bold text-violet-900"><?= e($catName) ?></h2>
          <span class="text-xs font-semibold text-violet-700"><?= count($items) ?> Services</span>
        </div>
        <div class="smm-table-wrap">
          <table class="smm-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Service</th>
                <th>Rate / 1K</th>
                <th>Min / Max</th>
                <th>Avg Time</th>
                <th>Features</th>
                <th class="text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $svc): ?>
                <?php $effectiveRate = money_mul((string)$svc['rate_per_1000'], $multiplier, 4); ?>
                <tr>
                  <td class="font-mono text-xs font-bold text-slate-600">#<?= (int)$svc['id'] ?></td>
                  <td>
                    <div class="font-semibold text-slate-900"><?= e($svc['name']) ?></div>
                    <?php if (!empty($svc['description'])): ?>
                      <div class="text-xs text-slate-500 mt-0.5 max-w-md"><?= e($svc['description']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="font-bold text-violet-700"><?= e(format_money($effectiveRate, 2)) ?></td>
                  <td class="text-xs text-slate-600"><?= number_format((int)$svc['min_qty']) ?> / <?= number_format((int)$svc['max_qty']) ?></td>
                  <td class="text-xs text-slate-500"><?= e($svc['average_time'] ?: '—') ?></td>
                  <td>
                    <div class="flex flex-wrap gap-1">
                      <?php if (!empty($svc['refill_supported'])): ?><span class="badge badge-success">Refill</span><?php endif; ?>
                      <?php if (!empty($svc['cancel_supported'])): ?><span class="badge badge-info">Cancel</span><?php endif; ?>
                    </div>
                  </td>
                  <td class="text-right">
                    <a href="<?= e(app_url('/user/new-order.php?service_id=' . (int)$svc['id'])) ?>" class="btn-primary px-3 py-1.5 text-xs">Order</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
