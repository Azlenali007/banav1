<?php
declare(strict_types=1);
/** @var string $token @var string $error */
?>
<div class="flex-1 flex items-center justify-center px-4 py-12">
  <div class="smm-card max-w-md w-full p-7 sm:p-8">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Reset Password</h1>
    <p class="text-sm text-slate-500 mb-6">Choose a strong new password for your account.</p>

    <?php if ($error !== ''): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div>
        <label class="smm-label">New Password (min 8 characters)</label>
        <input type="password" name="password" required minlength="8" class="smm-input" autocomplete="new-password">
      </div>
      <div>
        <label class="smm-label">Confirm New Password</label>
        <input type="password" name="password_confirm" required minlength="8" class="smm-input" autocomplete="new-password">
      </div>
      <button type="submit" class="btn-primary w-full py-2.5 text-sm">Update Password</button>
    </form>
  </div>
</div>
