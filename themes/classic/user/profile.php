<?php
declare(strict_types=1);
/** @var array $currentUser */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Account Profile</h1>
  <p class="text-sm text-slate-500">Manage your personal details and view account status.</p>
</div>

<div class="max-w-xl smm-card p-6">
  <form method="POST" class="space-y-4">
    <?= CSRF::field() ?>
    <div>
      <label class="smm-label">Username</label>
      <input type="text" value="<?= e($currentUser['username']) ?>" disabled class="smm-input bg-slate-50 text-slate-500">
    </div>
    <div>
      <label class="smm-label">Email Address</label>
      <input type="email" value="<?= e($currentUser['email']) ?>" disabled class="smm-input bg-slate-50 text-slate-500">
    </div>
    <div>
      <label class="smm-label">Full Name</label>
      <input type="text" name="full_name" value="<?= e($currentUser['full_name']) ?>" required class="smm-input">
    </div>
    <div class="grid grid-cols-2 gap-4 pt-2 text-xs">
      <div class="p-3 rounded-xl bg-slate-50">
        <div class="text-slate-400">Account Status</div>
        <div class="mt-1"><span class="badge <?= e(status_badge_class((string)$currentUser['status'])) ?>"><?= e($currentUser['status']) ?></span></div>
      </div>
      <div class="p-3 rounded-xl bg-slate-50">
        <div class="text-slate-400">Member Since</div>
        <div class="font-semibold text-slate-800 mt-1"><?= e(format_datetime($currentUser['created_at'])) ?></div>
      </div>
    </div>
    <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Save Changes</button>
  </form>
</div>
