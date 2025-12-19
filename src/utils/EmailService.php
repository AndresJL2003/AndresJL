<?php
/**
 * Clase EmailService
 * Servicio para envío de correos electrónicos
 * Soporta tanto mail() de PHP como SMTP
 */

class EmailService {

    /**
     * Envía un correo de activación de cuenta
     *
     * @param string $email Email del destinatario
     * @param string $nombre Nombre del destinatario
     * @param string $token Token de activación
     * @return bool True si se envió correctamente
     */
    public function enviarActivacion($email, $nombre, $token) {
        $asunto = 'Activa tu cuenta - Plataforma Educativa';
        $url_activacion = base_url('activar.php?token=' . $token);

        $mensaje = $this->plantillaActivacion($nombre, $url_activacion);

        return $this->enviar($email, $nombre, $asunto, $mensaje);
    }

    /**
     * Envía un correo de recuperación de contraseña
     *
     * @param string $email Email del destinatario
     * @param string $nombre Nombre del destinatario
     * @param string $token Token de recuperación
     * @return bool True si se envió correctamente
     */
    public function enviarRecuperacion($email, $nombre, $token) {
        $asunto = 'Recuperación de contraseña - Plataforma Educativa';
        $url_recuperacion = base_url('recuperar.php?token=' . $token);

        $mensaje = $this->plantillaRecuperacion($nombre, $url_recuperacion);

        return $this->enviar($email, $nombre, $asunto, $mensaje);
    }

    /**
     * Envía un correo de bienvenida
     *
     * @param string $email Email del destinatario
     * @param string $nombre Nombre del destinatario
     * @return bool True si se envió correctamente
     */
    public function enviarBienvenida($email, $nombre) {
        $asunto = 'Bienvenido a Plataforma Educativa';
        $mensaje = $this->plantillaBienvenida($nombre);

        return $this->enviar($email, $nombre, $asunto, $mensaje);
    }

    /**
     * Función principal para enviar correos
     *
     * @param string $email Email del destinatario
     * @param string $nombre Nombre del destinatario
     * @param string $asunto Asunto del correo
     * @param string $mensaje Mensaje HTML
     * @return bool True si se envió correctamente
     */
    private function enviar($email, $nombre, $asunto, $mensaje) {
        if (SMTP_ENABLED) {
            return $this->enviarSMTP($email, $nombre, $asunto, $mensaje);
        } else {
            return $this->enviarPHP($email, $nombre, $asunto, $mensaje);
        }
    }

    /**
     * Envía correo usando la función mail() de PHP
     *
     * @param string $email Email del destinatario
     * @param string $nombre Nombre del destinatario
     * @param string $asunto Asunto del correo
     * @param string $mensaje Mensaje HTML
     * @return bool True si se envió correctamente
     */
    private function enviarPHP($email, $nombre, $asunto, $mensaje) {
        // Configurar cabeceras
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
            'Reply-To: ' . MAIL_FROM,
            'X-Mailer: PHP/' . phpversion()
        ];

