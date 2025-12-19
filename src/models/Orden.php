<?php
/**
 * Clase Orden
 * Modelo para gestión de órdenes de compra
 */

class Orden {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crea una nueva orden desde el carrito del usuario
     *
     * @param int $id_usuario ID del usuario
     * @param array $datos_facturacion Datos de facturación del usuario
     * @param array $items_carrito Items del carrito
     * @return int|false ID de la orden creada o false si falla
     */
    public function crearOrden($id_usuario, $datos_facturacion, $items_carrito) {
        if (empty($items_carrito)) {
            return false;
        }

        // Calcular totales
        $totales = $this->calcularTotales($items_carrito);

        // Generar número de orden único
        $numero_orden = $this->generarNumeroOrden();

        // Iniciar transacción
        $this->db->beginTransaction();

        try {
            // 1. Insertar orden
            $sql_orden = "INSERT INTO ordenes (
                            numero_orden, id_usuario, nombre_facturacion, email_facturacion, telefono_facturacion,
                            direccion_facturacion, ciudad_facturacion, estado_facturacion, codigo_postal_facturacion, pais_facturacion,
                            subtotal, impuestos, descuento, total, notas_cliente
                          )
                          VALUES (
                            :numero_orden, :id_usuario, :nombre_facturacion, :email_facturacion, :telefono_facturacion,
                            :direccion_facturacion, :ciudad_facturacion, :estado_facturacion, :codigo_postal_facturacion, :pais_facturacion,
                            :subtotal, :impuestos, :descuento, :total, :notas_cliente
                          )";

            $id_orden = $this->db->insert($sql_orden, [
                ':numero_orden' => $numero_orden,
                ':id_usuario' => $id_usuario,
                ':nombre_facturacion' => $datos_facturacion['nombre_completo'],
                ':email_facturacion' => $datos_facturacion['email'],
                ':telefono_facturacion' => $datos_facturacion['telefono'] ?? '',
                ':direccion_facturacion' => $datos_facturacion['direccion'] ?? '',
                ':ciudad_facturacion' => $datos_facturacion['ciudad'] ?? '',
                ':estado_facturacion' => $datos_facturacion['estado'] ?? '',
                ':codigo_postal_facturacion' => $datos_facturacion['codigo_postal'] ?? '',
                ':pais_facturacion' => $datos_facturacion['pais'] ?? 'Bolivia',
                ':subtotal' => $totales['subtotal'],
                ':impuestos' => $totales['impuestos'],
                ':descuento' => $totales['descuento'] ?? 0,
                ':total' => $totales['total'],
                ':notas_cliente' => $datos_facturacion['notas'] ?? ''
            ]);

            if (!$id_orden) {
                throw new Exception('Error al crear la orden');
            }

            // 2. Insertar items de la orden
            $sql_item = "INSERT INTO items_orden (
                            id_orden, id_curso, titulo_curso, precio_unitario, cantidad, subtotal
                         )
                         VALUES (
                            :id_orden, :id_curso, :titulo_curso, :precio_unitario, :cantidad, :subtotal
                         )";

            foreach ($items_carrito as $item) {
                $cantidad = 1; // Siempre 1 para cursos
                $subtotal = $item['precio_agregado'] * $cantidad;

                $resultado = $this->db->execute($sql_item, [
                    ':id_orden' => $id_orden,
                    ':id_curso' => $item['id_curso'],
                    ':titulo_curso' => $item['titulo'],
                    ':precio_unitario' => $item['precio_agregado'],
                    ':cantidad' => $cantidad,
                    ':subtotal' => $subtotal
                ]);

                if (!$resultado) {
                    throw new Exception('Error al insertar item de orden');
                }
            }

            // Confirmar transacción
            $this->db->commit();

