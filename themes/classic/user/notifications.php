<?php
declare(strict_types=1);
/** @var array $items */
?>
<div class="flex items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Notifications</h1>
    <p class="text-sm text-slate-500">Order updates, deposit confirmations, support replies, and announcements.</p>
  </div>
  <form method="POST">
    <?= CSRF::field() ?>
    <button type="submit" class="btn-secondary px-4 py-2 text-xs">Mark All Read</button>
  </form>
</div>

<div class="smm-card divide-y divide-slate-100">
  <?php if (empty($items)): ?>
    <div class="p-10 text-center text-sm text-slate-500">No notifications yet.</div>
  <?php else: ?>
    <?php foreach ($items as $n): ?>
      <div class="p-5 flex items-start justify-between gap-4 <?= empty($n['is_read']) ? 'bg-violet-50/40' : '' ?>">
        <div>
          <div class="flex items-center gap-2">
            <?php if (empty($n['is_read'])): ?><span class="w-2 h-2 rounded-full bg-violet-600"></span><?php endif; ?>
            <span class="font-bold text-sm text-slate-900"><?= e($n['title']) ?></span>
          </div>
          <p class="text-sm text-slate-600 mt-1"><?= e($n['message']) ?></p>
          <div class="text-xs text-slate-400 mt-1.5"><?= e(format_datetime($n['created_at'])) ?></div>
        </div>
        <?php if (!empty($n['link'])): ?>
          <a href="<?= e(app_url($n['link'])) ?>" class="btn-secondary px-3 py-1.5 text-xs shrink-0">View</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
