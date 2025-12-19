<?php
/**
 * Clase AuthController
 * Controlador para manejo de autenticación
 * Gestiona registro, login, activación de cuenta y recuperación de contraseña
 */

class AuthController {
    private $usuario_model;
    private $auth_middleware;
    private $email_service;

    public function __construct() {
        $this->usuario_model = new Usuario();
        $this->auth_middleware = new AuthMiddleware();
        $this->email_service = new EmailService();
    }

    /**
     * Procesa el registro de un nuevo usuario
     *
     * @param array $datos Datos del formulario de registro
     * @return array Resultado del registro ['success' => bool, 'message' => string]
     */
    public function registrar($datos) {
        // Validar datos
        $validacion = $this->validarRegistro($datos);
        if (!$validacion['success']) {
            return $validacion;
        }

        // Verificar que el email no exista
        if ($this->usuario_model->emailExiste($datos['email'])) {
            return [
                'success' => false,
                'message' => 'El correo electrónico ya está registrado'
            ];
        }

        // Verificar que las contraseñas coincidan
        if ($datos['password'] !== $datos['password_confirm']) {
            return [
                'success' => false,
                'message' => 'Las contraseñas no coinciden'
            ];
        }

        // Crear usuario
        $id_usuario = $this->usuario_model->crear([
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'id_plan' => $datos['id_plan'] ?? 1 // Plan básico por defecto
        ]);

        if (!$id_usuario) {
            return [
                'success' => false,
                'message' => 'Error al crear el usuario. Por favor intenta de nuevo.'
            ];
        }

        // Obtener token de activación
        $token = $this->usuario_model->obtenerTokenActivacion($id_usuario);

        // Enviar correo de activación
        $email_enviado = $this->email_service->enviarActivacion(
            $datos['email'],
            $datos['nombre'],
            $token
        );

        if (!$email_enviado) {
            return [
                'success' => true,
                'message' => 'Usuario registrado correctamente, pero hubo un error al enviar el correo de activación. Contacta al administrador.',
                'warning' => true
            ];
        }

        return [
            'success' => true,
            'message' => 'Usuario registrado correctamente. Por favor revisa tu correo electrónico para activar tu cuenta.'
        ];
    }

    /**
     * Procesa el inicio de sesión
     *
     * @param array $datos Datos del formulario de login
     * @return array Resultado del login ['success' => bool, 'message' => string]
     */
    public function login($datos) {
        // Validar datos
        if (empty($datos['email']) || empty($datos['password'])) {
            return [
                'success' => false,
                'message' => 'Por favor ingresa tu correo y contraseña'
            ];
        }

        // Verificar credenciales
        $usuario = $this->usuario_model->verificarCredenciales(
            $datos['email'],
            $datos['password']
        );

        if (!$usuario) {
            return [
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ];
        }

        // Verificar que la cuenta esté activada
        if (!$usuario['activo']) {
            return [
                'success' => false,
                'message' => 'Tu cuenta no está activada. Por favor revisa tu correo electrónico.'
            ];
        }

        // Iniciar sesión
        $sesion_iniciada = $this->auth_middleware->iniciarSesion($usuario);

        if (!$sesion_iniciada) {
            return [
                'success' => false,
                'message' => 'Error al iniciar sesión. Por favor intenta de nuevo.'
            ];
        }

        return [
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'redirect' => base_url('dashboard.php')
        ];
    }

