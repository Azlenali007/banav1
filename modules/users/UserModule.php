<?php
declare(strict_types=1);

class UserModule
{
    public static function summaryCounts(): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS total_users, SUM(status = 'active') AS active_users, SUM(status = 'suspended') AS suspended_users FROM users"
        );
        return [
            'total_users' => (int)($row['total_users'] ?? 0),
            'active_users' => (int)($row['active_users'] ?? 0),
            'suspended_users' => (int)($row['suspended_users'] ?? 0),
        ];
    }
}
