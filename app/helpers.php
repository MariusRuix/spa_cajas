<?php
declare(strict_types=1);

/** Conexión única a la base de datos. */
function db(): PDO
{
    static $pdo = null;
    return $pdo ??= Database::connect(Flight::get('config')['db_path']);
}

/** Escapa texto para imprimirlo en HTML (protección XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Todos los ajustes como arreglo clave => valor. */
function settings(bool $reload = false): array
{
    static $cache = null;
    if ($cache === null || $reload) {
        $cache = db()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache;
}

function set_setting(string $key, string $value): void
{
    db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
        ->execute([$key, $value]);
    settings(true);
}

/** Llave de cifrado derivada de app_key (config.local.php). */
function secret_key(): string
{
    $cfg = Flight::get('config');
    $base = ($cfg['app_key'] ?? '') !== '' ? $cfg['app_key'] : ($cfg['setup_key'] ?? '');
    if ($base === '') {
        throw new RuntimeException('Falta app_key en app/config.local.php.');
    }
    return hash('sha256', $base, true);
}

/** Cifra un secreto (AES-256-GCM) con app_key de config.local.php. */
function secret_encrypt(string $plain): string
{
    $key = secret_key();
    $iv  = random_bytes(12);
    $ct  = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return 'enc:' . base64_encode($iv . $tag . $ct);
}

function secret_decrypt(string $stored): string
{
    if (!str_starts_with($stored, 'enc:')) {
        return $stored;
    }
    $key = secret_key();
    $raw = base64_decode(substr($stored, 4), true) ?: '';
    if (strlen($raw) < 29) {
        return '';
    }
    $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    return $plain === false ? '' : $plain;
}

/** Enlace de WhatsApp con mensaje prellenado. */
function wa_link(string $message = ''): string
{
    $number = preg_replace('/\D+/', '', settings()['whatsapp'] ?? '');
    return 'https://wa.me/' . $number . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}

/** Solo permite URLs http(s); evita javascript: y similares. */
function safe_url(?string $url): string
{
    $url = trim((string) $url);
    return preg_match('#^https?://#i', $url) ? $url : '';
}

function csrf_token(): string
{
    return $_SESSION['csrf'];
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Datos del cuerpo de la petición (JSON o formulario). */
function body(): array
{
    return Flight::request()->data->getData();
}

// ---- Seguridad -------------------------------------------------------------

/** Verifica el token CSRF enviado en el header X-CSRF-Token. */
function require_csrf(): void
{
    $sent = Flight::request()->getHeader('X-CSRF-Token');
    if ($sent === '' || !hash_equals($_SESSION['csrf'], $sent)) {
        Flight::jsonHalt(['error' => 'Token CSRF inválido. Recarga la página.'], 403);
    }
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $st = db()->prepare('SELECT id, name, email, role, session_version FROM users WHERE id = ?');
    $st->execute([$_SESSION['user_id']]);
    $user = $st->fetch();
    // Usuario eliminado, o su contraseña/rol cambió desde otra sesión: se cierra esta sesión.
    if (!$user || (int) ($_SESSION['uv'] ?? 0) !== (int) $user['session_version']) {
        unset($_SESSION['user_id'], $_SESSION['uv']);
        return null;
    }
    unset($user['session_version']);
    return $user;
}

/** Inicia la sesión de un usuario (tras login o instalación). */
function login_user(int $id): void
{
    session_regenerate_id(true);   // evita fijación de sesión
    $st = db()->prepare('SELECT session_version FROM users WHERE id = ?');
    $st->execute([$id]);
    $_SESSION['user_id'] = $id;
    $_SESSION['uv'] = (int) $st->fetchColumn();
}

/** Corta la petición si no hay sesión de administrador. */
function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        Flight::jsonHalt(['error' => 'No autenticado'], 401);
    }
    return $user;
}

/** Roles del panel. admin: todo. editor: mensajes, catálogo y servicios. */
function roles(): array
{
    return ['admin' => 'Administrador', 'editor' => 'Editor'];
}

