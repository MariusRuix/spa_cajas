<?php
/**
 * Plantilla de configuración LOCAL (propia de cada servidor).
 *
 * 1. Copia este archivo como app/config.local.php (en Laragon y en Hostinger, uno en cada uno).
 * 2. Llena los valores. Solo pon lo que quieras cambiar respecto a app/config.php.
 * 3. config.local.php NUNCA se sube en los despliegues ni se reemplaza: así no pierdes tus claves.
 *
 * Generar claves nuevas (en una terminal con PHP):
 *   php -r "echo bin2hex(random_bytes(16)), PHP_EOL;"   // setup_key
 *   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"   // app_key
 */
return [
    'debug'     => false,   // true solo en Laragon
    'setup_key' => 'CAMBIA-ESTA-CLAVE',
    'app_key'   => 'CAMBIA-ESTA-LLAVE-LARGA',
];
