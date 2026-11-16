<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$currentAdmin = admin_require_permission('website.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verifyOrAbort();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $title = Validator::cleanString($_POST['title'] ?? '', 200);
        $slug = preg_replace('/[^a-z0-9\-_]+/', '-', strtolower(trim((string)($_POST['slug'] ?: $title))));
        $slug = trim((string)$slug, '-');
        $content = (string)($_POST['content'] ?? '');
        $metaTitle = Validator::cleanString($_POST['meta_title'] ?? '', 200);
        $metaDesc = Validator::cleanString($_POST['meta_description'] ?? '', 500);
        $published = !empty($_POST['is_published']) ? 1 : 0;

        if ($title === '' || $slug === '') {
            Session::setFlash('error', 'Title and slug are required.');
        } else {
            if ($id > 0) {
                Database::execute(
                    'UPDATE pages SET slug=?, title=?, content=?, meta_title=?, meta_description=?, is_published=?, updated_at=NOW() WHERE id=?',
                    [$slug, $title, $content, $metaTitle, $metaDesc, $published, $id]
                );
                Logger::adminActivity((int)$currentAdmin['id'], 'page.update', 'page', $id, "Updated page {$slug}");
            } else {
                $newId = Database::insert(
                    'INSERT INTO pages (slug, title, content, meta_title, meta_description, is_published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
                    [$slug, $title, $content, $metaTitle, $metaDesc, $published]
                );
                Logger::adminActivity((int)$currentAdmin['id'], 'page.create', 'page', $newId, "Created page {$slug}");
            }
            Session::setFlash('success', 'Static page saved.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        Database::execute('DELETE FROM pages WHERE id = ?', [$id]);
        Logger::adminActivity((int)$currentAdmin['id'], 'page.delete', 'page', $id, 'Deleted static page');
        Session::setFlash('success', 'Page deleted.');
    }
    Response::redirect('/admin/pages.php');
}

$editPage = !empty($_GET['edit']) ? Database::fetch('SELECT * FROM pages WHERE id = ? LIMIT 1', [(int)$_GET['edit']]) : null;
$pages = Database::fetchAll('SELECT * FROM pages ORDER BY id ASC');

Theme::render('admin/pages', [
    'editPage' => $editPage,
    'pages' => $pages,
], 'admin', [
    'pageTitle' => 'Static Pages',
    'currentAdmin' => $currentAdmin,
]);
