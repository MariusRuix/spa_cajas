<?php
/**
 * Landing pública de Royal Vault.
 * Diseño basado en la hoja de marca del logotipo (azul noche, plata, capitales romanas).
 * Todo el contenido viene de la base de datos y se administra en /admin.
 * IMPORTANTE: imprime siempre con e() para evitar XSS.
 *
 * @var array $s        Ajustes del sitio
 * @var array $services Servicios activos
 * @var array $products Productos activos
 * @var array $gallery  Fotos por producto [id => [ruta, ...]]
 * @var string $csrf
 */
$phoneDigits = preg_replace('/\D+/', '', $s['phone_display'] ?? '');
$phoneHref   = $phoneDigits !== '' ? 'tel:+' . $phoneDigits : '';
$brandName   = $s['brand_name'] ?? '';
$brandHi     = $s['brand_highlight'] ?? '';
$brandFull   = trim($brandName . ' ' . $brandHi);

/** Icono de línea: clase de Tabler ("ti ti-key") o, si no hay, la caja fuerte dibujada. */
$icon = function (?string $class, string $extra = ''): string {
    $class = trim((string) $class);
    if (preg_match('/^ti ti-[a-z0-9-]+$/', $class)) {
        return '<i class="' . e($class . ' ' . $extra) . '" aria-hidden="true"></i>';
    }
    return '<svg class="icon-safe ' . e($extra) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<rect x="3" y="3" width="18" height="16" rx="1.5"/><circle cx="11" cy="11" r="3.6"/><path d="M11 7.4v1.4M11 13.2v1.4M7.4 11h1.4M13.2 11h1.4"/>'
        . '<path d="M17.5 8.5v5"/><path d="M6 19v2M18 19v2"/></svg>';
};

