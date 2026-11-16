<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('payments.view');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_permission('payments.manage');
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save_razorpay') {
        $enabled = !empty($_POST['is_enabled']);
        $minAmt = Validator::parseDecimal($_POST['min_amount'] ?? '10', 2) ?? '10.00';
        $maxAmt = Validator::parseDecimal($_POST['max_amount'] ?? '100000', 2) ?? '100000.00';
        $keyId = trim((string)($_POST['key_id'] ?? ''));
        $keySecret = trim((string)($_POST['key_secret'] ?? ''));
        $instructions = Validator::cleanString($_POST['instructions'] ?? '', 1000);

        $existing = Payment::getMethod('razorpay');
        $secretCfg = [
            'key_id' => $keyId !== '' ? $keyId : ($existing['config']['key_id'] ?? ''),
            'key_secret' => $keySecret !== '' ? $keySecret : ($existing['config']['key_secret'] ?? ''),
        ];
        Payment::saveMethodConfig('razorpay', $enabled, $minAmt, $maxAmt, $instructions, $secretCfg);
        Logger::adminActivity((int)$currentAdmin['id'], 'payment_gateway.razorpay', 'payment_method', 'razorpay', 'Updated Razorpay gateway settings');
        Session::setFlash('success', 'Razorpay settings saved securely.');
    } elseif ($action === 'save_manual') {
        $enabled = !empty($_POST['is_enabled']);
        $minAmt = Validator::parseDecimal($_POST['min_amount'] ?? '50', 2) ?? '50.00';
        $maxAmt = Validator::parseDecimal($_POST['max_amount'] ?? '100000', 2) ?? '100000.00';
        $instructions = trim((string)($_POST['instructions'] ?? ''));

        Payment::saveMethodConfig('manual', $enabled, $minAmt, $maxAmt, $instructions, []);
        Logger::adminActivity((int)$currentAdmin['id'], 'payment_gateway.manual', 'payment_method', 'manual', 'Updated Manual payment instructions');
        Session::setFlash('success', 'Manual payment settings updated.');
    }
    Response::redirect('/admin/payments.php');
}

$razorpay = Payment::getMethod('razorpay');
$manual = Payment::getMethod('manual');

$statusFilter = strtolower(trim((string)($_GET['status'] ?? 'all')));
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];
if ($statusFilter !== 'all' && in_array($statusFilter, PAYMENT_STATUSES, true)) {
    $where[] = 'p.status = ?';
    $params[] = $statusFilter;
}
if ($q !== '') {
    $where[] = '(p.payment_uid LIKE ? OR u.username LIKE ? OR p.transaction_reference LIKE ? OR p.gateway_payment_id LIKE ? OR p.gateway_order_id LIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
$whereSql = implode(' AND ', $where);

$total = (int)Database::fetchColumn("SELECT COUNT(*) FROM payments p INNER JOIN users u ON u.id = p.user_id WHERE {$whereSql}", $params);
$pg = paginate_build($total, $page, 25);

$payments = Database::fetchAll(
    "SELECT p.*, u.username FROM payments p INNER JOIN users u ON u.id = p.user_id WHERE {$whereSql} ORDER BY p.id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
    $params
);

Theme::render('admin/payments', [
    'razorpay' => $razorpay,
    'manual' => $manual,
    'statusFilter' => $statusFilter,
    'q' => $q,
    'payments' => $payments,
    'pg' => $pg,
], 'admin', [
    'pageTitle' => 'Payments & Gateways',
    'currentAdmin' => $currentAdmin,
]);
