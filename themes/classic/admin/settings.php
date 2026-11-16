<?php
declare(strict_types=1);
/** @var string $tab @var string $cronToken */
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-900">Website &amp; System Settings</h1>
  <p class="text-sm text-slate-500">Manage brand identity, landing content, SEO, email SMTP, and cPanel cron schedules.</p>
</div>

<div class="flex flex-wrap gap-2 mb-6">
  <?php foreach (['general' => 'General & Brand', 'landing' => 'Landing Content & FAQ', 'seo' => 'SEO, Contact & Footer', 'mail' => 'Email & SMTP', 'cron' => 'cPanel Cron Setup'] as $tk => $tl): ?>
    <a href="?tab=<?= e($tk) ?>" class="px-4 py-2 rounded-xl text-xs font-semibold border <?= $tab === $tk ? 'bg-violet-600 text-white border-violet-600' : 'bg-white text-slate-600 border-slate-200' ?>"><?= e($tl) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'general'): ?>
<div class="smm-card p-6 max-w-3xl">
  <form method="POST" class="space-y-4">
    <?= CSRF::field() ?>
    <input type="hidden" name="section" value="general">
    <input type="hidden" name="active_tab" value="general">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div><label class="smm-label">Website Name</label><input type="text" name="site_name" value="<?= e(get_setting('site_name')) ?>" required class="smm-input"></div>
      <div><label class="smm-label">Tagline</label><input type="text" name="site_tagline" value="<?= e(get_setting('site_tagline')) ?>" class="smm-input"></div>
      <div><label class="smm-label">Currency Code (e.g. INR, USD)</label><input type="text" name="currency_code" value="<?= e(get_setting('currency_code', 'INR')) ?>" required class="smm-input"></div>
      <div><label class="smm-label">Currency Symbol</label><input type="text" name="currency_symbol" value="<?= e(get_setting('currency_symbol', '₹')) ?>" required class="smm-input"></div>
      <div><label class="smm-label">Timezone</label><input type="text" name="timezone" value="<?= e(get_setting('timezone', 'Asia/Kolkata')) ?>" class="smm-input"></div>
      <div><label class="smm-label">Logo URL (Optional)</label><input type="text" name="logo_url" value="<?= e(get_setting('logo_url')) ?>" class="smm-input" placeholder="https://..."></div>
      <div class="sm:col-span-2"><label class="smm-label">Favicon URL (Optional)</label><input type="text" name="favicon_url" value="<?= e(get_setting('favicon_url')) ?>" class="smm-input" placeholder="https://..."></div>
    </div>
    <div class="flex flex-wrap gap-6 pt-2 text-sm text-slate-700">
      <label class="flex items-center gap-2"><input type="checkbox" name="registration_enabled" value="1" <?= get_setting('registration_enabled', '1') === '1' ? 'checked' : '' ?>> Allow User Registration</label>
      <label class="flex items-center gap-2"><input type="checkbox" name="maintenance_mode" value="1" <?= get_setting('maintenance_mode', '0') === '1' ? 'checked' : '' ?>> Maintenance Mode</label>
    </div>
    <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Save General Settings</button>
  </form>
</div>

<?php elseif ($tab === 'landing'): ?>
<div class="smm-card p-6 max-w-4xl">
  <form method="POST" class="space-y-4">
    <?= CSRF::field() ?>
    <input type="hidden" name="section" value="landing">
    <input type="hidden" name="active_tab" value="landing">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div><label class="smm-label">Hero Badge</label><input type="text" name="hero_badge" value="<?= e(get_setting('hero_badge')) ?>" class="smm-input"></div>
      <div><label class="smm-label">Hero Title</label><input type="text" name="hero_title" value="<?= e(get_setting('hero_title')) ?>" class="smm-input"></div>
    </div>
    <div><label class="smm-label">Hero Subtitle</label><textarea name="hero_subtitle" rows="2" class="smm-input"><?= e(get_setting('hero_subtitle')) ?></textarea></div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div><label class="smm-label">Why Choose Us Title</label><input type="text" name="why_choose_title" value="<?= e(get_setting('why_choose_title')) ?>" class="smm-input"></div>
      <div><label class="smm-label">Why Choose Us Subtitle</label><input type="text" name="why_choose_subtitle" value="<?= e(get_setting('why_choose_subtitle')) ?>" class="smm-input"></div>
    </div>
    <div><label class="smm-label">Features JSON Array ([{title, desc}])</label><textarea name="features_json" rows="5" class="smm-input font-mono text-xs"><?= e(get_setting('features_json', '[]')) ?></textarea></div>
    <div><label class="smm-label">FAQ JSON Array ([{q, a}])</label><textarea name="faq_json" rows="5" class="smm-input font-mono text-xs"><?= e(get_setting('faq_json', '[]')) ?></textarea></div>
    <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Save Landing Content</button>
  </form>
