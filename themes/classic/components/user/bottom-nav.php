<?php
declare(strict_types=1);
$bottomNavItems = $bottomNavItems ?? [];
$currentScript = current_script_name();
if (empty($bottomNavItems)) {
    return;
}
?>
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t border-slate-200 px-2 py-1.5 flex items-center justify-around shadow-lg user-bottom-nav">
  <?php foreach ($bottomNavItems as $bItem): ?>
    <?php $isActive = ($currentScript === basename($bItem['href'])); ?>
    <a href="<?= e(app_url($bItem['href'])) ?>" class="flex flex-col items-center gap-0.5 px-2 py-1 rounded-lg text-[11px] font-medium <?= $isActive ? 'text-violet-700' : 'text-slate-500' ?>">
      <span class="w-5 h-5"><?= $bItem['icon'] ?></span>
      <span><?= e($bItem['label']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
