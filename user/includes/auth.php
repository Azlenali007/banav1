<?php
declare(strict_types=1);

$bootstrapContext = 'user';
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once Theme::path('theme.php');
require_once __DIR__ . '/navigation.php';
require_once MODULES_PATH . '/orders/OrderModule.php';
require_once MODULES_PATH . '/wallet/WalletModule.php';
require_once MODULES_PATH . '/tickets/TicketModule.php';

enforce_maintenance_mode();
$currentUser = Auth::requireUser();
