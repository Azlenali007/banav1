<?php
declare(strict_types=1);
/** @var string $error @var string $message */
?>
<div class="flex-1 flex items-center justify-center px-4 py-12">
  <div class="smm-card max-w-md w-full p-7 sm:p-8">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">Forgot Password</h1>
    <p class="text-sm text-slate-500 mb-6">Enter your registered email address to receive a reset link.</p>

    <?php if ($error !== ''): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($message !== ''): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm"><?= e($message) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <div>
        <label class="smm-label">Email Address</label>
        <input type="email" name="email" required class="smm-input" placeholder="you@example.com">
      </div>
      <button type="submit" class="btn-primary w-full py-2.5 text-sm">Send Reset Link</button>
    </form>
    <p class="text-center text-sm text-slate-600 mt-6">
      <a href="<?= e(app_url('/login.php')) ?>" class="font-semibold text-violet-700 hover:underline">&larr; Back to Sign In</a>
    </p>
  </div>
</div>
