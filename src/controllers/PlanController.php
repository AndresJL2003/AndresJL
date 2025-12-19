<?php
/**
 * Clase PlanController
 * Controlador para gestión de planes de suscripción
 */

class PlanController {
    private $plan_model;
    private $usuario_model;
    private $auth_middleware;

    public function __construct() {
        $this->plan_model = new Plan();
        $this->usuario_model = new Usuario();
        $this->auth_middleware = new AuthMiddleware();
    }

    /**
     * Obtiene todos los planes disponibles
     *
     * @return array Lista de planes
     */
    public function obtenerTodos() {
        return $this->plan_model->obtenerTodos();
    }

    /**
     * Obtiene un plan específico
     *
     * @param int $id_plan ID del plan
     * @return array|false Datos del plan
     */
    public function obtenerPlan($id_plan) {
        return $this->plan_model->obtenerPorId($id_plan);
    }

    /**
     * Obtiene el plan del usuario actual
     *
     * @return array|false Datos del plan del usuario
     */
    public function obtenerPlanActual() {
        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        if (!$usuario) {
            return false;
        }

        return $this->plan_model->obtenerPorId($usuario['id_plan']);
    }

    /**
     * Cambia el plan del usuario actual
     *
     * @param int $id_plan ID del nuevo plan
     * @return array Resultado del cambio
     */
    public function cambiarPlan($id_plan) {
        // Verificar autenticación
        if (!$this->auth_middleware->verificarAutenticacion(false)) {
            return [
                'success' => false,
                'message' => 'Debes iniciar sesión'
            ];
        }

        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        // Verificar que el plan exista
        $plan = $this->plan_model->obtenerPorId($id_plan);

        if (!$plan) {
            return [
                'success' => false,
                'message' => 'El plan seleccionado no existe'
            ];
        }

        // Verificar que no sea el mismo plan
        if ($usuario['id_plan'] == $id_plan) {
            return [
                'success' => false,
                'message' => 'Ya tienes este plan activo'
            ];
        }

        // Cambiar plan
        $cambiado = $this->usuario_model->actualizarPlan($usuario['id_usuario'], $id_plan);

        if (!$cambiado) {
            return [
                'success' => false,
                'message' => 'Error al cambiar el plan. Por favor intenta de nuevo.'
            ];
        }

        // Si el nuevo plan tiene menos sesiones, cerrar sesiones extras
        if ($plan['sesiones_maximas'] < $usuario['sesiones_maximas']) {
            $this->ajustarSesiones($usuario['id_usuario'], $plan['sesiones_maximas']);
        }

        // Actualizar sesión
        $_SESSION['user_plan'] = $plan['nombre_plan'];
        $_SESSION['sesiones_maximas'] = $plan['sesiones_maximas'];

        return [
            'success' => true,
            'message' => 'Plan actualizado correctamente',
            'plan' => $plan['nombre_plan']
        ];
    }

    /**
     * Ajusta las sesiones activas según el límite del nuevo plan
     *
     * @param int $id_usuario ID del usuario
     * @param int $sesiones_maximas Límite de sesiones del nuevo plan
     */
    private function ajustarSesiones($id_usuario, $sesiones_maximas) {
        $sesion_model = new Sesion();
        $sesiones_activas = $sesion_model->contarSesionesActivas($id_usuario);

        // Si tiene más sesiones activas que el límite, cerrar las más antiguas
        while ($sesiones_activas > $sesiones_maximas) {
            $sesion_model->cerrarSesionMasAntigua($id_usuario);
            $sesiones_activas--;
        }
    }

    /**
     * Obtiene información de suscripción del usuario actual
     *
     * @return array Información de suscripción
     */
    public function obtenerInfoSuscripcion() {
        $usuario = $this->auth_middleware->obtenerUsuarioActual();

        if (!$usuario) {
            return [
                'plan' => 'Ninguno',
                'sesiones_maximas' => 0,
                'precio' => 0,
                'fecha_registro' => null
            ];
        }

        return [
            'plan' => $usuario['nombre_plan'],
            'sesiones_maximas' => $usuario['sesiones_maximas'],
            'precio' => $usuario['precio'],
            'fecha_registro' => $usuario['fecha_registro']
        ];
    }
}
