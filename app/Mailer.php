<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once BASE_PATH . '/lib/phpmailer/Exception.php';
require_once BASE_PATH . '/lib/phpmailer/PHPMailer.php';
require_once BASE_PATH . '/lib/phpmailer/SMTP.php';

/**
 * Envío de correos por SMTP (Gmail, Hostinger, Outlook…) con PHPMailer.
 * La configuración se captura en Panel → Configuración → Correo.
 */
final class Mailer
{
    /** ¿Están completos los datos mínimos para enviar? */
    public static function isConfigured(): bool
    {
        $s = settings();
        return ($s['smtp_host'] ?? '') !== '' && ($s['smtp_user'] ?? '') !== ''
            && ($s['smtp_pass'] ?? '') !== '' && self::recipients() !== [];
    }

    /** @return string[] */
    public static function recipients(): array
    {
        $list = preg_split('/[\s,;]+/', settings()['mail_to'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_filter($list, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
    }

    /**
     * Envía un correo a los destinatarios configurados.
     * @throws \PHPMailer\PHPMailer\Exception|RuntimeException
     */
    public static function send(string $subject, string $html, string $text): void
    {
        $s = settings();
        $pass = secret_decrypt($s['smtp_pass'] ?? '');
        if ($pass === '') {
            throw new RuntimeException('No se pudo leer la contraseña SMTP. Vuelve a capturarla en Configuración → Correo.');
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $s['smtp_host'];
        $mail->Port       = (int) ($s['smtp_port'] ?: 587);
        $mail->SMTPAuth   = true;
        $mail->Username   = $s['smtp_user'];
        $mail->Password   = $pass;
        $mail->SMTPSecure = match ($s['smtp_secure'] ?? 'tls') {
            'ssl'   => PHPMailer::ENCRYPTION_SMTPS,
            'none'  => '',
            default => PHPMailer::ENCRYPTION_STARTTLS,
        };
        $mail->SMTPAutoTLS = ($s['smtp_secure'] ?? 'tls') !== 'none';
        $mail->Timeout  = 15;
        $mail->getSMTPInstance()->Timelimit = 20; // evita que el formulario se quede esperando minutos
        $mail->CharSet  = PHPMailer::CHARSET_UTF8;
        $mail->XMailer  = ' ';

        // Gmail y la mayoría de servidores exigen que el remitente sea la misma cuenta.
        $brand = trim(($s['brand_name'] ?? '') . ' ' . ($s['brand_highlight'] ?? ''));
        $mail->setFrom($s['smtp_user'], ($s['mail_from_name'] ?? '') !== '' ? $s['mail_from_name'] : $brand);
        foreach (self::recipients() as $to) {
            $mail->addAddress($to);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text;
        $mail->send();
    }

    /**
     * Notificación de un mensaje nuevo del formulario.
     * Nunca lanza excepción: si falla, se registra y el mensaje queda guardado en el panel.
     */
    public static function notifyNewMessage(array $m): void
    {
        $s = settings();
        if (($s['mail_enabled'] ?? '0') !== '1' || !self::isConfigured()) {
            return;
        }
        try {
            $brand = trim(($s['brand_name'] ?? '') . ' ' . ($s['brand_highlight'] ?? ''));
            $phone = preg_replace('/\D+/', '', $m['phone']);
            $wa    = 'https://wa.me/' . $phone . '?text=' . rawurlencode("Hola {$m['name']}, gracias por escribir a {$brand}.");
            $panel = base_url() . '/admin#/messages';

            $html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#1e293b">'
                . '<div style="background:#0b1d3f;color:#fff;padding:18px 24px;border-radius:12px 12px 0 0">'
                . '<strong style="font-size:18px">' . e($brand) . '</strong><br><span style="opacity:.8">Nuevo mensaje desde el sitio web</span></div>'
                . '<div style="border:1px solid #e2e8f0;border-top:0;padding:24px;border-radius:0 0 12px 12px">'
                . '<p style="margin:0 0 6px"><strong>Nombre:</strong> ' . e($m['name']) . '</p>'
                . '<p style="margin:0 0 6px"><strong>Teléfono / WhatsApp:</strong> ' . e($m['phone']) . '</p>'
                . '<p style="margin:16px 0 6px"><strong>Mensaje:</strong></p>'
                . '<div style="background:#f8fafc;border-radius:8px;padding:12px 16px;white-space:pre-line">' . e($m['body']) . '</div>'
                . '<p style="margin:24px 0 0">'
                . ($phone !== '' ? '<a href="' . e($wa) . '" style="background:#25d366;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:bold;margin-right:8px">Responder por WhatsApp</a>' : '')
                . '<a href="' . e($panel) . '" style="background:#6366f1;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:bold">Ver en el panel</a></p>'
                . '<p style="color:#94a3b8;font-size:12px;margin-top:24px">Recibido el ' . gmdate('d/m/Y H:i') . ' UTC</p>'
                . '</div></div>';

            $text = "Nuevo mensaje desde el sitio web de {$brand}\n\n"
                . "Nombre: {$m['name']}\nTeléfono: {$m['phone']}\n\nMensaje:\n{$m['body']}\n\n"
                . ($phone !== '' ? "Responder por WhatsApp: {$wa}\n" : '') . "Panel: {$panel}\n";

            self::send("Nuevo mensaje de {$m['name']} · {$brand}", $html, $text);
            set_setting('mail_last_error', '');
        } catch (Throwable $e) {
            error_log('[Mailer] ' . $e->getMessage());
            set_setting('mail_last_error', gmdate('Y-m-d H:i') . ' UTC — ' . mb_substr($e->getMessage(), 0, 300));
        }
    }
}
