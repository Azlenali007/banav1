<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

function smm_is_api_request(): bool
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($uri, '/api/') || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
}

function smm_render_fatal_page(string $message = 'An unexpected server error occurred. The incident has been logged.'): void
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "[FATAL] {$message}\n");
        return;
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    if (smm_is_api_request()) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode(['success' => false, 'error' => 'Internal server error. Please try again later.']);
        return;
    }

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    $safeMsg = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>500 — Service Error</title>
<style>
body{margin:0;font-family:Inter,system-ui,-apple-system,sans-serif;background:#F8FAFC;color:#0F172A;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px;}
.box{max-width:480px;width:100%;background:#FFFFFF;border:1px solid #E2E8F0;border-radius:16px;padding:32px;box-shadow:0 12px 30px rgba(91,33,182,0.08);text-align:center;}
.tag{display:inline-block;padding:6px 14px;border-radius:999px;background:#FEE2E2;color:#B91C1C;font-weight:700;font-size:13px;margin-bottom:14px;}
h1{font-size:21px;margin:0 0 10px;color:#1E1B4B;}
p{font-size:15px;line-height:1.6;color:#475569;margin:0 0 22px;}
a{display:inline-block;padding:10px 20px;border-radius:10px;background:#7C3AED;color:#FFFFFF;text-decoration:none;font-weight:600;font-size:14px;}
</style>
</head>
<body>
<div class="box">
  <div class="tag">Server Error</div>
  <h1>Unable to Process Request</h1>
  <p>{$safeMsg}</p>
  <a href="/">Return to Homepage</a>
</div>
</body>
</html>
HTML;
}

set_error_handler(function (int $severity, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    if (class_exists('Logger')) {
        Logger::error('PHP Error', $errstr, $errfile, $errline);
    }
    if (in_array($severity, [E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
        throw new ErrorException($errstr, 0, $severity, $errfile, $errline);
    }
    return true;
});

set_exception_handler(function (Throwable $e): void {
    if (class_exists('Logger')) {
        Logger::error('Uncaught Exception', $e->getMessage(), $e->getFile(), $e->getLine());
    }
    smm_render_fatal_page();
    exit(1);
});

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (class_exists('Logger')) {
            Logger::error('Fatal Error', $err['message'], $err['file'], $err['line']);
        }
        if (ob_get_level() > 0) {
            @ob_end_clean();
        }
        smm_render_fatal_page();
    }
});
