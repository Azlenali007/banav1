<?php
declare(strict_types=1);

function e(mixed $val): string
{
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    static $baseUrl = null;
    if ($baseUrl === null) {
        $appCfg = require CONFIG_PATH . '/app.php';
        $baseUrl = rtrim((string)$appCfg['base_url'], '/');
    }
    if ($path === '' || $path === '/') {
        return $baseUrl . '/';
    }
    return $baseUrl . '/' . ltrim($path, '/');
}

function current_script_name(): string
{
    return basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
}

/**
 * Exact fixed-point decimal math helpers (scale 4..8, zero floating-point drift).
 */
function dec_split_norm(string $val): array
{
    $val = trim($val);
    $neg = false;
    if (str_starts_with($val, '-')) {
        $neg = true;
        $val = substr($val, 1);
    } elseif (str_starts_with($val, '+')) {
        $val = substr($val, 1);
    }
    if (!preg_match('/^\d+(\.\d+)?$/', $val)) {
        return [false, '0', 0];
    }
    [$whole, $frac] = array_pad(explode('.', $val, 2), 2, '');
    $digits = ltrim($whole . $frac, '0') ?: '0';
    return [$neg && $digits !== '0', $digits, strlen($frac)];
}

function dec_add_uint(string $a, string $b): string
{
    $i = strlen($a) - 1;
    $j = strlen($b) - 1;
    $carry = 0;
    $out = '';
    while ($i >= 0 || $j >= 0 || $carry > 0) {
        $da = $i >= 0 ? (int)$a[$i--] : 0;
        $db = $j >= 0 ? (int)$b[$j--] : 0;
        $sum = $da + $db + $carry;
        $out = ($sum % 10) . $out;
        $carry = intdiv($sum, 10);
    }
    return ltrim($out, '0') ?: '0';
}

function dec_mul_uint(string $a, string $b): string
{
    if ($a === '0' || $b === '0') {
        return '0';
    }
    $lenA = strlen($a);
    $lenB = strlen($b);
    $res = array_fill(0, $lenA + $lenB, 0);
    for ($i = $lenA - 1; $i >= 0; $i--) {
        for ($j = $lenB - 1; $j >= 0; $j--) {
            $mul = ((int)$a[$i]) * ((int)$b[$j]) + $res[$i + $j + 1];
            $res[$i + $j + 1] = $mul % 10;
            $res[$i + $j] += intdiv($mul, 10);
        }
    }
    $str = ltrim(implode('', $res), '0');
    return $str === '' ? '0' : $str;
}

function dec_cmp_uint(string $a, string $b): int
{
    $a = ltrim($a, '0') ?: '0';
    $b = ltrim($b, '0') ?: '0';
    if (strlen($a) !== strlen($b)) {
        return strlen($a) <=> strlen($b);
    }
    return strcmp($a, $b);
}

function dec_sub_uint(string $a, string $b): string
{
    $i = strlen($a) - 1;
    $j = strlen($b) - 1;
    $borrow = 0;
    $out = '';
    while ($i >= 0) {
        $da = (int)$a[$i--] - $borrow;
        $db = $j >= 0 ? (int)$b[$j--] : 0;
        if ($da < $db) {
            $da += 10;
            $borrow = 1;
        } else {
            $borrow = 0;
        }
        $out = ($da - $db) . $out;
    }
    return ltrim($out, '0') ?: '0';
}

function dec_format_digits(bool $neg, string $digits, int $fracLen, int $targetScale): string
{
    $digits = ltrim($digits, '0') ?: '0';
    if ($digits === '0') {
        $neg = false;
    }
    if ($fracLen > $targetScale) {
        $cut = $fracLen - $targetScale;
        $digits = strlen($digits) > $cut ? substr($digits, 0, -$cut) : '0';
    } elseif ($fracLen < $targetScale) {
        $digits .= str_repeat('0', $targetScale - $fracLen);
    }
    if ($targetScale <= 0) {
        return ($neg && $digits !== '0' ? '-' : '') . $digits;
    }
    $padded = str_pad($digits, $targetScale + 1, '0', STR_PAD_LEFT);
    $whole = substr($padded, 0, -$targetScale);
    $frac = substr($padded, -$targetScale);
    $isZero = ($whole === '0' && trim($frac, '0') === '');
    return ($neg && !$isZero ? '-' : '') . $whole . '.' . $frac;
}

