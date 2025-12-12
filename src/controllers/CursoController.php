<?php
/**
 * Clase CursoController
 * Controlador para gestión de cursos e inscripciones
 */

class CursoController {
    private $curso_model;
    private $auth_middleware;

    public function __construct() {
        $this->curso_model = new Curso();
        $this->auth_middleware = new AuthMiddleware();
    }

    /**
     * Obtiene todos los cursos disponibles
     *
     * @return array Lista de cursos
     */
    public function obtenerTodos() {
        return $this->curso_model->obtenerTodos();
    }

    /**
     * Obtiene un curso específico
     *
     * @param int $id_curso ID del curso
     * @return array|false Datos del curso
     */
    public function obtenerCurso($id_curso) {
        return $this->curso_model->obtenerPorId($id_curso);
    }

    /**
     * Obtiene los cursos del usuario actual
     *
     * @return array Lista de cursos inscritos
     */
    public function obtenerMisCursos() {
        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        if (!$usuario) {
            return [];
        }

        return $this->curso_model->obtenerCursosUsuario($usuario['id_usuario']);
    }

    /**
     * Inscribe al usuario actual en un curso
     *
     * @param int $id_curso ID del curso
     * @return array Resultado de la inscripción
     */
    public function inscribirse($id_curso) {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return [
                'success' => false,
                'message' => 'Debes iniciar sesión para inscribirte'
            ];
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        // Verificar que el curso exista
        $curso = $this->curso_model->obtenerPorId($id_curso);

        if (!$curso) {
            return [
                'success' => false,
                'message' => 'El curso no existe'
            ];
        }

        // Verificar si ya está inscrito
        if ($this->curso_model->estaInscrito($usuario['id_usuario'], $id_curso)) {
            return [
                'success' => false,
                'message' => 'Ya estás inscrito en este curso'
            ];
        }

        // Inscribir al usuario
        $inscrito = $this->curso_model->inscribir($usuario['id_usuario'], $id_curso);

        if (!$inscrito) {
            return [
                'success' => false,
                'message' => 'Error al inscribirse en el curso. Por favor intenta de nuevo.'
            ];
        }

        return [
            'success' => true,
            'message' => 'Te has inscrito correctamente en el curso'
        ];
    }

    /**
     * Actualiza el progreso del usuario en un curso
     *
     * @param int $id_curso ID del curso
     * @param int $progreso Progreso en porcentaje (0-100)
     * @return array Resultado de la actualización
     */
    public function actualizarProgreso($id_curso, $progreso) {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return [
                'success' => false,
                'message' => 'Debes iniciar sesión'
            ];
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        // Verificar que esté inscrito
        if (!$this->curso_model->estaInscrito($usuario['id_usuario'], $id_curso)) {
            return [
                'success' => false,
                'message' => 'No estás inscrito en este curso'
            ];
        }

        // Actualizar progreso
        $actualizado = $this->curso_model->actualizarProgreso(
            $usuario['id_usuario'],
            $id_curso,
            $progreso
        );

        if (!$actualizado) {
            return [
                'success' => false,
                'message' => 'Error al actualizar el progreso'
            ];
        }

        return [
            'success' => true,
            'message' => 'Progreso actualizado correctamente',
            'progreso' => $progreso
        ];
    }

    /**
     * Obtiene estadísticas del usuario actual
     *
     * @return array Estadísticas del usuario
     */
    public function obtenerEstadisticas() {
        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        if (!$usuario) {
            return [
                'total_cursos' => 0,
                'cursos_completados' => 0,
                'progreso_promedio' => 0
            ];
        }

        $stats = $this->curso_model->obtenerEstadisticasUsuario($usuario['id_usuario']);

        return [
            'total_cursos' => (int)($stats['total_cursos'] ?? 0),
            'cursos_completados' => (int)($stats['cursos_completados'] ?? 0),
            'progreso_promedio' => round($stats['progreso_promedio'] ?? 0, 1)
        ];
    }

    /**
     * Busca cursos por término
     *
     * @param string $termino Término de búsqueda
     * @return array Lista de cursos encontrados
     */
    public function buscar($termino) {
        if (empty($termino)) {
            return $this->obtenerTodos();
        }

        return $this->curso_model->buscar($termino);
    }

    /**
     * Obtiene los cursos más populares
     *
     * @param int $limit Número de cursos
     * @return array Lista de cursos populares
     */
    public function obtenerPopulares($limit = 5) {
        return $this->curso_model->obtenerPopulares($limit);
    }
}
