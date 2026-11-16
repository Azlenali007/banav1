<?php
declare(strict_types=1);
$flashes = Session::getFlashes();
if (!empty($flashes)):
?>
<div class="space-y-3 mb-5">
  <?php foreach ($flashes as $f): ?>
    <?php
    $type = $f['type'] ?? 'info';
    $cls = match ($type) {
        'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
        default => 'bg-violet-50 border-violet-200 text-violet-800',
    };
    ?>
    <div x-data="{ show: true }" x-show="show" class="flex items-start justify-between gap-3 px-4 py-3 rounded-xl border text-sm font-medium <?= $cls ?> animate-fade-in">
      <span><?= e($f['message'] ?? '') ?></span>
      <button type="button" @click="show = false" class="opacity-60 hover:opacity-100 text-base leading-none" aria-label="Dismiss">&times;</button>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
