<?php
declare(strict_types=1);

$bootstrapContext = 'install';
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (is_installed()) {
    Response::redirect('/install/complete.php');
}

$error = '';
$testSuccess = '';
$form = [
    'db_host' => $_POST['db_host'] ?? '127.0.0.1',
    'db_port' => (int)($_POST['db_port'] ?? 3306),
    'db_name' => trim((string)($_POST['db_name'] ?? '')),
    'db_user' => trim((string)($_POST['db_user'] ?? '')),
    'db_pass' => (string)($_POST['db_pass'] ?? ''),
    'site_name' => trim((string)($_POST['site_name'] ?? 'ApexSMM')),
    'admin_username' => trim((string)($_POST['admin_username'] ?? 'admin')),
    'admin_email' => trim((string)($_POST['admin_email'] ?? '')),
    'admin_name' => trim((string)($_POST['admin_name'] ?? 'Super Administrator')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = $_POST['action'] ?? 'install';

    if ($form['db_host'] === '' || $form['db_name'] === '' || $form['db_user'] === '') {
        $error = 'Database host, name, and username are required.';
    } else {
        try {
            $pdo = Database::connectWith($form['db_host'], $form['db_port'], $form['db_name'], $form['db_user'], $form['db_pass']);
            if ($action === 'test') {
                $testSuccess = 'MySQL connection succeeded! Ready to install database and Super Admin.';
            } else {
                // Validate Super Admin inputs
                if (!Validator::isUsername($form['admin_username'])) {
                    throw new RuntimeException('Admin username must be 3-40 alphanumeric/underscore characters.');
                }
                if (!Validator::isEmail($form['admin_email'])) {
                    throw new RuntimeException('Please provide a valid Super Admin email address.');
                }
                $adminPass = (string)($_POST['admin_password'] ?? '');
                if (strlen($adminPass) < 8) {
                    throw new RuntimeException('Super Admin password must be at least 8 characters.');
                }

                // Execute install.sql & seed.sql
                $installSql = file_get_contents(DATABASE_PATH . '/install.sql');
                $seedSql = file_get_contents(DATABASE_PATH . '/seed.sql');
                if ($installSql === false || $seedSql === false) {
                    throw new RuntimeException('Missing database/install.sql or database/seed.sql.');
                }

                $execSqlBatch = static function (PDO $conn, string $sql): void {
                    $lines = preg_split('/\R/', $sql);
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

                $execSqlBatch($pdo, $installSql);
                $execSqlBatch($pdo, $seedSql);

                // Generate local security & database configs
                $appKey = bin2hex(random_bytes(32));
                $cronToken = bin2hex(random_bytes(24));

                $dbExport = "<?php\ndeclare(strict_types=1);\nreturn " . var_export([
                    'host' => $form['db_host'],
                    'port' => $form['db_port'],
                    'database' => $form['db_name'],
                    'username' => $form['db_user'],
                    'password' => $form['db_pass'],
                    'charset' => 'utf8mb4',
                    'installed' => true,
                ], true) . ";\n";

                $secExport = "<?php\ndeclare(strict_types=1);\nreturn " . var_export([
                    'app_key' => $appKey,
                    'cron_token' => $cronToken,
                ], true) . ";\n";

                if (file_put_contents(DB_LOCAL_CONFIG_FILE, $dbExport, LOCK_EX) === false) {
                    throw new RuntimeException('Failed to write config/database.local.php.');
                }
                if (file_put_contents(SECURITY_LOCAL_CONFIG_FILE, $secExport, LOCK_EX) === false) {
                    throw new RuntimeException('Failed to write config/security.local.php.');
                }

                // Update site name if customized
                if ($form['site_name'] !== '') {
                    $stmtSite = $pdo->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
                    $stmtSite->execute([$form['site_name'], 'site_name']);
                    $stmtMailName = $pdo->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
                    $stmtMailName->execute([$form['site_name'], 'mail_from_name']);
                }

                // Create Initial Super Admin (role_id = 1)
                $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmtAdmin = $pdo->prepare(
                    'INSERT INTO admins (role_id, username, email, full_name, password_hash, status, created_at, updated_at)
                     VALUES (1, ?, ?, ?, ?, ?, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE email = VALUES(email), full_name = VALUES(full_name), password_hash = VALUES(password_hash), status = VALUES(status)'
                );
                $stmtAdmin->execute([$form['admin_username'], strtolower($form['admin_email']), $form['admin_name'], $hash, 'active']);

                // Verify tables exist
                $tableCount = (int)$pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ' . $pdo->quote($form['db_name']))->fetchColumn();
                if ($tableCount < 20) {
                    throw new RuntimeException('Database verification failed: incomplete schema.');
                }

                // Lock installer
                file_put_contents(INSTALL_LOCK_FILE, "Installed at " . date('c') . "\n", LOCK_EX);

                Response::redirect('/install/complete.php');
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ApexSMM Installer — Step 3: Database &amp; Super Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-4">
  <div class="max-w-2xl w-full bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">MySQL Configuration &amp; Super Admin</h1>
    <p class="text-sm text-slate-500 mb-6">Configure your MySQL connection and create the initial Super Admin account.</p>

    <?php if ($error !== ''): ?>
      <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($testSuccess !== ''): ?>
      <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm"><?= e($testSuccess) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <?= CSRF::field() ?>

      <div>
        <h2 class="text-xs font-bold uppercase tracking-wider text-violet-700 mb-3">MySQL Database Connection</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Database Host</label>
            <input type="text" name="db_host" value="<?= e($form['db_host']) ?>" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Port</label>
            <input type="number" name="db_port" value="<?= e($form['db_port']) ?>" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Database Name</label>
            <input type="text" name="db_name" value="<?= e($form['db_name']) ?>" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Database Username</label>
            <input type="text" name="db_user" value="<?= e($form['db_user']) ?>" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Database Password</label>
            <input type="password" name="db_pass" value="" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm" autocomplete="new-password">
          </div>
        </div>
      </div>

      <hr class="border-slate-200">

      <div>
        <h2 class="text-xs font-bold uppercase tracking-wider text-violet-700 mb-3">Website &amp; Super Admin Account</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Panel Brand Name</label>
            <input type="text" name="site_name" value="<?= e($form['site_name']) ?>" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Admin Username</label>
            <input type="text" name="admin_username" value="<?= e($form['admin_username']) ?>" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Admin Full Name</label>
            <input type="text" name="admin_name" value="<?= e($form['admin_name']) ?>" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Admin Email</label>
            <input type="email" name="admin_email" value="<?= e($form['admin_email']) ?>" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Admin Password (min 8 chars)</label>
            <input type="password" name="admin_password" minlength="8" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm" autocomplete="new-password">
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
        <a href="requirements.php" class="px-4 py-2 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
        <div class="flex items-center gap-3">
          <button type="submit" name="action" value="test" class="px-4 py-2.5 rounded-xl border border-violet-300 text-violet-700 bg-violet-50 hover:bg-violet-100 text-sm font-semibold">Test Connection</button>
          <button type="submit" name="action" value="install" class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold">Install &amp; Lock Setup</button>
        </div>
      </div>
    </form>
  </div>
</body>
</html>
