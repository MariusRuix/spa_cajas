# Royal Vault · Landing + Dashboard

Stack: **Flight PHP 3.19** (incluido en `lib/`, sin Composer) · **Alpine.js 3.14** · **Tabler 1.4** · **SQLite**.

## Estructura

```
.htaccess            → redirige todo a public/
app/                 → lógica (bloqueada al navegador)
  config.php         → valores por defecto (se reemplaza en cada despliegue)
  config.local.php   → claves y debug de ESTE servidor (no se sube; plantilla: config.local.example.php)
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
2. Entra a `http://royal-vault.test/admin`, escribe la `setup_key` de `app/config.local.php` y crea tu usuario.

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
5. En `royal-vault/app/` copia `config.local.example.php` como **`config.local.php`** y pon tus claves (`setup_key` y `app_key` nuevas, `debug => false`).
6. Permisos de escritura (755) en `royal-vault/storage/` y `public_html/uploads/`.
7. Activa SSL y descomenta en `public_html/.htaccess` la redirección a HTTPS y el header HSTS.
8. Entra a `https://tudominio.com/admin` y crea tu usuario con la `setup_key`.

**Respaldos:** `royal-vault/storage/` (base de datos) y `public_html/uploads/` (imágenes).

## Actualizar el sitio (despliegues)

1. En Laragon, doble clic en **`deploy.bat`** (o `php tools/build-deploy.php`). Genera:
   - `deploy/public_html.zip` → súbelo a `public_html/` y **extrae reemplazando**.
   - `deploy/royal-vault.zip` → súbelo a `royal-vault/` y **extrae reemplazando**.
2. Borra los ZIP del servidor y recarga con Ctrl+F5. Los cambios de base de datos se aplican solos al abrir el sitio.

| Se reemplaza en cada despliegue (seguro) | Nunca se reemplaza (vive solo en el servidor) |
|---|---|
| `app/` (incluido `config.php`), `lib/`, `public/` | `app/config.local.php` (claves, debug) |
| `storage/.htaccess`, `uploads/.htaccess` | `storage/database.sqlite*` (mensajes, productos, ajustes) |
| | `uploads/products/…` (imágenes subidas) |

Los ZIP ya excluyen lo de la columna derecha, así que extraer reemplazando no borra nada tuyo. Si algún día cambias un valor por defecto (límites, sesión), hazlo en `config.php`; si es propio de un servidor, en su `config.local.php`.

### Alternativa: todo dentro de `public_html`

También funciona subir el proyecto completo a `public_html/` (el `.htaccess` de la raíz redirige a `public/` y bloquea `app/`, `lib/` y `storage/`). Es más simple pero depende de que esos `.htaccess` funcionen: comprueba que `https://tudominio.com/storage/database.sqlite` y `/app/config.local.php` respondan **403**.

Requisitos: PHP 8.0+ con `pdo_sqlite` y `gd` (activos por defecto en Hostinger).

## Seguridad incluida

- Sesiones con cookie `HttpOnly`, `SameSite=Strict`, `Secure` con HTTPS y regeneración de ID al iniciar sesión.
- Token CSRF en todas las peticiones que modifican datos.
- Contraseñas con `password_hash()`; mínimo 10 caracteres.
- Límite de intentos de login (5 cada 15 min por IP) y de mensajes de contacto (5 por hora), más honeypot anti-bots.
- PDO con consultas preparadas; nombres de tablas y columnas solo desde una lista blanca.
- Todo lo que se imprime pasa por `e()` (anti-XSS); las URLs solo aceptan `http(s)://`.
- `app/`, `lib/` y `storage/` bloqueados por `.htaccess`; headers de seguridad.

## Usuarios del panel

En **Panel → Usuarios** (solo administradores) se crean, editan y eliminan cuentas.

| Rol | Puede |
|---|---|
| **Administrador** | Todo: mensajes, catálogo, servicios, Configuración (SEO, analítica, correo) y Usuarios. |
| **Editor** | Mensajes, catálogo y servicios, y cambiar su propia contraseña. |

- El primer usuario (creado con la `setup_key`) es administrador.
- Nadie puede eliminarse a sí mismo, quitarse el rol de administrador ni dejar el panel sin administradores.
- Al eliminar un usuario, o cambiar su contraseña o su rol, sus sesiones abiertas se cierran solas.
- Los permisos se validan en el servidor; ocultar el menú es solo comodidad.

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

- La contraseña se guarda **cifrada** (AES-256-GCM con `app_key` de `app/config.local.php`) y nunca se envía al navegador.
- Si el envío falla, el mensaje igual se guarda en el panel y el error aparece en la pestaña Correo.
- Usa PHPMailer 6.10 (incluido en `lib/phpmailer`, sin Composer).

## Fotos de productos (galería)

Cada producto admite **hasta 20 fotos**. En el formulario del producto (Panel → Catálogo → Editar):

- **Agregar fotos**: selecciona o arrastra varias a la vez (JPG, PNG, WEBP o GIF, máx. 5 MB cada una). También se puede agregar una foto por URL.
- **Ordenar**: arrastra las miniaturas o usa las flechas. La **primera es la portada** (botón ★ para hacer portada cualquiera).
- Pulsa **Guardar** para aplicar los cambios; las fotos que quites se borran del servidor.

En el sitio, la tarjeta muestra la portada con el número de fotos; al hacer clic se abre un carrusel a pantalla completa (flechas, teclado, deslizar con el dedo y miniaturas).

Detalles técnicos: tabla `product_images`; `products.image_url` guarda la portada. Las fotos se validan por su contenido, se re-generan como WEBP de máx. 1600 px sin metadatos y se guardan en `uploads/products/AAAA/MM/` (carpeta que solo sirve imágenes). Al eliminar un producto se borran sus fotos. En Hostinger, `public_html/uploads/` necesita permisos de escritura (755) y conviene respaldarla junto con `storage/`.

## Agregar un módulo CRUD nuevo

1. Crea la tabla en `app/Database.php` (método `migrate`).
2. Agrégala en `resources()` de `app/helpers.php` (campos y validación).
3. Agrégala en `RESOURCES` y `MENU` de `public/assets/js/admin.js`. Para un campo de imagen usa `type: 'image'` en ambos lados.

Con eso el dashboard ya tiene listado, búsqueda, alta, edición, activar/desactivar y eliminación.
