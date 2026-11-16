<?php
declare(strict_types=1);
$siteName = get_setting('site_name', APP_DEFAULT_NAME);
$logoUrl = (string)get_setting('logo_url', '');
$publicUser = Auth::currentUser();
?>
<header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200" x-data="{ mobileNavOpen: false }">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <button type="button" @click="mobileNavOpen = !mobileNavOpen" class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-violet-50 hover:text-violet-700" aria-label="Toggle Navigation">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <a href="<?= e(app_url('/')) ?>" class="flex items-center gap-2.5">
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

    <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
      <a href="<?= e(app_url('/#services')) ?>" class="hover:text-violet-700 transition">Services</a>
      <a href="<?= e(app_url('/page.php?slug=about')) ?>" class="hover:text-violet-700 transition">About</a>
      <a href="<?= e(app_url('/page.php?slug=faq')) ?>" class="hover:text-violet-700 transition">FAQ</a>
      <a href="<?= e(app_url('/page.php?slug=contact')) ?>" class="hover:text-violet-700 transition">Contact</a>
    </nav>

    <div class="flex items-center gap-2.5">
      <?php if ($publicUser): ?>
        <a href="<?= e(app_url('/user/dashboard.php')) ?>" class="btn-primary px-4 py-2 text-sm">Dashboard</a>
      <?php else: ?>
        <a href="<?= e(app_url('/login.php')) ?>" class="btn-secondary px-4 py-2 text-sm">Sign In</a>
        <a href="<?= e(app_url('/register.php')) ?>" class="btn-primary px-4 py-2 text-sm">Get Started</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Mobile Nav Drawer -->
  <div x-show="mobileNavOpen" x-cloak class="md:hidden border-t border-slate-100 bg-white px-4 py-3 space-y-2 text-sm font-medium text-slate-700">
    <a href="<?= e(app_url('/#services')) ?>" @click="mobileNavOpen = false" class="block py-1.5 hover:text-violet-700">Services</a>
    <a href="<?= e(app_url('/page.php?slug=about')) ?>" class="block py-1.5 hover:text-violet-700">About</a>
    <a href="<?= e(app_url('/page.php?slug=faq')) ?>" class="block py-1.5 hover:text-violet-700">FAQ</a>
    <a href="<?= e(app_url('/page.php?slug=contact')) ?>" class="block py-1.5 hover:text-violet-700">Contact</a>
  </div>
</header>
