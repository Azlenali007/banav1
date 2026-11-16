<?php
declare(strict_types=1);

/**
 * Idempotent database bootstrap used by the Ideavo setup steps.
 * Installs the MySQL schema/seed once and generates the local install files.
 */

$root = dirname(__DIR__);
require_once $root . '/config/constants.php';

$home = getenv('HOME') ?: '/home/user';
$socket = $home . '/.local/mariadb-run/mysqld.sock';
$host = '127.0.0.1';
$port = 3306;
$name = 'apexsmm';
$user = 'apexsmm';
$pass = 'apexsmm';

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$rootPdo = new PDO('mysql:unix_socket=' . $socket . ';charset=utf8mb4', 'root', '', $pdoOptions);
$rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$rootPdo->exec("CREATE USER IF NOT EXISTS '{$user}'@'127.0.0.1' IDENTIFIED BY '{$pass}'");
$rootPdo->exec("CREATE USER IF NOT EXISTS '{$user}'@'localhost' IDENTIFIED BY '{$pass}'");
$rootPdo->exec("GRANT ALL PRIVILEGES ON `{$name}`.* TO '{$user}'@'127.0.0.1'");
$rootPdo->exec("GRANT ALL PRIVILEGES ON `{$name}`.* TO '{$user}'@'localhost'");
$rootPdo->exec('FLUSH PRIVILEGES');

$pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, $pdoOptions);

$execSqlFile = static function (PDO $conn, string $path): void {
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Unable to read ' . $path);
    }
    $lines = preg_split('/\R/', $sql) ?: [];
    $clean = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $clean[] = $line;
    }
    $statements = array_filter(array_map('trim', explode(";\n", implode("\n", $clean))));
    foreach ($statements as $stmt) {
        $stmt = rtrim($stmt, "; \t\n\r");
        if ($stmt !== '') {
            $conn->exec($stmt);
        }
    }
};

$tableCount = (int)$pdo->query(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($name)
)->fetchColumn();

if ($tableCount < 20) {
    $execSqlFile($pdo, DATABASE_PATH . '/install.sql');
    $execSqlFile($pdo, DATABASE_PATH . '/seed.sql');
    $tableCount = (int)$pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($name)
    )->fetchColumn();
} else {
    $roleCount = (int)$pdo->query('SELECT COUNT(*) FROM admin_roles')->fetchColumn();
    if ($roleCount === 0) {
        $execSqlFile($pdo, DATABASE_PATH . '/seed.sql');
    }
}

if (!is_file(DB_LOCAL_CONFIG_FILE)) {
    $dbConfig = [
        'host' => $host,
        'port' => $port,
        'database' => $name,
        'username' => $user,
        'password' => $pass,
        'charset' => 'utf8mb4',
        'installed' => true,
    ];
    file_put_contents(
        DB_LOCAL_CONFIG_FILE,
        "<?php\ndeclare(strict_types=1);\nreturn " . var_export($dbConfig, true) . ";\n",
        LOCK_EX
    );
}

if (!is_file(SECURITY_LOCAL_CONFIG_FILE)) {
    $security = [
        'app_key' => bin2hex(random_bytes(32)),
        'cron_token' => bin2hex(random_bytes(24)),
    ];
    file_put_contents(
        SECURITY_LOCAL_CONFIG_FILE,
        "<?php\ndeclare(strict_types=1);\nreturn " . var_export($security, true) . ";\n",
        LOCK_EX
    );
}

$adminCount = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount === 0) {
    $stmt = $pdo->prepare(
        'INSERT INTO admins (role_id, username, email, full_name, password_hash, status, created_at, updated_at)
         VALUES (1, ?, ?, ?, ?, ?, NOW(), NOW())'
    );
    $stmt->execute([
        'admin',
        'admin@example.com',
        'Super Administrator',
        password_hash('Admin@12345', PASSWORD_DEFAULT),
        'active',
    ]);
}

if (!is_file(INSTALL_LOCK_FILE)) {
    file_put_contents(INSTALL_LOCK_FILE, 'Installed at ' . date('c') . "\n", LOCK_EX);
}

fwrite(STDOUT, "Database ready: {$name} (" . $tableCount . " tables)\n");
