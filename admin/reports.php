<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('reports.view');

$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['from'] ?? '')) ? $_GET['from'] : date('Y-m-d', strtotime('-30 days'));
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['to'] ?? '')) ? $_GET['to'] : date('Y-m-d');
$reportType = in_array($_GET['type'] ?? '', ['sales', 'orders', 'users', 'payments', 'providers'], true) ? $_GET['type'] : 'sales';

$rows = [];
if ($reportType === 'sales') {
    $rows = Database::fetchAll(
        "SELECT DATE(created_at) AS date_day,
                COUNT(*) AS total_orders,
                SUM(status = 'completed') AS completed_orders,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','failed') THEN charge - refunded_amount ELSE 0 END), 0) AS net_sales,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','failed') THEN provider_cost ELSE 0 END), 0) AS provider_cost
         FROM orders
         WHERE DATE(created_at) BETWEEN ? AND ?
         GROUP BY DATE(created_at)
         ORDER BY date_day DESC",
        [$from, $to]
    );
} elseif ($reportType === 'orders') {
    $rows = Database::fetchAll(
        "SELECT o.order_uid, u.username, s.name AS service_name, o.quantity, o.charge, o.refunded_amount, o.status, o.created_at
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         INNER JOIN services s ON s.id = o.service_id
         WHERE DATE(o.created_at) BETWEEN ? AND ?
         ORDER BY o.id DESC LIMIT 500",
        [$from, $to]
    );
} elseif ($reportType === 'users') {
    $rows = Database::fetchAll(
        "SELECT u.id, u.username, u.email, u.status, COALESCE(w.balance,0) AS balance, COALESCE(w.total_deposited,0) AS total_deposited, COALESCE(w.total_spent,0) AS total_spent, u.created_at
         FROM users u LEFT JOIN wallets w ON w.user_id = u.id
         WHERE DATE(u.created_at) BETWEEN ? AND ?
         ORDER BY u.id DESC LIMIT 500",
        [$from, $to]
    );
} elseif ($reportType === 'payments') {
    $rows = Database::fetchAll(
        "SELECT p.payment_uid, u.username, p.method_code, p.amount, p.currency, p.status, p.transaction_reference, p.created_at
         FROM payments p INNER JOIN users u ON u.id = p.user_id
         WHERE DATE(p.created_at) BETWEEN ? AND ?
         ORDER BY p.id DESC LIMIT 500",
        [$from, $to]
    );
} elseif ($reportType === 'providers') {
    $rows = Database::fetchAll(
        "SELECT p.name, p.balance, p.currency,
                COUNT(o.id) AS total_orders,
                COALESCE(SUM(o.provider_cost), 0) AS total_cost,
                COALESCE(SUM(o.charge - o.refunded_amount), 0) AS gross_billed
         FROM providers p
         LEFT JOIN orders o ON o.provider_id = p.id AND DATE(o.created_at) BETWEEN ? AND ?
         GROUP BY p.id",
        [$from, $to]
    );
}

// CSV Export
if (!empty($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="report-' . $reportType . '-' . $from . '-to-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    if (!empty($rows)) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $r) {
            fputcsv($out, array_values($r));
        }
    }
    fclose($out);
    exit;
}

Theme::render('admin/reports', [
    'reportType' => $reportType,
    'from' => $from,
    'to' => $to,
    'rows' => $rows,
], 'admin', [
    'pageTitle' => 'Reports & Analytics',
    'currentAdmin' => $currentAdmin,
]);
