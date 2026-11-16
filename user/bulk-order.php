<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
$multiplier = (string)($currentUser['custom_rate_multiplier'] ?? '1.0000');
$rawInput = (string)($_POST['bulk_lines'] ?? '');
$lineErrors = [];
$results = [];
$generalError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $batchIdem = Validator::cleanString($_POST['idempotency_key'] ?? '', 80);

    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $rawInput)), static fn($l) => $l !== ''));
    if (empty($lines)) {
        $generalError = 'Please enter at least one order line.';
    } elseif (count($lines) > 100) {
        $generalError = 'Maximum 100 lines per bulk submission.';
    } else {
        $validated = [];
        $totalRequired = '0.0000';
        foreach ($lines as $idx => $line) {
            $lineNo = $idx + 1;
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) !== 3) {
                $lineErrors[] = "Line {$lineNo}: Invalid format. Use service_id | link | quantity";
                continue;
            }
            [$sidRaw, $link, $qtyRaw] = $parts;
            if (!Validator::isPositiveInt($sidRaw)) {
                $lineErrors[] = "Line {$lineNo}: Invalid service ID.";
                continue;
            }
            if (!Validator::isUrl($link)) {
                $lineErrors[] = "Line {$lineNo}: Invalid URL (must start with http:// or https://).";
                continue;
            }
            if (!Validator::isPositiveInt($qtyRaw)) {
                $lineErrors[] = "Line {$lineNo}: Quantity must be a positive integer.";
                continue;
            }

            $sid = (int)$sidRaw;
            $qty = (int)$qtyRaw;
            $svc = Database::fetch(
                "SELECT s.*, c.status AS category_status FROM services s INNER JOIN categories c ON c.id = s.category_id WHERE s.id = ? LIMIT 1",
                [$sid]
            );
            if (!$svc || $svc['status'] !== 'active' || $svc['category_status'] !== 'active') {
                $lineErrors[] = "Line {$lineNo}: Service #{$sid} is unavailable.";
                continue;
            }
            if ($qty < (int)$svc['min_qty'] || $qty > (int)$svc['max_qty']) {
                $lineErrors[] = "Line {$lineNo}: Quantity {$qty} out of range ({$svc['min_qty']}–{$svc['max_qty']}).";
                continue;
            }
            $charge = Order::calculateCharge((string)$svc['rate_per_1000'], $qty, $multiplier);
            $totalRequired = money_add($totalRequired, $charge, 4);
            $validated[] = ['line_no' => $lineNo, 'service_id' => $sid, 'link' => $link, 'quantity' => $qty, 'charge' => $charge];
        }

        if (empty($lineErrors)) {
            $walletBal = Wallet::getBalance($userId);
            if (money_cmp($walletBal, $totalRequired) < 0) {
                $generalError = 'Insufficient wallet balance. Required total: ' . format_money($totalRequired, 2) . ', Available: ' . format_money($walletBal, 2);
            } else {
                $successCount = 0;
                foreach ($validated as $item) {
                    $lineIdem = $batchIdem !== '' ? $batchIdem . '_line_' . $item['line_no'] : null;
                    $res = Order::placeOrder($userId, $item['service_id'], $item['link'], $item['quantity'], 'bulk', $lineIdem);
                    if ($res['success']) {
                        $successCount++;
                        $results[] = "Line {$item['line_no']}: Placed {$res['order_uid']} (" . format_money($res['charge'], 2) . ")";
                    } else {
                        $lineErrors[] = "Line {$item['line_no']}: " . ($res['error'] ?? 'Failed');
                    }
                }
                if ($successCount > 0 && empty($lineErrors)) {
                    Session::setFlash('success', "All {$successCount} bulk orders placed successfully!");
                    Response::redirect('/user/orders.php');
                }
            }
        }
    }
}

$formIdemKey = bin2hex(random_bytes(16));

Theme::render('user/bulk-order', [
    'currentUser' => $currentUser,
    'generalError' => $generalError,
    'lineErrors' => $lineErrors,
    'results' => $results,
    'rawInput' => $rawInput,
    'formIdemKey' => $formIdemKey,
], 'user', [
    'pageTitle' => 'Bulk Order',
    'currentUser' => $currentUser,
]);
