/**
 * Royal Vault · Dashboard SPA (Alpine.js + Tabler)
 *
 * Para agregar un nuevo módulo CRUD:
 *   1. Crea la tabla en app/Database.php
 *   2. Descríbela en resources() de app/helpers.php (validación en servidor)
 *   3. Agrégala en RESOURCES (abajo) y en MENU
 */

const ICON_HINT = 'Icono de línea de <a href="https://tabler.io/icons" target="_blank" rel="noopener">Tabler Icons</a>, ej. <code>ti ti-key</code>. Vacío = caja fuerte.';

const RESOURCES = {
    products: {
        title: 'Catálogo de cajas fuertes',
        singular: 'producto',
        search: ['name', 'description', 'badge'],
        columns: [
            { key: 'icon', label: '', icon: true, imageKey: 'image_url', class: 'w-1 text-primary' },
            { key: 'name', label: 'Nombre', class: 'fw-medium' },
            { key: 'badge', label: 'Etiqueta', hideSm: true },
            { key: 'description', label: 'Descripción', class: 'text-secondary text-truncate', max: 60, hideSm: true },
            { key: 'sort', label: 'Orden', class: 'w-1', hideSm: true },
        ],
        fields: [
            { key: 'name', label: 'Nombre', type: 'text', required: true },
            { key: 'badge', label: 'Etiqueta', type: 'text', placeholder: 'Residencial' },
            { key: 'description', label: 'Descripción corta', type: 'textarea', wide: true, rows: 2 },
            { key: 'features', label: 'Características', type: 'textarea', wide: true, rows: 4, hint: 'Una por línea.' },
            { key: 'badge_style', label: 'Color de etiqueta', type: 'select', options: [{ value: 'primary', label: 'Plata (relleno)' }, { value: 'dark', label: 'Contorno' }] },
            { key: 'icon', label: 'Icono', type: 'icon', hint: ICON_HINT },
            { key: 'images', label: 'Fotos', type: 'gallery', wide: true, folder: 'products', hint: 'Puedes subir varias a la vez (JPG, PNG, WEBP o GIF, máx. 5 MB cada una; se optimizan solas). Arrastra para ordenar: la primera es la portada. Sin fotos se muestra el icono.' },
            { key: 'sort', label: 'Orden', type: 'number' },
            { key: 'active', label: 'Visible en el sitio', type: 'switch' },
        ],
        defaults: { name: '', badge: '', description: '', features: '', badge_style: 'primary', icon: '', images: [], sort: 0, active: true },
    },
    services: {
        title: 'Servicios técnicos',
        singular: 'servicio',
        search: ['title', 'description'],
        columns: [
            { key: 'icon', label: '', icon: true, class: 'w-1 text-primary' },
            { key: 'title', label: 'Título', class: 'fw-medium' },
            { key: 'description', label: 'Descripción', class: 'text-secondary', max: 80, hideSm: true },
            { key: 'sort', label: 'Orden', class: 'w-1', hideSm: true },
        ],
        fields: [
            { key: 'title', label: 'Título', type: 'text', required: true },
            { key: 'icon', label: 'Icono', type: 'icon', hint: ICON_HINT },
            { key: 'description', label: 'Descripción', type: 'textarea', wide: true, rows: 3 },
            { key: 'cta_label', label: 'Texto del botón', type: 'text' },
            { key: 'wa_message', label: 'Mensaje prellenado de WhatsApp', type: 'text' },
            { key: 'sort', label: 'Orden', type: 'number' },
            { key: 'active', label: 'Visible en el sitio', type: 'switch' },
        ],
        defaults: { title: '', icon: 'ti ti-tool', description: '', cta_label: 'Más información', wa_message: '', sort: 0, active: true },
    },
};

const MENU = [
    { view: 'dashboard', label: 'Resumen', icon: 'ti-layout-dashboard', title: 'Resumen' },
    { view: 'messages', label: 'Mensajes', icon: 'ti-inbox', title: 'Mensajes de contacto' },
    { view: 'products', label: 'Catálogo', icon: 'ti-box', title: RESOURCES.products.title },
    { view: 'services', label: 'Servicios', icon: 'ti-tool', title: RESOURCES.services.title },
    { view: 'settings', label: 'Configuración', icon: 'ti-settings', title: 'Configuración del sitio', admin: true },
    { view: 'users', label: 'Usuarios', icon: 'ti-users', title: 'Usuarios del panel', admin: true },
    { view: 'account', label: 'Mi cuenta', icon: 'ti-user', title: 'Mi cuenta' },
];

