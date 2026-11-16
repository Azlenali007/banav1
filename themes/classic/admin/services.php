<?php
declare(strict_types=1);
/** @var ?array $editSvc @var array $categories @var array $providers @var array $services @var array $pg @var string $q @var int $catFilter */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Service Management</h1>
  <p class="text-sm text-slate-500">Create services, configure pricing &amp; quantity limits, and map upstream providers.</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  <!-- Create / Edit Service Form -->
  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-4"><?= $editSvc ? 'Edit Service #' . (int)$editSvc['id'] : 'Create New Service' ?></h2>
    <?php if (empty($categories)): ?>
      <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900">
        Create at least one <a href="<?= e(app_url('/admin/categories.php')) ?>" class="underline font-bold">category</a> before adding services.
      </div>
    <?php else: ?>
    <form method="POST" class="space-y-3.5">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($editSvc['id'] ?? 0) ?>">
      <div>
        <label class="smm-label">Category</label>
        <select name="category_id" required class="smm-input">
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)($editSvc['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="smm-label">Service Name</label>
        <input type="text" name="name" value="<?= e($editSvc['name'] ?? '') ?>" required class="smm-input" placeholder="e.g. Instagram Real Likes">
      </div>
      <div>
        <label class="smm-label">Description</label>
        <textarea name="description" rows="2" class="smm-input"><?= e($editSvc['description'] ?? '') ?></textarea>
      </div>
      <div class="grid grid-cols-3 gap-2.5">
        <div><label class="smm-label">Rate / 1K</label><input type="number" step="0.0001" min="0.0001" name="rate_per_1000" value="<?= e($editSvc['rate_per_1000'] ?? '50.00') ?>" required class="smm-input"></div>
        <div><label class="smm-label">Min Qty</label><input type="number" min="1" name="min_qty" value="<?= (int)($editSvc['min_qty'] ?? 10) ?>" required class="smm-input"></div>
        <div><label class="smm-label">Max Qty</label><input type="number" min="1" name="max_qty" value="<?= (int)($editSvc['max_qty'] ?? 100000) ?>" required class="smm-input"></div>
      </div>
      <div class="grid grid-cols-3 gap-2.5">
        <div><label class="smm-label">Avg Time</label><input type="text" name="average_time" value="<?= e($editSvc['average_time'] ?? '') ?>" class="smm-input" placeholder="1-3 hrs"></div>
        <div><label class="smm-label">Sort</label><input type="number" name="sort_order" value="<?= (int)($editSvc['sort_order'] ?? 0) ?>" class="smm-input"></div>
        <div>
          <label class="smm-label">Status</label>
          <select name="status" class="smm-input">
            <option value="active" <?= ($editSvc['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($editSvc['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
      </div>
      <div class="flex gap-4 text-xs text-slate-700 pt-1">
        <label class="flex items-center gap-1.5"><input type="checkbox" name="refill_supported" value="1" <?= !empty($editSvc['refill_supported']) ? 'checked' : '' ?>> Refill</label>
        <label class="flex items-center gap-1.5"><input type="checkbox" name="cancel_supported" value="1" <?= !empty($editSvc['cancel_supported']) ? 'checked' : '' ?>> Cancel</label>
      </div>

      <div class="pt-3 border-t border-slate-100 space-y-2.5">
        <div class="text-xs font-bold uppercase text-violet-700">Provider Mapping (Optional)</div>
        <div>
          <label class="smm-label">Provider</label>
          <select name="provider_id" class="smm-input">
            <option value="0">None (Manual Fulfillment)</option>
            <?php foreach ($providers as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= (int)($editSvc['provider_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-2.5">
          <div><label class="smm-label">Provider Svc ID</label><input type="text" name="provider_service_id" value="<?= e($editSvc['provider_service_id'] ?? '') ?>" class="smm-input"></div>
          <div><label class="smm-label">Provider Rate</label><input type="number" step="0.0001" name="provider_rate" value="<?= e($editSvc['provider_rate'] ?? '0.0000') ?>" class="smm-input"></div>
        </div>
      </div>

      <div class="flex gap-2 pt-2">
        <button type="submit" class="btn-primary flex-1 py-2.5 text-sm"><?= $editSvc ? 'Update Service' : 'Create Service' ?></button>
        <?php if ($editSvc): ?><a href="<?= e(app_url('/admin/services.php')) ?>" class="btn-secondary px-4 py-2.5 text-sm">Cancel</a><?php endif; ?>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <!-- Services Table -->
  <div class="xl:col-span-2 space-y-4">
    <form method="GET" class="smm-card p-4 flex flex-col sm:flex-row gap-3">
      <input type="hidden" name="archived" value="<?= $showArchived ? '1' : '0' ?>">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search service ID or name..." class="smm-input flex-1">
      <select name="category" class="smm-input sm:w-52">
        <option value="0">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $catFilter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary px-5 py-2 text-sm">Filter</button>
      <a href="?archived=<?= $showArchived ? '0' : '1' ?>" class="btn-secondary px-4 py-2 text-sm"><?= $showArchived ? 'View Active' : 'View Archived' ?></a>
    </form>

    <form method="POST" x-data="{ bulkAction: 'activate' }"
          @submit="if (bulkAction === 'delete' && !confirm('Delete selected services? Services with existing orders will be archived instead of deleted.')) { $event.preventDefault(); }">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="bulk">

      <div class="smm-card p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
          <div class="sm:col-span-2">
            <label class="smm-label">Bulk Action</label>
            <select name="bulk_action" x-model="bulkAction" class="smm-input">
              <option value="activate">Activate Selected</option>
              <option value="deactivate">Deactivate Selected</option>
              <option value="delete">Delete Selected (safe)</option>
              <option value="set_category">Move to Category</option>
              <option value="set_rate">Set Rate / 1K</option>
              <option value="increase_percent">Increase Price by %</option>
              <option value="decrease_percent">Decrease Price by %</option>
              <option value="increase_fixed">Increase Price by Fixed</option>
              <option value="decrease_fixed">Decrease Price by Fixed</option>
              <option value="set_markup">Apply Provider Margin</option>
            </select>
          </div>
          <div x-show="bulkAction === 'set_category'" x-cloak>
            <label class="smm-label">Category</label>
            <select name="category_id" class="smm-input">
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div x-show="['set_rate','increase_percent','decrease_percent','increase_fixed','decrease_fixed'].includes(bulkAction)" x-cloak>
            <label class="smm-label">Amount / Percent</label>
            <input type="number" step="0.0001" min="0.0001" name="amount_value" value="10" class="smm-input">
          </div>
          <div x-show="bulkAction === 'set_markup'" x-cloak class="grid grid-cols-2 gap-2">
            <div>
              <label class="smm-label">Type</label>
              <select name="markup_type" class="smm-input">
                <option value="percent">Percent %</option>
                <option value="fixed">Fixed +</option>
              </select>
            </div>
            <div>
              <label class="smm-label">Value</label>
              <input type="number" step="0.0001" name="markup_value" value="25" class="smm-input">
            </div>
          </div>
          <button type="submit" class="btn-primary px-4 py-2.5 text-sm">Apply to Selected</button>
        </div>
        <p class="text-[11px] text-slate-400 mt-2">Check services below first. Archived services are hidden from the catalog but kept for historical order records.</p>
      </div>

      <div class="smm-card overflow-hidden">
        <div class="smm-table-wrap">
          <table class="smm-table">
            <thead>
              <tr>
                <th class="w-8"><input type="checkbox" onclick="SMMAdmin.toggleCheckboxes(this, '.svc-chk')" aria-label="Select all services"></th>
                <th>ID</th><th>Service / Category</th><th>Rate/1K</th><th>Limits</th><th>Provider</th><th>Status</th><th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($services)): ?>
                <tr><td colspan="8" class="text-center text-slate-500 py-8">No services found.</td></tr>
              <?php else: foreach ($services as $s): ?>
                <tr>
                  <td><input type="checkbox" name="service_ids[]" value="<?= (int)$s['id'] ?>" class="svc-chk" aria-label="Select service <?= (int)$s['id'] ?>"></td>
                  <td class="font-mono text-xs">#<?= (int)$s['id'] ?></td>
                  <td>
                    <div class="font-semibold text-slate-900 text-xs sm:text-sm"><?= e($s['name']) ?></div>
                    <div class="text-[11px] text-slate-400"><?= e($s['category_name']) ?></div>
                  </td>
                  <td class="font-bold text-violet-700 text-xs"><?= e(format_money($s['rate_per_1000'], 2)) ?></td>
                  <td class="text-xs text-slate-500"><?= (int)$s['min_qty'] ?>–<?= number_format((int)$s['max_qty']) ?></td>
                  <td class="text-xs text-slate-500"><?= e($s['provider_name'] ? $s['provider_name'] . ' #' . $s['provider_service_id'] : 'Manual') ?></td>
                  <td>
                    <span class="badge <?= e(status_badge_class((string)$s['status'])) ?>"><?= e($s['status']) ?></span>
                    <?php if (!empty($s['archived_at'])): ?><span class="badge badge-neutral">archived</span><?php endif; ?>
                  </td>
                  <td class="text-right space-x-1 whitespace-nowrap">
                    <a href="?edit=<?= (int)$s['id'] ?>" class="btn-secondary px-2.5 py-1 text-xs">Edit</a>
                    <button type="button"
                            onclick='SMM.confirmAction(<?= e(json_encode([
                                "title" => "Delete Service",
                                "message" => "Delete service \"" . $s["name"] . "\"? Services referenced by existing orders will be archived instead.",
                                "actionUrl" => app_url("/admin/services.php"),
                                "fields" => ["action" => "delete", "id" => (int)$s["id"]],
                                "confirmLabel" => "Delete"
                            ])) ?>)'
                            class="btn-secondary px-2.5 py-1 text-xs text-red-700">Delete</button>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <?php Theme::component('shared/pagination', ['pagination' => $pg, 'baseQuery' => ['q' => $q, 'category' => $catFilter, 'archived' => $showArchived ? 1 : 0], 'itemLabel' => 'services']); ?>
      </div>
    </form>
  </div>
</div>
