<?php
declare(strict_types=1);
/** @var int $userId @var array $user @var string $error */
?>
<div class="mb-6">
  <a href="<?= e(app_url('/admin/user-view.php?id=' . $userId)) ?>" class="text-xs font-semibold text-violet-700 hover:underline">&larr; Back to User View</a>
  <h1 class="text-2xl font-bold text-slate-900 mt-1">Edit User: <?= e($user['username']) ?></h1>
</div>

<?php if ($error !== ''): ?>
  <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"><?= e($error) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  <div class="smm-card p-6">
    <h2 class="font-bold text-slate-900 mb-4">Account Details &amp; Privileges</h2>
    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="update_profile">
      <div><label class="smm-label">Full Name</label><input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required class="smm-input"></div>
      <div><label class="smm-label">Email Address</label><input type="email" name="email" value="<?= e($user['email']) ?>" required class="smm-input"></div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="smm-label">Account Status</label>
          <select name="status" class="smm-input">
            <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            <option value="pending" <?= $user['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
          </select>
        </div>
        <div>
          <label class="smm-label">Custom Rate Multiplier</label>
          <input type="number" step="0.01" min="0.1" max="5" name="custom_rate_multiplier" value="<?= e($user['custom_rate_multiplier']) ?>" class="smm-input">
        </div>
      </div>
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="api_enabled" value="1" <?= !empty($user['api_enabled']) ? 'checked' : '' ?> class="rounded border-slate-300 text-violet-600">
        <span>Allow API v1 Access</span>
      </label>
      <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Save Changes</button>
    </form>
  </div>

  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-4">Set New User Password</h2>
    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="change_password">
      <div>
        <label class="smm-label">New Password (min 8 chars)</label>
        <input type="password" name="new_password" required minlength="8" class="smm-input" autocomplete="new-password">
      </div>
      <button type="submit" class="btn-secondary px-5 py-2.5 text-sm">Update User Password</button>
    </form>
  </div>
</div>
