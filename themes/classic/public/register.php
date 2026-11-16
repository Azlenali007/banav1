<?php
declare(strict_types=1);
/** @var bool $registrationEnabled @var string $error @var string $fullName @var string $username @var string $email */
?>
<div class="flex-1 flex items-center justify-center px-4 py-12">
  <div class="smm-card max-w-md w-full p-7 sm:p-8">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-900">Create your account</h1>
      <p class="text-sm text-slate-500 mt-1">Get instant access to services, wallet funding, and order tracking.</p>
    </div>

    <?php if (!$registrationEnabled): ?>
      <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
        New registrations are currently closed. Please contact support for assistance.
      </div>
    <?php else: ?>
      <?php if ($error !== ''): ?>
        <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <?= CSRF::field() ?>
        <div>
          <label class="smm-label">Full Name</label>
          <input type="text" name="full_name" value="<?= e($fullName) ?>" required class="smm-input" placeholder="Alex Morgan">
        </div>
        <div>
          <label class="smm-label">Username</label>
          <input type="text" name="username" value="<?= e($username) ?>" required pattern="[a-zA-Z0-9_]{3,40}" class="smm-input" placeholder="alex_smm">
        </div>
        <div>
          <label class="smm-label">Email Address</label>
          <input type="email" name="email" value="<?= e($email) ?>" required class="smm-input" placeholder="you@example.com">
        </div>
        <div>
          <label class="smm-label">Password (min 8 characters)</label>
          <input type="password" name="password" required minlength="8" class="smm-input" autocomplete="new-password">
        </div>
        <div>
          <label class="smm-label">Confirm Password</label>
          <input type="password" name="password_confirm" required minlength="8" class="smm-input" autocomplete="new-password">
        </div>
        <button type="submit" class="btn-primary w-full py-2.5 text-sm">Create Account</button>
      </form>
    <?php endif; ?>

    <p class="text-center text-sm text-slate-600 mt-6">
      Already registered? <a href="<?= e(app_url('/login.php')) ?>" class="font-semibold text-violet-700 hover:underline">Sign in</a>
    </p>
  </div>
</div>