const SETTING_GROUPS = [
    { title: 'Marca', icon: 'ti-crown', keys: ['brand_name', 'brand_highlight', 'brand_tagline', 'site_title'] },
    { title: 'Contacto', icon: 'ti-phone', keys: ['phone_display', 'whatsapp', 'topbar_text', 'address', 'hours', 'maps_url', 'maps_embed'] },
    { title: 'Portada', icon: 'ti-layout-navbar', keys: ['hero_badge', 'hero_title', 'hero_highlight', 'hero_text'] },
    { title: 'Nosotros y pie', icon: 'ti-info-circle', keys: ['about_text', 'footer_text', 'facebook_url', 'instagram_url'] },
    {
        title: 'SEO', icon: 'ti-brand-google', preview: true,
        intro: 'Cómo aparece el sitio en Google y al compartirlo en redes. El <a href="/sitemap.xml" target="_blank">sitemap.xml</a> y el <a href="/robots.txt" target="_blank">robots.txt</a> se generan solos.',
        keys: ['seo_index', 'site_url', 'seo_description', 'seo_keywords', 'og_title', 'og_description', 'og_image', 'business_city', 'business_region', 'business_postal', 'business_country'],
    },
    {
        title: 'Analítica', icon: 'ti-chart-bar',
        intro: 'Pega solo el ID; el código se agrega automáticamente. Los clics en WhatsApp/teléfono y los envíos del formulario se registran como eventos (<code>whatsapp_click</code>, <code>phone_click</code>, <code>generate_lead</code>).',
        keys: ['ga4_id', 'gtm_id', 'gads_id', 'meta_pixel_id', 'tiktok_pixel_id', 'google_verification', 'bing_verification'],
    },
    {
        title: 'Correo', icon: 'ti-mail', mail: true,
        intro: 'Recibe un aviso por correo cada vez que alguien use el formulario <em>Envíanos un Mensaje</em>. Guarda los cambios y luego usa <strong>Enviar correo de prueba</strong>.',
        keys: ['mail_enabled', 'mail_to', 'mail_from_name', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass'],
    },
    {
        title: 'Código extra', icon: 'ti-code',
        intro: '<span class="text-warning"><i class="ti ti-alert-triangle"></i> Este código se inserta sin filtrar en el sitio público.</span> Pega únicamente scripts de servicios confiables (chat en vivo, Microsoft Clarity, Hotjar…).',
        keys: ['custom_head', 'custom_body'],
    },
];
const LONG_SETTINGS = ['hero_text', 'about_text', 'hours', 'maps_embed', 'seo_description', 'og_description'];
const CODE_SETTINGS = ['custom_head', 'custom_body'];

// ---------------------------------------------------------------------------
//  Cliente de la API (agrega CSRF y maneja sesión expirada)
// ---------------------------------------------------------------------------
async function api(method, url, data) {
    const res = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content,
        },
        body: data !== undefined ? JSON.stringify(data) : undefined,
    });
    const json = await res.json().catch(() => ({}));
    if (res.status === 401 && !url.endsWith('/login')) {
        window.dispatchEvent(new CustomEvent('session-expired'));
    }
    if (!res.ok) {
        const err = new Error(json.error || `Error ${res.status}`);
        err.status = res.status;
        throw err;
    }
    return json;
}

