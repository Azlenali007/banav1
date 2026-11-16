<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('payments.view');

$paymentId = (int)($_GET['id'] ?? 0);
$payment = Database::fetch(
    'SELECT p.*, u.username, u.email FROM payments p INNER JOIN users u ON u.id = p.user_id WHERE p.id = ? LIMIT 1',
    [$paymentId]
);
if (!$payment) {
    Response::abort(404, 'Payment Not Found', 'The requested payment record does not exist.');
}

// Securely stream proof file to authorized admin without exposing /storage/uploads/ publicly
if (!empty($_GET['view_proof']) && !empty($payment['proof_file'])) {
    $safeFile = basename((string)$payment['proof_file']);
    $fullPath = UPLOADS_PATH . '/' . $safeFile;
    if (is_file($fullPath)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($fullPath);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }
    Response::abort(404, 'Proof Missing', 'Proof image file not found on disk.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_permission('payments.manage');
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');
    $note = (string)($_POST['admin_note'] ?? '');

    if ($action === 'approve') {
        $res = Payment::approveManualPayment($paymentId, (int)$currentAdmin['id'], $note);
        Session::setFlash($res['success'] ? 'success' : 'error', $res['success'] ? 'Payment approved and wallet credited.' : (string)$res['error']);
    } elseif ($action === 'reject') {
        $res = Payment::rejectManualPayment($paymentId, (int)$currentAdmin['id'], $note);
        Session::setFlash($res['success'] ? 'success' : 'error', $res['success'] ? 'Payment marked as rejected.' : (string)$res['error']);
    }
    Response::redirect('/admin/payment-view.php?id=' . $paymentId);
}

Theme::render('admin/payment-view', [
    'paymentId' => $paymentId,
    'payment' => $payment,
], 'admin', [
    'pageTitle' => 'Review Payment ' . $payment['payment_uid'],
    'currentAdmin' => $currentAdmin,
]);
