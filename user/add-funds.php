<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$userId = (int)$currentUser['id'];
$razorpay = Payment::getMethod('razorpay');
$manual = Payment::getMethod('manual');

$rzpEnabled = $razorpay && !empty($razorpay['is_enabled']) && !empty($razorpay['config']['key_id']) && !empty($razorpay['config']['key_secret']);
$manualEnabled = $manual && !empty($manual['is_enabled']);

$error = '';
$razorpayOrderPayload = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $gateway = (string)($_POST['gateway'] ?? '');
    if ($gateway === 'razorpay') {
        $amount = Currency::toBase((string)($_POST['amount'] ?? '0'), Currency::displayCode(), 4);
        $res = Payment::createRazorpayOrder($userId, $amount);
        if ($res['success']) {
            $razorpayOrderPayload = $res;
        } else {
            $error = (string)$res['error'];
        }
    } elseif ($gateway === 'manual') {
        $amount = Currency::toBase((string)($_POST['amount'] ?? '0'), Currency::displayCode(), 4);
        $ref = (string)($_POST['transaction_reference'] ?? '');
        $proof = $_FILES['proof_file'] ?? null;
        $res = Payment::submitManualPayment($userId, $amount, $ref, $proof);
        if ($res['success']) {
            Session::setFlash('success', "Manual deposit {$res['payment_uid']} submitted for admin verification.");
            Response::redirect('/user/transactions.php');
        }
        $error = (string)$res['error'];
    }
}

Theme::render('user/add-funds', [
    'currentUser' => $currentUser,
    'razorpay' => $razorpay,
    'manual' => $manual,
    'rzpEnabled' => $rzpEnabled,
    'manualEnabled' => $manualEnabled,
    'error' => $error,
    'razorpayOrderPayload' => $razorpayOrderPayload,
], 'user', [
    'pageTitle' => 'Add Funds',
    'currentUser' => $currentUser,
]);
