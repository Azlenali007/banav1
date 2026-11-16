<?php
declare(strict_types=1);

class Theme
{
    private static ?array $settingsCache = null;

    public static function active(): string
    {
        $slug = get_setting('active_theme', DEFAULT_THEME);
        $clean = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$slug);
        if ($clean === '' || !is_dir(THEMES_PATH . '/' . $clean)) {
            return DEFAULT_THEME;
        }
        return $clean;
    }

    public static function path(string $relative = ''): string
    {
        $base = THEMES_PATH . '/' . self::active();
        if ($relative === '') {
            return $base;
        }
        $clean = ltrim(str_replace(['..', "\0"], '', $relative), '/');
        return $base . '/' . $clean;
    }

    public static function asset(string $relative): string
    {
        $clean = ltrim(str_replace(['..', "\0"], '', $relative), '/');
        return app_url('/themes/' . self::active() . '/assets/' . $clean);
    }

    public static function settings(): array
    {
        if (self::$settingsCache !== null) {
            return self::$settingsCache;
        }
        $defaults = [
            'primary_color' => '#7C3AED',
            'primary_dark' => '#5B21B6',
            'accent_color' => '#A855F7',
            'surface_bg' => '#F8FAFC',
            'card_radius' => '14px',
            'show_announcements' => '1',
            'sidebar_style' => 'light',
        ];
        try {
            $rows = Database::fetchAll('SELECT setting_key, setting_value FROM theme_settings WHERE theme_slug = ?', [self::active()]);
            foreach ($rows as $row) {
                $defaults[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable) {
            // fallback to defaults
        }
        self::$settingsCache = $defaults;
        return self::$settingsCache;
    }

    public static function get(string $key, string $default = ''): string
    {
        $all = self::settings();
        return (string)($all[$key] ?? $default);
    }

    public static function component(string $name, array $vars = []): void
    {
        $clean = trim(str_replace(['..', "\0", '.php'], '', $name), '/');
        if ($clean === '') {
            return;
        }
        $candidates = [
            self::path('components/' . $clean . '.php'),
            self::path('components/shared/' . $clean . '.php'),
        ];
        $file = null;
        foreach ($candidates as $cand) {
            if (is_file($cand)) {
                $file = $cand;
                break;
            }
        }
        if ($file === null) {
            Logger::error('Theme::component', "Missing theme component: {$clean}");
            return;
        }
        extract($vars, EXTR_SKIP);
        require $file;
    }

    public static function render(string $template, array $data = [], ?string $layout = null, array $layoutVars = []): void
    {
        $themeBootstrap = self::path('theme.php');
        if (is_file($themeBootstrap)) {
            require_once $themeBootstrap;
        }

        $cleanTpl = trim(str_replace(['..', "\0", '.php'], '', $template), '/');
        $tplFile = self::path($cleanTpl . '.php');
        if (!is_file($tplFile)) {
            Logger::error('Theme::render', "Missing theme template: {$tplFile}");
            Response::abort(500, 'Template Error', 'The requested Classic Theme view file could not be loaded.');
        }

        extract($layoutVars, EXTR_SKIP);
        extract($data, EXTR_SKIP);

        if ($layout === null) {
            require $tplFile;
            return;
        }

        $cleanLayout = trim(str_replace(['..', "\0", '.php'], '', $layout), '/');
        $layoutFile = self::path('layouts/' . $cleanLayout . '.php');
        if (!is_file($layoutFile)) {
            Logger::error('Theme::render', "Missing theme layout: {$layoutFile}");
            Response::abort(500, 'Layout Error', 'The requested Classic Theme layout file could not be loaded.');
        }

        ob_start();
        try {
            require $tplFile;
            $content = ob_get_clean();
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }

        require $layoutFile;
    }
}
