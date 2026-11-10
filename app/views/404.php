<?php
/**
 * Página 404 (no encontrada), con la marca del sitio.
 * @var array $s Ajustes del sitio
 */
$brandFull = trim(($s['brand_name'] ?? '') . ' ' . ($s['brand_highlight'] ?? ''));
$phoneDigits = preg_replace('/\D+/', '', $s['phone_display'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">
    <title>Página no encontrada · <?= e($brandFull) ?></title>
<?php include __DIR__ . '/_favicons.php'; ?>
    <link rel="preload" href="/assets/fonts/barlow-sc-700.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/assets/vendor/tabler-icons/tabler-icons.min.css">
    <link rel="stylesheet" href="/assets/css/site.css">
</head>
<body class="page-404">
    <header class="masthead">
        <div class="wrap masthead__inner">
            <a class="brand" href="/" aria-label="<?= e($brandFull) ?>, inicio">
                <img class="brand__mark" src="/assets/img/emblem-sm.webp" alt="" width="34" height="41">
                <span class="brand__text">
                    <span class="brand__name"><?= e($brandFull) ?></span>
                    <span class="brand__tagline"><?= e($s['brand_tagline'] ?? '') ?></span>
                </span>
            </a>
            <a class="btn btn--wa btn--sm" href="<?= e(wa_link('Hola, necesito información sobre sus servicios de cajas fuertes.')) ?>" target="_blank" rel="noopener">
                <i class="ti ti-brand-whatsapp" aria-hidden="true"></i><span class="hide-sm">WhatsApp 24/7</span>
            </a>
        </div>
    </header>

    <main class="sheet lost">
        <div class="wrap sheet__inner">
            <div class="emblem emblem--sm" aria-hidden="true">
                <img src="/assets/img/emblem.webp" alt="" width="455" height="552">
            </div>
            <p class="lost__code" aria-hidden="true">404</p>
            <h1 class="sheet__title lost__title">Esta página no existe</h1>
            <p class="ruled-caption"><span>La dirección cambió o se escribió mal</span></p>
            <p class="sheet__lead">Lo que buscas está a un clic: vuelve al inicio, revisa el catálogo o escríbenos y te ayudamos.</p>
            <div class="sheet__actions">
                <a class="btn btn--ghost btn--lg" href="/"><i class="ti ti-home" aria-hidden="true"></i>Ir al inicio</a>
                <a class="btn btn--ghost btn--lg" href="/#catalogo"><i class="ti ti-shield-lock" aria-hidden="true"></i>Ver catálogo</a>
                <a class="btn btn--wa btn--lg" href="<?= e(wa_link('Hola, llegué a una página que no existe en su sitio y necesito ayuda.')) ?>" target="_blank" rel="noopener">
                    <i class="ti ti-brand-whatsapp" aria-hidden="true"></i>Escríbenos por WhatsApp
                </a>
            </div>
            <?php if ($phoneDigits !== ''): ?>
            <p class="sheet__call">o llama al <a href="tel:+<?= e($phoneDigits) ?>"><?= e($s['phone_display']) ?></a></p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
