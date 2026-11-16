<?php
declare(strict_types=1);

class Payment
{
    public static function getMethod(string $code): ?array
    {
        $row = Database::fetch('SELECT * FROM payment_methods WHERE code = ? LIMIT 1', [$code]);
        if (!$row) {
            return null;
        }
        $config = [];
        if (!empty($row['config_json_encrypted'])) {
            $decrypted = crypto_decrypt((string)$row['config_json_encrypted']);
            $decoded = json_decode($decrypted, true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }
        $row['config'] = $config;
        return $row;
    }

    public static function saveMethodConfig(string $code, bool $enabled, string $minAmount, string $maxAmount, string $instructions, array $secretConfig = []): bool
    {
        $existing = self::getMethod($code);
        if (!$existing) {
            return false;
        }
        $mergedConfig = array_merge($existing['config'], $secretConfig);
        $encrypted = crypto_encrypt(json_encode($mergedConfig, JSON_UNESCAPED_SLASHES));

        Database::execute(
            'UPDATE payment_methods SET is_enabled = ?, min_amount = ?, max_amount = ?, instructions = ?, config_json_encrypted = ?, updated_at = NOW() WHERE code = ?',
            [$enabled ? 1 : 0, $minAmount, $maxAmount, $instructions, $encrypted, $code]
        );
        return true;
    }

    private static function razorpayCurl(string $method, string $endpoint, string $keyId, string $keySecret, ?array $payload = null): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'error' => 'PHP cURL extension is required for Razorpay.'];
        }
        $cfg = require CONFIG_PATH . '/payment.php';
        $url = rtrim($cfg['razorpay']['api_base'], '/') . '/' . ltrim($endpoint, '/');

        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $keyId . ':' . $keySecret,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ];
        if (strtoupper($method) === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($payload ?? []);
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            Logger::file('payment', 'ERROR', 'Razorpay cURL error', ['endpoint' => $endpoint, 'error' => $err]);
            return ['success' => false, 'error' => 'Gateway connection error: ' . $err];
        }

        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded) || $httpCode < 200 || $httpCode >= 300) {
            $errMsg = $decoded['error']['description'] ?? "Razorpay HTTP {$httpCode}";
            Logger::file('payment', 'ERROR', 'Razorpay API failure', ['endpoint' => $endpoint, 'http' => $httpCode, 'response' => $raw]);
            return ['success' => false, 'error' => $errMsg];
        }

        return ['success' => true, 'data' => $decoded];
    }

    public static function createRazorpayOrder(int $userId, string $amount): array
    {
        $method = self::getMethod('razorpay');
        if (!$method || empty($method['is_enabled'])) {
            return ['success' => false, 'error' => 'Razorpay gateway is currently disabled.'];
        }

        $keyId = trim((string)($method['config']['key_id'] ?? ''));
        $keySecret = trim((string)($method['config']['key_secret'] ?? ''));
        if ($keyId === '' || $keySecret === '') {
            return ['success' => false, 'error' => 'Razorpay API credentials are not configured by the administrator yet.'];
        }

        $decAmount = Validator::parseDecimal($amount, 2);
        if ($decAmount === null || money_cmp($decAmount, (string)$method['min_amount']) < 0 || money_cmp($decAmount, (string)$method['max_amount']) > 0) {
            return ['success' => false, 'error' => "Deposit amount must be between {$method['min_amount']} and {$method['max_amount']}."];
        }

        $currency = strtoupper(get_setting('currency_code', DEFAULT_CURRENCY));
        $subunits = (int)round((float)money_mul($decAmount, '100', 0));
        if ($subunits < 100) {
            return ['success' => false, 'error' => 'Amount too small for Razorpay processing.'];
        }

        $paymentUid = 'PAY-' . strtoupper(bin2hex(random_bytes(5)));
        $res = self::razorpayCurl('POST', '/orders', $keyId, $keySecret, [
            'amount' => $subunits,
            'currency' => $currency,
            'receipt' => $paymentUid,
            'payment_capture' => 1,
        ]);

        if (!$res['success'] || empty($res['data']['id'])) {
            return ['success' => false, 'error' => $res['error'] ?? 'Could not initialize Razorpay order.'];
        }

        $rzpOrderId = (string)$res['data']['id'];
        $paymentId = Database::insert(
            'INSERT INTO payments (payment_uid, user_id, method_code, amount, currency, gateway_order_id, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [$paymentUid, $userId, 'razorpay', money_format_dec($decAmount, 4), $currency, $rzpOrderId, 'pending']
        );

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'payment_uid' => $paymentUid,
            'razorpay_order_id' => $rzpOrderId,
            'key_id' => $keyId,
            'amount_subunits' => $subunits,
            'currency' => $currency,
        ];
    }

    public static function verifyRazorpayPayment(string $rzpOrderId, string $rzpPaymentId, string $rzpSignature, ?int $expectedUserId = null): array
    {
        $method = self::getMethod('razorpay');
        if (!$method) {
            return ['success' => false, 'error' => 'Razorpay configuration missing.'];
        }
        $keyId = trim((string)($method['config']['key_id'] ?? ''));
        $keySecret = trim((string)($method['config']['key_secret'] ?? ''));
        if ($keyId === '' || $keySecret === '') {
            return ['success' => false, 'error' => 'Razorpay secret is not configured.'];
        }

        // 1. Verify cryptographic HMAC-SHA256 signature
        $expectedSignature = hash_hmac('sha256', $rzpOrderId . '|' . $rzpPaymentId, $keySecret);
        if (!hash_equals($expectedSignature, $rzpSignature)) {
            Logger::file('payment', 'WARN', 'Razorpay signature mismatch', ['order_id' => $rzpOrderId, 'payment_id' => $rzpPaymentId]);
            return ['success' => false, 'error' => 'Payment signature verification failed.'];
        }

        // 2. Fetch live payment object from Razorpay API to verify captured status, amount, and currency
        $remote = self::razorpayCurl('GET', '/payments/' . urlencode($rzpPaymentId), $keyId, $keySecret);
        if (!$remote['success']) {
            return ['success' => false, 'error' => 'Could not confirm payment with Razorpay server: ' . ($remote['error'] ?? '')];
        }
        $pData = $remote['data'];
        if (($pData['order_id'] ?? '') !== $rzpOrderId) {
            return ['success' => false, 'error' => 'Payment order mismatch.'];
        }

        if (($pData['status'] ?? '') === 'authorized') {
            $cap = self::razorpayCurl('POST', '/payments/' . urlencode($rzpPaymentId) . '/capture', $keyId, $keySecret, [
                'amount' => (int)$pData['amount'],
                'currency' => (string)$pData['currency'],
            ]);
            if (!$cap['success']) {
                return ['success' => false, 'error' => 'Unable to capture authorized payment.'];
            }
            $pData = $cap['data'];
        }

        if (($pData['status'] ?? '') !== 'captured') {
            return ['success' => false, 'error' => 'Payment is not captured (status: ' . ($pData['status'] ?? 'unknown') . ').'];
        }

        // 3. Atomic row-locked wallet credit
        $pdo = Database::getInstance();
        $pdo->beginTransaction();
        try {
            $payment = Database::fetch('SELECT * FROM payments WHERE gateway_order_id = ? FOR UPDATE', [$rzpOrderId]);
            if (!$payment) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Payment order record not found.'];
            }
            if ($expectedUserId !== null && (int)$payment['user_id'] !== $expectedUserId) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Payment does not belong to current account.'];
            }
            if ($payment['status'] === 'completed' && $payment['credited_at'] !== null) {
                $pdo->commit();
                return ['success' => true, 'duplicate' => true, 'payment_uid' => $payment['payment_uid']];
            }

            $expectedSubunits = (int)round((float)money_mul((string)$payment['amount'], '100', 0));
            if ((int)$pData['amount'] !== $expectedSubunits || strtoupper((string)$pData['currency']) !== strtoupper((string)$payment['currency'])) {
                Database::execute('UPDATE payments SET status = ?, admin_note = ?, updated_at = NOW() WHERE id = ?', ['failed', 'Amount or currency mismatch on gateway verification.', $payment['id']]);
                $pdo->commit();
                return ['success' => false, 'error' => 'Verified payment amount or currency does not match order.'];
            }

            $credit = Wallet::credit(
                (int)$payment['user_id'],
                (string)$payment['amount'],
                'razorpay_deposit',
                (string)$payment['id'],
                "Razorpay deposit {$payment['payment_uid']} ({$rzpPaymentId})",
                'razorpay_' . $rzpPaymentId
            );
            if (!$credit['success']) {
                $pdo->rollBack();
                return $credit;
            }

            Database::execute(
                'UPDATE payments SET gateway_payment_id = ?, gateway_signature = ?, status = ?, credited_at = NOW(), updated_at = NOW() WHERE id = ?',
                [$rzpPaymentId, $rzpSignature, 'completed', $payment['id']]
            );

            $pdo->commit();
            Notification::send((int)$payment['user_id'], 'payment_success', 'Funds Added: ' . format_money($payment['amount']), "Your Razorpay deposit {$payment['payment_uid']} has been verified and credited to your wallet.", '/user/transactions.php', true);

            return ['success' => true, 'duplicate' => false, 'payment_uid' => $payment['payment_uid'], 'amount' => $payment['amount']];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Payment::verifyRazorpayPayment', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['success' => false, 'error' => 'Failed to finalize payment safely.'];
        }
    }

    public static function submitManualPayment(int $userId, string $amount, string $reference, ?array $proofUpload = null): array
    {
        $method = self::getMethod('manual');
        if (!$method || empty($method['is_enabled'])) {
            return ['success' => false, 'error' => 'Manual payment is currently disabled.'];
        }

        $decAmount = Validator::parseDecimal($amount, 2);
        if ($decAmount === null || money_cmp($decAmount, (string)$method['min_amount']) < 0 || money_cmp($decAmount, (string)$method['max_amount']) > 0) {
            return ['success' => false, 'error' => "Deposit amount must be between {$method['min_amount']} and {$method['max_amount']}."];
        }

        $reference = Validator::cleanString($reference, 150);
        if (mb_strlen($reference) < 3) {
            return ['success' => false, 'error' => 'Please enter a valid transaction reference / UTR number.'];
        }

        $proofFilename = null;
        if ($proofUpload && ($proofUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $up = handle_secure_upload($proofUpload, 'proof');
            if (!$up['success']) {
                return $up;
            }
            $proofFilename = $up['filename'];
        }

        $currency = strtoupper(get_setting('currency_code', DEFAULT_CURRENCY));
        $paymentUid = 'MAN-' . strtoupper(bin2hex(random_bytes(5)));

        $paymentId = Database::insert(
            'INSERT INTO payments (payment_uid, user_id, method_code, amount, currency, transaction_reference, proof_file, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [$paymentUid, $userId, 'manual', money_format_dec($decAmount, 4), $currency, $reference, $proofFilename, 'pending']
        );

        Notification::send($userId, 'payment_pending', 'Manual Deposit Submitted: ' . $paymentUid, "Your manual payment request of " . format_money($decAmount) . " (Ref: {$reference}) is pending administrator review.", '/user/transactions.php');

        return ['success' => true, 'payment_id' => $paymentId, 'payment_uid' => $paymentUid];
    }

    public static function approveManualPayment(int $paymentId, int $adminId, string $note = ''): array
    {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();
        try {
            $payment = Database::fetch('SELECT * FROM payments WHERE id = ? FOR UPDATE', [$paymentId]);
            if (!$payment) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Payment record not found.'];
            }
            if ($payment['status'] !== 'pending' || $payment['credited_at'] !== null) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'This payment has already been processed (' . $payment['status'] . ').'];
            }

            $credit = Wallet::credit(
                (int)$payment['user_id'],
                (string)$payment['amount'],
                'manual_deposit',
                (string)$payment['id'],
                "Approved manual deposit {$payment['payment_uid']} (Ref: {$payment['transaction_reference']})",
                'manual_approve_' . $payment['id'],
                null,
                true
            );
            if (!$credit['success']) {
                $pdo->rollBack();
                return $credit;
            }

            Database::execute(
                'UPDATE payments SET status = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW(), credited_at = NOW(), updated_at = NOW() WHERE id = ?',
                ['completed', Validator::cleanString($note, 500), $adminId, $paymentId]
            );

            Logger::adminActivity($adminId, 'payment.approve', 'payment', $paymentId, "Approved manual payment {$payment['payment_uid']} for {$payment['amount']}");
            $pdo->commit();

            Notification::send((int)$payment['user_id'], 'payment_success', 'Deposit Approved: ' . $payment['payment_uid'], "Your manual deposit of " . format_money($payment['amount']) . " has been approved and credited to your wallet.", '/user/transactions.php', true);
            return ['success' => true];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Payment::approveManualPayment', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['success' => false, 'error' => 'Failed to approve payment safely.'];
        }
    }

    public static function rejectManualPayment(int $paymentId, int $adminId, string $note = ''): array
    {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();
        try {
            $payment = Database::fetch('SELECT * FROM payments WHERE id = ? FOR UPDATE', [$paymentId]);
            if (!$payment) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Payment record not found.'];
            }
            if ($payment['status'] !== 'pending') {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Only pending payments can be rejected.'];
            }

            Database::execute(
                'UPDATE payments SET status = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW() WHERE id = ?',
                ['rejected', Validator::cleanString($note, 500), $adminId, $paymentId]
            );

            Logger::adminActivity($adminId, 'payment.reject', 'payment', $paymentId, "Rejected manual payment {$payment['payment_uid']}. Reason: {$note}");
            $pdo->commit();

            Notification::send((int)$payment['user_id'], 'payment_rejected', 'Deposit Rejected: ' . $payment['payment_uid'], "Your manual deposit {$payment['payment_uid']} was rejected." . ($note !== '' ? " Reason: {$note}" : ''), '/user/add-funds.php', true);
            return ['success' => true];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Payment::rejectManualPayment', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['success' => false, 'error' => 'Failed to reject payment.'];
        }
    }
}
