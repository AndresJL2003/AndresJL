<?php
/**
 * Clase Sesion
 * Modelo para gestión de sesiones activas y control de límites por plan
 */

class Sesion {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crea una nueva sesión activa para un usuario
     *
     * @param int $id_usuario ID del usuario
     * @param int $sesiones_maximas Número máximo de sesiones permitidas según el plan
     * @return bool|string Session ID si se creó correctamente, false si se excedió el límite
     */
    public function crearSesion($id_usuario, $sesiones_maximas) {
        // Primero limpiar sesiones inactivas
        $this->limpiarSesionesInactivas();

        // Contar sesiones activas actuales
        $sesiones_activas = $this->contarSesionesActivas($id_usuario);

        // Si ya alcanzó el límite de sesiones
        if ($sesiones_activas >= $sesiones_maximas) {
            // Si el plan es básico (1 sesión), cerrar la sesión anterior
            if ($sesiones_maximas == 1) {
                $this->cerrarTodasLasSesiones($id_usuario);
            } else {
                // Para otros planes, cerrar la sesión más antigua
                $this->cerrarSesionMasAntigua($id_usuario);
            }
        }

        // Crear nueva sesión
        $session_id = session_id();
        $ip_address = $this->obtenerIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

        $sql = "INSERT INTO sesiones_activas (id_usuario, session_id, ip_address, user_agent)
                VALUES (:id_usuario, :session_id, :ip_address, :user_agent)";

        $params = [
            ':id_usuario' => $id_usuario,
            ':session_id' => $session_id,
            ':ip_address' => $ip_address,
            ':user_agent' => $user_agent
        ];

        if ($this->db->execute($sql, $params)) {
            return $session_id;
        }

        return false;
    }

    /**
     * Verifica si una sesión es válida
     *
     * @param int $id_usuario ID del usuario
     * @param string $session_id ID de la sesión
     * @return bool True si la sesión es válida
     */
    public function verificarSesion($id_usuario, $session_id) {
        // Limpiar sesiones inactivas primero
        $this->limpiarSesionesInactivas();

        $sql = "SELECT * FROM sesiones_activas
                WHERE id_usuario = :id_usuario
                AND session_id = :session_id
                AND activa = 1";

        $params = [
            ':id_usuario' => $id_usuario,
            ':session_id' => $session_id
        ];

        $sesion = $this->db->queryOne($sql, $params);

        if ($sesion) {
            // Actualizar última actividad
            $this->actualizarActividad($sesion['id_sesion']);
            return true;
        }

        return false;
    }

    /**
     * Actualiza la fecha de última actividad de una sesión
     *
     * @param int $id_sesion ID de la sesión
     * @return bool True si se actualizó correctamente
     */
    public function actualizarActividad($id_sesion) {
        $sql = "UPDATE sesiones_activas
                SET fecha_ultima_actividad = NOW()
                WHERE id_sesion = :id_sesion";

        return $this->db->execute($sql, [':id_sesion' => $id_sesion]);
    }

