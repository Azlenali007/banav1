<?php
declare(strict_types=1);
/**
 * Shared Pagination Bar
 * Expects:
 * - $pagination: array from paginate_build()
 * - $baseQuery: associative array of query parameters to preserve
 * - $itemLabel: optional label (e.g. 'orders', 'users', 'records')
 */
$pagination = $pagination ?? null;
if (!is_array($pagination) || ($pagination['total_pages'] ?? 1) <= 1) {
    return;
}
$baseQuery = is_array($baseQuery ?? null) ? $baseQuery : [];
$itemLabel = (string)($itemLabel ?? 'records');
$current = (int)$pagination['current_page'];
$totalPages = (int)$pagination['total_pages'];
$totalItems = (int)$pagination['total'];

$buildUrl = static function (int $targetPage) use ($baseQuery): string {
    $params = $baseQuery;
    $params['page'] = $targetPage;
    return '?' . http_build_query($params);
};

$startPage = max(1, $current - 2);
$endPage = min($totalPages, $current + 2);
?>
<div class="px-5 py-3.5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500">
  <span>Page <strong><?= $current ?></strong> of <strong><?= $totalPages ?></strong> (<?= number_format($totalItems) ?> <?= e($itemLabel) ?>)</span>
  <div class="flex flex-wrap items-center gap-1.5">
    <?php if ($current > 1): ?>
      <a href="<?= e($buildUrl($current - 1)) ?>" class="btn-secondary px-3 py-1">Prev</a>
    <?php endif; ?>

    <?php if ($startPage > 1): ?>
      <a href="<?= e($buildUrl(1)) ?>" class="btn-secondary px-2.5 py-1">1</a>
      <?php if ($startPage > 2): ?><span class="px-1 text-slate-400">&hellip;</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
      <a href="<?= e($buildUrl($p)) ?>"
         class="px-2.5 py-1 rounded-lg font-semibold border transition <?= $p === $current ? 'bg-violet-600 text-white border-violet-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' ?>">
        <?= $p ?>
      </a>
    <?php endfor; ?>

    <?php if ($endPage < $totalPages): ?>
      <?php if ($endPage < $totalPages - 1): ?><span class="px-1 text-slate-400">&hellip;</span><?php endif; ?>
      <a href="<?= e($buildUrl($totalPages)) ?>" class="btn-secondary px-2.5 py-1"><?= $totalPages ?></a>
    <?php endif; ?>

    <?php if ($current < $totalPages): ?>
      <a href="<?= e($buildUrl($current + 1)) ?>" class="btn-secondary px-3 py-1">Next</a>
    <?php endif; ?>
  </div>
</div>
