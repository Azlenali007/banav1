<?php
declare(strict_types=1);
/** @var array $ticket @var array $messages */
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <a href="<?= e(app_url('/user/tickets.php')) ?>" class="text-xs font-semibold text-violet-700 hover:underline">&larr; Back to Tickets</a>
    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1"><?= e($ticket['subject']) ?></h1>
    <div class="flex items-center gap-2 mt-1 text-xs text-slate-500">
      <span class="font-mono font-bold text-violet-700"><?= e($ticket['ticket_uid']) ?></span>
      <span>&bull;</span>
      <span class="badge <?= e(status_badge_class((string)$ticket['status'])) ?>"><?= e($ticket['status']) ?></span>
    </div>
  </div>
  <?php if ($ticket['status'] !== 'closed'): ?>
    <form method="POST">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="close">
      <button type="submit" class="btn-secondary px-4 py-2 text-xs">Close Ticket</button>
    </form>
  <?php endif; ?>
</div>

<div class="smm-card p-6 space-y-4 mb-6">
  <?php foreach ($messages as $m): ?>
    <?php $isUser = $m['sender_type'] === 'user'; ?>
    <div class="flex <?= $isUser ? 'justify-end' : 'justify-start' ?>">
      <div class="max-w-2xl rounded-2xl px-4 py-3 text-sm <?= $isUser ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-900 border border-slate-200' ?>">
        <div class="text-[11px] font-bold opacity-75 mb-1"><?= $isUser ? 'You' : 'Support Staff' ?> &bull; <?= e(format_datetime($m['created_at'])) ?></div>
        <div class="whitespace-pre-line leading-relaxed"><?= e($m['message']) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($ticket['status'] !== 'closed'): ?>
  <div class="smm-card p-6">
    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="reply">
      <div>
        <label class="smm-label">Reply Message</label>
        <textarea name="message" rows="4" required class="smm-input" placeholder="Write your reply..."></textarea>
      </div>
      <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Send Reply</button>
    </form>
  </div>
<?php endif; ?>