</div>

<?php elseif ($tab === 'seo'): ?>
<div class="smm-card p-6 max-w-3xl">
  <form method="POST" class="space-y-4">
    <?= CSRF::field() ?>
    <input type="hidden" name="section" value="seo_contact">
    <input type="hidden" name="active_tab" value="seo">
    <div><label class="smm-label">SEO Meta Title</label><input type="text" name="meta_title" value="<?= e(get_setting('meta_title')) ?>" class="smm-input"></div>
    <div><label class="smm-label">SEO Meta Description</label><textarea name="meta_description" rows="2" class="smm-input"><?= e(get_setting('meta_description')) ?></textarea></div>
    <div><label class="smm-label">SEO Keywords</label><input type="text" name="meta_keywords" value="<?= e(get_setting('meta_keywords')) ?>" class="smm-input"></div>
    <hr class="border-slate-100">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div><label class="smm-label">Contact Email</label><input type="email" name="contact_email" value="<?= e(get_setting('contact_email')) ?>" class="smm-input"></div>
      <div><label class="smm-label">Contact Phone</label><input type="text" name="contact_phone" value="<?= e(get_setting('contact_phone')) ?>" class="smm-input"></div>
      <div><label class="smm-label">Support Hours</label><input type="text" name="support_hours" value="<?= e(get_setting('support_hours')) ?>" class="smm-input"></div>
      <div><label class="smm-label">Office Address</label><input type="text" name="contact_address" value="<?= e(get_setting('contact_address')) ?>" class="smm-input"></div>
    </div>
    <div><label class="smm-label">Footer About</label><textarea name="footer_about" rows="2" class="smm-input"><?= e(get_setting('footer_about')) ?></textarea></div>
    <div><label class="smm-label">Footer Copyright Text</label><input type="text" name="footer_copyright" value="<?= e(get_setting('footer_copyright')) ?>" class="smm-input"></div>
    <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Save SEO &amp; Contact</button>
  </form>
</div>

