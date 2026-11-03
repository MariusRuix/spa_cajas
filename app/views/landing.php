<?php
/**
 * Landing pública (maqueta "fresca y juvenil").
 * Todo el contenido viene de la base de datos y se administra en /admin.
 * IMPORTANTE: imprime siempre con e() para evitar XSS.
 *
 * @var array $s        Ajustes del sitio
 * @var array $services Servicios activos
 * @var array $products Productos activos
 * @var string $csrf
 */
$phoneHref = 'tel:+' . preg_replace('/\D+/', '', $s['phone_display'] ?? '');
$brandFull = trim(($s['brand_name'] ?? '') . ' ' . ($s['brand_highlight'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <title><?= e($s['site_title'] ?? '') ?></title>
<?php include __DIR__ . '/_favicons.php'; ?>
<?php include __DIR__ . '/_seo_head.php'; ?>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        indigo: { 50: '#eef2ff', 100: '#e0e7ff', 600: '#6366f1', 700: '#4f46e5', 900: '#312e81' },
                        whatsapp: { DEFAULT: '#25d366', hover: '#22c55e' }
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="/assets/vendor/alpine.min.js"></script>
    <style>[x-cloak]{display:none !important}</style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased selection:bg-indigo-600 selection:text-white">
<?php $part = 'open'; include __DIR__ . '/_seo_body.php'; ?>

    <!-- Barra Superior de Emergencias 24/7 -->
    <div class="bg-indigo-900 text-white text-xs sm:text-sm py-2 px-4">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-whatsapp animate-ping"></span>
                <span class="font-medium"><?= e($s['topbar_text'] ?? '') ?></span>
            </div>
            <a href="<?= e($phoneHref) ?>" class="flex items-center gap-2 font-bold text-indigo-200 hover:text-white transition">
                <i class="fa-solid fa-phone-volume text-sm"></i> <?= e($s['phone_display'] ?? '') ?>
            </a>
        </div>
    </div>

    <!-- Header / Navegación -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="#inicio" class="flex items-center gap-3">
                <img src="/assets/img/logo-mark.jpg" alt="<?= e($brandFull) ?>" width="48" height="48" class="w-12 h-12 rounded-2xl shadow-lg shadow-slate-900/30">
                <div>
                    <span class="text-xl font-black tracking-tight text-slate-900 block leading-none"><?= e($s['brand_name'] ?? '') ?> <span class="text-indigo-600"><?= e($s['brand_highlight'] ?? '') ?></span></span>
                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest"><?= e($s['brand_tagline'] ?? '') ?></span>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                <a href="#inicio" class="hover:text-indigo-600 transition">Inicio</a>
                <a href="#servicios" class="hover:text-indigo-600 transition">Servicios</a>
                <a href="#catalogo" class="hover:text-indigo-600 transition">Cajas Fuertes</a>
                <a href="#nosotros" class="hover:text-indigo-600 transition">Confianza</a>
                <a href="#ubicacion" class="hover:text-indigo-600 transition">Ubicación</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="<?= e(wa_link('Hola, necesito información sobre sus servicios de cajas fuertes.')) ?>" target="_blank" rel="noopener" class="bg-whatsapp hover:bg-whatsapp-hover text-white font-bold px-4 py-2.5 rounded-xl text-sm flex items-center gap-2 shadow-md shadow-whatsapp/20 transition transform hover:-translate-y-0.5">
                    <i class="fa-brands fa-whatsapp text-lg"></i>
                    <span class="hidden sm:inline">WhatsApp 24/7</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="inicio" class="relative overflow-hidden py-20 lg:py-28 bg-gradient-to-b from-indigo-50/60 to-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-12 gap-12 items-center">
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <span class="inline-flex items-center gap-2 bg-indigo-100 text-indigo-700 px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-shield-halved"></i> <?= e($s['hero_badge'] ?? '') ?>
                </span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-slate-900 leading-tight">
                    <?= e($s['hero_title'] ?? '') ?> <span class="text-indigo-600"><?= e($s['hero_highlight'] ?? '') ?></span>
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                    <?= e($s['hero_text'] ?? '') ?>
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-4">
                    <a href="#catalogo" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-8 py-4 rounded-2xl shadow-xl shadow-indigo-600/30 transition transform hover:-translate-y-0.5 text-center flex items-center justify-center gap-2">
                        <i class="fa-solid fa-box-open"></i> Ver Catálogo
                    </a>
                    <a href="<?= e(wa_link('Hola, necesito asistencia urgente.')) ?>" target="_blank" rel="noopener" class="w-full sm:w-auto bg-white hover:bg-slate-100 text-slate-800 border border-slate-200 font-bold px-8 py-4 rounded-2xl shadow-sm transition text-center flex items-center justify-center gap-2">
                        <i class="fa-solid fa-wrench text-indigo-600"></i> Servicio Urgente
                    </a>
                </div>
                <div class="pt-6 grid grid-cols-3 gap-4 border-t border-slate-200 text-center lg:text-left">
                    <?php foreach ([1, 2, 3] as $n): ?>
                    <div>
                        <span class="block text-2xl font-black text-slate-900"><?= e($s["stat_{$n}_value"] ?? '') ?></span>
                        <span class="text-xs text-slate-500 font-medium"><?= e($s["stat_{$n}_label"] ?? '') ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="lg:col-span-5 flex justify-center">
                <div class="relative w-full max-w-md bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xl shadow-slate-200/50">
                    <div class="absolute -top-4 -right-4 bg-whatsapp text-white p-3 rounded-2xl shadow-lg shadow-whatsapp/30">
                        <i class="fa-brands fa-whatsapp text-2xl"></i>
                    </div>
                    <div class="h-64 bg-[#020b1d] rounded-2xl mb-6 relative overflow-hidden group">
                        <img src="/assets/img/logo-full.jpg" alt="Logotipo <?= e($brandFull) ?>" width="900" height="677" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    </div>
                    <h3 class="font-bold text-xl text-slate-900 mb-1">Asistencia Técnica Inmediata</h3>
                    <p class="text-sm text-slate-600 mb-6">¿Problemas con la combinación o cerradura digital? Un técnico especializado acude a tu ubicación.</p>
                    <a href="<?= e(wa_link('Hola, tengo una emergencia con mi caja fuerte.')) ?>" target="_blank" rel="noopener" class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 transition shadow">
                        <i class="fa-solid fa-headset"></i> Contactar Técnico Ahora
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Servicios Técnicos -->
    <?php if ($services): ?>
    <section id="servicios" class="py-20 bg-white border-y border-slate-200/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-indigo-600 text-xs font-bold uppercase tracking-widest block mb-2">Especialistas en Campo</span>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mb-4">Servicios Técnicos Profesionales</h2>
                <p class="text-slate-600 text-sm sm:text-base">Garantizamos la integridad de tus equipos de seguridad con herramientas de última generación y total confidencialidad.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <?php foreach ($services as $sv): ?>
                <div class="bg-slate-50 border border-slate-200/80 p-8 rounded-3xl hover:shadow-xl hover:border-indigo-200 transition group flex flex-col justify-between">
                    <div>
                        <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-indigo-600 group-hover:text-white transition">
                            <i class="<?= e($sv['icon']) ?>"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3"><?= e($sv['title']) ?></h3>
                        <p class="text-slate-600 text-sm leading-relaxed mb-6"><?= e($sv['description']) ?></p>
                    </div>
                    <a href="<?= e(wa_link($sv['wa_message'] ?: 'Hola, me interesa: ' . $sv['title'])) ?>" target="_blank" rel="noopener" class="text-indigo-600 font-bold text-sm flex items-center gap-2 hover:text-indigo-700">
                        <?= e($sv['cta_label']) ?> <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Catálogo de Cajas Fuertes -->
    <section id="catalogo" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12">
                <div>
                    <span class="text-indigo-600 text-xs font-bold uppercase tracking-widest block mb-2">Alta Seguridad</span>
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-900">Catálogo de Cajas Fuertes</h2>
                </div>
                <p class="text-slate-600 text-sm mt-3 md:mt-0 max-w-md">
                    Selecciona el equipo ideal para tu hogar u oficina. Cotiza directamente por WhatsApp con un solo clic.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($products as $p): ?>
                <?php $features = array_filter(array_map('trim', explode("\n", $p['features']))); ?>
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-sm hover:shadow-xl transition flex flex-col justify-between">
                    <div>
                        <div class="relative h-48 bg-indigo-50 rounded-2xl mb-4 flex items-center justify-center text-indigo-600 overflow-hidden">
                            <?php if (safe_image($p['image_url'])): ?>
                                <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" class="w-full h-full object-cover" loading="lazy">
                            <?php else: ?>
                                <i class="<?= e($p['icon']) ?> text-6xl"></i>
                            <?php endif; ?>
                            <?php if ($p['badge'] !== ''): ?>
                            <span class="absolute top-3 right-3 <?= $p['badge_style'] === 'dark' ? 'bg-slate-900' : 'bg-indigo-600' ?> text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase"><?= e($p['badge']) ?></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-1"><?= e($p['name']) ?></h3>
                        <p class="text-xs text-slate-500 mb-4"><?= e($p['description']) ?></p>
                        <?php if ($features): ?>
                        <ul class="space-y-2 text-xs text-slate-600 mb-6">
                            <?php foreach ($features as $f): ?>
                            <li><i class="fa-solid fa-check text-indigo-600 mr-2"></i> <?= e($f) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="<?= e(wa_link('Hola, me interesa cotizar el modelo: ' . $p['name'])) ?>" target="_blank" rel="noopener" class="w-full bg-whatsapp hover:bg-whatsapp-hover text-white font-bold py-3 rounded-xl text-sm flex items-center justify-center gap-2 transition shadow-md shadow-whatsapp/20">
                            <i class="fa-brands fa-whatsapp text-lg"></i> Cotizar por WhatsApp
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (!$products): ?>
                <p class="text-slate-500 text-sm col-span-full text-center">Muy pronto publicaremos nuestro catálogo. Escríbenos por WhatsApp para cotizar.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Sección de Confianza y Contacto -->
    <section id="nosotros" class="py-20 bg-white border-t border-slate-200/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-indigo-600 text-xs font-bold uppercase tracking-widest block mb-2">Por qué Elegirnos</span>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mb-6">Confianza, Discreción y Soluciones Reales</h2>
                <p class="text-slate-600 mb-8 leading-relaxed">
                    En <strong class="text-slate-900"><?= e($brandFull) ?></strong> <?= e(lcfirst($s['about_text'] ?? '')) ?>
                </p>
                <div class="space-y-4 mb-8">
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs shrink-0 mt-0.5"><i class="fa-solid fa-check"></i></div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm">Protocolo de Confidencialidad Absoluta</h4>
                            <p class="text-xs text-slate-500">Tus bienes y datos están protegidos bajo estrictas normas de privacidad.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs shrink-0 mt-0.5"><i class="fa-solid fa-check"></i></div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm">Repuestos Originales y Homologados</h4>
                            <p class="text-xs text-slate-500">Trabajamos con componentes certificados de fábrica para mayor durabilidad.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Contacto (guarda en la BD vía /api/contact) -->
            <div class="bg-slate-50 border border-slate-200/80 p-8 rounded-3xl shadow-sm" x-data="contactForm()">
                <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-envelope text-indigo-600"></i> Envíanos un Mensaje
                </h3>
                <form class="space-y-4" @submit.prevent="send">
                    <!-- Honeypot anti-bots: debe quedar vacío -->
                    <input type="text" name="website" x-model="form.website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Nombre Completo</label>
                        <input type="text" required maxlength="100" x-model="form.name" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600" placeholder="Ej. María García">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Teléfono o WhatsApp</label>
                        <input type="tel" required maxlength="30" x-model="form.phone" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600" placeholder="Ej. 600 123 456">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">¿Qué servicio o modelo necesitas?</label>
                        <textarea rows="3" required maxlength="2000" x-model="form.body" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-indigo-600" placeholder="Describe brevemente tu requerimiento..."></textarea>
                    </div>
                    <p x-show="error" x-text="error" x-cloak class="text-sm text-red-600 font-medium"></p>
                    <button type="submit" :disabled="sending" class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-bold py-3.5 rounded-xl text-sm transition shadow-lg shadow-indigo-600/20">
                        <span x-text="sending ? 'Enviando…' : 'Enviar Solicitud'"></span>
                    </button>
                </form>

                <!-- Mensaje de confirmación (sin alert) -->
                <div x-show="done" x-cloak x-transition.opacity class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl text-center space-y-4" @click.outside="done = false">
                        <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center text-2xl mx-auto">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900">¡Éxito!</h3>
                        <p class="text-sm text-slate-600">¡Mensaje enviado con éxito! Nos pondremos en contacto contigo de inmediato.</p>
                        <button type="button" @click="done = false" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl text-sm transition">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Ubicación y Mapa -->
    <section id="ubicacion" class="py-20 bg-slate-50 border-t border-slate-200/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-indigo-600 text-xs font-bold uppercase tracking-widest block mb-2">Visítanos</span>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mb-4">Ubicación y Cobertura</h2>
                <p class="text-slate-600 text-sm sm:text-base">Acude a nuestra tienda física o solicita asistencia móvil en toda la región.</p>
            </div>

            <div class="grid lg:grid-cols-2 gap-8 items-stretch">
                <div class="bg-white border border-slate-200/80 p-8 rounded-3xl shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-6">Datos del Taller y Tienda</h3>
                        <ul class="space-y-6 text-slate-600 text-sm">
                            <li class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shrink-0">
                                    <i class="fa-solid fa-location-dot"></i>
                                </div>
                                <div>
                                    <strong class="block text-slate-900">Dirección Principal:</strong>
                                    <span><?= e($s['address'] ?? '') ?></span>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shrink-0">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div>
                                    <strong class="block text-slate-900">Horarios:</strong>
                                    <span><?= nl2br(e($s['hours'] ?? '')) ?><br><strong class="text-indigo-600">Servicio de Emergencias 24/7</strong></span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <?php if (safe_url($s['maps_url'] ?? '')): ?>
                    <div class="mt-8 pt-6 border-t border-slate-100">
                        <a href="<?= e($s['maps_url']) ?>" target="_blank" rel="noopener" class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-semibold py-3 rounded-xl text-sm text-center block transition">
                            <i class="fa-solid fa-map-location-dot mr-2"></i> Abrir en Google Maps
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="rounded-3xl overflow-hidden border border-slate-200/80 h-[380px] lg:h-auto bg-slate-200">
                    <?php if (safe_url($s['maps_embed'] ?? '')): ?>
                    <iframe src="<?= e($s['maps_embed']) ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Mapa de ubicación"></iframe>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-12 border-t border-slate-800 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <img src="/assets/img/logo-mark.jpg" alt="" width="36" height="36" class="w-9 h-9 rounded-xl">
                <span class="font-bold text-white tracking-tight"><?= e(mb_strtoupper($brandFull)) ?></span>
            </div>
            <p class="text-xs text-center">© <?= date('Y') ?> <?= e($brandFull) ?>. Todos los derechos reservados. <?= e($s['footer_text'] ?? '') ?></p>
            <div class="flex gap-4 text-slate-300">
                <?php if (safe_url($s['facebook_url'] ?? '')): ?>
                <a href="<?= e($s['facebook_url']) ?>" target="_blank" rel="noopener" class="hover:text-indigo-400 transition" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <?php endif; ?>
                <?php if (safe_url($s['instagram_url'] ?? '')): ?>
                <a href="<?= e($s['instagram_url']) ?>" target="_blank" rel="noopener" class="hover:text-indigo-400 transition" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <?php endif; ?>
                <a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener" class="hover:text-whatsapp transition" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp text-lg"></i></a>
            </div>
        </div>
    </footer>

    <!-- Botón Flotante de WhatsApp -->
    <a href="<?= e(wa_link('Hola, necesito asistencia urgente o información.')) ?>" target="_blank" rel="noopener" class="fixed bottom-6 right-6 z-50 bg-whatsapp hover:bg-whatsapp-hover text-white w-16 h-16 rounded-full flex items-center justify-center text-3xl shadow-2xl transition transform hover:scale-110 group" aria-label="WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
        <span class="absolute right-20 bg-slate-900 text-white text-xs font-semibold px-3 py-1.5 rounded-xl shadow-xl opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap border border-slate-800">
            ¡Escríbenos por WhatsApp!
        </span>
    </a>

    <script>
        function contactForm() {
            return {
                form: { name: '', phone: '', body: '', website: '' },
                sending: false, done: false, error: '',
                async send() {
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
                        if (!res.ok) throw new Error(data.error || 'No se pudo enviar el mensaje.');
                        this.form = { name: '', phone: '', body: '', website: '' };
                        this.done = true;
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
