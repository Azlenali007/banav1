<?php
declare(strict_types=1);
/** @var array $currentUser @var ?array $apiRecord @var ?string $newlyGeneratedKey @var string $endpointUrl */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">API Access &amp; Documentation</h1>
  <p class="text-sm text-slate-500">Connect your external store or reseller panel via standard JSON REST API.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- API Key Card -->
  <div class="smm-card p-6 space-y-4 h-fit">
    <div class="flex items-center justify-between">
      <h2 class="font-bold text-slate-900">Your API Key</h2>
      <?php if (empty($currentUser['api_enabled'])): ?>
        <span class="badge badge-danger">Disabled by Admin</span>
      <?php elseif ($apiRecord && $apiRecord['status'] === 'active'): ?>
        <span class="badge badge-success">Active</span>
      <?php else: ?>
        <span class="badge badge-neutral">Not Generated</span>
      <?php endif; ?>
    </div>

    <?php if ($newlyGeneratedKey): ?>
      <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs">
        <div class="font-bold text-emerald-900 mb-1">Copy Your New API Key (shown once):</div>
        <div class="flex items-center gap-2">
          <code class="flex-1 bg-white px-2.5 py-1.5 rounded border border-emerald-200 font-mono text-emerald-800 break-all"><?= e($newlyGeneratedKey) ?></code>
          <button type="button" onclick="SMM.copyText(<?= e(json_encode($newlyGeneratedKey)) ?>, this)" class="btn-secondary px-2.5 py-1.5 text-xs">Copy</button>
        </div>
      </div>
    <?php elseif ($apiRecord): ?>
      <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
        <div class="text-slate-400">Stored Key Prefix</div>
        <div class="font-mono font-bold text-slate-800"><?= e($apiRecord['api_key_prefix']) ?></div>
        <div class="text-slate-400 pt-1">Last Used: <?= e(format_datetime($apiRecord['last_used_at'])) ?></div>
      </div>
    <?php endif; ?>

    <div class="flex flex-col gap-2 pt-2">
      <form method="POST">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="generate">
        <button type="submit" class="btn-primary w-full py-2.5 text-sm"><?= $apiRecord ? 'Regenerate API Key' : 'Generate API Key' ?></button>
      </form>
      <?php if ($apiRecord && $apiRecord['status'] === 'active'): ?>
        <form method="POST">
          <?= CSRF::field() ?>
          <input type="hidden" name="action" value="revoke">
          <button type="submit" class="btn-secondary w-full py-2 text-xs text-red-700 border-red-200 hover:bg-red-50">Revoke Key</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- API Documentation -->
  <div class="lg:col-span-2 smm-card p-6 space-y-6">
    <div>
      <h2 class="font-bold text-slate-900 text-lg mb-2">API v1 Endpoint Specification</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
          <div class="text-slate-400">API Endpoint URL</div>
          <div class="font-mono font-bold text-violet-700 break-all mt-0.5"><?= e($endpointUrl) ?></div>
        </div>
        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
          <div class="text-slate-400">Method &amp; Format</div>
          <div class="font-semibold text-slate-800 mt-0.5">HTTP POST (form or JSON) &rarr; JSON</div>
        </div>
      </div>
    </div>

    <?php
    $endpoints = [
        ['Balance', 'key, action=balance', '{"balance":"1250.0000","currency":"INR"}'],
        ['Services List', 'key, action=services', '[{"service":1,"name":"Instagram Followers","category":"Instagram","rate":"45.0000","min":10,"max":50000,"refill":true,"cancel":false}]'],
        ['Add Order', 'key, action=add, service, link, quantity, [idempotency_key]', '{"order":1042,"order_uid":"ORD-A1B2C3D4E5","charge":"45.0000"}'],
        ['Order Status', 'key, action=status, order (ID or order_uid)', '{"order":1042,"status":"processing","charge":"45.0000","start_count":120,"remains":1000,"currency":"INR"}'],
        ['Request Refill', 'key, action=refill, order (ID or order_uid)', '{"refill":1042,"status":"requested"}'],
    ];
    foreach ($endpoints as [$title, $params, $resp]):
    ?>
      <div class="border border-slate-200 rounded-xl overflow-hidden">
        <div class="bg-slate-50 px-4 py-2.5 border-b border-slate-200 flex items-center justify-between">
          <span class="font-bold text-sm text-slate-900"><?= e($title) ?></span>
          <span class="font-mono text-[11px] text-violet-700"><?= e($params) ?></span>
        </div>
        <pre class="p-4 text-xs font-mono bg-slate-900 text-emerald-300 overflow-x-auto"><?= e($resp) ?></pre>
      </div>
    <?php endforeach; ?>
  </div>
</div>
