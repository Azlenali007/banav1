<?php
declare(strict_types=1);
/** @var array $frequencyOptions @var int $views */
$enabled = get_setting('login_popup_enabled', '0') === '1';
$frequency = (string)get_setting('login_popup_frequency', 'once_per_session');
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Post-login Popup</h1>
    <p class="text-sm text-slate-500">Prompt users to join your WhatsApp or Telegram channel after they sign in.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="badge <?= $enabled ? 'badge-success' : 'badge-neutral' ?>"><?= $enabled ? 'Enabled' : 'Disabled' ?></span>
    <form method="POST" onsubmit="return confirm('Clear popup view history for all users?');">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="reset_views">
      <button type="submit" class="btn-secondary px-4 py-2 text-sm">Reset View History</button>
    </form>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  <div class="xl:col-span-2 smm-card p-6">
    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="save">
      <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
        <input type="checkbox" name="enabled" value="1" <?= $enabled ? 'checked' : '' ?>> Show popup after login
      </label>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="smm-label">Title</label>
          <input type="text" name="title" value="<?= e(get_setting('login_popup_title', 'Join our community')) ?>" class="smm-input" maxlength="140">
        </div>
        <div class="sm:col-span-2">
          <label class="smm-label">Message</label>
          <textarea name="message" rows="3" class="smm-input"><?= e(get_setting('login_popup_message', '')) ?></textarea>
        </div>
        <div class="sm:col-span-2">
          <label class="smm-label">Image URL (optional)</label>
          <input type="text" name="image_url" value="<?= e(get_setting('login_popup_image_url', '')) ?>" class="smm-input" placeholder="https://example.com/banner.png">
        </div>
      </div>

      <div class="pt-3 border-t border-slate-100 space-y-3">
        <div class="text-xs font-bold uppercase text-violet-700">WhatsApp Button</div>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" name="whatsapp_enabled" value="1" <?= get_setting('login_popup_whatsapp_enabled', '1') === '1' ? 'checked' : '' ?>> Show WhatsApp button
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div><label class="smm-label">WhatsApp URL</label><input type="text" name="whatsapp_url" value="<?= e(get_setting('login_popup_whatsapp_url', '')) ?>" class="smm-input" placeholder="https://chat.whatsapp.com/..."></div>
          <div><label class="smm-label">Button Label</label><input type="text" name="whatsapp_label" value="<?= e(get_setting('login_popup_whatsapp_label', 'Join WhatsApp Channel')) ?>" class="smm-input" maxlength="80"></div>
        </div>
      </div>

      <div class="pt-3 border-t border-slate-100 space-y-3">
        <div class="text-xs font-bold uppercase text-violet-700">Telegram Button</div>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" name="telegram_enabled" value="1" <?= get_setting('login_popup_telegram_enabled', '1') === '1' ? 'checked' : '' ?>> Show Telegram button
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div><label class="smm-label">Telegram URL</label><input type="text" name="telegram_url" value="<?= e(get_setting('login_popup_telegram_url', '')) ?>" class="smm-input" placeholder="https://t.me/yourchannel"></div>
          <div><label class="smm-label">Button Label</label><input type="text" name="telegram_label" value="<?= e(get_setting('login_popup_telegram_label', 'Join Telegram Channel')) ?>" class="smm-input" maxlength="80"></div>
        </div>
      </div>

      <div class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="smm-label">Frequency</label>
          <select name="frequency" class="smm-input">
            <?php foreach ($frequencyOptions as $fv => $fl): ?>
              <option value="<?= e($fv) ?>" <?= $frequency === $fv ? 'selected' : '' ?>><?= e($fl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="smm-label">Interval (days)</label>
          <input type="number" min="1" max="365" name="frequency_days" value="<?= (int)get_setting('login_popup_frequency_days', '7') ?>" class="smm-input">
        </div>
        <div>
          <label class="smm-label">Delay (seconds)</label>
          <input type="number" min="0" max="60" name="delay_seconds" value="<?= (int)get_setting('login_popup_delay_seconds', '0') ?>" class="smm-input">
        </div>
      </div>

      <div class="flex items-center justify-between pt-2">
        <span class="text-[11px] text-slate-400"><?= $views ?> popup view(s) recorded.</span>
        <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Save Popup Settings</button>
      </div>
    </form>
  </div>

  <div class="smm-card p-6 h-fit">
    <div class="text-xs font-bold uppercase text-slate-400 mb-3">Live Preview</div>
    <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm">
      <?php $previewImage = (string)get_setting('login_popup_image_url', ''); ?>
      <?php if ($previewImage !== ''): ?>
        <img src="<?= e($previewImage) ?>" alt="Popup banner" class="w-full h-32 object-cover">
      <?php endif; ?>
      <div class="p-5 space-y-4">
        <div class="flex items-start justify-between gap-3">
          <h3 class="font-bold text-slate-900 text-lg"><?= e(get_setting('login_popup_title', 'Join our community')) ?></h3>
          <span class="text-slate-400">&times;</span>
        </div>
        <p class="text-sm text-slate-600 whitespace-pre-line"><?= e(get_setting('login_popup_message', '')) ?></p>
        <div class="space-y-2">
          <?php if (get_setting('login_popup_whatsapp_enabled', '1') === '1' && get_setting('login_popup_whatsapp_url', '') !== ''): ?>
            <div class="w-full py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold text-center"><?= e(get_setting('login_popup_whatsapp_label', 'Join WhatsApp Channel')) ?></div>
          <?php endif; ?>
          <?php if (get_setting('login_popup_telegram_enabled', '1') === '1' && get_setting('login_popup_telegram_url', '') !== ''): ?>
            <div class="w-full py-2.5 rounded-xl bg-sky-500 text-white text-sm font-semibold text-center"><?= e(get_setting('login_popup_telegram_label', 'Join Telegram Channel')) ?></div>
          <?php endif; ?>
          <div class="w-full py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold text-center">Maybe later</div>
        </div>
      </div>
    </div>
    <p class="text-[11px] text-slate-400 mt-3">The popup uses the same modal styling as the rest of the panel.</p>
  </div>
</div>