/** Rutas de /api/admin que solo puede usar un administrador. */
function admin_only_path(string $url): bool
{
    return (bool) preg_match('#^/api/admin/(settings|mail|users)(/|\?|$)#', $url);
}

/** ¿Se superó el límite de eventos para esta IP? */
function rate_limited(string $bucket, int $max, int $minutes): bool
{
    $st = db()->prepare("SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND ip = ? AND created_at > datetime('now', ?)");
    $st->execute([$bucket, client_ip(), "-{$minutes} minutes"]);
    return (int) $st->fetchColumn() >= $max;
}

function rate_hit(string $bucket): void
{
    db()->prepare('INSERT INTO rate_limits (bucket, ip) VALUES (?, ?)')->execute([$bucket, client_ip()]);
    // Limpieza ocasional de registros viejos
    if (random_int(1, 50) === 1) {
        db()->exec("DELETE FROM rate_limits WHERE created_at < datetime('now', '-1 day')");
    }
}

function rate_clear(string $bucket): void
{
    db()->prepare('DELETE FROM rate_limits WHERE bucket = ? AND ip = ?')->execute([$bucket, client_ip()]);
}

// ---- Validación genérica ---------------------------------------------------

/**
 * Valida y limpia $data según $fields.
 * Tipos: text (max), int, bool, url, enum (options).
 * Devuelve [datosLimpios, errores].
 */
function validate(array $fields, array $data): array
{
    $clean = [];
    $errors = [];
    foreach ($fields as $name => $rule) {
        $value = $data[$name] ?? null;
        $label = $rule['label'] ?? $name;
        switch ($rule['type']) {
            case 'int':
                $clean[$name] = (int) $value;
                break;
            case 'bool':
                $clean[$name] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                break;
            case 'enum':
                $value = (string) $value;
                if (!in_array($value, $rule['options'], true)) {
                    $value = $rule['options'][0];
                }
                $clean[$name] = $value;
                break;
            case 'url':
                $value = trim((string) $value);
                if ($value !== '' && safe_url($value) === '') {
                    $errors[$name] = "$label debe empezar con http:// o https://";
                }
                $clean[$name] = $value;
                break;
            case 'secret': // contraseñas: vacío = no cambiar (se maneja en la ruta)
                $value = (string) $value;
                if (strlen($value) > 200) {
                    $errors[$name] = "$label es demasiado larga";
                }
                $clean[$name] = $value;
                break;
            case 'image': // URL externa (https://…) o archivo subido (/uploads/…)
                $value = trim((string) $value);
                if ($value !== '' && safe_image($value) === '') {
                    $errors[$name] = "$label debe ser una URL https:// o una imagen subida";
                }
                $clean[$name] = $value;
                break;
            case 'regex': // identificadores (GA4, GTM, píxeles…): vacío o con formato exacto
                $value = trim((string) $value);
                if ($value !== '' && !preg_match($rule['pattern'], $value)) {
                    $errors[$name] = "$label no tiene un formato válido" . (isset($rule['example']) ? " (ej. {$rule['example']})" : '');
                }
                $clean[$name] = $value;
                break;
            default: // text
                $value = trim((string) $value);
                if (!empty($rule['required']) && $value === '') {
                    $errors[$name] = "$label es obligatorio";
                }
                if (mb_strlen($value) > ($rule['max'] ?? 500)) {
                    $errors[$name] = "$label es demasiado largo (máx. " . ($rule['max'] ?? 500) . ')';
                }
                $clean[$name] = $value;
        }
    }
    return [$clean, $errors];
}

/**
 * Recursos con CRUD genérico en el dashboard.
 * Para agregar uno nuevo: crea la tabla en Database.php y descríbelo aquí
 * y en public/assets/js/admin.js (RESOURCES).
 */
