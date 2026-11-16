<?php
declare(strict_types=1);

class Logger
{
    private static function redact(mixed $data): mixed
    {
        if (is_array($data)) {
            $sensitiveKeys = ['password', 'password_hash', 'api_key', 'key', 'key_secret', 'razorpay_signature', 'secret', 'token'];
            $out = [];
            foreach ($data as $k => $v) {
                $lk = strtolower((string)$k);
                $isSensitive = false;
                foreach ($sensitiveKeys as $sk) {
                    if (str_contains($lk, $sk)) {
                        $isSensitive = true;
                        break;
                    }
                }
                $out[$k] = $isSensitive ? '[REDACTED]' : self::redact($v);
            }
            return $out;
        }
        return $data;
    }

    public static function file(string $channel, string $level, string $message, array $context = []): void
    {
        try {
            if (!is_dir(LOGS_PATH)) {
                @mkdir(LOGS_PATH, 0755, true);
            }
            $safeChannel = preg_replace('/[^a-zA-Z0-9_\-]/', '', $channel) ?: 'app';
            $file = LOGS_PATH . '/' . $safeChannel . '-' . date('Y-m-d') . '.log';
            $cleanContext = self::redact($context);
            $line = sprintf(
                "[%s] [%s] %s %s\n",
                date('Y-m-d H:i:s'),
                strtoupper($level),
                $message,
                $cleanContext !== [] ? json_encode($cleanContext, JSON_UNESCAPED_SLASHES) : ''
            );
            @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        } catch (Throwable) {
            // Never throw from file logger
        }
    }

    public static function error(string $context, string $message, ?string $file = null, ?int $line = null): void
    {
        self::file('error', 'ERROR', $context . ': ' . $message, ['file' => $file, 'line' => $line]);
        try {
            Database::insert(
                'INSERT INTO error_logs (level, context, message, file, line, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
                ['error', mb_substr($context, 0, 100), mb_substr($message, 0, 2000), $file ? mb_substr($file, 0, 255) : null, $line]
            );
        } catch (Throwable) {
            // DB may not be available yet during install or DB outage
        }
    }

    public static function adminActivity(?int $adminId, string $action, ?string $targetType = null, string|int|null $targetId = null, string $details = ''): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            Database::insert(
                'INSERT INTO admin_activity_logs (admin_id, action, target_type, target_id, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                [$adminId, mb_substr($action, 0, 100), $targetType, $targetId !== null ? (string)$targetId : null, $details, $ip, $ua]
            );
        } catch (Throwable $e) {
            self::file('admin', 'WARN', 'Failed to write admin_activity_logs: ' . $e->getMessage());
        }
    }

    public static function provider(?int $providerId, string $action, array $request, ?string $response, ?int $httpCode, bool $success, ?string $errorMessage = null): void
    {
        try {
            $maskedReq = json_encode(self::redact($request), JSON_UNESCAPED_SLASHES);
            Database::insert(
                'INSERT INTO provider_logs (provider_id, action, request_payload, response_payload, http_code, success, error_message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    $providerId,
                    mb_substr($action, 0, 50),
                    $maskedReq,
                    $response !== null ? mb_substr($response, 0, 65000) : null,
                    $httpCode,
                    $success ? 1 : 0,
                    $errorMessage ? mb_substr($errorMessage, 0, 2000) : null,
                ]
            );
        } catch (Throwable $e) {
            self::file('provider', 'WARN', 'Failed to write provider_logs: ' . $e->getMessage());
        }
    }
}
