<?php
declare(strict_types=1);
$pageTitle = (string)($pageTitle ?? 'User Panel');
$metaDescription = (string)($metaDescription ?? '');
$currentUser = $currentUser ?? [];
$navItems = $navItems ?? user_nav_items();
$bottomNavItems = $bottomNavItems ?? user_bottom_nav_items();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php classic_render_head($pageTitle, $metaDescription, 'user'); ?>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col" x-data="{ mobileSidebarOpen: false }">
  <?php Theme::component('user/header', ['currentUser' => $currentUser]); ?>
  <div class="flex-1 flex max-w-full">
    <?php Theme::component('user/sidebar', ['navItems' => $navItems]); ?>
    <main class="flex-1 min-w-0 flex flex-col pb-20 lg:pb-0">
      <div class="p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto flex-1">
        <?php Theme::component('shared/alerts'); ?>
        <?= $content ?? '' ?>
      </div>
      <?php Theme::component('user/footer'); ?>
    </main>
  </div>
  <?php Theme::component('user/bottom-nav', ['bottomNavItems' => $bottomNavItems]); ?>
  <?php Theme::component('shared/modals'); ?>
  <?php if (!empty($currentUser['id'])): ?>
    <?php Theme::component('user/login-popup', ['popup' => LoginPopup::payload((int)$currentUser['id'])]); ?>
  <?php endif; ?>
  <?php classic_render_scripts('user'); ?>
</body>
</html>
