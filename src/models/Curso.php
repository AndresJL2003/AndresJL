<?php
/**
 * Clase Curso
 * Modelo para gestión de cursos e inscripciones
 */

class Curso {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtiene todos los cursos activos
     *
     * @return array Lista de cursos
     */
    public function obtenerTodos() {
        $sql = "SELECT * FROM cursos WHERE activo = 1 ORDER BY fecha_creacion DESC";
        return $this->db->query($sql);
    }

    /**
     * Obtiene un curso por su ID
     *
     * @param int $id_curso ID del curso
     * @return array|false Datos del curso o false
     */
    public function obtenerPorId($id_curso) {
        $sql = "SELECT * FROM cursos WHERE id_curso = :id_curso AND activo = 1";
        return $this->db->queryOne($sql, [':id_curso' => $id_curso]);
    }

    /**
     * Obtiene cursos por nivel
     *
     * @param string $nivel Nivel del curso (Principiante, Intermedio, Avanzado)
     * @return array Lista de cursos
     */
    public function obtenerPorNivel($nivel) {
        $sql = "SELECT * FROM cursos WHERE nivel = :nivel AND activo = 1 ORDER BY titulo";
        return $this->db->query($sql, [':nivel' => $nivel]);
    }

    /**
     * Inscribe un usuario a un curso
     *
     * @param int $id_usuario ID del usuario
     * @param int $id_curso ID del curso
     * @return bool True si se inscribió correctamente
     */
    public function inscribir($id_usuario, $id_curso) {
        // Verificar si ya está inscrito
        if ($this->estaInscrito($id_usuario, $id_curso)) {
            return false;
        }

        $sql = "INSERT INTO inscripciones (id_usuario, id_curso) VALUES (:id_usuario, :id_curso)";
        return $this->db->execute($sql, [
            ':id_usuario' => $id_usuario,
            ':id_curso' => $id_curso
        ]);
    }

    /**
     * Verifica si un usuario está inscrito en un curso
     *
     * @param int $id_usuario ID del usuario
     * @param int $id_curso ID del curso
     * @return bool True si está inscrito
     */
    public function estaInscrito($id_usuario, $id_curso) {
        $sql = "SELECT COUNT(*) as total FROM inscripciones
                WHERE id_usuario = :id_usuario AND id_curso = :id_curso";

        $result = $this->db->queryOne($sql, [
            ':id_usuario' => $id_usuario,
            ':id_curso' => $id_curso
        ]);

        return $result && $result['total'] > 0;
    }

    /**
     * Obtiene los cursos en los que está inscrito un usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array Lista de cursos con información de inscripción
     */
    public function obtenerCursosUsuario($id_usuario) {
        $sql = "SELECT c.*, i.fecha_inscripcion, i.progreso, i.completado, i.fecha_completado
                FROM cursos c
                INNER JOIN inscripciones i ON c.id_curso = i.id_curso
                WHERE i.id_usuario = :id_usuario AND c.activo = 1
                ORDER BY i.fecha_inscripcion DESC";

        return $this->db->query($sql, [':id_usuario' => $id_usuario]);
    }

    /**
     * Actualiza el progreso de un usuario en un curso
     *
     * @param int $id_usuario ID del usuario
     * @param int $id_curso ID del curso
     * @param int $progreso Progreso en porcentaje (0-100)
     * @return bool True si se actualizó correctamente
     */
    public function actualizarProgreso($id_usuario, $id_curso, $progreso) {
        $completado = ($progreso >= 100) ? 1 : 0;
        $fecha_completado = $completado ? 'NOW()' : 'NULL';

        $sql = "UPDATE inscripciones
                SET progreso = :progreso,
                    completado = :completado,
                    fecha_completado = CASE WHEN :completado = 1 THEN NOW() ELSE NULL END
                WHERE id_usuario = :id_usuario AND id_curso = :id_curso";

        return $this->db->execute($sql, [
            ':progreso' => min(100, max(0, $progreso)),
            ':completado' => $completado,
            ':id_usuario' => $id_usuario,
            ':id_curso' => $id_curso
        ]);
    }

