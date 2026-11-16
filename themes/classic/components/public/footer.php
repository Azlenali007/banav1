<?php
declare(strict_types=1);
$siteName = get_setting('site_name', APP_DEFAULT_NAME);
$footerAbout = get_setting('footer_about', '');
$footerCopy = get_setting('footer_copyright', 'All rights reserved.');
$contactEmail = get_setting('contact_email', '');
?>
<footer class="bg-white border-t border-slate-200 mt-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
      <div class="md:col-span-2">
        <div class="flex items-center gap-2.5 mb-3">
          <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-600 to-purple-800 flex items-center justify-center text-white">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </span>
          <span class="font-bold text-lg text-slate-900"><?= e($siteName) ?></span>
        </div>
        <p class="text-sm text-slate-600 max-w-md leading-relaxed"><?= e($footerAbout) ?></p>
      </div>
      <div>
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3">Platform</h4>
        <ul class="space-y-2 text-sm text-slate-600">
          <li><a href="<?= e(app_url('/login.php')) ?>" class="hover:text-violet-700">Client Sign In</a></li>
          <li><a href="<?= e(app_url('/register.php')) ?>" class="hover:text-violet-700">Create Account</a></li>
          <li><a href="<?= e(app_url('/page.php?slug=about')) ?>" class="hover:text-violet-700">About Us</a></li>
          <li><a href="<?= e(app_url('/page.php?slug=faq')) ?>" class="hover:text-violet-700">FAQ</a></li>
          <li><a href="<?= e(app_url('/page.php?slug=contact')) ?>" class="hover:text-violet-700">Contact</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3">Legal &amp; Support</h4>
        <ul class="space-y-2 text-sm text-slate-600">
          <li><a href="<?= e(app_url('/page.php?slug=terms')) ?>" class="hover:text-violet-700">Terms of Service</a></li>
          <li><a href="<?= e(app_url('/page.php?slug=privacy')) ?>" class="hover:text-violet-700">Privacy Policy</a></li>
          <?php if ($contactEmail !== ''): ?>
            <li><a href="mailto:<?= e($contactEmail) ?>" class="hover:text-violet-700"><?= e($contactEmail) ?></a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="border-t border-slate-100 mt-10 pt-6 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-slate-500">
      <span>&copy; <?= date('Y') ?> <?= e($siteName) ?>. <?= e($footerCopy) ?></span>
      <span>Classic Theme</span>
    </div>
  </div>
</footer>
