<?php
declare(strict_types=1);
/** @var array $backupItems @var int $retentionDays */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Database Backups</h1>
    <p class="text-sm text-slate-500">Create full MySQL SQL backups stored in protected <code>/storage/backups/</code>.</p>
  </div>
  <form method="POST">
    <?= CSRF::field() ?>
    <input type="hidden" name="action" value="create_backup">
    <button type="submit" class="btn-primary px-5 py-2.5 text-sm">+ Create Database Backup</button>
  </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-3">Retention Policy</h2>
    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="save_retention">
      <div>
        <label class="smm-label">Keep Backups For (Days)</label>
        <input type="number" min="1" max="365" name="backup_retention_days" value="<?= $retentionDays ?>" class="smm-input">
      </div>
      <button type="submit" class="btn-secondary px-4 py-2 text-xs">Save Retention</button>
    </form>
  </div>

  <div class="lg:col-span-2 smm-card overflow-hidden">
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead><tr><th>Backup File</th><th>Size</th><th>Created</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
          <?php if (empty($backupItems)): ?>
            <tr><td colspan="4" class="text-center text-slate-500 py-8">No backups generated yet.</td></tr>
          <?php else: foreach ($backupItems as $b): ?>
            <tr>
              <td class="font-mono text-xs font-semibold text-violet-700"><?= e($b['name']) ?></td>
              <td class="text-xs"><?= number_format($b['size'] / 1024, 1) ?> KB</td>
              <td class="text-xs text-slate-500"><?= date('M d, Y H:i', $b['mtime']) ?></td>
              <td class="text-right space-x-1 whitespace-nowrap">
                <a href="?download=<?= e(urlencode($b['name'])) ?>" class="btn-secondary px-2.5 py-1 text-xs">Download</a>
                <button type="button"
                        onclick='SMM.confirmAction(<?= e(json_encode([
                            "title" => "Delete Backup",
                            "message" => "Delete backup file \"" . $b["name"] . "\" permanently?",
                            "actionUrl" => app_url("/admin/backups.php"),
                            "fields" => ["action" => "delete_backup", "file" => $b["name"]],
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
