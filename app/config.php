<?php
/**
 * Configuración POR DEFECTO de Royal Vault.
 *
 * Este archivo se puede reemplazar en cada despliegue: NO pongas aquí claves ni valores
 * propios de un servidor. Lo que cambie entre Laragon y Hostinger va en
 * app/config.local.php (ver app/config.local.example.php), que nunca se sube ni se reemplaza.
 *
 * Este archivo NO debe ser accesible desde el navegador (app/.htaccess lo bloquea).
 */
return [
    // En producción va en false. En Laragon pon 'debug' => true en config.local.php.
    'debug' => false,

    // Ruta del archivo SQLite. La carpeta storage/ debe tener permisos de escritura.
    'db_path' => dirname(__DIR__) . '/storage/database.sqlite',

    // Clave para crear el PRIMER usuario administrador en /admin (solo mientras no exista ninguno).
    // Se define en config.local.php; vacía = instalación deshabilitada.
    'setup_key' => '',

    // Llave para cifrar datos sensibles en la BD (contraseña SMTP). Se define en config.local.php.
    // Si la cambias tendrás que volver a capturar la contraseña del correo.
    'app_key' => '',

    // Sesión
    'session_name' => 'rv_session',
    'session_lifetime' => 60 * 60 * 8, // 8 horas

    // Límite de intentos de login: N intentos fallidos por IP en X minutos.
    'login_max_attempts' => 5,
    'login_window_minutes' => 15,

    // Límite de mensajes del formulario público por IP y por hora.
    'contact_max_per_hour' => 5,
];
