<?php
declare(strict_types=1);

// ============================================================================
//  SITIO PÚBLICO
// ============================================================================

Flight::route('GET /', function () {
    $pdo = db();
    Flight::render('landing', [
        's'        => settings(),
        'services' => $pdo->query('SELECT * FROM services WHERE active = 1 ORDER BY sort, id')->fetchAll(),
        'products' => $pdo->query('SELECT * FROM products WHERE active = 1 ORDER BY sort, id')->fetchAll(),
        'gallery'  => gallery_map(),
        'csrf'     => csrf_token(),
    ]);
});

// robots.txt dinámico (respeta el interruptor "Permitir indexar")
Flight::route('GET /robots.txt', function () {
    $index = (settings()['seo_index'] ?? '1') === '1';
    $txt = "User-agent: *\n" . ($index ? "Disallow: /admin\nDisallow: /api/\n" : "Disallow: /\n");
    if ($index) {
        $txt .= "\nSitemap: " . base_url() . "/sitemap.xml\n";
    }
    Flight::response()->header('Content-Type', 'text/plain; charset=utf-8');
    echo $txt;
});

// sitemap.xml (landing de una sola página; lastmod = última edición de contenido)
Flight::route('GET /sitemap.xml', function () {
    $last = db()->query("SELECT MAX(u) FROM (SELECT MAX(updated_at) u FROM products UNION ALL SELECT MAX(updated_at) FROM services)")->fetchColumn();
    $lastmod = $last ? gmdate('Y-m-d', strtotime($last)) : gmdate('Y-m-d');
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
         . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
         . '  <url><loc>' . e(base_url() . '/') . "</loc><lastmod>$lastmod</lastmod><changefreq>weekly</changefreq><priority>1.0</priority></url>\n"
         . '</urlset>';
    Flight::response()->header('Content-Type', 'application/xml; charset=utf-8');
    echo $xml;
});

// Formulario de contacto de la landing
Flight::route('POST /api/contact', function () {
    require_csrf();
    $cfg = Flight::get('config');
    $data = body();

    // Honeypot: campo oculto que solo llenan los bots
    if (!empty($data['website'])) {
        Flight::json(['ok' => true]);
        return;
    }
    if (rate_limited('contact', $cfg['contact_max_per_hour'], 60)) {
        Flight::jsonHalt(['error' => 'Has enviado demasiados mensajes. Intenta más tarde o escríbenos por WhatsApp.'], 429);
    }

    [$clean, $errors] = validate([
        'name'  => ['type' => 'text', 'label' => 'Nombre', 'required' => true, 'max' => 100],
        'phone' => ['type' => 'text', 'label' => 'Teléfono', 'required' => true, 'max' => 30],
        'body'  => ['type' => 'text', 'label' => 'Mensaje', 'required' => true, 'max' => 2000],
    ], $data);
    if ($errors) {
        Flight::jsonHalt(['error' => reset($errors), 'fields' => $errors], 422);
    }

    db()->prepare('INSERT INTO messages (name, phone, body, ip) VALUES (?, ?, ?, ?)')
        ->execute([$clean['name'], $clean['phone'], $clean['body'], client_ip()]);
    rate_hit('contact');

    // Notificación por correo (si falla, el mensaje igual queda guardado en el panel)
    Mailer::notifyNewMessage($clean);
    Flight::json(['ok' => true], 201);
});

// ============================================================================
//  DASHBOARD (SPA)
// ============================================================================

Flight::route('GET /admin', function () {
    Flight::render('admin', ['csrf' => csrf_token(), 's' => settings()]);
});

// ---- Autenticación ---------------------------------------------------------

Flight::route('GET /api/me', function () {
    $hasUsers = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    Flight::json(['user' => current_user(), 'needsSetup' => !$hasUsers]);
});

