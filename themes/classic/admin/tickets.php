<?php
declare(strict_types=1);
/** @var string $statusFilter @var string $q @var array $tickets @var array $pg */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Support Tickets</h1>
  <p class="text-sm text-slate-500">Review, reply, close, and reopen user support requests.</p>
</div>

<div class="flex flex-wrap gap-2 mb-4">
  <?php foreach (array_merge(['all'], TICKET_STATUSES) as $st): ?>
    <a href="?status=<?= e($st) ?>&q=<?= e(urlencode($q)) ?>" class="px-3.5 py-1.5 rounded-full text-xs font-semibold border <?= $statusFilter === $st ? 'bg-violet-600 text-white border-violet-600' : 'bg-white text-slate-600 border-slate-200' ?>">
      <?= e(ucfirst($st)) ?>
    </a>
  <?php endforeach; ?>
</div>

<form method="GET" class="smm-card p-4 mb-5 flex gap-3">
  <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search Ticket UID, Subject, or Username..." class="smm-input flex-1">
  <button type="submit" class="btn-primary px-5 py-2 text-sm">Search</button>
</form>

<div class="smm-card overflow-hidden">
  <div class="smm-table-wrap">
    <table class="smm-table">
      <thead><tr><th>Ticket</th><th>User</th><th>Subject</th><th>Priority</th><th>Status</th><th>Last Reply</th><th class="text-right">Action</th></tr></thead>
      <tbody>
        <?php if (empty($tickets)): ?>
          <tr><td colspan="7" class="text-center text-slate-500 py-8">No support tickets found.</td></tr>
        <?php else: foreach ($tickets as $t): ?>
          <tr>
            <td class="font-mono text-xs font-bold text-violet-700"><?= e($t['ticket_uid']) ?></td>
            <td class="text-xs font-semibold"><?= e($t['username']) ?></td>
            <td class="text-sm font-medium"><?= e($t['subject']) ?></td>
            <td class="text-xs uppercase"><?= e($t['priority']) ?></td>
            <td><span class="badge <?= e(status_badge_class((string)$t['status'])) ?>"><?= e($t['status']) ?></span></td>
            <td class="text-xs text-slate-500"><?= e(format_datetime($t['last_reply_at'])) ?></td>
            <td class="text-right"><a href="<?= e(app_url('/admin/ticket-view.php?id=' . (int)$t['id'])) ?>" class="btn-secondary px-3 py-1 text-xs">Open</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php Theme::component('shared/pagination', ['pagination' => $pg, 'baseQuery' => ['status' => $statusFilter, 'q' => $q], 'itemLabel' => 'tickets']); ?>
</div>
