<?php
declare(strict_types=1);
/** @var string $error @var string $identity */
?>
<div class="flex-1 flex items-center justify-center px-4 py-12">
  <div class="smm-card max-w-md w-full p-7 sm:p-8">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-900">Welcome back</h1>
      <p class="text-sm text-slate-500 mt-1">Sign in to manage orders, wallet balance, and API access.</p>
    </div>

    <?php Theme::component('shared/alerts'); ?>
    <?php if ($error !== ''): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <div>
        <label class="smm-label">Username or Email</label>
        <input type="text" name="identity" value="<?= e($identity) ?>" required autofocus class="smm-input" placeholder="you@example.com">
      </div>
      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="smm-label mb-0">Password</label>
          <a href="<?= e(app_url('/forgot-password.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">Forgot password?</a>
        </div>
        <input type="password" name="password" required class="smm-input" placeholder="••••••••">
      </div>
      <button type="submit" class="btn-primary w-full py-2.5 text-sm">Sign In</button>
    </form>

    <p class="text-center text-sm text-slate-600 mt-6">
      Don't have an account? <a href="<?= e(app_url('/register.php')) ?>" class="font-semibold text-violet-700 hover:underline">Create account</a>
    </p>
  </div>
</div>
