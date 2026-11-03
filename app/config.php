<?php
/**
 * Configuración de Royal Vault.
 * Este archivo NO debe ser accesible desde el navegador (app/.htaccess lo bloquea).
 */
return [
    // Pon false en producción (Hostinger) para no mostrar errores en pantalla.
    'debug' => true,

    // Ruta del archivo SQLite. La carpeta storage/ debe tener permisos de escritura.
    'db_path' => dirname(__DIR__) . '/storage/database.sqlite',

    // Clave para crear el PRIMER usuario administrador en /admin.
    // Solo funciona mientras no exista ningún usuario. Cámbiala por una propia.
    'setup_key' => '15c490c065e852473ab352701cdd7e9a',

    // Llave para cifrar datos sensibles en la BD (contraseña SMTP).
    // Genérala una sola vez; si la cambias tendrás que volver a capturar la contraseña del correo.
    'app_key' => 'b586a1f305255a7b219af0f45c55c4c77f54154bb181fceb9ae1227c9a4e35f4',

    // Sesión
    'session_name' => 'rv_session',
    'session_lifetime' => 60 * 60 * 8, // 8 horas

    // Límite de intentos de login: N intentos fallidos por IP en X minutos.
    'login_max_attempts' => 5,
    'login_window_minutes' => 15,

    // Límite de mensajes del formulario público por IP y por hora.
    'contact_max_per_hour' => 5,
];
