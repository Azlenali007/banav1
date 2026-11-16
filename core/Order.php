<?php
declare(strict_types=1);

class Order
{
    public static function calculateCharge(string $ratePer1000, int $quantity, string $multiplier = '1.0000'): string
    {
        $effectiveRate = money_mul($ratePer1000, $multiplier, 6);
        $raw = money_div(money_mul($effectiveRate, (string)$quantity, 8), '1000', 4);
        return money_format_dec($raw, 4);
    }

    public static function placeOrder(
        int $userId,
        int $serviceId,
        string $link,
        int $quantity,
        string $source = 'web',
        ?string $idempotencyKey = null
    ): array {
        $link = Validator::cleanString($link, 1000);
        if (!Validator::isUrl($link)) {
            return ['success' => false, 'error' => 'Please enter a valid target URL starting with http:// or https://.'];
        }

        $user = Database::fetch('SELECT * FROM users WHERE id = ? AND status = ? LIMIT 1', [$userId, 'active']);
        if (!$user) {
            return ['success' => false, 'error' => 'User account is inactive or unavailable.'];
        }

        $service = Database::fetch(
            'SELECT s.*, c.status AS category_status
             FROM services s
             INNER JOIN categories c ON c.id = s.category_id
             WHERE s.id = ? LIMIT 1',
            [$serviceId]
        );
        if (!$service || $service['status'] !== 'active' || $service['category_status'] !== 'active') {
            return ['success' => false, 'error' => 'Selected service is unavailable or disabled.'];
        }

        $minQty = (int)$service['min_qty'];
        $maxQty = (int)$service['max_qty'];
        if ($quantity < $minQty || $quantity > $maxQty) {
            return ['success' => false, 'error' => "Quantity must be between {$minQty} and {$maxQty}."];
        }

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $dup = Database::fetch('SELECT id, order_uid, charge, status FROM orders WHERE idempotency_key = ? LIMIT 1', [$idempotencyKey]);
            if ($dup) {
                return ['success' => true, 'duplicate' => true, 'order_id' => (int)$dup['id'], 'order_uid' => $dup['order_uid'], 'charge' => $dup['charge']];
            }
        }

        $multiplier = (string)($user['custom_rate_multiplier'] ?? '1.0000');
        $charge = self::calculateCharge((string)$service['rate_per_1000'], $quantity, $multiplier);
        if (money_cmp($charge, '0.0000') <= 0) {
            return ['success' => false, 'error' => 'Calculated order charge must be greater than zero.'];
        }

        $mapping = Database::fetch(
            'SELECT psm.*, p.status AS provider_status
             FROM provider_service_mapping psm
             INNER JOIN providers p ON p.id = psm.provider_id
             WHERE psm.service_id = ? LIMIT 1',
            [$serviceId]
        );

        $providerId = ($mapping && $mapping['provider_status'] === 'active') ? (int)$mapping['provider_id'] : null;
        $providerCost = '0.0000';
        if ($mapping && $providerId !== null) {
            $providerCost = self::calculateCharge((string)$mapping['provider_rate'], $quantity, '1.0000');
        }

        $orderUid = 'ORD-' . strtoupper(bin2hex(random_bytes(5)));
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $orderId = Database::insert(
                'INSERT INTO orders (order_uid, user_id, service_id, provider_id, link, quantity, charge, provider_cost, remains, status, source, idempotency_key, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [$orderUid, $userId, $serviceId, $providerId, $link, $quantity, $charge, $providerCost, $quantity, 'pending', $source, $idempotencyKey]
            );

            $debit = Wallet::debit(
                $userId,
                $charge,
                'order',
                (string)$orderId,
                "Order {$orderUid} — {$service['name']} (Qty: {$quantity})",
                'order_debit_' . $orderId
            );

            if (!$debit['success']) {
                $pdo->rollBack();
                return ['success' => false, 'error' => $debit['error'] ?? 'Insufficient wallet balance.'];
            }