$pillars = [
    ['label' => 'Venta',         'icon' => 'ti ti-shield-lock', 'href' => '#catalogo', 'hint' => 'Ver catálogo', 'wa' => false],
    ['label' => 'Reparación',    'icon' => 'ti ti-tools',       'href' => wa_link('Hola, necesito reparar mi caja fuerte.'), 'hint' => 'Cotizar', 'wa' => true],
    ['label' => 'Mantenimiento', 'icon' => 'ti ti-settings',    'href' => wa_link('Hola, me interesa el mantenimiento de mi caja fuerte.'), 'hint' => 'Agendar', 'wa' => true],
    ['label' => 'Aperturas',     'icon' => null,                'href' => wa_link('Hola, tengo una emergencia: mi caja fuerte no abre.'), 'hint' => 'Emergencia 24/7', 'wa' => true],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <title><?= e($s['site_title'] ?? '') ?></title>
<?php include __DIR__ . '/_favicons.php'; ?>
<?php include __DIR__ . '/_seo_head.php'; ?>
    <link rel="preload" href="/assets/fonts/barlow-sc-700.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/jost.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/img/emblem.webp" as="image">
    <link rel="stylesheet" href="/assets/vendor/tabler-icons/tabler-icons.min.css">
    <link rel="stylesheet" href="/assets/css/site.css">
    <script defer src="/assets/vendor/alpine.min.js"></script>
</head>
<body x-data="{ menu: false }" :class="{ 'menu-open': menu }" @keydown.escape="menu = false">
<?php $part = 'open'; include __DIR__ . '/_seo_body.php'; ?>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <!-- Barra de emergencias -->
    <div class="topbar">
        <div class="wrap topbar__inner">
            <span class="topbar__note"><span class="pulse" aria-hidden="true"></span><?= e($s['topbar_text'] ?? '') ?></span>
            <?php if ($phoneHref): ?>
            <a class="topbar__phone" href="<?= e($phoneHref) ?>"><i class="ti ti-phone" aria-hidden="true"></i><?= e($s['phone_display']) ?></a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cabecera -->
    <header class="masthead">
        <div class="wrap masthead__inner">
            <a class="brand" href="#inicio" aria-label="<?= e($brandFull) ?>, inicio">
                <img class="brand__mark" src="/assets/img/emblem-sm.webp" alt="" width="34" height="41">
                <span class="brand__text">
                    <span class="brand__name"><?= e($brandName) ?> <?= e($brandHi) ?></span>
                    <span class="brand__tagline"><?= e($s['brand_tagline'] ?? '') ?></span>
                </span>
            </a>

            <nav id="menu" class="nav" :class="{ 'is-open': menu }" aria-label="Principal">
                <a href="#servicios" @click="menu = false">Servicios</a>
                <a href="#catalogo" @click="menu = false">Catálogo</a>
                <a href="#nosotros" @click="menu = false">Nosotros</a>
                <a href="#ubicacion" @click="menu = false">Ubicación</a>
                <a href="#contacto" @click="menu = false">Contacto</a>
            </nav>

            <div class="masthead__actions">
                <a class="btn btn--wa btn--sm" href="<?= e(wa_link('Hola, necesito información sobre sus servicios de cajas fuertes.')) ?>" target="_blank" rel="noopener">
                    <i class="ti ti-brand-whatsapp" aria-hidden="true"></i><span class="hide-sm">WhatsApp 24/7</span>
                </a>
                <button class="menu-toggle" type="button" @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="menu" aria-label="Menú">
                    <i class="ti" :class="menu ? 'ti-x' : 'ti-menu-2'" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </header>

    <main id="contenido">
        <!-- Portada: la hoja de marca -->
        <section id="inicio" class="sheet">
            <div class="wrap sheet__inner">
                <div class="emblem" aria-hidden="true">
                    <img src="/assets/img/emblem.webp" alt="" width="455" height="552" fetchpriority="high">
                </div>

                <h1 class="sheet__title">
                    <span><?= e($s['hero_title'] ?? '') ?></span>
                    <span><?= e($s['hero_highlight'] ?? '') ?></span>
                </h1>
                <?php if (($s['hero_badge'] ?? '') !== ''): ?>
                <p class="ruled-caption"><span><?= e($s['hero_badge']) ?></span></p>
                <?php endif; ?>
                <p class="sheet__lead"><?= e($s['hero_text'] ?? '') ?></p>

                <div class="sheet__actions">
                    <a class="btn btn--wa btn--lg" href="<?= e(wa_link('Hola, tengo una emergencia con mi caja fuerte.')) ?>" target="_blank" rel="noopener">
                        <i class="ti ti-brand-whatsapp" aria-hidden="true"></i>Emergencias 24/7 por WhatsApp
                    </a>
                    <a class="btn btn--ghost btn--lg" href="#catalogo">Ver catálogo</a>
                </div>
                <?php if ($phoneHref): ?>
                <p class="sheet__call">o llama al <a href="<?= e($phoneHref) ?>"><?= e($s['phone_display']) ?></a></p>
                <?php endif; ?>
            </div>

            <ul class="wrap pillars" aria-label="Qué hacemos">
                <?php foreach ($pillars as $p): ?>
                <li>
                    <a class="pillar" href="<?= e($p['href']) ?>"<?= $p['wa'] ? ' target="_blank" rel="noopener"' : '' ?>>
                        <span class="pillar__icon"><?= $icon($p['icon']) ?></span>
                        <span class="pillar__label"><?= e($p['label']) ?></span>
                        <span class="pillar__hint"><?= e($p['hint']) ?> <i class="ti ti-arrow-narrow-right" aria-hidden="true"></i></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <!-- Servicios -->
        <?php if ($services): ?>
        <section id="servicios" class="section section--light">
            <div class="wrap">
                <header class="section__head">
                    <h2 class="display">Servicio técnico</h2>
                    <p>Atendemos tu caja fuerte en tu domicilio o negocio. Escríbenos y te decimos qué necesita.</p>
                </header>

                <ul class="ledger">
                    <?php foreach ($services as $sv): ?>
                    <li class="ledger__row">
                        <span class="ledger__icon"><?= $icon($sv['icon']) ?></span>
                        <div class="ledger__body">
                            <h3><?= e($sv['title']) ?></h3>
                            <p><?= e($sv['description']) ?></p>
                        </div>
                        <a class="btn btn--outline" href="<?= e(wa_link($sv['wa_message'] ?: 'Hola, me interesa: ' . $sv['title'])) ?>" target="_blank" rel="noopener">
                            <?= e($sv['cta_label']) ?><i class="ti ti-arrow-narrow-right" aria-hidden="true"></i>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
        <?php endif; ?>

        <!-- Catálogo -->
        <section id="catalogo" class="section section--navy">
            <div class="wrap">
                <header class="section__head section__head--split">
                    <h2 class="display">Catálogo de cajas fuertes</h2>
                    <p>Elige el equipo para tu hogar u oficina y cotízalo por WhatsApp con un clic.</p>
                </header>

                <?php if ($products): ?>
                <ul class="vitrine">
                    <?php foreach ($products as $p): ?>
                    <?php $features = array_filter(array_map('trim', explode("\n", $p['features']))); ?>
                    <?php
                        $photos = array_values(array_filter($gallery[(int) $p['id']] ?? [], fn($img) => safe_image($img) !== ''));
                        if (!$photos && safe_image($p['image_url'])) {
                            $photos = [$p['image_url']];
                        }
                        $jsonOpts = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
                    ?>
                    <li class="case">
                        <?php if ($photos): ?>
                        <button type="button" class="case__plate case__plate--zoom"
                                @click="$store.gallery.open(<?= e(json_encode($photos, $jsonOpts)) ?>, <?= e(json_encode($p['name'], $jsonOpts)) ?>, $el)"
                                aria-label="Ver <?= count($photos) > 1 ? count($photos) . ' fotos' : 'foto' ?> de <?= e($p['name']) ?>">
                            <img src="<?= e($photos[0]) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                            <span class="case__count" aria-hidden="true">
                                <i class="ti <?= count($photos) > 1 ? 'ti-photo' : 'ti-arrows-maximize' ?>"></i><?= count($photos) > 1 ? count($photos) . ' fotos' : 'Ampliar' ?>
                            </span>
                        <?php else: ?>
                        <div class="case__plate">
                            <?= $icon($p['icon'], 'case__glyph') ?>
                        <?php endif; ?>
                            <?php if ($p['badge'] !== ''): ?>
                            <span class="tag <?= $p['badge_style'] === 'dark' ? 'tag--outline' : '' ?>"><?= e($p['badge']) ?></span>
                            <?php endif; ?>
                        <?= $photos ? '</button>' : '</div>' ?>
                        <div class="case__body">
                            <h3><?= e($p['name']) ?></h3>
                            <?php if ($p['description'] !== ''): ?><p class="case__desc"><?= e($p['description']) ?></p><?php endif; ?>
                            <?php if ($features): ?>
                            <ul class="case__specs">
                                <?php foreach ($features as $f): ?>
                                <li><i class="ti ti-check" aria-hidden="true"></i><?= e($f) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                        <a class="btn btn--wa btn--block" href="<?= e(wa_link('Hola, me interesa cotizar el modelo: ' . $p['name'])) ?>" target="_blank" rel="noopener">
                            <i class="ti ti-brand-whatsapp" aria-hidden="true"></i>Cotizar por WhatsApp
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="empty-note">Muy pronto publicaremos nuestro catálogo. Escríbenos por WhatsApp para cotizar.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Nosotros + contacto -->
        <section id="nosotros" class="section section--light">
            <div class="wrap duo">
                <div class="duo__text">
                    <h2 class="display">Confianza, discreción y soluciones reales</h2>
                    <p class="prose">En <strong><?= e($brandFull) ?></strong> <?= e(lcfirst($s['about_text'] ?? '')) ?></p>
                    <ul class="points">
                        <li>
                            <i class="ti ti-shield-lock" aria-hidden="true"></i>
                            <div><h3>Discreción en cada servicio</h3><p>Tratamos tus bienes y tu información con total reserva.</p></div>
                        </li>
                        <li>
                            <i class="ti ti-message-circle-check" aria-hidden="true"></i>
                            <div><h3>Atención directa</h3><p>Cotiza y agenda por WhatsApp o por teléfono.</p></div>
                        </li>
                    </ul>
                </div>

                <div id="contacto" class="form-panel" x-data="contactForm()">
                    <h2 class="form-panel__title">Envíanos un mensaje</h2>
                    <p class="form-panel__intro">Te respondemos por teléfono o WhatsApp.</p>
                    <form @submit.prevent="send" novalidate>
                        <input type="text" name="website" x-model="form.website" tabindex="-1" autocomplete="off" class="hp" aria-hidden="true">
                        <div class="field">
                            <label for="cf-name">Nombre completo</label>
                            <input id="cf-name" type="text" required maxlength="100" autocomplete="name" x-model="form.name" placeholder="Ej. María García">
                        </div>
                        <div class="field">
                            <label for="cf-phone">Teléfono o WhatsApp</label>
                            <input id="cf-phone" type="tel" required maxlength="30" autocomplete="tel" inputmode="tel" x-model="form.phone" placeholder="Ej. 999 123 4567">
                        </div>
                        <div class="field">
                            <label for="cf-body">¿Qué servicio o modelo necesitas?</label>
                            <textarea id="cf-body" rows="4" required maxlength="2000" x-model="form.body" placeholder="Cuéntanos brevemente qué necesitas"></textarea>
                        </div>
                        <p class="form-error" role="alert" x-show="error" x-text="error" x-cloak></p>
                        <button type="submit" class="btn btn--navy btn--block btn--lg" :disabled="sending">
                            <span x-text="sending ? 'Enviando…' : 'Enviar solicitud'">Enviar solicitud</span>
                        </button>
                        <p class="form-note">Usamos tus datos solo para responder tu solicitud.</p>
                    </form>

                    <div class="dialog" x-show="done" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="sent-title" @keydown.escape.window="done = false">
                        <div class="dialog__card" @click.outside="done = false">
                            <i class="ti ti-circle-check dialog__icon" aria-hidden="true"></i>
                            <h3 id="sent-title">Mensaje enviado</h3>
                            <p>Gracias. Nos pondremos en contacto contigo muy pronto.</p>
                            <button type="button" class="btn btn--navy btn--block" @click="done = false" x-ref="ok">Aceptar</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ubicación -->
        <section id="ubicacion" class="section section--mist">
            <div class="wrap">
                <header class="section__head">
                    <h2 class="display">Ubicación y cobertura</h2>
                    <p>Visítanos o solicita servicio en tu domicilio o negocio.</p>
                </header>
                <div class="locate">
                    <div class="locate__info">
                        <dl>
                            <div>
                                <dt><i class="ti ti-map-pin" aria-hidden="true"></i>Dirección</dt>
                                <dd><?= e($s['address'] ?? '') ?></dd>
                            </div>
                            <div>
                                <dt><i class="ti ti-clock" aria-hidden="true"></i>Horario</dt>
                                <dd><?= nl2br(e($s['hours'] ?? '')) ?><br><strong>Emergencias 24/7</strong></dd>
                            </div>
                            <?php if ($phoneHref): ?>
                            <div>
                                <dt><i class="ti ti-phone" aria-hidden="true"></i>Teléfono</dt>
                                <dd><a href="<?= e($phoneHref) ?>"><?= e($s['phone_display']) ?></a></dd>
                            </div>
                            <?php endif; ?>
                        </dl>
                        <?php if (safe_url($s['maps_url'] ?? '')): ?>
                        <a class="btn btn--navy" href="<?= e($s['maps_url']) ?>" target="_blank" rel="noopener"><i class="ti ti-map-2" aria-hidden="true"></i>Abrir en Google Maps</a>
                        <?php endif; ?>
                    </div>
                    <div class="locate__map">
                        <?php if (safe_url($s['maps_embed'] ?? '')): ?>
                        <iframe src="<?= e($s['maps_embed']) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Mapa de ubicación de <?= e($brandFull) ?>" allowfullscreen></iframe>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Pie -->
    <footer class="footer">
        <div class="wrap footer__inner">
            <img class="footer__mark" src="/assets/img/emblem-sm.webp" alt="" width="56" height="68" loading="lazy">
            <p class="footer__name"><?= e($brandFull) ?></p>
            <?php if (($s['footer_text'] ?? '') !== ''): ?>
            <p class="ruled-caption ruled-caption--sm"><span><?= e($s['footer_text']) ?></span></p>
            <?php endif; ?>
            <nav class="footer__nav" aria-label="Pie de página">
                <a href="#servicios">Servicios</a><a href="#catalogo">Catálogo</a><a href="#nosotros">Nosotros</a><a href="#ubicacion">Ubicación</a>
            </nav>
            <div class="footer__social">
                <?php if (safe_url($s['facebook_url'] ?? '')): ?>
                <a href="<?= e($s['facebook_url']) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="ti ti-brand-facebook" aria-hidden="true"></i></a>
                <?php endif; ?>
                <?php if (safe_url($s['instagram_url'] ?? '')): ?>
                <a href="<?= e($s['instagram_url']) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="ti ti-brand-instagram" aria-hidden="true"></i></a>
                <?php endif; ?>
                <a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i></a>
            </div>
            <p class="footer__legal">© <?= date('Y') ?> <?= e($brandFull) ?>. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- Galería de fotos del producto -->
    <div class="lightbox" x-data x-show="$store.gallery.isOpen" x-cloak x-transition.opacity.duration.200ms
         role="dialog" aria-modal="true" :aria-label="'Fotos de ' + $store.gallery.title"
         @keydown.escape.window="$store.gallery.close()"
         @keydown.arrow-left.window="$store.gallery.isOpen && $store.gallery.prev()"
         @keydown.arrow-right.window="$store.gallery.isOpen && $store.gallery.next()"
         @click.self="$store.gallery.close()">
        <div class="lightbox__bar">
            <p class="lightbox__title" x-text="$store.gallery.title"></p>
            <p class="lightbox__count" x-show="$store.gallery.images.length > 1" x-text="($store.gallery.index + 1) + ' / ' + $store.gallery.images.length"></p>
            <button type="button" class="lightbox__close" @click="$store.gallery.close()" aria-label="Cerrar" x-ref="close"><i class="ti ti-x" aria-hidden="true"></i></button>
        </div>
        <div class="lightbox__stage" @click.self="$store.gallery.close()"
             @touchstart.passive="$store.gallery.touchStart($event)" @touchend="$store.gallery.touchEnd($event)">
            <button type="button" class="lightbox__nav lightbox__nav--prev" x-show="$store.gallery.images.length > 1" @click="$store.gallery.prev()" aria-label="Foto anterior"><i class="ti ti-chevron-left" aria-hidden="true"></i></button>
            <template x-for="(img, i) in $store.gallery.images" :key="img">
                <img class="lightbox__img" :src="img" :alt="$store.gallery.title + ' — foto ' + (i + 1)"
                     x-show="i === $store.gallery.index" x-transition:enter.opacity.duration.250ms>
            </template>
            <button type="button" class="lightbox__nav lightbox__nav--next" x-show="$store.gallery.images.length > 1" @click="$store.gallery.next()" aria-label="Foto siguiente"><i class="ti ti-chevron-right" aria-hidden="true"></i></button>
        </div>
        <div class="lightbox__thumbs" x-show="$store.gallery.images.length > 1">
            <template x-for="(img, i) in $store.gallery.images" :key="'t' + img">
                <button type="button" class="lightbox__thumb" :class="{ 'is-active': i === $store.gallery.index }" @click="$store.gallery.index = i" :aria-label="'Ver foto ' + (i + 1)" :aria-current="i === $store.gallery.index">
                    <img :src="img" alt="" loading="lazy">
                </button>
            </template>
        </div>
    </div>

    <!-- Celular: llamar o escribir sin buscar -->
    <div class="callbar">
        <?php if ($phoneHref): ?>
        <a class="callbar__call" href="<?= e($phoneHref) ?>"><i class="ti ti-phone" aria-hidden="true"></i>Llamar</a>
        <?php endif; ?>
        <a class="callbar__wa" href="<?= e(wa_link('Hola, necesito asistencia con mi caja fuerte.')) ?>" target="_blank" rel="noopener"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>WhatsApp</a>
    </div>

    <script>
        // Carrusel de fotos de productos (uno para toda la página)
        document.addEventListener('alpine:init', () => {
            Alpine.store('gallery', {
                isOpen: false, images: [], index: 0, title: '', trigger: null, x0: null,
                open(images, title, trigger) {
                    this.images = images; this.title = title; this.index = 0; this.trigger = trigger;
                    this.isOpen = true;
                    document.documentElement.classList.add('no-scroll');
                    setTimeout(() => document.querySelector('.lightbox__close')?.focus(), 50);
                },
                close() {
                    if (!this.isOpen) return;
                    this.isOpen = false;
                    document.documentElement.classList.remove('no-scroll');
                    this.trigger?.focus();
                },
                next() { this.index = (this.index + 1) % this.images.length; },
                prev() { this.index = (this.index - 1 + this.images.length) % this.images.length; },
                touchStart(e) { this.x0 = e.changedTouches[0].clientX; },
                touchEnd(e) {
                    if (this.x0 === null) return;
                    const dx = e.changedTouches[0].clientX - this.x0;
                    this.x0 = null;
                    if (Math.abs(dx) > 40 && this.images.length > 1) dx < 0 ? this.next() : this.prev();
                },
            });
        });

        function contactForm() {
            return {
                form: { name: '', phone: '', body: '', website: '' },
                sending: false, done: false, error: '',
                async send() {
                    if (!this.form.name.trim() || !this.form.phone.trim() || !this.form.body.trim()) {
                        this.error = 'Completa tu nombre, teléfono y lo que necesitas.';
                        return;
                    }
                    this.sending = true; this.error = '';
                    try {
                        const res = await fetch('/api/contact', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(this.form)
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(data.error || 'No se pudo enviar el mensaje. Intenta de nuevo o escríbenos por WhatsApp.');
                        this.form = { name: '', phone: '', body: '', website: '' };
                        this.done = true;
                        this.$nextTick(() => this.$refs.ok && this.$refs.ok.focus());
                        window.rvTrack && rvTrack('generate_lead', { form: 'contacto' });
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.sending = false;
                    }
                }
            }
        }
    </script>
<?php $part = 'close'; include __DIR__ . '/_seo_body.php'; ?>
</body>
</html>
