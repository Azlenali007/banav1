<?php
declare(strict_types=1);
/** @var string $tab @var array $rows @var array $logNames @var string $selectedFile @var string $fileContent */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Logs &amp; Audit Trail</h1>
  <p class="text-sm text-slate-500">Inspect admin activity, provider API communications, error logs, and payment/cron log files.</p>
</div>

<div class="flex flex-wrap gap-2 mb-5">
  <?php
  $tabs = ['activity' => 'Admin Activity', 'provider' => 'Provider API Logs', 'error' => 'Error Logs', 'files' => 'File Logs (Payment / Cron / App)'];
  foreach ($tabs as $k => $lbl):
  ?>
    <a href="?tab=<?= e($k) ?>" class="px-4 py-2 rounded-xl text-xs font-semibold border <?= $tab === $k ? 'bg-violet-600 text-white border-violet-600' : 'bg-white text-slate-600 border-slate-200' ?>"><?= e($lbl) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'files'): ?>
  <div class="smm-card p-6 space-y-4">
    <?php if (empty($logNames)): ?>
      <div class="text-sm text-slate-500">No log files recorded in storage/logs/ yet.</div>
    <?php else: ?>
      <div class="flex flex-wrap gap-2">
        <?php foreach ($logNames as $ln): ?>
          <a href="?tab=files&file=<?= e(urlencode($ln)) ?>" class="px-3 py-1 rounded-lg text-xs font-mono border <?= $selectedFile === $ln ? 'bg-violet-100 border-violet-300 text-violet-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600' ?>"><?= e($ln) ?></a>
        <?php endforeach; ?>
      </div>
      <pre class="p-4 rounded-xl bg-slate-900 text-slate-200 text-xs font-mono overflow-x-auto max-h-[500px]"><?= e($fileContent ?: 'Log file is empty.') ?></pre>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="smm-card overflow-hidden">
    <div class="smm-table-wrap">
      <table class="smm-table">
        <?php if ($tab === 'activity'): ?>
          <thead><tr><th>Time</th><th>Admin</th><th>Action</th><th>Target</th><th>Details</th><th>IP</th></tr></thead>
          <tbody>
            <?php if (empty($rows)): ?><tr><td colspan="6" class="text-center text-slate-500 py-8">No activity logs.</td></tr><?php else: foreach ($rows as $r): ?>
              <tr>
                <td class="text-xs text-slate-500 whitespace-nowrap"><?= e(format_datetime($r['created_at'])) ?></td>
                <td class="text-xs font-semibold"><?= e($r['admin_username'] ?: 'System') ?></td>
                <td class="font-mono text-xs text-violet-700"><?= e($r['action']) ?></td>
                <td class="text-xs"><?= e(($r['target_type'] ?? '') . ($r['target_id'] ? ' #' . $r['target_id'] : '')) ?></td>
                <td class="text-xs max-w-md"><?= e($r['details']) ?></td>
                <td class="font-mono text-xs text-slate-400"><?= e($r['ip_address']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        <?php elseif ($tab === 'provider'): ?>
          <thead><tr><th>Time</th><th>Provider</th><th>Action</th><th>HTTP</th><th>Result</th><th>Error / Response</th></tr></thead>
          <tbody>
            <?php if (empty($rows)): ?><tr><td colspan="6" class="text-center text-slate-500 py-8">No provider logs.</td></tr><?php else: foreach ($rows as $r): ?>
              <tr>
                <td class="text-xs text-slate-500 whitespace-nowrap"><?= e(format_datetime($r['created_at'])) ?></td>
                <td class="text-xs font-semibold"><?= e($r['provider_name'] ?: ('#' . $r['provider_id'])) ?></td>
                <td class="font-mono text-xs"><?= e($r['action']) ?></td>
                <td class="text-xs"><?= (int)$r['http_code'] ?></td>
                <td><span class="badge <?= !empty($r['success']) ? 'badge-success' : 'badge-danger' ?>"><?= !empty($r['success']) ? 'OK' : 'Fail' ?></span></td>
                <td class="text-xs max-w-md truncate"><?= e($r['error_message'] ?: $r['response_payload']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        <?php else: ?>
          <thead><tr><th>Time</th><th>Context</th><th>Message</th><th>Location</th></tr></thead>
          <tbody>
            <?php if (empty($rows)): ?><tr><td colspan="4" class="text-center text-slate-500 py-8">No error logs recorded.</td></tr><?php else: foreach ($rows as $r): ?>
              <tr>
                <td class="text-xs text-slate-500 whitespace-nowrap"><?= e(format_datetime($r['created_at'])) ?></td>
                <td class="font-mono text-xs text-red-700"><?= e($r['context']) ?></td>
                <td class="text-xs max-w-lg"><?= e($r['message']) ?></td>
                <td class="font-mono text-[11px] text-slate-400"><?= e(($r['file'] ?? '') . ':' . ($r['line'] ?? '')) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        <?php endif; ?>
      </table>
    </div>
  </div>
<?php endif; ?>
