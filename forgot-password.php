<?php
declare(strict_types=1);

$bootstrapContext = 'user';
require_once __DIR__ . '/includes/bootstrap.php';
enforce_maintenance_mode();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $email = strtolower(Validator::cleanString($_POST['email'] ?? '', 150));
    if (!Validator::isEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $token = Auth::createPasswordResetToken($email);
        if ($token !== null) {
            $resetLink = app_url('/reset-password.php?token=' . urlencode($token));
            $siteName = get_setting('site_name', APP_DEFAULT_NAME);
            $html = '<p>A password reset was requested for your account on ' . e($siteName) . '.</p>'
                . '<p><a href="' . e($resetLink) . '">Click here to reset your password</a> (valid for 60 minutes).</p>'
                . '<p>If you did not request this, you can safely ignore this email.</p>';
            $mailRes = send_mail($email, 'Password Reset — ' . $siteName, $html);
            if (!$mailRes['success']) {
                Logger::file('mail', 'WARN', 'Password reset email failed to send', ['email' => $email, 'err' => $mailRes['error'] ?? '']);
            }
        }
        // Always show neutral message so account existence is not leaked
        $message = 'If an active account matches that email address, a password reset link has been dispatched.';
    }
}

Theme::render('public/forgot-password', [
    'error' => $error,
    'message' => $message,
], 'public', [
    'pageTitle' => 'Forgot Password',
]);
