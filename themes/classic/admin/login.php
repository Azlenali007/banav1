<?php
declare(strict_types=1);
/** @var string $siteName @var string $error @var string $identity */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php classic_render_head('Admin Sign In', '', 'admin'); ?>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-4">
  <div class="smm-card max-w-md w-full p-8">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-purple-800 text-white flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
      </div>
      <div>
        <h1 class="text-xl font-bold text-slate-900"><?= e($siteName) ?> Admin</h1>
        <p class="text-xs text-slate-500">Protected Administrative Portal</p>
      </div>
    </div>

    <?php if ($error !== ''): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <div>
        <label class="smm-label">Admin Username or Email</label>
        <input type="text" name="identity" value="<?= e($identity) ?>" required autofocus class="smm-input">
      </div>
      <div>
        <label class="smm-label">Password</label>
        <input type="password" name="password" required class="smm-input">
      </div>
      <button type="submit" class="btn-primary w-full py-2.5 text-sm">Sign In to Admin Panel</button>
    </form>

    <div class="mt-6 text-center">
      <a href="<?= e(app_url('/')) ?>" class="text-xs text-slate-500 hover:text-violet-700">&larr; Back to Public Website</a>
    </div>
  </div>
  <?php classic_render_scripts('admin'); ?>
</body>
</html>
