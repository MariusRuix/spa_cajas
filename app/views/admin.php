<?php
/**
 * Shell del dashboard (SPA). Toda la lógica vive en /assets/js/admin.js
 * y los datos llegan por la API JSON (/api/...).
 *
 * @var string $csrf
 * @var array  $s
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <meta name="robots" content="noindex, nofollow">
<?php include __DIR__ . '/_favicons.php'; ?>
    <title>Panel · <?= e(trim(($s['brand_name'] ?? '') . ' ' . ($s['brand_highlight'] ?? ''))) ?></title>
    <link rel="stylesheet" href="/assets/vendor/tabler/tabler.min.css">
    <link rel="stylesheet" href="/assets/vendor/tabler-icons/tabler-icons.min.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <script src="/assets/js/admin.js"></script>
    <script defer src="/assets/vendor/alpine.min.js"></script>
</head>
<body x-data="app" x-init="boot()" :data-bs-theme="theme">

<!-- ===================== CARGANDO ===================== -->
<div x-show="!ready" class="page page-center"><div class="spinner-border text-primary mx-auto" role="status"></div></div>

<!-- ===================== LOGIN / INSTALACIÓN ===================== -->
<template x-if="ready && !user">
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <img src="/assets/img/logo-mark.jpg" alt="" width="80" height="80" class="brand-logo">
                <h1 class="brand-word mt-3 mb-0"><?= e(trim(($s['brand_name'] ?? '') . ' ' . ($s['brand_highlight'] ?? ''))) ?></h1>
                <div class="text-secondary">Panel de control</div>
            </div>

            <!-- Login -->
            <form class="card card-md" x-show="!needsSetup" @submit.prevent="login">
                <div class="card-body">
                    <h2 class="h3 text-center mb-4">Inicia sesión</h2>
                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <input type="email" class="form-control" x-model="auth.email" required autocomplete="username" autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" class="form-control" x-model="auth.password" required autocomplete="current-password">
                    </div>
                    <div class="alert alert-danger py-2" x-show="auth.error" x-text="auth.error"></div>
                    <button class="btn btn-primary w-100" :disabled="busy">
                        <span x-show="busy" class="spinner-border spinner-border-sm me-2"></span>Entrar
                    </button>
                </div>
            </form>

            <!-- Primer uso: crear administrador -->
            <form class="card card-md" x-show="needsSetup" @submit.prevent="setup">
                <div class="card-body">
                    <h2 class="h3 mb-1">Crear administrador</h2>
                    <p class="text-secondary small mb-4">Primer uso del panel. La clave de instalación es <code>setup_key</code> en <code>app/config.local.php</code>.</p>
                    <div class="mb-3">
                        <label class="form-label">Clave de instalación</label>
                        <input type="text" class="form-control" x-model="auth.setup_key" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" x-model="auth.name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <input type="email" class="form-control" x-model="auth.email" required autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña <span class="text-secondary">(mín. 10 caracteres)</span></label>
                        <input type="password" class="form-control" x-model="auth.password" minlength="10" required autocomplete="new-password">
                    </div>
                    <div class="alert alert-danger py-2" x-show="auth.error" x-text="auth.error"></div>
                    <button class="btn btn-primary w-100" :disabled="busy">Crear cuenta y entrar</button>
                </div>
            </form>
            <div class="text-center text-secondary mt-3"><a href="/" class="text-secondary"><i class="ti ti-arrow-left"></i> Volver al sitio</a></div>
        </div>
    </div>
</template>

<!-- ===================== PANEL ===================== -->
<template x-if="ready && user">
<div class="page">
    <!-- Barra lateral -->
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" @click="menuOpen = !menuOpen" aria-label="Menú">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a href="#/" class="navbar-brand navbar-brand-autodark">
                <img src="/assets/img/emblem-sm.webp" alt="" width="26" height="32" class="brand-emblem">
                <span class="ms-2 brand-word brand-word--sm"><?= e(trim(($s['brand_name'] ?? '') . ' ' . ($s['brand_highlight'] ?? ''))) ?></span>
            </a>
            <div class="collapse navbar-collapse" :class="{ show: menuOpen }">
                <ul class="navbar-nav pt-lg-3">
                    <template x-for="item in menu" :key="item.view">
                        <li class="nav-item" :class="{ active: view === item.view }">
                            <a class="nav-link" :href="'#/' + item.view" @click="menuOpen = false">
                                <span class="nav-link-icon"><i class="ti" :class="item.icon"></i></span>
                                <span class="nav-link-title" x-text="item.label"></span>
                                <span class="badge bg-red text-red-fg ms-auto position-static" x-show="item.view === 'messages' && stats.unread > 0" x-text="stats.unread"></span>
                            </a>
                        </li>
                    </template>
                    <li class="nav-item mt-lg-3 border-top border-secondary-subtle pt-lg-2">
                        <a class="nav-link" href="/" target="_blank">
                            <span class="nav-link-icon"><i class="ti ti-external-link"></i></span>
                            <span class="nav-link-title">Ver sitio</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </aside>

    <!-- Barra superior -->
    <header class="navbar navbar-expand-md d-print-none">
        <div class="container-xl">
            <div class="navbar-nav flex-row order-md-last ms-auto align-items-center gap-2">
                <button class="btn btn-ghost-secondary btn-icon" @click="toggleTheme" :title="theme === 'dark' ? 'Modo claro' : 'Modo oscuro'">
                    <i class="ti" :class="theme === 'dark' ? 'ti-sun' : 'ti-moon'"></i>
                </button>
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm rounded-circle bg-primary-lt" x-text="initials(user.name)"></span>
                    <div class="d-none d-xl-block lh-sm">
                        <div x-text="user.name"></div>
                        <div class="small text-secondary" x-text="user.email"></div>
                    </div>
                </div>
                <button class="btn btn-ghost-danger btn-icon" @click="logout" title="Cerrar sesión"><i class="ti ti-logout"></i></button>
            </div>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Panel de control</div>
                        <h2 class="page-title" x-text="title"></h2>
                    </div>
                    <div class="col-auto" x-show="resource">
                        <button class="btn btn-primary" @click="openForm()"><i class="ti ti-plus me-1"></i> Nuevo</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">

                <!-- ============ RESUMEN ============ -->
                <div x-show="view === 'dashboard'">
                    <div class="row row-deck row-cards">
                        <template x-for="kpi in kpis" :key="kpi.label">
                            <div class="col-sm-6 col-lg-3">
                                <a class="card card-link" :href="kpi.href">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar me-3" :class="kpi.color"><i class="ti" :class="kpi.icon"></i></span>
                                            <div>
                                                <div class="h1 mb-0" x-text="kpi.value"></div>
                                                <div class="text-secondary" x-text="kpi.label"></div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </template>

                        <div class="col-lg-7">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">Mensajes recibidos · últimos 14 días</h3></div>
                                <div class="card-body">
                                    <div class="mini-chart">
                                        <template x-for="d in stats.daily" :key="d.date">
                                            <div class="mini-chart-col" :title="d.date + ': ' + d.count">
                                                <span class="mini-chart-val" x-text="d.count || ''"></span>
                                                <div class="mini-chart-bar" :style="'height:' + barHeight(d.count) + '%'"></div>
                                                <span class="mini-chart-label" x-text="d.date.slice(8)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Últimos mensajes</h3>
                                    <div class="card-actions"><a href="#/messages">Ver todos</a></div>
                                </div>
                                <div class="list-group list-group-flush">
                                    <template x-for="m in stats.latest" :key="m.id">
                                        <a href="#/messages" class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between">
                                                <strong x-text="m.name"></strong>
                                                <span class="badge bg-blue-lt" x-show="!m.read_at">Nuevo</span>
                                            </div>
                                            <div class="text-secondary text-truncate small" x-text="m.body"></div>
                                        </a>
                                    </template>
                                    <div class="list-group-item text-secondary" x-show="!stats.latest?.length">Aún no hay mensajes.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============ MENSAJES ============ -->
                <div x-show="view === 'messages'">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-pills card-header-pills">
                                <li class="nav-item"><a class="nav-link" href="#" :class="{active: msgFilter==='all'}" @click.prevent="msgFilter='all'; loadMessages()">Todos</a></li>
                                <li class="nav-item"><a class="nav-link" href="#" :class="{active: msgFilter==='unread'}" @click.prevent="msgFilter='unread'; loadMessages()">Sin leer</a></li>
                            </ul>
                        </div>
                        <div class="list-group list-group-flush">
                            <template x-for="m in messages" :key="m.id">
                                <div class="list-group-item" :class="{ 'bg-primary-lt': !m.read_at }">
                                    <div class="row align-items-start g-3">
                                        <div class="col">
                                            <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                                                <strong x-text="m.name"></strong>
                                                <span class="text-secondary" x-text="m.phone"></span>
                                                <span class="badge bg-blue text-blue-fg" x-show="!m.read_at">Nuevo</span>
                                            </div>
                                            <div class="text-body" style="white-space: pre-line" x-text="m.body"></div>
                                            <div class="small text-secondary mt-1" x-text="fmtDate(m.created_at)"></div>
                                        </div>
                                        <div class="col-auto d-flex gap-2">
                                            <a class="btn btn-whatsapp btn-action" :href="waReply(m)" target="_blank" rel="noopener" title="Responder por WhatsApp"><i class="ti ti-brand-whatsapp"></i><span class="d-none d-md-inline">WhatsApp</span></a>
                                            <button class="btn btn-outline-secondary btn-action" @click="toggleRead(m)" :title="m.read_at ? 'Marcar como no leído' : 'Marcar como leído'">
                                                <i class="ti" :class="m.read_at ? 'ti-mail' : 'ti-mail-opened'"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-action" @click="askDelete('messages', m)" title="Eliminar"><i class="ti ti-trash"></i><span class="d-none d-md-inline">Eliminar</span></button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div class="empty" x-show="!messages.length">
                                <div class="empty-icon"><i class="ti ti-inbox"></i></div>
                                <p class="empty-title">Sin mensajes</p>
                                <p class="empty-subtitle text-secondary">Los mensajes del formulario de contacto aparecerán aquí.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============ CRUD GENÉRICO (productos, servicios, ...) ============ -->
                <template x-if="resource">
                    <div class="card">
                        <div class="card-header">
                            <div class="input-icon w-100" style="max-width: 320px">
                                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                <input type="search" class="form-control" placeholder="Buscar…" x-model="search">
                            </div>
                        </div>
                        <div class="card-body py-2 small text-secondary border-bottom" x-show="rows.length > 1">
                            <i class="ti ti-arrows-sort me-1"></i>
                            <span x-show="canReorder">Arrastra <i class="ti ti-grip-vertical"></i> (o usa las flechas) para cambiar el orden en el sitio. Se guarda solo.</span>
                            <span x-show="!canReorder">Borra la búsqueda para poder cambiar el orden.</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th class="w-1"><span class="visually-hidden">Orden</span></th>
                                        <template x-for="col in resource.columns" :key="col.key"><th :class="col.hideSm && 'd-none d-md-table-cell'" x-text="col.label"></th></template>
                                        <th class="w-1">Activo</th>
                                        <th class="w-1"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, idx) in filteredRows" :key="row.id">
                                        <tr :class="{ 'is-dragging-row': dragRowId === row.id }"
                                            @dragover.prevent="dragOverRow(row, $event)" @drop.prevent="dropRow()">
                                            <td class="drag-cell">
                                                <template x-if="canReorder">
                                                    <div class="d-flex align-items-center">
                                                        <span class="drag-handle" draggable="true" title="Arrastra para cambiar el orden"
                                                              @dragstart="dragStartRow(row, $event)" @dragend="dropRow()"><i class="ti ti-grip-vertical"></i></span>
                                                        <span class="reorder-btns">
                                                            <button type="button" class="reorder-btn" @click="moveRow(idx, -1)" :disabled="idx === 0" aria-label="Subir"><i class="ti ti-chevron-up"></i></button>
                                                            <button type="button" class="reorder-btn" @click="moveRow(idx, 1)" :disabled="idx === filteredRows.length - 1" aria-label="Bajar"><i class="ti ti-chevron-down"></i></button>
                                                        </span>
                                                    </div>
                                                </template>
                                            </td>
                                            <template x-for="col in resource.columns" :key="col.key">
                                                <td :class="[col.class, col.hideSm && 'd-none d-md-table-cell']">
                                                    <template x-if="col.icon && !(col.imageKey && row[col.imageKey])"><i :class="row[col.key] || 'ti ti-lock-square-rounded'" class="me-1 fs-2"></i></template>
                                                    <template x-if="col.imageKey && row[col.imageKey]"><span class="avatar avatar-sm rounded" :style="`background-image: url('${row[col.imageKey]}')`"></span></template>
                                                    <span x-text="cell(row, col)"></span>
                                                </td>
                                            </template>
                                            <td>
                                                <label class="form-check form-switch m-0">
                                                    <input class="form-check-input" type="checkbox" :checked="+row.active" @change="toggleActive(row)">
                                                </label>
                                            </td>
                                            <td class="text-nowrap">
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <button class="btn btn-outline-primary btn-action" @click="openForm(row)" title="Editar"><i class="ti ti-pencil"></i><span class="d-none d-md-inline">Editar</span></button>
                                                    <button class="btn btn-outline-danger btn-action" @click="askDelete(resourceKey, row)" title="Eliminar"><i class="ti ti-trash"></i><span class="d-none d-md-inline">Eliminar</span></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="!filteredRows.length"><td :colspan="resource.columns.length + 3" class="text-center text-secondary py-4">Sin registros</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- ============ CONFIGURACIÓN ============ -->
                <form x-show="view === 'settings'" @submit.prevent="saveSettings" class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs flex-nowrap overflow-auto">
                            <template x-for="group in settingGroups" :key="group.title">
                                <li class="nav-item">
                                    <a href="#" class="nav-link text-nowrap" :class="{ active: settingTab === group.title }" @click.prevent="settingTab = group.title">
                                        <i class="ti me-1" :class="group.icon"></i><span x-text="group.title"></span>
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <div class="card-body">
                        <template x-for="group in settingGroups" :key="group.title">
                            <div x-show="settingTab === group.title">
                                <p class="text-secondary" x-show="group.intro" x-html="group.intro"></p>

                                <!-- Extras de la pestaña Correo -->
                                <template x-if="group.mail">
                                    <div class="mb-4">
                                        <div class="alert alert-danger" x-show="settings.mail_last_error">
                                            <div class="d-flex gap-2"><i class="ti ti-mail-x mt-1"></i>
                                                <div><strong>El último envío falló.</strong> <span x-text="settings.mail_last_error"></span></div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <span class="text-secondary small me-1">Datos rápidos:</span>
                                            <button type="button" class="btn btn-sm" @click="mailPreset('gmail')"><i class="ti ti-brand-gmail me-1 text-red"></i>Gmail</button>
                                            <button type="button" class="btn btn-sm" @click="mailPreset('hostinger')"><i class="ti ti-server me-1"></i>Correo de Hostinger</button>
                                            <button type="button" class="btn btn-sm btn-outline-primary ms-md-auto" @click="testMail" :disabled="busy">
                                                <i class="ti ti-send me-1"></i>Enviar correo de prueba
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <!-- Vista previa en Google (solo pestaña SEO) -->
                                <template x-if="group.preview">
                                    <div class="card card-sm bg-body-tertiary mb-4">
                                        <div class="card-body">
                                            <div class="small text-secondary mb-1">Vista previa aproximada en Google</div>
                                            <div class="small text-body" x-text="(settings.site_url || location.origin).replace(/\/$/, '')"></div>
                                            <div class="fs-3 text-primary text-truncate" x-text="settings.site_title"></div>
                                            <div class="small text-secondary" x-text="(settings.seo_description || settings.hero_text || '').slice(0, 160)"></div>
                                            <div class="small mt-2" :class="seoLenClass">
                                                Meta descripción: <strong x-text="(settings.seo_description || '').length"></strong> caracteres
                                                <span x-text="seoLenHint"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div class="row g-3">
                                    <template x-for="key in group.keys" :key="key">
                                        <div :class="['short', 'select'].includes(fieldKind(key)) ? 'col-md-6' : 'col-12'">
                                            <template x-if="fieldKind(key) === 'switch'">
                                                <label class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" :checked="settings[key] == '1'" @change="settings[key] = $event.target.checked ? '1' : '0'">
                                                    <span class="form-check-label" x-text="settingFields[key]?.label || key"></span>
                                                </label>
                                            </template>
                                            <template x-if="fieldKind(key) !== 'switch'">
                                                <label class="form-label" x-text="settingFields[key]?.label || key"></label>
                                            </template>
                                            <template x-if="fieldKind(key) === 'long'">
                                                <textarea class="form-control" rows="3" x-model="settings[key]"></textarea>
                                            </template>
                                            <template x-if="fieldKind(key) === 'code'">
                                                <textarea class="form-control font-monospace small" rows="6" spellcheck="false" x-model="settings[key]" placeholder="<script>…</script>"></textarea>
                                            </template>
                                            <template x-if="fieldKind(key) === 'select'">
                                                <select class="form-select" x-model="settings[key]">
                                                    <template x-for="(opt, i) in settingFields[key].options" :key="opt">
                                                        <option :value="opt" x-text="settingFields[key].labels?.[i] || opt" :selected="settings[key] === opt"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="fieldKind(key) === 'password'">
                                                <div class="input-group">
                                                    <input class="form-control" :type="showPass ? 'text' : 'password'" x-model="settings[key]" autocomplete="new-password"
                                                           :placeholder="settings.smtp_pass_set ? '•••••••• (guardada, déjala vacía para conservarla)' : 'Sin contraseña guardada'">
                                                    <button type="button" class="btn" @click="showPass = !showPass" :title="showPass ? 'Ocultar' : 'Mostrar'"><i class="ti" :class="showPass ? 'ti-eye-off' : 'ti-eye'"></i></button>
                                                </div>
                                            </template>
                                            <template x-if="fieldKind(key) === 'short'">
                                                <input class="form-control" x-model="settings[key]" :type="settingFields[key]?.type === 'url' ? 'url' : 'text'" :placeholder="settingFields[key]?.example || ''">
                                            </template>
                                            <div class="form-hint" x-show="settingFields[key]?.hint" x-html="settingFields[key]?.hint"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <a href="/" target="_blank" class="text-secondary small"><i class="ti ti-external-link"></i> Ver sitio</a>
                        <button class="btn btn-primary" :disabled="busy"><i class="ti ti-device-floppy me-1"></i> Guardar cambios</button>
                    </div>
                </form>

                <!-- ============ USUARIOS ============ -->
                <div x-show="view === 'users'">
                    <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-primary" @click="openUser()"><i class="ti ti-user-plus me-1"></i> Nuevo usuario</button>
                    </div>
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>Nombre</th><th class="d-none d-md-table-cell">Correo</th><th>Rol</th><th class="w-1"></th></tr></thead>
                                <tbody>
                                    <template x-for="u in users" :key="u.id">
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="avatar avatar-sm rounded-circle bg-primary-lt" x-text="initials(u.name)"></span>
                                                    <div>
                                                        <div class="fw-medium"><span x-text="u.name"></span> <span class="text-secondary small" x-show="u.id === user.id">(tú)</span> <span class="badge bg-yellow-lt ms-1" x-show="!!+u.is_owner" title="No se puede eliminar ni quitarle el rol; solo él puede editar su cuenta"><i class="ti ti-crown me-1"></i>Principal</span></div>
                                                        <div class="small text-secondary d-md-none" x-text="u.email"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell text-secondary" x-text="u.email"></td>
                                            <td><span class="badge" :class="u.role === 'admin' ? 'bg-primary text-primary-fg' : 'bg-secondary-lt'" x-text="roles[u.role] || u.role"></span></td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <button class="btn btn-outline-primary btn-action" @click="openUser(u)" :disabled="!!(+u.is_owner && u.id !== user.id)" title="Editar"><i class="ti ti-pencil"></i><span class="d-none d-md-inline">Editar</span></button>
                                                    <button class="btn btn-outline-danger btn-action" @click="askDelete('users', u)" :disabled="!!(u.id === user.id || +u.is_owner)" title="Eliminar"><i class="ti ti-trash"></i><span class="d-none d-md-inline">Eliminar</span></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-secondary small">
                            <strong>Administrador:</strong> acceso completo. <strong>Editor:</strong> mensajes, catálogo y servicios; no ve Configuración, Correo ni Usuarios.<span x-show="user.is_owner"> <strong>Principal</strong> (<i class="ti ti-crown"></i>): eres tú; no se te puede eliminar ni quitar el rol, solo tú editas tu cuenta y los demás administradores no te ven en esta lista.</span>
                        </div>
                    </div>
                </div>

                <!-- ============ CUENTA ============ -->
                <form x-show="view === 'account'" @submit.prevent="changePassword" class="card" style="max-width: 520px">
                    <div class="card-header"><h3 class="card-title">Cambiar contraseña</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Contraseña actual</label>
                            <input type="password" class="form-control" x-model="pwd.current" required autocomplete="current-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva contraseña <span class="text-secondary">(mín. 10)</span></label>
                            <input type="password" class="form-control" x-model="pwd.new" minlength="10" required autocomplete="new-password">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Repite la nueva contraseña</label>
                            <input type="password" class="form-control" x-model="pwd.confirm" minlength="10" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="card-footer text-end"><button class="btn btn-primary" :disabled="busy">Actualizar</button></div>
                </form>

            </div>
        </div>
    </div>

    <!-- ============ MODAL FORMULARIO GENÉRICO ============ -->
    <template x-if="form.open">
        <div>
            <div class="modal-backdrop fade show"></div>
            <div class="modal d-block" tabindex="-1" @keydown.escape.window="form.open = false" @click.self="form.open = false">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <form class="modal-content" @submit.prevent="saveForm">
                        <div class="modal-header">
                            <h5 class="modal-title" x-text="(form.data.id ? 'Editar ' : 'Nuevo ') + resource.singular"></h5>
                            <button type="button" class="btn-close" @click="form.open = false"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <template x-for="f in resource.fields" :key="f.key">
                                    <div :class="f.wide ? 'col-12' : 'col-md-6'">
                                        <label class="form-label" :class="{ required: f.required }" x-text="f.label"></label>

                                        <template x-if="f.type === 'textarea'">
                                            <textarea class="form-control" :rows="f.rows || 3" x-model="form.data[f.key]" :required="!!f.required"></textarea>
                                        </template>
                                        <template x-if="f.type === 'select'">
                                            <select class="form-select" x-model="form.data[f.key]">
                                                <template x-for="o in f.options" :key="o.value"><option :value="o.value" x-text="o.label" :selected="form.data[f.key] === o.value"></option></template>
                                            </select>
                                        </template>
                                        <template x-if="f.type === 'switch'">
                                            <label class="form-check form-switch mt-1">
                                                <input class="form-check-input" type="checkbox" x-model="form.data[f.key]">
                                                <span class="form-check-label" x-text="form.data[f.key] ? 'Sí' : 'No'"></span>
                                            </label>
                                        </template>
                                        <template x-if="f.type === 'image'">
                                            <div class="d-flex gap-3 align-items-start"
                                                 @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                                                 @drop.prevent="dragging = false; uploadImage($event.dataTransfer.files[0], f.key)">
                                                <div class="img-drop" :class="{ 'is-dragging': dragging }" @click="$refs.fileInput.click()" title="Haz clic o arrastra una imagen aquí">
                                                    <template x-if="form.data[f.key]"><img :src="form.data[f.key]" alt=""></template>
                                                    <template x-if="!form.data[f.key] && !uploading">
                                                        <div class="text-center text-secondary small"><i class="ti ti-photo-up d-block fs-1 mb-1"></i>Subir imagen</div>
                                                    </template>
                                                    <div class="img-drop-busy" x-show="uploading"><span class="spinner-border spinner-border-sm"></span></div>
                                                </div>
                                                <div class="flex-fill">
                                                    <input type="file" class="d-none" accept="image/jpeg,image/png,image/webp,image/gif" x-ref="fileInput"
                                                           @change="uploadImage($event.target.files[0], f.key); $event.target.value = ''">
                                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                                        <button type="button" class="btn btn-sm" @click="$refs.fileInput.click()" :disabled="uploading">
                                                            <i class="ti ti-upload me-1"></i><span x-text="form.data[f.key] ? 'Cambiar imagen' : 'Subir desde mi equipo'"></span>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-ghost-danger" x-show="form.data[f.key]" @click="form.data[f.key] = ''">
                                                            <i class="ti ti-x me-1"></i>Quitar
                                                        </button>
                                                    </div>
                                                    <input type="text" class="form-control form-control-sm" x-model="form.data[f.key]" placeholder="…o pega una URL: https://…">
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="f.type === 'gallery'">
                                            <div class="gallery-field" :class="{ 'is-dragging': dragging }"
                                                 @dragover.prevent="if ($event.dataTransfer.types.includes('Files')) dragging = true"
                                                 @dragleave.self="dragging = false"
                                                 @drop.prevent="dragging = false; if ($event.dataTransfer.files.length) uploadGallery($event.dataTransfer.files, f.key)">
                                                <div class="gallery-grid">
                                                    <template x-for="(img, i) in form.data[f.key]" :key="img">
                                                        <figure class="gallery-thumb" draggable="true"
                                                                @dragstart="dragFrom = i; $event.dataTransfer.effectAllowed = 'move'"
                                                                @dragover.prevent @drop.prevent.stop="moveImage(f.key, dragFrom, i); dragFrom = null"
                                                                @dragend="dragFrom = null" :class="{ 'is-moving': dragFrom === i }">
                                                            <img :src="img" alt="" loading="lazy">
                                                            <span class="gallery-cover" x-show="i === 0">Portada</span>
                                                            <figcaption class="gallery-tools">
                                                                <button type="button" class="gallery-btn" @click="moveImage(f.key, i, i - 1)" :disabled="i === 0" title="Mover a la izquierda" aria-label="Mover a la izquierda"><i class="ti ti-chevron-left"></i></button>
                                                                <button type="button" class="gallery-btn" @click="moveImage(f.key, i, 0)" x-show="i !== 0" title="Usar como portada" aria-label="Usar como portada"><i class="ti ti-star"></i></button>
                                                                <button type="button" class="gallery-btn gallery-btn--danger" @click="removeImage(f.key, i)" title="Quitar foto" aria-label="Quitar foto"><i class="ti ti-trash"></i></button>
                                                                <button type="button" class="gallery-btn" @click="moveImage(f.key, i, i + 1)" :disabled="i === form.data[f.key].length - 1" title="Mover a la derecha" aria-label="Mover a la derecha"><i class="ti ti-chevron-right"></i></button>
                                                            </figcaption>
                                                        </figure>
                                                    </template>
                                                    <button type="button" class="gallery-add" @click="$refs.galleryInput.click()" :disabled="uploading">
                                                        <template x-if="!uploading"><span><i class="ti ti-photo-plus d-block fs-1 mb-1"></i>Agregar fotos</span></template>
                                                        <template x-if="uploading"><span><span class="spinner-border spinner-border-sm d-block mx-auto mb-2"></span><span x-text="`Subiendo ${upload.done + 1} de ${upload.total}`"></span></span></template>
                                                    </button>
                                                </div>
                                                <input type="file" class="d-none" multiple accept="image/jpeg,image/png,image/webp,image/gif" x-ref="galleryInput"
                                                       @change="uploadGallery($event.target.files, f.key); $event.target.value = ''">
                                                <div class="input-group input-group-sm mt-2">
                                                    <input type="url" class="form-control" placeholder="…o agrega una foto por URL: https://…" x-ref="galleryUrl"
                                                           @keydown.enter.prevent="addImageUrl(f.key, $refs.galleryUrl)">
                                                    <button type="button" class="btn" @click="addImageUrl(f.key, $refs.galleryUrl)">Agregar</button>
                                                </div>
                                                <div class="small text-secondary mt-1" x-text="`${form.data[f.key].length} de 20 fotos`"></div>
                                            </div>
                                        </template>
                                        <template x-if="f.type === 'icon'">
                                            <div class="input-group">
                                                <span class="input-group-text" style="width: 2.75rem"><i class="fs-2" :class="form.data[f.key] || 'ti ti-lock-square-rounded'"></i></span>
                                                <input type="text" class="form-control" x-model="form.data[f.key]" placeholder="ti ti-key">
                                            </div>
                                        </template>
                                        <template x-if="['text','number','url'].includes(f.type)">
                                            <input class="form-control" :type="f.type" x-model="form.data[f.key]" :required="!!f.required" :placeholder="f.placeholder || ''">
                                        </template>

                                        <div class="form-hint" x-show="f.hint" x-html="f.hint"></div>
                                    </div>
                                </template>
                            </div>
                            <div class="alert alert-danger mt-3 mb-0 py-2" x-show="form.error" x-text="form.error"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" @click="form.open = false">Cancelar</button>
                            <button class="btn btn-primary" :disabled="busy">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- ============ MODAL USUARIO ============ -->
    <template x-if="userForm.open">
        <div>
            <div class="modal-backdrop fade show"></div>
            <div class="modal d-block" tabindex="-1" @keydown.escape.window="userForm.open = false" @click.self="userForm.open = false">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content" @submit.prevent="saveUser">
                        <div class="modal-header">
                            <h5 class="modal-title" x-text="userForm.data.id ? 'Editar usuario' : 'Nuevo usuario'"></h5>
                            <button type="button" class="btn-close" @click="userForm.open = false" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required">Nombre</label>
                                <input type="text" class="form-control" x-model="userForm.data.name" required maxlength="80">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Correo</label>
                                <input type="email" class="form-control" x-model="userForm.data.email" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select" x-model="userForm.data.role" :disabled="!!(userForm.data.id === user.id || userForm.data.is_owner)">
                                    <template x-for="(label, key) in roles" :key="key"><option :value="key" x-text="label" :selected="userForm.data.role === key"></option></template>
                                </select>
                                <div class="form-hint" x-show="userForm.data.id === user.id">No puedes cambiar tu propio rol.</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" :class="{ required: !userForm.data.id }" x-text="userForm.data.id ? 'Nueva contraseña (opcional)' : 'Contraseña'"></label>
                                <input type="password" class="form-control" x-model="userForm.data.password" :required="!userForm.data.id" minlength="10" autocomplete="new-password">
                                <div class="form-hint" x-text="userForm.data.id ? 'Déjala vacía para no cambiarla. Si la cambias, se cierran sus sesiones abiertas.' : 'Mínimo 10 caracteres. Compártela con la persona por un medio seguro.'"></div>
                            </div>
                            <div class="alert alert-danger mt-3 mb-0 py-2" x-show="userForm.error" x-text="userForm.error"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" @click="userForm.open = false">Cancelar</button>
                            <button class="btn btn-primary" :disabled="busy">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- ============ MODAL CONFIRMAR ELIMINACIÓN ============ -->
    <template x-if="confirm.open">
        <div>
            <div class="modal-backdrop fade show"></div>
            <div class="modal d-block" tabindex="-1" @keydown.escape.window="confirm.open = false" @click.self="confirm.open = false">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-status bg-danger"></div>
                        <div class="modal-body text-center py-4">
                            <i class="ti ti-alert-triangle text-danger" style="font-size: 2.5rem"></i>
                            <h3 class="mt-2">¿Eliminar?</h3>
                            <div class="text-secondary">Esta acción no se puede deshacer.</div>
                        </div>
                        <div class="modal-footer">
                            <div class="w-100 d-flex gap-2">
                                <button class="btn w-100" @click="confirm.open = false">Cancelar</button>
                                <button class="btn btn-danger w-100" @click="doDelete" :disabled="busy">Eliminar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
</template>

<!-- ============ AVISOS ============ -->
<div class="toast-stack">
    <template x-for="t in toasts" :key="t.id">
        <div class="alert alert-important mb-2 shadow" :class="t.type === 'error' ? 'alert-danger' : 'alert-success'" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="ti" :class="t.type === 'error' ? 'ti-alert-circle' : 'ti-check'"></i>
                <span x-text="t.text"></span>
            </div>
        </div>
    </template>
</div>

</body>
</html>
