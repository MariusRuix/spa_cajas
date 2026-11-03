<?php
/**
 * Va justo después de <body> (GTM noscript) y antes de </body> (eventos y código extra).
 * @var array $s
 * @var string $part  'open' | 'close'
 */
$gtm = preg_match('/^GTM-[A-Z0-9]{4,12}$/', $s['gtm_id'] ?? '') ? $s['gtm_id'] : '';
?>
<?php if ($part === 'open'): ?>
<?php if ($gtm): ?>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= $gtm ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>
<?php else: ?>
    <script>
        /* Eventos de conversión: clic en WhatsApp/teléfono y envío del formulario.
           Se envían a GA4/Ads (gtag), GTM (dataLayer), Meta y TikTok si están configurados. */
        window.rvTrack = function (name, params) {
            params = params || {};
            if (window.dataLayer) dataLayer.push(Object.assign({ event: name }, params));
            if (typeof gtag === 'function') gtag('event', name, params);
            var fbMap = { whatsapp_click: 'Contact', phone_click: 'Contact', generate_lead: 'Lead' };
            if (typeof fbq === 'function' && fbMap[name]) fbq('track', fbMap[name], params);
            if (window.ttq && fbMap[name]) ttq.track(fbMap[name] === 'Lead' ? 'SubmitForm' : 'Contact', params);
        };
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href]');
            if (!a) return;
            if (a.href.indexOf('https://wa.me/') === 0) rvTrack('whatsapp_click', { link_text: a.textContent.trim().slice(0, 80) });
            else if (a.href.indexOf('tel:') === 0) rvTrack('phone_click', {});
        });
    </script>
<?php if (($s['custom_body'] ?? '') !== ''): ?>
    <!-- Código personalizado (body) -->
<?= $s['custom_body'] ?>

<?php endif; ?>
<?php endif; ?>
