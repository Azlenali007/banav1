<?php
declare(strict_types=1);
/** @var string $reportType @var string $from @var string $to @var array $rows */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Reports &amp; CSV Exports</h1>
    <p class="text-sm text-slate-500">Filter real database reports by date range and export CSV spreadsheets.</p>
  </div>
  <a href="?type=<?= e($reportType) ?>&from=<?= e($from) ?>&to=<?= e($to) ?>&export=csv" class="btn-primary px-4 py-2 text-sm">Export CSV</a>
</div>

<form method="GET" class="smm-card p-4 mb-6 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
  <div>
    <label class="smm-label">Report Type</label>
    <select name="type" class="smm-input">
      <option value="sales" <?= $reportType === 'sales' ? 'selected' : '' ?>>Sales &amp; Profit Report</option>
      <option value="orders" <?= $reportType === 'orders' ? 'selected' : '' ?>>Orders Report</option>
      <option value="users" <?= $reportType === 'users' ? 'selected' : '' ?>>Users Report</option>
      <option value="payments" <?= $reportType === 'payments' ? 'selected' : '' ?>>Payments Report</option>
      <option value="providers" <?= $reportType === 'providers' ? 'selected' : '' ?>>Provider Performance</option>
    </select>
  </div>
  <div><label class="smm-label">From Date</label><input type="date" name="from" value="<?= e($from) ?>" class="smm-input"></div>
  <div><label class="smm-label">To Date</label><input type="date" name="to" value="<?= e($to) ?>" class="smm-input"></div>
  <button type="submit" class="btn-primary py-2.5 px-5 text-sm">Run Report</button>
</form>

<div class="smm-card overflow-hidden">
  <?php if (empty($rows)): ?>
    <div class="p-10 text-center text-sm text-slate-500">No records found for this date range.</div>
  <?php else: ?>
    <div class="smm-table-wrap">
      <table class="smm-table">
        <thead>
          <tr>
            <?php foreach (array_keys($rows[0]) as $col): ?>
              <th><?= e(str_replace('_', ' ', $col)) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <?php foreach ($r as $val): ?>
                <td class="text-xs"><?= e((string)$val) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
