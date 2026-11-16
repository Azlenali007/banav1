<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
$page = max(1, (int)($_GET['page'] ?? 1));

$deposits = Database::fetchAll('SELECT * FROM payments WHERE user_id = ? ORDER BY id DESC LIMIT 25', [$userId]);
$totalTx = (int)Database::fetchColumn('SELECT COUNT(*) FROM wallet_transactions WHERE user_id = ?', [$userId]);
$pg = paginate_build($totalTx, $page, 20);
$transactions = Database::fetchAll(
    "SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
    [$userId]
);

Theme::render('user/transactions', [
    'deposits' => $deposits,
    'transactions' => $transactions,
    'pg' => $pg,
], 'user', [
    'pageTitle' => 'Transactions & Deposits',
    'currentUser' => $currentUser,
]);
