<?php
declare(strict_types=1);

/**
 * Post-login WhatsApp / Telegram popup. Frequency is enforced with a
 * combination of the login session flag and persisted view history.
 */
class LoginPopup
{
    public const SESSION_PENDING = 'login_popup_pending';
    public const SESSION_SHOWN = 'login_popup_shown';

    public static function isEnabled(): bool
    {
        return get_setting('login_popup_enabled', '0') === '1';
    }

    public static function config(): array
    {
        return [
            'title' => (string)get_setting('login_popup_title', 'Join our community'),
            'message' => (string)get_setting('login_popup_message', ''),
            'image_url' => self::safeUrl((string)get_setting('login_popup_image_url', '')),
            'whatsapp_enabled' => get_setting('login_popup_whatsapp_enabled', '1') === '1',
            'whatsapp_url' => self::safeUrl((string)get_setting('login_popup_whatsapp_url', '')),
            'whatsapp_label' => (string)get_setting('login_popup_whatsapp_label', 'Join WhatsApp'),
            'telegram_enabled' => get_setting('login_popup_telegram_enabled', '1') === '1',
            'telegram_url' => self::safeUrl((string)get_setting('login_popup_telegram_url', '')),
            'telegram_label' => (string)get_setting('login_popup_telegram_label', 'Join Telegram'),
            'frequency' => self::frequency(),
            'frequency_days' => max(1, (int)get_setting('login_popup_frequency_days', '7')),
            'delay_seconds' => max(0, min(60, (int)get_setting('login_popup_delay_seconds', '0'))),
        ];
    }

    public static function frequency(): string
    {
        $allowed = array_keys(self::frequencyOptions());
        $freq = (string)get_setting('login_popup_frequency', 'once_per_session');
        return in_array($freq, $allowed, true) ? $freq : 'once_per_session';
    }

    public static function frequencyOptions(): array
    {
        return [
            'every_login' => 'Every login',
            'once_per_session' => 'Once per browser session',
            'once_per_day' => 'Once per day',
            'once_per_days' => 'Once every N days',
            'once_ever' => 'Only once ever',
        ];
    }

    /**
     * Mark the current login as eligible to see the popup. Called from Auth::loginUser.
     */
    public static function markLogin(): void
    {
        Session::set(self::SESSION_PENDING, true);
        Session::remove(self::SESSION_SHOWN);
    }

    public static function shouldShow(int $userId): bool
    {
        if ($userId <= 0 || !self::isEnabled() || !Session::get(self::SESSION_PENDING)) {
            return false;
        }

        $config = self::config();
        $hasCta = ($config['whatsapp_enabled'] && $config['whatsapp_url'] !== '')
            || ($config['telegram_enabled'] && $config['telegram_url'] !== '');
        if (!$hasCta && trim((string)$config['message']) === '') {
            return false;
        }

        $frequency = $config['frequency'];
        if ($frequency === 'every_login') {
            return true;
        }
        if (Session::get(self::SESSION_SHOWN)) {
            return false;
        }

        $lastShown = self::lastShownAt($userId);
        if ($lastShown === null) {
            return true;
        }

        return match ($frequency) {
            'once_ever' => false,
            'once_per_day' => strtotime($lastShown) < strtotime('today'),
            'once_per_days' => (time() - strtotime($lastShown)) >= ($config['frequency_days'] * 86400),
            default => true,
        };
    }

    public static function markShown(int $userId): void
    {
        Session::set(self::SESSION_SHOWN, true);
        Session::set(self::SESSION_PENDING, false);
        if ($userId <= 0) {
            return;
        }
        try {
            Database::insert('INSERT INTO login_popup_views (user_id, shown_at) VALUES (?, NOW())', [$userId]);
        } catch (Throwable $e) {
            Logger::error('LoginPopup::markShown', $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public static function payload(int $userId): ?array
    {
        if (!self::shouldShow($userId)) {
            return null;
        }
        $config = self::config();
        self::markShown($userId);
        return $config;
    }

    private static function lastShownAt(int $userId): ?string
    {
        try {
            $value = Database::fetchColumn(
                'SELECT shown_at FROM login_popup_views WHERE user_id = ? ORDER BY id DESC LIMIT 1',
                [$userId]
            );
        } catch (Throwable) {
            return null;
        }
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !Validator::isUrl($url)) {
            return '';
        }
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $url : '';
    }
}
