<?php
declare(strict_types=1);
/** @var array $page @var array $faqs */
?>
<div class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-12 space-y-6">
  <div class="smm-card p-6 sm:p-10">
    <span class="text-xs font-bold uppercase tracking-wider text-violet-700">Knowledge Base</span>
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1 mb-4"><?= e($page['title']) ?></h1>
    <div class="prose prose-slate max-w-none text-sm sm:text-base leading-relaxed space-y-4">
      <?= $page['content'] /* Admin-authored HTML static page */ ?>
    </div>
  </div>

  <?php if (!empty($faqs)): ?>
    <div class="space-y-3" x-data="{ openIdx: 0 }">
      <?php foreach ($faqs as $idx => $faq): ?>
        <div class="smm-card overflow-hidden">
          <button type="button" @click="openIdx = openIdx === <?= (int)$idx ?> ? -1 : <?= (int)$idx ?>" class="w-full px-6 py-4 text-left flex items-center justify-between gap-4 font-semibold text-slate-900 hover:bg-violet-50/40">
            <span><?= e($faq['q'] ?? '') ?></span>
            <svg class="w-5 h-5 text-violet-600 transition-transform" :class="openIdx === <?= (int)$idx ?> ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div x-show="openIdx === <?= (int)$idx ?>" x-cloak class="px-6 pb-5 text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
            <?= e($faq['a'] ?? '') ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
