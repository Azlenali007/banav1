<?php
declare(strict_types=1);

class Wallet
{
    public static function ensureWallet(int $userId): array
    {
        $wallet = Database::fetch('SELECT * FROM wallets WHERE user_id = ? LIMIT 1', [$userId]);
        if ($wallet) {
            return $wallet;
        }
        $currency = get_setting('currency_code', DEFAULT_CURRENCY);
        Database::insert(
            'INSERT INTO wallets (user_id, balance, total_deposited, total_spent, currency, created_at, updated_at) VALUES (?, 0.0000, 0.0000, 0.0000, ?, NOW(), NOW())',
            [$userId, $currency]
        );
        return Database::fetch('SELECT * FROM wallets WHERE user_id = ? LIMIT 1', [$userId]);
    }

    public static function getBalance(int $userId): string
    {
        $wallet = self::ensureWallet($userId);
        return money_format_dec((string)$wallet['balance'], 4);
    }

    /**
     * Atomically credit a user's wallet inside a row-locked DB transaction.
     */
    public static function credit(
        int $userId,
        string $amount,
        string $referenceType,
        string $referenceId,
        string $description,
        string $idempotencyKey,
        ?int $adminId = null,
        bool $countAsDeposit = true
    ): array {
        if (money_cmp($amount, '0.0000') <= 0) {
            return ['success' => false, 'error' => 'Credit amount must be positive.'];
        }

        $pdo = Database::getInstance();
        $startedTx = !$pdo->inTransaction();
        if ($startedTx) {
            $pdo->beginTransaction();
        }

        try {
            // Idempotency guard
            $existing = Database::fetch('SELECT id FROM wallet_transactions WHERE idempotency_key = ? LIMIT 1', [$idempotencyKey]);
            if ($existing) {
                if ($startedTx) {
                    $pdo->commit();
                }
                return ['success' => true, 'duplicate' => true, 'transaction_id' => (int)$existing['id']];
            }

            self::ensureWallet($userId);
            $wallet = Database::fetch('SELECT * FROM wallets WHERE user_id = ? FOR UPDATE', [$userId]);
            $before = money_format_dec((string)$wallet['balance'], 4);
            $after = money_add($before, $amount, 4);

            $depositAdd = $countAsDeposit ? $amount : '0.0000';
            $newDeposited = money_add((string)$wallet['total_deposited'], $depositAdd, 4);

            Database::execute(
                'UPDATE wallets SET balance = ?, total_deposited = ?, updated_at = NOW() WHERE id = ?',
                [$after, $newDeposited, $wallet['id']]
            );

            $txId = Database::insert(
                'INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, balance_before, balance_after, reference_type, reference_id, idempotency_key, description, admin_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [$userId, $wallet['id'], $adminId !== null ? 'adjustment' : 'credit', money_format_dec($amount, 4), $before, $after, $referenceType, $referenceId, $idempotencyKey, mb_substr($description, 0, 255), $adminId]
            );

            if ($startedTx) {
                $pdo->commit();
            }

            return ['success' => true, 'duplicate' => false, 'transaction_id' => $txId, 'balance_after' => $after];
        } catch (Throwable $e) {
            if ($startedTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Wallet::credit', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['success' => false, 'error' => 'Failed to credit wallet safely.'];
        }
    }

    /**
     * Atomically debit a user's wallet inside a row-locked DB transaction.
     */
    public static function debit(
        int $userId,
        string $amount,
        string $referenceType,
        string $referenceId,
        string $description,
        string $idempotencyKey,
        ?int $adminId = null,
        bool $countAsSpent = true
    ): array {
        if (money_cmp($amount, '0.0000') <= 0) {
            return ['success' => false, 'error' => 'Debit amount must be positive.'];
        }

        $pdo = Database::getInstance();
        $startedTx = !$pdo->inTransaction();
        if ($startedTx) {
            $pdo->beginTransaction();
        }

        try {
            $existing = Database::fetch('SELECT id FROM wallet_transactions WHERE idempotency_key = ? LIMIT 1', [$idempotencyKey]);
            if ($existing) {
                if ($startedTx) {
                    $pdo->commit();
                }
                return ['success' => true, 'duplicate' => true, 'transaction_id' => (int)$existing['id']];
            }

            self::ensureWallet($userId);
            $wallet = Database::fetch('SELECT * FROM wallets WHERE user_id = ? FOR UPDATE', [$userId]);
            $before = money_format_dec((string)$wallet['balance'], 4);

            if (money_cmp($before, $amount) < 0) {
                if ($startedTx) {
                    $pdo->rollBack();
                }
                return ['success' => false, 'error' => 'Insufficient wallet balance.'];
            }

            $after = money_sub($before, $amount, 4);
            $spentAdd = $countAsSpent ? $amount : '0.0000';
            $newSpent = money_add((string)$wallet['total_spent'], $spentAdd, 4);

            Database::execute(
                'UPDATE wallets SET balance = ?, total_spent = ?, updated_at = NOW() WHERE id = ?',
                [$after, $newSpent, $wallet['id']]
            );

            $txId = Database::insert(
                'INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, balance_before, balance_after, reference_type, reference_id, idempotency_key, description, admin_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [$userId, $wallet['id'], $adminId !== null ? 'adjustment' : 'debit', money_format_dec($amount, 4), $before, $after, $referenceType, $referenceId, $idempotencyKey, mb_substr($description, 0, 255), $adminId]
            );

            if ($startedTx) {
                $pdo->commit();
            }

            return ['success' => true, 'duplicate' => false, 'transaction_id' => $txId, 'balance_after' => $after];
        } catch (Throwable $e) {
            if ($startedTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Wallet::debit', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['success' => false, 'error' => 'Failed to debit wallet safely.'];
        }
    }

    /**
     * Atomically refund a user's wallet with idempotency protection.
     */
    public static function refund(
        int $userId,
        string $amount,
        string $referenceType,
        string $referenceId,
        string $description,
        string $idempotencyKey,
        ?int $adminId = null
    ): array {
        if (money_cmp($amount, '0.0000') <= 0) {
            return ['success' => false, 'error' => 'Refund amount must be positive.'];
        }

        $pdo = Database::getInstance();
        $startedTx = !$pdo->inTransaction();
        if ($startedTx) {
            $pdo->beginTransaction();
        }

        try {
            $existing = Database::fetch('SELECT id FROM wallet_transactions WHERE idempotency_key = ? LIMIT 1', [$idempotencyKey]);
            if ($existing) {
                if ($startedTx) {
                    $pdo->commit();
                }
                return ['success' => true, 'duplicate' => true, 'transaction_id' => (int)$existing['id']];
            }

            self::ensureWallet($userId);
            $wallet = Database::fetch('SELECT * FROM wallets WHERE user_id = ? FOR UPDATE', [$userId]);
            $before = money_format_dec((string)$wallet['balance'], 4);
            $after = money_add($before, $amount, 4);

            // Reduce total_spent safely if applicable (clamped at 0)
            $newSpent = money_sub((string)$wallet['total_spent'], $amount, 4);
            if (money_cmp($newSpent, '0.0000') < 0) {
                $newSpent = '0.0000';
            }

            Database::execute(
                'UPDATE wallets SET balance = ?, total_spent = ?, updated_at = NOW() WHERE id = ?',
                [$after, $newSpent, $wallet['id']]
            );

            $txId = Database::insert(
                'INSERT INTO wallet_transactions (user_id, wallet_id, type, amount, balance_before, balance_after, reference_type, reference_id, idempotency_key, description, admin_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [$userId, $wallet['id'], 'refund', money_format_dec($amount, 4), $before, $after, $referenceType, $referenceId, $idempotencyKey, mb_substr($description, 0, 255), $adminId]
            );

            if ($startedTx) {
                $pdo->commit();
            }

            return ['success' => true, 'duplicate' => false, 'transaction_id' => $txId, 'balance_after' => $after];
        } catch (Throwable $e) {
            if ($startedTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Wallet::refund', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['success' => false, 'error' => 'Failed to process refund safely.'];
        }
    }
}
