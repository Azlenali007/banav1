<?php
declare(strict_types=1);

class Permission
{
    public const ALL_PERMISSIONS = [
        'dashboard.view' => 'View Admin Dashboard & Analytics',
        'users.view' => 'View Users & Profiles',
        'users.manage' => 'Create, Edit, Suspend Users & Adjust Wallets',
        'orders.view' => 'View All Orders & Logs',
        'orders.manage' => 'Update Orders, Refunds, Refills & Cancellations',
        'categories.manage' => 'Manage Categories',
        'services.manage' => 'Manage Services & Pricing',
        'providers.manage' => 'Manage External Providers & Service Sync',
        'payments.view' => 'View Deposits & Transactions',
        'payments.manage' => 'Approve/Reject Manual Payments & Configure Gateways',
        'currencies.manage' => 'Manage Currencies, Exchange Rates & Rate Providers',
        'referrals.view' => 'View Referral Program & Rewards',
        'referrals.manage' => 'Manage Referral Settings & Reward Payouts',
        'popup.manage' => 'Manage Post-login WhatsApp/Telegram Popup',
        'tickets.view' => 'View Support Tickets',
        'tickets.manage' => 'Reply, Close & Reopen Support Tickets',
        'website.manage' => 'Manage Landing Content, Pages & Announcements',
        'reports.view' => 'View & Export Financial/Order Reports',
        'logs.view' => 'View System, Provider & Activity Logs',
        'backups.manage' => 'Create & Download Database Backups',
        'staff.manage' => 'Manage Staff Admins & Roles (Super Admin Recommended)',
        'themes.manage' => 'Configure Classic Theme Settings',
        'settings.manage' => 'Manage Global Website, Mail & Security Settings',
    ];

    public static function getRolePermissions(int $roleId): array
    {
        $rows = Database::fetchAll('SELECT permission_key FROM admin_permissions WHERE role_id = ?', [$roleId]);
        return array_column($rows, 'permission_key');
    }

    public static function adminHas(?array $admin, string $permissionKey): bool
    {
        if (!$admin) {
            return false;
        }
        if (!empty($admin['is_super_admin'])) {
            return true;
        }
        $perms = $admin['permissions'] ?? [];
        return in_array($permissionKey, $perms, true);
    }

    public static function require(string $permissionKey): void
    {
        $admin = Auth::currentAdmin();
        if (!$admin) {
            Response::redirect('/admin/login.php');
        }
        if (!self::adminHas($admin, $permissionKey)) {
            Response::abort(403, 'Access Denied', 'You do not have permission (' . $permissionKey . ') to access this administrative module.');
        }
    }
}
