<?php
declare(strict_types=1);

/**
 * Refer & Earn: referral codes, signup attribution and wallet reward payouts.
 * Every reward credit is idempotent via Wallet::credit().
 */
class Referral
{
    public static function isEnabled(): bool
    {
        return get_setting('referral_enabled', '1') === '1';
    }

    public static function rewardType(): string
    {
        return get_setting('referral_reward_type', 'fixed') === 'percent' ? 'percent' : 'fixed';
    }

    public static function qualifyingEvent(): string
    {
        $event = (string)get_setting('referral_qualifying_event', 'signup');
        return in_array($event, ['signup', 'first_order', 'first_deposit', 'manual'], true) ? $event : 'signup';
    }

    public static function generateCode(int $userId): string
    {
        $user = Database::fetch('SELECT username FROM users WHERE id = ? LIMIT 1', [$userId]);
        $stem = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($user['username'] ?? 'USER')) ?: 'USER');
        $stem = substr($stem, 0, 8);

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = $stem . strtoupper(bin2hex(random_bytes(3)));
            $exists = Database::fetch('SELECT id FROM users WHERE referral_code = ? LIMIT 1', [$code]);
            if (!$exists) {
                return $code;
            }
        }
        return 'REF' . strtoupper(bin2hex(random_bytes(6)));
    }

    public static function ensureCode(int $userId): string
    {
        $code = (string)Database::fetchColumn('SELECT referral_code FROM users WHERE id = ? LIMIT 1', [$userId]);
        if ($code !== '') {
            return $code;
        }
        $code = self::generateCode($userId);
        Database::execute('UPDATE users SET referral_code = ?, updated_at = NOW() WHERE id = ?', [$code, $userId]);
        return $code;
    }

    public static function findByCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }
        return Database::fetch(
            'SELECT id, username, email, status, referral_code FROM users WHERE referral_code = ? LIMIT 1',
            [$code]
        );
    }

    /**
     * Attach a new registration to a referrer. Signup-time rewards are processed
     * immediately when the configured qualifying event is "signup".
     */
    public static function attribute(int $referredUserId, string $code): array
    {
        $code = strtoupper(Validator::cleanString($code, 30));
        if ($code === '' || !self::isEnabled()) {
            return ['success' => false, 'error' => 'Referral is not available.'];
        }
        $referrer = self::findByCode($code);
        if (!$referrer) {
            return ['success' => false, 'error' => 'Invalid referral code.'];
        }
        if ((int)$referrer['id'] === $referredUserId) {
            return ['success' => false, 'error' => 'You cannot refer yourself.'];
        }
        if ((string)$referrer['status'] !== 'active') {
            return ['success' => false, 'error' => 'This referral code is no longer active.'];
        }

        $existing = Database::fetch('SELECT id FROM referrals WHERE referred_id = ? LIMIT 1', [$referredUserId]);
        if ($existing) {
            return ['success' => false, 'error' => 'This account is already linked to a referrer.'];
        }

        try {
            $referralId = Database::insert(
                'INSERT INTO referrals (referrer_id, referred_id, code, status, qualifying_event, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
                [(int)$referrer['id'], $referredUserId, $code, 'pending', self::qualifyingEvent()]
            );
            Database::execute('UPDATE users SET referred_by = ?, updated_at = NOW() WHERE id = ?', [(int)$referrer['id'], $referredUserId]);
        } catch (Throwable $e) {
            Logger::error('Referral::attribute', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['success' => false, 'error' => 'Unable to link referral.'];
        }

        if (self::qualifyingEvent() === 'signup') {
            self::qualify($referralId, 'signup', '0');
        }

        return ['success' => true, 'referral_id' => $referralId, 'referrer_id' => (int)$referrer['id']];
    }

    /**
     * Called after a qualifying business event. No-op unless the event matches
     * the configured qualifying event.
     */
    public static function processEvent(int $userId, string $event, string $baseAmount = '0'): void
    {
        if (!self::isEnabled() || self::qualifyingEvent() !== $event) {
            return;
        }
        $referral = Database::fetch(
            'SELECT * FROM referrals WHERE referred_id = ? AND status = ? ORDER BY id ASC LIMIT 1',
            [$userId, 'pending']
        );
        if (!$referral) {
            return;
        }
        self::qualify((int)$referral['id'], $event, $baseAmount);
    }

    public static function qualify(int $referralId, string $event, string $baseAmount = '0', ?int $adminId = null): array
    {
        $referral = Database::fetch('SELECT * FROM referrals WHERE id = ? LIMIT 1', [$referralId]);
        if (!$referral) {
            return ['success' => false, 'error' => 'Referral not found.'];
        }
        if ((string)$referral['status'] === 'rejected') {
            return ['success' => false, 'error' => 'This referral was rejected.'];
        }

        $minDeposit = money_format_dec((string)get_setting('referral_min_deposit', '0'), 4);
        if (in_array($event, ['first_deposit', 'first_order'], true) && money_cmp($baseAmount, $minDeposit, 4) < 0) {
            return ['success' => false, 'error' => 'Referral qualifying amount has not been reached yet.'];
        }

        $reward = self::calculateReward($baseAmount);
        $adminId = $adminId !== null ? $adminId : null;

        Database::execute(
            'UPDATE referrals SET status = ?, qualifying_event = ?, reward_amount = ?, admin_id = COALESCE(?, admin_id), approved_at = COALESCE(approved_at, NOW()), updated_at = NOW() WHERE id = ?',
            ['qualified', $event, $reward, $adminId, $referralId]
        );
        $referral['status'] = 'qualified';
        $referral['reward_amount'] = $reward;

        if (get_setting('referral_auto_approve', '1') === '1' || $adminId !== null) {
            self::creditReward($referral, $adminId);
        }

        return ['success' => true, 'reward' => $reward];
    }

    public static function calculateReward(string $baseAmount): string
    {
        if (self::rewardType() === 'percent') {
            $percent = money_format_dec((string)get_setting('referral_reward_percent', '0'), 4);
            if (money_cmp($percent, '0', 4) <= 0 || money_cmp($baseAmount, '0', 4) <= 0) {
                return '0.0000';
            }
            return money_div(money_mul($baseAmount, $percent, 8), '100', 4);
        }
        $amount = money_format_dec((string)get_setting('referral_reward_amount', '0'), 4);
        return money_cmp($amount, '0', 4) > 0 ? $amount : '0.0000';
    }

    public static function approve(int $referralId, int $adminId, ?string $amount = null): array
    {
        $referral = Database::fetch('SELECT * FROM referrals WHERE id = ? LIMIT 1', [$referralId]);
        if (!$referral) {
            return ['success' => false, 'error' => 'Referral not found.'];
        }
        if ((string)$referral['status'] === 'rejected') {
            return ['success' => false, 'error' => 'Rejected referrals cannot be approved.'];
        }

        $reward = $amount !== null ? Validator::parseDecimal($amount, 4) : null;
        if ($reward !== null && money_cmp($reward, '0', 4) >= 0) {
            $referral['reward_amount'] = $reward;
        } elseif (money_cmp((string)$referral['reward_amount'], '0', 4) <= 0) {
            $referral['reward_amount'] = self::calculateReward('0');
        }

        Database::execute(
            'UPDATE referrals SET status = ?, reward_amount = ?, admin_id = ?, approved_at = NOW(), updated_at = NOW() WHERE id = ?',
            ['qualified', (string)$referral['reward_amount'], $adminId, $referralId]
        );
        $referral['status'] = 'qualified';
        $result = self::creditReward($referral, $adminId);
        Logger::adminActivity($adminId, 'referral.approve', 'referral', $referralId, 'Approved referral reward ' . (string)$referral['reward_amount']);
        return $result;
    }

    public static function reject(int $referralId, int $adminId, string $note = ''): array
    {
        $referral = Database::fetch('SELECT * FROM referrals WHERE id = ? LIMIT 1', [$referralId]);
        if (!$referral) {
            return ['success' => false, 'error' => 'Referral not found.'];
        }
        $credited = Database::fetch('SELECT id FROM referral_rewards WHERE referral_id = ? AND status = ? LIMIT 1', [$referralId, 'credited']);
        if ($credited) {
            return ['success' => false, 'error' => 'Reward has already been credited and cannot be rejected.'];
        }
        Database::execute(
            'UPDATE referrals SET status = ?, admin_id = ?, note = ?, updated_at = NOW() WHERE id = ?',
            ['rejected', $adminId, mb_substr(Validator::cleanString($note, 255), 0, 255), $referralId]
        );
        Logger::adminActivity($adminId, 'referral.reject', 'referral', $referralId, 'Rejected referral. ' . $note);
        return ['success' => true];
    }

    public static function creditReward(array $referral, ?int $adminId = null): array
    {
        $referralId = (int)$referral['id'];
        $referrerId = (int)$referral['referrer_id'];
        $reward = money_format_dec((string)($referral['reward_amount'] ?? '0'), 4);

        if (money_cmp($reward, '0', 4) > 0) {
            $res = Wallet::credit(
                $referrerId,
                $reward,
                'referral_reward',
                (string)$referralId,
                'Referral bonus for inviting a new customer',
                'referral_' . $referralId . '_referrer',
                $adminId,
                false
            );
            if (!$res['success']) {
                return $res;
            }
            self::recordReward($referralId, $referrerId, 'referrer_bonus', $reward, (int)($res['transaction_id'] ?? 0), $adminId);
        }

        $refereeBonus = money_format_dec((string)get_setting('referral_reward_referee', '0'), 4);
        if (money_cmp($refereeBonus, '0', 4) > 0) {
            $refereeId = (int)$referral['referred_id'];
            $res = Wallet::credit(
                $refereeId,
                $refereeBonus,
                'referral_referee_reward',
                (string)$referralId,
                'Welcome referral bonus',
                'referral_' . $referralId . '_referee',
                $adminId,
                false
            );
            if ($res['success']) {
                self::recordReward($referralId, $refereeId, 'referee_bonus', $refereeBonus, (int)($res['transaction_id'] ?? 0), $adminId);
            }
        }

        return ['success' => true];
    }

    private static function recordReward(int $referralId, int $userId, string $type, string $amount, int $walletTxId, ?int $adminId): void
    {
        try {
            Database::execute(
                'INSERT INTO referral_rewards (referral_id, user_id, reward_type, amount, status, idempotency_key, wallet_transaction_id, admin_id, note, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE wallet_transaction_id = VALUES(wallet_transaction_id)',
                [
                    $referralId,
                    $userId,
                    $type,
                    $amount,
                    'credited',
                    'referral_reward_' . $referralId . '_' . $type,
                    $walletTxId > 0 ? $walletTxId : null,
                    $adminId,
                    'Referral reward credited to wallet',
                ]
            );
        } catch (Throwable $e) {
            Logger::error('Referral::recordReward', $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public static function stats(int $userId): array
    {
        $total = (int)Database::fetchColumn('SELECT COUNT(*) FROM referrals WHERE referrer_id = ?', [$userId]);
        $qualified = (int)Database::fetchColumn('SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND status = ?', [$userId, 'qualified']);
        $pending = (int)Database::fetchColumn('SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND status = ?', [$userId, 'pending']);
        $earned = (string)Database::fetchColumn(
            'SELECT COALESCE(SUM(amount), 0) FROM referral_rewards WHERE user_id = ? AND status = ?',
            [$userId, 'credited']
        );
        return [
            'total' => $total,
            'qualified' => $qualified,
            'pending' => $pending,
            'earned' => money_format_dec($earned !== '' ? $earned : '0', 4),
        ];
    }

    public static function referredUsers(int $userId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return Database::fetchAll(
            "SELECT r.*, u.username, u.email, u.created_at AS joined_at
             FROM referrals r
             INNER JOIN users u ON u.id = r.referred_id
             WHERE r.referrer_id = ?
             ORDER BY r.id DESC LIMIT {$limit}",
            [$userId]
        );
    }

    public static function link(string $code): string
    {
        return app_url('/register.php?ref=' . rawurlencode($code));
    }
}
