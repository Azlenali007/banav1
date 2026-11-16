<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('services.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = Validator::cleanString($_POST['name'] ?? '', 200);
        $desc = trim((string)($_POST['description'] ?? ''));
        $rate = Validator::parseDecimal($_POST['rate_per_1000'] ?? '0', 4);
        $minQty = max(1, (int)($_POST['min_qty'] ?? 10));
        $maxQty = max($minQty, (int)($_POST['max_qty'] ?? 100000));
        $avgTime = Validator::cleanString($_POST['average_time'] ?? '', 60);
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $refill = !empty($_POST['refill_supported']) ? 1 : 0;
        $cancel = !empty($_POST['cancel_supported']) ? 1 : 0;

        $providerId = (int)($_POST['provider_id'] ?? 0);
        $providerSvcId = Validator::cleanString($_POST['provider_service_id'] ?? '', 64);
        $providerRate = Validator::parseDecimal($_POST['provider_rate'] ?? '0', 4) ?? '0.0000';

        if ($categoryId <= 0 || $name === '' || $rate === null || money_cmp($rate, '0.0000') <= 0) {
            Session::setFlash('error', 'Valid category, service name, and positive rate per 1,000 are required.');
        } else {
            if ($id > 0) {
                Database::execute(
                    'UPDATE services SET category_id=?, name=?, description=?, rate_per_1000=?, min_qty=?, max_qty=?, refill_supported=?, cancel_supported=?, average_time=?, sort_order=?, status=?, updated_at=NOW() WHERE id=?',
                    [$categoryId, $name, $desc, $rate, $minQty, $maxQty, $refill, $cancel, $avgTime, $sortOrder, $status, $id]
                );
                $serviceId = $id;
                Logger::adminActivity((int)$currentAdmin['id'], 'service.update', 'service', $serviceId, "Updated service {$name}");
            } else {
                $serviceId = Database::insert(
                    'INSERT INTO services (category_id, name, description, rate_per_1000, min_qty, max_qty, refill_supported, cancel_supported, average_time, sort_order, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                    [$categoryId, $name, $desc, $rate, $minQty, $maxQty, $refill, $cancel, $avgTime, $sortOrder, $status]
                );
                Logger::adminActivity((int)$currentAdmin['id'], 'service.create', 'service', $serviceId, "Created service {$name}");
            }

            if ($providerId > 0 && $providerSvcId !== '') {
                Database::execute(
                    'INSERT INTO provider_service_mapping (service_id, provider_id, provider_service_id, provider_rate, updated_at)
                     VALUES (?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE provider_id = VALUES(provider_id), provider_service_id = VALUES(provider_service_id), provider_rate = VALUES(provider_rate), updated_at = NOW()',
                    [$serviceId, $providerId, $providerSvcId, $providerRate]
                );
            } else {
                Database::execute('DELETE FROM provider_service_mapping WHERE service_id = ?', [$serviceId]);
            }
            Session::setFlash('success', 'Service saved successfully.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $ordCount = (int)Database::fetchColumn('SELECT COUNT(*) FROM orders WHERE service_id = ?', [$id]);
        if ($ordCount > 0) {
            Session::setFlash('error', "Cannot delete service: {$ordCount} orders reference it. Disable it instead.");
        } else {
            Database::execute('DELETE FROM services WHERE id = ?', [$id]);
            Logger::adminActivity((int)$currentAdmin['id'], 'service.delete', 'service', $id, 'Deleted service');
            Session::setFlash('success', 'Service deleted.');
        }
    }
    Response::redirect('/admin/services.php');
}

$editSvc = null;
if (!empty($_GET['edit'])) {
    $editSvc = Database::fetch(
        'SELECT s.*, psm.provider_id, psm.provider_service_id, psm.provider_rate
         FROM services s
         LEFT JOIN provider_service_mapping psm ON psm.service_id = s.id
         WHERE s.id = ? LIMIT 1',
        [(int)$_GET['edit']]
    );
}

$q = trim((string)($_GET['q'] ?? ''));
$catFilter = (int)($_GET['category'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];
if ($catFilter > 0) {
    $where[] = 's.category_id = ?';
    $params[] = $catFilter;
}
if ($q !== '') {
    $where[] = '(s.name LIKE ? OR s.id = ?)';
    $params[] = "%{$q}%";
    $params[] = ctype_digit($q) ? (int)$q : 0;
}
$whereSql = implode(' AND ', $where);

$total = (int)Database::fetchColumn("SELECT COUNT(*) FROM services s WHERE {$whereSql}", $params);
$pg = paginate_build($total, $page, 25);

$categories = Database::fetchAll('SELECT id, name FROM categories ORDER BY sort_order ASC, id ASC');
$providers = Database::fetchAll('SELECT id, name FROM providers ORDER BY name ASC');
$services = Database::fetchAll(
    "SELECT s.*, c.name AS category_name, p.name AS provider_name, psm.provider_service_id
     FROM services s
     INNER JOIN categories c ON c.id = s.category_id
     LEFT JOIN provider_service_mapping psm ON psm.service_id = s.id
     LEFT JOIN providers p ON p.id = psm.provider_id
     WHERE {$whereSql}
     ORDER BY c.sort_order ASC, s.sort_order ASC, s.id ASC
     LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
    $params
);

Theme::render('admin/services', [
    'editSvc' => $editSvc,
    'categories' => $categories,
    'providers' => $providers,
    'services' => $services,
    'pg' => $pg,
    'q' => $q,
    'catFilter' => $catFilter,
], 'admin', [
    'pageTitle' => 'Services',
    'currentAdmin' => $currentAdmin,
]);
