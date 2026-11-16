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
                    'UPDATE services SET category_id=?, name=?, description=?, rate_per_1000=?, min_qty=?, max_qty=?, refill_supported=?, cancel_supported=?, average_time=?, sort_order=?, status=?, archived_at=NULL, updated_at=NOW() WHERE id=?',
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
        if ($id > 0) {
            if ($ordCount > 0) {
                Database::execute('UPDATE services SET status = ?, archived_at = NOW(), updated_at = NOW() WHERE id = ?', ['inactive', $id]);
                Logger::adminActivity((int)$currentAdmin['id'], 'service.archive', 'service', $id, "Archived service (referenced by {$ordCount} orders)");
                Session::setFlash('success', 'Service has existing orders, so it was archived and deactivated instead of deleted.');
            } else {
                Database::execute('DELETE FROM provider_service_mapping WHERE service_id = ?', [$id]);
                Database::execute('DELETE FROM services WHERE id = ?', [$id]);
                Logger::adminActivity((int)$currentAdmin['id'], 'service.delete', 'service', $id, 'Deleted service');
                Session::setFlash('success', 'Service deleted.');
            }
        }
    } elseif ($action === 'bulk') {
        $bulkAction = (string)($_POST['bulk_action'] ?? '');
        $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['service_ids'] ?? [])), static fn(int $v): bool => $v > 0)));
        if (empty($ids)) {
            Session::setFlash('error', 'Select at least one service for the bulk action.');
            Response::redirect('/admin/services.php');
        }

        $place = implode(',', array_fill(0, count($ids), '?'));
        $affected = 0;
        $archived = 0;
        $skipped = 0;

        if ($bulkAction === 'activate' || $bulkAction === 'deactivate') {
            $status = $bulkAction === 'activate' ? 'active' : 'inactive';
            Database::execute(
                "UPDATE services SET status = ?, archived_at = NULL, updated_at = NOW() WHERE id IN ({$place})",
                array_merge([$status], $ids)
            );
            $affected = count($ids);
        } elseif ($bulkAction === 'delete') {
            foreach ($ids as $id) {
                $ordCount = (int)Database::fetchColumn('SELECT COUNT(*) FROM orders WHERE service_id = ?', [$id]);
                if ($ordCount > 0) {
                    Database::execute('UPDATE services SET status = ?, archived_at = NOW(), updated_at = NOW() WHERE id = ?', ['inactive', $id]);
                    $archived++;
                } else {
                    Database::execute('DELETE FROM provider_service_mapping WHERE service_id = ?', [$id]);
                    $deleted = Database::execute('DELETE FROM services WHERE id = ?', [$id]);
                    if ($deleted) {
                        $affected++;
                    }
                }
            }
        } elseif ($bulkAction === 'set_category') {
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $catExists = $categoryId > 0 ? Database::fetchColumn('SELECT id FROM categories WHERE id = ? LIMIT 1', [$categoryId]) : null;
            if (!$catExists) {
                Session::setFlash('error', 'Choose a valid destination category.');
                Response::redirect('/admin/services.php');
            }
            Database::execute(
                "UPDATE services SET category_id = ?, updated_at = NOW() WHERE id IN ({$place})",
                array_merge([$categoryId], $ids)
            );
            $affected = count($ids);
        } elseif (in_array($bulkAction, ['set_rate', 'increase_percent', 'decrease_percent', 'increase_fixed', 'decrease_fixed'], true)) {
            $value = Validator::parseDecimal($_POST['amount_value'] ?? '', 4);
            if ($value === null || money_cmp($value, '0', 4) <= 0) {
                Session::setFlash('error', 'Enter a positive numeric value for the bulk price change.');
                Response::redirect('/admin/services.php');
            }
            $rows = Database::fetchAll("SELECT id, rate_per_1000 FROM services WHERE id IN ({$place})", $ids);
            foreach ($rows as $row) {
                $rate = money_format_dec((string)$row['rate_per_1000'], 4);
                $newRate = match ($bulkAction) {
                    'set_rate' => money_format_dec($value, 4),
                    'increase_percent' => money_add($rate, money_div(money_mul($rate, $value, 8), '100', 4), 4),
                    'decrease_percent' => money_sub($rate, money_div(money_mul($rate, $value, 8), '100', 4), 4),
                    'increase_fixed' => money_add($rate, $value, 4),
                    'decrease_fixed' => money_sub($rate, $value, 4),
                    default => $rate,
                };
                if (money_cmp($newRate, '0.0001', 4) < 0) {
                    $newRate = '0.0001';
                }
                Database::execute('UPDATE services SET rate_per_1000 = ?, updated_at = NOW() WHERE id = ?', [$newRate, (int)$row['id']]);
                $affected++;
            }
        } elseif ($bulkAction === 'set_markup') {
            $markupType = ($_POST['markup_type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
            $markupValue = Validator::parseDecimal($_POST['markup_value'] ?? '', 4);
            if ($markupValue === null) {
                Session::setFlash('error', 'Enter a valid markup value.');
                Response::redirect('/admin/services.php');
            }
            $rows = Database::fetchAll(
                "SELECT psm.id AS mapping_id, psm.provider_rate, s.id AS service_id
                 FROM provider_service_mapping psm
                 INNER JOIN services s ON s.id = psm.service_id
                 WHERE s.id IN ({$place})",
                $ids
            );
            foreach ($rows as $row) {
                $providerRate = money_format_dec((string)$row['provider_rate'], 4);
                $sellRate = $markupType === 'percent'
                    ? money_add($providerRate, money_div(money_mul($providerRate, $markupValue, 8), '100', 4), 4)
                    : money_add($providerRate, $markupValue, 4);
                if (money_cmp($sellRate, '0.0001', 4) < 0) {
                    $sellRate = '0.0001';
                }
                Database::execute(
                    'UPDATE provider_service_mapping SET markup_type = ?, markup_value = ?, updated_at = NOW() WHERE id = ?',
                    [$markupType, $markupValue, (int)$row['mapping_id']]
                );
                Database::execute('UPDATE services SET rate_per_1000 = ?, updated_at = NOW() WHERE id = ?', [$sellRate, (int)$row['service_id']]);
                $affected++;
            }
            $skipped = count($ids) - $affected;
        } else {
            Session::setFlash('error', 'Unknown bulk action.');
            Response::redirect('/admin/services.php');
        }

        $summary = "Bulk {$bulkAction}: {$affected} service(s) updated";
        if ($archived > 0) {
            $summary .= ", {$archived} archived because orders reference them";
        }
        if ($skipped > 0) {
            $summary .= ", {$skipped} skipped";
        }
        Logger::adminActivity(
            (int)$currentAdmin['id'],
            'service.bulk.' . $bulkAction,
            'service',
            null,
            $summary . '. IDs: ' . implode(',', array_slice($ids, 0, 100))
        );
        Session::setFlash('success', $summary . '.');
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
$showArchived = (int)($_GET['archived'] ?? 0) === 1;

$where = [$showArchived ? 's.archived_at IS NOT NULL' : 's.archived_at IS NULL'];
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
    'showArchived' => $showArchived,
], 'admin', [
    'pageTitle' => 'Services',
    'currentAdmin' => $currentAdmin,
]);
