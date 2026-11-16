<?php
declare(strict_types=1);

$bootstrapContext = 'install';
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (is_installed()) {
    Response::redirect('/install/complete.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ApexSMM Installer — Step 1: Welcome</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-4">
  <div class="max-w-xl w-full bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-purple-800 text-white flex items-center justify-center font-bold">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      </div>
      <div>
        <h1 class="text-xl font-bold text-slate-900">ApexSMM Web Installer</h1>
        <p class="text-xs text-slate-500">Version <?= e(APP_VERSION) ?> &bull; cPanel &amp; MySQL Compatible</p>
      </div>
    </div>

    <div class="flex items-center gap-2 text-xs font-semibold mb-6">
      <span class="px-2.5 py-1 rounded-full bg-violet-100 text-violet-800">1. Welcome</span>
      <span class="text-slate-300">&rarr;</span>
      <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">2. Requirements</span>
      <span class="text-slate-300">&rarr;</span>
      <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">3. Database &amp; Admin</span>
      <span class="text-slate-300">&rarr;</span>
      <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">4. Complete</span>
    </div>

    <div class="space-y-3 text-sm text-slate-600 leading-relaxed mb-8">
      <p>Welcome to the ApexSMM Panel setup wizard. This installer will verify your server environment, connect to your MySQL database, create all relational tables, initialize essential settings, and create your primary Super Admin account.</p>
      <div class="bg-violet-50 border border-violet-100 rounded-xl p-4 text-violet-900 text-xs space-y-1">
        <div class="font-bold">Before you begin, prepare:</div>
        <ul class="list-disc pl-4 space-y-0.5">
          <li>MySQL Hostname, Database Name, Username, and Password</li>
          <li>Writable permissions on <code>/config/</code> and <code>/storage/</code></li>
          <li>Super Admin email and strong password</li>
        </ul>
      </div>
    </div>

    <div class="flex justify-end">
      <a href="requirements.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition">
        <span>Start Installation</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>
  </div>
</body>
</html>
