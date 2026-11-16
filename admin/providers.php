<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('providers.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = Validator::cleanString($_POST['name'] ?? '', 120);
        $apiUrl = trim((string)($_POST['api_url'] ?? ''));
        $apiKey = trim((string)($_POST['api_key'] ?? ''));
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $notes = Validator::cleanString($_POST['notes'] ?? '', 500);

        if ($name === '' || !Validator::isUrl($apiUrl)) {
            Session::setFlash('error', 'Valid provider name and HTTPS/HTTP API URL are required.');
        } else {
            if ($id > 0) {
                if ($apiKey !== '') {
                    Database::execute(
                        'UPDATE providers SET name=?, api_url=?, api_key_encrypted=?, status=?, notes=?, updated_at=NOW() WHERE id=?',
                        [$name, $apiUrl, crypto_encrypt($apiKey), $status, $notes, $id]
                    );
                } else {
                    Database::execute(
                        'UPDATE providers SET name=?, api_url=?, status=?, notes=?, updated_at=NOW() WHERE id=?',
                        [$name, $apiUrl, $status, $notes, $id]
                    );
                }
                Logger::adminActivity((int)$currentAdmin['id'], 'provider.update', 'provider', $id, "Updated provider {$name}");
                Session::setFlash('success', 'Provider updated.');
            } else {
                if ($apiKey === '') {
                    Session::setFlash('error', 'API Key is required when creating a new provider.');
                } else {
                    $newId = Database::insert(
                        'INSERT INTO providers (name, api_url, api_key_encrypted, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
                        [$name, $apiUrl, crypto_encrypt($apiKey), $status, $notes]
                    );
                    Logger::adminActivity((int)$currentAdmin['id'], 'provider.create', 'provider', $newId, "Added provider {$name}");
                    Session::setFlash('success', 'Provider added. You can now test connection or import services.');
                }
            }
        }
    } elseif ($action === 'test_balance') {
        $id = (int)($_POST['id'] ?? 0);
        $res = Provider::syncBalance($id);
        if ($res['success']) {
            Session::setFlash('success', "Connection OK! Provider Balance: {$res['balance']} {$res['currency']}");
        } else {
            Session::setFlash('error', 'Connection failed: ' . ($res['error'] ?? 'Unknown error'));
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $mapCount = (int)Database::fetchColumn('SELECT COUNT(*) FROM provider_service_mapping WHERE provider_id = ?', [$id]);
        if ($mapCount > 0) {
            Session::setFlash('error', "Cannot delete provider: {$mapCount} services are mapped to it. Disable it or unmap services first.");
        } else {
            Database::execute('DELETE FROM providers WHERE id = ?', [$id]);
            Logger::adminActivity((int)$currentAdmin['id'], 'provider.delete', 'provider', $id, 'Deleted provider');
            Session::setFlash('success', 'Provider deleted.');
        }
    }
    Response::redirect('/admin/providers.php');
}

$editProv = null;
if (!empty($_GET['edit'])) {
    $editProv = Database::fetch('SELECT id, name, api_url, status, notes FROM providers WHERE id = ? LIMIT 1', [(int)$_GET['edit']]);
}

$providers = Database::fetchAll(
    'SELECT p.id, p.name, p.api_url, p.balance, p.currency, p.last_balance_sync, p.status, p.notes,
            (SELECT COUNT(*) FROM provider_service_mapping m WHERE m.provider_id = p.id) AS mapped_count
     FROM providers p ORDER BY p.id DESC'
);

Theme::render('admin/providers', [
    'editProv' => $editProv,
    'providers' => $providers,
], 'admin', [
    'pageTitle' => 'SMM Providers',
    'currentAdmin' => $currentAdmin,
]);
