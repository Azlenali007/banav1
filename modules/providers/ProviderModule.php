<?php
declare(strict_types=1);

class ProviderModule
{
    public static function allActive(): array
    {
        return Database::fetchAll("SELECT id, name, api_url, balance, currency, status, last_balance_sync FROM providers WHERE status = 'active' ORDER BY name ASC");
    }
}
