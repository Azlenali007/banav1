<?php
declare(strict_types=1);
/** @var array $roles @var array $allPermissions */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Roles &amp; Granular Permissions</h1>
  <p class="text-sm text-slate-500">Define Staff Admin roles and control exact module access.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-4">Create Staff Role</h2>
    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="create_role">
      <div><label class="smm-label">Role Name</label><input type="text" name="name" required class="smm-input" placeholder="e.g. Billing Manager"></div>
      <div><label class="smm-label">Description</label><textarea name="description" rows="2" class="smm-input"></textarea></div>
      <button type="submit" class="btn-primary w-full py-2.5 text-sm">Create Role</button>
    </form>
  </div>

  <div class="lg:col-span-2 space-y-5">
    <?php foreach ($roles as $r): ?>
      <div class="smm-card p-6">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h3 class="font-bold text-slate-900"><?= e($r['name']) ?></h3>
            <p class="text-xs text-slate-500"><?= e($r['description']) ?></p>
          </div>
          <?php if (!empty($r['is_super_admin'])): ?>
            <span class="badge badge-purple">Full Unrestricted Access</span>
          <?php endif; ?>
        </div>

        <?php if (empty($r['is_super_admin'])): ?>
          <form method="POST" class="space-y-4">
            <?= CSRF::field() ?>
            <input type="hidden" name="action" value="save_permissions">
            <input type="hidden" name="role_id" value="<?= (int)$r['id'] ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-2 border-t border-slate-100">
              <?php foreach ($allPermissions as $key => $label): ?>
                <label class="flex items-start gap-2 text-xs text-slate-700 p-2 rounded-lg hover:bg-slate-50">
                  <input type="checkbox" name="permissions[]" value="<?= e($key) ?>" <?= in_array($key, $r['perms'], true) ? 'checked' : '' ?> class="mt-0.5">
                  <span><strong class="block text-slate-900"><?= e($key) ?></strong><?= e($label) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <button type="submit" class="btn-primary px-5 py-2 text-xs">Save Role Permissions</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
