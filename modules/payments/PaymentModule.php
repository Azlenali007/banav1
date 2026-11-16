<?php
declare(strict_types=1);

class PaymentModule
{
    public static function totalCompletedDeposits(): string
    {
        return (string)Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'");
    }

    public static function pendingManualCount(): int
    {
        return (int)Database::fetchColumn("SELECT COUNT(*) FROM payments WHERE method_code = 'manual' AND status = 'pending'");
    }
}