    /**
     * Cuenta las sesiones activas de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @return int Número de sesiones activas
     */
    public function contarSesionesActivas($id_usuario) {
        $sql = "SELECT COUNT(*) as total
                FROM sesiones_activas
                WHERE id_usuario = :id_usuario
                AND activa = 1";

        $result = $this->db->queryOne($sql, [':id_usuario' => $id_usuario]);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Cierra una sesión específica
     *
     * @param string $session_id ID de la sesión a cerrar
     * @return bool True si se cerró correctamente
     */
    public function cerrarSesion($session_id) {
        $sql = "UPDATE sesiones_activas
                SET activa = 0
                WHERE session_id = :session_id";

        return $this->db->execute($sql, [':session_id' => $session_id]);
    }

    /**
     * Cierra todas las sesiones de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @return bool True si se cerraron correctamente
     */
    public function cerrarTodasLasSesiones($id_usuario) {
        $sql = "UPDATE sesiones_activas
                SET activa = 0
                WHERE id_usuario = :id_usuario";

        return $this->db->execute($sql, [':id_usuario' => $id_usuario]);
    }

    /**
     * Cierra la sesión más antigua de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @return bool True si se cerró correctamente
     */
    public function cerrarSesionMasAntigua($id_usuario) {
        $sql = "UPDATE sesiones_activas
                SET activa = 0
                WHERE id_sesion = (
                    SELECT id_sesion
                    FROM sesiones_activas
                    WHERE id_usuario = :id_usuario
                    AND activa = 1
                    ORDER BY fecha_inicio ASC
                    LIMIT 1
                )";

        return $this->db->execute($sql, [':id_usuario' => $id_usuario]);
    }

    /**
     * Limpia sesiones inactivas (más de 30 minutos sin actividad)
     *
     * @return bool True si se limpiaron correctamente
     */
    public function limpiarSesionesInactivas() {
        $sql = "UPDATE sesiones_activas
                SET activa = 0
                WHERE activa = 1
                AND TIMESTAMPDIFF(MINUTE, fecha_ultima_actividad, NOW()) > :timeout";

        return $this->db->execute($sql, [':timeout' => SESSION_TIMEOUT]);
    }

    /**
     * Obtiene todas las sesiones activas de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array Lista de sesiones activas
     */
    public function obtenerSesionesActivas($id_usuario) {
        $this->limpiarSesionesInactivas();

        $sql = "SELECT *
                FROM sesiones_activas
                WHERE id_usuario = :id_usuario
                AND activa = 1
                ORDER BY fecha_ultima_actividad DESC";

        return $this->db->query($sql, [':id_usuario' => $id_usuario]);
    }

    /**
     * Verifica si se puede crear una nueva sesión según el límite del plan
     *
     * @param int $id_usuario ID del usuario
     * @param int $sesiones_maximas Límite de sesiones del plan
     * @return bool True si se puede crear una nueva sesión
     */
    public function puedeCrearSesion($id_usuario, $sesiones_maximas) {
        $sesiones_activas = $this->contarSesionesActivas($id_usuario);
        return $sesiones_activas < $sesiones_maximas;
    }

    /**
     * Obtiene información detallada de las sesiones activas
     *
     * @param int $id_usuario ID del usuario
     * @return array Lista de sesiones con detalles
     */
    public function obtenerSesionesDetalladas($id_usuario) {
        $this->limpiarSesionesInactivas();

        $sql = "SELECT
                    id_sesion,
                    session_id,
                    ip_address,
                    user_agent,
                    fecha_inicio,
                    fecha_ultima_actividad,
                    CASE
                        WHEN session_id = :current_session THEN 1
                        ELSE 0
                    END as es_sesion_actual
                FROM sesiones_activas
                WHERE id_usuario = :id_usuario
                AND activa = 1
                ORDER BY es_sesion_actual DESC, fecha_ultima_actividad DESC";

        return $this->db->query($sql, [
            ':id_usuario' => $id_usuario,
            ':current_session' => session_id()
        ]);
    }

    /**
     * Obtiene la dirección IP del cliente
     *
     * @return string Dirección IP
     */
    private function obtenerIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
        }
        return $ip;
    }

    /**
     * Elimina sesiones antiguas de la base de datos (más de 30 días)
     * Útil para limpieza de mantenimiento
     *
     * @return bool True si se eliminaron correctamente
     */
    public function eliminarSesionesAntiguas() {
        $sql = "DELETE FROM sesiones_activas
                WHERE TIMESTAMPDIFF(DAY, fecha_inicio, NOW()) > 30";

        return $this->db->execute($sql);
    }

    /**
     * Obtiene estadísticas de sesiones de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array Estadísticas de sesiones
     */
    public function obtenerEstadisticas($id_usuario) {
        $sql = "SELECT
                    COUNT(*) as total_sesiones,
                    SUM(CASE WHEN activa = 1 THEN 1 ELSE 0 END) as sesiones_activas,
                    MAX(fecha_ultima_actividad) as ultima_actividad
                FROM sesiones_activas
                WHERE id_usuario = :id_usuario";

        return $this->db->queryOne($sql, [':id_usuario' => $id_usuario]);
    }
}
