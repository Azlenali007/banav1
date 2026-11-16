<?php
declare(strict_types=1);

class CSRF
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf_token']) || empty($_SESSION['_csrf_time']) || (time() - (int)$_SESSION['_csrf_time']) > 7200) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['_csrf_time'] = time();
        }
        return (string)$_SESSION['_csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $submittedToken = null): bool
    {
        $token = $submittedToken ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        if ($token === '' || $sessionToken === '') {
            return false;
        }
        return hash_equals((string)$sessionToken, (string)$token);
    }

    public static function verifyOrAbort(bool $json = false): void
    {
        if (!self::verify()) {
            if ($json) {
                Response::json(['success' => false, 'error' => 'Invalid or expired security token (CSRF). Please refresh and try again.'], 403);
            }
            Response::abort(403, 'Security Verification Failed', 'Your security token is missing or expired. Please go back, refresh the page, and submit again.');
        }
    }
}
