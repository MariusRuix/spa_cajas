<?php
/**
 * SEO, redes sociales, verificaciones y etiquetas de analítica.
 * Todo se configura en Panel → Configuración → SEO / Analítica.
 *
 * @var array $s
 */
$brand    = trim(($s['brand_name'] ?? '') . ' ' . ($s['brand_highlight'] ?? ''));
$title    = $s['site_title'] ?? $brand;
$desc     = ($s['seo_description'] ?? '') !== '' ? $s['seo_description'] : ($s['hero_text'] ?? '');
$ogTitle  = ($s['og_title'] ?? '') !== '' ? $s['og_title'] : $title;
$ogDesc   = ($s['og_description'] ?? '') !== '' ? $s['og_description'] : $desc;
$ogImage  = abs_url(($s['og_image'] ?? '') !== '' ? $s['og_image'] : '/assets/img/logo-full.jpg');
$canon    = base_url() . '/';
$index    = ($s['seo_index'] ?? '1') === '1';

// Los IDs ya vienen validados con regex desde el panel; se vuelven a filtrar por seguridad.
$id = fn(string $k, string $re) => preg_match($re, $s[$k] ?? '') ? $s[$k] : '';
$ga4   = $id('ga4_id', '/^G-[A-Z0-9]{4,20}$/');
$gtm   = $id('gtm_id', '/^GTM-[A-Z0-9]{4,12}$/');
$gads  = $id('gads_id', '/^AW-\d{6,15}$/');
$pixel = $id('meta_pixel_id', '/^\d{6,20}$/');
$tiktok = $id('tiktok_pixel_id', '/^[A-Z0-9]{10,30}$/');

// Datos estructurados (Google entiende que es un negocio local)
$sameAs = array_values(array_filter([safe_url($s['facebook_url'] ?? ''), safe_url($s['instagram_url'] ?? '')]));
$jsonLd = array_filter([
    '@context'    => 'https://schema.org',
    '@type'       => 'LocksmithService',
    'name'        => $brand,
    'description' => $desc,
    'url'         => $canon,
    'logo'        => abs_url('/assets/img/logo-mark.jpg'),
    'image'       => $ogImage,
    'telephone'   => $s['phone_display'] ?? '',
    'address'     => array_filter([
        '@type'           => 'PostalAddress',
        'streetAddress'   => $s['address'] ?? '',
        'addressLocality' => $s['business_city'] ?? '',
        'addressRegion'   => $s['business_region'] ?? '',
        'postalCode'      => $s['business_postal'] ?? '',
        'addressCountry'  => $s['business_country'] ?? '',
    ]),
    'sameAs'      => $sameAs,
]);
?>
    <meta name="description" content="<?= e($desc) ?>">
<?php if (($s['seo_keywords'] ?? '') !== ''): ?>
    <meta name="keywords" content="<?= e($s['seo_keywords']) ?>">
<?php endif; ?>
    <meta name="robots" content="<?= $index ? 'index, follow, max-image-preview:large' : 'noindex, nofollow' ?>">
    <link rel="canonical" href="<?= e($canon) ?>">

    <!-- Open Graph (Facebook, WhatsApp, LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_MX">
    <meta property="og:site_name" content="<?= e($brand) ?>">
    <meta property="og:url" content="<?= e($canon) ?>">
    <meta property="og:title" content="<?= e($ogTitle) ?>">
    <meta property="og:description" content="<?= e($ogDesc) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($ogTitle) ?>">
    <meta name="twitter:description" content="<?= e($ogDesc) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">

<?php if ($v = $id('google_verification', '/^[A-Za-z0-9_\-]{10,100}$/')): ?>
    <meta name="google-site-verification" content="<?= e($v) ?>">
<?php endif; ?>
<?php if ($v = $id('bing_verification', '/^[A-Za-z0-9]{16,64}$/')): ?>
    <meta name="msvalidate.01" content="<?= e($v) ?>">
<?php endif; ?>

    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<?php if ($gtm): ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= $gtm ?>');</script>
<?php endif; ?>
<?php if ($ga4 || $gads): ?>
    <!-- Google tag (gtag.js): Analytics 4 / Google Ads -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $ga4 ?: $gads ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
<?php if ($ga4): ?>
        gtag('config', '<?= $ga4 ?>');
<?php endif; ?>
<?php if ($gads): ?>
        gtag('config', '<?= $gads ?>');
<?php endif; ?>
    </script>
<?php endif; ?>
<?php if ($pixel): ?>
    <!-- Meta Pixel -->
    <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= $pixel ?>');fbq('track','PageView');</script>
<?php endif; ?>
<?php if ($tiktok): ?>
    <!-- TikTok Pixel -->
    <script>!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=d.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=d.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};ttq.load('<?= $tiktok ?>');ttq.page();}(window,document,'ttq');</script>
<?php endif; ?>
<?php if (($s['custom_head'] ?? '') !== ''): ?>
    <!-- Código personalizado (head) -->
<?= $s['custom_head'] ?>

<?php endif; ?>
