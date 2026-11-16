<?php
declare(strict_types=1);

$bootstrapContext = 'user';
require_once __DIR__ . '/includes/bootstrap.php';
enforce_maintenance_mode();

$siteName = (string)get_setting('site_name', APP_DEFAULT_NAME);
$features = json_decode((string)get_setting('features_json', '[]'), true);
if (!is_array($features)) {
    $features = [];
}
$faqs = json_decode((string)get_setting('faq_json', '[]'), true);
if (!is_array($faqs)) {
    $faqs = [];
}

$categories = Database::fetchAll(
    "SELECT c.id, c.name, c.description, COUNT(s.id) AS service_count, MIN(s.rate_per_1000) AS starting_rate
     FROM categories c
     INNER JOIN services s ON s.category_id = c.id AND s.status = 'active'
     WHERE c.status = 'active'
     GROUP BY c.id
     ORDER BY c.sort_order ASC, c.id ASC
     LIMIT 8"
);

Theme::render('public/index', [
    'currentUser' => Auth::currentUser(),
    'heroBadge' => (string)get_setting('hero_badge', 'Automated Order Infrastructure'),
    'heroTitle' => (string)get_setting('hero_title', 'Scale Social Campaigns With Precision & Speed'),
    'heroSubtitle' => (string)get_setting('hero_subtitle', 'A transparent, API-driven social media marketing panel built for agencies, creators, and resellers.'),
    'whyTitle' => (string)get_setting('why_choose_title', 'Engineered for Reliability'),
    'whySubtitle' => (string)get_setting('why_choose_subtitle', 'Every order, wallet ledger entry, and provider sync is tracked with database-level integrity.'),
    'features' => $features,
    'faqs' => $faqs,
    'categories' => $categories,
    'contactEmail' => (string)get_setting('contact_email', ''),
    'contactPhone' => (string)get_setting('contact_phone', ''),
    'contactAddress' => (string)get_setting('contact_address', ''),
    'supportHours' => (string)get_setting('support_hours', ''),
], 'public', [
    'pageTitle' => (string)get_setting('meta_title', $siteName),
    'metaDescription' => (string)get_setting('meta_description', ''),
    'announcements' => active_announcements(),
]);