Flight::route('POST /api/setup', function () {
    require_csrf();
    $cfg = Flight::get('config');
    if ((int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
        Flight::jsonHalt(['error' => 'El panel ya está instalado. Pide a un administrador que te cree un usuario.'], 403);
    }
    if (rate_limited('setup', 5, 15)) {
        Flight::jsonHalt(['error' => 'Demasiados intentos. Espera unos minutos.'], 429);
    }
    $data = body();
    if (($cfg['setup_key'] ?? '') === '') {
        Flight::jsonHalt(['error' => 'Instalación deshabilitada: define setup_key en app/config.local.php.'], 403);
    }
    if (!hash_equals($cfg['setup_key'], (string) ($data['setup_key'] ?? ''))) {
        rate_hit('setup');
        Flight::jsonHalt(['error' => 'Clave de instalación incorrecta (ver app/config.local.php).'], 403);
    }
    [$clean, $errors] = validate([
        'name'  => ['type' => 'text', 'label' => 'Nombre', 'required' => true, 'max' => 80],
        'email' => ['type' => 'text', 'label' => 'Correo', 'required' => true, 'max' => 120],
    ], $data);
    if (!filter_var($clean['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Correo no válido';
    }
    $password = (string) ($data['password'] ?? '');
    if (strlen($password) < 10) {
        $errors['password'] = 'La contraseña debe tener al menos 10 caracteres';
    }
    if ($errors) {
        Flight::jsonHalt(['error' => reset($errors)], 422);
    }
    db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')")
        ->execute([$clean['name'], strtolower($clean['email']), password_hash($password, PASSWORD_DEFAULT)]);

    login_user((int) db()->lastInsertId());
    Flight::json(['user' => current_user()]);
});

Flight::route('POST /api/login', function () {
    require_csrf();
    $cfg = Flight::get('config');
    if (rate_limited('login', $cfg['login_max_attempts'], $cfg['login_window_minutes'])) {
        Flight::jsonHalt(['error' => "Demasiados intentos fallidos. Espera {$cfg['login_window_minutes']} minutos."], 429);
    }
    $data = body();
    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([strtolower(trim((string) ($data['email'] ?? '')))]);
    $user = $st->fetch();

    if (!$user || !password_verify((string) ($data['password'] ?? ''), $user['password_hash'])) {
        rate_hit('login');
        Flight::jsonHalt(['error' => 'Correo o contraseña incorrectos'], 401);
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash((string) $data['password'], PASSWORD_DEFAULT), $user['id']]);
    }
    rate_clear('login');
    login_user((int) $user['id']);
    Flight::json(['user' => current_user()]);
});

Flight::route('POST /api/logout', function () {
    require_csrf();
    unset($_SESSION['user_id'], $_SESSION['uv']);
    session_regenerate_id(true);
    Flight::json(['ok' => true]);
});

// ---- Rutas protegidas ------------------------------------------------------
// Todo lo que está dentro del grupo exige sesión; y CSRF si modifica datos.

Flight::group('/api/admin', function () {

    Flight::route('GET /stats', function () {
        $pdo = db();
        $count = fn(string $sql) => (int) $pdo->query($sql)->fetchColumn();

        // Mensajes por día (últimos 14 días)
        $rows = $pdo->query("SELECT date(created_at) d, COUNT(*) c FROM messages
                             WHERE created_at >= date('now', '-13 days') GROUP BY d")->fetchAll(PDO::FETCH_KEY_PAIR);
        $daily = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = gmdate('Y-m-d', strtotime("-{$i} days"));
            $daily[] = ['date' => $d, 'count' => (int) ($rows[$d] ?? 0)];
        }

        Flight::json([
            'products'       => $count('SELECT COUNT(*) FROM products WHERE active = 1'),
            'products_total' => $count('SELECT COUNT(*) FROM products'),
            'services'       => $count('SELECT COUNT(*) FROM services WHERE active = 1'),
            'services_total' => $count('SELECT COUNT(*) FROM services'),
            'unread'         => $count('SELECT COUNT(*) FROM messages WHERE read_at IS NULL'),
            'messages_week'  => $count("SELECT COUNT(*) FROM messages WHERE created_at >= datetime('now', '-7 days')"),
            'daily'          => $daily,
            'latest'         => $pdo->query('SELECT * FROM messages ORDER BY id DESC LIMIT 5')->fetchAll(),
        ]);
    });

    // ---- Mensajes ----
    Flight::route('GET /messages', function () {
        $filter = Flight::request()->query['filter'] ?? 'all';
        $where = $filter === 'unread' ? 'WHERE read_at IS NULL' : '';
        Flight::json(db()->query("SELECT * FROM messages $where ORDER BY id DESC LIMIT 500")->fetchAll());
    });

    Flight::route('PATCH /messages/@id:[0-9]+', function (string $id) {
        $read = !empty(body()['read']);
        db()->prepare('UPDATE messages SET read_at = ' . ($read ? "datetime('now')" : 'NULL') . ' WHERE id = ?')
            ->execute([(int) $id]);
        Flight::json(['ok' => true]);
    });

    Flight::route('DELETE /messages/@id:[0-9]+', function (string $id) {
        db()->prepare('DELETE FROM messages WHERE id = ?')->execute([(int) $id]);
        Flight::json(['ok' => true]);
    });

    // ---- Configuración del sitio ----
    Flight::route('GET /settings', function () {
        $values = settings();
        // La contraseña SMTP nunca sale del servidor: solo se indica si existe.
        $values['smtp_pass_set'] = ($values['smtp_pass'] ?? '') !== '';
        $values['smtp_pass'] = '';
        Flight::json(['values' => $values, 'fields' => setting_fields()]);
    });

    Flight::route('PUT /settings', function () {
        [$clean, $errors] = validate(setting_fields(), body());
        if ($errors) {
            Flight::jsonHalt(['error' => reset($errors), 'fields' => $errors], 422);
        }
        $clean['whatsapp'] = preg_replace('/\D+/', '', $clean['whatsapp']);

        // Correo: validar destinatarios y cifrar la contraseña (vacía = conservar la actual)
        foreach (preg_split('/[\s,;]+/', $clean['mail_to'], -1, PREG_SPLIT_NO_EMPTY) as $addr) {
            if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                Flight::jsonHalt(['error' => "El correo \"$addr\" no es válido"], 422);
            }
        }
        if ($clean['smtp_user'] !== '' && !filter_var($clean['smtp_user'], FILTER_VALIDATE_EMAIL)) {
            Flight::jsonHalt(['error' => 'El usuario SMTP debe ser una dirección de correo'], 422);
        }
        if ($clean['smtp_pass'] === '') {
            unset($clean['smtp_pass']);
        } else {
            try {
                $clean['smtp_pass'] = secret_encrypt(str_replace(' ', '', $clean['smtp_pass'])); // Gmail muestra la clave con espacios
            } catch (RuntimeException $e) {
                Flight::jsonHalt(['error' => $e->getMessage()], 422);
            }
        }
        $st = db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?)
                             ON CONFLICT(key) DO UPDATE SET value = excluded.value');
        foreach ($clean as $k => $v) {
            $st->execute([$k, (string) $v]);
        }
        Flight::json(['ok' => true]);
    });

    // Enviar un correo de prueba con la configuración guardada
    Flight::route('POST /mail/test', function () {
        if (!Mailer::isConfigured()) {
            Flight::jsonHalt(['error' => 'Completa servidor, usuario, contraseña y destinatario, y guarda los cambios antes de probar.'], 422);
        }
        try {
            $brand = trim((settings()['brand_name'] ?? '') . ' ' . (settings()['brand_highlight'] ?? ''));
            Mailer::send(
                "Correo de prueba · {$brand}",
                '<p style="font-family:Arial,sans-serif">✅ La configuración de correo de <strong>' . e($brand) . '</strong> funciona. Aquí llegarán los mensajes del formulario de contacto.</p>',
                "La configuración de correo de {$brand} funciona."
            );
            set_setting('mail_last_error', '');
            Flight::json(['ok' => true, 'to' => Mailer::recipients()]);
        } catch (Throwable $e) {
            error_log('[Mailer test] ' . $e->getMessage());
            $msg = $e->getMessage();
            if (stripos($msg, 'authenticate') !== false) {
                $msg .= ' Revisa usuario y contraseña. En Gmail necesitas una contraseña de aplicación (16 letras), no tu contraseña normal.';
            } elseif (stripos($msg, 'connect') !== false) {
                $msg .= ' Revisa servidor, puerto y seguridad (Gmail: smtp.gmail.com, 587, STARTTLS).';
            }
            Flight::jsonHalt(['error' => 'No se pudo enviar: ' . $msg], 422);
        }
    });

    // ---- Cuenta ----
    Flight::route('PUT /account/password', function () {
        $user = current_user();
        $data = body();
        $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$user['id']]);
        if (!password_verify((string) ($data['current'] ?? ''), (string) $st->fetchColumn())) {
            Flight::jsonHalt(['error' => 'La contraseña actual no es correcta'], 422);
        }
        $new = (string) ($data['new'] ?? '');
        if (strlen($new) < 10) {
            Flight::jsonHalt(['error' => 'La nueva contraseña debe tener al menos 10 caracteres'], 422);
        }
        // Nueva contraseña: se cierran las demás sesiones de este usuario y esta sigue activa
        db()->prepare('UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        login_user((int) $user['id']);
        Flight::json(['ok' => true]);
    });

    // ---- Usuarios del panel (solo administradores) ----
    $userFields = [
        'name'  => ['type' => 'text', 'label' => 'Nombre', 'required' => true, 'max' => 80],
        'email' => ['type' => 'text', 'label' => 'Correo', 'required' => true, 'max' => 120],
        'role'  => ['type' => 'enum', 'label' => 'Rol', 'options' => array_keys(roles())],
    ];
    $checkUser = function (array $data, ?int $id) use ($userFields): array {
        [$clean, $errors] = validate($userFields, $data);
        $clean['email'] = strtolower($clean['email']);
        if (!$errors && !filter_var($clean['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Correo no válido';
        }
        $st = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?');
        $st->execute([$clean['email'], $id ?? 0]);
        if (!$errors && (int) $st->fetchColumn() > 0) {
            $errors['email'] = 'Ya existe un usuario con ese correo';
        }
        $password = (string) ($data['password'] ?? '');
        if (($id === null || $password !== '') && strlen($password) < 10) {
            $errors['password'] = 'La contraseña debe tener al menos 10 caracteres';
        }
        if ($errors) {
            Flight::jsonHalt(['error' => reset($errors), 'fields' => $errors], 422);
        }
        return [$clean, $password];
    };
    $adminCount = fn(): int => (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

    Flight::route('GET /users', function () {
        Flight::json([
            'users' => db()->query('SELECT id, name, email, role, created_at FROM users ORDER BY name')->fetchAll(),
            'roles' => roles(),
        ]);
    });

    Flight::route('POST /users', function () use ($checkUser) {
        [$clean, $password] = $checkUser(body(), null);
        db()->prepare('INSERT INTO users (name, email, role, password_hash) VALUES (?, ?, ?, ?)')
            ->execute([$clean['name'], $clean['email'], $clean['role'], password_hash($password, PASSWORD_DEFAULT)]);
        Flight::json(['id' => (int) db()->lastInsertId()], 201);
    });

    Flight::route('PUT /users/@id:[0-9]+', function (string $id) use ($checkUser, $adminCount) {
        $id = (int) $id;
        $me = current_user();
        $st = db()->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([$id]);
        $old = $st->fetch() ?: Flight::jsonHalt(['error' => 'Usuario no encontrado'], 404);
        [$clean, $password] = $checkUser(body(), $id);

        if ($old['role'] === 'admin' && $clean['role'] !== 'admin' && $adminCount() <= 1) {
            Flight::jsonHalt(['error' => 'Debe quedar al menos un administrador.'], 422);
        }
        if ($id === (int) $me['id'] && $clean['role'] !== 'admin') {
            Flight::jsonHalt(['error' => 'No puedes quitarte a ti mismo el rol de administrador.'], 422);
        }
        // Cambiar contraseña o rol cierra las sesiones abiertas de ese usuario
        $bump = ($password !== '' || $clean['role'] !== $old['role']) ? 1 : 0;
        db()->prepare('UPDATE users SET name = ?, email = ?, role = ?, session_version = session_version + ? WHERE id = ?')
            ->execute([$clean['name'], $clean['email'], $clean['role'], $bump, $id]);
        if ($password !== '') {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
        }
        if ($id === (int) $me['id'] && $bump) {
            login_user($id); // tu propia sesión sigue abierta
        }
        Flight::json(['ok' => true]);
    });

    Flight::route('DELETE /users/@id:[0-9]+', function (string $id) use ($adminCount) {
        $id = (int) $id;
        if ($id === (int) current_user()['id']) {
            Flight::jsonHalt(['error' => 'No puedes eliminar tu propio usuario.'], 422);
        }
        $st = db()->prepare('SELECT role FROM users WHERE id = ?');
        $st->execute([$id]);
        $role = $st->fetchColumn();
        if ($role === false) {
            Flight::jsonHalt(['error' => 'Usuario no encontrado'], 404);
        }
        if ($role === 'admin' && $adminCount() <= 1) {
            Flight::jsonHalt(['error' => 'Debe quedar al menos un administrador.'], 422);
        }
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]); // su sesión se cierra en la siguiente petición
        Flight::json(['ok' => true]);
    });

    // ---- CRUD genérico: /api/admin/r/products, /api/admin/r/services ----
    $resource = function (string $name): array {
        $res = resources()[$name] ?? null;
        if (!$res) {
            Flight::jsonHalt(['error' => 'Recurso no encontrado'], 404);
        }
        return $res;
    };

    $fetchRow = function (array $res, int $id): ?array {
        $st = db()->prepare("SELECT * FROM {$res['table']} WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    };
    $imageFields = fn(array $res): array => array_keys(array_filter($res['fields'], fn($r) => $r['type'] === 'image'));

    // Subida de imágenes (multipart/form-data, campo "file")
    Flight::route('POST /upload', function () {
        if (rate_limited('upload', 300, 60)) {
            Flight::jsonHalt(['error' => 'Demasiadas subidas, espera un momento.'], 429);
        }
        $folder = (string) (Flight::request()->query['folder'] ?? 'misc');
        try {
            $path = store_upload($_FILES['file'] ?? [], in_array($folder, ['products', 'services', 'misc'], true) ? $folder : 'misc');
        } catch (RuntimeException $e) {
            Flight::jsonHalt(['error' => $e->getMessage()], 422);
        }
        rate_hit('upload');
        Flight::json(['url' => $path], 201);
    });

    Flight::route('GET /r/@name:[a-z_]+', function (string $name) use ($resource) {
        $res = $resource($name);
        $rows = db()->query("SELECT * FROM {$res['table']} ORDER BY sort, id")->fetchAll();
        if (!empty($res['gallery'])) {
            $map = gallery_map();
            foreach ($rows as &$row) {
                $row['images'] = $map[(int) $row['id']] ?? [];
            }
            unset($row);
        }
        Flight::json($rows);
    });

    Flight::route('POST /r/@name:[a-z_]+', function (string $name) use ($resource) {
        $res = $resource($name);
        $data = body();
        [$clean, $errors] = validate($res['fields'], $data);
        $images = null;
        if (!empty($res['gallery']) && array_key_exists('images', $data)) {
            [$images, $galleryError] = gallery_clean($data['images']);
            if ($galleryError) {
                $errors['images'] = $galleryError;
            }
        }
        if ($errors) {
            Flight::jsonHalt(['error' => reset($errors), 'fields' => $errors], 422);
        }
        $cols = array_keys($clean);
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $res['table'], implode(', ', $cols), implode(', ', array_fill(0, count($cols), '?')));
        db()->prepare($sql)->execute(array_values($clean));
        $id = (int) db()->lastInsertId();
        if ($images !== null) {
            gallery_sync($id, $images);
        }
        Flight::json(['id' => $id], 201);
    });

    Flight::route('PUT /r/@name:[a-z_]+/@id:[0-9]+', function (string $name, string $id) use ($resource, $fetchRow, $imageFields) {
        $res = $resource($name);
        $data = body();
        [$clean, $errors] = validate($res['fields'], $data);
        $images = null;
        if (!empty($res['gallery']) && array_key_exists('images', $data)) {
            [$images, $galleryError] = gallery_clean($data['images']);
            if ($galleryError) {
                $errors['images'] = $galleryError;
            }
        }
        if ($errors) {
            Flight::jsonHalt(['error' => reset($errors), 'fields' => $errors], 422);
        }
        $old = $fetchRow($res, (int) $id);
        if (!$old) {
            Flight::jsonHalt(['error' => 'Registro no encontrado'], 404);
        }
        // Los nombres de columna vienen de resources(), nunca del usuario.
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($clean)));
        $sql = "UPDATE {$res['table']} SET $set, updated_at = datetime('now') WHERE id = ?";
        db()->prepare($sql)->execute([...array_values($clean), (int) $id]);
        // Si se reemplazó o quitó una imagen subida, se borra el archivo anterior
        foreach ($imageFields($res) as $f) {
            if ($old && ($old[$f] ?? '') !== $clean[$f]) {
                delete_upload($old[$f]);
            }
        }
        if ($images !== null) {
            gallery_sync((int) $id, $images);
        }
        Flight::json(['ok' => true]);
    });

    Flight::route('DELETE /r/@name:[a-z_]+/@id:[0-9]+', function (string $name, string $id) use ($resource, $fetchRow, $imageFields) {
        $res = $resource($name);
        $old = $fetchRow($res, (int) $id);
        $photos = !empty($res['gallery']) ? gallery_get((int) $id) : [];
        db()->prepare("DELETE FROM {$res['table']} WHERE id = ?")->execute([(int) $id]);
        foreach ($photos as $photo) {
            delete_upload($photo);
        }
        foreach ($imageFields($res) as $f) {
            delete_upload($old[$f] ?? '');
        }
        Flight::json(['ok' => true]);
    });

}, [
    // Middleware del grupo: sesión obligatoria + CSRF en métodos que modifican
    function () {
        $user = require_auth();
        if ($user['role'] !== 'admin' && admin_only_path(Flight::request()->url)) {
            Flight::jsonHalt(['error' => 'Tu usuario no tiene permiso para esta sección.'], 403);
        }
        if (Flight::request()->method !== 'GET') {
            require_csrf();
        }
    },
]);
