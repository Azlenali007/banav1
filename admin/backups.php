<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('backups.manage');

function generate_mysql_dump(): string
{
    $pdo = Database::getInstance();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $dump = "-- ApexSMM MySQL Backup\n-- Generated: " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $createRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        $dump .= "DROP TABLE IF EXISTS `{$table}`;\n" . ($createRow[1] ?? '') . ";\n\n";

        $stmt = $pdo->query("SELECT * FROM `{$table}`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cols = array_map(static fn($c) => "`{$c}`", array_keys($row));
            $vals = array_map(static fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($row));
            $dump .= "INSERT INTO `{$table}` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
        }
        $dump .= "\n";
    }
    $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $dump;
}

if (!empty($_GET['download'])) {
    $file = basename((string)$_GET['download']);
    $path = BACKUPS_PATH . '/' . $file;
    if (!preg_match('/^backup-[a-zA-Z0-9_\-]+\.sql$/', $file) || !is_file($path)) {
        Response::abort(404, 'Backup Not Found', 'The requested backup file does not exist.');
    }
    Logger::adminActivity((int)$currentAdmin['id'], 'backup.download', 'backup', $file, 'Downloaded database backup');
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create_backup') {
        if (!is_dir(BACKUPS_PATH)) {
            @mkdir(BACKUPS_PATH, 0755, true);
        }
        $filename = 'backup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.sql';
        $sql = generate_mysql_dump();
        if (file_put_contents(BACKUPS_PATH . '/' . $filename, $sql, LOCK_EX) !== false) {
            Logger::adminActivity((int)$currentAdmin['id'], 'backup.create', 'backup', $filename, 'Created MySQL database backup');
            Session::setFlash('success', "Backup {$filename} created safely in protected storage.");
        } else {
            Session::setFlash('error', 'Failed to write backup file to storage/backups/.');
        }
    } elseif ($action === 'delete_backup') {
        $file = basename((string)($_POST['file'] ?? ''));
        $path = BACKUPS_PATH . '/' . $file;
        if (preg_match('/^backup-[a-zA-Z0-9_\-]+\.sql$/', $file) && is_file($path)) {
            @unlink($path);
            Logger::adminActivity((int)$currentAdmin['id'], 'backup.delete', 'backup', $file, 'Deleted database backup');
            Session::setFlash('success', 'Backup deleted.');
        }
    } elseif ($action === 'save_retention') {
        $days = max(1, min(365, (int)($_POST['backup_retention_days'] ?? 14)));
        set_setting('backup', 'backup_retention_days', (string)$days);
        Session::setFlash('success', "Backup retention updated to {$days} days.");
    }
    Response::redirect('/admin/backups.php');
}

$files = glob(BACKUPS_PATH . '/backup-*.sql') ?: [];
rsort($files);
$backupItems = array_map(static fn(string $f): array => [
    'name' => basename($f),
    'size' => (int)@filesize($f),
    'mtime' => (int)@filemtime($f),
], $files);
$retentionDays = (int)get_setting('backup_retention_days', '14');

Theme::render('admin/backups', [
    'backupItems' => $backupItems,
    'retentionDays' => $retentionDays,
], 'admin', [
    'pageTitle' => 'Database Backups',
    'currentAdmin' => $currentAdmin,
]);
