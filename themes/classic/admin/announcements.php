<?php
declare(strict_types=1);
/** @var array $items */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Announcements</h1>
  <p class="text-sm text-slate-500">Post maintenance notices, service updates, and important alerts for users.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-4">New Announcement</h2>
    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="create">
      <div><label class="smm-label">Title</label><input type="text" name="title" required class="smm-input"></div>
      <div>
        <label class="smm-label">Type</label>
        <select name="type" class="smm-input">
          <option value="info">Info</option>
          <option value="success">Success</option>
          <option value="warning">Warning</option>
          <option value="urgent">Urgent</option>
        </select>
      </div>
      <div><label class="smm-label">Message</label><textarea name="content" rows="4" required class="smm-input"></textarea></div>
      <label class="flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" name="is_active" value="1" checked> Active Immediately</label>
      <label class="flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" name="notify_users" value="1" checked> Also broadcast to User Notifications</label>
      <button type="submit" class="btn-primary w-full py-2.5 text-sm">Publish Announcement</button>
    </form>
  </div>

  <div class="lg:col-span-2 smm-card overflow-hidden">
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead><tr><th>Announcement</th><th>Type</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr><td colspan="4" class="text-center text-slate-500 py-8">No announcements created.</td></tr>
          <?php else: foreach ($items as $a): ?>
            <tr>
              <td>
                <div class="font-semibold text-slate-900"><?= e($a['title']) ?></div>
                <div class="text-xs text-slate-500 mt-0.5"><?= e($a['content']) ?></div>
              </td>
              <td><span class="badge badge-purple"><?= e($a['type']) ?></span></td>
              <td><span class="badge <?= !empty($a['is_active']) ? 'badge-success' : 'badge-neutral' ?>"><?= !empty($a['is_active']) ? 'Active' : 'Hidden' ?></span></td>
              <td class="text-right space-x-1 whitespace-nowrap">
                <form method="POST" class="inline">
                  <?= CSRF::field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                  <button type="submit" class="btn-secondary px-2.5 py-1 text-xs">Toggle</button>
                </form>
                <button type="button"
                        onclick='SMM.confirmAction(<?= e(json_encode([
                            "title" => "Delete Announcement",
                            "message" => "Delete announcement \"" . $a["title"] . "\"?",
                            "actionUrl" => app_url("/admin/announcements.php"),
                            "fields" => ["action" => "delete", "id" => (int)$a["id"]],
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
