<?php
declare(strict_types=1);

function admin_can(string $permissionKey): bool
{
    $admin = Auth::currentAdmin();
    return Permission::adminHas($admin, $permissionKey);
}

function admin_require_permission(string $permissionKey): array
{
    $admin = Auth::requireAdmin();
    if (!Permission::adminHas($admin, $permissionKey)) {
        Response::abort(403, 'Permission Denied', 'Your staff role does not allow access to this section.');
    }
    return $admin;
}