// ---------------------------------------------------------------------------
//  Componente principal
// ---------------------------------------------------------------------------
document.addEventListener('alpine:init', () => {
    Alpine.data('app', () => ({
        ready: false,
        busy: false,
        user: null,
        needsSetup: false,
        auth: { email: '', password: '', name: '', setup_key: '', error: '' },

        view: 'dashboard',
        get menu() { return MENU.filter(m => !m.admin || this.isAdmin); },
        get isAdmin() { return this.user?.role === 'admin'; },
        menuOpen: false,
        theme: 'light',
        toasts: [],

        stats: { daily: [], latest: [], unread: 0 },
        messages: [],
        msgFilter: 'all',

        rows: [],
        search: '',
        form: { open: false, data: {}, error: '' },
        confirm: { open: false, resource: null, row: null },

        settings: {},
        settingFields: {},
        settingGroups: SETTING_GROUPS,
        settingTab: SETTING_GROUPS[0].title,
        showPass: false,

        pwd: { current: '', new: '', confirm: '' },

        // ---------- Arranque y navegación ----------
        async boot() {
            try { this.theme = localStorage.getItem('rv-theme') || 'light'; } catch (e) {}
            window.addEventListener('hashchange', () => this.route());
            window.addEventListener('session-expired', () => {
                if (this.user) { this.user = null; this.toast('Tu sesión expiró, vuelve a entrar.', 'error'); }
            });
            try {
                const me = await api('GET', '/api/me');
                this.user = me.user;
                this.needsSetup = me.needsSetup;
            } catch (e) {
                this.toast(e.message, 'error');
            }
            this.ready = true;
            if (this.user) this.route();
        },

        route() {
            const v = (location.hash.replace(/^#\/?/, '') || 'dashboard');
            this.view = this.menu.some(m => m.view === v) ? v : 'dashboard';
            this.search = '';
            this.load();
        },

        get title() { return MENU.find(m => m.view === this.view)?.title || ''; },
        get resourceKey() { return RESOURCES[this.view] ? this.view : null; },
        get resource() { return RESOURCES[this.view] || null; },

        async load() {
            try {
                if (this.view === 'dashboard') return await this.loadStats();
                if (this.view === 'messages') return await this.loadMessages();
                if (this.view === 'settings') return await this.loadSettings();
                if (this.view === 'users') return await this.loadUsers();
                if (this.resource) {
                    this.rows = [];
                    this.rows = await api('GET', `/api/admin/r/${this.view}`);
                }
                this.loadStats(); // mantiene el contador de no leídos al día
            } catch (e) {
                if (e.status !== 401) this.toast(e.message, 'error');
            }
        },

        // ---------- Autenticación ----------
        async login() {
            this.busy = true; this.auth.error = '';
            try {
                const r = await api('POST', '/api/login', { email: this.auth.email, password: this.auth.password });
                this.user = r.user;
                this.auth.password = '';
                this.route();
            } catch (e) { this.auth.error = e.message; }
            this.busy = false;
        },

        async setup() {
            this.busy = true; this.auth.error = '';
            try {
                const r = await api('POST', '/api/setup', {
                    setup_key: this.auth.setup_key, name: this.auth.name,
                    email: this.auth.email, password: this.auth.password,
                });
                this.user = r.user;
                this.needsSetup = false;
                this.auth.password = '';
                this.route();
                this.toast('¡Administrador creado!');
            } catch (e) { this.auth.error = e.message; }
            this.busy = false;
        },

        async logout() {
            try { await api('POST', '/api/logout'); } catch (e) {}
            this.user = null;
            location.hash = '';
        },

        // ---------- Resumen ----------
        async loadStats() {
            this.stats = await api('GET', '/api/admin/stats');
        },

        get kpis() {
            const s = this.stats;
            return [
                { label: 'Mensajes sin leer', value: s.unread ?? 0, icon: 'ti-mail', color: 'bg-red-lt', href: '#/messages' },
                { label: 'Mensajes esta semana', value: s.messages_week ?? 0, icon: 'ti-message-circle', color: 'bg-blue-lt', href: '#/messages' },
                { label: `Productos activos (de ${s.products_total ?? 0})`, value: s.products ?? 0, icon: 'ti-box', color: 'bg-primary-lt', href: '#/products' },
                { label: `Servicios activos (de ${s.services_total ?? 0})`, value: s.services ?? 0, icon: 'ti-tool', color: 'bg-green-lt', href: '#/services' },
            ];
        },

        barHeight(count) {
            const max = Math.max(1, ...this.stats.daily.map(d => d.count));
            return Math.max(3, Math.round((count / max) * 100));
        },

        // ---------- Mensajes ----------
        async loadMessages() {
            this.messages = await api('GET', `/api/admin/messages?filter=${this.msgFilter}`);
        },

        async toggleRead(m) {
            try {
                await api('PATCH', `/api/admin/messages/${m.id}`, { read: !m.read_at });
                await this.loadMessages();
                this.loadStats();
            } catch (e) { this.toast(e.message, 'error'); }
        },

        waReply(m) {
            const phone = String(m.phone).replace(/\D+/g, '');
            return `https://wa.me/${phone}?text=${encodeURIComponent(`Hola ${m.name}, gracias por escribirnos.`)}`;
        },

        // ---------- CRUD genérico ----------
        get filteredRows() {
            const q = this.search.trim().toLowerCase();
            if (!q || !this.resource) return this.rows;
            return this.rows.filter(r => this.resource.search.some(k => String(r[k] ?? '').toLowerCase().includes(q)));
        },

        cell(row, col) {
            if (col.icon) return '';
            const v = String(row[col.key] ?? '');
            return col.max && v.length > col.max ? v.slice(0, col.max) + '…' : v;
        },

        dragging: false,
        uploading: false,

        /** Sube un archivo y devuelve su ruta (/uploads/...). Lanza error con mensaje claro. */
        async uploadFile(file, folder) {
            if (!/^image\/(jpeg|png|webp|gif)$/.test(file.type)) throw new Error(`${file.name}: formato no permitido (usa JPG, PNG, WEBP o GIF).`);
            if (file.size > 5 * 1024 * 1024) throw new Error(`${file.name}: pesa más de 5 MB.`);
            const body = new FormData();
            body.append('file', file);
            const res = await fetch(`/api/admin/upload?folder=${folder || 'misc'}`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content },
                body,
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(`${file.name}: ${json.error || 'Error ' + res.status}`);
            return json.url;
        },

        async uploadImage(file, key) {
            if (!file) return;
            const field = this.resource.fields.find(f => f.key === key);
            this.uploading = true;
            try {
                this.form.data[key] = await this.uploadFile(file, field?.folder);
                this.toast('Imagen subida. Recuerda guardar.');
            } catch (e) { this.toast(e.message, 'error'); }
            this.uploading = false;
        },

        // ---------- Galería (varias fotos) ----------
        upload: { done: 0, total: 0 },
        dragFrom: null,

        async uploadGallery(fileList, key) {
            const files = Array.from(fileList || []);
            if (!files.length) return;
            const field = this.resource.fields.find(f => f.key === key);
            const list = this.form.data[key];
            const room = 20 - list.length;
            if (room <= 0) return this.toast('Máximo 20 fotos por producto.', 'error');
            if (files.length > room) this.toast(`Solo caben ${room} fotos más; se subirán las primeras ${room}.`, 'error');
            const queue = files.slice(0, room);
            this.uploading = true;
            this.upload = { done: 0, total: queue.length };
            const errors = [];
            for (const file of queue) {            // una por una: no satura el hosting compartido
                try { list.push(await this.uploadFile(file, field?.folder)); }
                catch (e) { errors.push(e.message); }
                this.upload.done++;
            }
            this.uploading = false;
            const ok = queue.length - errors.length;
            if (ok) this.toast(`${ok} foto${ok > 1 ? 's' : ''} subida${ok > 1 ? 's' : ''}. Recuerda guardar.`);
            errors.slice(0, 3).forEach(m => this.toast(m, 'error'));
        },

        addImageUrl(key, input) {
            const url = (input.value || '').trim();
            if (!/^https?:\/\//i.test(url)) return this.toast('La URL debe empezar con https://', 'error');
            if (this.form.data[key].length >= 20) return this.toast('Máximo 20 fotos por producto.', 'error');
            this.form.data[key].push(url);
            input.value = '';
        },

        removeImage(key, i) { this.form.data[key].splice(i, 1); },

        moveImage(key, from, to) {
            const list = this.form.data[key];
            if (from === null || from === to || to < 0 || to >= list.length) return;
            list.splice(to, 0, list.splice(from, 1)[0]);
        },

        openForm(row = null) {
            const data = { ...this.resource.defaults, ...(row || {}) };
            // Copia de las listas para no modificar la tabla si se cancela
            for (const f of this.resource.fields) if (f.type === 'gallery') data[f.key] = [...(data[f.key] || [])];
            if (!row) data.sort = (Math.max(0, ...this.rows.map(r => +r.sort)) + 1);
            data.active = !!+data.active;
            this.form = { open: true, data, error: '' };
        },

        async saveForm() {
            this.busy = true; this.form.error = '';
            const { id } = this.form.data;
            try {
                if (id) await api('PUT', `/api/admin/r/${this.resourceKey}/${id}`, this.form.data);
                else await api('POST', `/api/admin/r/${this.resourceKey}`, this.form.data);
                this.form.open = false;
                this.toast('Guardado');
                await this.load();
            } catch (e) { this.form.error = e.message; }
            this.busy = false;
        },

        async toggleActive(row) {
            try {
                await api('PUT', `/api/admin/r/${this.resourceKey}/${row.id}`, { ...row, active: !+row.active });
                row.active = +row.active ? 0 : 1;
                this.toast(row.active ? 'Ahora es visible en el sitio' : 'Oculto en el sitio');
            } catch (e) { this.toast(e.message, 'error'); this.load(); }
        },

        askDelete(resource, row) {
            this.confirm = { open: true, resource, row };
        },

        async doDelete() {
            const { resource, row } = this.confirm;
            this.busy = true;
            try {
                const url = resource === 'messages' ? `/api/admin/messages/${row.id}`
                    : resource === 'users' ? `/api/admin/users/${row.id}`
                    : `/api/admin/r/${resource}/${row.id}`;
                await api('DELETE', url);
                this.confirm.open = false;
                this.toast('Eliminado');
                await this.load();
                if (resource === 'messages') this.loadStats();
            } catch (e) { this.toast(e.message, 'error'); }
            this.busy = false;
        },

        // ---------- Usuarios ----------
        users: [],
        roles: {},
        userForm: { open: false, data: {}, error: '' },

        async loadUsers() {
            const r = await api('GET', '/api/admin/users');
            this.users = r.users;
            this.roles = r.roles;
        },

        openUser(u = null) {
            this.userForm = {
                open: true, error: '',
                data: u ? { id: u.id, name: u.name, email: u.email, role: u.role, password: '' }
                        : { name: '', email: '', role: 'editor', password: '' },
            };
        },

        async saveUser() {
            this.busy = true; this.userForm.error = '';
            const d = this.userForm.data;
            try {
                if (d.id) await api('PUT', `/api/admin/users/${d.id}`, d);
                else await api('POST', '/api/admin/users', d);
                this.userForm.open = false;
                this.toast(d.id ? 'Usuario actualizado' : 'Usuario creado');
                if (d.id === this.user.id) this.user = (await api('GET', '/api/me')).user;
                await this.loadUsers();
            } catch (e) { this.userForm.error = e.message; }
            this.busy = false;
        },

        // ---------- Configuración ----------
        async loadSettings() {
            const r = await api('GET', '/api/admin/settings');
            this.settings = r.values;
            this.settingFields = r.fields;
        },

        fieldKind(key) {
            if (CODE_SETTINGS.includes(key)) return 'code';
            const type = this.settingFields[key]?.type;
            if (type === 'bool') return 'switch';
            if (type === 'enum') return 'select';
            if (type === 'secret') return 'password';
            return LONG_SETTINGS.includes(key) ? 'long' : 'short';
        },

        mailPreset(name) {
            const presets = {
                gmail: { smtp_host: 'smtp.gmail.com', smtp_port: '587', smtp_secure: 'tls' },
                hostinger: { smtp_host: 'smtp.hostinger.com', smtp_port: '465', smtp_secure: 'ssl' },
            };
            Object.assign(this.settings, presets[name]);
            this.toast(name === 'gmail'
                ? 'Datos de Gmail listos. Captura tu correo y una contraseña de aplicación.'
                : 'Datos de Hostinger listos. Usa el correo y contraseña de tu buzón.');
        },

        async testMail() {
            this.busy = true;
            try {
                const r = await api('POST', '/api/admin/mail/test');
                this.settings.mail_last_error = '';
                this.toast('Correo de prueba enviado a ' + r.to.join(', '));
            } catch (e) {
                this.toast(e.message, 'error');
            }
            this.busy = false;
        },

        get seoLenClass() {
            const n = (this.settings.seo_description || '').length;
            return n >= 120 && n <= 165 ? 'text-success' : 'text-warning';
        },
        get seoLenHint() {
            const n = (this.settings.seo_description || '').length;
            if (!n) return '· vacía, se usará el texto de portada';
            if (n < 120) return '· un poco corta (ideal 140–160)';
            if (n > 165) return '· Google la recortará (ideal 140–160)';
            return '· longitud ideal';
        },

        async saveSettings() {
            this.busy = true;
            try {
                await api('PUT', '/api/admin/settings', this.settings);
                this.toast('Configuración guardada');
                await this.loadSettings();
            } catch (e) { this.toast(e.message, 'error'); }
            this.busy = false;
        },

        // ---------- Cuenta ----------
        async changePassword() {
            if (this.pwd.new !== this.pwd.confirm) return this.toast('Las contraseñas no coinciden', 'error');
            this.busy = true;
            try {
                await api('PUT', '/api/admin/account/password', { current: this.pwd.current, new: this.pwd.new });
                this.pwd = { current: '', new: '', confirm: '' };
                this.toast('Contraseña actualizada');
            } catch (e) { this.toast(e.message, 'error'); }
            this.busy = false;
        },

        // ---------- Utilidades ----------
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            try { localStorage.setItem('rv-theme', this.theme); } catch (e) {}
        },

        toast(text, type = 'success') {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, text, type });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 3500);
        },

        initials(name = '') {
            return name.split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('');
        },

        fmtDate(utc) {
            // La BD guarda UTC; se muestra en la hora local del navegador.
            const d = new Date(utc.replace(' ', 'T') + 'Z');
            return d.toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' });
        },
    }));
});
