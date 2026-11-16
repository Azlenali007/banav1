<?php
declare(strict_types=1);

/**
 * Application runtime configuration & base URL detection.
 */
if (!function_exists('detect_base_url')) {
    function detect_base_url(): string
    {
        if (PHP_SAPI === 'cli') {
            return rtrim((string)(getenv('APP_URL') ?: 'http://localhost'), '/');
        }
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443)
            || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Determine subdirectory path relative to document root if hosted in a subfolder
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string)$_SERVER['DOCUMENT_ROOT']) : false;
        $basePath = realpath(BASE_PATH);
        $subPath = '';
        if ($docRoot && $basePath) {
            $docRootNorm = str_replace('\\', '/', rtrim($docRoot, '/\\'));
            $basePathNorm = str_replace('\\', '/', rtrim($basePath, '/\\'));
            if (strpos($basePathNorm, $docRootNorm) === 0) {
                $subPath = substr($basePathNorm, strlen($docRootNorm));
            }
        }
        $subPath = '/' . trim((string)$subPath, '/');
        if ($subPath === '/') {
            $subPath = '';
        }

        return $scheme . '://' . $host . $subPath;
    }
}

return [
    'base_url' => detect_base_url(),
    'timezone' => 'Asia/Kolkata',
    'debug' => false,
];
