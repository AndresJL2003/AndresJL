<?php
/**
 * Clase CarritoController
 * Controlador para gestión del carrito de compras
 */

class CarritoController {
    private $carrito_model;
    private $curso_model;
    private $auth_middleware;

    public function __construct() {
        $this->carrito_model = new Carrito();
        $this->curso_model = new Curso();
        $this->auth_middleware = new AuthMiddleware();
    }

    /**
     * Agrega un curso al carrito del usuario actual
     *
     * @param int $id_curso ID del curso a agregar
     * @return array Respuesta JSON con success, message y data
     */
    public function agregarAlCarrito($id_curso) {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return [
                'success' => false,
                'message' => 'Debes iniciar sesión para agregar cursos al carrito'
            ];
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        // Validar que el curso existe
        $curso = $this->curso_model->obtenerPorId($id_curso);

        if (!$curso) {
            return [
                'success' => false,
                'message' => 'El curso no existe'
            ];
        }

        // Verificar que el curso no es gratuito
        if ($curso['precio'] <= 0) {
            return [
                'success' => false,
                'message' => 'Este curso es gratuito. Puedes inscribirte directamente.'
            ];
        }

        // Verificar que no está ya inscrito
        if ($this->curso_model->estaInscrito($usuario['id_usuario'], $id_curso)) {
            return [
                'success' => false,
                'message' => 'Ya estás inscrito en este curso'
            ];
        }

        // Verificar que no está ya en el carrito
        if ($this->carrito_model->verificarCursoEnCarrito($usuario['id_usuario'], $id_curso)) {
            return [
                'success' => false,
                'message' => 'Este curso ya está en tu carrito'
            ];
        }

        // Agregar al carrito
        $resultado = $this->carrito_model->agregarCurso($usuario['id_usuario'], $id_curso);

        if (!$resultado) {
            return [
                'success' => false,
                'message' => 'Error al agregar el curso al carrito. Por favor intenta de nuevo.'
            ];
        }

        // Obtener contador actualizado
        $contador = $this->carrito_model->contarItems($usuario['id_usuario']);

        return [
            'success' => true,
            'message' => 'Curso agregado al carrito exitosamente',
            'data' => [
                'items_count' => $contador,
                'curso_titulo' => $curso['titulo']
            ]
        ];
    }

    /**
     * Elimina un curso del carrito
     *
     * @param int $id_curso ID del curso a eliminar
     * @return array Respuesta JSON
     */
    public function eliminarDelCarrito($id_curso) {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return [
                'success' => false,
                'message' => 'No autenticado'
            ];
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        // Eliminar del carrito
        $resultado = $this->carrito_model->eliminarItem($usuario['id_usuario'], $id_curso);

        if (!$resultado) {
            return [
                'success' => false,
                'message' => 'Error al eliminar el curso del carrito'
            ];
        }

        // Obtener contador actualizado
        $contador = $this->carrito_model->contarItems($usuario['id_usuario']);
        $total = $this->carrito_model->obtenerTotal($usuario['id_usuario']);

        return [
            'success' => true,
            'message' => 'Curso eliminado del carrito',
            'data' => [
                'items_count' => $contador,
                'total' => $total
            ]
        ];
    }

    /**
     * Obtiene el carrito del usuario actual con detalles
     *
     * @return array Respuesta JSON con items del carrito
     */
    public function obtenerCarrito() {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return [
                'success' => false,
                'message' => 'No autenticado',
                'data' => []
            ];
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        // Obtener items del carrito
        $items = $this->carrito_model->obtenerItemsDetallados($usuario['id_usuario']);
        $total = $this->carrito_model->obtenerTotal($usuario['id_usuario']);

        return [
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => $total,
                'items_count' => count($items)
            ]
        ];
    }

    /**
     * Vacía el carrito del usuario actual
     *
     * @return array Respuesta JSON
     */
    public function vaciarCarrito() {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return [
                'success' => false,
                'message' => 'No autenticado'
            ];
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        // Vaciar carrito
        $resultado = $this->carrito_model->vaciarCarrito($usuario['id_usuario']);

        if (!$resultado) {
            return [
                'success' => false,
                'message' => 'Error al vaciar el carrito'
            ];
        }

        return [
            'success' => true,
            'message' => 'Carrito vaciado exitosamente',
            'data' => [
                'items_count' => 0,
                'total' => 0
            ]
        ];
    }

    /**
     * Cuenta los items en el carrito del usuario actual
     *
     * @return int Número de items
     */
    public function contarItems() {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return 0;
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        return $this->carrito_model->contarItems($usuario['id_usuario']);
    }

    /**
     * Obtiene el total del carrito del usuario actual
     *
     * @return float Total del carrito
     */
    public function obtenerTotal() {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return 0;
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        return $this->carrito_model->obtenerTotal($usuario['id_usuario']);
    }

    /**
     * Verifica si un curso está en el carrito
     *
     * @param int $id_curso ID del curso
     * @return bool True si está en el carrito
     */
    public function cursoEnCarrito($id_curso) {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return false;
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        return $this->carrito_model->verificarCursoEnCarrito($usuario['id_usuario'], $id_curso);
    }

    /**
     * Verifica la disponibilidad de un curso para compra
     *
     * @param int $id_curso ID del curso
     * @return array Respuesta con disponibilidad y mensaje
     */
    public function verificarDisponibilidad($id_curso) {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return [
                'disponible' => false,
                'mensaje' => 'Debes iniciar sesión'
            ];
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        // Verificar que el curso existe
        $curso = $this->curso_model->obtenerPorId($id_curso);

        if (!$curso) {
            return [
                'disponible' => false,
                'mensaje' => 'El curso no existe'
            ];
        }

        // Verificar que el curso está activo
        if (!$curso['activo']) {
            return [
                'disponible' => false,
                'mensaje' => 'El curso no está disponible actualmente'
            ];
        }

        // Verificar que no es gratuito
        if ($curso['precio'] <= 0) {
            return [
                'disponible' => false,
                'mensaje' => 'Este es un curso gratuito'
            ];
        }

        // Verificar que no está inscrito
        if ($this->curso_model->estaInscrito($usuario['id_usuario'], $id_curso)) {
            return [
                'disponible' => false,
                'mensaje' => 'Ya estás inscrito en este curso'
            ];
        }

        return [
            'disponible' => true,
            'mensaje' => 'El curso está disponible para compra'
        ];
    }
}
