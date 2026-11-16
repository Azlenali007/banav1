<?php
declare(strict_types=1);
/** @var ?array $editProv @var array $providers */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Provider API Management</h1>
  <p class="text-sm text-slate-500">Connect multiple external SMM v2 API providers. API keys are encrypted at rest and never exposed to the browser.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-4"><?= $editProv ? 'Edit Provider #' . (int)$editProv['id'] : 'Add Provider' ?></h2>
    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($editProv['id'] ?? 0) ?>">
      <div><label class="smm-label">Provider Name</label><input type="text" name="name" value="<?= e($editProv['name'] ?? '') ?>" required class="smm-input" placeholder="e.g. Main SMM Supplier"></div>
      <div><label class="smm-label">API Endpoint URL</label><input type="url" name="api_url" value="<?= e($editProv['api_url'] ?? '') ?>" required class="smm-input" placeholder="https://provider-domain.com/api/v2"></div>
      <div>
        <label class="smm-label">API Key <?= $editProv ? '(Leave blank to keep existing)' : '' ?></label>
        <input type="password" name="api_key" <?= $editProv ? '' : 'required' ?> class="smm-input" autocomplete="new-password" placeholder="Encrypted at rest">
      </div>
      <div>
        <label class="smm-label">Status</label>
        <select name="status" class="smm-input">
          <option value="active" <?= ($editProv['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= ($editProv['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div><label class="smm-label">Notes</label><textarea name="notes" rows="2" class="smm-input"><?= e($editProv['notes'] ?? '') ?></textarea></div>
      <div class="flex gap-2">
        <button type="submit" class="btn-primary flex-1 py-2.5 text-sm"><?= $editProv ? 'Update Provider' : 'Add Provider' ?></button>
        <?php if ($editProv): ?><a href="<?= e(app_url('/admin/providers.php')) ?>" class="btn-secondary px-4 py-2.5 text-sm">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="lg:col-span-2 smm-card overflow-hidden">
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead><tr><th>Provider</th><th>Balance</th><th>Mapped</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
          <?php if (empty($providers)): ?>
            <tr><td colspan="5" class="text-center text-slate-500 py-8">No providers configured yet.</td></tr>
          <?php else: foreach ($providers as $p): ?>
            <tr>
              <td>
                <div class="font-semibold text-slate-900"><?= e($p['name']) ?></div>
                <div class="text-xs text-slate-400 truncate max-w-[220px]"><?= e($p['api_url']) ?></div>
              </td>
              <td>
                <div class="font-bold text-violet-700 text-xs"><?= e($p['balance']) ?> <?= e($p['currency']) ?></div>
                <div class="text-[10px] text-slate-400"><?= e(format_datetime($p['last_balance_sync'])) ?></div>
              </td>
              <td class="text-xs"><?= (int)$p['mapped_count'] ?></td>
              <td><span class="badge <?= e(status_badge_class((string)$p['status'])) ?>"><?= e($p['status']) ?></span></td>
              <td class="text-right space-x-1 whitespace-nowrap">
                <form method="POST" class="inline">
                  <?= CSRF::field() ?>
                  <input type="hidden" name="action" value="test_balance">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="btn-secondary px-2.5 py-1 text-xs">Test &amp; Sync</button>
                </form>
                <a href="<?= e(app_url('/admin/provider-services.php?provider_id=' . (int)$p['id'])) ?>" class="btn-primary px-2.5 py-1 text-xs">Import Services</a>
                <a href="?edit=<?= (int)$p['id'] ?>" class="btn-secondary px-2.5 py-1 text-xs">Edit</a>
                <button type="button"
                        onclick='SMM.confirmAction(<?= e(json_encode([
                            "title" => "Delete Provider",
                            "message" => "Delete provider \"" . $p["name"] . "\"? Providers with mapped services cannot be deleted.",
                            "actionUrl" => app_url("/admin/providers.php"),
                            "fields" => ["action" => "delete", "id" => (int)$p["id"]],
                            "confirmLabel" => "Delete"
                        ])) ?>)'
                        class="btn-secondary px-2.5 py-1 text-xs text-red-700">Delete</button>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
