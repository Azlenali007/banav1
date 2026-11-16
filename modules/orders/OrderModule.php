<?php
declare(strict_types=1);

class OrderModule
{
    public static function userStats(int $userId): array
    {
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'pending') AS pending,
                SUM(status = 'processing') AS processing,
                SUM(status = 'in_progress') AS in_progress,
                SUM(status = 'completed') AS completed,
                SUM(status = 'partial') AS partial,
                SUM(status = 'cancelled') AS cancelled,
                SUM(status = 'failed') AS failed
             FROM orders WHERE user_id = ?",
            [$userId]
        );
        return [
            'total' => (int)($row['total'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
            'processing' => (int)($row['processing'] ?? 0),
            'in_progress' => (int)($row['in_progress'] ?? 0),
            'completed' => (int)($row['completed'] ?? 0),
            'partial' => (int)($row['partial'] ?? 0),
            'cancelled' => (int)($row['cancelled'] ?? 0),
            'failed' => (int)($row['failed'] ?? 0),
        ];
    }

    public static function adminStats(): array
    {
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'pending') AS pending,
                SUM(status = 'processing') AS processing,
                SUM(status = 'in_progress') AS in_progress,
                SUM(status = 'completed') AS completed,
                SUM(status = 'partial') AS partial,
                SUM(status = 'cancelled') AS cancelled,
                SUM(status = 'failed') AS failed,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','failed') THEN charge - refunded_amount ELSE 0 END), 0) AS net_revenue,
                COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() AND status NOT IN ('cancelled','failed') THEN charge - refunded_amount ELSE 0 END), 0) AS today_revenue
             FROM orders"
        );
        return $row ?: [];
    }
}