        // Enviar correo
        return mail($email, $asunto, $mensaje, implode("\r\n", $headers));
    }

    /**
     * Envía correo usando SMTP con PHPMailer
     *
     * @param string $email Email del destinatario
     * @param string $nombre Nombre del destinatario
     * @param string $asunto Asunto del correo
     * @param string $mensaje Mensaje HTML
     * @return bool True si se envió correctamente
     */
    private function enviarSMTP($email, $nombre, $asunto, $mensaje) {
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
            $mail->addAddress($email, $nombre);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $mensaje;

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
     * Plantilla HTML para correo de activación
     *
     * @param string $nombre Nombre del usuario
     * @param string $url_activacion URL de activación
     * @return string HTML del correo
     */
    private function plantillaActivacion($nombre, $url_activacion) {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activa tu cuenta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 30px 20px;
            text-align: center;
        }
        .content {
            padding: 30px 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            background: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Bienvenido a Plataforma Educativa!</h1>
        </div>
        <div class="content">
            <p>Hola <strong>$nombre</strong>,</p>
            <p>Gracias por registrarte en nuestra plataforma educativa. Para completar tu registro y comenzar a disfrutar de nuestros cursos, por favor activa tu cuenta haciendo clic en el siguiente botón:</p>
            <p style="text-align: center;">
                <a href="$url_activacion" class="button">Activar mi cuenta</a>
            </p>
            <p>Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
            <p style="word-break: break-all; font-size: 12px; color: #666;">$url_activacion</p>
            <p>Este enlace es válido por 24 horas.</p>
            <p>Si no solicitaste esta cuenta, puedes ignorar este correo.</p>
        </div>
        <div class="footer">
            <p>© 2024 Plataforma Educativa. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Plantilla HTML para correo de recuperación
     *
     * @param string $nombre Nombre del usuario
     * @param string $url_recuperacion URL de recuperación
     * @return string HTML del correo
     */
    private function plantillaRecuperacion($nombre, $url_recuperacion) {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recupera tu contraseña</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #fff;
            padding: 30px 20px;
            text-align: center;
        }
        .content {
            padding: 30px 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            background: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Recuperación de Contraseña</h1>
        </div>
        <div class="content">
            <p>Hola <strong>$nombre</strong>,</p>
            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. Si no realizaste esta solicitud, puedes ignorar este correo.</p>
            <p style="text-align: center;">
                <a href="$url_recuperacion" class="button">Restablecer mi contraseña</a>
            </p>
            <p>Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
            <p style="word-break: break-all; font-size: 12px; color: #666;">$url_recuperacion</p>
            <div class="warning">
                <strong>⚠️ Importante:</strong> Este enlace es válido solo por 1 hora por razones de seguridad.
            </div>
            <p>Si no solicitaste este cambio de contraseña, te recomendamos que cambies tu contraseña inmediatamente para proteger tu cuenta.</p>
        </div>
        <div class="footer">
            <p>© 2024 Plataforma Educativa. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Plantilla HTML para correo de bienvenida
     *
     * @param string $nombre Nombre del usuario
     * @return string HTML del correo
     */
    private function plantillaBienvenida($nombre) {
        $url_cursos = base_url('cursos.php');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: #fff;
            padding: 30px 20px;
            text-align: center;
        }
        .content {
            padding: 30px 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            background: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Bienvenido a Plataforma Educativa!</h1>
        </div>
        <div class="content">
            <p>Hola <strong>$nombre</strong>,</p>
            <p>¡Tu cuenta ha sido activada exitosamente! Ahora puedes acceder a todos nuestros cursos y recursos educativos.</p>
            <p>Explora nuestro catálogo de cursos y comienza tu viaje de aprendizaje hoy mismo.</p>
            <p style="text-align: center;">
                <a href="$url_cursos" class="button">Explorar Cursos</a>
            </p>
            <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.</p>
            <p>¡Feliz aprendizaje!</p>
        </div>
        <div class="footer">
            <p>© 2024 Plataforma Educativa. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Envía un correo de confirmación de compra
     *
     * @param string $email Email del destinatario
     * @param string $nombre Nombre del destinatario
     * @param array $orden Datos de la orden
     * @param array $items Items comprados
     * @return bool True si se envió correctamente
     */
    public function enviarConfirmacionCompra($email, $nombre, $orden, $items) {
        $asunto = 'Confirmación de Compra #' . $orden['numero_orden'] . ' - Plataforma Educativa';
        $mensaje = $this->plantillaConfirmacionCompra($nombre, $orden, $items);

        return $this->enviar($email, $nombre, $asunto, $mensaje);
    }

    /**
     * Plantilla HTML para correo de confirmación de compra
     *
     * @param string $nombre Nombre del usuario
     * @param array $orden Datos de la orden
     * @param array $items Items comprados
     * @return string HTML del correo
     */
    private function plantillaConfirmacionCompra($nombre, $orden, $items) {
        $url_dashboard = base_url('dashboard.php');
        $url_compras = base_url('mis-compras.php');

        // Generar lista de items
        $items_html = '';
        foreach ($items as $item) {
            $precio = format_price($item['precio_unitario']);
            $items_html .= <<<HTML
            <tr>
                <td style="padding: 12px; border-bottom: 1px solid #E5E7EB;">
                    <strong>{$item['titulo_curso']}</strong>
                </td>
                <td style="padding: 12px; border-bottom: 1px solid #E5E7EB; text-align: right;">
                    {$precio}
                </td>
            </tr>
HTML;
        }

        $total = format_price($orden['total']);
        $numero_orden = htmlspecialchars($orden['numero_orden']);
        $fecha_orden = format_date($orden['fecha_creacion'], 'd/m/Y H:i');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Compra</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            padding: 30px 20px;
            text-align: center;
        }
        .content {
            padding: 30px 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            background: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .order-box {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .total-row {
            background: #f9fafb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ ¡Compra Exitosa!</h1>
        </div>
        <div class="content">
            <p>Hola <strong>$nombre</strong>,</p>
            <p>¡Gracias por tu compra! Tu pago ha sido procesado exitosamente y ya puedes acceder a tus cursos.</p>

            <div class="order-box">
                <h3 style="margin-top: 0; color: #374151;">Detalles de tu Compra</h3>
                <p style="margin: 5px 0;"><strong>Número de Orden:</strong> $numero_orden</p>
                <p style="margin: 5px 0;"><strong>Fecha:</strong> $fecha_orden</p>
                <p style="margin: 5px 0;"><strong>Total:</strong> <span style="color: #10b981; font-size: 20px; font-weight: bold;">$total</span></p>
            </div>

            <h3 style="color: #374151;">Cursos Adquiridos:</h3>
            <table>
                $items_html
                <tr class="total-row">
                    <td style="padding: 12px; font-weight: bold; text-align: right;">TOTAL:</td>
                    <td style="padding: 12px; font-weight: bold; text-align: right; color: #10b981; font-size: 18px;">
                        $total
                    </td>
                </tr>
            </table>

            <p style="text-align: center;">
                <a href="$url_dashboard" class="button">Ir a Mis Cursos</a>
            </p>

            <p style="text-align: center;">
                <a href="$url_compras" style="color: #6366f1; text-decoration: none;">Ver historial de compras</a>
            </p>

            <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
                Si tienes alguna pregunta sobre tu compra, no dudes en contactarnos.
            </p>
        </div>
        <div class="footer">
            <p>© 2024 Plataforma Educativa. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
