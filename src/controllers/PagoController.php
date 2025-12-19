<?php
/**
 * Clase PagoController
 * Controlador para gestión de pagos con Stripe
 */

class PagoController {
    private $pago_model;
    private $orden_model;
    private $carrito_model;
    private $auth_middleware;

    public function __construct() {
        $this->pago_model = new Pago();
        $this->orden_model = new Orden();
        $this->carrito_model = new Carrito();
        $this->auth_middleware = new AuthMiddleware();
    }

    /**
     * Crea una sesión de checkout de Stripe
     *
     * @param array $datos_facturacion Datos de facturación del usuario
     * @return array Respuesta con URL de checkout o error
     */
    public function crearCheckoutSession($datos_facturacion) {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return [
                'success' => false,
                'message' => 'Debes iniciar sesión'
            ];
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        // Cargar Stripe SDK
        require_once ROOT_PATH . '/vendor/autoload.php';
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

        // Obtener items del carrito
        $items_carrito = $this->carrito_model->obtenerItemsDetallados($usuario['id_usuario']);

        if (empty($items_carrito)) {
            return [
                'success' => false,
                'message' => 'El carrito está vacío'
            ];
        }

        try {
            // Crear orden en la base de datos
            $id_orden = $this->orden_model->crearOrden($usuario['id_usuario'], $datos_facturacion, $items_carrito);

            if (!$id_orden) {
                return [
                    'success' => false,
                    'message' => 'Error al crear la orden'
                ];
            }

            // Preparar line items para Stripe
            $line_items = [];
            $total = 0;

            foreach ($items_carrito as $item) {
                $line_items[] = [
                    'price_data' => [
                        'currency' => STRIPE_CURRENCY,
                        'product_data' => [
                            'name' => $item['titulo'],
                            'description' => substr($item['descripcion'], 0, 200),
                            'images' => [$item['imagen_url']]
                        ],
                        'unit_amount' => to_cents($item['precio_agregado'])
                    ],
                    'quantity' => 1
                ];

                $total += $item['precio_agregado'];
            }

            // Crear sesión de checkout en Stripe
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $line_items,
                'mode' => 'payment',
                'success_url' => str_replace('{CHECKOUT_SESSION_ID}', '{CHECKOUT_SESSION_ID}', STRIPE_SUCCESS_URL),
                'cancel_url' => STRIPE_CANCEL_URL,
                'customer_email' => $usuario['email'],
                'metadata' => [
                    'id_orden' => $id_orden,
                    'id_usuario' => $usuario['id_usuario'],
                    'numero_orden' => $this->orden_model->obtenerOrden($id_orden)['numero_orden']
                ]
            ]);

            // Guardar registro de pago en la base de datos
            $id_pago = $this->pago_model->crearPago(
                $id_orden,
                $usuario['id_usuario'],
                $total,
                $session->id
            );

            if (!$id_pago) {
                return [
                    'success' => false,
                    'message' => 'Error al registrar el pago'
                ];
            }

