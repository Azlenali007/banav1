<?php
declare(strict_types=1);
$siteName = get_setting('site_name', APP_DEFAULT_NAME);
$logoUrl = (string)get_setting('logo_url', '');
$currentAdmin = $currentAdmin ?? [];
?>
<header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <button type="button" @click="mobileSidebarOpen = !mobileSidebarOpen" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-violet-50 hover:text-violet-700" aria-label="Toggle Menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>

      <a href="<?= e(app_url('/admin/dashboard.php')) ?>" class="flex items-center gap-2.5">
        <?php if ($logoUrl !== ''): ?>
          <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" class="h-8 w-auto object-contain">
        <?php else: ?>
          <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-600 to-purple-800 flex items-center justify-center text-white shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </span>
        <?php endif; ?>
        <span class="font-bold text-lg tracking-tight text-slate-900"><?= e($siteName) ?></span>
        <span class="hidden sm:inline-flex px-2 py-0.5 text-xs font-semibold rounded-md bg-violet-100 text-violet-800">ADMIN</span>
      </a>
    </div>

    <div class="flex items-center gap-3">
      <a href="<?= e(app_url('/')) ?>" target="_blank" rel="noopener noreferrer" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50">
        <span>View Site</span>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
      </a>
      <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
        <span class="w-8 h-8 rounded-full bg-violet-700 text-white font-bold text-xs flex items-center justify-center">
          <?= e(strtoupper(substr((string)($currentAdmin['username'] ?? 'AD'), 0, 2))) ?>
        </span>
        <div class="hidden sm:block leading-tight">
          <div class="text-xs font-semibold text-slate-800"><?= e((string)($currentAdmin['full_name'] ?? 'Admin')) ?></div>
          <div class="text-[11px] text-violet-600 font-medium"><?= e((string)($currentAdmin['role_name'] ?? 'Staff')) ?></div>
        </div>
      </div>
    </div>
  </div>
</header>
