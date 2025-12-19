<?php
/**
 * Clase AuthMiddleware
 * Middleware para autenticación y control de sesiones
 * Valida que el usuario esté logueado y que su sesión sea válida según su plan
 */

class AuthMiddleware {
    private $usuario_model;
    private $sesion_model;

    public function __construct() {
        $this->usuario_model = new Usuario();
        $this->sesion_model = new Sesion();
    }

    /**
     * Verifica que el usuario esté autenticado
     * Si no lo está, redirige al login
     *
     * @param bool $redirect Si es true, redirige al login; si es false, retorna false
     * @return bool True si está autenticado, false si no
     */
    public function verificarAutenticacion($redirect = true) {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            if ($redirect) {
                $_SESSION['mensaje_error'] = 'Debes iniciar sesión para acceder';
                redirect(base_url('login.php'));
            }
            return false;
        }

        return true;
    }

    /**
     * Verifica que la sesión del usuario sea válida según su plan
     * Controla el límite de sesiones simultáneas
     *
     * @return bool True si la sesión es válida
     */
    public function verificarSesion() {
        if (!$this->verificarAutenticacion(false)) {
            return false;
        }

        $user_id = $_SESSION['user_id'];
        $session_id = session_id();

        // Verificar que la sesión sea válida
        if (!$this->sesion_model->verificarSesion($user_id, $session_id)) {
            // La sesión no es válida (fue cerrada por otra sesión o expiró)
            $this->cerrarSesionActual();
            $_SESSION['mensaje_error'] = 'Tu sesión ha expirado o fue cerrada desde otro dispositivo';
            redirect(base_url('login.php'));
            return false;
        }

        return true;
    }

    /**
     * Middleware completo: verifica autenticación y sesión válida
     * Este es el método principal que debe usarse en páginas protegidas
     */
    public function protegerRuta() {
        // Primero verificar autenticación
        if (!$this->verificarAutenticacion()) {
            return;
        }

        // Luego verificar sesión válida
        $this->verificarSesion();
    }

    /**
     * Verifica que el usuario haya activado su cuenta
     *
     * @return bool True si está activo
     */
    public function verificarCuentaActiva() {
        if (!$this->verificarAutenticacion(false)) {
            return false;
        }

        $user_id = $_SESSION['user_id'];
        $usuario = $this->usuario_model->obtenerPorId($user_id);

        if (!$usuario || !$usuario['activo']) {
            $_SESSION['mensaje_error'] = 'Tu cuenta no está activada. Por favor revisa tu correo electrónico.';
            $this->cerrarSesionActual();
            redirect(base_url('login.php'));
            return false;
        }

        return true;
    }

    /**
     * Obtiene los datos del usuario actual
     *
     * @return array|false Datos del usuario o false
     */
    public function obtenerUsuarioActual() {
        if (!$this->verificarAutenticacion(false)) {
            return false;
        }

        $user_id = $_SESSION['user_id'];
        return $this->usuario_model->obtenerPorId($user_id);
    }

    /**
     * Verifica que el usuario tenga un plan específico
     *
     * @param string $nombre_plan Nombre del plan requerido
     * @return bool True si tiene el plan
     */
    public function verificarPlan($nombre_plan) {
        $usuario = $this->obtenerUsuarioActual();

        if (!$usuario) {
            return false;
        }

        return strtolower($usuario['nombre_plan']) === strtolower($nombre_plan);
    }

    /**
     * Verifica que el usuario tenga al menos un plan específico
     * Orden de planes: Basico < Pro < Premium
     *
     * @param string $plan_minimo Plan mínimo requerido
     * @return bool True si tiene el plan o uno superior
     */
    public function verificarPlanMinimo($plan_minimo) {
        $usuario = $this->obtenerUsuarioActual();

        if (!$usuario) {
            return false;
        }

        $jerarquia_planes = [
            'basico' => 1,
            'pro' => 2,
            'premium' => 3
        ];

        $plan_usuario = strtolower($usuario['nombre_plan']);
        $plan_requerido = strtolower($plan_minimo);

        if (!isset($jerarquia_planes[$plan_usuario]) || !isset($jerarquia_planes[$plan_requerido])) {
            return false;
        }

        return $jerarquia_planes[$plan_usuario] >= $jerarquia_planes[$plan_requerido];
    }

    /**
     * Cierra la sesión actual del usuario
     */
    public function cerrarSesionActual() {
        if (isset($_SESSION['user_id'])) {
            // Cerrar sesión en la base de datos
            $session_id = session_id();
            $this->sesion_model->cerrarSesion($session_id);
        }

        // Destruir sesión PHP
        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        session_destroy();
    }

    /**
     * Inicia sesión para un usuario
     * Controla el límite de sesiones según el plan
     *
     * @param array $usuario Datos del usuario
     * @return bool True si se inició sesión correctamente
     */
    public function iniciarSesion($usuario) {
        // Verificar que la cuenta esté activa
        if (!$usuario['activo']) {
            return false;
        }

        // Regenerar ID de sesión para seguridad
        session_regenerate_id(true);

        // Guardar datos en sesión
        $_SESSION['user_id'] = $usuario['id_usuario'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['user_nombre'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
        $_SESSION['user_plan'] = $usuario['nombre_plan'];
        $_SESSION['sesiones_maximas'] = $usuario['sesiones_maximas'];
        $_SESSION['user_rol'] = $usuario['rol'] ?? 'usuario';

        // Crear sesión en la base de datos
        $this->sesion_model->crearSesion($usuario['id_usuario'], $usuario['sesiones_maximas']);

        // Actualizar última conexión
        $this->usuario_model->actualizarUltimaConexion($usuario['id_usuario']);

        return true;
    }

    /**
     * Obtiene información de las sesiones activas del usuario actual
     *
     * @return array Información de sesiones
     */
    public function obtenerInfoSesiones() {
        if (!$this->verificarAutenticacion(false)) {
            return [
                'sesiones_activas' => 0,
                'sesiones_maximas' => 0,
                'sesiones' => []
            ];
        }

        $user_id = $_SESSION['user_id'];
        $usuario = $this->usuario_model->obtenerPorId($user_id);

        return [
            'sesiones_activas' => $this->sesion_model->contarSesionesActivas($user_id),
            'sesiones_maximas' => $usuario['sesiones_maximas'],
            'sesiones' => $this->sesion_model->obtenerSesionesDetalladas($user_id)
        ];
    }

    /**
     * Cierra una sesión específica por su ID
     * Solo permite cerrar sesiones del usuario actual
     *
     * @param int $id_sesion ID de la sesión a cerrar
     * @return bool True si se cerró correctamente
     */
    public function cerrarSesionPorId($id_sesion) {
        if (!$this->verificarAutenticacion(false)) {
            return false;
        }

        $user_id = $_SESSION['user_id'];

        // Verificar que la sesión pertenezca al usuario actual
        $sesiones = $this->sesion_model->obtenerSesionesActivas($user_id);
        $sesion_encontrada = false;

        foreach ($sesiones as $sesion) {
            if ($sesion['id_sesion'] == $id_sesion) {
                $sesion_encontrada = true;
                break;
            }
        }

        if (!$sesion_encontrada) {
            return false;
        }

        // Obtener el session_id de esa sesión
        $sql = "SELECT session_id FROM sesiones_activas WHERE id_sesion = :id_sesion";
        $db = Database::getInstance();
        $sesion = $db->queryOne($sql, [':id_sesion' => $id_sesion]);

        if ($sesion) {
            return $this->sesion_model->cerrarSesion($sesion['session_id']);
        }

        return false;
    }

    /**
     * Bloquea el acceso si el usuario no cumple con un requisito
     *
     * @param bool $condicion Condición que debe cumplirse
     * @param string $mensaje Mensaje de error
     * @param string $redirect_url URL de redirección
     */
    public function bloquearSi($condicion, $mensaje, $redirect_url = 'login.php') {
        if ($condicion) {
            $_SESSION['mensaje_error'] = $mensaje;
            redirect(base_url($redirect_url));
        }
    }

    /**
     * Permite el acceso solo si el usuario cumple con un requisito
     *
     * @param bool $condicion Condición que debe cumplirse
     * @param string $mensaje Mensaje de error
     * @param string $redirect_url URL de redirección
     */
    public function permitirSolo($condicion, $mensaje, $redirect_url = 'dashboard.php') {
        if (!$condicion) {
            $_SESSION['mensaje_error'] = $mensaje;
            redirect(base_url($redirect_url));
        }
    }

    /**
     * Redirige al dashboard si el usuario ya está logueado
     * Útil para páginas de login y registro
     */
    public function redirigirSiEstaLogueado() {
        if ($this->verificarAutenticacion(false)) {
            redirect(base_url('dashboard.php'));
        }
    }

    /**
     * Verifica si el usuario actual es administrador
     *
     * @return bool True si es admin
     */
    public function esAdmin() {
        if (!$this->verificarAutenticacion(false)) {
            return false;
        }

        $usuario = $this->obtenerUsuarioActual();
        return $usuario && isset($usuario['rol']) && $usuario['rol'] === 'admin';
    }

    /**
     * Protege una ruta para que solo admins puedan acceder
     * Redirige al dashboard si no es admin
     */
    public function requiereAdmin() {
        $this->protegerRuta();

        if (!$this->esAdmin()) {
            $_SESSION['mensaje_error'] = 'No tienes permisos para acceder a esta sección';
            redirect(base_url('dashboard.php'));
        }
    }

    /**
     * Verifica si el usuario actual tiene permisos de admin (sin redirección)
     *
     * @return bool True si tiene permisos de admin
     */
    public function tienePermisosAdmin() {
        return $this->esAdmin();
    }
}
