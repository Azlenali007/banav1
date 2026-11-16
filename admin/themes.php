<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('themes.manage');

$classicConfig = require THEMES_PATH . '/classic/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? 'save');

    if ($action === 'activate') {
        set_setting('general', 'active_theme', 'classic');
        Logger::adminActivity((int)$currentAdmin['id'], 'theme.activate', 'theme', 'classic', 'Activated Classic theme');
        Session::setFlash('success', 'Classic theme is active.');
    } elseif ($action === 'save') {
        $sidebarStyle = in_array($_POST['sidebar_style'] ?? '', ['light', 'purple'], true) ? $_POST['sidebar_style'] : 'light';
        $fields = [
            'primary_color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)($_POST['primary_color'] ?? '')) ? $_POST['primary_color'] : '#7C3AED',
            'primary_dark' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)($_POST['primary_dark'] ?? '')) ? $_POST['primary_dark'] : '#5B21B6',
            'accent_color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)($_POST['accent_color'] ?? '')) ? $_POST['accent_color'] : '#A855F7',
            'surface_bg' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)($_POST['surface_bg'] ?? '')) ? $_POST['surface_bg'] : '#F8FAFC',
            'card_radius' => in_array($_POST['card_radius'] ?? '', ['8px', '12px', '14px', '18px'], true) ? $_POST['card_radius'] : '14px',
            'sidebar_style' => $sidebarStyle,
            'show_announcements' => !empty($_POST['show_announcements']) ? '1' : '0',
        ];
        foreach ($fields as $k => $v) {
            Database::execute(
                "INSERT INTO theme_settings (theme_slug, setting_key, setting_value, updated_at) VALUES ('classic', ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()",
                [$k, $v]
            );
        }
        Logger::adminActivity((int)$currentAdmin['id'], 'theme.configure', 'theme', 'classic', 'Updated Classic theme appearance settings');
        Session::setFlash('success', 'Classic theme configuration saved.');
    }
    Response::redirect('/admin/themes.php');
}

Theme::render('admin/themes', [
    'classicConfig' => $classicConfig,
    'themeSettings' => Theme::settings(),
], 'admin', [
    'pageTitle' => 'Theme Management',
    'currentAdmin' => $currentAdmin,
]);
