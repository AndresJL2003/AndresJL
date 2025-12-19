<?php
/**
 * Clase Carrito
 * Modelo para gestión del carrito de compras
 */

class Carrito {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Agrega un curso al carrito del usuario
     *
     * @param int $id_usuario ID del usuario
     * @param int $id_curso ID del curso
     * @return bool True si se agregó correctamente
     */
    public function agregarCurso($id_usuario, $id_curso) {
        // Verificar que el curso existe y está activo
        $curso_model = new Curso();
        $curso = $curso_model->obtenerPorId($id_curso);

        if (!$curso) {
            return false;
        }

        // Verificar que el curso tiene precio > 0
        if ($curso['precio'] <= 0) {
            return false; // Cursos gratuitos no van al carrito
        }

        // Verificar que el usuario no está ya inscrito
        if ($curso_model->estaInscrito($id_usuario, $id_curso)) {
            return false;
        }

        // Verificar que no está ya en el carrito
        if ($this->verificarCursoEnCarrito($id_usuario, $id_curso)) {
            return false;
        }

        // Agregar al carrito
        $sql = "INSERT INTO carrito (id_usuario, id_curso, precio_agregado)
                VALUES (:id_usuario, :id_curso, :precio_agregado)";

        return $this->db->execute($sql, [
            ':id_usuario' => $id_usuario,
            ':id_curso' => $id_curso,
            ':precio_agregado' => $curso['precio']
        ]);
    }

    /**
     * Obtiene los items del carrito del usuario con detalles de los cursos
     *
     * @param int $id_usuario ID del usuario
     * @return array Lista de items con información completa del curso
     */
    public function obtenerItemsDetallados($id_usuario) {
        $sql = "SELECT
                    c.id_carrito,
                    c.id_usuario,
                    c.id_curso,
                    c.precio_agregado,
                    c.fecha_agregado,
                    cur.titulo,
                    cur.descripcion,
                    cur.instructor,
                    cur.duracion_horas,
                    cur.nivel,
                    cur.imagen_url,
                    cur.precio as precio_actual
                FROM carrito c
                INNER JOIN cursos cur ON c.id_curso = cur.id_curso
                WHERE c.id_usuario = :id_usuario
                AND cur.activo = 1
                ORDER BY c.fecha_agregado DESC";

        return $this->db->query($sql, [':id_usuario' => $id_usuario]);
    }

    /**
     * Obtiene items del carrito sin detalles adicionales
     *
     * @param int $id_usuario ID del usuario
     * @return array Lista de items básicos
     */
    public function obtenerCarritoUsuario($id_usuario) {
        $sql = "SELECT * FROM carrito WHERE id_usuario = :id_usuario ORDER BY fecha_agregado DESC";
        return $this->db->query($sql, [':id_usuario' => $id_usuario]);
    }

    /**
     * Elimina un item del carrito
     *
     * @param int $id_usuario ID del usuario
     * @param int $id_curso ID del curso a eliminar
     * @return bool True si se eliminó correctamente
     */
    public function eliminarItem($id_usuario, $id_curso) {
        $sql = "DELETE FROM carrito WHERE id_usuario = :id_usuario AND id_curso = :id_curso";

        return $this->db->execute($sql, [
            ':id_usuario' => $id_usuario,
            ':id_curso' => $id_curso
        ]);
    }

    /**
     * Vacía completamente el carrito del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return bool True si se vació correctamente
     */
    public function vaciarCarrito($id_usuario) {
        $sql = "DELETE FROM carrito WHERE id_usuario = :id_usuario";
        return $this->db->execute($sql, [':id_usuario' => $id_usuario]);
    }

    /**
     * Obtiene el total del carrito del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return float Total del carrito
     */
    public function obtenerTotal($id_usuario) {
        $sql = "SELECT SUM(precio_agregado) as total FROM carrito WHERE id_usuario = :id_usuario";

        $result = $this->db->queryOne($sql, [':id_usuario' => $id_usuario]);

        return $result ? (float)($result['total'] ?? 0) : 0;
    }

    /**
     * Cuenta el número de items en el carrito
     *
     * @param int $id_usuario ID del usuario
     * @return int Número de items
     */
    public function contarItems($id_usuario) {
        $sql = "SELECT COUNT(*) as total FROM carrito WHERE id_usuario = :id_usuario";

        $result = $this->db->queryOne($sql, [':id_usuario' => $id_usuario]);

        return $result ? (int)($result['total'] ?? 0) : 0;
    }

    /**
     * Verifica si un curso está en el carrito del usuario
     *
     * @param int $id_usuario ID del usuario
     * @param int $id_curso ID del curso
     * @return bool True si el curso está en el carrito
     */
    public function verificarCursoEnCarrito($id_usuario, $id_curso) {
        $sql = "SELECT COUNT(*) as total FROM carrito
                WHERE id_usuario = :id_usuario AND id_curso = :id_curso";

        $result = $this->db->queryOne($sql, [
            ':id_usuario' => $id_usuario,
            ':id_curso' => $id_curso
        ]);

        return $result && $result['total'] > 0;
    }

    /**
     * Obtiene un item específico del carrito
     *
     * @param int $id_usuario ID del usuario
     * @param int $id_curso ID del curso
     * @return array|false Datos del item o false
     */
    public function obtenerItem($id_usuario, $id_curso) {
        $sql = "SELECT * FROM carrito WHERE id_usuario = :id_usuario AND id_curso = :id_curso";

        return $this->db->queryOne($sql, [
            ':id_usuario' => $id_usuario,
            ':id_curso' => $id_curso
        ]);
    }

    /**
     * Obtiene estadísticas del carrito
     *
     * @param int $id_usuario ID del usuario
     * @return array Estadísticas (total_items, total_precio, precio_promedio)
     */
    public function obtenerEstadisticas($id_usuario) {
        $sql = "SELECT
                    COUNT(*) as total_items,
                    SUM(precio_agregado) as total_precio,
                    AVG(precio_agregado) as precio_promedio
                FROM carrito
                WHERE id_usuario = :id_usuario";

        $result = $this->db->queryOne($sql, [':id_usuario' => $id_usuario]);

        return [
            'total_items' => (int)($result['total_items'] ?? 0),
            'total_precio' => (float)($result['total_precio'] ?? 0),
            'precio_promedio' => (float)($result['precio_promedio'] ?? 0)
        ];
    }
}
