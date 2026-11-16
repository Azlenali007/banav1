<?php
declare(strict_types=1);
/** @var array $classicConfig @var array $themeSettings */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Theme Management</h1>
  <p class="text-sm text-slate-500">Configure and preview the installed Classic White &amp; Purple theme.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="smm-card p-6 space-y-4 h-fit">
    <div class="flex items-center justify-between">
      <h2 class="font-bold text-lg text-slate-900"><?= e($classicConfig['name']) ?> Theme</h2>
      <span class="badge badge-success">Active</span>
    </div>
    <p class="text-xs text-slate-600 leading-relaxed"><?= e($classicConfig['description']) ?></p>
    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 space-y-2">
      <div class="text-xs font-bold text-slate-700">Live Palette Preview</div>
      <div class="flex items-center gap-2">
        <span class="w-8 h-8 rounded-lg shadow-sm" style="background: <?= e($themeSettings['primary_color']) ?>"></span>
        <span class="w-8 h-8 rounded-lg shadow-sm" style="background: <?= e($themeSettings['primary_dark']) ?>"></span>
        <span class="w-8 h-8 rounded-lg shadow-sm" style="background: <?= e($themeSettings['accent_color']) ?>"></span>
        <span class="w-8 h-8 rounded-lg border border-slate-300" style="background: <?= e($themeSettings['surface_bg']) ?>"></span>
      </div>
      <div class="text-[11px] text-slate-500 pt-1">
        Sidebar: <strong><?= ($themeSettings['sidebar_style'] ?? 'light') === 'purple' ? 'Royal Violet' : 'Light White' ?></strong>
        &bull; Radius: <strong><?= e($themeSettings['card_radius'] ?? '14px') ?></strong>
      </div>
    </div>
    <div class="flex gap-2">
      <a href="<?= e(app_url('/')) ?>" target="_blank" class="btn-secondary flex-1 py-2 text-center text-xs">Preview Site</a>
      <form method="POST" class="flex-1">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="activate">
        <button type="submit" class="btn-primary w-full py-2 text-xs">Confirm Active</button>
      </form>
    </div>
  </div>

  <div class="lg:col-span-2 smm-card p-6">
    <h2 class="font-bold text-slate-900 mb-4">Classic Theme Appearance Settings</h2>
    <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="save">
      <div>
        <label class="smm-label">Primary Purple</label>
        <input type="color" name="primary_color" value="<?= e($themeSettings['primary_color']) ?>" class="h-10 w-full rounded-lg border border-slate-300 p-1">
      </div>
      <div>
        <label class="smm-label">Primary Dark Purple</label>
        <input type="color" name="primary_dark" value="<?= e($themeSettings['primary_dark']) ?>" class="h-10 w-full rounded-lg border border-slate-300 p-1">
      </div>
      <div>
        <label class="smm-label">Accent Purple</label>
        <input type="color" name="accent_color" value="<?= e($themeSettings['accent_color']) ?>" class="h-10 w-full rounded-lg border border-slate-300 p-1">
      </div>
      <div>
        <label class="smm-label">Surface Background</label>
        <input type="color" name="surface_bg" value="<?= e($themeSettings['surface_bg']) ?>" class="h-10 w-full rounded-lg border border-slate-300 p-1">
      </div>
      <div>
        <label class="smm-label">Card Border Radius</label>
        <select name="card_radius" class="smm-input">
          <?php foreach (['8px', '12px', '14px', '18px'] as $rad): ?>
            <option value="<?= e($rad) ?>" <?= ($themeSettings['card_radius'] ?? '14px') === $rad ? 'selected' : '' ?>><?= e($rad) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="smm-label">Panel Sidebar Style</label>
        <select name="sidebar_style" class="smm-input">
          <option value="light" <?= ($themeSettings['sidebar_style'] ?? 'light') === 'light' ? 'selected' : '' ?>>Light White</option>
          <option value="purple" <?= ($themeSettings['sidebar_style'] ?? '') === 'purple' ? 'selected' : '' ?>>Royal Violet Gradient</option>
        </select>
      </div>
      <div class="sm:col-span-2 flex items-center pt-2">
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" name="show_announcements" value="1" <?= ($themeSettings['show_announcements'] ?? '1') === '1' ? 'checked' : '' ?>>
          <span>Show Announcement Banner</span>
        </label>
      </div>
      <div class="sm:col-span-2 pt-2">
        <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Save Classic Settings</button>
      </div>
    </form>
  </div>
</div>
