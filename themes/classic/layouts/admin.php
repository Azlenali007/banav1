<?php
declare(strict_types=1);
$pageTitle = (string)($pageTitle ?? 'Admin Panel');
$metaDescription = (string)($metaDescription ?? '');
$currentAdmin = $currentAdmin ?? [];
$navItems = $navItems ?? admin_nav_items($currentAdmin);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php classic_render_head('Admin: ' . $pageTitle, $metaDescription, 'admin'); ?>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col" x-data="{ mobileSidebarOpen: false }">
  <?php Theme::component('admin/header', ['currentAdmin' => $currentAdmin]); ?>
  <div class="flex-1 flex max-w-full">
    <?php Theme::component('admin/sidebar', ['navItems' => $navItems]); ?>
    <main class="flex-1 min-w-0 flex flex-col">
      <div class="p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto flex-1">
        <?php Theme::component('shared/alerts'); ?>
        <?= $content ?? '' ?>
      </div>
      <?php Theme::component('admin/footer'); ?>
    </main>
  </div>
  <?php Theme::component('shared/modals'); ?>
  <?php classic_render_scripts('admin'); ?>
</body>
</html>
