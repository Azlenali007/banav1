<?php
declare(strict_types=1);

class ReportModule
{
    public static function revenueSeries(int $days = 14): array
    {
        $days = max(1, min(90, $days));
        return Database::fetchAll(
            "SELECT DATE(created_at) AS day,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','failed') THEN charge - refunded_amount ELSE 0 END), 0) AS revenue
             FROM orders
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$days - 1]
        );
    }
}
