<?php
declare(strict_types=1);
/** @var array $deposits @var array $transactions @var array $pg */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Wallet Ledger &amp; Deposits</h1>
    <p class="text-sm text-slate-500">Immutable record of every credit, debit, refund, and deposit request.</p>
  </div>
  <a href="<?= e(app_url('/user/add-funds.php')) ?>" class="btn-primary px-4 py-2 text-sm">+ Add Funds</a>
</div>

<!-- Deposits History -->
<div class="smm-card overflow-hidden mb-7">
  <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-900">Recent Deposit Requests</div>
  <?php if (empty($deposits)): ?>
    <div class="p-6 text-sm text-slate-500 text-center">No deposit requests submitted yet.</div>
  <?php else: ?>
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead>
          <tr>
            <th>Payment ID</th>
            <th>Method</th>
            <th>Amount</th>
            <th>Reference</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($deposits as $dep): ?>
            <tr>
              <td class="font-mono text-xs font-bold text-violet-700"><?= e($dep['payment_uid']) ?></td>
              <td class="uppercase text-xs font-semibold"><?= e($dep['method_code']) ?></td>
              <td class="font-semibold"><?= e(format_money($dep['amount'], 2)) ?></td>
              <td class="text-xs text-slate-500"><?= e($dep['transaction_reference'] ?: ($dep['gateway_payment_id'] ?: '—')) ?></td>
              <td><span class="badge <?= e(status_badge_class((string)$dep['status'])) ?>"><?= e($dep['status']) ?></span></td>
              <td class="text-xs text-slate-500"><?= e(format_datetime($dep['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Immutable Wallet Transactions -->
<div class="smm-card overflow-hidden">
  <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-900">Immutable Wallet Ledger</div>
  <?php if (empty($transactions)): ?>
    <div class="p-6 text-sm text-slate-500 text-center">No ledger entries recorded yet.</div>
  <?php else: ?>
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Type</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Balance Before</th>
            <th>Balance After</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $tx): ?>
            <?php $isCredit = in_array($tx['type'], ['credit', 'refund'], true) || ($tx['type'] === 'adjustment' && money_cmp((string)$tx['balance_after'], (string)$tx['balance_before']) >= 0); ?>
            <tr>
              <td class="font-mono text-xs text-slate-400">#<?= (int)$tx['id'] ?></td>
              <td><span class="badge <?= $isCredit ? 'badge-success' : 'badge-neutral' ?>"><?= e($tx['type']) ?></span></td>
              <td class="text-xs sm:text-sm"><?= e($tx['description']) ?></td>
              <td class="font-bold <?= $isCredit ? 'text-emerald-600' : 'text-red-600' ?>"><?= $isCredit ? '+' : '-' ?><?= e(format_money($tx['amount'], 2)) ?></td>
              <td class="text-xs text-slate-500"><?= e(format_money($tx['balance_before'], 2)) ?></td>
              <td class="text-xs font-semibold text-slate-800"><?= e(format_money($tx['balance_after'], 2)) ?></td>
              <td class="text-xs text-slate-500"><?= e(format_datetime($tx['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php Theme::component('shared/pagination', ['pagination' => $pg, 'baseQuery' => [], 'itemLabel' => 'transactions']); ?>
  <?php endif; ?>
</div>
