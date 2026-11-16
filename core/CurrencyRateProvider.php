<?php
declare(strict_types=1);

/**
 * Fetches live FX rates from a configurable provider and persists them against
 * the currency registry. Failed fetches never overwrite the last valid rate.
 */
class CurrencyRateProvider
{
    public static function providerName(): string
    {
        return trim((string)get_setting('currency_rate_provider', 'open.er-api.com')) ?: 'open.er-api.com';
    }

    public static function endpoint(string $baseCode): string
    {
        $configured = trim((string)get_setting('currency_rate_api_url', ''));
        if ($configured === '') {
            $configured = match (self::providerName()) {
                'frankfurter', 'frankfurter.app' => 'https://api.frankfurter.app/latest?from={base}',
                'custom' => '',
                default => 'https://open.er-api.com/v6/latest/{base}',
            };
        }
        if ($configured === '') {
            return '';
        }
        $url = str_replace('{base}', rawurlencode(strtoupper($baseCode)), $configured);
        $apiKey = '';
        $encrypted = (string)get_setting('currency_rate_api_key_encrypted', '');
        if ($encrypted !== '') {
            try {
                $apiKey = crypto_decrypt($encrypted);
            } catch (Throwable) {
                $apiKey = '';
            }
        }
        return str_replace('{api_key}', rawurlencode($apiKey), $url);
    }

    /**
     * @return array{success:bool,rates:array<string,string>,base:string,error?:string,source?:string}
     */
    public static function fetch(string $baseCode): array
    {
        $baseCode = strtoupper(trim($baseCode));
        $url = self::endpoint($baseCode);
        if ($url === '') {
            return ['success' => false, 'rates' => [], 'base' => $baseCode, 'error' => 'No exchange-rate API URL is configured.'];
        }
        if (!function_exists('curl_init')) {
            return ['success' => false, 'rates' => [], 'base' => $baseCode, 'error' => 'PHP cURL extension is not available on this server.'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'ApexSMM/' . APP_VERSION . ' CurrencyRateProvider',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            return ['success' => false, 'rates' => [], 'base' => $baseCode, 'error' => 'Rate provider request failed: ' . $err];
        }
        if ($status < 200 || $status >= 300) {
            return ['success' => false, 'rates' => [], 'base' => $baseCode, 'error' => 'Rate provider returned HTTP ' . $status . '.'];
        }

        $data = json_decode((string)$body, true);
        if (!is_array($data)) {
            return ['success' => false, 'rates' => [], 'base' => $baseCode, 'error' => 'Rate provider returned invalid JSON.'];
        }
        if (isset($data['result']) && strtolower((string)$data['result']) !== 'success') {
            $type = (string)($data['error-type'] ?? 'unknown');
            return ['success' => false, 'rates' => [], 'base' => $baseCode, 'error' => 'Rate provider error: ' . $type];
        }

        $rates = $data['rates'] ?? $data['conversion_rates'] ?? null;
        if (!is_array($rates) || empty($rates)) {
            return ['success' => false, 'rates' => [], 'base' => $baseCode, 'error' => 'Rate provider response did not contain any rates.'];
        }

        $normalized = [];
        foreach ($rates as $code => $value) {
            $code = strtoupper(trim((string)$code));
            if ($code === '' || !is_numeric($value)) {
                continue;
            }
            $rate = money_format_dec((string)$value, 8);
            if (money_cmp($rate, '0', 8) > 0) {
                $normalized[$code] = $rate;
            }
        }

        if (empty($normalized)) {
            return ['success' => false, 'rates' => [], 'base' => $baseCode, 'error' => 'No valid positive rates were returned.'];
        }

        return ['success' => true, 'rates' => $normalized, 'base' => $baseCode, 'source' => self::providerName()];
    }

    /**
     * Update every enabled non-base currency from the provider.
     *
     * @return array{success:bool,updated:int,skipped:int,message:string,base:string}
     */
    public static function updateAll(): array
    {
        $base = Currency::baseCode();
        $result = self::fetch($base);
        if (!$result['success']) {
            set_setting('currency', 'currency_last_sync_status', 'error: ' . mb_substr((string)$result['error'], 0, 200));
            Logger::file('currency', 'ERROR', 'Currency rate sync failed', ['base' => $base, 'error' => $result['error'] ?? '']);
            return ['success' => false, 'updated' => 0, 'skipped' => 0, 'message' => (string)($result['error'] ?? 'Unable to fetch rates.'), 'base' => $base];
        }

        $rates = $result['rates'];
        $source = (string)($result['source'] ?? self::providerName());
        $updated = 0;
        $skipped = 0;

        foreach (Currency::all(true) as $currency) {
            $code = strtoupper((string)$currency['code']);
            if ($code === $base) {
                continue;
            }
            if (!isset($rates[$code])) {
                $skipped++;
                continue;
            }
            $rate = $rates[$code];
            if (money_cmp($rate, '0', 8) <= 0) {
                $skipped++;
                continue;
            }

            Database::execute(
                'UPDATE currencies SET exchange_rate = ?, rate_source = ?, rate_updated_at = NOW(), updated_at = NOW() WHERE id = ?',
                [$rate, mb_substr($source, 0, 60), (int)$currency['id']]
            );
            if ((int)$currency['id'] > 0) {
                Database::insert(
                    'INSERT INTO currency_rate_history (currency_id, rate, source, created_at) VALUES (?, ?, ?, NOW())',
                    [(int)$currency['id'], $rate, mb_substr($source, 0, 60)]
                );
            }
            $updated++;
        }

        Currency::clearCache();
        $now = date('Y-m-d H:i:s');
        set_setting('currency', 'currency_last_sync_at', $now);
        set_setting('currency', 'currency_last_sync_status', "success: updated {$updated}, skipped {$skipped}");
        Logger::file('currency', 'INFO', 'Currency rate sync completed', ['base' => $base, 'updated' => $updated, 'skipped' => $skipped, 'source' => $source]);

        return [
            'success' => true,
            'updated' => $updated,
            'skipped' => $skipped,
            'message' => "Updated {$updated} currency rate(s); skipped {$skipped}.",
            'base' => $base,
        ];
    }

    public static function testConnection(): array
    {
        $base = Currency::baseCode();
        $res = self::fetch($base);
        if (!$res['success']) {
            return $res;
        }
        return [
            'success' => true,
            'rates' => $res['rates'],
            'base' => $base,
            'message' => 'Connected successfully. ' . count($res['rates']) . ' rates available for base ' . $base . '.',
        ];
    }
}
