<?php
declare(strict_types=1);
/** @var array $referrals @var array $stats @var string $status @var string $q @var array $pg @var bool $canManage */
$rewardType = (string)get_setting('referral_reward_type', 'fixed');
$event = (string)get_setting('referral_qualifying_event', 'signup');
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Refer &amp; Earn</h1>
  <p class="text-sm text-slate-500">Manage referral rewards, qualifying events and payout approval.</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <?php foreach ([
      ['Total Referrals', (string)$stats['total'], 'badge-info'],
      ['Pending', (string)$stats['pending'], 'badge-warning'],
      ['Qualified', (string)$stats['qualified'], 'badge-success'],
      ['Rewards Credited', format_money($stats['credited'], 2), 'badge-purple'],
  ] as $card): ?>
    <div class="smm-card p-4">
      <div class="text-[11px] uppercase font-bold text-slate-400"><?= e($card[0]) ?></div>
      <div class="text-xl font-extrabold text-slate-900 mt-1"><?= e($card[1]) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-1">Program Settings</h2>
    <p class="text-xs text-slate-500 mb-4">Rewards are paid in <?= e(Currency::baseCode()) ?> and credited to the wallet ledger.</p>
    <form method="POST" class="space-y-3.5">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="save_settings">
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="referral_enabled" value="1" <?= get_setting('referral_enabled', '1') === '1' ? 'checked' : '' ?>> Enable Refer &amp; Earn
      </label>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="smm-label">Reward Type</label>
          <select name="referral_reward_type" class="smm-input">
            <option value="fixed" <?= $rewardType === 'fixed' ? 'selected' : '' ?>>Fixed amount</option>
            <option value="percent" <?= $rewardType === 'percent' ? 'selected' : '' ?>>Percent of event value</option>
          </select>
        </div>
        <div>
          <label class="smm-label">Fixed Reward</label>
          <input type="number" step="0.0001" min="0" name="referral_reward_amount" value="<?= e(money_format_dec(get_setting('referral_reward_amount', '0'), 4)) ?>" class="smm-input">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="smm-label">Percent Reward (%)</label><input type="number" step="0.01" min="0" name="referral_reward_percent" value="<?= e(money_format_dec(get_setting('referral_reward_percent', '0'), 4)) ?>" class="smm-input"></div>
        <div><label class="smm-label">Referee Bonus</label><input type="number" step="0.0001" min="0" name="referral_reward_referee" value="<?= e(money_format_dec(get_setting('referral_reward_referee', '0'), 4)) ?>" class="smm-input"></div>
      </div>
      <div>
        <label class="smm-label">Qualifying Event</label>
        <select name="referral_qualifying_event" class="smm-input">
          <option value="signup" <?= $event === 'signup' ? 'selected' : '' ?>>On signup</option>
          <option value="first_order" <?= $event === 'first_order' ? 'selected' : '' ?>>After first order</option>
          <option value="first_deposit" <?= $event === 'first_deposit' ? 'selected' : '' ?>>After first deposit</option>
          <option value="manual" <?= $event === 'manual' ? 'selected' : '' ?>>Manual approval only</option>
        </select>
      </div>
      <div>
        <label class="smm-label">Minimum Qualifying Amount</label>
        <input type="number" step="0.0001" min="0" name="referral_min_deposit" value="<?= e(money_format_dec(get_setting('referral_min_deposit', '0'), 4)) ?>" class="smm-input">
      </div>
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="referral_auto_approve" value="1" <?= get_setting('referral_auto_approve', '1') === '1' ? 'checked' : '' ?>> Auto-credit rewards on qualification
      </label>
      <div>
        <label class="smm-label">Program Terms (shown to users)</label>
        <textarea name="referral_terms" rows="3" class="smm-input"><?= e(get_setting('referral_terms', '')) ?></textarea>
      </div>
      <?php if ($canManage): ?>
        <button type="submit" class="btn-primary w-full py-2.5 text-sm">Save Referral Settings</button>
      <?php else: ?>
        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-500">You have view-only access to referral settings.</div>
      <?php endif; ?>
    </form>
  </div>

  <div class="xl:col-span-2 space-y-4">
    <form method="GET" class="smm-card p-4 flex flex-col sm:flex-row gap-3">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search user, email or code..." class="smm-input flex-1">
      <select name="status" class="smm-input sm:w-44">
        <option value="">All Statuses</option>
        <?php foreach (['pending' => 'Pending', 'qualified' => 'Qualified', 'rejected' => 'Rejected'] as $sv => $sl): ?>
          <option value="<?= e($sv) ?>" <?= $status === $sv ? 'selected' : '' ?>><?= e($sl) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary px-5 py-2 text-sm">Filter</button>
    </form>

    <div class="smm-card overflow-hidden">
      <div class="smm-table-wrap">
        <table class="smm-table">
          <thead><tr><th>ID</th><th>Referrer</th><th>Referred User</th><th>Code</th><th>Status</th><th>Reward</th><th>Joined</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            <?php if (empty($referrals)): ?>
              <tr><td colspan="8" class="text-center text-slate-500 py-8">No referrals found.</td></tr>
            <?php else: foreach ($referrals as $ref): ?>
              <?php
                $statusClass = match ((string)$ref['status']) {
                    'qualified' => 'badge-success',
                    'rejected' => 'badge-danger',
                    default => 'badge-warning',
                };
              ?>
              <tr>
                <td class="font-mono text-xs">#<?= (int)$ref['id'] ?></td>
                <td class="text-xs">
                  <div class="font-semibold text-slate-900"><?= e($ref['referrer_username']) ?></div>
                  <div class="text-[11px] text-slate-400"><?= e($ref['referrer_email']) ?></div>
                </td>
                <td class="text-xs">
                  <div class="font-semibold text-slate-900"><?= e($ref['referred_username']) ?></div>
                  <div class="text-[11px] text-slate-400"><?= e($ref['referred_email']) ?></div>
                </td>
                <td class="font-mono text-[11px]"><?= e($ref['code']) ?></td>
                <td><span class="badge <?= e($statusClass) ?>"><?= e($ref['status']) ?></span></td>
                <td class="text-xs font-semibold"><?= e(format_money($ref['reward_amount'], 2)) ?>
                  <?php if ((int)$ref['referrer_credited'] > 0): ?><span class="badge badge-success ml-1">credited</span><?php endif; ?>
                </td>
                <td class="text-[11px] text-slate-500"><?= e(format_datetime((string)$ref['referred_joined'])) ?></td>
                <td class="text-right whitespace-nowrap space-x-1">
                  <?php if ($canManage && (string)$ref['status'] !== 'rejected'): ?>
                    <button type="button"
                            onclick='SMM.confirmAction(<?= e(json_encode([
                                'title' => 'Approve Referral',
                                'message' => 'Credit the referral reward for ' . $ref['referrer_username'] . '?',
                                'actionUrl' => app_url('/admin/referrals.php'),
                                'fields' => ['action' => 'approve', 'id' => (int)$ref['id']],
                                'confirmLabel' => 'Approve & Credit',
                            ])) ?>)'
                            class="btn-secondary px-2.5 py-1 text-xs text-emerald-700">Approve</button>
                    <button type="button"
                            onclick='SMM.confirmAction(<?= e(json_encode([
                                'title' => 'Reject Referral',
                                'message' => 'Reject this referral? Credited rewards cannot be rejected.',
                                'actionUrl' => app_url('/admin/referrals.php'),
                                'fields' => ['action' => 'reject', 'id' => (int)$ref['id'], 'note' => 'Rejected by administrator'],
                                'confirmLabel' => 'Reject',
                            ])) ?>)'
                            class="btn-secondary px-2.5 py-1 text-xs text-red-700">Reject</button>
                  <?php else: ?>
                    <span class="text-[11px] text-slate-400">&mdash;</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <?php Theme::component('shared/pagination', ['pagination' => $pg, 'baseQuery' => ['q' => $q, 'status' => $status], 'itemLabel' => 'referrals']); ?>
    </div>
  </div>
</div>