function resources(): array
{
    return [
        'products' => [
            'table'  => 'products',
            'gallery' => true,   // varias fotos por producto (tabla product_images)
            'fields' => [
                'name'        => ['type' => 'text', 'label' => 'Nombre', 'required' => true, 'max' => 120],
                'description' => ['type' => 'text', 'label' => 'Descripción', 'max' => 400],
                'badge'       => ['type' => 'text', 'label' => 'Etiqueta', 'max' => 40],
                'badge_style' => ['type' => 'enum', 'options' => ['primary', 'dark']],
                'features'    => ['type' => 'text', 'label' => 'Características', 'max' => 1000],
                'icon'        => ['type' => 'text', 'label' => 'Icono', 'max' => 60],
                'sort'        => ['type' => 'int'],
                'active'      => ['type' => 'bool'],
            ],
        ],
        'services' => [
            'table'  => 'services',
            'fields' => [
                'title'       => ['type' => 'text', 'label' => 'Título', 'required' => true, 'max' => 120],
                'description' => ['type' => 'text', 'label' => 'Descripción', 'max' => 600],
                'icon'        => ['type' => 'text', 'label' => 'Icono', 'max' => 60],
                'cta_label'   => ['type' => 'text', 'label' => 'Texto del botón', 'max' => 60],
                'wa_message'  => ['type' => 'text', 'label' => 'Mensaje de WhatsApp', 'max' => 300],
                'sort'        => ['type' => 'int'],
                'active'      => ['type' => 'bool'],
            ],
        ],
    ];
}

