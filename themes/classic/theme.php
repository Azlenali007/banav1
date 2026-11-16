<?php
declare(strict_types=1);

/**
 * Classic Theme Bootstrap & Head/Script Asset Renderer
 */
function classic_render_head(string $pageTitle, string $metaDescription = '', string $area = 'public'): void
{
    $siteName = get_setting('site_name', APP_DEFAULT_NAME);
    $fullTitle = $pageTitle !== '' ? $pageTitle . ' — ' . $siteName : $siteName;
    $desc = $metaDescription !== '' ? $metaDescription : (string)get_setting('meta_description', '');
    $favicon = (string)get_setting('favicon_url', '');
    $primary = Theme::get('primary_color', '#7C3AED');
    $primaryDark = Theme::get('primary_dark', '#5B21B6');
    $accent = Theme::get('accent_color', '#A855F7');
    $surfaceBg = Theme::get('surface_bg', '#F8FAFC');
    $cardRadius = Theme::get('card_radius', '14px');
    $baseCssUrl = Theme::asset('css/style.css') . '?v=' . APP_VERSION;
    $areaCssUrl = match ($area) {
        'admin' => Theme::asset('css/admin.css') . '?v=' . APP_VERSION,
        'user' => Theme::asset('css/user.css') . '?v=' . APP_VERSION,
        default => '',
    };
    $csrfToken = CSRF::token();
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($fullTitle) ?></title>
    <?php if ($desc !== ''): ?>
        <meta name="description" content="<?= e($desc) ?>">
    <?php endif; ?>
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <?php if ($favicon !== ''): ?>
        <link rel="icon" href="<?= e($favicon) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: { sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'] },
            colors: {
              brand: {
                50: '#FAF5FF',
                100: '#F3E8FF',
                200: '#E9D5FF',
                500: '<?= e($accent) ?>',
                600: '<?= e($primary) ?>',
                700: '<?= e($primaryDark) ?>',
                900: '#2E1065'
              }
            }
          }
        }
      };
    </script>
    <style>
      :root {
        --smm-primary: <?= e($primary) ?>;
        --smm-primary-dark: <?= e($primaryDark) ?>;
        --smm-accent: <?= e($accent) ?>;
        --smm-surface: <?= e($surfaceBg) ?>;
        --smm-radius: <?= e($cardRadius) ?>;
      }
    </style>
    <link rel="stylesheet" href="<?= e($baseCssUrl) ?>">
    <?php if ($areaCssUrl !== ''): ?>
      <link rel="stylesheet" href="<?= e($areaCssUrl) ?>">
    <?php endif; ?>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <?php
}

function classic_render_scripts(string $area = 'public'): void
{
    $baseJsUrl = Theme::asset('js/app.js') . '?v=' . APP_VERSION;
    echo '<script src="' . e($baseJsUrl) . '"></script>';
    if ($area === 'admin') {
        echo '<script src="' . e(Theme::asset('js/admin.js') . '?v=' . APP_VERSION) . '"></script>';
    } elseif ($area === 'user') {
        echo '<script src="' . e(Theme::asset('js/user.js') . '?v=' . APP_VERSION) . '"></script>';
    }
}