function money_format_dec(string $val, int $scale = 4): string
{
    if (function_exists('bcadd')) {
        return bcadd(trim($val) === '' ? '0' : trim($val), '0', $scale);
    }
    [$neg, $digits, $fracLen] = dec_split_norm($val);
    return dec_format_digits($neg, $digits, $fracLen, $scale);
}

function money_add(string $a, string $b, int $scale = 4): string
{
    if (function_exists('bcadd')) {
        return bcadd($a, $b, $scale);
    }
    [$na, $da, $fa] = dec_split_norm($a);
    [$nb, $db, $fb] = dec_split_norm($b);
    $s = max($fa, $fb, $scale);
    $da .= str_repeat('0', $s - $fa);
    $db .= str_repeat('0', $s - $fb);
    if ($na === $nb) {
        return dec_format_digits($na, dec_add_uint($da, $db), $s, $scale);
    }
    $cmp = dec_cmp_uint($da, $db);
    if ($cmp === 0) {
        return money_format_dec('0', $scale);
    }
    if ($cmp > 0) {
        return dec_format_digits($na, dec_sub_uint($da, $db), $s, $scale);
    }
    return dec_format_digits($nb, dec_sub_uint($db, $da), $s, $scale);
}

function money_sub(string $a, string $b, int $scale = 4): string
{
    if (function_exists('bcsub')) {
        return bcsub($a, $b, $scale);
    }
    $b = trim($b);
    $negB = str_starts_with($b, '-') ? substr($b, 1) : '-' . ltrim($b, '+');
    return money_add($a, $negB, $scale);
}

function money_mul(string $a, string $b, int $scale = 4): string
{
    if (function_exists('bcmul')) {
        return bcmul($a, $b, $scale);
    }
    [$na, $da, $fa] = dec_split_norm($a);
    [$nb, $db, $fb] = dec_split_norm($b);
    $prod = dec_mul_uint($da, $db);
    return dec_format_digits($na !== $nb, $prod, $fa + $fb, $scale);
}

function money_div(string $a, string $b, int $scale = 4): string
{
    if (function_exists('bcdiv')) {
        if (bccomp($b, '0', 8) === 0) {
            return money_format_dec('0', $scale);
        }
        return bcdiv($a, $b, $scale);
    }
    [$na, $da, $fa] = dec_split_norm($a);
    [$nb, $db, $fb] = dec_split_norm($b);
    if ($db === '0') {
        return money_format_dec('0', $scale);
    }
    if ($da === '0') {
        return money_format_dec('0', $scale);
    }
    $shift = $fb - $fa + $scale;
    if ($shift >= 0) {
        $num = $da . str_repeat('0', $shift);
        $den = $db;
    } else {
        $num = $da;
        $den = $db . str_repeat('0', -$shift);
    }
    // Long division on unsigned decimal strings
    $quot = '';
    $rem = '0';
    $len = strlen($num);
    for ($i = 0; $i < $len; $i++) {
        $rem = ltrim($rem . $num[$i], '0') ?: '0';
        $qDigit = 0;
        while (dec_cmp_uint($rem, $den) >= 0) {
            $rem = dec_sub_uint($rem, $den);
            $qDigit++;
        }
        $quot .= (string)$qDigit;
    }
    return dec_format_digits($na !== $nb, $quot, $scale, $scale);
}

