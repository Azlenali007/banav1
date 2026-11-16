<?php
declare(strict_types=1);
/** @var string $error */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Change Password</h1>
  <p class="text-sm text-slate-500">Keep your account secure with a strong unique password.</p>
</div>

<?php if ($error !== ''): ?>
  <div class="max-w-xl mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"><?= e($error) ?></div>
<?php endif; ?>

<div class="max-w-xl smm-card p-6">
  <form method="POST" class="space-y-4">
    <?= CSRF::field() ?>
    <div>
      <label class="smm-label">Current Password</label>
      <input type="password" name="current_password" required class="smm-input">
    </div>
    <div>
      <label class="smm-label">New Password (min 8 chars)</label>
      <input type="password" name="new_password" required minlength="8" class="smm-input" autocomplete="new-password">
    </div>
    <div>
      <label class="smm-label">Confirm New Password</label>
      <input type="password" name="confirm_password" required minlength="8" class="smm-input" autocomplete="new-password">
    </div>
    <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Update Password</button>
  </form>
</div>
