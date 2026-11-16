<?php
declare(strict_types=1);

$bootstrapContext = 'admin';
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once Theme::path('theme.php');
require_once __DIR__ . '/navigation.php';
require_once MODULES_PATH . '/orders/OrderModule.php';
require_once MODULES_PATH . '/wallet/WalletModule.php';
require_once MODULES_PATH . '/payments/PaymentModule.php';
require_once MODULES_PATH . '/users/UserModule.php';
require_once MODULES_PATH . '/reports/ReportModule.php';
require_once MODULES_PATH . '/tickets/TicketModule.php';

if (!defined('ADMIN_ALLOW_GUEST')) {
    $currentAdmin = Auth::requireAdmin();
}
