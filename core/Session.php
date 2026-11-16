<?php
declare(strict_types=1);

class Session
{
    private static string $currentContext = 'user';

    public static function start(string $context = 'user'): void
    {
        self::$currentContext = $context === 'admin' ? 'admin' : 'user';

        if (PHP_SAPI === 'cli') {
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sec = require CONFIG_PATH . '/security.php';
        $sessionName = self::$currentContext === 'admin'
            ? ($sec['session_name_admin'] ?? 'SMM_ADMIN_SESS')
            : ($sec['session_name_user'] ?? 'SMM_USER_SESS');

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443)
            || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        $lifetime = (int)($sec['session_lifetime'] ?? 7200);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string)$lifetime);

        session_name($sessionName);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        // Enforce idle timeout
        if (isset($_SESSION['_last_activity']) && (time() - (int)$_SESSION['_last_activity']) > $lifetime) {
            self::destroy();
            session_start();
        }
        $_SESSION['_last_activity'] = time();
    }

    public static function regenerate(): void
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (PHP_SAPI !== 'cli' && ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['_flashes'][] = ['type' => $type, 'message' => $message];
    }

    public static function getFlashes(): array
    {
        $flashes = $_SESSION['_flashes'] ?? [];
        unset($_SESSION['_flashes']);
        return is_array($flashes) ? $flashes : [];
    }
}
