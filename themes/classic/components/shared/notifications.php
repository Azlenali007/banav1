<?php
declare(strict_types=1);
$announcements = $announcements ?? [];
if (empty($announcements) || Theme::get('show_announcements', '1') !== '1') {
    return;
}
$compact = !empty($compact);
?>
<?php if ($compact): ?>
  <div class="bg-violet-700 text-white text-xs sm:text-sm py-2 px-4 text-center">
    <span class="font-semibold"><?= e($announcements[0]['title']) ?>:</span>
    <span class="opacity-95"><?= e($announcements[0]['content']) ?></span>
  </div>
<?php else: ?>
  <div class="mb-6 space-y-2.5">
    <?php foreach ($announcements as $ann): ?>
      <div class="smm-card p-4 border-l-4 border-l-violet-600 bg-violet-50/40 flex items-start gap-3">
        <span class="badge badge-purple mt-0.5"><?= e($ann['type']) ?></span>
        <div>
          <div class="font-semibold text-sm text-slate-900"><?= e($ann['title']) ?></div>
          <p class="text-xs sm:text-sm text-slate-600 mt-0.5"><?= e($ann['content']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