            Database::insert(
                'INSERT INTO order_logs (order_id, actor_type, actor_id, old_status, new_status, message, created_at) VALUES (?, ?, ?, NULL, ?, ?, NOW())',
                [$orderId, 'user', $userId, 'pending', "Order created via {$source}. Charge: {$charge}"]
            );

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Order::placeOrder', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['success' => false, 'error' => 'Could not create order safely. Please try again.'];
        }

        // Dispatch to external provider outside DB lock
        if ($providerId !== null && $mapping) {
            $provider = Provider::getById($providerId);
            if ($provider && $provider['status'] === 'active') {
                $apiRes = Provider::createOrder($provider, (string)$mapping['provider_service_id'], $link, $quantity);
                if ($apiRes['success'] && !empty($apiRes['data']['order'])) {
                    $providerOrderId = (string)$apiRes['data']['order'];
                    Database::execute(
                        'UPDATE orders SET provider_order_id = ?, status = ?, updated_at = NOW() WHERE id = ?',
                        [$providerOrderId, 'processing', $orderId]
                    );
                    Database::insert(
                        'INSERT INTO order_logs (order_id, actor_type, old_status, new_status, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
                        [$orderId, 'provider', 'pending', 'processing', "Submitted to provider #{$providerId}, provider order ID: {$providerOrderId}"]
                    );
                } else {
                    $errReason = (string)($apiRes['error'] ?? 'Provider rejected or failed to accept order.');
                    self::failAndRefundOrder($orderId, $userId, $charge, $orderUid, $errReason);
                    return ['success' => false, 'error' => 'Provider submission failed: ' . $errReason . '. Your wallet has been refunded automatically.', 'order_id' => $orderId, 'order_uid' => $orderUid];
                }
            }
        }

        Notification::send($userId, 'order_new', 'Order Placed: ' . $orderUid, "Your order {$orderUid} for {$service['name']} ({$quantity}) has been placed.", '/user/order-view.php?id=' . $orderId);

        try {
            Referral::processEvent($userId, 'first_order', $charge);
        } catch (Throwable $e) {
            Logger::error('Order::placeOrder referral', $e->getMessage(), $e->getFile(), $e->getLine());
        }

        return [
            'success' => true,
            'order_id' => $orderId,
            'order_uid' => $orderUid,
            'charge' => $charge,
        ];
    }

    public static function failAndRefundOrder(int $orderId, int $userId, string $charge, string $orderUid, string $reason): void
    {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();
        try {
            $order = Database::fetch('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
            if (!$order || money_cmp((string)$order['refunded_amount'], '0.0000') > 0) {
                $pdo->commit();
                return;
            }
            Database::execute(
                'UPDATE orders SET status = ?, refunded_amount = ?, notes = ?, updated_at = NOW() WHERE id = ?',
                ['failed', $charge, mb_substr($reason, 0, 1000), $orderId]
            );
            Wallet::refund($userId, $charge, 'order_refund', (string)$orderId, "Automatic refund for failed order {$orderUid}", 'refund_failed_order_' . $orderId);
            Database::insert(
                'INSERT INTO order_logs (order_id, actor_type, old_status, new_status, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
                [$orderId, 'system', (string)$order['status'], 'failed', "Provider error: {$reason}. Auto-refunded {$charge}."]
            );
            $pdo->commit();
            Notification::send($userId, 'order_cancelled', 'Order Failed & Refunded: ' . $orderUid, "Order {$orderUid} failed at provider ({$reason}) and {$charge} was refunded to your wallet.", '/user/order-view.php?id=' . $orderId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Order::failAndRefundOrder', $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public static function syncOrder(int $orderId): array
    {
        $order = Database::fetch('SELECT * FROM orders WHERE id = ? LIMIT 1', [$orderId]);
        if (!$order || empty($order['provider_id']) || empty($order['provider_order_id'])) {
            return ['success' => false, 'error' => 'Order is not linked to a provider order.'];
        }
        $provider = Provider::getById((int)$order['provider_id']);
        if (!$provider) {
            return ['success' => false, 'error' => 'Provider not found.'];
        }

        $res = Provider::orderStatus($provider, (string)$order['provider_order_id']);
        if (!$res['success']) {
            return $res;
        }

        $data = $res['data'];
        $newStatus = Provider::mapProviderStatus((string)($data['status'] ?? $order['status']));
        $startCount = isset($data['start_count']) ? max(0, (int)$data['start_count']) : (int)$order['start_count'];
        $remains = isset($data['remains']) ? min((int)$order['quantity'], max(0, (int)$data['remains'])) : (int)$order['remains'];

        return self::applyStatusTransition($orderId, $newStatus, $startCount, $remains, 'cron', null, 'Synchronized from provider');
    }

    public static function applyStatusTransition(
        int $orderId,
        string $newStatus,
        int $startCount,
        int $remains,
        string $actorType = 'system',
        ?int $actorId = null,
        string $reason = ''
    ): array {
        if (!in_array($newStatus, ORDER_STATUSES, true)) {
            return ['success' => false, 'error' => 'Invalid order status.'];
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();
        try {
            $order = Database::fetch('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
            if (!$order) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Order not found.'];
            }

            $oldStatus = (string)$order['status'];
            $refundAmount = '0.0000';

            if ($newStatus === 'completed') {
                $remains = 0;
            }

            if (in_array($newStatus, ['cancelled', 'failed'], true) && money_cmp((string)$order['refunded_amount'], '0.0000') === 0) {
                $refundAmount = (string)$order['charge'];
                $remains = (int)$order['quantity'];
                $wRes = Wallet::refund(
                    (int)$order['user_id'],
                    $refundAmount,
                    'order_refund',
                    (string)$orderId,
                    "Full refund for {$newStatus} order {$order['order_uid']}",
                    'refund_order_' . $orderId,
                    $actorType === 'admin' ? $actorId : null
                );
                if (!$wRes['success']) {
                    $pdo->rollBack();
                    return $wRes;
                }
            } elseif ($newStatus === 'partial' && money_cmp((string)$order['refunded_amount'], '0.0000') === 0 && $remains > 0) {
                $qty = max(1, (int)$order['quantity']);
                $refundAmount = money_div(money_mul((string)$order['charge'], (string)$remains, 8), (string)$qty, 4);
                if (money_cmp($refundAmount, (string)$order['charge']) > 0) {
                    $refundAmount = (string)$order['charge'];
                }
                if (money_cmp($refundAmount, '0.0000') > 0) {
                    $wRes = Wallet::refund(
                        (int)$order['user_id'],
                        $refundAmount,
                        'order_partial_refund',
                        (string)$orderId,
                        "Partial refund ({$remains}/{$qty} remaining) for order {$order['order_uid']}",
                        'refund_partial_order_' . $orderId,
                        $actorType === 'admin' ? $actorId : null
                    );
                    if (!$wRes['success']) {
                        $pdo->rollBack();
                        return $wRes;
                    }
                }
            }

            $totalRefunded = money_add((string)$order['refunded_amount'], $refundAmount, 4);

            Database::execute(
                'UPDATE orders SET status = ?, start_count = ?, remains = ?, refunded_amount = ?, updated_at = NOW() WHERE id = ?',
                [$newStatus, $startCount, $remains, $totalRefunded, $orderId]
            );

            if ($oldStatus !== $newStatus || $reason !== '') {
                $logMsg = $reason !== '' ? $reason : "Status updated from {$oldStatus} to {$newStatus}";
                if (money_cmp($refundAmount, '0.0000') > 0) {
                    $logMsg .= " (Refunded: {$refundAmount})";
                }
                Database::insert(
                    'INSERT INTO order_logs (order_id, actor_type, actor_id, old_status, new_status, message, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
                    [$orderId, $actorType, $actorId, $oldStatus, $newStatus, $logMsg]
                );
            }

            $pdo->commit();

            if ($oldStatus !== $newStatus && in_array($newStatus, ['processing', 'completed', 'partial', 'cancelled'], true)) {
                $notifType = 'order_' . $newStatus;
                Notification::send(
                    (int)$order['user_id'],
                    $notifType,
                    "Order {$order['order_uid']} is now " . ucfirst(str_replace('_', ' ', $newStatus)),
                    "Your order {$order['order_uid']} changed status from {$oldStatus} to {$newStatus}.",
                    '/user/order-view.php?id=' . $orderId
                );
            }

            return ['success' => true, 'status' => $newStatus, 'refunded' => $refundAmount];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Order::applyStatusTransition', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['success' => false, 'error' => 'Database error while updating order.'];
        }
    }

    public static function requestRefill(int $orderId, int $userId): array
    {
        $order = Database::fetch(
            'SELECT o.*, s.refill_supported FROM orders o INNER JOIN services s ON s.id = o.service_id WHERE o.id = ? AND o.user_id = ? LIMIT 1',
            [$orderId, $userId]
        );
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found.'];
        }
        if (empty($order['refill_supported'])) {
            return ['success' => false, 'error' => 'Refill is not supported for this service.'];
        }
        if ($order['status'] !== 'completed') {
            return ['success' => false, 'error' => 'Refill can only be requested on completed orders.'];
        }
        if (in_array($order['refill_status'], ['requested', 'processing'], true)) {
            return ['success' => false, 'error' => 'A refill request is already active for this order.'];
        }

        $refillProviderId = null;
        $newRefillStatus = 'requested';
        if (!empty($order['provider_id']) && !empty($order['provider_order_id'])) {
            $provider = Provider::getById((int)$order['provider_id']);
            if ($provider && $provider['status'] === 'active') {
                $apiRes = Provider::refillOrder($provider, (string)$order['provider_order_id']);
                if ($apiRes['success'] && !empty($apiRes['data']['refill'])) {
                    $refillProviderId = (string)$apiRes['data']['refill'];
                    $newRefillStatus = 'processing';
                } elseif (!$apiRes['success']) {
                    return ['success' => false, 'error' => 'Provider refill error: ' . ($apiRes['error'] ?? 'Unknown error')];
                }
            }
        }

        Database::execute(
            'UPDATE orders SET refill_status = ?, refill_provider_id = ?, updated_at = NOW() WHERE id = ?',
            [$newRefillStatus, $refillProviderId, $orderId]
        );
        Database::insert(
            'INSERT INTO order_logs (order_id, actor_type, actor_id, old_status, new_status, message, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$orderId, 'user', $userId, (string)$order['status'], (string)$order['status'], 'Refill requested' . ($refillProviderId ? " (Provider Refill ID: {$refillProviderId})" : '')]
        );

        return ['success' => true, 'refill_status' => $newRefillStatus, 'refill_id' => $refillProviderId];
    }

    public static function requestCancel(int $orderId, int $userId): array
    {
        $order = Database::fetch(
            'SELECT o.*, s.cancel_supported FROM orders o INNER JOIN services s ON s.id = o.service_id WHERE o.id = ? AND o.user_id = ? LIMIT 1',
            [$orderId, $userId]
        );
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found.'];
        }
        if (empty($order['cancel_supported'])) {
            return ['success' => false, 'error' => 'Cancellation is not supported for this service.'];
        }
        if (!in_array($order['status'], ['pending', 'processing', 'in_progress'], true)) {
            return ['success' => false, 'error' => 'This order can no longer be cancelled.'];
        }
        if (!empty($order['cancel_requested'])) {
            return ['success' => false, 'error' => 'Cancellation has already been requested.'];
        }

        if (!empty($order['provider_id']) && !empty($order['provider_order_id'])) {
            $provider = Provider::getById((int)$order['provider_id']);
            if ($provider && $provider['status'] === 'active') {
                $apiRes = Provider::cancelOrders($provider, (string)$order['provider_order_id']);
                if (!$apiRes['success']) {
                    return ['success' => false, 'error' => 'Provider cancel error: ' . ($apiRes['error'] ?? 'Unable to cancel')];
                }
            }
        }

        Database::execute('UPDATE orders SET cancel_requested = 1, updated_at = NOW() WHERE id = ?', [$orderId]);
        Database::insert(
            'INSERT INTO order_logs (order_id, actor_type, actor_id, old_status, new_status, message, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$orderId, 'user', $userId, (string)$order['status'], (string)$order['status'], 'User requested order cancellation.']
        );

        return ['success' => true];
    }
}
