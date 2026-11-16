<?php
declare(strict_types=1);

class Response
{
    public static function json(array $data, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $path, int $status = 302): never
    {
        if (!preg_match('#^https?://#i', $path)) {
            $path = app_url($path);
        }
        if (!headers_sent()) {
            header('Location: ' . $path, true, $status);
        } else {
            echo '<script>window.location.href=' . json_encode($path) . ';</script>';
        }
        exit;
    }

    public static function abort(int $code, string $title, string $message): never
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: text/html; charset=UTF-8');
        }
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $homeUrl = function_exists('app_url') ? app_url('/') : '/';
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$code} — {$safeTitle}</title>
<style>
body{margin:0;font-family:Inter,system-ui,-apple-system,sans-serif;background:#F8FAFC;color:#0F172A;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px;}
.card{max-width:480px;width:100%;background:#FFFFFF;border:1px solid #E2E8F0;border-radius:16px;padding:32px;box-shadow:0 10px 30px rgba(91,33,182,0.08);text-align:center;}
.badge{display:inline-block;padding:6px 14px;border-radius:999px;background:#F3E8FF;color:#6D28D9;font-weight:700;font-size:13px;margin-bottom:16px;}
h1{font-size:22px;margin:0 0 10px;color:#1E1B4B;}
p{font-size:15px;line-height:1.6;color:#475569;margin:0 0 24px;}
a.btn{display:inline-block;padding:11px 22px;border-radius:10px;background:#7C3AED;color:#FFFFFF;text-decoration:none;font-weight:600;font-size:14px;}
a.btn:hover{background:#6D28D9;}
</style>
</head>
<body>
<div class="card">
  <div class="badge">HTTP {$code}</div>
  <h1>{$safeTitle}</h1>
  <p>{$safeMessage}</p>
  <a class="btn" href="{$homeUrl}">Return Home</a>
</div>
</body>
</html>
HTML;
        exit;
    }
}
