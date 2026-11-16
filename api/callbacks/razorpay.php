<?php
declare(strict_types=1);

$bootstrapContext = 'user';
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$currentUser = Auth::requireUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::redirect('/user/add-funds.php');
}

CSRF::verifyOrAbort();

$rzpOrderId = trim((string)($_POST['razorpay_order_id'] ?? ''));
$rzpPaymentId = trim((string)($_POST['razorpay_payment_id'] ?? ''));
$rzpSignature = trim((string)($_POST['razorpay_signature'] ?? ''));

if ($rzpOrderId === '' || $rzpPaymentId === '' || $rzpSignature === '') {
    Session::setFlash('error', 'Missing Razorpay verification parameters.');
    Response::redirect('/user/add-funds.php');
}

$res = Payment::verifyRazorpayPayment($rzpOrderId, $rzpPaymentId, $rzpSignature, (int)$currentUser['id']);
if ($res['success']) {
    $msg = !empty($res['duplicate'])
        ? "Payment {$res['payment_uid']} was already verified and credited."
        : "Payment {$res['payment_uid']} verified! " . format_money($res['amount'] ?? 0, 2) . " added to your wallet.";
    Session::setFlash('success', $msg);
    Response::redirect('/user/transactions.php');
}

Session::setFlash('error', 'Payment verification failed: ' . ($res['error'] ?? 'Unknown error'));
Response::redirect('/user/add-funds.php');
