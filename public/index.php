<?php
/**
 * Front controller: todas las peticiones pasan por aquí.
 *
 * Este archivo vive en la carpeta PÚBLICA del servidor:
 *   - Laragon / desarrollo:  royal-vault/public/
 *   - Hostinger (seguro):    public_html/   (el código queda afuera, en ../royal-vault/)
 *
 * Se busca automáticamente la carpeta del proyecto (la que contiene app/, lib/ y storage/).
 * Si la tuya tiene otro nombre o está en otro lugar, escríbela en $APP_ROOT.
 */
$APP_ROOT = '';   // ej. '/home/u123456789/domains/tudominio.com/royal-vault'

$candidates = array_filter([
    $APP_ROOT,
    dirname(__DIR__),                 // estructura de desarrollo: proyecto/public/index.php
    dirname(__DIR__) . '/royal-vault', // Hostinger: public_html/ junto a royal-vault/
]);
foreach ($candidates as $root) {
    if (is_file($root . '/app/bootstrap.php')) {
        define('PUBLIC_PATH', __DIR__);
        require $root . '/app/bootstrap.php';
        Flight::start();
        return;
    }
}

http_response_code(500);
echo 'No se encontró la carpeta del proyecto (app/bootstrap.php). Revisa $APP_ROOT en index.php.';
