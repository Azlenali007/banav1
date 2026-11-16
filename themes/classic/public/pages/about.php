<?php
declare(strict_types=1);
/** @var array $page @var int $activeCategoriesCount @var int $activeServicesCount */
?>
<div class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-12 space-y-6">
  <div class="smm-card p-6 sm:p-10">
    <span class="text-xs font-bold uppercase tracking-wider text-violet-700">About Platform</span>
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1 mb-6"><?= e($page['title']) ?></h1>
    <div class="prose prose-slate max-w-none text-sm sm:text-base leading-relaxed space-y-4">
      <?= $page['content'] /* Admin-authored HTML static page */ ?>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="smm-card p-5">
      <div class="text-xs text-slate-500">Active Service Categories</div>
      <div class="text-2xl font-extrabold text-violet-700 mt-1"><?= (int)$activeCategoriesCount ?></div>
    </div>
    <div class="smm-card p-5">
      <div class="text-xs text-slate-500">Active Automated Services</div>
      <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$activeServicesCount ?></div>
    </div>
  </div>
</div>
