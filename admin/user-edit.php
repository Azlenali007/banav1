<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('users.manage');

$userId = (int)($_GET['id'] ?? 0);
$user = Database::fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$userId]);
if (!$user) {
    Response::abort(404, 'User Not Found', 'The requested user does not exist.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? 'update_profile');

    if ($action === 'update_profile') {
        $fullName = Validator::cleanString($_POST['full_name'] ?? '', 120);
        $email = strtolower(Validator::cleanString($_POST['email'] ?? '', 150));
        $status = in_array($_POST['status'] ?? '', ['active', 'suspended', 'pending'], true) ? $_POST['status'] : 'active';
        $apiEnabled = !empty($_POST['api_enabled']) ? 1 : 0;
        $mult = Validator::parseDecimal($_POST['custom_rate_multiplier'] ?? '1.0000', 4) ?? '1.0000';

        if (!Validator::isEmail($email)) {
            $error = 'Invalid email address.';
        } elseif (money_cmp($mult, '0.1000') < 0 || money_cmp($mult, '5.0000') > 0) {
            $error = 'Rate multiplier must be between 0.1000 and 5.0000.';
        } else {
            $dup = Database::fetch('SELECT id FROM users WHERE LOWER(email) = ? AND id != ? LIMIT 1', [$email, $userId]);
            if ($dup) {
                $error = 'That email address is already used by another account.';
            } else {
                Database::execute(
                    'UPDATE users SET full_name = ?, email = ?, status = ?, api_enabled = ?, custom_rate_multiplier = ?, updated_at = NOW() WHERE id = ?',
                    [$fullName, $email, $status, $apiEnabled, $mult, $userId]
                );
                Logger::adminActivity((int)$currentAdmin['id'], 'user.edit', 'user', $userId, "Updated user profile/status ({$status}, mult={$mult})");
                Session::setFlash('success', 'User details updated.');
                Response::redirect('/admin/user-view.php?id=' . $userId);
            }
        }
    } elseif ($action === 'change_password') {
        $newPass = (string)($_POST['new_password'] ?? '');
        if (strlen($newPass) < 8) {
            $error = 'New password must be at least 8 characters.';
        } else {
            Database::execute('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', [password_hash($newPass, PASSWORD_DEFAULT), $userId]);
            Logger::adminActivity((int)$currentAdmin['id'], 'user.password_reset', 'user', $userId, 'Admin reset user password');
            Session::setFlash('success', 'User password updated securely.');
            Response::redirect('/admin/user-view.php?id=' . $userId);
        }
    }
}

Theme::render('admin/user-edit', [
    'userId' => $userId,
    'user' => $user,
    'error' => $error,
], 'admin', [
    'pageTitle' => 'Edit User: ' . $user['username'],
    'currentAdmin' => $currentAdmin,
]);
