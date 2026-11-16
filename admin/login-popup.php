<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('popup.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? 'save');

    if ($action === 'reset_views') {
        Database::execute('DELETE FROM login_popup_views');
        Logger::adminActivity((int)$currentAdmin['id'], 'popup.reset_views', 'popup', null, 'Cleared post-login popup view history');
        Session::setFlash('success', 'Popup view history cleared. The popup can be shown again.');
        Response::redirect('/admin/login-popup.php');
    }

    $imageUrl = trim((string)($_POST['image_url'] ?? ''));
    $waUrl = trim((string)($_POST['whatsapp_url'] ?? ''));
    $tgUrl = trim((string)($_POST['telegram_url'] ?? ''));

    foreach ([
        'Image URL' => $imageUrl,
        'WhatsApp URL' => $waUrl,
        'Telegram URL' => $tgUrl,
    ] as $label => $url) {
        if ($url !== '' && !Validator::isUrl($url)) {
            Session::setFlash('error', $label . ' must be a valid http(s) URL.');
            Response::redirect('/admin/login-popup.php');
        }
    }

    $frequency = (string)($_POST['frequency'] ?? 'once_per_session');
    if (!array_key_exists($frequency, LoginPopup::frequencyOptions())) {
        $frequency = 'once_per_session';
    }

    set_setting('popup', 'login_popup_enabled', !empty($_POST['enabled']) ? '1' : '0');
    set_setting('popup', 'login_popup_title', Validator::cleanString($_POST['title'] ?? '', 140));
    set_setting('popup', 'login_popup_message', trim((string)($_POST['message'] ?? '')));
    set_setting('popup', 'login_popup_image_url', $imageUrl);
    set_setting('popup', 'login_popup_whatsapp_enabled', !empty($_POST['whatsapp_enabled']) ? '1' : '0');
    set_setting('popup', 'login_popup_whatsapp_url', $waUrl);
    set_setting('popup', 'login_popup_whatsapp_label', Validator::cleanString($_POST['whatsapp_label'] ?? 'Join WhatsApp', 80));
    set_setting('popup', 'login_popup_telegram_enabled', !empty($_POST['telegram_enabled']) ? '1' : '0');
    set_setting('popup', 'login_popup_telegram_url', $tgUrl);
    set_setting('popup', 'login_popup_telegram_label', Validator::cleanString($_POST['telegram_label'] ?? 'Join Telegram', 80));
    set_setting('popup', 'login_popup_frequency', $frequency);
    set_setting('popup', 'login_popup_frequency_days', (string)max(1, (int)($_POST['frequency_days'] ?? 7)));
    set_setting('popup', 'login_popup_delay_seconds', (string)max(0, min(60, (int)($_POST['delay_seconds'] ?? 0))));

    Logger::adminActivity((int)$currentAdmin['id'], 'popup.save', 'settings', null, 'Updated post-login popup settings');
    Session::setFlash('success', 'Post-login popup settings saved.');
    Response::redirect('/admin/login-popup.php');
}

Theme::render('admin/login-popup', [
    'frequencyOptions' => LoginPopup::frequencyOptions(),
    'views' => (int)Database::fetchColumn('SELECT COUNT(*) FROM login_popup_views'),
], 'admin', [
    'pageTitle' => 'Post-login Popup',
    'currentAdmin' => $currentAdmin,
]);
