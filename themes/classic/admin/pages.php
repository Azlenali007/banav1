<?php
declare(strict_types=1);
/** @var ?array $editPage @var array $pages */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Static Pages</h1>
  <p class="text-sm text-slate-500">Manage Terms of Service, Privacy Policy, About, Contact, FAQ, and custom informational pages.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-4"><?= $editPage ? 'Edit Page' : 'Create Page' ?></h2>
    <form method="POST" class="space-y-3.5">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($editPage['id'] ?? 0) ?>">
      <div><label class="smm-label">Page Title</label><input type="text" name="title" value="<?= e($editPage['title'] ?? '') ?>" required class="smm-input"></div>
      <div><label class="smm-label">URL Slug</label><input type="text" name="slug" value="<?= e($editPage['slug'] ?? '') ?>" required class="smm-input" placeholder="terms"></div>
      <div><label class="smm-label">Content (HTML)</label><textarea name="content" rows="7" required class="smm-input font-mono text-xs"><?= e($editPage['content'] ?? '') ?></textarea></div>
      <div><label class="smm-label">SEO Meta Title</label><input type="text" name="meta_title" value="<?= e($editPage['meta_title'] ?? '') ?>" class="smm-input"></div>
      <div><label class="smm-label">SEO Meta Description</label><textarea name="meta_description" rows="2" class="smm-input"><?= e($editPage['meta_description'] ?? '') ?></textarea></div>
      <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="is_published" value="1" <?= !isset($editPage) || !empty($editPage['is_published']) ? 'checked' : '' ?>> Published</label>
      <div class="flex gap-2">
        <button type="submit" class="btn-primary flex-1 py-2.5 text-sm">Save Page</button>
        <?php if ($editPage): ?><a href="<?= e(app_url('/admin/pages.php')) ?>" class="btn-secondary px-4 py-2.5 text-sm">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="lg:col-span-2 smm-card overflow-hidden">
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
          <?php if (empty($pages)): ?>
            <tr><td colspan="4" class="text-center text-slate-500 py-8">No static pages found.</td></tr>
          <?php else: foreach ($pages as $p): ?>
            <tr>
              <td class="font-semibold"><?= e($p['title']) ?></td>
              <td class="font-mono text-xs"><a href="<?= e(app_url('/page.php?slug=' . urlencode($p['slug']))) ?>" target="_blank" class="text-violet-700 hover:underline">/page.php?slug=<?= e($p['slug']) ?></a></td>
              <td><span class="badge <?= !empty($p['is_published']) ? 'badge-success' : 'badge-neutral' ?>"><?= !empty($p['is_published']) ? 'Published' : 'Draft' ?></span></td>
              <td class="text-right space-x-1 whitespace-nowrap">
                <a href="?edit=<?= (int)$p['id'] ?>" class="btn-secondary px-2.5 py-1 text-xs">Edit</a>
                <button type="button"
                        onclick='SMM.confirmAction(<?= e(json_encode([
                            "title" => "Delete Static Page",
                            "message" => "Delete page \"" . $p["title"] . "\"? This cannot be undone.",
                            "actionUrl" => app_url("/admin/pages.php"),
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
