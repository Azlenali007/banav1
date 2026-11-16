<?php
declare(strict_types=1);
$navItems = $navItems ?? [];
$currentScript = current_script_name();
$sidebarStyle = Theme::get('sidebar_style', 'light');
$isPurple = ($sidebarStyle === 'purple');
?>
<div x-show="mobileSidebarOpen" x-cloak @click="mobileSidebarOpen = false" class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden"></div>

<aside :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed lg:static inset-y-0 left-0 z-50 w-64 border-r flex flex-col transition-transform duration-200 ease-out shrink-0 <?= $isPurple ? 'bg-gradient-to-b from-violet-950 to-purple-900 border-purple-900 text-violet-100' : 'bg-white border-slate-200' ?>">
  <div class="p-4 flex items-center justify-between lg:hidden border-b <?= $isPurple ? 'border-purple-800' : 'border-slate-100' ?>">
    <span class="font-bold <?= $isPurple ? 'text-white' : 'text-slate-900' ?>">Menu</span>
    <button type="button" @click="mobileSidebarOpen = false" class="p-1.5 rounded-lg <?= $isPurple ? 'text-violet-200 hover:bg-purple-800' : 'text-slate-500 hover:bg-slate-100' ?>" aria-label="Close Menu">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>
  <nav class="flex-1 overflow-y-auto p-3 space-y-1 pb-20 lg:pb-6">
    <?php foreach ($navItems as $item): ?>
      <?php if (!empty($item['section'])): ?>
        <div class="px-3 pt-4 pb-1 text-[11px] font-bold uppercase tracking-wider <?= $isPurple ? 'text-violet-300/70' : 'text-slate-400' ?>"><?= e($item['section']) ?></div>
      <?php else: ?>
        <?php $isActive = ($currentScript === basename($item['href'])); ?>
        <a href="<?= e(app_url($item['href'])) ?>"
           class="nav-item <?= $isActive ? 'active' : '' ?> <?= $isPurple ? ($isActive ? '!bg-white/20 !text-white' : '!text-violet-100 hover:!bg-white/10 hover:!text-white') : '' ?>">
          <span class="w-5 h-5 shrink-0 <?= $isPurple ? 'text-violet-200' : ($isActive ? 'text-violet-700' : 'text-slate-400') ?>"><?= $item['icon'] ?></span>
          <span class="truncate"><?= e($item['label']) ?></span>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
</aside>
