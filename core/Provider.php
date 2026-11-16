<?php
declare(strict_types=1);

class Provider
{
    public static function getById(int $providerId): ?array
    {
        return Database::fetch('SELECT * FROM providers WHERE id = ? LIMIT 1', [$providerId]);
    }

    public static function decryptApiKey(array $provider): string
    {
        return crypto_decrypt((string)($provider['api_key_encrypted'] ?? ''));
    }

    public static function request(array $provider, string $action, array $params = []): array
    {
        $apiUrl = trim((string)($provider['api_url'] ?? ''));
        $apiKey = self::decryptApiKey($provider);
        $providerId = isset($provider['id']) ? (int)$provider['id'] : null;

        if (!Validator::isUrl($apiUrl) || $apiKey === '') {
            $msg = 'Provider API URL or API Key is missing/invalid.';
            Logger::provider($providerId, $action, $params, null, null, false, $msg);
            return ['success' => false, 'error' => $msg];
        }

        if (!function_exists('curl_init')) {
            $msg = 'PHP cURL extension is not enabled on this server.';
            Logger::provider($providerId, $action, $params, null, null, false, $msg);
            return ['success' => false, 'error' => $msg];
        }

        $payload = array_merge(['key' => $apiKey, 'action' => $action], $params);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: ApexSMM-Client/1.0',
            ],
        ]);

        $rawResponse = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawResponse === false) {
            $msg = 'cURL error: ' . ($curlErr ?: 'Connection failed');
            Logger::provider($providerId, $action, $payload, null, $httpCode, false, $msg);
            return ['success' => false, 'error' => $msg];
        }

        $decoded = json_decode((string)$rawResponse, true);
        if (!is_array($decoded)) {
            $msg = "Invalid JSON response from provider (HTTP {$httpCode}).";
            Logger::provider($providerId, $action, $payload, (string)$rawResponse, $httpCode, false, $msg);
            return ['success' => false, 'error' => $msg];
        }

        if (isset($decoded['error'])) {
            $msg = (string)$decoded['error'];
            Logger::provider($providerId, $action, $payload, (string)$rawResponse, $httpCode, false, $msg);
            return ['success' => false, 'error' => $msg, 'data' => $decoded];
        }

        Logger::provider($providerId, $action, $payload, (string)$rawResponse, $httpCode, $httpCode >= 200 && $httpCode < 300, null);
        return ['success' => true, 'data' => $decoded, 'http_code' => $httpCode];
    }

    public static function syncBalance(int $providerId): array
    {
        $provider = self::getById($providerId);
        if (!$provider) {
            return ['success' => false, 'error' => 'Provider not found.'];
        }
        $res = self::request($provider, 'balance');
        if (!$res['success']) {
            return $res;
        }
        $data = $res['data'];
        if (!isset($data['balance'])) {
            return ['success' => false, 'error' => 'Provider response did not include balance.'];
        }
        $bal = Validator::parseDecimal($data['balance'], 4) ?? '0.0000';
        $currency = Validator::cleanString($data['currency'] ?? $provider['currency'], 10) ?: 'USD';
        Database::execute(
            'UPDATE providers SET balance = ?, currency = ?, last_balance_sync = NOW(), updated_at = NOW() WHERE id = ?',
            [$bal, $currency, $providerId]
        );
        return ['success' => true, 'balance' => $bal, 'currency' => $currency];
    }

    public static function fetchServices(int $providerId): array
    {
        $provider = self::getById($providerId);
        if (!$provider) {
            return ['success' => false, 'error' => 'Provider not found.'];
        }
        $res = self::request($provider, 'services');
        if (!$res['success']) {
            return $res;
        }
        $list = $res['data'];
        if (!is_array($list) || !array_is_list($list)) {
            return ['success' => false, 'error' => 'Provider did not return a valid service list array.'];
        }
        return ['success' => true, 'services' => $list];
    }

    public static function createOrder(array $provider, string $providerServiceId, string $link, int $quantity): array
    {
        return self::request($provider, 'add', [
            'service' => $providerServiceId,
            'link' => $link,
            'quantity' => $quantity,
        ]);
    }

    public static function orderStatus(array $provider, string $providerOrderId): array
    {
        return self::request($provider, 'status', [
            'order' => $providerOrderId,
        ]);
    }

    public static function refillOrder(array $provider, string $providerOrderId): array
    {
        return self::request($provider, 'refill', [
            'order' => $providerOrderId,
        ]);
    }

    public static function cancelOrders(array $provider, string $providerOrderId): array
    {
        return self::request($provider, 'cancel', [
            'orders' => $providerOrderId,
        ]);
    }

    public static function mapProviderStatus(string $rawStatus): string
    {
        $s = strtolower(trim($rawStatus));
        return match ($s) {
            'pending' => 'pending',
            'processing' => 'processing',
            'in progress', 'in_progress', 'inprogress' => 'in_progress',
            'completed', 'complete' => 'completed',
            'partial' => 'partial',
            'canceled', 'cancelled' => 'cancelled',
            'fail', 'failed', 'error' => 'failed',
            default => 'processing',
        };
    }
}
