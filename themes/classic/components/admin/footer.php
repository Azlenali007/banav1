<?php
declare(strict_types=1);
$siteName = get_setting('site_name', APP_DEFAULT_NAME);
$footerCopy = get_setting('footer_copyright', 'All rights reserved.');
?>
<footer class="mt-auto py-5 px-4 sm:px-6 text-xs text-slate-400 border-t border-slate-200/70 bg-white/60">
  <div class="flex flex-col sm:flex-row justify-between items-center gap-2">
    <span>&copy; <?= date('Y') ?> <?= e($siteName) ?>. <?= e($footerCopy) ?></span>
    <span>Classic Theme &bull; Admin Portal</span>
  </div>
</footer>
