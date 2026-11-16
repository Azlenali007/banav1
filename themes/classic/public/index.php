<?php
declare(strict_types=1);
/** @var string $heroBadge @var string $heroTitle @var string $heroSubtitle @var string $whyTitle @var string $whySubtitle */
/** @var array $features @var array $faqs @var array $categories @var ?array $currentUser */
/** @var string $contactEmail @var string $contactPhone @var string $contactAddress @var string $supportHours */
?>
<!-- Hero Section -->
<section class="relative overflow-hidden bg-white border-b border-slate-200">
  <div class="absolute inset-0 pointer-events-none bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-violet-100/70 via-transparent to-transparent"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 relative">
    <div class="max-w-3xl">
      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-violet-100 text-violet-800 text-xs font-bold tracking-wide uppercase mb-5">
        <span class="w-2 h-2 rounded-full bg-violet-600"></span>
        <?= e($heroBadge) ?>
      </span>
      <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900 leading-tight mb-5">
        <?= e($heroTitle) ?>
      </h1>
      <p class="text-base sm:text-lg text-slate-600 leading-relaxed mb-8">
        <?= e($heroSubtitle) ?>
      </p>
      <div class="flex flex-wrap items-center gap-3.5">
        <?php if ($currentUser): ?>
          <a href="<?= e(app_url('/user/new-order.php')) ?>" class="btn-primary px-6 py-3 text-sm sm:text-base">Place New Order</a>
          <a href="<?= e(app_url('/user/dashboard.php')) ?>" class="btn-secondary px-6 py-3 text-sm sm:text-base">Go to Dashboard</a>
        <?php else: ?>
          <a href="<?= e(app_url('/register.php')) ?>" class="btn-primary px-6 py-3 text-sm sm:text-base">Create Free Account</a>
          <a href="<?= e(app_url('/login.php')) ?>" class="btn-secondary px-6 py-3 text-sm sm:text-base">Sign In to Panel</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Services Overview -->
<section id="services" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 w-full">
  <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-10">
    <div>
      <span class="text-xs font-bold uppercase tracking-wider text-violet-700">Live Catalog</span>
      <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1">Available Service Categories</h2>
    </div>
    <a href="<?= e(app_url($currentUser ? '/user/services.php' : '/register.php')) ?>" class="text-sm font-semibold text-violet-700 hover:text-violet-900">
      Browse Full Service List &rarr;
    </a>
  </div>

  <?php if (empty($categories)): ?>
    <div class="smm-card p-8 text-center text-sm text-slate-500">
      Services are currently being configured by the panel administrator. Sign in or check back shortly.
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <?php foreach ($categories as $cat): ?>
        <div class="smm-card smm-card-hover p-5 flex flex-col justify-between">
          <div>
            <div class="w-10 h-10 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center mb-4">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <h3 class="font-bold text-slate-900 mb-1.5"><?= e($cat['name']) ?></h3>
            <p class="text-xs text-slate-500 line-clamp-2 mb-4"><?= e($cat['description'] ?: 'Active automated and managed services in this category.') ?></p>
          </div>
          <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="font-semibold text-slate-700"><?= (int)$cat['service_count'] ?> Services</span>
            <span class="text-violet-700 font-bold">From <?= e(format_money($cat['starting_rate'], 2)) ?>/1K</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Why Choose Us / Features -->
<section id="features" class="bg-white border-y border-slate-200 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mb-10">
      <span class="text-xs font-bold uppercase tracking-wider text-violet-700">Why Choose Us</span>
      <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1"><?= e($whyTitle) ?></h2>
      <p class="text-sm sm:text-base text-slate-600 mt-2"><?= e($whySubtitle) ?></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
      <?php foreach ($features as $feat): ?>
        <div class="smm-card p-6">
          <div class="w-9 h-9 rounded-lg bg-violet-600 text-white flex items-center justify-center mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          </div>
          <h3 class="font-bold text-slate-900 mb-2"><?= e($feat['title'] ?? '') ?></h3>
          <p class="text-sm text-slate-600 leading-relaxed"><?= e($feat['desc'] ?? '') ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- How It Works -->