    /**
     * Procesa la activación de una cuenta
     *
     * @param string $token Token de activación
     * @return array Resultado de la activación
     */
    public function activarCuenta($token) {
        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Token de activación inválido'
            ];
        }

        $activado = $this->usuario_model->activarCuenta($token);

        if (!$activado) {
            return [
                'success' => false,
                'message' => 'Token inválido o cuenta ya activada'
            ];
        }

        return [
            'success' => true,
            'message' => 'Cuenta activada correctamente. Ya puedes iniciar sesión.',
            'redirect' => base_url('login.php')
        ];
    }

    /**
     * Procesa la solicitud de recuperación de contraseña
     *
     * @param string $email Email del usuario
     * @return array Resultado de la solicitud
     */
    public function solicitarRecuperacion($email) {
        if (empty($email)) {
            return [
                'success' => false,
                'message' => 'Por favor ingresa tu correo electrónico'
            ];
        }

        // Verificar que el email exista
        if (!$this->usuario_model->emailExiste($email)) {
            // Por seguridad, no revelar si el email existe o no
            return [
                'success' => true,
                'message' => 'Si el correo existe en nuestra base de datos, recibirás un enlace de recuperación.'
            ];
        }

        // Crear token de recuperación
        $token = $this->usuario_model->crearTokenRecuperacion($email);

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Error al generar token de recuperación. Por favor intenta de nuevo.'
            ];
        }

        // Obtener datos del usuario
        $usuario = $this->usuario_model->obtenerPorEmail($email);

        // Enviar correo de recuperación
        $email_enviado = $this->email_service->enviarRecuperacion(
            $email,
            $usuario['nombre'],
            $token
        );

        if (!$email_enviado) {
            return [
                'success' => false,
                'message' => 'Error al enviar el correo de recuperación. Por favor intenta de nuevo.'
            ];
        }

        return [
            'success' => true,
            'message' => 'Se ha enviado un enlace de recuperación a tu correo electrónico.'
        ];
    }

    /**
     * Procesa el cambio de contraseña con token
     *
     * @param array $datos Datos del formulario
     * @return array Resultado del cambio
     */
    public function cambiarPasswordConToken($datos) {
        // Validar datos
        if (empty($datos['token']) || empty($datos['password']) || empty($datos['password_confirm'])) {
            return [
                'success' => false,
                'message' => 'Todos los campos son obligatorios'
            ];
        }

        // Verificar que las contraseñas coincidan
        if ($datos['password'] !== $datos['password_confirm']) {
            return [
                'success' => false,
                'message' => 'Las contraseñas no coinciden'
            ];
        }

        // Validar contraseña
        if (strlen($datos['password']) < 8) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos 8 caracteres'
            ];
        }

        // Verificar token
        $token_valido = $this->usuario_model->verificarTokenRecuperacion($datos['token']);

        if (!$token_valido) {
            return [
                'success' => false,
                'message' => 'Token inválido o expirado'
            ];
        }

        // Cambiar contraseña
        $cambiado = $this->usuario_model->cambiarPasswordConToken(
            $datos['token'],
            $datos['password']
        );

        if (!$cambiado) {
            return [
                'success' => false,
                'message' => 'Error al cambiar la contraseña. Por favor intenta de nuevo.'
            ];
        }

        return [
            'success' => true,
            'message' => 'Contraseña cambiada correctamente. Ya puedes iniciar sesión.',
            'redirect' => base_url('login.php')
        ];
    }

    /**
     * Cierra la sesión actual
     *
     * @return array Resultado del cierre de sesión
     */
    public function logout() {
        $this->auth_middleware->cerrarSesionActual();

        return [
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
            'redirect' => base_url('login.php')
        ];
    }

    /**
     * Valida los datos del formulario de registro
     *
     * @param array $datos Datos a validar
     * @return array Resultado de la validación
     */
    private function validarRegistro($datos) {
        // Verificar campos requeridos
        $campos_requeridos = ['nombre', 'apellido', 'email', 'password', 'password_confirm'];

        foreach ($campos_requeridos as $campo) {
            if (empty($datos[$campo])) {
                return [
                    'success' => false,
                    'message' => 'Todos los campos son obligatorios'
                ];
            }
        }

        // Validar email
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'El correo electrónico no es válido'
            ];
        }

        // Validar longitud de contraseña
        if (strlen($datos['password']) < 8) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos 8 caracteres'
            ];
        }

        // Validar que la contraseña tenga al menos una letra y un número
        if (!preg_match('/[a-zA-Z]/', $datos['password']) || !preg_match('/[0-9]/', $datos['password'])) {
            return [
                'success' => false,
                'message' => 'La contraseña debe contener al menos una letra y un número'
            ];
        }

        return ['success' => true];
    }
}
