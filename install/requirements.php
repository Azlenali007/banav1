<?php
declare(strict_types=1);

$bootstrapContext = 'install';
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (is_installed()) {
    Response::redirect('/install/complete.php');
}

$phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
$extensions = [
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'curl' => extension_loaded('curl'),
    'openssl' => extension_loaded('openssl'),
    'json' => extension_loaded('json'),
    'mbstring' => extension_loaded('mbstring'),
    'fileinfo' => extension_loaded('fileinfo'),
];

$dirs = [
    'config/' => CONFIG_PATH,
    'storage/' => STORAGE_PATH,
    'storage/logs/' => LOGS_PATH,
    'storage/backups/' => BACKUPS_PATH,
    'storage/cache/' => CACHE_PATH,
    'storage/uploads/' => UPLOADS_PATH,
    'storage/temp/' => TEMP_PATH,
];

$dirResults = [];
foreach ($dirs as $label => $path) {
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
    $dirResults[$label] = is_dir($path) && is_writable($path);
}

$allPassed = $phpOk && !in_array(false, $extensions, true) && !in_array(false, $dirResults, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ApexSMM Installer — Step 2: Server Requirements</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-4">
  <div class="max-w-2xl w-full bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Server Requirements &amp; Permissions</h1>
    <p class="text-sm text-slate-500 mb-6">Confirm PHP version, required extensions, and writable directories.</p>

    <div class="space-y-5 mb-8">
      <div class="border border-slate-200 rounded-xl overflow-hidden">
        <div class="bg-slate-50 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">PHP Environment</div>
        <div class="p-4 flex items-center justify-between text-sm">
          <span>PHP Version (&gt;= 8.1.0 required)</span>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $phpOk ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>">
            <?= e(PHP_VERSION) ?> <?= $phpOk ? 'OK' : 'FAIL' ?>
          </span>
        </div>
      </div>

      <div class="border border-slate-200 rounded-xl overflow-hidden">
        <div class="bg-slate-50 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">Required PHP Extensions</div>
        <div class="divide-y divide-slate-100">
          <?php foreach ($extensions as $ext => $ok): ?>
            <div class="px-4 py-2.5 flex items-center justify-between text-sm">
              <span class="font-mono text-xs"><?= e($ext) ?></span>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $ok ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>">
                <?= $ok ? 'Enabled' : 'Missing' ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="border border-slate-200 rounded-xl overflow-hidden">
        <div class="bg-slate-50 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">Directory Permissions</div>
        <div class="divide-y divide-slate-100">
          <?php foreach ($dirResults as $label => $writable): ?>
            <div class="px-4 py-2.5 flex items-center justify-between text-sm">
              <span class="font-mono text-xs"><?= e($label) ?></span>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $writable ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>">
                <?= $writable ? 'Writable' : 'Not Writable' ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="flex items-center justify-between">
      <a href="index.php" class="px-4 py-2 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
      <?php if ($allPassed): ?>
        <a href="database.php" class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold">Continue to Database &rarr;</a>
      <?php else: ?>
        <a href="requirements.php" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold">Re-check Requirements</a>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
