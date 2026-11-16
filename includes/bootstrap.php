<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/constants.php';
require_once INCLUDES_PATH . '/error-handler.php';
require_once INCLUDES_PATH . '/helpers.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/mailer.php';

require_once CORE_PATH . '/Logger.php';
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Session.php';
require_once CORE_PATH . '/CSRF.php';
require_once CORE_PATH . '/Validator.php';
require_once CORE_PATH . '/Response.php';
require_once CORE_PATH . '/Permission.php';
require_once CORE_PATH . '/Auth.php';
require_once CORE_PATH . '/Theme.php';
require_once CORE_PATH . '/Wallet.php';
require_once CORE_PATH . '/Notification.php';
require_once CORE_PATH . '/Provider.php';
require_once CORE_PATH . '/Order.php';
require_once CORE_PATH . '/Payment.php';

require_once INCLUDES_PATH . '/permissions.php';
require_once INCLUDES_PATH . '/notifications.php';

$bootstrapContext = $bootstrapContext ?? 'user';

if ($bootstrapContext !== 'install' && !is_installed()) {
    if ($bootstrapContext === 'api') {
        Response::json(['error' => 'Application is not installed yet.'], 503);
    }
    if ($bootstrapContext === 'cron') {
        fwrite(STDERR, "Application is not installed yet.\n");
        exit(1);
    }
    Response::redirect('/install/index.php');
}

if ($bootstrapContext !== 'install' && is_installed()) {
    $tz = (string)get_setting('timezone', 'Asia/Kolkata');
    if ($tz !== '') {
        @date_default_timezone_set($tz);
    }
}

if (in_array($bootstrapContext, ['user', 'admin', 'install'], true)) {
    Session::start($bootstrapContext === 'admin' ? 'admin' : 'user');
}