            return $id_orden;

        } catch (Exception $e) {
            // Revertir transacción
            $this->db->rollback();
            error_log("Error al crear orden: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera un número de orden único
     * Formato: ORD-YYYYMMDD-#### (ej: ORD-20250312-0001)
     *
     * @return string Número de orden
     */
    public function generarNumeroOrden() {
        $fecha = date('Ymd');
        $prefijo = 'ORD-' . $fecha . '-';

        // Buscar el último número de orden del día
        $sql = "SELECT numero_orden FROM ordenes
                WHERE numero_orden LIKE :prefijo
                ORDER BY id_orden DESC LIMIT 1";

        $result = $this->db->queryOne($sql, [':prefijo' => $prefijo . '%']);

        if ($result) {
            // Extraer el número y sumar 1
            $ultimo_numero = (int)substr($result['numero_orden'], -4);
            $nuevo_numero = $ultimo_numero + 1;
        } else {
            $nuevo_numero = 1;
        }

        return $prefijo . str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calcula totales de la orden
     *
     * @param array $items Items del carrito
     * @return array ['subtotal' => float, 'impuestos' => float, 'descuento' => float, 'total' => float]
     */
    public function calcularTotales($items) {
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += $item['precio_agregado'];
        }

        // Por ahora sin impuestos ni descuentos, se puede agregar después
        $impuestos = 0;
        $descuento = 0;
        $total = $subtotal + $impuestos - $descuento;

        return [
            'subtotal' => $subtotal,
            'impuestos' => $impuestos,
            'descuento' => $descuento,
            'total' => $total
        ];
    }

    /**
     * Obtiene una orden por su ID
     *
     * @param int $id_orden ID de la orden
     * @return array|false Datos de la orden o false
     */
    public function obtenerOrden($id_orden) {
        $sql = "SELECT * FROM ordenes WHERE id_orden = :id_orden";
        return $this->db->queryOne($sql, [':id_orden' => $id_orden]);
    }

    /**
     * Obtiene una orden por su número de orden
     *
     * @param string $numero_orden Número de orden
     * @return array|false Datos de la orden o false
     */
    public function obtenerOrdenPorNumero($numero_orden) {
        $sql = "SELECT * FROM ordenes WHERE numero_orden = :numero_orden";
        return $this->db->queryOne($sql, [':numero_orden' => $numero_orden]);
    }

    /**
     * Obtiene todas las órdenes de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @param int $limit Número máximo de órdenes a devolver
     * @return array Lista de órdenes
     */
    public function obtenerOrdenesUsuario($id_usuario, $limit = 10) {
        $sql = "SELECT * FROM ordenes
                WHERE id_usuario = :id_usuario
                ORDER BY fecha_creacion DESC
                LIMIT :limit";

        return $this->db->query($sql, [
            ':id_usuario' => $id_usuario,
            ':limit' => (int)$limit
        ]);
    }

    /**
     * Obtiene los items de una orden
     *
     * @param int $id_orden ID de la orden
     * @return array Lista de items
     */
    public function obtenerItemsOrden($id_orden) {
        $sql = "SELECT * FROM items_orden WHERE id_orden = :id_orden ORDER BY id_item";
        return $this->db->query($sql, [':id_orden' => $id_orden]);
    }

    /**
     * Actualiza el estado de una orden
     *
     * @param int $id_orden ID de la orden
     * @param string $estado_orden Estado de la orden (pendiente, pagada, fallida, etc.)
     * @param string $estado_pago Estado del pago (pendiente, completado, fallido, etc.)
     * @return bool True si se actualizó correctamente
     */
    public function actualizarEstado($id_orden, $estado_orden, $estado_pago = null) {
        if ($estado_pago) {
            $sql = "UPDATE ordenes
                    SET estado_orden = :estado_orden,
                        estado_pago = :estado_pago
                    WHERE id_orden = :id_orden";

            return $this->db->execute($sql, [
                ':id_orden' => $id_orden,
                ':estado_orden' => $estado_orden,
                ':estado_pago' => $estado_pago
            ]);
        } else {
            $sql = "UPDATE ordenes
                    SET estado_orden = :estado_orden
                    WHERE id_orden = :id_orden";

            return $this->db->execute($sql, [
                ':id_orden' => $id_orden,
                ':estado_orden' => $estado_orden
            ]);
        }
    }

    /**
     * Procesa las inscripciones de los cursos después de un pago exitoso
     *
     * @param int $id_orden ID de la orden
     * @return bool True si se procesaron correctamente
     */
    public function procesarInscripciones($id_orden) {
        // Obtener items de la orden
        $items = $this->obtenerItemsOrden($id_orden);

        if (empty($items)) {
            return false;
        }

        // Obtener orden para saber el usuario
        $orden = $this->obtenerOrden($id_orden);

        if (!$orden) {
            return false;
        }

        $curso_model = new Curso();
        $inscripciones_exitosas = 0;

        // Inscribir al usuario en cada curso
        foreach ($items as $item) {
            $resultado = $curso_model->inscribir($orden['id_usuario'], $item['id_curso']);

            if ($resultado) {
                $inscripciones_exitosas++;
            }
        }

        // Si se inscribió al menos a un curso, consideramos éxito
        return $inscripciones_exitosas > 0;
    }

    /**
     * Obtiene el total de una orden
     *
     * @param int $id_orden ID de la orden
     * @return float Total de la orden
     */
    public function obtenerTotalOrden($id_orden) {
        $orden = $this->obtenerOrden($id_orden);
        return $orden ? (float)$orden['total'] : 0;
    }

    /**
     * Verifica si una orden pertenece a un usuario
     *
     * @param int $id_orden ID de la orden
     * @param int $id_usuario ID del usuario
     * @return bool True si la orden pertenece al usuario
     */
    public function perteneceAUsuario($id_orden, $id_usuario) {
        $sql = "SELECT COUNT(*) as total FROM ordenes
                WHERE id_orden = :id_orden AND id_usuario = :id_usuario";

        $result = $this->db->queryOne($sql, [
            ':id_orden' => $id_orden,
            ':id_usuario' => $id_usuario
        ]);

        return $result && $result['total'] > 0;
    }

    /**
     * Obtiene estadísticas de órdenes del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array Estadísticas (total_ordenes, total_gastado, promedio_orden)
     */
    public function obtenerEstadisticasUsuario($id_usuario) {
        $sql = "SELECT
                    COUNT(*) as total_ordenes,
                    SUM(total) as total_gastado,
                    AVG(total) as promedio_orden
                FROM ordenes
                WHERE id_usuario = :id_usuario
                AND estado_orden = 'pagada'";

        $result = $this->db->queryOne($sql, [':id_usuario' => $id_usuario]);

        return [
            'total_ordenes' => (int)($result['total_ordenes'] ?? 0),
            'total_gastado' => (float)($result['total_gastado'] ?? 0),
            'promedio_orden' => (float)($result['promedio_orden'] ?? 0)
        ];
    }
}