            return [
                'success' => true,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'id_orden' => $id_orden
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Error de Stripe API: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar el pago: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log("Error al crear checkout: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar el pago. Por favor intenta de nuevo.'
            ];
        }
    }

    /**
     * Procesa webhooks de Stripe
     *
     * @param string $payload Payload del webhook
     * @param string $signature Firma del webhook
     * @return void
     */
    public function procesarWebhook($payload, $signature) {
        $log_file = ROOT_PATH . '/stripe-webhook-log.txt';

        function log_msg($message) {
            global $log_file;
            $timestamp = date('Y-m-d H:i:s');
            @file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
        }

        log_msg("Iniciando procesarWebhook");

        // Cargar Stripe SDK
        require_once ROOT_PATH . '/vendor/autoload.php';
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

        log_msg("Stripe SDK cargado");

        try {
            // Verificar la firma del webhook
            log_msg("Verificando firma...");
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                STRIPE_WEBHOOK_SECRET
            );

            log_msg("Firma verificada. Evento tipo: " . $event->type);

        } catch(\UnexpectedValueException $e) {
            log_msg("ERROR: Payload inválido - " . $e->getMessage());
            error_log("Webhook error: Invalid payload");
            http_response_code(400);
            exit();
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            log_msg("ERROR: Firma inválida - " . $e->getMessage());
            error_log("Webhook error: Invalid signature");
            http_response_code(400);
            exit();
        }

        // Manejar el evento
        log_msg("Manejando evento: " . $event->type);

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                log_msg("Procesando pago exitoso para sesión: " . $session->id);
                $this->procesarPagoExitoso($session);
                log_msg("Pago exitoso procesado");
                break;

            case 'checkout.session.async_payment_succeeded':
                $session = $event->data->object;
                log_msg("Procesando pago async exitoso para sesión: " . $session->id);
                $this->procesarPagoExitoso($session);
                break;

            case 'checkout.session.async_payment_failed':
                $session = $event->data->object;
                log_msg("Procesando pago fallido para sesión: " . $session->id);
                $this->procesarPagoFallido($session);
                break;

            case 'payment_intent.payment_failed':
                $intent = $event->data->object;
                log_msg("Procesando payment intent fallido: " . $intent->id);
                $this->procesarPagoFallidoIntent($intent);
                break;

            default:
                log_msg("Evento no manejado: " . $event->type);
                error_log("Evento no manejado: " . $event->type);
        }

        log_msg("Webhook procesado exitosamente");
        http_response_code(200);
    }

    /**
     * Procesa un pago exitoso
     *
     * @param object $session Sesión de Stripe
     * @return bool True si se procesó correctamente
     */
    private function procesarPagoExitoso($session) {
        try {
            // Verificar si ya fue procesado (idempotencia)
            if ($this->pago_model->sesionYaProcesada($session->id)) {
                error_log("Sesión ya procesada: " . $session->id);
                return true;
            }

            // Obtener información del pago
            $pago = $this->pago_model->obtenerPagoPorSession($session->id);

            if (!$pago) {
                error_log("Pago no encontrado para sesión: " . $session->id);
                return false;
            }

            // Actualizar estado del pago
            $this->pago_model->actualizarEstado($pago['id_pago'], 'completado', [
                'payment_intent_id' => $session->payment_intent,
                'customer_id' => $session->customer ?? null
            ]);

            // Actualizar estado de la orden
            $this->orden_model->actualizarEstado($pago['id_orden'], 'pagada', 'completado');

            // Procesar inscripciones automáticamente
            $this->orden_model->procesarInscripciones($pago['id_orden']);

            // Vaciar el carrito del usuario
            $this->carrito_model->vaciarCarrito($pago['id_usuario']);

            // Enviar email de confirmación
            $this->enviarEmailConfirmacion($pago['id_orden']);

            return true;

        } catch (Exception $e) {
            error_log("Error al procesar pago exitoso: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesa un pago fallido
     *
     * @param object $session Sesión de Stripe
     * @return bool True si se procesó correctamente
     */
    private function procesarPagoFallido($session) {
        try {
            $pago = $this->pago_model->obtenerPagoPorSession($session->id);

            if (!$pago) {
                return false;
            }

            // Actualizar estado del pago
            $this->pago_model->actualizarEstado($pago['id_pago'], 'fallido', [
                'mensaje_error' => 'Pago rechazado o cancelado'
            ]);

            // Actualizar estado de la orden
            $this->orden_model->actualizarEstado($pago['id_orden'], 'fallida', 'fallido');

            return true;

        } catch (Exception $e) {
            error_log("Error al procesar pago fallido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesa un pago fallido desde payment intent
     *
     * @param object $intent Payment intent de Stripe
     * @return bool True si se procesó correctamente
     */
    private function procesarPagoFallidoIntent($intent) {
        try {
            $pago = $this->pago_model->obtenerPagoPorIntent($intent->id);

            if (!$pago) {
                return false;
            }

            $mensaje_error = $intent->last_payment_error ? $intent->last_payment_error->message : 'Pago fallido';

            $this->pago_model->actualizarEstado($pago['id_pago'], 'fallido', [
                'mensaje_error' => $mensaje_error
            ]);

            $this->orden_model->actualizarEstado($pago['id_orden'], 'fallida', 'fallido');

            return true;

        } catch (Exception $e) {
            error_log("Error al procesar payment intent fallido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica el estado de un pago
     *
     * @param string $session_id ID de la sesión de Stripe
     * @return array Respuesta con el estado del pago
     */
    public function verificarEstadoPago($session_id) {
        $pago = $this->pago_model->obtenerPagoPorSession($session_id);

        if (!$pago) {
            return [
                'success' => false,
                'message' => 'Pago no encontrado'
            ];
        }

        $orden = $this->orden_model->obtenerOrden($pago['id_orden']);
        $items = $this->orden_model->obtenerItemsOrden($pago['id_orden']);

        return [
            'success' => true,
            'pago' => $pago,
            'orden' => $orden,
            'items' => $items
        ];
    }

    /**
     * Obtiene los detalles de una orden
     *
     * @param int $id_orden ID de la orden
     * @return array|false Detalles de la orden o false
     */
    public function obtenerDetallesOrden($id_orden) {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return false;
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        // Verificar que la orden pertenece al usuario
        if (!$this->orden_model->perteneceAUsuario($id_orden, $usuario['id_usuario'])) {
            return false;
        }

        $orden = $this->orden_model->obtenerOrden($id_orden);
        $items = $this->orden_model->obtenerItemsOrden($id_orden);
        $pagos = $this->pago_model->obtenerPagosOrden($id_orden);

        return [
            'orden' => $orden,
            'items' => $items,
            'pagos' => $pagos
        ];
    }

    /**
     * Envía email de confirmación de compra
     *
     * @param int $id_orden ID de la orden
     * @return bool True si se envió correctamente
     */
    private function enviarEmailConfirmacion($id_orden) {
        try {
            $orden = $this->orden_model->obtenerOrden($id_orden);
            $items = $this->orden_model->obtenerItemsOrden($id_orden);

            if (!$orden || empty($items)) {
                return false;
            }

            // Utilizar la clase EmailService para enviar el correo
            $email_service = new EmailService();

            // Extraer nombre del nombre de facturación
            $nombre = explode(' ', $orden['nombre_facturacion'])[0];

            return $email_service->enviarConfirmacionCompra(
                $orden['email_facturacion'],
                $nombre,
                $orden,
                $items
            );

        } catch (Exception $e) {
            error_log("Error al enviar email de confirmación: " . $e->getMessage());
            return false;
        }
    }
}
