<?php
/**
 * Clase Plan
 * Modelo para gestión de planes de suscripción
 */

class Plan {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtiene todos los planes activos
     *
     * @return array Lista de planes
     */
    public function obtenerTodos() {
        $sql = "SELECT * FROM planes WHERE activo = 1 ORDER BY precio ASC";
        return $this->db->query($sql);
    }

    /**
     * Obtiene un plan por su ID
     *
     * @param int $id_plan ID del plan
     * @return array|false Datos del plan o false
     */
    public function obtenerPorId($id_plan) {
        $sql = "SELECT * FROM planes WHERE id_plan = :id_plan";
        return $this->db->queryOne($sql, [':id_plan' => $id_plan]);
    }

    /**
     * Obtiene un plan por su nombre
     *
     * @param string $nombre_plan Nombre del plan
     * @return array|false Datos del plan o false
     */
    public function obtenerPorNombre($nombre_plan) {
        $sql = "SELECT * FROM planes WHERE nombre_plan = :nombre_plan";
        return $this->db->queryOne($sql, [':nombre_plan' => $nombre_plan]);
    }

    /**
     * Crea un nuevo plan
     *
     * @param array $data Datos del plan
     * @return bool True si se creó correctamente
     */
    public function crear($data) {
        $sql = "INSERT INTO planes (nombre_plan, sesiones_maximas, precio, descripcion)
                VALUES (:nombre_plan, :sesiones_maximas, :precio, :descripcion)";

        $params = [
            ':nombre_plan' => $data['nombre_plan'],
            ':sesiones_maximas' => $data['sesiones_maximas'],
            ':precio' => $data['precio'],
            ':descripcion' => $data['descripcion'] ?? ''
        ];

        return $this->db->execute($sql, $params);
    }

    /**
     * Actualiza un plan existente
     *
     * @param int $id_plan ID del plan
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function actualizar($id_plan, $data) {
        $sql = "UPDATE planes
                SET nombre_plan = :nombre_plan,
                    sesiones_maximas = :sesiones_maximas,
                    precio = :precio,
                    descripcion = :descripcion
                WHERE id_plan = :id_plan";

        $params = [
            ':id_plan' => $id_plan,
            ':nombre_plan' => $data['nombre_plan'],
            ':sesiones_maximas' => $data['sesiones_maximas'],
            ':precio' => $data['precio'],
            ':descripcion' => $data['descripcion'] ?? ''
        ];

        return $this->db->execute($sql, $params);
    }

    /**
     * Desactiva un plan
     *
     * @param int $id_plan ID del plan
     * @return bool True si se desactivó correctamente
     */
    public function desactivar($id_plan) {
        $sql = "UPDATE planes SET activo = 0 WHERE id_plan = :id_plan";
        return $this->db->execute($sql, [':id_plan' => $id_plan]);
    }

    /**
     * Obtiene el número de usuarios suscritos a un plan
     *
     * @param int $id_plan ID del plan
     * @return int Número de usuarios
     */
    public function contarUsuarios($id_plan) {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE id_plan = :id_plan AND activo = 1";
        $result = $this->db->queryOne($sql, [':id_plan' => $id_plan]);
        return $result ? (int)$result['total'] : 0;
    }
}
