<?php
/**
 * Clase Pago
 * Modelo para gestión de pagos con Stripe
 */

class Pago {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crea un nuevo registro de pago
     *
     * @param int $id_orden ID de la orden
     * @param int $id_usuario ID del usuario
     * @param float $monto Monto del pago
     * @param string $stripe_session_id ID de la sesión de Stripe
     * @return int|false ID del pago creado o false
     */
    public function crearPago($id_orden, $id_usuario, $monto, $stripe_session_id) {
        $sql = "INSERT INTO pagos (
                    id_orden, id_usuario, monto, moneda, stripe_checkout_session_id
                )
                VALUES (
                    :id_orden, :id_usuario, :monto, :moneda, :stripe_session_id
                )";

        return $this->db->insert($sql, [
            ':id_orden' => $id_orden,
            ':id_usuario' => $id_usuario,
            ':monto' => $monto,
            ':moneda' => STRIPE_CURRENCY,
            ':stripe_session_id' => $stripe_session_id
        ]);
    }

    /**
     * Actualiza el estado de un pago
     *
     * @param int $id_pago ID del pago
     * @param string $estado Estado del pago
     * @param array $datos_stripe Datos adicionales de Stripe
     * @return bool True si se actualizó correctamente
     */
    public function actualizarEstado($id_pago, $estado, $datos_stripe = []) {
        $sql = "UPDATE pagos
                SET estado = :estado";

        $params = [
            ':id_pago' => $id_pago,
            ':estado' => $estado
        ];

        // Agregar campos opcionales si existen
        if (isset($datos_stripe['payment_intent_id'])) {
            $sql .= ", stripe_payment_intent_id = :payment_intent_id";
            $params[':payment_intent_id'] = $datos_stripe['payment_intent_id'];
        }

        if (isset($datos_stripe['customer_id'])) {
            $sql .= ", stripe_customer_id = :customer_id";
            $params[':customer_id'] = $datos_stripe['customer_id'];
        }

        if (isset($datos_stripe['metodo_pago'])) {
            $sql .= ", metodo_pago = :metodo_pago";
            $params[':metodo_pago'] = $datos_stripe['metodo_pago'];
        }

        if (isset($datos_stripe['mensaje_error'])) {
            $sql .= ", mensaje_error = :mensaje_error";
            $params[':mensaje_error'] = $datos_stripe['mensaje_error'];
        }

        // Si el pago se completó, actualizar fecha_completado
        if ($estado === 'completado') {
            $sql .= ", fecha_completado = NOW()";
        }

        $sql .= " WHERE id_pago = :id_pago";

        return $this->db->execute($sql, $params);
    }

    /**
     * Obtiene un pago por su session ID de Stripe
     *
     * @param string $stripe_session_id ID de sesión de Stripe
     * @return array|false Datos del pago o false
     */
    public function obtenerPagoPorSession($stripe_session_id) {
        $sql = "SELECT * FROM pagos WHERE stripe_checkout_session_id = :session_id";
        return $this->db->queryOne($sql, [':session_id' => $stripe_session_id]);
    }

    /**
     * Obtiene un pago por su payment intent ID de Stripe
     *
     * @param string $stripe_payment_intent_id ID de payment intent de Stripe
     * @return array|false Datos del pago o false
     */
    public function obtenerPagoPorIntent($stripe_payment_intent_id) {
        $sql = "SELECT * FROM pagos WHERE stripe_payment_intent_id = :intent_id";
        return $this->db->queryOne($sql, [':intent_id' => $stripe_payment_intent_id]);
    }

    /**
     * Obtiene todos los pagos de una orden
     *
     * @param int $id_orden ID de la orden
     * @return array Lista de pagos
     */
    public function obtenerPagosOrden($id_orden) {
        $sql = "SELECT * FROM pagos WHERE id_orden = :id_orden ORDER BY fecha_pago DESC";
        return $this->db->query($sql, [':id_orden' => $id_orden]);
    }

    /**
     * Obtiene un pago por su ID
     *
     * @param int $id_pago ID del pago
     * @return array|false Datos del pago o false
     */
    public function obtenerPago($id_pago) {
        $sql = "SELECT * FROM pagos WHERE id_pago = :id_pago";
        return $this->db->queryOne($sql, [':id_pago' => $id_pago]);
    }

    /**
     * Guarda metadata adicional del pago
     *
     * @param int $id_pago ID del pago
     * @param array $metadata Datos a guardar en formato JSON
     * @return bool True si se guardó correctamente
     */
    public function guardarMetadata($id_pago, $metadata) {
        $sql = "UPDATE pagos SET metadata = :metadata WHERE id_pago = :id_pago";

        return $this->db->execute($sql, [
            ':id_pago' => $id_pago,
            ':metadata' => json_encode($metadata)
        ]);
    }

    /**
     * Obtiene todos los pagos de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @param int $limit Número máximo de pagos a devolver
     * @return array Lista de pagos
     */
    public function obtenerPagosUsuario($id_usuario, $limit = 10) {
        $sql = "SELECT * FROM pagos
                WHERE id_usuario = :id_usuario
                ORDER BY fecha_pago DESC
                LIMIT :limit";

        return $this->db->query($sql, [
            ':id_usuario' => $id_usuario,
            ':limit' => (int)$limit
        ]);
    }

    /**
     * Verifica si una sesión de Stripe ya fue procesada
     *
     * @param string $stripe_session_id ID de sesión de Stripe
     * @return bool True si ya fue procesada
     */
    public function sesionYaProcesada($stripe_session_id) {
        $pago = $this->obtenerPagoPorSession($stripe_session_id);

        if (!$pago) {
            return false;
        }

        // Consideramos procesado si el estado no es 'pendiente'
        return $pago['estado'] !== 'pendiente';
    }

    /**
     * Obtiene estadísticas de pagos
     *
     * @param int $id_usuario ID del usuario (opcional)
     * @return array Estadísticas
     */
    public function obtenerEstadisticas($id_usuario = null) {
        if ($id_usuario) {
            $sql = "SELECT
                        COUNT(*) as total_pagos,
                        SUM(CASE WHEN estado = 'completado' THEN monto ELSE 0 END) as total_completado,
                        SUM(CASE WHEN estado = 'fallido' THEN 1 ELSE 0 END) as total_fallidos,
                        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as total_pendientes
                    FROM pagos
                    WHERE id_usuario = :id_usuario";

            $result = $this->db->queryOne($sql, [':id_usuario' => $id_usuario]);
        } else {
            $sql = "SELECT
                        COUNT(*) as total_pagos,
                        SUM(CASE WHEN estado = 'completado' THEN monto ELSE 0 END) as total_completado,
                        SUM(CASE WHEN estado = 'fallido' THEN 1 ELSE 0 END) as total_fallidos,
                        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as total_pendientes
                    FROM pagos";

            $result = $this->db->queryOne($sql);
        }

        return [
            'total_pagos' => (int)($result['total_pagos'] ?? 0),
            'total_completado' => (float)($result['total_completado'] ?? 0),
            'total_fallidos' => (int)($result['total_fallidos'] ?? 0),
            'total_pendientes' => (int)($result['total_pendientes'] ?? 0)
        ];
    }

    /**
     * Actualiza el pago cuando se recibe un webhook de Stripe
     *
     * @param string $stripe_session_id ID de sesión de Stripe
     * @param string $payment_intent_id ID de payment intent
     * @param string $customer_id ID del customer en Stripe
     * @param string $metodo_pago Método de pago utilizado
     * @return bool True si se actualizó correctamente
     */
    public function procesarWebhookCompletado($stripe_session_id, $payment_intent_id, $customer_id = null, $metodo_pago = 'card') {
        $pago = $this->obtenerPagoPorSession($stripe_session_id);

        if (!$pago) {
            return false;
        }

        return $this->actualizarEstado($pago['id_pago'], 'completado', [
            'payment_intent_id' => $payment_intent_id,
            'customer_id' => $customer_id,
            'metodo_pago' => $metodo_pago
        ]);
    }

    /**
     * Marca un pago como fallido
     *
     * @param string $stripe_session_id ID de sesión de Stripe
     * @param string $mensaje_error Mensaje de error
     * @return bool True si se actualizó correctamente
     */
    public function procesarWebhookFallido($stripe_session_id, $mensaje_error) {
        $pago = $this->obtenerPagoPorSession($stripe_session_id);

        if (!$pago) {
            return false;
        }

        return $this->actualizarEstado($pago['id_pago'], 'fallido', [
            'mensaje_error' => $mensaje_error
        ]);
    }
}
