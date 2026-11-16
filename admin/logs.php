<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('logs.view');

$tab = in_array($_GET['tab'] ?? '', ['activity', 'provider', 'error', 'files'], true) ? $_GET['tab'] : 'activity';
$rows = [];
$logNames = [];
$fileContent = '';
$selectedFile = '';

if ($tab === 'activity') {
    $rows = Database::fetchAll(
        'SELECT l.*, a.username AS admin_username FROM admin_activity_logs l LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.id DESC LIMIT 100'
    );
} elseif ($tab === 'provider') {
    $rows = Database::fetchAll(
        'SELECT l.*, p.name AS provider_name FROM provider_logs l LEFT JOIN providers p ON p.id = l.provider_id ORDER BY l.id DESC LIMIT 100'
    );
} elseif ($tab === 'error') {
    $rows = Database::fetchAll('SELECT * FROM error_logs ORDER BY id DESC LIMIT 100');
} elseif ($tab === 'files') {
    $logFiles = glob(LOGS_PATH . '/*.log') ?: [];
    rsort($logFiles);
    $logNames = array_map('basename', $logFiles);
    $reqFile = basename((string)($_GET['file'] ?? ($logNames[0] ?? '')));
    if ($reqFile !== '' && in_array($reqFile, $logNames, true)) {
        $selectedFile = $reqFile;
        $raw = @file_get_contents(LOGS_PATH . '/' . $reqFile);
        $fileContent = $raw !== false ? mb_substr($raw, -16000) : '';
    }
}

Theme::render('admin/logs', [
    'tab' => $tab,
    'rows' => $rows,
    'logNames' => $logNames,
    'selectedFile' => $selectedFile,
    'fileContent' => $fileContent,
], 'admin', [
    'pageTitle' => 'System & Audit Logs',
    'currentAdmin' => $currentAdmin,
]);
