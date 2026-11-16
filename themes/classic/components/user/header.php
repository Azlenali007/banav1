<?php
declare(strict_types=1);
$siteName = get_setting('site_name', APP_DEFAULT_NAME);
$logoUrl = (string)get_setting('logo_url', '');
$currentUser = $currentUser ?? [];
?>
<header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <button type="button" @click="mobileSidebarOpen = !mobileSidebarOpen" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-violet-50 hover:text-violet-700" aria-label="Toggle Menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>

      <a href="<?= e(app_url('/user/dashboard.php')) ?>" class="flex items-center gap-2.5">
        <?php if ($logoUrl !== ''): ?>
          <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" class="h-8 w-auto object-contain">
        <?php else: ?>
          <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-600 to-purple-800 flex items-center justify-center text-white shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </span>
        <?php endif; ?>
        <span class="font-bold text-lg tracking-tight text-slate-900"><?= e($siteName) ?></span>
      </a>
    </div>

    <div class="flex items-center gap-3">
      <a href="<?= e(app_url('/user/add-funds.php')) ?>" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-violet-50 border border-violet-200 text-violet-800 text-sm font-semibold hover:bg-violet-100 transition">
        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        <span><?= e(format_money($currentUser['wallet_balance'] ?? 0)) ?></span>
      </a>
      <?php $unreadNotifs = !empty($currentUser['id']) ? user_unread_notifications_count((int)$currentUser['id']) : 0; ?>
      <a href="<?= e(app_url('/user/notifications.php')) ?>" class="relative p-2 rounded-xl text-slate-500 hover:bg-violet-50 hover:text-violet-700 transition" aria-label="Notifications">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <?php if ($unreadNotifs > 0): ?>
          <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-violet-600 rounded-full ring-2 ring-white"></span>
        <?php endif; ?>
      </a>
      <a href="<?= e(app_url('/user/profile.php')) ?>" class="hidden sm:flex items-center gap-2 pl-2 border-l border-slate-200 hover:text-violet-700">
        <span class="w-8 h-8 rounded-full bg-violet-100 text-violet-700 font-bold text-xs flex items-center justify-center">
          <?= e(strtoupper(substr((string)($currentUser['username'] ?? 'U'), 0, 2))) ?>
        </span>
        <span class="text-sm font-medium text-slate-700"><?= e((string)($currentUser['username'] ?? '')) ?></span>
      </a>
    </div>
  </div>
</header>
