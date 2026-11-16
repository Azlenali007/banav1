<?php
declare(strict_types=1);

/**
 * Multi-currency registry and display conversion.
 *
 * All monetary values in the database (wallet balances, order charges, service
 * rates, payments) are stored in the base currency. Currency::format() converts
 * those base amounts into the viewer's preferred currency for display only.
 *
 * `exchange_rate` represents units of a currency per 1 unit of the base
 * currency, so:  base -> foreign  = amount * rate
 *                foreign -> base  = amount / rate
 *                A -> B           = amount * (rateB / rateA)
 */
class Currency
{
    private static ?array $cache = null;
    private static ?string $displayOverride = null;
    private static array $userPreferences = [];

    public static function clearCache(): void
    {
        self::$cache = null;
        self::$userPreferences = [];
        self::$displayOverride = null;
    }

    public static function all(bool $enabledOnly = false): array
    {
        $rows = self::load();
        if (!$enabledOnly) {
            return $rows;
        }
        return array_values(array_filter($rows, static fn(array $row): bool => (int)$row['is_enabled'] === 1));
    }

    public static function getByCode(?string $code): ?array
    {
        $code = strtoupper(trim((string)$code));
        if ($code === '') {
            return null;
        }
        foreach (self::load() as $row) {
            if (strtoupper((string)$row['code']) === $code) {
                return $row;
            }
        }
        return null;
    }

    public static function baseCode(): string
    {
        foreach (self::load() as $row) {
            if ((int)$row['is_default'] === 1) {
                return strtoupper((string)$row['code']);
            }
        }
        $fallback = function_exists('get_setting') ? (string)get_setting('currency_code', DEFAULT_CURRENCY) : DEFAULT_CURRENCY;
        return strtoupper($fallback !== '' ? $fallback : DEFAULT_CURRENCY);
    }

    public static function base(): ?array
    {
        return self::getByCode(self::baseCode());
    }

    public static function rate(string $code): string
    {
        $row = self::getByCode($code);
        if (!$row) {
            return '1.00000000';
        }
        $rate = money_format_dec((string)$row['exchange_rate'], 8);
        return money_cmp($rate, '0', 8) > 0 ? $rate : '1.00000000';
    }

    /**
     * Convert an amount between two currencies.
     */
    public static function convert(string $amount, string $fromCode, string $toCode, int $scale = 4): string
    {
        $from = strtoupper(trim($fromCode));
        $to = strtoupper(trim($toCode));
        if ($from === '' || $to === '' || $from === $to) {
            return money_format_dec($amount, $scale);
        }
        $factor = money_div(self::rate($to), self::rate($from), 12);
        return money_mul(money_format_dec($amount, 8), $factor, $scale);
    }

    public static function toBase(string $amount, string $fromCode, int $scale = 4): string
    {
        return self::convert($amount, $fromCode, self::baseCode(), $scale);
    }

    public static function fromBase(string $amount, string $toCode, int $scale = 4): string
    {
        return self::convert($amount, self::baseCode(), $toCode, $scale);
    }

    public static function setDisplayCurrency(?string $code): void
    {
        $code = strtoupper(trim((string)$code));
        self::$displayOverride = ($code !== '' && self::getByCode($code)) ? $code : null;
    }

    public static function displayCode(): string
    {
        if (self::$displayOverride !== null) {
            $row = self::getByCode(self::$displayOverride);
            if ($row && (int)$row['is_enabled'] === 1) {
                return strtoupper((string)$row['code']);
            }
        }

        $auth = class_exists('Session') ? Session::get('user_auth') : null;
        if (is_array($auth) && !empty($auth['id'])) {
            $userId = (int)$auth['id'];
            if (!array_key_exists($userId, self::$userPreferences)) {
                self::$userPreferences[$userId] = self::loadUserPreference($userId);
            }
            $pref = self::$userPreferences[$userId];
            if ($pref !== null) {
                $row = self::getByCode($pref);
                if ($row && (int)$row['is_enabled'] === 1) {
                    return strtoupper((string)$row['code']);
                }
            }
        }

        return self::baseCode();
    }

    public static function display(): ?array
    {
        return self::getByCode(self::displayCode());
    }

    public static function userPreferred(int $userId): ?string
    {
        if (!array_key_exists($userId, self::$userPreferences)) {
            self::$userPreferences[$userId] = self::loadUserPreference($userId);
        }
        return self::$userPreferences[$userId];
    }