<section id="how-it-works" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 w-full">
  <div class="text-center max-w-2xl mx-auto mb-12">
    <span class="text-xs font-bold uppercase tracking-wider text-violet-700">Simple Workflow</span>
    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1">How It Works</h2>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
    <?php
    $steps = [
        ['1', 'Create Account', 'Register in seconds with your email and access your personal client dashboard.'],
        ['2', 'Add Wallet Funds', 'Fund your wallet via Razorpay instant checkout or verified manual bank/UPI transfer.'],
        ['3', 'Select & Submit Order', 'Pick a category and service, provide your link and quantity, and confirm exact pricing.'],
        ['4', 'Track Live Progress', 'Monitor order status, start count, remains, and automated refunds directly in your history.'],
    ];
    foreach ($steps as [$num, $title, $desc]):
    ?>
      <div class="smm-card p-6 relative">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-violet-100 text-violet-800 font-bold text-sm mb-4"><?= $num ?></span>
        <h3 class="font-bold text-slate-900 mb-1.5"><?= e($title) ?></h3>
        <p class="text-sm text-slate-600 leading-relaxed"><?= e($desc) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- FAQ -->
<?php if (!empty($faqs)): ?>
<section id="faq" class="bg-white border-y border-slate-200 py-16">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-10">
      <span class="text-xs font-bold uppercase tracking-wider text-violet-700">Answers</span>
      <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1">Frequently Asked Questions</h2>
    </div>
    <div class="space-y-3" x-data="{ openIdx: 0 }">
      <?php foreach ($faqs as $idx => $faq): ?>
        <div class="smm-card overflow-hidden">
          <button type="button" @click="openIdx = openIdx === <?= (int)$idx ?> ? -1 : <?= (int)$idx ?>" class="w-full px-6 py-4 text-left flex items-center justify-between gap-4 font-semibold text-slate-900 hover:bg-violet-50/40">
            <span><?= e($faq['q'] ?? '') ?></span>
            <svg class="w-5 h-5 text-violet-600 transition-transform" :class="openIdx === <?= (int)$idx ?> ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div x-show="openIdx === <?= (int)$idx ?>" x-cloak class="px-6 pb-5 text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
            <?= e($faq['a'] ?? '') ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Contact Section -->
<section id="contact" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 w-full">
  <div class="smm-card p-8 sm:p-10 bg-gradient-to-br from-white to-violet-50/60 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
    <div>
      <span class="text-xs font-bold uppercase tracking-wider text-violet-700">Support &amp; Inquiries</span>
      <h2 class="text-2xl font-bold text-slate-900 mt-1 mb-2">Need Help With Orders or Reseller API?</h2>
      <div class="text-sm text-slate-600 space-y-1">
        <?php if ($contactEmail !== ''): ?><div>Email: <a href="mailto:<?= e($contactEmail) ?>" class="text-violet-700 font-semibold"><?= e($contactEmail) ?></a></div><?php endif; ?>
        <?php if ($contactPhone !== ''): ?><div>Phone: <span class="font-medium text-slate-800"><?= e($contactPhone) ?></span></div><?php endif; ?>
        <?php if ($supportHours !== ''): ?><div>Support Hours: <span class="font-medium text-slate-800"><?= e($supportHours) ?></span></div><?php endif; ?>
        <?php if ($contactAddress !== ''): ?><div>Address: <span class="font-medium text-slate-800"><?= e($contactAddress) ?></span></div><?php endif; ?>
      </div>
    </div>
    <div class="flex flex-wrap gap-3">
      <a href="<?= e(app_url('/user/tickets.php')) ?>" class="btn-primary px-5 py-2.5 text-sm">Open Support Ticket</a>
      <a href="<?= e(app_url('/register.php')) ?>" class="btn-secondary px-5 py-2.5 text-sm">Register Now</a>
    </div>
  </div>
</section>
