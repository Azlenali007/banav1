<?php
declare(strict_types=1);

$bootstrapContext = 'user';
require_once __DIR__ . '/includes/bootstrap.php';
enforce_maintenance_mode();

$slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', (string)($_GET['slug'] ?? ''));
if ($slug === '') {
    Response::abort(404, 'Page Not Found', 'The requested page does not exist.');
}

$page = Database::fetch('SELECT * FROM pages WHERE slug = ? AND is_published = 1 LIMIT 1', [$slug]);
if (!$page) {
    Response::abort(404, 'Page Not Found', 'The requested page could not be found or is unpublished.');
}

$template = match ($slug) {
    'about' => 'public/pages/about',
    'contact' => 'public/pages/contact',
    'faq' => 'public/pages/faq',
    'terms' => 'public/pages/terms',
    default => 'public/pages/page',
};

$data = ['page' => $page];
if ($slug === 'about') {
    $data['activeCategoriesCount'] = (int)Database::fetchColumn("SELECT COUNT(*) FROM categories WHERE status = 'active'");
    $data['activeServicesCount'] = (int)Database::fetchColumn("SELECT COUNT(*) FROM services WHERE status = 'active'");
} elseif ($slug === 'contact') {
    $data['contactEmail'] = (string)get_setting('contact_email', '');
    $data['contactPhone'] = (string)get_setting('contact_phone', '');
    $data['contactAddress'] = (string)get_setting('contact_address', '');
    $data['supportHours'] = (string)get_setting('support_hours', '');
} elseif ($slug === 'faq') {
    $faqs = json_decode((string)get_setting('faq_json', '[]'), true);
    $data['faqs'] = is_array($faqs) ? $faqs : [];
}

Theme::render($template, $data, 'public', [
    'pageTitle' => (string)($page['meta_title'] ?: $page['title']),
    'metaDescription' => (string)($page['meta_description'] ?? ''),
]);
