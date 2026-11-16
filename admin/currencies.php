<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('currencies.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(Validator::cleanString($_POST['code'] ?? '', 10));
        $name = Validator::cleanString($_POST['name'] ?? '', 100);
        $symbol = Validator::cleanString($_POST['symbol'] ?? '', 12);
        $decimals = max(0, min(4, (int)($_POST['decimals'] ?? 2)));
        $position = ($_POST['symbol_position'] ?? 'before') === 'after' ? 'after' : 'before';
        $rate = Validator::parseDecimal($_POST['exchange_rate'] ?? '1', 8);
        $enabled = !empty($_POST['is_enabled']) ? 1 : 0;
        $isDefault = !empty($_POST['is_default']);
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        $existing = $id > 0 ? Database::fetch('SELECT * FROM currencies WHERE id = ? LIMIT 1', [$id]) : null;
        $duplicate = Database::fetch('SELECT id FROM currencies WHERE code = ? AND id <> ? LIMIT 1', [$code, $id]);

        if (!preg_match('/^[A-Z0-9]{2,10}$/', $code)) {
            Session::setFlash('error', 'Currency code must be 2-10 letters/numbers (e.g. USD).');
        } elseif ($name === '' || $symbol === '') {
            Session::setFlash('error', 'Currency name and symbol are required.');
        } elseif ($duplicate) {
            Session::setFlash('error', 'That currency code already exists.');
        } elseif ($rate === null || money_cmp($rate, '0', 8) <= 0) {
            Session::setFlash('error', 'Exchange rate must be a positive number.');
        } elseif ($isDefault && $id === 0) {
            Session::setFlash('error', 'Create the currency first, then mark it as the base currency.');
        } else {
            if ($isDefault) {
                $rate = '1.00000000';
                $enabled = 1;
            }

            if ($existing) {
                Database::execute(
                    'UPDATE currencies SET code=?, name=?, symbol=?, decimals=?, symbol_position=?, exchange_rate=?, is_enabled=?, sort_order=?, rate_source=?, rate_updated_at=NOW(), updated_at=NOW() WHERE id=?',
                    [$code, $name, $symbol, $decimals, $position, $rate, $enabled, $sortOrder, 'manual', $id]
                );
                if ($isDefault) {
                    Database::execute('UPDATE currencies SET is_default = 0, updated_at = NOW() WHERE id <> ?', [$id]);
                    Database::execute('UPDATE currencies SET is_default = 1 WHERE id = ?', [$id]);
                    set_setting('general', 'currency_code', $code);
                    set_setting('general', 'currency_symbol', $symbol);
                }
                if (money_cmp((string)$existing['exchange_rate'], $rate, 8) !== 0) {
                    Database::insert('INSERT INTO currency_rate_history (currency_id, rate, source, created_at) VALUES (?, ?, ?, NOW())', [$id, $rate, 'manual']);
                }
                Logger::adminActivity((int)$currentAdmin['id'], 'currency.update', 'currency', $id, "Updated currency {$code}");
                Session::setFlash('success', "Currency {$code} updated.");
            } else {
                $newId = Database::insert(
                    'INSERT INTO currencies (code, name, symbol, decimals, symbol_position, exchange_rate, is_enabled, is_default, rate_source, rate_updated_at, sort_order, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, NOW(), ?, NOW(), NOW())',
                    [$code, $name, $symbol, $decimals, $position, $rate, $enabled, 'manual', $sortOrder]
                );
                Database::insert('INSERT INTO currency_rate_history (currency_id, rate, source, created_at) VALUES (?, ?, ?, NOW())', [$newId, $rate, 'manual']);
                Logger::adminActivity((int)$currentAdmin['id'], 'currency.create', 'currency', $newId, "Created currency {$code}");
                Session::setFlash('success', "Currency {$code} created.");
            }
            Currency::clearCache();
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $row = Database::fetch('SELECT * FROM currencies WHERE id = ? LIMIT 1', [$id]);
        if (!$row) {
            Session::setFlash('error', 'Currency not found.');
        } elseif ((int)$row['is_default'] === 1) {
            Session::setFlash('error', 'The base currency cannot be disabled.');
        } else {
            $new = (int)$row['is_enabled'] === 1 ? 0 : 1;
            Database::execute('UPDATE currencies SET is_enabled = ?, updated_at = NOW() WHERE id = ?', [$new, $id]);
            Logger::adminActivity((int)$currentAdmin['id'], 'currency.toggle', 'currency', $id, "Currency {$row['code']} " . ($new ? 'enabled' : 'disabled'));
            Session::setFlash('success', "Currency {$row['code']} " . ($new ? 'enabled' : 'disabled') . '.');
            Currency::clearCache();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $row = Database::fetch('SELECT * FROM currencies WHERE id = ? LIMIT 1', [$id]);
        if (!$row) {
            Session::setFlash('error', 'Currency not found.');
        } elseif ((int)$row['is_default'] === 1) {
            Session::setFlash('error', 'The base currency cannot be deleted.');
        } else {
            $code = (string)$row['code'];
            $used = (int)Database::fetchColumn('SELECT COUNT(*) FROM wallets WHERE currency = ?', [$code])
                + (int)Database::fetchColumn('SELECT COUNT(*) FROM users WHERE preferred_currency = ?', [$code])
                + (int)Database::fetchColumn('SELECT COUNT(*) FROM payments WHERE currency = ?', [$code]);
            if ($used > 0) {
                Session::setFlash('error', "Currency {$code} is referenced by existing wallets or payments. Disable it instead.");
            } else {
                Database::execute('DELETE FROM currencies WHERE id = ?', [$id]);
                Logger::adminActivity((int)$currentAdmin['id'], 'currency.delete', 'currency', $id, "Deleted currency {$code}");
                Session::setFlash('success', "Currency {$code} deleted.");
                Currency::clearCache();
            }
        }
    } elseif ($action === 'refresh_rates') {
        $res = CurrencyRateProvider::updateAll();
        Session::setFlash($res['success'] ? 'success' : 'error', $res['message']);
    } elseif ($action === 'test_connection') {
        $res = CurrencyRateProvider::testConnection();
        Session::setFlash($res['success'] ? 'success' : 'error', $res['success'] ? (string)$res['message'] : (string)($res['error'] ?? 'Connection failed.'));
    } elseif ($action === 'save_rate_settings') {
        set_setting('currency', 'currency_auto_update', !empty($_POST['currency_auto_update']) ? '1' : '0');
        $provider = Validator::cleanString($_POST['currency_rate_provider'] ?? 'open.er-api.com', 60);
        set_setting('currency', 'currency_rate_provider', $provider !== '' ? $provider : 'open.er-api.com');
        $apiUrl = trim((string)($_POST['currency_rate_api_url'] ?? ''));
        $probe = str_replace('{base}', 'USD', $apiUrl);
        if ($apiUrl !== '' && !Validator::isUrl($probe)) {
            Session::setFlash('error', 'Rate API URL must be a valid http(s) URL and may contain {base} / {api_key} placeholders.');
        } else {
            set_setting('currency', 'currency_rate_api_url', $apiUrl);
            if (!empty($_POST['currency_rate_api_key'])) {
                set_setting('currency', 'currency_rate_api_key_encrypted', crypto_encrypt((string)$_POST['currency_rate_api_key']));
            }
            Logger::adminActivity((int)$currentAdmin['id'], 'currency.settings', 'settings', null, 'Updated exchange-rate provider settings');
            Session::setFlash('success', 'Exchange-rate provider settings saved.');
        }
    }

    Response::redirect('/admin/currencies.php' . (!empty($_POST['edit_id']) ? '?edit=' . (int)$_POST['edit_id'] : ''));
}

$edit = null;
if (!empty($_GET['edit'])) {
    $edit = Database::fetch('SELECT * FROM currencies WHERE id = ? LIMIT 1', [(int)$_GET['edit']]);
}

$currencies = Currency::all(false);
$history = Database::fetchAll(
    'SELECT h.*, c.code FROM currency_rate_history h
     INNER JOIN currencies c ON c.id = h.currency_id
     ORDER BY h.id DESC LIMIT 20'
);
$lastSyncAt = (string)get_setting('currency_last_sync_at', '');
$lastSyncStatus = (string)get_setting('currency_last_sync_status', '');

Theme::render('admin/currencies', [
    'edit' => $edit,
    'currencies' => $currencies,
    'history' => $history,
    'lastSyncAt' => $lastSyncAt,
    'lastSyncStatus' => $lastSyncStatus,
], 'admin', [
    'pageTitle' => 'Currencies & Exchange Rates',
    'currentAdmin' => $currentAdmin,
]);
