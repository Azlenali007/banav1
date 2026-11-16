<?php
declare(strict_types=1);
/** @var array $page @var string $contactEmail @var string $contactPhone @var string $contactAddress @var string $supportHours */
?>
<div class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-12 space-y-6">
  <div class="smm-card p-6 sm:p-10">
    <span class="text-xs font-bold uppercase tracking-wider text-violet-700">Support Channels</span>
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1 mb-4"><?= e($page['title']) ?></h1>
    <div class="prose prose-slate max-w-none text-sm sm:text-base leading-relaxed space-y-4 mb-6">
      <?= $page['content'] /* Admin-authored HTML static page */ ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-6 border-t border-slate-100 text-sm">
      <?php if ($contactEmail !== ''): ?>
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
          <div class="text-xs text-slate-400">Support Email</div>
          <a href="mailto:<?= e($contactEmail) ?>" class="font-bold text-violet-700 hover:underline mt-0.5 inline-block"><?= e($contactEmail) ?></a>
        </div>
      <?php endif; ?>
      <?php if ($contactPhone !== ''): ?>
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
          <div class="text-xs text-slate-400">Phone</div>
          <div class="font-bold text-slate-800 mt-0.5"><?= e($contactPhone) ?></div>
        </div>
      <?php endif; ?>
      <?php if ($supportHours !== ''): ?>
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
          <div class="text-xs text-slate-400">Support Hours</div>
          <div class="font-bold text-slate-800 mt-0.5"><?= e($supportHours) ?></div>
        </div>
      <?php endif; ?>
      <?php if ($contactAddress !== ''): ?>
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
          <div class="text-xs text-slate-400">Office Address</div>
          <div class="font-bold text-slate-800 mt-0.5"><?= e($contactAddress) ?></div>
        </div>
      <?php endif; ?>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
      <a href="<?= e(app_url('/user/tickets.php')) ?>" class="btn-primary px-5 py-2.5 text-sm">Open Support Ticket</a>
      <a href="<?= e(app_url('/register.php')) ?>" class="btn-secondary px-5 py-2.5 text-sm">Create Account</a>
    </div>
  </div>
</div>