function money_cmp(string $a, string $b, int $scale = 4): int
{
    if (function_exists('bccomp')) {
        return bccomp($a, $b, $scale);
    }
    $diff = money_sub($a, $b, $scale);
    if (str_starts_with($diff, '-')) {
        return -1;
    }
    [, $digits] = dec_split_norm($diff);
    return $digits === '0' ? 0 : 1;
}

function format_money(mixed $amount, int $decimals = 2): string
{
    if (class_exists('Currency')) {
        try {
            return Currency::format($amount, null, $decimals);
        } catch (Throwable) {
            // Fall through to the legacy formatter when the currency registry
            // is unavailable (e.g. during installation).
        }
    }
    $symbol = function_exists('get_setting') ? get_setting('currency_symbol', DEFAULT_CURRENCY_SYMBOL) : DEFAULT_CURRENCY_SYMBOL;
    $formatted = number_format((float)money_format_dec((string)$amount, 4), $decimals, '.', ',');
    return $symbol . $formatted;
}

/**
 * AES-256-GCM encryption for API keys & payment gateway secrets at rest.
 */
function crypto_get_key(): string
{
    $sec = require CONFIG_PATH . '/security.php';
    $raw = (string)($sec['app_key'] ?? '');
    if ($raw === '') {
        throw new RuntimeException('Application encryption key is not initialized.');
    }
    return hash('sha256', $raw, true);
}

function crypto_encrypt(string $plaintext): string
{
    if ($plaintext === '') {
        return '';
    }
    $key = crypto_get_key();
    if (function_exists('openssl_encrypt') && in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext !== false) {
            return 'v1:' . base64_encode($iv . $tag . $ciphertext);
        }
    }
    $iv = random_bytes(16);
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $iv . $ciphertext, $key, true);
    return 'v0:' . base64_encode($hmac . $iv . $ciphertext);
}

function crypto_decrypt(string $encoded): string
{
    if ($encoded === '') {
        return '';
    }
    $key = crypto_get_key();
    if (str_starts_with($encoded, 'v1:')) {
        $blob = base64_decode(substr($encoded, 3), true);
        if ($blob === false || strlen($blob) < 28) {
            return '';
        }
        $iv = substr($blob, 0, 12);
        $tag = substr($blob, 12, 16);
        $cipher = substr($blob, 28);
        $res = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $res === false ? '' : $res;
    }
    if (str_starts_with($encoded, 'v0:')) {
        $blob = base64_decode(substr($encoded, 3), true);
        if ($blob === false || strlen($blob) < 48) {
            return '';
        }
        $hmac = substr($blob, 0, 32);
        $iv = substr($blob, 32, 16);
        $cipher = substr($blob, 48);
        if (!hash_equals($hmac, hash_hmac('sha256', $iv . $cipher, $key, true))) {
            return '';
        }
        $res = openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $res === false ? '' : $res;
    }
    return '';
}

function handle_secure_upload(array $file, string $prefix = 'upload'): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload failed with code ' . ($file['error'] ?? -1) . '.'];
    }
    $sec = require CONFIG_PATH . '/security.php';
    $maxSize = (int)($sec['max_upload_size_bytes'] ?? 2097152);
    if ((int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Uploaded file must be under 2 MB.'];
    }
    $tmp = (string)$file['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        return ['success' => false, 'error' => 'Invalid upload source.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmp);
    $allowed = $sec['allowed_upload_mimes'] ?? ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        return ['success' => false, 'error' => 'Only JPG, PNG, and WEBP images are allowed.'];
    }
    if (@getimagesize($tmp) === false) {
        return ['success' => false, 'error' => 'Uploaded file is not a valid image.'];
    }
    if (!is_dir(UPLOADS_PATH)) {
        @mkdir(UPLOADS_PATH, 0755, true);
    }
    $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $prefix) . '_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $dest = UPLOADS_PATH . '/' . $safeName;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['success' => false, 'error' => 'Failed to save uploaded file.'];
    }
    return ['success' => true, 'filename' => $safeName];
}