/** Campos editables en "Configuración del sitio". */
function setting_fields(): array
{
    $text = fn(string $label, int $max = 200) => ['type' => 'text', 'label' => $label, 'max' => $max];
    return [
        'site_title'      => $text('Título de la pestaña'),
        'brand_name'      => $text('Marca', 40),
        'brand_highlight' => $text('Marca (resaltado)', 40),
        'brand_tagline'   => $text('Lema', 80),
        'topbar_text'     => $text('Texto barra superior'),
        'phone_display'   => $text('Teléfono visible', 40),
        'whatsapp'        => $text('WhatsApp (solo números con lada)', 20),
        'hero_badge'      => $text('Etiqueta portada', 80),
        'hero_title'      => $text('Título portada'),
        'hero_highlight'  => $text('Título portada (resaltado)'),
        'hero_text'       => $text('Texto portada', 600),
        'about_text'      => $text('Texto "Por qué elegirnos" (va después de "En MARCA…")', 800),
        'address'         => $text('Dirección', 200),
        'hours'           => $text('Horarios', 300),
        'maps_url'        => ['type' => 'url', 'label' => 'Enlace Google Maps'],
        'maps_embed'      => ['type' => 'url', 'label' => 'URL del mapa incrustado'],
        'facebook_url'    => ['type' => 'url', 'label' => 'Facebook'],
        'instagram_url'   => ['type' => 'url', 'label' => 'Instagram'],
        'footer_text'     => $text('Texto del pie', 200),

        // ---- SEO ----
        'site_url'         => ['type' => 'url', 'label' => 'URL pública del sitio', 'hint' => 'Ej. https://royalvault.mx — se usa en canonical, sitemap y redes sociales. Si la dejas vacía se detecta sola.'],
        'seo_description'  => $text('Meta descripción (Google)', 300) + ['hint' => 'Ideal 140–160 caracteres. Es el texto bajo el título en los resultados de Google.'],
        'seo_keywords'     => $text('Palabras clave (opcional)', 300) + ['hint' => 'Separadas por coma. Google casi no las usa; Bing un poco.'],
        'seo_index'        => ['type' => 'bool', 'label' => 'Permitir que Google indexe el sitio', 'hint' => 'Apágalo mientras el sitio está en pruebas.'],
        'og_title'         => $text('Título al compartir (Facebook/WhatsApp)', 120) + ['hint' => 'Vacío = usa el título de la pestaña.'],
        'og_description'   => $text('Descripción al compartir', 300) + ['hint' => 'Vacío = usa la meta descripción.'],
        'og_image'         => ['type' => 'url', 'label' => 'Imagen al compartir (URL)', 'hint' => 'Recomendado 1200×630. Vacío = usa el logotipo.'],
        'business_city'    => $text('Ciudad (datos estructurados)', 80),
        'business_region'  => $text('Estado', 80),
        'business_postal'  => $text('Código postal', 10),
        'business_country' => $text('País (código ISO, ej. MX)', 2),

        // ---- Analítica y seguimiento ----
        'ga4_id'             => ['type' => 'regex', 'label' => 'Google Analytics 4', 'pattern' => '/^G-[A-Z0-9]{4,20}$/', 'example' => 'G-ABC123XYZ', 'hint' => 'ID de medición: Analytics → Administrar → Flujos de datos.'],
        'gtm_id'             => ['type' => 'regex', 'label' => 'Google Tag Manager', 'pattern' => '/^GTM-[A-Z0-9]{4,12}$/', 'example' => 'GTM-AB12CD3', 'hint' => 'Si usas GTM, puedes poner GA4 dentro de GTM y dejar vacío el campo de arriba.'],
        'gads_id'            => ['type' => 'regex', 'label' => 'Google Ads (conversiones)', 'pattern' => '/^AW-\d{6,15}$/', 'example' => 'AW-123456789'],
        'google_verification'=> ['type' => 'regex', 'label' => 'Verificación Google Search Console', 'pattern' => '/^[A-Za-z0-9_\-]{10,100}$/', 'example' => 'abc123…', 'hint' => 'Solo el valor de <code>content</code> de la etiqueta meta que te da Search Console.'],
        'bing_verification'  => ['type' => 'regex', 'label' => 'Verificación Bing Webmaster', 'pattern' => '/^[A-Za-z0-9]{16,64}$/', 'example' => '1A2B3C…'],
        'meta_pixel_id'      => ['type' => 'regex', 'label' => 'Meta Pixel (Facebook/Instagram)', 'pattern' => '/^\d{6,20}$/', 'example' => '123456789012345'],
        'tiktok_pixel_id'    => ['type' => 'regex', 'label' => 'TikTok Pixel', 'pattern' => '/^[A-Z0-9]{10,30}$/', 'example' => 'C4ABCDEF123456'],

        // ---- Código personalizado (solo administradores de confianza) ----
        'custom_head' => $text('Código adicional en <head>', 10000) + ['hint' => 'Se inserta tal cual. Úsalo solo con código de fuentes confiables (chat, Clarity, Hotjar…).'],
        'custom_body' => $text('Código adicional antes de </body>', 10000),

        // ---- Correo de notificación (SMTP) ----
        'mail_enabled'   => ['type' => 'bool', 'label' => 'Enviar un correo cada vez que llegue un mensaje del formulario'],
        'mail_to'        => $text('Enviar notificaciones a', 300) + ['hint' => 'Uno o varios correos separados por coma.'],
        'mail_from_name' => $text('Nombre del remitente', 80) + ['hint' => 'Vacío = nombre de la marca.'],
        'smtp_host'      => $text('Servidor SMTP', 120) + ['example' => 'smtp.gmail.com'],
        'smtp_port'      => ['type' => 'int', 'label' => 'Puerto'],
        'smtp_secure'    => ['type' => 'enum', 'label' => 'Seguridad', 'options' => ['tls', 'ssl', 'none'], 'labels' => ['STARTTLS (587)', 'SSL/TLS (465)', 'Ninguna']],
        'smtp_user'      => $text('Usuario / correo de la cuenta', 120) + ['example' => 'tucuenta@gmail.com', 'hint' => 'También se usa como remitente.'],
        'smtp_pass'      => ['type' => 'secret', 'label' => 'Contraseña', 'hint' => 'En Gmail usa una <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">contraseña de aplicación</a> (requiere verificación en 2 pasos), no tu contraseña normal. Se guarda cifrada; déjala vacía para conservar la actual.'],
    ];
}

