<?php
declare(strict_types=1);
/** @var ?array $editStaff @var array $roles @var array $staffList */
?>
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Staff Admin Management</h1>
    <p class="text-sm text-slate-500">Assign Super Admin or restricted Staff Admin roles with module-level RBAC.</p>
  </div>
  <a href="<?= e(app_url('/admin/roles.php')) ?>" class="btn-secondary px-4 py-2 text-sm">Manage Roles &amp; Permissions</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-4"><?= $editStaff ? 'Edit Staff #' . (int)$editStaff['id'] : 'Add Staff Admin' ?></h2>
    <form method="POST" class="space-y-3.5">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($editStaff['id'] ?? 0) ?>">
      <div><label class="smm-label">Full Name</label><input type="text" name="full_name" value="<?= e($editStaff['full_name'] ?? '') ?>" required class="smm-input"></div>
      <div><label class="smm-label">Username</label><input type="text" name="username" value="<?= e($editStaff['username'] ?? '') ?>" required pattern="[a-zA-Z0-9_]{3,40}" class="smm-input"></div>
      <div><label class="smm-label">Email</label><input type="email" name="email" value="<?= e($editStaff['email'] ?? '') ?>" required class="smm-input"></div>
      <div>
        <label class="smm-label">Role</label>
        <select name="role_id" class="smm-input">
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= (int)($editStaff['role_id'] ?? 2) === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="smm-label">Status</label>
        <select name="status" class="smm-input">
          <option value="active" <?= ($editStaff['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="suspended" <?= ($editStaff['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
        </select>
      </div>
      <div>
        <label class="smm-label">Password <?= $editStaff ? '(Leave blank to keep)' : '' ?></label>
        <input type="password" name="password" <?= $editStaff ? '' : 'required' ?> minlength="8" class="smm-input" autocomplete="new-password">
      </div>
      <div class="flex gap-2">
        <button type="submit" class="btn-primary flex-1 py-2.5 text-sm"><?= $editStaff ? 'Update Staff' : 'Create Staff' ?></button>
        <?php if ($editStaff): ?><a href="<?= e(app_url('/admin/staff.php')) ?>" class="btn-secondary px-4 py-2.5 text-sm">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="lg:col-span-2 smm-card overflow-hidden">
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead><tr><th>Admin</th><th>Role</th><th>Last Login</th><th>Status</th><th class="text-right">Action</th></tr></thead>
        <tbody>
          <?php foreach ($staffList as $s): ?>
            <tr>
              <td>
                <div class="font-semibold text-slate-900"><?= e($s['full_name']) ?> (@<?= e($s['username']) ?>)</div>
                <div class="text-xs text-slate-400"><?= e($s['email']) ?></div>
              </td>
              <td><span class="badge <?= !empty($s['is_super_admin']) ? 'badge-purple' : 'badge-info' ?>"><?= e($s['role_name']) ?></span></td>
              <td class="text-xs text-slate-500"><?= e(format_datetime($s['last_login_at'])) ?></td>
              <td><span class="badge <?= e(status_badge_class((string)$s['status'])) ?>"><?= e($s['status']) ?></span></td>
              <td class="text-right"><a href="?edit=<?= (int)$s['id'] ?>" class="btn-secondary px-3 py-1 text-xs">Edit</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
