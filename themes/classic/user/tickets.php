<?php
declare(strict_types=1);
/** @var string $error @var array $tickets @var string $subjectVal @var string $categoryVal @var string $priorityVal @var string $messageVal */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Support Tickets</h1>
  <p class="text-sm text-slate-500">Reach our support team regarding orders, billing, or API integration.</p>
</div>

<?php if ($error !== ''): ?>
  <div class="mb-5 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"><?= e($error) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- New Ticket Form -->
  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-4">Create New Ticket</h2>
    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <div>
        <label class="smm-label">Subject</label>
        <input type="text" name="subject" value="<?= e($subjectVal) ?>" required minlength="4" class="smm-input" placeholder="e.g. Order #ORD-XXXX status inquiry">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="smm-label">Category</label>
          <select name="category" class="smm-input">
            <option value="order" <?= $categoryVal === 'order' ? 'selected' : '' ?>>Order</option>
            <option value="payment" <?= $categoryVal === 'payment' ? 'selected' : '' ?>>Payment / Wallet</option>
            <option value="api" <?= $categoryVal === 'api' ? 'selected' : '' ?>>API</option>
            <option value="general" <?= $categoryVal === 'general' ? 'selected' : '' ?>>General</option>
          </select>
        </div>
        <div>
          <label class="smm-label">Priority</label>
          <select name="priority" class="smm-input">
            <option value="low" <?= $priorityVal === 'low' ? 'selected' : '' ?>>Low</option>
            <option value="medium" <?= $priorityVal === 'medium' ? 'selected' : '' ?>>Medium</option>
            <option value="high" <?= $priorityVal === 'high' ? 'selected' : '' ?>>High</option>
          </select>
        </div>
      </div>
      <div>
        <label class="smm-label">Message</label>
        <textarea name="message" rows="5" required minlength="10" class="smm-input" placeholder="Describe your issue with relevant Order ID or Payment Reference..."><?= e($messageVal) ?></textarea>
      </div>
      <button type="submit" class="btn-primary w-full py-2.5 text-sm">Submit Ticket</button>
    </form>
  </div>

  <!-- Ticket List -->
  <div class="lg:col-span-2 smm-card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-900">Your Ticket History</div>
    <?php if (empty($tickets)): ?>
      <div class="p-10 text-center text-sm text-slate-500">You have not created any support tickets yet.</div>
    <?php else: ?>
      <div class="smm-table-wrap">
        <table class="smm-table">
          <thead>
            <tr>
              <th>Ticket</th>
              <th>Subject</th>
              <th>Category</th>
              <th>Status</th>
              <th>Last Update</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tickets as $t): ?>
              <tr>
                <td class="font-mono text-xs font-bold text-violet-700"><?= e($t['ticket_uid']) ?></td>
                <td class="font-medium"><?= e($t['subject']) ?></td>
                <td class="text-xs uppercase text-slate-500"><?= e($t['category']) ?></td>
                <td><span class="badge <?= e(status_badge_class((string)$t['status'])) ?>"><?= e($t['status']) ?></span></td>
                <td class="text-xs text-slate-500"><?= e(format_datetime($t['last_reply_at'])) ?></td>
                <td class="text-right">
                  <a href="<?= e(app_url('/user/ticket-view.php?id=' . (int)$t['id'])) ?>" class="btn-secondary px-3 py-1 text-xs">Open</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
