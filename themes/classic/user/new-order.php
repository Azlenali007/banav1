<?php
declare(strict_types=1);
/** @var array $currentUser @var array $categories @var array $services @var int $preselectServiceId */
/** @var int $initialQuantity @var string $linkValue @var string $formIdemKey @var string $currencySymbol @var string $error */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">New Order</h1>
  <p class="text-sm text-slate-500">Select a category and service, review limits, and submit your order.</p>
</div>

<?php if ($error !== ''): ?>
  <div class="mb-5 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"><?= e($error) ?></div>
<?php endif; ?>

<?php if (empty($services)): ?>
  <div class="smm-card p-8 text-center text-sm text-slate-500">No active services are available right now. Please check back later.</div>
<?php else: ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
     x-data="newOrderForm(<?= e(json_encode($categories)) ?>, <?= e(json_encode($services)) ?>, <?= (int)$preselectServiceId ?>, '<?= e($currencySymbol) ?>', <?= (int)$initialQuantity ?>)">
  <div class="lg:col-span-2 smm-card p-6">
    <form method="POST" class="space-y-5" @submit="submitting = true">
      <?= CSRF::field() ?>
      <input type="hidden" name="idempotency_key" value="<?= e($formIdemKey) ?>">

      <div>
        <label class="smm-label">Quick Search Service (ID or Keyword)</label>
        <input type="text" x-model="searchQuery" @input="filterBySearch()" class="smm-input" placeholder="Search e.g. Instagram, 104, Likes...">
      </div>

      <div>
        <label class="smm-label">Category</label>
        <select x-model.number="selectedCategoryId" @change="onCategoryChange()" class="smm-input">
          <template x-for="cat in categories" :key="cat.id">
            <option :value="cat.id" x-text="cat.name"></option>
          </template>
        </select>
      </div>

      <div>
        <label class="smm-label">Service</label>
        <select name="service_id" x-model.number="selectedServiceId" @change="onServiceChange()" required class="smm-input">
          <template x-for="svc in filteredServices" :key="svc.id">
            <option :value="svc.id" x-text="'#' + svc.id + ' — ' + svc.name + ' (' + currency + formatRate(svc.effective_rate) + '/1K)'"></option>
          </template>
        </select>
      </div>

      <div>
        <label class="smm-label">Target Link</label>
        <input type="url" name="link" value="<?= e($linkValue) ?>" required class="smm-input" placeholder="https://...">
        <p class="text-[11px] text-slate-400 mt-1">Ensure the target profile/post is public and starts with https:// or http://.</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="smm-label">Quantity</label>
          <input type="number" name="quantity" x-model.number="quantity" :min="activeService ? activeService.min_qty : 1" :max="activeService ? activeService.max_qty : 1000000" required class="smm-input">
          <p class="text-[11px] text-slate-500 mt-1" x-show="activeService">
            Min: <strong x-text="activeService?.min_qty"></strong> &bull; Max: <strong x-text="activeService?.max_qty"></strong>
          </p>
        </div>
        <div>
          <label class="smm-label">Estimated Charge</label>
          <div class="smm-input bg-violet-50 border-violet-200 font-extrabold text-violet-900 flex items-center justify-between">
            <span x-text="currency + estimatedCharge"></span>
            <span class="text-xs font-normal text-violet-600">Server-verified</span>
          </div>
          <p class="text-[11px] text-slate-500 mt-1">Wallet Balance: <?= e(format_money($currentUser['wallet_balance'] ?? 0)) ?></p>
        </div>
      </div>

      <button type="submit" :disabled="submitting || !activeService" class="btn-primary w-full py-3 text-sm disabled:opacity-60">
        <span x-show="!submitting">Confirm &amp; Place Order</span>
        <span x-show="submitting" x-cloak class="inline-flex items-center justify-center">
          <?php Theme::component('shared/loading', ['label' => 'Processing Order...']); ?>
        </span>
      </button>
    </form>
  </div>

  <!-- Service Details Card -->
  <div class="smm-card p-6 h-fit space-y-4">
    <div class="flex items-center justify-between">
      <span class="text-xs font-bold uppercase tracking-wider text-violet-700">Service Details</span>
      <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded-full bg-violet-100 text-violet-800" x-text="activeService ? 'ID #' + activeService.id : '—'"></span>
    </div>

    <template x-if="activeService">
      <div class="space-y-4 text-sm">
        <div>
          <div class="font-bold text-slate-900 text-base" x-text="activeService.name"></div>
          <p class="text-xs text-slate-600 mt-2 leading-relaxed whitespace-pre-line" x-text="activeService.description || 'Standard automated service delivery.'"></p>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100 text-xs">
          <div class="p-3 rounded-xl bg-slate-50">
            <div class="text-slate-400">Rate per 1,000</div>
            <div class="font-bold text-slate-900 mt-0.5" x-text="currency + formatRate(activeService.effective_rate)"></div>
          </div>
          <div class="p-3 rounded-xl bg-slate-50">
            <div class="text-slate-400">Average Time</div>
            <div class="font-bold text-slate-900 mt-0.5" x-text="activeService.average_time || 'Variable'"></div>
          </div>
          <div class="p-3 rounded-xl bg-slate-50">
            <div class="text-slate-400">Minimum Qty</div>
            <div class="font-bold text-slate-900 mt-0.5" x-text="activeService.min_qty"></div>
          </div>
          <div class="p-3 rounded-xl bg-slate-50">
            <div class="text-slate-400">Maximum Qty</div>
            <div class="font-bold text-slate-900 mt-0.5" x-text="activeService.max_qty"></div>
          </div>
        </div>
        <div class="flex flex-wrap gap-2 pt-1">
          <span x-show="Number(activeService.refill_supported) === 1" class="badge badge-success">Refill Supported</span>
          <span x-show="Number(activeService.cancel_supported) === 1" class="badge badge-info">Cancel Supported</span>
        </div>
      </div>
    </template>
  </div>
</div>
<?php endif; ?>