/** Valores por defecto de los ajustes agregados después de la primera versión. */
function setting_defaults(): array
{
    return [
        'site_url' => '', 'seo_description' => 'Venta de cajas fuertes y cajas de seguridad en Mérida, Yucatán. Aperturas de emergencia 24/7, reparación, mantenimiento e instalación con garantía.',
        'seo_keywords' => 'cajas fuertes, cajas de seguridad, apertura de cajas fuertes, cerrajería, Mérida, Yucatán',
        'seo_index' => '1', 'og_title' => '', 'og_description' => '', 'og_image' => '',
        'business_city' => 'Mérida', 'business_region' => 'Yucatán', 'business_postal' => '', 'business_country' => 'MX',
        'ga4_id' => '', 'gtm_id' => '', 'gads_id' => '', 'google_verification' => '', 'bing_verification' => '',
        'meta_pixel_id' => '', 'tiktok_pixel_id' => '', 'custom_head' => '', 'custom_body' => '',
        'mail_enabled' => '0', 'mail_to' => '', 'mail_from_name' => '', 'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => '587', 'smtp_secure' => 'tls', 'smtp_user' => '', 'smtp_pass' => '', 'mail_last_error' => '',
    ];
}

/** URL base absoluta: la configurada o la detectada de la petición. */
function base_url(): string
{
    $configured = rtrim(safe_url(settings()['site_url'] ?? ''), '/');
    if ($configured !== '') {
        return $configured;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = preg_replace('/[^A-Za-z0-9.\-:]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    return ($https ? 'https' : 'http') . '://' . $host;
}

/** Convierte una ruta relativa (/assets/...) en URL absoluta. */
function abs_url(string $pathOrUrl): string
{
    return safe_url($pathOrUrl) !== '' ? $pathOrUrl : base_url() . '/' . ltrim($pathOrUrl, '/');
}

// ---- Imágenes subidas ------------------------------------------------------

const UPLOAD_MAX_BYTES = 5 * 1024 * 1024;  // 5 MB
const UPLOAD_MAX_SIDE  = 1600;              // px, se redimensiona si es mayor

/** Devuelve la imagen si es una URL http(s) o un archivo de /uploads; si no, ''. */
function safe_image(?string $value): string
{
    $value = trim((string) $value);
    if (safe_url($value) !== '') {
        return $value;
    }
    return preg_match('#^/uploads/[a-z0-9_\-/]+\.(jpe?g|png|webp|gif)$#i', $value) && !str_contains($value, '..') ? $value : '';
}

function is_local_upload(?string $value): bool
{
    return safe_image($value) !== '' && str_starts_with((string) $value, '/uploads/');
}

/** Borra un archivo subido (solo si está dentro de la carpeta pública uploads/). */
function delete_upload(?string $value): void
{
    if (!is_local_upload($value)) {
        return;
    }
    $base = realpath(PUBLIC_PATH . '/uploads');
    $file = realpath(PUBLIC_PATH . $value);
    if ($base && $file && str_starts_with($file, $base . DIRECTORY_SEPARATOR) && is_file($file)) {
        @unlink($file);
    }
}

/**
 * Valida y guarda una imagen subida. Con GD la vuelve a generar (quita metadatos/EXIF,
 * neutraliza archivos disfrazados y la reduce a UPLOAD_MAX_SIDE).
 * @return string Ruta pública, ej. /uploads/products/2026/10/ab12cd.webp
 * @throws RuntimeException con un mensaje para el usuario
 */
function store_upload(array $file, string $folder): string
{
    $errors = [
        UPLOAD_ERR_INI_SIZE  => 'La imagen supera el límite del servidor (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE => 'La imagen es demasiado grande.',
        UPLOAD_ERR_PARTIAL   => 'La imagen se subió incompleta, intenta de nuevo.',
        UPLOAD_ERR_NO_FILE   => 'No se recibió ninguna imagen.',
    ];
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err !== UPLOAD_ERR_OK) {
        throw new RuntimeException($errors[$err] ?? 'No se pudo subir la imagen.');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Archivo no válido.');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('La imagen pesa más de 5 MB.');
    }

    // Tipo real según el contenido, no según la extensión
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime]) || !($info = @getimagesize($file['tmp_name']))) {
        throw new RuntimeException('Formato no permitido. Usa JPG, PNG, WEBP o GIF.');
    }
    [$w, $h] = $info;
    if ($w < 50 || $h < 50 || $w * $h > 40_000_000) {
        throw new RuntimeException('Dimensiones de imagen no válidas.');
    }

    $folder = preg_replace('/[^a-z0-9_\-]/', '', strtolower($folder)) ?: 'misc';
    $relDir = "/uploads/$folder/" . gmdate('Y/m');
    $absDir = PUBLIC_PATH . $relDir;
    if (!is_dir($absDir) && !mkdir($absDir, 0755, true)) {
        throw new RuntimeException('No se pudo crear la carpeta de imágenes (revisa permisos de la carpeta uploads).');
    }
    $name = bin2hex(random_bytes(8));

    $gd = extension_loaded('gd') && $mime !== 'image/gif'; // GIF se guarda tal cual (puede ser animado)
    if ($gd) {
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file['tmp_name']) : false,
        };
        if (!$src) {
            throw new RuntimeException('La imagen está dañada o no se puede leer.');
        }
        // Respeta la orientación de fotos de celular
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $o = (int) (@exif_read_data($file['tmp_name'])['Orientation'] ?? 1);
            $rot = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
            if ($rot) {
                $src = imagerotate($src, $rot, 0);
                [$w, $h] = [imagesx($src), imagesy($src)];
            }
        }
        $scale = min(1, UPLOAD_MAX_SIDE / max($w, $h));
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        if (function_exists('imagewebp')) {
            $ext = 'webp';
            $ok = imagewebp($dst, "$absDir/$name.webp", 82);
        } elseif ($mime === 'image/png') {
            $ext = 'png';
            $ok = imagepng($dst, "$absDir/$name.png", 6);
        } else {
            $ext = 'jpg';
            $ok = imagejpeg($dst, "$absDir/$name.jpg", 85);
        }
        imagedestroy($src);
        imagedestroy($dst);
        if (!$ok) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }
    } else {
        $ext = $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], "$absDir/$name.$ext")) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }
    }
    @chmod("$absDir/$name.$ext", 0644);
    return "$relDir/$name.$ext";
}

