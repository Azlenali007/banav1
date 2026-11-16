<?php
declare(strict_types=1);

class WalletModule
{
    public static function recentUserTransactions(int $userId, int $limit = 8): array
    {
        return Database::fetchAll(
            'SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT ?',
            [$userId, $limit]
        );
    }

    public static function totals(): array
    {
        return Database::fetch(
            'SELECT COALESCE(SUM(balance), 0) AS total_balance, COALESCE(SUM(total_deposited), 0) AS total_deposited, COALESCE(SUM(total_spent), 0) AS total_spent FROM wallets'
        ) ?: ['total_balance' => '0.0000', 'total_deposited' => '0.0000', 'total_spent' => '0.0000'];
    }
}
