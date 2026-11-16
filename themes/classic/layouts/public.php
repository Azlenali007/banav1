<?php
declare(strict_types=1);
$pageTitle = (string)($pageTitle ?? get_setting('site_name', APP_DEFAULT_NAME));
$metaDescription = (string)($metaDescription ?? '');
$announcements = $announcements ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php classic_render_head($pageTitle, $metaDescription, 'public'); ?>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col">
  <?php Theme::component('public/header'); ?>
  <?php if (!empty($announcements)): ?>
    <?php Theme::component('shared/notifications', ['announcements' => $announcements, 'compact' => true]); ?>
  <?php endif; ?>

  <main class="flex-1 flex flex-col">
    <?= $content ?? '' ?>
  </main>

  <?php Theme::component('public/footer'); ?>
  <?php Theme::component('shared/modals'); ?>
  <?php classic_render_scripts('public'); ?>
</body>
</html>