// ---- Galería de fotos por producto ------------------------------------------

const GALLERY_MAX = 20;

/** Fotos de todos los productos: [product_id => [ruta, ...]] en su orden. */
function gallery_map(): array
{
    $map = [];
    foreach (db()->query('SELECT product_id, path FROM product_images ORDER BY product_id, sort, id') as $r) {
        $map[(int) $r['product_id']][] = $r['path'];
    }
    return $map;
}

function gallery_get(int $productId): array
{
    $st = db()->prepare('SELECT path FROM product_images WHERE product_id = ? ORDER BY sort, id');
    $st->execute([$productId]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

/** Valida la lista de fotos enviada por el panel. Devuelve [rutas, error|null]. */
function gallery_clean($images): array
{
    if (!is_array($images)) {
        return [[], 'La lista de fotos no es válida.'];
    }
    $clean = [];
    foreach ($images as $img) {
        $img = trim((string) $img);
        if ($img === '' || in_array($img, $clean, true)) {
            continue;
        }
        if (safe_image($img) === '') {
            return [[], 'Una de las fotos no es válida (debe ser una imagen subida o una URL https://).'];
        }
        $clean[] = $img;
    }
    if (count($clean) > GALLERY_MAX) {
        return [[], 'Máximo ' . GALLERY_MAX . ' fotos por producto.'];
    }
    return [$clean, null];
}

/**
 * Guarda la galería de un producto en el orden recibido (la primera es la portada),
 * borra del servidor las fotos subidas que se quitaron y actualiza products.image_url.
 */
function gallery_sync(int $productId, array $paths): void
{
    $old = gallery_get($productId);
    $pdo = db();
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$productId]);
    $ins = $pdo->prepare('INSERT INTO product_images (product_id, path, sort) VALUES (?, ?, ?)');
    foreach (array_values($paths) as $i => $path) {
        $ins->execute([$productId, $path, $i]);
    }
    $pdo->prepare("UPDATE products SET image_url = ?, updated_at = datetime('now') WHERE id = ?")
        ->execute([$paths[0] ?? '', $productId]);
    $pdo->commit();
    foreach (array_diff($old, $paths) as $removed) {
        delete_upload($removed);
    }
}
