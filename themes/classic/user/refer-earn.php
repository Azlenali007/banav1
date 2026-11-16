<?php
declare(strict_types=1);
/** @var array $currentUser @var string $code @var array $stats @var array $referred @var string $referralLink @var bool $enabled */
$rewardType = (string)get_setting('referral_reward_type', 'fixed');
$rewardLabel = $rewardType === 'percent'
    ? money_format_dec((string)get_setting('referral_reward_percent', '0'), 2) . '%'
    : format_money((string)get_setting('referral_reward_amount', '0'), 2);
$event = (string)get_setting('referral_qualifying_event', 'signup');
$eventLabel = match ($event) {
    'first_order' => 'after their first order',
    'first_deposit' => 'after their first deposit',
    'manual' => 'once approved by our team',
    default => 'when they sign up',
};
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Refer &amp; Earn</h1>
  <p class="text-sm text-slate-500">Invite friends and earn <?= e($rewardLabel) ?> <?= e($eventLabel) ?>.</p>
</div>

<?php if (!$enabled): ?>
  <div class="smm-card p-6 border-amber-200 bg-amber-50 text-amber-900 text-sm">
    The referral program is currently paused. Please check back later.
  </div>
<?php else: ?>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="smm-card p-4">
      <div class="text-[11px] uppercase font-bold text-slate-400">Total Referrals</div>
      <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$stats['total'] ?></div>
    </div>
    <div class="smm-card p-4">
      <div class="text-[11px] uppercase font-bold text-slate-400">Qualified</div>
      <div class="text-2xl font-extrabold text-emerald-600 mt-1"><?= (int)$stats['qualified'] ?></div>
    </div>
    <div class="smm-card p-4">
      <div class="text-[11px] uppercase font-bold text-slate-400">Pending</div>
      <div class="text-2xl font-extrabold text-amber-600 mt-1"><?= (int)$stats['pending'] ?></div>
    </div>
    <div class="smm-card p-4">
      <div class="text-[11px] uppercase font-bold text-slate-400">Total Earned</div>
      <div class="text-2xl font-extrabold text-violet-700 mt-1"><?= e(format_money($stats['earned'], 2)) ?></div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-5">
      <div class="smm-card p-6">
        <h2 class="font-bold text-slate-900 mb-1">Your Referral Link</h2>
        <p class="text-xs text-slate-500 mb-4">Share this link with friends. Your code is applied automatically at registration.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
            <div class="text-[10px] uppercase font-bold text-slate-400">Your Code</div>
            <div class="font-mono font-bold text-violet-700 text-lg mt-0.5"><?= e($code) ?></div>
          </div>
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 min-w-0">
            <div class="text-[10px] uppercase font-bold text-slate-400">Referral Link</div>
            <div class="text-xs text-slate-700 truncate mt-1"><?= e($referralLink) ?></div>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <button type="button" onclick="SMM.copyText(<?= e(json_encode($referralLink)) ?>, this)" class="btn-primary px-4 py-2 text-sm">Copy Referral Link</button>
          <button type="button" onclick="SMM.copyText(<?= e(json_encode($code)) ?>, this)" class="btn-secondary px-4 py-2 text-sm">Copy Code</button>
        </div>
      </div>

      <div class="smm-card overflow-hidden">
        <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-500">Your Referrals</div>
        <div class="smm-table-wrap">
          <table class="smm-table">
            <thead><tr><th>User</th><th>Status</th><th>Reward</th><th>Joined</th></tr></thead>
            <tbody>
              <?php if (empty($referred)): ?>
                <tr><td colspan="4" class="text-center text-slate-500 py-8">No referrals yet. Share your link to get started.</td></tr>
              <?php else: foreach ($referred as $ref): ?>
                <?php
                  $statusClass = match ((string)$ref['status']) {
                      'qualified' => 'badge-success',
                      'rejected' => 'badge-danger',
                      default => 'badge-warning',
                  };
                  $masked = (string)$ref['username'];
                ?>
                <tr>
                  <td class="text-sm font-semibold text-slate-800"><?= e($masked) ?></td>
                  <td><span class="badge <?= e($statusClass) ?>"><?= e($ref['status']) ?></span></td>
                  <td class="text-sm font-semibold"><?= e(format_money($ref['reward_amount'], 2)) ?></td>
                  <td class="text-xs text-slate-500"><?= e(format_datetime((string)$ref['joined_at'])) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="smm-card p-6 h-fit space-y-3">
      <h2 class="font-bold text-slate-900">How It Works</h2>
      <ol class="text-sm text-slate-600 space-y-2 list-decimal list-inside">
        <li>Share your referral link or code.</li>
        <li>Your friend creates an account with it.</li>
        <li>You earn <?= e($rewardLabel) ?> <?= e($eventLabel) ?>.</li>
        <li>Rewards are credited to your wallet automatically.</li>
      </ol>
      <?php $terms = trim((string)get_setting('referral_terms', '')); ?>
      <?php if ($terms !== ''): ?>
        <div class="pt-3 border-t border-slate-100 text-xs text-slate-500 whitespace-pre-line"><?= e($terms) ?></div>
      <?php endif; ?>
      <a href="<?= e(app_url('/user/transactions.php')) ?>" class="btn-secondary w-full py-2 text-sm text-center block">View Wallet Ledger</a>
    </div>
  </div>
<?php endif; ?>
