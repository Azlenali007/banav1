<?php
declare(strict_types=1);
/** @var string $q @var string $typeFilter @var array $rows @var array $pg */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Wallet Transaction Ledger</h1>
  <p class="text-sm text-slate-500">Immutable financial audit trail of all credits, debits, refunds, and admin adjustments.</p>
</div>

<form method="GET" class="smm-card p-4 mb-5 grid grid-cols-1 sm:grid-cols-4 gap-3">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search username, description, reference..." class="smm-input sm:col-span-2">
  <select name="type" class="smm-input">
    <option value="all" <?= $typeFilter === 'all' ? 'selected' : '' ?>>All Types</option>
    <option value="credit" <?= $typeFilter === 'credit' ? 'selected' : '' ?>>Credit</option>
    <option value="debit" <?= $typeFilter === 'debit' ? 'selected' : '' ?>>Debit</option>
    <option value="refund" <?= $typeFilter === 'refund' ? 'selected' : '' ?>>Refund</option>
    <option value="adjustment" <?= $typeFilter === 'adjustment' ? 'selected' : '' ?>>Admin Adjustment</option>
  </select>
  <button type="submit" class="btn-primary px-4 py-2 text-sm">Filter</button>
</form>

<div class="smm-card overflow-hidden">
  <div class="smm-table-wrap">
    <table class="smm-table">
      <thead><tr><th>ID</th><th>User</th><th>Type</th><th>Description</th><th>Amount</th><th>Before</th><th>After</th><th>Date</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="text-center text-slate-500 py-8">No transactions found.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <?php $isCredit = in_array($r['type'], ['credit', 'refund'], true) || ($r['type'] === 'adjustment' && money_cmp((string)$r['balance_after'], (string)$r['balance_before']) >= 0); ?>
          <tr>
            <td class="font-mono text-xs text-slate-400">#<?= (int)$r['id'] ?></td>
            <td><a href="<?= e(app_url('/admin/user-view.php?id=' . (int)$r['user_id'])) ?>" class="text-xs font-semibold text-violet-700 hover:underline"><?= e($r['username']) ?></a></td>
            <td><span class="badge <?= $isCredit ? 'badge-success' : 'badge-neutral' ?>"><?= e($r['type']) ?></span></td>
            <td class="text-xs"><?= e($r['description']) ?></td>
            <td class="text-xs font-bold <?= $isCredit ? 'text-emerald-600' : 'text-red-600' ?>"><?= $isCredit ? '+' : '-' ?><?= e(format_money($r['amount'], 2)) ?></td>
            <td class="text-xs text-slate-500"><?= e(format_money($r['balance_before'], 2)) ?></td>
            <td class="text-xs font-semibold"><?= e(format_money($r['balance_after'], 2)) ?></td>
            <td class="text-xs text-slate-500"><?= e(format_datetime($r['created_at'])) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php Theme::component('shared/pagination', ['pagination' => $pg, 'baseQuery' => ['q' => $q, 'type' => $typeFilter], 'itemLabel' => 'transactions']); ?>
</div>
