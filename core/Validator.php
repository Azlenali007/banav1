<?php
declare(strict_types=1);

class Validator
{
    public static function cleanString(mixed $val, int $maxLength = 255): string
    {
        $str = trim((string)$val);
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str) ?? '';
        return mb_substr($str, 0, $maxLength);
    }

    public static function isEmail(string $email): bool
    {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function isUsername(string $username): bool
    {
        return (bool)preg_match('/^[a-zA-Z0-9_]{3,40}$/', $username);
    }

    public static function isUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }

    public static function isPositiveInt(mixed $val, int $min = 1, int $max = PHP_INT_MAX): bool
    {
        if (is_int($val)) {
            return $val >= $min && $val <= $max;
        }
        if (!is_string($val) && !is_numeric($val)) {
            return false;
        }
        $s = trim((string)$val);
        if (!preg_match('/^\d+$/', $s)) {
            return false;
        }
        $intVal = (int)$s;
        return $intVal >= $min && $intVal <= $max;
    }

    public static function parseDecimal(mixed $val, int $scale = 4): ?string
    {
        $s = trim((string)$val);
        if (!preg_match('/^\d+(\.\d+)?$/', $s)) {
            return null;
        }
        if (function_exists('bcadd')) {
            return bcadd($s, '0', $scale);
        }
        return number_format((float)$s, $scale, '.', '');
    }
}
