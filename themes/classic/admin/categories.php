<?php
declare(strict_types=1);
/** @var ?array $editCat @var array $categories */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Category Management</h1>
  <p class="text-sm text-slate-500">Organize services into ordered, active categories.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-4"><?= $editCat ? 'Edit Category #' . (int)$editCat['id'] : 'Add Category' ?></h2>
    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($editCat['id'] ?? 0) ?>">
      <div>
        <label class="smm-label">Category Name</label>
        <input type="text" name="name" value="<?= e($editCat['name'] ?? '') ?>" required class="smm-input" placeholder="e.g. Instagram Growth">
      </div>
      <div>
        <label class="smm-label">Slug (optional)</label>
        <input type="text" name="slug" value="<?= e($editCat['slug'] ?? '') ?>" class="smm-input" placeholder="instagram-growth">
      </div>
      <div>
        <label class="smm-label">Description</label>
        <textarea name="description" rows="2" class="smm-input"><?= e($editCat['description'] ?? '') ?></textarea>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="smm-label">Sort Order</label>
          <input type="number" name="sort_order" value="<?= (int)($editCat['sort_order'] ?? 0) ?>" class="smm-input">
        </div>
        <div>
          <label class="smm-label">Status</label>
          <select name="status" class="smm-input">
            <option value="active" <?= ($editCat['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($editCat['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
      </div>
      <div class="flex gap-2">
        <button type="submit" class="btn-primary flex-1 py-2.5 text-sm"><?= $editCat ? 'Update Category' : 'Create Category' ?></button>
        <?php if ($editCat): ?><a href="<?= e(app_url('/admin/categories.php')) ?>" class="btn-secondary px-4 py-2.5 text-sm">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="lg:col-span-2 smm-card overflow-hidden">
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead><tr><th>Sort</th><th>Category</th><th>Services</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
          <?php if (empty($categories)): ?>
            <tr><td colspan="5" class="text-center text-slate-500 py-8">No categories created yet.</td></tr>
          <?php else: foreach ($categories as $c): ?>
            <tr>
              <td class="font-mono text-xs"><?= (int)$c['sort_order'] ?></td>
              <td>
                <div class="font-semibold text-slate-900"><?= e($c['name']) ?></div>
                <div class="text-xs text-slate-400"><?= e($c['slug']) ?></div>
              </td>
              <td><?= (int)$c['service_count'] ?></td>
              <td><span class="badge <?= e(status_badge_class((string)$c['status'])) ?>"><?= e($c['status']) ?></span></td>
              <td class="text-right space-x-1.5 whitespace-nowrap">
                <a href="?edit=<?= (int)$c['id'] ?>" class="btn-secondary px-2.5 py-1 text-xs">Edit</a>
                <button type="button"
                        onclick='SMM.confirmAction(<?= e(json_encode([
                            "title" => "Delete Category",
                            "message" => "Delete category \"" . $c["name"] . "\"? Categories with active or historical services cannot be removed.",
                            "actionUrl" => app_url("/admin/categories.php"),
                            "fields" => ["action" => "delete", "id" => (int)$c["id"]],
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
