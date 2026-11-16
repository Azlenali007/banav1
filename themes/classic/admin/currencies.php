<?php
declare(strict_types=1);
/** @var ?array $edit @var array $currencies @var array $history @var string $lastSyncAt @var string $lastSyncStatus */
$baseCode = Currency::baseCode();
$provider = (string)get_setting('currency_rate_provider', 'open.er-api.com');
$apiUrl = (string)get_setting('currency_rate_api_url', '');
$autoUpdate = get_setting('currency_auto_update', '1') === '1';
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Currencies &amp; Exchange Rates</h1>
    <p class="text-sm text-slate-500">Base currency: <strong><?= e($baseCode) ?></strong>. Wallet and order values are stored in the base currency and converted for display.</p>
  </div>
  <div class="flex flex-wrap gap-2">
    <form method="POST">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="test_connection">
      <button type="submit" class="btn-secondary px-4 py-2 text-sm">Test Provider</button>
    </form>
    <form method="POST">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="refresh_rates">
      <button type="submit" class="btn-primary px-4 py-2 text-sm">Refresh Rates Now</button>
    </form>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-1"><?= $edit ? 'Edit ' . e($edit['code']) : 'Add Currency' ?></h2>
    <p class="text-xs text-slate-500 mb-4">Rate = units of this currency per 1 <?= e($baseCode) ?>.</p>
    <form method="POST" class="space-y-3.5">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <?php if ($edit): ?><input type="hidden" name="edit_id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="smm-label">Code</label><input type="text" name="code" value="<?= e($edit['code'] ?? '') ?>" required maxlength="10" class="smm-input uppercase" placeholder="USD"></div>
        <div><label class="smm-label">Symbol</label><input type="text" name="symbol" value="<?= e($edit['symbol'] ?? '') ?>" required maxlength="12" class="smm-input" placeholder="$"></div>
      </div>
      <div><label class="smm-label">Name</label><input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required class="smm-input" placeholder="US Dollar"></div>
      <div class="grid grid-cols-3 gap-2.5">
        <div><label class="smm-label">Rate</label><input type="number" step="0.00000001" min="0.00000001" name="exchange_rate" value="<?= e($edit['exchange_rate'] ?? '1.00000000') ?>" required class="smm-input"></div>
        <div><label class="smm-label">Decimals</label><input type="number" min="0" max="4" name="decimals" value="<?= (int)($edit['decimals'] ?? 2) ?>" class="smm-input"></div>
        <div><label class="smm-label">Sort</label><input type="number" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0) ?>" class="smm-input"></div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="smm-label">Symbol Position</label>
          <select name="symbol_position" class="smm-input">
            <option value="before" <?= ($edit['symbol_position'] ?? 'before') === 'before' ? 'selected' : '' ?>>Before amount</option>
            <option value="after" <?= ($edit['symbol_position'] ?? '') === 'after' ? 'selected' : '' ?>>After amount</option>
          </select>
        </div>
        <div class="flex flex-col justify-end gap-1.5 pb-1">
          <label class="flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" name="is_enabled" value="1" <?= !$edit || (int)$edit['is_enabled'] === 1 ? 'checked' : '' ?>> Enabled</label>
          <label class="flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" name="is_default" value="1" <?= $edit && (int)$edit['is_default'] === 1 ? 'checked' : '' ?>> Base currency</label>
        </div>
      </div>
      <?php if ($edit && (int)$edit['is_default'] === 1): ?>
        <div class="p-3 rounded-xl bg-violet-50 border border-violet-200 text-[11px] text-violet-900">This is the base currency. Its rate is fixed at 1.00000000.</div>
      <?php endif; ?>
      <div class="flex gap-2 pt-1">
        <button type="submit" class="btn-primary flex-1 py-2.5 text-sm"><?= $edit ? 'Update Currency' : 'Add Currency' ?></button>
        <?php if ($edit): ?><a href="<?= e(app_url('/admin/currencies.php')) ?>" class="btn-secondary px-4 py-2.5 text-sm">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="xl:col-span-2 space-y-5">
    <div class="smm-card overflow-hidden">
      <div class="smm-table-wrap">
        <table class="smm-table">
          <thead><tr><th>Currency</th><th>Rate (per <?= e($baseCode) ?>)</th><th>Status</th><th>Updated</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            <?php foreach ($currencies as $cur): ?>
              <?php $isBase = (int)$cur['is_default'] === 1; ?>
              <tr>
                <td>
                  <div class="font-semibold text-slate-900 text-sm"><?= e($cur['code']) ?> <span class="text-slate-400 font-normal"><?= e($cur['symbol']) ?></span></div>
                  <div class="text-[11px] text-slate-400"><?= e($cur['name']) ?></div>
                </td>
                <td class="font-mono text-xs"><?= e(rtrim(rtrim(number_format((float)$cur['exchange_rate'], 8, '.', ''), '0'), '.')) ?></td>
                <td class="space-x-1">
                  <?php if ($isBase): ?><span class="badge badge-purple">Base</span><?php endif; ?>
                  <span class="badge <?= (int)$cur['is_enabled'] === 1 ? 'badge-success' : 'badge-neutral' ?>"><?= (int)$cur['is_enabled'] === 1 ? 'Enabled' : 'Disabled' ?></span>
                </td>
                <td class="text-[11px] text-slate-500">
                  <?= e((string)($cur['rate_source'] ?? '—')) ?><br>
                  <?= e($cur['rate_updated_at'] ? format_datetime((string)$cur['rate_updated_at']) : '—') ?>
                </td>
                <td class="text-right whitespace-nowrap space-x-1">
                  <a href="?edit=<?= (int)$cur['id'] ?>" class="btn-secondary px-2.5 py-1 text-xs">Edit</a>
                  <?php if (!$isBase): ?>
                    <form method="POST" class="inline">
                      <?= CSRF::field() ?>
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?= (int)$cur['id'] ?>">
                      <button type="submit" class="btn-secondary px-2.5 py-1 text-xs"><?= (int)$cur['is_enabled'] === 1 ? 'Disable' : 'Enable' ?></button>
                    </form>
                    <button type="button"
                            onclick='SMM.confirmAction(<?= e(json_encode([
                                'title' => 'Delete Currency',
                                'message' => 'Delete ' . $cur['code'] . '? Currencies used by wallets or payments cannot be deleted.',
                                'actionUrl' => app_url('/admin/currencies.php'),
                                'fields' => ['action' => 'delete', 'id' => (int)$cur['id']],
                                'confirmLabel' => 'Delete',
                            ])) ?>)'
                            class="btn-secondary px-2.5 py-1 text-xs text-red-700">Delete</button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="smm-card p-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
        <div>
          <h2 class="font-bold text-slate-900">Automatic Rate Updates</h2>
          <p class="text-xs text-slate-500">Run <code class="font-mono">cron/sync-currency-rates.php</code> from cPanel for scheduled updates.</p>
        </div>
        <div class="text-[11px] text-slate-500">
          Last sync: <strong><?= e($lastSyncAt !== '' ? format_datetime($lastSyncAt) : 'never') ?></strong><br>
          <span class="text-slate-400"><?= e($lastSyncStatus !== '' ? $lastSyncStatus : '—') ?></span>
        </div>
      </div>
      <form method="POST" class="space-y-4">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="save_rate_settings">
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" name="currency_auto_update" value="1" <?= $autoUpdate ? 'checked' : '' ?>>
          Allow the cron job to update rates automatically
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="smm-label">Provider</label>
            <select name="currency_rate_provider" class="smm-input">
              <?php foreach (['open.er-api.com' => 'open.er-api.com (free, no key)', 'frankfurter' => 'frankfurter.app (free, no key)', 'custom' => 'Custom API'] as $pk => $pl): ?>
                <option value="<?= e($pk) ?>" <?= $provider === $pk ? 'selected' : '' ?>><?= e($pl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="smm-label">API Key (optional)</label>
            <input type="password" name="currency_rate_api_key" class="smm-input" autocomplete="new-password" placeholder="<?= get_setting('currency_rate_api_key_encrypted', '') !== '' ? '•••••• (leave blank to keep)' : 'Optional' ?>">
          </div>
        </div>
        <div>
          <label class="smm-label">API URL Template</label>
          <input type="text" name="currency_rate_api_url" value="<?= e($apiUrl) ?>" class="smm-input font-mono text-xs" placeholder="https://open.er-api.com/v6/latest/{base}">
          <p class="text-[11px] text-slate-400 mt-1">Use <code>{base}</code> for the base currency code and <code>{api_key}</code> if your provider needs a key.</p>
        </div>
        <button type="submit" class="btn-primary px-5 py-2.5 text-sm">Save Provider Settings</button>
      </form>
    </div>

    <div class="smm-card overflow-hidden">
      <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-500">Recent Rate History</div>
      <div class="smm-table-wrap">
        <table class="smm-table">
          <thead><tr><th>Currency</th><th>Rate</th><th>Source</th><th>When</th></tr></thead>
          <tbody>
            <?php if (empty($history)): ?>
              <tr><td colspan="4" class="text-center text-slate-500 py-6">No rate history yet.</td></tr>
            <?php else: foreach ($history as $h): ?>
              <tr>
                <td class="font-semibold text-xs"><?= e($h['code']) ?></td>
                <td class="font-mono text-xs"><?= e(rtrim(rtrim(number_format((float)$h['rate'], 8, '.', ''), '0'), '.')) ?></td>
                <td class="text-xs text-slate-500"><?= e($h['source']) ?></td>
                <td class="text-xs text-slate-500"><?= e(format_datetime((string)$h['created_at'])) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
