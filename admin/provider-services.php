<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('providers.manage');

$providerId = (int)($_GET['provider_id'] ?? $_POST['provider_id'] ?? 0);
$provider = Provider::getById($providerId);
if (!$provider) {
    Response::abort(404, 'Provider Not Found', 'Select a valid provider to import services.');
}

$categories = Database::fetchAll('SELECT id, name FROM categories ORDER BY sort_order ASC, id ASC');
$remoteServices = [];
$fetchError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_selected') {
    CSRF::verifyOrAbort();
    $targetCatId = (int)($_POST['category_id'] ?? 0);
    $markupType = ($_POST['markup_type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
    $markupValue = Validator::parseDecimal($_POST['markup_value'] ?? '25', 4) ?? '25.0000';
    $selected = array_values(array_unique(array_map('strval', (array)($_POST['selected_services'] ?? []))));

    if ($targetCatId <= 0 || empty($selected)) {
        Session::setFlash('error', 'Select a destination category and at least one provider service.');
        Response::redirect('/admin/provider-services.php?provider_id=' . $providerId);
    }

    $payloadList = json_decode((string)($_POST['services_payload'] ?? '[]'), true);
    $byPid = [];
    if (is_array($payloadList)) {
        foreach ($payloadList as $item) {
            if (isset($item['service'])) {
                $byPid[(string)$item['service']] = $item;
            }
        }
    }

    $pdo = Database::getInstance();
    $pdo->beginTransaction();
    $imported = 0;
    $skipped = 0;
    $failed = 0;
    $errors = [];

    foreach ($selected as $pidStr) {
        if (!isset($byPid[$pidStr])) {
            $skipped++;
            continue;
        }

        $existing = Database::fetch(
            'SELECT psm.service_id FROM provider_service_mapping psm WHERE psm.provider_id = ? AND psm.provider_service_id = ? LIMIT 1',
            [$providerId, $pidStr]
        );
        if ($existing) {
            $skipped++;
            continue;
        }

        $item = $byPid[$pidStr];
        $pRate = Validator::parseDecimal($item['rate'] ?? '0', 4) ?? '0.0000';
        $sellRate = $markupType === 'percent'
            ? money_add($pRate, money_div(money_mul($pRate, $markupValue, 8), '100', 4), 4)
            : money_add($pRate, $markupValue, 4);
        if (money_cmp($sellRate, '0.0001') <= 0) {
            $sellRate = '1.0000';
        }

        try {
            $svcId = Database::insert(
                'INSERT INTO services (category_id, name, description, service_type, rate_per_1000, min_qty, max_qty, refill_supported, cancel_supported, dripfeed_supported, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [
                    $targetCatId,
                    Validator::cleanString($item['name'] ?? ('Service #' . $pidStr), 200),
                    Validator::cleanString($item['desc'] ?? ($item['category'] ?? ''), 1000),
                    Validator::cleanString($item['type'] ?? 'default', 30),
                    $sellRate,
                    max(1, (int)($item['min'] ?? 10)),
                    max(10, (int)($item['max'] ?? 100000)),
                    !empty($item['refill']) ? 1 : 0,
                    !empty($item['cancel']) ? 1 : 0,
                    !empty($item['dripfeed']) ? 1 : 0,
                    'active',
                ]
            );
            Database::insert(
                'INSERT INTO provider_service_mapping (service_id, provider_id, provider_service_id, provider_rate, markup_type, markup_value, last_synced_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())',
                [$svcId, $providerId, $pidStr, $pRate, $markupType, $markupValue]
            );
            $imported++;
        } catch (Throwable $e) {
            $failed++;
            $errors[] = 'Service ' . $pidStr . ': ' . $e->getMessage();
            Logger::error('provider.import_services', $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    $pdo->commit();

    $summary = "Imported {$imported} service(s). Skipped {$skipped} duplicate/invalid selection(s).";
    if ($failed > 0) {
        $summary .= " {$failed} failed.";
        Logger::file('provider', 'WARN', 'Provider bulk import had failures', ['provider_id' => $providerId, 'errors' => array_slice($errors, 0, 10)]);
    }
    Logger::adminActivity(
        (int)$currentAdmin['id'],
        'provider.import_services',
        'provider',
        $providerId,
        "Bulk import: imported={$imported}, skipped={$skipped}, failed={$failed}, category=#{$targetCatId}"
    );

    Session::setFlash($failed > 0 ? 'warning' : 'success', $summary);
    Response::redirect('/admin/services.php?category=' . $targetCatId);
}

$res = Provider::fetchServices($providerId);
if ($res['success']) {
    $remoteServices = $res['services'];
} else {
    $fetchError = (string)$res['error'];
}

$existingIds = [];
foreach (Database::fetchAll('SELECT provider_service_id FROM provider_service_mapping WHERE provider_id = ?', [$providerId]) as $row) {
    $existingIds[(string)$row['provider_service_id']] = true;
}

Theme::render('admin/provider-services', [
    'providerId' => $providerId,
    'provider' => $provider,
    'categories' => $categories,
    'remoteServices' => $remoteServices,
    'existingIds' => $existingIds,
    'fetchError' => $fetchError,
], 'admin', [
    'pageTitle' => 'Import Services — ' . $provider['name'],
    'currentAdmin' => $currentAdmin,
]);
