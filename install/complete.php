<?php
declare(strict_types=1);

$bootstrapContext = 'install';
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!is_installed()) {
    Response::redirect('/install/index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ApexSMM Installer — Complete</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-4">
  <div class="max-w-xl w-full bg-white border border-slate-200 rounded-2xl shadow-sm p-8 text-center">
    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
      <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-2xl font-bold text-slate-900 mb-2">Installation Complete &amp; Locked</h1>
    <p class="text-sm text-slate-600 mb-6">Your database schema, security keys, and Super Admin account are ready. The installer has been locked via <code>storage/installed.lock</code> to prevent unauthorized reuse.</p>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-left text-xs text-amber-900 mb-6">
      <strong>Recommended Security Step:</strong> You may also remove or restrict permissions on the <code>/install/</code> folder on production cPanel hosting.
    </div>

    <div class="flex flex-col sm:flex-row justify-center gap-3">
      <a href="<?= e(app_url('/')) ?>" class="px-5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50">View Public Website</a>
      <a href="<?= e(app_url('/admin/login.php')) ?>" class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold">Open Admin Panel</a>
    </div>
  </div>
</body>
</html>
