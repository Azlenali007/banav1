<?php
declare(strict_types=1);
$currencies = Currency::all(true);
$activeCode = Currency::displayCode();
$redirect = (string)($_SERVER['REQUEST_URI'] ?? '/user/dashboard.php');
if (!preg_match('#^/user/[A-Za-z0-9_\-./?=&%]*$#', $redirect)) {
    $redirect = '/user/dashboard.php';
}
?>
<?php if (count($currencies) > 1): ?>
<form method="POST" action="<?= e(app_url('/user/currency.php')) ?>" class="flex items-center">
  <?= CSRF::field() ?>
  <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
  <label class="sr-only" for="currency-switcher">Display currency</label>
  <select id="currency-switcher" name="currency" onchange="this.form.submit()"
          class="text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-700 px-2.5 py-1.5 focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
    <?php foreach ($currencies as $cur): ?>
      <option value="<?= e($cur['code']) ?>" <?= strtoupper((string)$cur['code']) === $activeCode ? 'selected' : '' ?>>
        <?= e($cur['code']) ?><?= (string)$cur['symbol'] !== '' ? ' ' . e($cur['symbol']) : '' ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>
<?php endif; ?>
