<?php
declare(strict_types=1);
/** @var array $page */
?>
<div class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-12">
  <div class="smm-card p-6 sm:p-10">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-6"><?= e($page['title']) ?></h1>
    <div class="prose prose-slate max-w-none text-sm sm:text-base leading-relaxed space-y-4">
      <?= $page['content'] /* Admin-authored HTML static page */ ?>
    </div>
  </div>
</div>
