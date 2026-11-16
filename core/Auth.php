<?php
declare(strict_types=1);

class Auth
{
    public static function isRateLimited(string $scope, string $identifier): bool
    {
        $sec = require CONFIG_PATH . '/security.php';
        $maxAttempts = (int)($sec['login_max_attempts'] ?? 5);
        $windowSec = (int)($sec['login_lockout_seconds'] ?? 900);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $count = (int)Database::fetchColumn(
            'SELECT COUNT(*) FROM login_attempts WHERE scope = ? AND (identifier = ? OR ip_address = ?) AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$scope, strtolower($identifier), $ip, $windowSec]
        );

        return $count >= $maxAttempts;
    }

    public static function recordFailedAttempt(string $scope, string $identifier): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        Database::insert(
            'INSERT INTO login_attempts (scope, identifier, ip_address, attempted_at) VALUES (?, ?, ?, NOW())',
            [$scope, strtolower($identifier), $ip]
        );
    }

    public static function clearAttempts(string $scope, string $identifier): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        Database::execute(
            'DELETE FROM login_attempts WHERE scope = ? AND (identifier = ? OR ip_address = ?)',
            [$scope, strtolower($identifier), $ip]
        );
    }

    public static function registerUser(string $username, string $email, string $fullName, string $password, ?string $referralCode = null): array
    {
        $username = Validator::cleanString($username, 60);
        $email = strtolower(Validator::cleanString($email, 150));
        $fullName = Validator::cleanString($fullName, 120);

        if (!Validator::isUsername($username)) {
            return ['success' => false, 'error' => 'Username must be 3-40 characters (letters, numbers, underscores only).'];
        }
        if (!Validator::isEmail($email)) {
            return ['success' => false, 'error' => 'Please enter a valid email address.'];
        }
        if (mb_strlen($fullName) < 2) {
            return ['success' => false, 'error' => 'Please enter your full name.'];
        }
        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters long.'];
        }

        $existing = Database::fetch(
            'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$username, $email]
        );
        if ($existing) {
            return ['success' => false, 'error' => 'An account with this username or email already exists.'];
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $userId = Database::insert(
                'INSERT INTO users (username, email, full_name, password_hash, status, api_enabled, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())',
                [$username, $email, $fullName, $hash, 'active']
            );

            $currency = get_setting('currency_code', DEFAULT_CURRENCY);
            Database::insert(
                'INSERT INTO wallets (user_id, balance, total_deposited, total_spent, currency, created_at, updated_at) VALUES (?, 0.0000, 0.0000, 0.0000, ?, NOW(), NOW())',
                [$userId, $currency]
            );

            // Every account gets a shareable referral code. Attribution is
            // best-effort and must never block account creation.
            try {
                Referral::ensureCode($userId);
                $referralCode = Validator::cleanString($referralCode ?? '', 30);
                if ($referralCode !== '') {
                    $attribution = Referral::attribute($userId, $referralCode);
                    if (!$attribution['success']) {
                        Logger::file('referral', 'WARN', 'Registration referral attribution failed', ['user_id' => $userId, 'error' => $attribution['error'] ?? '']);
                    }
                }
            } catch (Throwable $e) {
                Logger::error('Auth::registerUser referral', $e->getMessage(), $e->getFile(), $e->getLine());
            }

            $pdo->commit();
            return ['success' => true, 'user_id' => $userId];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Auth::registerUser', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['success' => false, 'error' => 'Unable to create account right now. Please try again.'];
        }
    }

    public static function loginUser(string $identity, string $password): array
    {
        $identity = strtolower(Validator::cleanString($identity, 150));
        if ($identity === '' || $password === '') {
            return ['success' => false, 'error' => 'Please enter both your email/username and password.'];
        }

        if (self::isRateLimited('user', $identity)) {
            return ['success' => false, 'error' => 'Too many failed login attempts. Please wait 15 minutes before trying again.'];
        }

        $user = Database::fetch(
            'SELECT * FROM users WHERE LOWER(email) = ? OR LOWER(username) = ? LIMIT 1',
            [$identity, $identity]
        );

        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            self::recordFailedAttempt('user', $identity);
            return ['success' => false, 'error' => 'Invalid login credentials.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your account is currently ' . $user['status'] . '. Please contact support.'];
        }

        if (password_needs_rehash((string)$user['password_hash'], PASSWORD_DEFAULT)) {
            Database::execute('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }

        self::clearAttempts('user', $identity);
        Session::regenerate();
        Session::set('user_auth', [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'logged_in_at' => time(),
        ]);
        LoginPopup::markLogin();

        Database::execute(
            'UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?',
            [$_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $user['id']]
        );

        return ['success' => true, 'user' => $user];
    }

    public static function currentUser(): ?array
    {
        $auth = Session::get('user_auth');
        if (!is_array($auth) || empty($auth['id'])) {
            return null;
        }

        $user = Database::fetch(
            'SELECT u.*, w.balance AS wallet_balance, w.currency AS wallet_currency, w.total_deposited, w.total_spent
             FROM users u
             LEFT JOIN wallets w ON w.user_id = u.id
             WHERE u.id = ? LIMIT 1',
            [(int)$auth['id']]
        );

        if (!$user || $user['status'] !== 'active') {
            self::logoutUser();
            return null;
        }

        return $user;
    }

    public static function requireUser(): array
    {
        $user = self::currentUser();
        if (!$user) {
            Session::setFlash('error', 'Please sign in to access your account.');
            Response::redirect('/login.php');
        }
        return $user;
    }

    public static function logoutUser(): void
    {
        Session::remove('user_auth');
        Session::destroy();
    }

    public static function loginAdmin(string $identity, string $password): array
    {
        $identity = strtolower(Validator::cleanString($identity, 150));
        if ($identity === '' || $password === '') {
            return ['success' => false, 'error' => 'Please enter your admin username/email and password.'];
        }

        if (self::isRateLimited('admin', $identity)) {
            return ['success' => false, 'error' => 'Too many failed admin login attempts. Account temporarily rate-limited for 15 minutes.'];
        }

        $admin = Database::fetch(
            'SELECT a.*, r.name AS role_name, r.slug AS role_slug, r.is_super_admin
             FROM admins a
             INNER JOIN admin_roles r ON r.id = a.role_id
             WHERE LOWER(a.email) = ? OR LOWER(a.username) = ? LIMIT 1',
            [$identity, $identity]
        );

        if (!$admin || !password_verify($password, (string)$admin['password_hash'])) {
            self::recordFailedAttempt('admin', $identity);
            return ['success' => false, 'error' => 'Invalid administrator credentials.'];
        }

        if ($admin['status'] !== 'active') {
            return ['success' => false, 'error' => 'This administrator account is suspended.'];
        }

        self::clearAttempts('admin', $identity);
        Session::regenerate();
        Session::set('admin_auth', [
            'id' => (int)$admin['id'],
            'role_id' => (int)$admin['role_id'],
            'logged_in_at' => time(),
        ]);

        Database::execute(
            'UPDATE admins SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?',
            [$_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $admin['id']]
        );
        Logger::adminActivity((int)$admin['id'], 'admin.login', 'admin', (int)$admin['id'], 'Successful admin login');

        return ['success' => true, 'admin' => $admin];
    }

    public static function currentAdmin(): ?array
    {
        $auth = Session::get('admin_auth');
        if (!is_array($auth) || empty($auth['id'])) {
            return null;
        }

        $admin = Database::fetch(
            'SELECT a.*, r.name AS role_name, r.slug AS role_slug, r.is_super_admin
             FROM admins a
             INNER JOIN admin_roles r ON r.id = a.role_id
             WHERE a.id = ? LIMIT 1',
            [(int)$auth['id']]
        );

        if (!$admin || $admin['status'] !== 'active') {
            self::logoutAdmin();
            return null;
        }

        $admin['permissions'] = !empty($admin['is_super_admin'])
            ? array_keys(Permission::ALL_PERMISSIONS)
            : Permission::getRolePermissions((int)$admin['role_id']);

        return $admin;
    }

    public static function requireAdmin(): array
    {
        $admin = self::currentAdmin();
        if (!$admin) {
            Response::redirect('/admin/login.php');
        }
        return $admin;
    }

    public static function logoutAdmin(): void
    {
        $auth = Session::get('admin_auth');
        if (is_array($auth) && !empty($auth['id'])) {
            Logger::adminActivity((int)$auth['id'], 'admin.logout', 'admin', (int)$auth['id'], 'Admin logged out');
        }
        Session::remove('admin_auth');
        Session::destroy();
    }

    public static function createPasswordResetToken(string $email): ?string
    {
        $email = strtolower(Validator::cleanString($email, 150));
        $user = Database::fetch('SELECT id, email, status FROM users WHERE LOWER(email) = ? LIMIT 1', [$email]);
        if (!$user || $user['status'] !== 'active') {
            return null;
        }

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $sec = require CONFIG_PATH . '/security.php';
        $ttl = (int)($sec['password_reset_ttl_minutes'] ?? 60);

        Database::execute('DELETE FROM password_reset_tokens WHERE email = ?', [$email]);
        Database::insert(
            'INSERT INTO password_reset_tokens (email, token_hash, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())',
            [$email, $tokenHash, $ttl]
        );

        return $rawToken;
    }

    public static function resetPasswordWithToken(string $rawToken, string $newPassword): array
    {
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'error' => 'New password must be at least 8 characters long.'];
        }
        $tokenHash = hash('sha256', trim($rawToken));
        $record = Database::fetch(
            'SELECT * FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at >= NOW() LIMIT 1',
            [$tokenHash]
        );
        if (!$record) {
            return ['success' => false, 'error' => 'Password reset link is invalid or has expired.'];
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();
        try {
            Database::execute(
                'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE LOWER(email) = ?',
                [password_hash($newPassword, PASSWORD_DEFAULT), strtolower($record['email'])]
            );
            Database::execute(
                'UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?',
                [$record['id']]
            );
            $pdo->commit();
            return ['success' => true];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Auth::resetPasswordWithToken', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['success' => false, 'error' => 'Could not update password. Please try again.'];
        }
    }
}
