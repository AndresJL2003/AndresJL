<?php
/**
 * Clase Mailer
 * Utilidad para envío de correos electrónicos
 * Soporta tanto la función mail() de PHP como SMTP
 */

class Mailer {

    /**
     * Envía un correo electrónico
     *
     * @param string $to Destinatario
     * @param string $subject Asunto
     * @param string $body Cuerpo del mensaje (HTML)
     * @return bool True si se envió correctamente
     */
    public static function enviar($to, $subject, $body) {
        if (SMTP_ENABLED) {
            return self::enviarSMTP($to, $subject, $body);
        } else {
            return self::enviarPHP($to, $subject, $body);
        }
    }

    /**
     * Envía un correo usando la función mail() de PHP
     *
     * @param string $to Destinatario
     * @param string $subject Asunto
     * @param string $body Cuerpo del mensaje (HTML)
     * @return bool True si se envió correctamente
     */
    private static function enviarPHP($to, $subject, $body) {
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>';
        $headers[] = 'Reply-To: ' . MAIL_FROM;
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * Envía un correo usando SMTP con PHPMailer
     *
     * @param string $to Destinatario
     * @param string $subject Asunto
     * @param string $body Cuerpo del mensaje (HTML)
     * @return bool True si se envió correctamente
     */
    private static function enviarSMTP($to, $subject, $body) {
        // Cargar PHPMailer
        require_once __DIR__ . '/../../vendor/autoload.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            // Remitente y destinatario
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($to);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            // Enviar
            $mail->send();
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error al enviar correo SMTP: " . $mail->ErrorInfo);
            }
            return false;
        }
    }

    /**
     * Envía correo de activación de cuenta
     *
     * @param string $email Email del destinatario
     * @param string $nombre Nombre del usuario
     * @param string $token Token de activación
     * @return bool True si se envió correctamente
     */
    public static function enviarActivacion($email, $nombre, $token) {
        $url_activacion = base_url('activar.php?token=' . $token);

        $subject = 'Activa tu cuenta - Plataforma Educativa';

        $body = self::getPlantillaHTML('
            <h2>¡Bienvenido, ' . htmlspecialchars($nombre) . '!</h2>
            <p>Gracias por registrarte en nuestra plataforma educativa.</p>
            <p>Para activar tu cuenta, haz clic en el siguiente botón:</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $url_activacion . '" style="background-color: #4F46E5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    Activar mi cuenta
                </a>
            </div>
            <p>O copia y pega este enlace en tu navegador:</p>
            <p style="word-break: break-all; color: #6B7280;">' . $url_activacion . '</p>
            <p style="color: #6B7280; font-size: 14px;">Este enlace expirará en ' . TOKEN_ACTIVATION_EXPIRY . ' horas.</p>
        ');

        return self::enviar($email, $subject, $body);
    }

    /**
     * Envía correo de recuperación de contraseña
     *
     * @param string $email Email del destinatario
     * @param string $nombre Nombre del usuario
     * @param string $token Token de recuperación
     * @return bool True si se envió correctamente
     */
    public static function enviarRecuperacion($email, $nombre, $token) {
        $url_recuperacion = base_url('recuperar.php?token=' . $token);

        $subject = 'Recupera tu contraseña - Plataforma Educativa';

        $body = self::getPlantillaHTML('
            <h2>Hola, ' . htmlspecialchars($nombre) . '</h2>
            <p>Recibimos una solicitud para restablecer tu contraseña.</p>
            <p>Si fuiste tú quien lo solicitó, haz clic en el siguiente botón:</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $url_recuperacion . '" style="background-color: #4F46E5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    Restablecer contraseña
                </a>
            </div>
            <p>O copia y pega este enlace en tu navegador:</p>
            <p style="word-break: break-all; color: #6B7280;">' . $url_recuperacion . '</p>
            <p style="color: #6B7280; font-size: 14px;">Este enlace expirará en ' . TOKEN_RECOVERY_EXPIRY . ' hora(s).</p>
            <p style="color: #DC2626; font-size: 14px;"><strong>Si no solicitaste este cambio, ignora este correo.</strong></p>
        ');

        return self::enviar($email, $subject, $body);
    }

    /**
     * Envía correo de bienvenida después de activar la cuenta
     *
     * @param string $email Email del destinatario
     * @param string $nombre Nombre del usuario
     * @return bool True si se envió correctamente
     */
    public static function enviarBienvenida($email, $nombre) {
        $url_login = base_url('login.php');

        $subject = '¡Tu cuenta ha sido activada! - Plataforma Educativa';

        $body = self::getPlantillaHTML('
            <h2>¡Cuenta activada exitosamente!</h2>
            <p>Hola, ' . htmlspecialchars($nombre) . '</p>
            <p>Tu cuenta ha sido activada correctamente. Ya puedes acceder a todos nuestros cursos.</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $url_login . '" style="background-color: #10B981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    Iniciar sesión
                </a>
            </div>
            <p>¡Comienza tu aprendizaje hoy mismo!</p>
        ');

        return self::enviar($email, $subject, $body);
    }

    /**
     * Plantilla HTML base para los correos
     *
     * @param string $contenido Contenido del correo
     * @return string HTML completo
     */
    private static function getPlantillaHTML($contenido) {
        return '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma Educativa</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #F3F4F6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F3F4F6; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #4F46E5; padding: 30px; text-align: center;">
                            <h1 style="color: #FFFFFF; margin: 0; font-size: 28px;">Plataforma Educativa</h1>
                        </td>
                    </tr>
                    <!-- Contenido -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            ' . $contenido . '
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F9FAFB; padding: 20px; text-align: center; border-top: 1px solid #E5E7EB;">
                            <p style="margin: 0; color: #6B7280; font-size: 12px;">
                                &copy; ' . date('Y') . ' Plataforma Educativa. Todos los derechos reservados.
                            </p>
                            <p style="margin: 10px 0 0 0; color: #6B7280; font-size: 12px;">
                                Si tienes alguna pregunta, contáctanos a ' . MAIL_FROM . '
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Envía correo de notificación de nueva sesión
     *
     * @param string $email Email del destinatario
     * @param string $nombre Nombre del usuario
     * @param string $dispositivo Información del dispositivo
     * @param string $ip Dirección IP
     * @return bool True si se envió correctamente
     */
    public static function enviarNotificacionNuevaSesion($email, $nombre, $dispositivo, $ip) {
        $subject = 'Nueva sesión iniciada - Plataforma Educativa';

        $body = self::getPlantillaHTML('
            <h2>Nueva sesión detectada</h2>
            <p>Hola, ' . htmlspecialchars($nombre) . '</p>
            <p>Se ha iniciado una nueva sesión en tu cuenta:</p>
            <ul style="color: #374151;">
                <li><strong>Dispositivo:</strong> ' . htmlspecialchars($dispositivo) . '</li>
                <li><strong>IP:</strong> ' . htmlspecialchars($ip) . '</li>
                <li><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</li>
            </ul>
            <p style="color: #DC2626;"><strong>Si no fuiste tú, cambia tu contraseña inmediatamente.</strong></p>
        ');

        return self::enviar($email, $subject, $body);
    }
}
