<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('users.view');

$userId = (int)($_GET['id'] ?? 0);
$user = Database::fetch(
    'SELECT u.*, w.balance, w.total_deposited, w.total_spent
     FROM users u
     LEFT JOIN wallets w ON w.user_id = u.id
     WHERE u.id = ? LIMIT 1',
    [$userId]
);

if (!$user) {
    Response::abort(404, 'User Not Found', 'The requested user record does not exist.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_permission('users.manage');
    CSRF::verifyOrAbort();

    $adjType = (string)($_POST['adj_type'] ?? '');
    $amount = Validator::parseDecimal($_POST['amount'] ?? '', 2);
    $reason = Validator::cleanString($_POST['reason'] ?? '', 200);
    $idem = 'admin_adj_' . $userId . '_' . Validator::cleanString($_POST['idempotency_key'] ?? bin2hex(random_bytes(8)), 64);

    if ($amount === null || money_cmp($amount, '0.0000') <= 0 || $reason === '') {
        Session::setFlash('error', 'Please enter a valid positive amount and adjustment reason.');
    } else {
        $adminId = (int)$currentAdmin['id'];
        if ($adjType === 'credit') {
            $res = Wallet::credit($userId, $amount, 'admin_credit', (string)$adminId, 'Admin credit: ' . $reason, $idem, $adminId, false);
        } elseif ($adjType === 'debit') {
            $res = Wallet::debit($userId, $amount, 'admin_debit', (string)$adminId, 'Admin debit: ' . $reason, $idem, $adminId, false);
        } elseif ($adjType === 'refund') {
            $res = Wallet::refund($userId, $amount, 'admin_refund', (string)$adminId, 'Admin refund: ' . $reason, $idem, $adminId);
        } else {
            $res = ['success' => false, 'error' => 'Invalid adjustment type.'];
        }

        if ($res['success']) {
            Logger::adminActivity($adminId, 'wallet.' . $adjType, 'user', $userId, "Amount {$amount}. Reason: {$reason}");
            Notification::send($userId, 'wallet_update', 'Wallet Balance Updated', "An administrator applied a {$adjType} of " . format_money($amount, 2) . ". Note: {$reason}", '/user/transactions.php');
            Session::setFlash('success', 'Wallet adjustment applied and logged.');
        } else {
            Session::setFlash('error', (string)($res['error'] ?? 'Wallet adjustment failed.'));
        }
    }
    Response::redirect('/admin/user-view.php?id=' . $userId);
}

$orders = Database::fetchAll('SELECT o.*, s.name AS service_name FROM orders o INNER JOIN services s ON s.id = o.service_id WHERE o.user_id = ? ORDER BY o.id DESC LIMIT 15', [$userId]);
$txs = Database::fetchAll('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 20', [$userId]);
$adjIdemKey = bin2hex(random_bytes(12));

Theme::render('admin/user-view', [
    'userId' => $userId,
    'user' => $user,
    'orders' => $orders,
    'txs' => $txs,
    'adjIdemKey' => $adjIdemKey,
], 'admin', [
    'pageTitle' => 'User: ' . $user['username'],
    'currentAdmin' => $currentAdmin,
]);
