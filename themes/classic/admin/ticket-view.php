<?php
declare(strict_types=1);
/** @var int $ticketId @var array $ticket @var array $messages */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <a href="<?= e(app_url('/admin/tickets.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">&larr; Back to Tickets</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-1"><?= e($ticket['subject']) ?></h1>
    <div class="text-xs text-slate-500 mt-1">
      <?= e($ticket['ticket_uid']) ?> &bull; User: <a href="<?= e(app_url('/admin/user-view.php?id=' . (int)$ticket['user_id'])) ?>" class="text-violet-700 font-semibold hover:underline"><?= e($ticket['username']) ?></a>
      &bull; <span class="badge <?= e(status_badge_class((string)$ticket['status'])) ?>"><?= e($ticket['status']) ?></span>
    </div>
  </div>
  <?php if (admin_can('tickets.manage')): ?>
    <form method="POST">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="<?= $ticket['status'] === 'closed' ? 'reopen' : 'close' ?>">
      <button type="submit" class="btn-secondary px-4 py-2 text-xs"><?= $ticket['status'] === 'closed' ? 'Reopen Ticket' : 'Close Ticket' ?></button>
    </form>
  <?php endif; ?>
</div>

<div class="smm-card p-6 space-y-4 mb-6">
  <?php foreach ($messages as $m): ?>
    <?php $isAdmin = $m['sender_type'] === 'admin'; ?>
    <div class="flex <?= $isAdmin ? 'justify-end' : 'justify-start' ?>">
      <div class="max-w-2xl rounded-2xl px-4 py-3 text-sm <?= $isAdmin ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-900 border border-slate-200' ?>">
        <div class="text-[11px] font-bold opacity-75 mb-1"><?= $isAdmin ? 'Staff Admin' : e($ticket['username']) ?> &bull; <?= e(format_datetime($m['created_at'])) ?></div>
        <div class="whitespace-pre-line leading-relaxed"><?= e($m['message']) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if (admin_can('tickets.manage')): ?>
<div class="smm-card p-6">
  <form method="POST" class="space-y-4">
    <?= CSRF::field() ?>
    <input type="hidden" name="action" value="reply">
    <div><label class="smm-label">Admin Reply</label><textarea name="message" rows="4" required class="smm-input" placeholder="Write response to customer..."></textarea></div>
    <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Send Reply</button>
  </form>
</div>
<?php endif; ?>
