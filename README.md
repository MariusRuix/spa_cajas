# Royal Vault · Landing + Dashboard

Stack: **Flight PHP 3.19** (incluido en `lib/`, sin Composer) · **Alpine.js 3.14** · **Tabler 1.4** · **SQLite**.

## Estructura

```
.htaccess            → redirige todo a public/
app/                 → lógica (bloqueada al navegador)
  config.php         → debug, setup_key, límites
  Database.php       → tablas + datos iniciales de la maqueta
  helpers.php        → seguridad, validación y recursos CRUD
  routes.php         → rutas web y API
  views/landing.php  → la maqueta, ahora dinámica
  views/admin.php    → shell del dashboard (Tabler)
lib/flight/          → Flight PHP
public/              → raíz web (en Hostinger: public_html/): index.php, assets, uploads
storage/             → database.sqlite y log de errores (se crean solos)
```

## Uso local (Laragon)

1. Abre `http://royal-vault.test/` (la BD se crea sola en `storage/`).
2. Entra a `http://royal-vault.test/admin`, escribe la `setup_key` de `app/config.php` y crea tu usuario.

## Despliegue en Hostinger (recomendado: código fuera de `public_html`)

El código, la base de datos y la configuración quedan **fuera** de la carpeta pública; en `public_html` solo va lo que debe verse en internet.

```
/home/u123456789/domains/tudominio.com/
├── royal-vault/        ← app/, lib/, storage/   (NO accesible desde internet)
└── public_html/        ← el CONTENIDO de public/ (index.php, .htaccess, assets/, uploads/, favicon…)
```

1. En el Administrador de archivos de hPanel entra a `domains/tudominio.com/`.
2. Crea la carpeta **`royal-vault`** (al mismo nivel que `public_html`) y sube ahí `app/`, `lib/` y `storage/`.
3. Borra lo que Hostinger pone por defecto en `public_html` (`default.php`, etc.) y sube **el contenido** de `public/` (no la carpeta). Incluye los archivos ocultos `.htaccess` y `uploads/.htaccess`.
4. `index.php` encuentra solo la carpeta `royal-vault` vecina. Si usas otro nombre o ubicación, escribe la ruta en `$APP_ROOT` al inicio de `public_html/index.php`.
5. En `royal-vault/app/config.php`: `debug => false` y cambia `setup_key`.
6. Permisos de escritura (755) en `royal-vault/storage/` y `public_html/uploads/`.
7. Activa SSL y descomenta en `public_html/.htaccess` la redirección a HTTPS y el header HSTS.
8. Entra a `https://tudominio.com/admin` y crea tu usuario con la `setup_key`.

**Respaldos:** `royal-vault/storage/` (base de datos) y `public_html/uploads/` (imágenes).

**Actualizar el sitio:** cambios en `app/` o `lib/` van a `royal-vault/`; cambios en `public/` van a `public_html/`. Nunca sobrescribas `storage/database.sqlite` ni `uploads/`.

### Alternativa: todo dentro de `public_html`

También funciona subir el proyecto completo a `public_html/` (el `.htaccess` de la raíz redirige a `public/` y bloquea `app/`, `lib/` y `storage/`). Es más simple pero depende de que esos `.htaccess` funcionen: comprueba que `https://tudominio.com/storage/database.sqlite` y `/app/config.php` respondan **403**.

Requisitos: PHP 8.0+ con `pdo_sqlite` y `gd` (activos por defecto en Hostinger).

## Seguridad incluida

- Sesiones con cookie `HttpOnly`, `SameSite=Strict`, `Secure` con HTTPS y regeneración de ID al iniciar sesión.
- Token CSRF en todas las peticiones que modifican datos.
- Contraseñas con `password_hash()`; mínimo 10 caracteres.
- Límite de intentos de login (5 cada 15 min por IP) y de mensajes de contacto (5 por hora), más honeypot anti-bots.
- PDO con consultas preparadas; nombres de tablas y columnas solo desde una lista blanca.
- Todo lo que se imprime pasa por `e()` (anti-XSS); las URLs solo aceptan `http(s)://`.
- `app/`, `lib/` y `storage/` bloqueados por `.htaccess`; headers de seguridad.

## SEO y analítica

En **Panel → Configuración**:

- **SEO**: meta descripción con vista previa de Google, palabras clave, canonical, Open Graph/Twitter (vista al compartir en WhatsApp/Facebook), datos estructurados `LocksmithService` (negocio local) e interruptor para indexar o no.
- **Analítica**: GA4, Google Tag Manager, Google Ads, Meta Pixel, TikTok Pixel y verificación de Search Console/Bing. Solo pegas el ID.
- **Eventos automáticos**: `whatsapp_click`, `phone_click` y `generate_lead` (formulario) se envían a GA4/GTM/Ads, Meta (Contact/Lead) y TikTok.
- **Código extra**: scripts propios en `<head>` o antes de `</body>`.
- `/robots.txt` y `/sitemap.xml` se generan solos (con "no indexar" activado, robots.txt bloquea todo).

Después de publicar: en Search Console agrega la propiedad, pega el código de verificación en el panel y envía `https://tudominio/sitemap.xml`.

## Correo de notificación (formulario de contacto)

En **Panel → Configuración → Correo** activa el envío, escribe a quién avisar y los datos SMTP. Luego **Guardar** y **Enviar correo de prueba**.

**Gmail:** botón *Gmail* (smtp.gmail.com · 587 · STARTTLS). Usuario = tu correo de Gmail. Contraseña = una
[contraseña de aplicación](https://myaccount.google.com/apppasswords) de 16 letras (necesitas la verificación en 2 pasos activa); tu contraseña normal no funciona.

**Correo de Hostinger:** botón *Correo de Hostinger* (smtp.hostinger.com · 465 · SSL) con el buzón creado en hPanel.

- La contraseña se guarda **cifrada** (AES-256-GCM con `app_key` de `app/config.php`) y nunca se envía al navegador.
- Si el envío falla, el mensaje igual se guarda en el panel y el error aparece en la pestaña Correo.
- Usa PHPMailer 6.10 (incluido en `lib/phpmailer`, sin Composer).

## Imágenes de productos

En el formulario de cada producto puedes **subir una foto** (clic o arrastrar) **o pegar una URL** externa.

- Formatos JPG, PNG, WEBP o GIF, máx. 5 MB. Se valida el contenido real del archivo y, con GD, se re-genera como WEBP de máx. 1600 px (quita metadatos y reduce peso).
- Se guardan en `uploads/products/AAAA/MM/` dentro de la carpeta pública (`public/` en Laragon, `public_html/` en Hostinger). Esa carpeta solo sirve imágenes (`.htaccess` bloquea cualquier script).
- Al cambiar o quitar la imagen, o al eliminar el producto, el archivo anterior se borra.
- En Hostinger, `public_html/uploads/` debe tener permisos de escritura (755). Inclúyela en tus respaldos junto con `storage/`.

## Agregar un módulo CRUD nuevo

1. Crea la tabla en `app/Database.php` (método `migrate`).
2. Agrégala en `resources()` de `app/helpers.php` (campos y validación).
3. Agrégala en `RESOURCES` y `MENU` de `public/assets/js/admin.js`. Para un campo de imagen usa `type: 'image'` en ambos lados.

Con eso el dashboard ya tiene listado, búsqueda, alta, edición, activar/desactivar y eliminación.
