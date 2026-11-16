<?php
declare(strict_types=1);
/** @var string $siteName @var string $contactEmail */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php classic_render_head('Scheduled Maintenance', 'The platform is currently undergoing scheduled maintenance.', 'public'); ?>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-4">
  <div class="smm-card max-w-lg w-full p-8 text-center">
    <div class="w-12 h-12 rounded-2xl bg-violet-100 text-violet-700 flex items-center justify-center mx-auto mb-4">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <span class="badge badge-purple mb-3">HTTP 503 &bull; Maintenance Mode</span>
    <h1 class="text-2xl font-bold text-slate-900 mb-2"><?= e($siteName) ?> is Under Maintenance</h1>
    <p class="text-sm text-slate-600 leading-relaxed mb-6">
      We are performing scheduled updates to keep our infrastructure fast and reliable. Please check back shortly.
    </p>
    <?php if ($contactEmail !== ''): ?>
      <p class="text-xs text-slate-500 mb-4">Urgent inquiries: <a href="mailto:<?= e($contactEmail) ?>" class="text-violet-700 font-semibold hover:underline"><?= e($contactEmail) ?></a></p>
    <?php endif; ?>
    <a href="<?= e(app_url('/admin/login.php')) ?>" class="text-xs font-medium text-slate-400 hover:text-violet-700">Administrator Login &rarr;</a>
  </div>
  <?php classic_render_scripts('public'); ?>
</body>
</html>