<?php elseif ($tab === 'mail'): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  <div class="smm-card p-6">
    <h2 class="font-bold text-slate-900 mb-4">Email &amp; SMTP Configuration</h2>
    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="section" value="mail">
      <input type="hidden" name="active_tab" value="mail">
      <div>
        <label class="smm-label">Mail Driver</label>
        <select name="mail_driver" class="smm-input">
          <option value="php_mail" <?= get_setting('mail_driver') === 'php_mail' ? 'selected' : '' ?>>PHP mail()</option>
          <option value="smtp" <?= get_setting('mail_driver') === 'smtp' ? 'selected' : '' ?>>Direct SMTP (Recommended)</option>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="smm-label">From Name</label><input type="text" name="mail_from_name" value="<?= e(get_setting('mail_from_name')) ?>" class="smm-input"></div>
        <div><label class="smm-label">From Email</label><input type="email" name="mail_from_email" value="<?= e(get_setting('mail_from_email')) ?>" class="smm-input"></div>
      </div>
      <div class="grid grid-cols-3 gap-3">
        <div class="col-span-2"><label class="smm-label">SMTP Host</label><input type="text" name="smtp_host" value="<?= e(get_setting('smtp_host')) ?>" class="smm-input" placeholder="smtp.hostinger.com"></div>
        <div><label class="smm-label">Port</label><input type="number" name="smtp_port" value="<?= e(get_setting('smtp_port', '587')) ?>" class="smm-input"></div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="smm-label">SMTP Username</label><input type="text" name="smtp_username" value="<?= e(get_setting('smtp_username')) ?>" class="smm-input"></div>
        <div>
          <label class="smm-label">Encryption</label>
          <select name="smtp_encryption" class="smm-input">
            <option value="tls" <?= get_setting('smtp_encryption') === 'tls' ? 'selected' : '' ?>>TLS (STARTTLS)</option>
            <option value="ssl" <?= get_setting('smtp_encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option>
            <option value="none" <?= get_setting('smtp_encryption') === 'none' ? 'selected' : '' ?>>None</option>
          </select>
        </div>
      </div>
      <div><label class="smm-label">SMTP Password (leave blank to keep existing)</label><input type="password" name="smtp_password" class="smm-input" autocomplete="new-password"></div>
      <button type="submit" class="btn-primary px-6 py-2.5 text-sm">Save Email Settings</button>
    </form>
  </div>

  <div class="smm-card p-6 h-fit">
    <h2 class="font-bold text-slate-900 mb-2">Send Test Email</h2>
    <p class="text-xs text-slate-500 mb-4">Verifies real SMTP or mail() delivery.</p>
    <form method="POST" class="space-y-4">
      <?= CSRF::field() ?>
      <input type="hidden" name="section" value="test_email">
      <input type="hidden" name="active_tab" value="mail">
      <div><label class="smm-label">Recipient Email</label><input type="email" name="test_to" required class="smm-input" placeholder="you@example.com"></div>
      <button type="submit" class="btn-secondary px-5 py-2.5 text-sm">Dispatch Test Email</button>
    </form>
  </div>
</div>

<?php elseif ($tab === 'cron'): ?>
<div class="smm-card p-6 max-w-4xl space-y-4">
  <h2 class="font-bold text-slate-900">cPanel Recommended Cron Jobs</h2>
  <p class="text-sm text-slate-600">Cron scripts reject browser access without CLI SAPI or a valid cron token. Configure these commands in cPanel &rarr; Cron Jobs:</p>
  <div class="space-y-3 text-xs font-mono">
    <div class="p-3 rounded-xl bg-slate-900 text-emerald-300 overflow-x-auto">
      <div class="text-slate-400 mb-1"># Every 1 minute: Process queued pending orders</div>
      * * * * * /usr/local/bin/php <?= e(BASE_PATH) ?>/cron/process-orders.php >/dev/null 2>&amp;1
    </div>
    <div class="p-3 rounded-xl bg-slate-900 text-emerald-300 overflow-x-auto">
      <div class="text-slate-400 mb-1"># Every 2 minutes: Sync active order statuses &amp; auto-refunds</div>
      */2 * * * * /usr/local/bin/php <?= e(BASE_PATH) ?>/cron/sync-orders.php >/dev/null 2>&amp;1
    </div>
    <div class="p-3 rounded-xl bg-slate-900 text-emerald-300 overflow-x-auto">
      <div class="text-slate-400 mb-1"># Every 15 minutes: Sync provider balances &amp; rates</div>
      */15 * * * * /usr/local/bin/php <?= e(BASE_PATH) ?>/cron/sync-providers.php >/dev/null 2>&amp;1
    </div>
    <div class="p-3 rounded-xl bg-slate-900 text-emerald-300 overflow-x-auto">
      <div class="text-slate-400 mb-1"># Every 5 minutes: Process refill statuses</div>
      */5 * * * * /usr/local/bin/php <?= e(BASE_PATH) ?>/cron/process-refills.php >/dev/null 2>&amp;1
    </div>
    <div class="p-3 rounded-xl bg-slate-900 text-emerald-300 overflow-x-auto">
      <div class="text-slate-400 mb-1"># Every 6 hours: Refresh currency exchange rates</div>
      0 */6 * * * /usr/local/bin/php <?= e(BASE_PATH) ?>/cron/sync-currency-rates.php >/dev/null 2>&amp;1
    </div>
    <div class="p-3 rounded-xl bg-slate-900 text-emerald-300 overflow-x-auto">
      <div class="text-slate-400 mb-1"># Daily at 3:00 AM: Cleanup old logs, temp files &amp; expired backups</div>
      0 3 * * * /usr/local/bin/php <?= e(BASE_PATH) ?>/cron/cleanup.php >/dev/null 2>&amp;1
    </div>
  </div>
  <?php if ($cronToken !== ''): ?>
    <div class="p-4 rounded-xl bg-violet-50 border border-violet-200 text-xs text-violet-900">
      <strong>Protected Web Cron Token (for external curl if CLI PHP unavailable):</strong> <code>?token=<?= e($cronToken) ?></code>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>
