<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('staff.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create_role') {
        $name = Validator::cleanString($_POST['name'] ?? '', 80);
        $slug = preg_replace('/[^a-z0-9_]+/', '_', strtolower(trim($name)));
        $desc = Validator::cleanString($_POST['description'] ?? '', 255);
        if ($name !== '' && $slug !== '') {
            $rid = Database::insert('INSERT INTO admin_roles (name, slug, description, is_super_admin, created_at, updated_at) VALUES (?, ?, ?, 0, NOW(), NOW())', [$name, $slug, $desc]);
            Logger::adminActivity((int)$currentAdmin['id'], 'role.create', 'admin_role', $rid, "Created role {$name}");
            Session::setFlash('success', "Role {$name} created.");
        }
    } elseif ($action === 'save_permissions') {
        $roleId = (int)($_POST['role_id'] ?? 0);
        $role = Database::fetch('SELECT * FROM admin_roles WHERE id = ? LIMIT 1', [$roleId]);
        if ($role && empty($role['is_super_admin'])) {
            $perms = array_intersect((array)($_POST['permissions'] ?? []), array_keys(Permission::ALL_PERMISSIONS));
            Database::execute('DELETE FROM admin_permissions WHERE role_id = ?', [$roleId]);
            foreach ($perms as $p) {
                Database::insert('INSERT INTO admin_permissions (role_id, permission_key, created_at) VALUES (?, ?, NOW())', [$roleId, $p]);
            }
            Logger::adminActivity((int)$currentAdmin['id'], 'role.permissions', 'admin_role', $roleId, 'Updated permissions: ' . implode(',', $perms));
            Session::setFlash('success', "Permissions updated for {$role['name']}.");
        }
    }
    Response::redirect('/admin/roles.php');
}

$roles = Database::fetchAll('SELECT * FROM admin_roles ORDER BY is_super_admin DESC, id ASC');
foreach ($roles as &$r) {
    $r['perms'] = Permission::getRolePermissions((int)$r['id']);
}
unset($r);

Theme::render('admin/roles', [
    'roles' => $roles,
    'allPermissions' => Permission::ALL_PERMISSIONS,
], 'admin', [
    'pageTitle' => 'Roles & Permissions',
    'currentAdmin' => $currentAdmin,
]);
