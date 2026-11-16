<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('settings.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $section = (string)($_POST['section'] ?? '');

    if ($section === 'general') {
        set_setting('general', 'site_name', Validator::cleanString($_POST['site_name'] ?? 'ApexSMM', 100));
        set_setting('general', 'site_tagline', Validator::cleanString($_POST['site_tagline'] ?? '', 200));
        set_setting('general', 'currency_code', strtoupper(Validator::cleanString($_POST['currency_code'] ?? 'INR', 10)));
        set_setting('general', 'currency_symbol', Validator::cleanString($_POST['currency_symbol'] ?? '₹', 10));
        set_setting('general', 'timezone', Validator::cleanString($_POST['timezone'] ?? 'Asia/Kolkata', 60));
        set_setting('general', 'logo_url', Validator::cleanString($_POST['logo_url'] ?? '', 255));
        set_setting('general', 'favicon_url', Validator::cleanString($_POST['favicon_url'] ?? '', 255));
        set_setting('general', 'registration_enabled', !empty($_POST['registration_enabled']) ? '1' : '0');
        set_setting('general', 'maintenance_mode', !empty($_POST['maintenance_mode']) ? '1' : '0');
        Logger::adminActivity((int)$currentAdmin['id'], 'settings.general', 'settings', null, 'Updated general settings');
        Session::setFlash('success', 'General website settings saved.');
    } elseif ($section === 'landing') {
        set_setting('landing', 'hero_badge', Validator::cleanString($_POST['hero_badge'] ?? '', 120));
        set_setting('landing', 'hero_title', Validator::cleanString($_POST['hero_title'] ?? '', 200));
        set_setting('landing', 'hero_subtitle', trim((string)($_POST['hero_subtitle'] ?? '')));
        set_setting('landing', 'why_choose_title', Validator::cleanString($_POST['why_choose_title'] ?? '', 200));
        set_setting('landing', 'why_choose_subtitle', trim((string)($_POST['why_choose_subtitle'] ?? '')));

        $faqJson = trim((string)($_POST['faq_json'] ?? '[]'));
        if (json_decode($faqJson, true) !== null) {
            set_setting('landing', 'faq_json', $faqJson);
        }
        $featJson = trim((string)($_POST['features_json'] ?? '[]'));
        if (json_decode($featJson, true) !== null) {
            set_setting('landing', 'features_json', $featJson);
        }
        Logger::adminActivity((int)$currentAdmin['id'], 'settings.landing', 'settings', null, 'Updated landing page content');
        Session::setFlash('success', 'Landing page content updated.');
    } elseif ($section === 'seo_contact') {
        set_setting('seo', 'meta_title', Validator::cleanString($_POST['meta_title'] ?? '', 200));
        set_setting('seo', 'meta_description', Validator::cleanString($_POST['meta_description'] ?? '', 500));
        set_setting('seo', 'meta_keywords', Validator::cleanString($_POST['meta_keywords'] ?? '', 300));
        set_setting('contact', 'contact_email', Validator::cleanString($_POST['contact_email'] ?? '', 150));
        set_setting('contact', 'contact_phone', Validator::cleanString($_POST['contact_phone'] ?? '', 60));
        set_setting('contact', 'contact_address', Validator::cleanString($_POST['contact_address'] ?? '', 255));
        set_setting('contact', 'support_hours', Validator::cleanString($_POST['support_hours'] ?? '', 120));
        set_setting('footer', 'footer_about', Validator::cleanString($_POST['footer_about'] ?? '', 500));
        set_setting('footer', 'footer_copyright', Validator::cleanString($_POST['footer_copyright'] ?? '', 200));
        Logger::adminActivity((int)$currentAdmin['id'], 'settings.seo_contact', 'settings', null, 'Updated SEO, contact, and footer settings');
        Session::setFlash('success', 'SEO, contact & footer settings saved.');
    } elseif ($section === 'mail') {
        set_setting('mail', 'mail_driver', ($_POST['mail_driver'] ?? 'php_mail') === 'smtp' ? 'smtp' : 'php_mail');
        set_setting('mail', 'mail_from_email', Validator::cleanString($_POST['mail_from_email'] ?? '', 150));
        set_setting('mail', 'mail_from_name', Validator::cleanString($_POST['mail_from_name'] ?? '', 120));
        set_setting('mail', 'smtp_host', Validator::cleanString($_POST['smtp_host'] ?? '', 150));
        set_setting('mail', 'smtp_port', (string)max(1, (int)($_POST['smtp_port'] ?? 587)));
        set_setting('mail', 'smtp_username', Validator::cleanString($_POST['smtp_username'] ?? '', 150));
        set_setting('mail', 'smtp_encryption', in_array($_POST['smtp_encryption'] ?? '', ['tls', 'ssl', 'none'], true) ? $_POST['smtp_encryption'] : 'tls');
        if (!empty($_POST['smtp_password'])) {
            set_setting('mail', 'smtp_password_encrypted', crypto_encrypt((string)$_POST['smtp_password']));
        }
        Logger::adminActivity((int)$currentAdmin['id'], 'settings.mail', 'settings', null, 'Updated email/SMTP settings');
        Session::setFlash('success', 'Email configuration saved.');
    } elseif ($section === 'test_email') {
        $to = trim((string)($_POST['test_to'] ?? ''));
        $res = send_mail($to, 'Test Email from ' . get_setting('site_name', APP_DEFAULT_NAME), '<p>Your email configuration is working properly.</p>');
        Session::setFlash($res['success'] ? 'success' : 'error', $res['success'] ? "Test email dispatched to {$to}." : 'Email send failed: ' . ($res['error'] ?? 'Unknown error'));
    }

    Response::redirect('/admin/settings.php?tab=' . urlencode((string)($_POST['active_tab'] ?? 'general')));
}

$tab = in_array($_GET['tab'] ?? '', ['general', 'landing', 'seo', 'mail', 'cron'], true) ? $_GET['tab'] : 'general';
$secCfg = require CONFIG_PATH . '/security.php';
$cronToken = (string)($secCfg['cron_token'] ?? '');

Theme::render('admin/settings', [
    'tab' => $tab,
    'cronToken' => $cronToken,
], 'admin', [
    'pageTitle' => 'Website Settings',
    'currentAdmin' => $currentAdmin,
]);
