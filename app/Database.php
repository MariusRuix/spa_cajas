<?php
declare(strict_types=1);

/**
 * Conexión SQLite + creación automática de tablas y datos iniciales.
 * La primera vez que se abre el sitio se crea storage/database.sqlite
 * con el contenido de la maqueta original.
 */
final class Database
{
    public static function connect(string $path): PDO
    {
        $isNew = !is_file($path);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');

        self::migrate($pdo);
        if ($isNew) {
            self::seed($pdo);
        }
        self::addMissingSettings($pdo);
        self::upgradeLegacyContent($pdo);
        self::moveCoverImagesToGallery($pdo);
        return $pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'admin',
                session_version INTEGER NOT NULL DEFAULT 1,
                is_owner INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL DEFAULT ''
            );
            CREATE TABLE IF NOT EXISTS services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                description TEXT NOT NULL DEFAULT '',
                icon TEXT NOT NULL DEFAULT 'ti ti-tool',
                cta_label TEXT NOT NULL DEFAULT 'Más información',
                wa_message TEXT NOT NULL DEFAULT '',
                sort INTEGER NOT NULL DEFAULT 0,
                active INTEGER NOT NULL DEFAULT 1,
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT NOT NULL DEFAULT '',
                badge TEXT NOT NULL DEFAULT '',
                badge_style TEXT NOT NULL DEFAULT 'primary',
                features TEXT NOT NULL DEFAULT '',
                icon TEXT NOT NULL DEFAULT '',
                image_url TEXT NOT NULL DEFAULT '',
                sort INTEGER NOT NULL DEFAULT 0,
                active INTEGER NOT NULL DEFAULT 1,
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                phone TEXT NOT NULL,
                body TEXT NOT NULL,
                ip TEXT NOT NULL DEFAULT '',
                read_at TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                bucket TEXT NOT NULL,
                ip TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );
            CREATE INDEX IF NOT EXISTS idx_rate_limits ON rate_limits (bucket, ip, created_at);
            CREATE TABLE IF NOT EXISTS product_images (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                path TEXT NOT NULL,
                sort INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );
            CREATE INDEX IF NOT EXISTS idx_product_images ON product_images (product_id, sort);
        SQL);

        // Columnas agregadas después (usuarios con rol y cierre de sesiones)
        $cols = array_column($pdo->query('PRAGMA table_info(users)')->fetchAll(), 'name');
        if (!in_array('role', $cols, true)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'admin'");
        }
        if (!in_array('session_version', $cols, true)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN session_version INTEGER NOT NULL DEFAULT 1');
        }
        if (!in_array('is_owner', $cols, true)) {
            // Administrador principal: el primer usuario creado queda protegido
            $pdo->exec('ALTER TABLE users ADD COLUMN is_owner INTEGER NOT NULL DEFAULT 0');
            $pdo->exec("UPDATE users SET is_owner = 1, role = 'admin' WHERE id = (SELECT MIN(id) FROM users)");
        }
    }

    /**
     * Actualiza el contenido de ejemplo de la maqueta original (afirmaciones sin respaldo e
     * iconos de Font Awesome) SOLO cuando sigue sin cambios; lo que el dueño ya editó no se toca.
     */
    private static function upgradeLegacyContent(PDO $pdo): void
    {
        if ($pdo->query("SELECT value FROM settings WHERE key = 'content_version'")->fetchColumn() === '2') {
            return;
        }
        $settings = [
            'topbar_text' => ['Servicio de Aperturas y Emergencias Técnicas 24/7:', 'Aperturas de emergencia 24/7'],
            'hero_badge' => ['Innovación en Seguridad Física', 'Cajas fuertes y cajas de seguridad · Yucatán'],
            'hero_title' => ['Protege tu patrimonio con', 'Tu seguridad,'],
            'hero_highlight' => ['expertos certificados', 'nuestro compromiso'],
            'hero_text' => ['Venta de cajas fuertes de alta gama, mantenimiento preventivo y aperturas de emergencia sin daño. Soluciones rápidas, discretas y garantizadas para hogares y empresas.', 'Venta, reparación, mantenimiento y apertura de cajas fuertes y cajas de seguridad para hogares y negocios. Atención de emergencias las 24 horas.'],
            'about_text' => ['Combinamos la experiencia técnica de nuestra trayectoria con tecnología moderna para ofrecerte un servicio impecable. Cada apertura y cada instalación cuenta con garantía por escrito.', 'cuidamos tus bienes con discreción y trato directo: te ayudamos a elegir la caja adecuada y nos encargamos de su instalación, mantenimiento o apertura.'],
        ];
        $st = $pdo->prepare('UPDATE settings SET value = ? WHERE key = ? AND value = ?');
        foreach ($settings as $key => [$old, $new]) {
            $st->execute([$new, $key, $old]);
        }
        $pdo->exec("DELETE FROM settings WHERE key LIKE 'stat\\_%' ESCAPE '\\'");

        $icons = [
            'fa-solid fa-unlock-keyhole'     => 'ti ti-lock-open',
            'fa-solid fa-screwdriver-wrench' => 'ti ti-tools',
            'fa-solid fa-key'                => 'ti ti-key',
            'fa-solid fa-wrench'             => 'ti ti-tool',
            'fa-solid fa-vault'              => '',
        ];
        foreach (['services', 'products'] as $table) {
            $st = $pdo->prepare("UPDATE $table SET icon = ? WHERE icon = ?");
            foreach ($icons as $old => $new) {
                $st->execute([$new, $old]);
            }
            // Cualquier otro icono de Font Awesome ya no se carga: vuelve al icono por defecto.
            $pdo->exec("UPDATE $table SET icon = '' WHERE icon LIKE 'fa-%'");
        }
        $pdo->prepare('UPDATE products SET features = ? WHERE features = ?')
            ->execute(["Capacidad: 95 Litros\nPlaca de acero templado", "Capacidad: 95 Litros\nPlaca de acero templado\nCertificación Grado III"]);

        $pdo->exec("INSERT INTO settings (key, value) VALUES ('content_version', '2')
                    ON CONFLICT(key) DO UPDATE SET value = excluded.value");
    }

    /** Una sola vez: la imagen única que ya tenía cada producto pasa a ser la primera de su galería. */
    private static function moveCoverImagesToGallery(PDO $pdo): void
    {
        if ($pdo->query("SELECT value FROM settings WHERE key = 'gallery_version'")->fetchColumn() === '1') {
            return;
        }
        $pdo->exec("INSERT INTO product_images (product_id, path, sort)
                    SELECT id, image_url, 0 FROM products
                    WHERE image_url <> '' AND id NOT IN (SELECT product_id FROM product_images)");
        $pdo->exec("INSERT INTO settings (key, value) VALUES ('gallery_version', '1')
                    ON CONFLICT(key) DO UPDATE SET value = excluded.value");
    }

    /** Agrega ajustes nuevos (SEO, analítica…) a bases de datos ya existentes. */
    private static function addMissingSettings(PDO $pdo): void
    {
        $defaults = setting_defaults();
        $check = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE key IN (' . implode(',', array_fill(0, count($defaults), '?')) . ')');
        $check->execute(array_keys($defaults));
        $have = (int) $check->fetchColumn();
        if ($have >= count($defaults)) {
            return;
        }
        $st = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)');
        foreach ($defaults as $k => $v) {
            $st->execute([$k, $v]);
        }
    }

    /** Datos iniciales tomados de la maqueta original. */
    private static function seed(PDO $pdo): void
    {
        $settings = [
            'brand_name'      => 'ROYAL',
            'brand_highlight' => 'VAULT',
            'brand_tagline'   => 'Cajas de Seguridad Yucatán',
            'site_title'      => 'Royal Vault | Cajas Fuertes y Cajas de Seguridad en Yucatán',
            'topbar_text'     => 'Aperturas de emergencia 24/7',
            'phone_display'   => '+34 900 987 654',
            'whatsapp'        => '34900987654',
            'hero_badge'      => 'Cajas fuertes y cajas de seguridad · Yucatán',
            'hero_title'      => 'Tu seguridad,',
            'hero_highlight'  => 'nuestro compromiso',
            'hero_text'       => 'Venta, reparación, mantenimiento y apertura de cajas fuertes y cajas de seguridad para hogares y negocios. Atención de emergencias las 24 horas.',
            'about_text'      => 'cuidamos tus bienes con discreción y trato directo: te ayudamos a elegir la caja adecuada y nos encargamos de su instalación, mantenimiento o apertura.',
            'address'         => 'Paseo de la Castellana #120, Local 4, 28046 Madrid.',
            'hours'           => "Lunes a Viernes: 9:00 AM – 7:00 PM\nSábados: 9:00 AM – 2:00 PM",
            'maps_url'        => 'https://maps.google.com',
            'maps_embed'      => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3037.158434728562!2d-3.6921319234857516!3d40.45037005497439!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd4228fe0b9685e5%3A0x8083818e3a241777!2sPaseo%20de%20la%20Castellana%2C%20Madrid%2C%20Spain!5e0!3m2!1ses!2sus!4v1700000000000!5m2!1ses!2sus',
            'facebook_url'    => '',
            'instagram_url'   => '',
            'footer_text'     => 'Tu seguridad, nuestro compromiso.',
        ];
        $st = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?)');
        foreach ($settings as $k => $v) {
            $st->execute([$k, $v]);
        }

        $services = [
            ['Aperturas de Emergencia', 'Apertura técnica especializada por pérdida de claves, fallas electrónicas o baterías agotadas. Evitamos daños estructurales innecesarios en tu caja fuerte.', 'ti ti-lock-open', 'Solicitar Apertura', 'Hola, necesito una apertura de emergencia.'],
            ['Mantenimiento Preventivo', 'Calibración de pestillos, lubricación de engranajes de alta precisión y revisión de circuitos electrónicos para prevenir fallos mecánicos imprevistos.', 'ti ti-tools', 'Agendar Revisión', 'Hola, me interesa mantenimiento preventivo.'],
            ['Reparación y Conversión', 'Actualización de sistemas mecánicos tradicionales a cerraduras biométricas o digitales avanzadas. Cambio de combinación por seguridad corporativa.', 'ti ti-key', 'Cotizar Reparación', 'Hola, quiero actualizar o reparar mi cerradura.'],
        ];
        $st = $pdo->prepare('INSERT INTO services (title, description, icon, cta_label, wa_message, sort) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($services as $i => $s) {
            $st->execute([...$s, $i + 1]);
        }

        $products = [
            ['NovaSecure Home Bio', 'Cerradura biométrica + código digital de alta velocidad.', 'Residencial', 'primary', "Capacidad: 30 Litros\nProtección ignífuga 60 min\nAnclaje de alta resistencia"],
            ['Enterprise Pro 500', 'Doble combinación mecánica y pestillería activa anti-taladro.', 'Oficina / Negocio', 'dark', "Capacidad: 95 Litros\nPlaca de acero templado"],
            ['Titan Fire Shield 120', 'Protección térmica extrema para documentos críticos y efectivo.', 'Ignífuga Avanzada', 'primary', "Capacidad: 60 Litros\nResistencia a fuego 120 min\nCierre hermético reforzado"],
        ];
        $st = $pdo->prepare('INSERT INTO products (name, description, badge, badge_style, features, sort) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($products as $i => $p) {
            $st->execute([...$p, $i + 1]);
        }
    }
}
