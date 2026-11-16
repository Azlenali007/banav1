<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('staff.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $roleId = (int)($_POST['role_id'] ?? 2);
        $username = Validator::cleanString($_POST['username'] ?? '', 60);
        $email = strtolower(Validator::cleanString($_POST['email'] ?? '', 150));
        $fullName = Validator::cleanString($_POST['full_name'] ?? '', 120);
        $status = ($_POST['status'] ?? 'active') === 'suspended' ? 'suspended' : 'active';
        $password = (string)($_POST['password'] ?? '');

        if (!Validator::isUsername($username) || !Validator::isEmail($email) || $fullName === '') {
            Session::setFlash('error', 'Valid username, email, and full name are required.');
        } else {
            if ($id > 0) {
                if ($id === (int)$currentAdmin['id'] && $status !== 'active') {
                    Session::setFlash('error', 'You cannot suspend your own active admin account.');
                } else {
                    Database::execute(
                        'UPDATE admins SET role_id=?, username=?, email=?, full_name=?, status=?, updated_at=NOW() WHERE id=?',
                        [$roleId, $username, $email, $fullName, $status, $id]
                    );
                    if ($password !== '') {
                        if (strlen($password) < 8) {
                            Session::setFlash('error', 'Password must be at least 8 characters.');
                            Response::redirect('/admin/staff.php');
                        }
                        Database::execute('UPDATE admins SET password_hash=? WHERE id=?', [password_hash($password, PASSWORD_DEFAULT), $id]);
                    }
                    Logger::adminActivity((int)$currentAdmin['id'], 'staff.update', 'admin', $id, "Updated staff {$username} (role #{$roleId})");
                    Session::setFlash('success', 'Staff admin updated.');
                }
            } else {
                if (strlen($password) < 8) {
                    Session::setFlash('error', 'Password must be at least 8 characters when creating a staff admin.');
                } else {
                    $newId = Database::insert(
                        'INSERT INTO admins (role_id, username, email, full_name, password_hash, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
                        [$roleId, $username, $email, $fullName, password_hash($password, PASSWORD_DEFAULT), $status]
                    );
                    Logger::adminActivity((int)$currentAdmin['id'], 'staff.create', 'admin', $newId, "Created staff {$username}");
                    Session::setFlash('success', 'Staff admin created.');
                }
            }
        }
    }
    Response::redirect('/admin/staff.php');
}

$editStaff = !empty($_GET['edit']) ? Database::fetch('SELECT * FROM admins WHERE id = ? LIMIT 1', [(int)$_GET['edit']]) : null;
$roles = Database::fetchAll('SELECT * FROM admin_roles ORDER BY is_super_admin DESC, id ASC');
$staffList = Database::fetchAll(
    'SELECT a.*, r.name AS role_name, r.is_super_admin FROM admins a INNER JOIN admin_roles r ON r.id = a.role_id ORDER BY a.id ASC'
);

Theme::render('admin/staff', [
    'editStaff' => $editStaff,
    'roles' => $roles,
    'staffList' => $staffList,
], 'admin', [
    'pageTitle' => 'Staff Admins',
    'currentAdmin' => $currentAdmin,
]);
