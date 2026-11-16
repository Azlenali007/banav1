<?php
declare(strict_types=1);

function is_installed(): bool
{
    if (!is_file(INSTALL_LOCK_FILE) || !is_file(DB_LOCAL_CONFIG_FILE)) {
        return false;
    }
    $cfg = require CONFIG_PATH . '/database.php';
    return !empty($cfg['installed']) && !empty($cfg['database']);
}

function get_all_settings(bool $refresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$refresh) {
        return $cache;
    }
    $cache = [];
    try {
        $rows = Database::fetchAll('SELECT setting_key, setting_value FROM settings');
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable) {
        // Fallback when DB not ready
    }
    return $cache;
}

function get_setting(string $key, mixed $default = ''): mixed
{
    $all = get_all_settings();
    return array_key_exists($key, $all) ? $all[$key] : $default;
}

function set_setting(string $group, string $key, ?string $value): void
{
    Database::execute(
        'INSERT INTO settings (setting_group, setting_key, setting_value, updated_at)
         VALUES (?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE setting_group = VALUES(setting_group), setting_value = VALUES(setting_value), updated_at = NOW()',
        [$group, $key, $value]
    );
    get_all_settings(true);
}

function active_announcements(): array
{
    try {
        return Database::fetchAll(
            'SELECT * FROM announcements
             WHERE is_active = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY created_at DESC LIMIT 5'
        );
    } catch (Throwable) {
        return [];
    }
}

function status_badge_class(string $status): string
{
    return match (strtolower($status)) {
        'completed', 'active', 'answered' => 'badge-success',
        'processing', 'in_progress', 'open' => 'badge-purple',
        'pending', 'requested' => 'badge-warning',
        'partial' => 'badge-info',
        'cancelled', 'failed', 'rejected', 'suspended', 'closed', 'inactive' => 'badge-danger',
        default => 'badge-neutral',
    };
}

function format_datetime(?string $dt): string
{
    if (!$dt) {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts ? date('M d, Y H:i', $ts) : $dt;
}

function paginate_build(int $totalItems, int $currentPage, int $perPage = 20): array
{
    $totalPages = max(1, (int)ceil($totalItems / $perPage));
    $page = max(1, min($currentPage, $totalPages));
    $offset = ($page - 1) * $perPage;
    return [
        'total' => $totalItems,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

function enforce_maintenance_mode(): void
{
    if (get_setting('maintenance_mode', '0') === '1') {
        if (!headers_sent()) {
            http_response_code(503);
        }
        Theme::render('public/maintenance', [
            'siteName' => get_setting('site_name', APP_DEFAULT_NAME),
            'contactEmail' => (string)get_setting('contact_email', ''),
        ]);
        exit;
    }
}
