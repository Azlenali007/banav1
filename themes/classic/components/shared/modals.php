<?php
declare(strict_types=1);
?>
<!-- Reusable Confirmation Modal (replaces native browser confirm()) -->
<div x-data="{ open: false, title: '', message: '', actionUrl: '', confirmLabel: 'Confirm', fields: {}, csrfToken: '<?= e(CSRF::token()) ?>' }"
     @open-confirm-modal.window="open = true; title = $event.detail.title || 'Confirm Action'; message = $event.detail.message || ''; actionUrl = $event.detail.actionUrl || window.location.href; confirmLabel = $event.detail.confirmLabel || 'Confirm'; fields = $event.detail.fields || {}"
     @keydown.escape.window="open = false"
     x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
  <div @click.away="open = false" class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
    <h3 class="text-lg font-bold text-slate-900 mb-2" x-text="title"></h3>
    <p class="text-sm text-slate-600 mb-6" x-text="message"></p>
    <div class="flex justify-end gap-3">
      <button type="button" @click="open = false" class="btn-secondary px-4 py-2 text-sm">Cancel</button>
      <form :action="actionUrl" method="POST">
        <input type="hidden" name="csrf_token" :value="csrfToken">
        <template x-for="[k, v] in Object.entries(fields)" :key="k">
          <input type="hidden" :name="k" :value="v">
        </template>
        <button type="submit" class="btn-primary px-4 py-2 text-sm" x-text="confirmLabel"></button>
      </form>
    </div>
  </div>
</div>