    /**
     * Obtiene estadísticas de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array Estadísticas del usuario
     */
    public function obtenerEstadisticasUsuario($id_usuario) {
        $sql = "SELECT
                    COUNT(*) as total_cursos,
                    SUM(CASE WHEN completado = 1 THEN 1 ELSE 0 END) as cursos_completados,
                    AVG(progreso) as progreso_promedio
                FROM inscripciones
                WHERE id_usuario = :id_usuario";

        return $this->db->queryOne($sql, [':id_usuario' => $id_usuario]);
    }

    /**
     * Busca cursos por título o descripción
     *
     * @param string $termino Término de búsqueda
     * @return array Lista de cursos encontrados
     */
    public function buscar($termino) {
        $sql = "SELECT * FROM cursos
                WHERE activo = 1
                AND (titulo LIKE :termino OR descripcion LIKE :termino)
                ORDER BY titulo";

        return $this->db->query($sql, [':termino' => '%' . $termino . '%']);
    }

    /**
     * Obtiene los cursos más populares
     *
     * @param int $limit Número de cursos a devolver
     * @return array Lista de cursos populares
     */
    public function obtenerPopulares($limit = 5) {
        $sql = "SELECT c.*, COUNT(i.id_inscripcion) as total_inscritos
                FROM cursos c
                LEFT JOIN inscripciones i ON c.id_curso = i.id_curso
                WHERE c.activo = 1
                GROUP BY c.id_curso, c.titulo, c.descripcion, c.instructor, c.duracion_horas,
                         c.nivel, c.imagen_url, c.precio, c.activo, c.fecha_creacion
                ORDER BY total_inscritos DESC
                LIMIT :limit";

        return $this->db->query($sql, [':limit' => (int)$limit]);
    }

    /**
     * Crea un nuevo curso
     *
     * @param array $data Datos del curso
     * @return bool True si se creó correctamente
     */
    public function crear($data) {
        $sql = "INSERT INTO cursos (titulo, descripcion, instructor, duracion_horas, nivel, imagen_url, precio)
                VALUES (:titulo, :descripcion, :instructor, :duracion_horas, :nivel, :imagen_url, :precio)";

        $params = [
            ':titulo' => $data['titulo'],
            ':descripcion' => $data['descripcion'] ?? '',
            ':instructor' => $data['instructor'] ?? '',
            ':duracion_horas' => $data['duracion_horas'] ?? 0,
            ':nivel' => $data['nivel'] ?? 'Principiante',
            ':imagen_url' => $data['imagen_url'] ?? '',
            ':precio' => $data['precio'] ?? 0.00
        ];

        return $this->db->execute($sql, $params);
    }

    /**
     * Actualiza un curso existente
     *
     * @param int $id_curso ID del curso
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function actualizar($id_curso, $data) {
        $sql = "UPDATE cursos
                SET titulo = :titulo,
                    descripcion = :descripcion,
                    instructor = :instructor,
                    duracion_horas = :duracion_horas,
                    nivel = :nivel,
                    imagen_url = :imagen_url,
                    precio = :precio
                WHERE id_curso = :id_curso";

        $params = [
            ':id_curso' => $id_curso,
            ':titulo' => $data['titulo'],
            ':descripcion' => $data['descripcion'] ?? '',
            ':instructor' => $data['instructor'] ?? '',
            ':duracion_horas' => $data['duracion_horas'] ?? 0,
            ':nivel' => $data['nivel'] ?? 'Principiante',
            ':imagen_url' => $data['imagen_url'] ?? '',
            ':precio' => $data['precio'] ?? 0.00
        ];

        return $this->db->execute($sql, $params);
    }

    /**
     * Desactiva un curso
     *
     * @param int $id_curso ID del curso
     * @return bool True si se desactivó correctamente
     */
    public function desactivar($id_curso) {
        $sql = "UPDATE cursos SET activo = 0 WHERE id_curso = :id_curso";
        return $this->db->execute($sql, [':id_curso' => $id_curso]);
    }
}