    public static function setUserPreferred(int $userId, ?string $code): array
    {
        $code = strtoupper(trim((string)$code));
        if ($code !== '') {
            $row = self::getByCode($code);
            if (!$row || (int)$row['is_enabled'] !== 1) {
                return ['success' => false, 'error' => 'That currency is not available.'];
            }
        } else {
            $code = '';
        }

        Database::execute(
            'UPDATE users SET preferred_currency = ?, updated_at = NOW() WHERE id = ?',
            [$code !== '' ? $code : null, $userId]
        );
        self::$userPreferences[$userId] = $code !== '' ? $code : null;
        return ['success' => true, 'currency' => $code !== '' ? $code : self::baseCode()];
    }

    public static function symbolFor(?string $code = null): string
    {
        $row = $code !== null ? self::getByCode($code) : self::display();
        if ($row && (string)$row['symbol'] !== '') {
            return (string)$row['symbol'];
        }
        return function_exists('get_setting') ? (string)get_setting('currency_symbol', DEFAULT_CURRENCY_SYMBOL) : DEFAULT_CURRENCY_SYMBOL;
    }

    public static function decimalsFor(?string $code = null): int
    {
        $row = $code !== null ? self::getByCode($code) : self::display();
        if ($row) {
            return max(0, min(4, (int)$row['decimals']));
        }
        return 2;
    }

    /**
     * Format a base-currency amount for display in a target currency.
     */
    public static function format(mixed $amount, ?string $code = null, ?int $decimals = null): string
    {
        $targetCode = $code !== null ? strtoupper(trim($code)) : self::displayCode();
        $row = self::getByCode($targetCode);
        if (!$row) {
            $targetCode = self::baseCode();
            $row = self::getByCode($targetCode);
        }

        $decimals = $decimals !== null ? max(0, min(8, $decimals)) : self::decimalsFor($targetCode);
        $converted = self::fromBase((string)$amount, $targetCode, max(4, $decimals));
        $formatted = self::formatNumber($converted, $decimals);

        $symbol = $row ? (string)$row['symbol'] : self::symbolFor($targetCode);
        if ($symbol === '') {
            return $formatted . ' ' . $targetCode;
        }
        $position = $row ? (string)$row['symbol_position'] : 'before';
        return $position === 'after' ? $formatted . ' ' . $symbol : $symbol . $formatted;
    }

    public static function formatNumber(string $amount, int $decimals = 2): string
    {
        $decimals = max(0, $decimals);
        $normalized = money_format_dec($amount, $decimals);
        $negative = str_starts_with($normalized, '-');
        if ($negative) {
            $normalized = substr($normalized, 1);
        }
        $parts = explode('.', $normalized, 2);
        $whole = $parts[0] !== '' ? $parts[0] : '0';
        $fraction = $parts[1] ?? '';
        $whole = strrev(implode(',', str_split(strrev($whole), 3)));
        $out = $whole . ($decimals > 0 ? '.' . str_pad($fraction, $decimals, '0') : '');
        return ($negative ? '-' : '') . $out;
    }

    private static function loadUserPreference(int $userId): ?string
    {
        try {
            $value = Database::fetchColumn('SELECT preferred_currency FROM users WHERE id = ? LIMIT 1', [$userId]);
        } catch (Throwable) {
            return null;
        }
        $value = is_string($value) ? strtoupper(trim($value)) : '';
        return $value !== '' ? $value : null;
    }

    private static function load(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $rows = [];
        try {
            $rows = Database::fetchAll('SELECT * FROM currencies ORDER BY sort_order ASC, id ASC');
        } catch (Throwable) {
            $rows = [];
        }

        if (empty($rows)) {
            $rows = [[
                'id' => 0,
                'code' => strtoupper((string)(function_exists('get_setting') ? get_setting('currency_code', DEFAULT_CURRENCY) : DEFAULT_CURRENCY)),
                'name' => 'Base Currency',
                'symbol' => (string)(function_exists('get_setting') ? get_setting('currency_symbol', DEFAULT_CURRENCY_SYMBOL) : DEFAULT_CURRENCY_SYMBOL),
                'decimals' => 2,
                'symbol_position' => 'before',
                'exchange_rate' => '1.00000000',
                'is_enabled' => 1,
                'is_default' => 1,
                'rate_source' => 'base',
                'rate_updated_at' => null,
                'sort_order' => 0,
            ]];
        }

        self::$cache = $rows;
        return self::$cache;
    }
}
