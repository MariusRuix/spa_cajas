<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
// Carpeta pública (la define public/index.php; en Hostinger es public_html/).
defined('PUBLIC_PATH') || define('PUBLIC_PATH', BASE_PATH . '/public');

require BASE_PATH . '/lib/flight/autoload.php';
require BASE_PATH . '/app/Database.php';
require BASE_PATH . '/app/helpers.php';
require BASE_PATH . '/app/Mailer.php';

// Valores por defecto (se reemplazan en cada despliegue) + valores propios de este servidor
// (app/config.local.php: claves, debug; nunca se sube ni se reemplaza).
$config = require BASE_PATH . '/app/config.php';
if (is_file(BASE_PATH . '/app/config.local.php')) {
    $config = array_replace($config, (array) require BASE_PATH . '/app/config.local.php');
}

// ---- Errores ---------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/php-errors.log');
date_default_timezone_set('UTC');

// ---- Sesión segura ---------------------------------------------------------
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_name($config['session_name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $isHttps,   // en Hostinger con SSL siempre será true
    'httponly' => true,
    'samesite' => 'Strict',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', (string) $config['session_lifetime']);
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
// Caducidad por inactividad
if (isset($_SESSION['user_id'], $_SESSION['last_seen'])
    && time() - $_SESSION['last_seen'] > $config['session_lifetime']) {
    unset($_SESSION['user_id']);
}
$_SESSION['last_seen'] = time();

// ---- Flight ----------------------------------------------------------------
Flight::set('config', $config);
Flight::set('flight.views.path', BASE_PATH . '/app/views');
Flight::set('flight.log_errors', true);

Flight::map('error', function (Throwable $e) use ($config) {
    error_log((string) $e);
    $isApi = str_starts_with(Flight::request()->url, '/api/');
    $msg = $config['debug'] ? $e->getMessage() : 'Error interno del servidor';
    if ($isApi) {
        Flight::jsonHalt(['error' => $msg], 500);
    }
    Flight::halt(500, $config['debug'] ? '<pre>' . e((string) $e) . '</pre>' : 'Error interno del servidor');
});

Flight::map('notFound', function () {
    $path = parse_url(Flight::request()->url, PHP_URL_PATH) ?: '/';
    if (str_starts_with($path, '/api/')) {
        Flight::jsonHalt(['error' => 'No encontrado'], 404);
    }
    // Archivos que no existen (imágenes, css, js…): respuesta corta, sin página completa
    if (preg_match('/\.[a-z0-9]{2,5}$/i', $path) && !preg_match('/\.(html?|php)$/i', $path)) {
        Flight::halt(404, 'No encontrado');
    }
    try {
        $s = settings();
    } catch (Throwable $e) {
        $s = [];
    }
    Flight::response()->status(404);
    Flight::render('404', ['s' => $s]);
    Flight::stop();
});

require BASE_PATH . '/app/routes.php';
