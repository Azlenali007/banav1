<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('categories.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = Validator::cleanString($_POST['name'] ?? '', 120);
        $slug = preg_replace('/[^a-z0-9\-]+/', '-', strtolower(trim((string)($_POST['slug'] ?: $name))));
        $slug = trim((string)$slug, '-') ?: 'cat-' . bin2hex(random_bytes(3));
        $desc = Validator::cleanString($_POST['description'] ?? '', 1000);
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($name === '') {
            Session::setFlash('error', 'Category name is required.');
        } else {
            if ($id > 0) {
                Database::execute(
                    'UPDATE categories SET name = ?, slug = ?, description = ?, sort_order = ?, status = ?, updated_at = NOW() WHERE id = ?',
                    [$name, $slug, $desc, $sortOrder, $status, $id]
                );
                Logger::adminActivity((int)$currentAdmin['id'], 'category.update', 'category', $id, "Updated category {$name}");
                Session::setFlash('success', 'Category updated.');
            } else {
                $newId = Database::insert(
                    'INSERT INTO categories (name, slug, description, sort_order, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
                    [$name, $slug, $desc, $sortOrder, $status]
                );
                Logger::adminActivity((int)$currentAdmin['id'], 'category.create', 'category', $newId, "Created category {$name}");
                Session::setFlash('success', 'Category created.');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $svcCount = (int)Database::fetchColumn('SELECT COUNT(*) FROM services WHERE category_id = ?', [$id]);
        if ($svcCount > 0) {
            Session::setFlash('error', "Cannot delete category: {$svcCount} services depend on it. Reassign or delete those services first.");
        } else {
            Database::execute('DELETE FROM categories WHERE id = ?', [$id]);
            Logger::adminActivity((int)$currentAdmin['id'], 'category.delete', 'category', $id, 'Deleted category');
            Session::setFlash('success', 'Category deleted.');
        }
    }
    Response::redirect('/admin/categories.php');
}

$editCat = null;
if (!empty($_GET['edit'])) {
    $editCat = Database::fetch('SELECT * FROM categories WHERE id = ? LIMIT 1', [(int)$_GET['edit']]);
}

$categories = Database::fetchAll(
    'SELECT c.*, (SELECT COUNT(*) FROM services s WHERE s.category_id = c.id) AS service_count FROM categories c ORDER BY c.sort_order ASC, c.id ASC'
);

Theme::render('admin/categories', [
    'editCat' => $editCat,
    'categories' => $categories,
], 'admin', [
    'pageTitle' => 'Categories',
    'currentAdmin' => $currentAdmin,
]);
