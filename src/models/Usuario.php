<?php
/**
 * Clase Usuario
 * Modelo para gestión de usuarios en la plataforma
 */

class Usuario {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crea un nuevo usuario en la base de datos
     *
     * @param array $data Datos del usuario (nombre, apellido, email, password, id_plan)
     * @return int|false ID del usuario creado o false si falla
     */
    public function crear($data) {
        // Generar hash de contraseña seguro
        $password_hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        // Generar token de activación
        $token_activacion = generate_token(32);

        $sql = "INSERT INTO usuarios (nombre, apellido, email, password_hash, id_plan, token_activacion, activo)
                VALUES (:nombre, :apellido, :email, :password_hash, :id_plan, :token_activacion, 0)";

        $params = [
            ':nombre' => $data['nombre'],
            ':apellido' => $data['apellido'],
            ':email' => $data['email'],
            ':password_hash' => $password_hash,
            ':id_plan' => $data['id_plan'] ?? 1, // Plan básico por defecto
            ':token_activacion' => $token_activacion
        ];

        if ($this->db->execute($sql, $params)) {
            // Obtener el ID del usuario recién creado
            $usuario = $this->obtenerPorEmail($data['email']);
            return $usuario ? $usuario['id_usuario'] : false;
        }

        return false;
    }

    /**
     * Obtiene un usuario por su email
     *
     * @param string $email Email del usuario
     * @return array|false Datos del usuario o false
     */
    public function obtenerPorEmail($email) {
        $sql = "SELECT u.*, p.nombre_plan, p.sesiones_maximas, p.precio
                FROM usuarios u
                INNER JOIN planes p ON u.id_plan = p.id_plan
                WHERE u.email = :email";

        $usuario = $this->db->queryOne($sql, [':email' => $email]);

        // Asegurar que tenga rol definido
        if ($usuario && !isset($usuario['rol'])) {
            $usuario['rol'] = 'usuario';
        }

        return $usuario;
    }

    /**
     * Obtiene un usuario por su ID
     *
     * @param int $id ID del usuario
     * @return array|false Datos del usuario o false
     */
    public function obtenerPorId($id) {
        $sql = "SELECT u.*, p.nombre_plan, p.sesiones_maximas, p.precio
                FROM usuarios u
                INNER JOIN planes p ON u.id_plan = p.id_plan
                WHERE u.id_usuario = :id";

        $usuario = $this->db->queryOne($sql, [':id' => $id]);

        // Asegurar que tenga rol definido
        if ($usuario && !isset($usuario['rol'])) {
            $usuario['rol'] = 'usuario';
        }

        return $usuario;
    }

    /**
     * Verifica las credenciales de un usuario
     *
     * @param string $email Email del usuario
     * @param string $password Contraseña sin encriptar
     * @return array|false Datos del usuario si las credenciales son válidas, false en caso contrario
     */
    public function verificarCredenciales($email, $password) {
        $usuario = $this->obtenerPorEmail($email);

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            return $usuario;
        }

        return false;
    }

    /**
     * Activa una cuenta de usuario usando el token de activación
     *
     * @param string $token Token de activación
     * @return bool True si se activó correctamente
     */
    public function activarCuenta($token) {
        $sql = "UPDATE usuarios
                SET activo = 1, token_activacion = NULL
                WHERE token_activacion = :token AND activo = 0";

        return $this->db->execute($sql, [':token' => $token]);
    }

    /**
     * Verifica si un email ya está registrado
     *
     * @param string $email Email a verificar
     * @return bool True si existe, false si no
     */
    public function emailExiste($email) {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE email = :email";
        $result = $this->db->queryOne($sql, [':email' => $email]);
        return $result && $result['total'] > 0;
    }

    /**
     * Actualiza la última conexión del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return bool True si se actualizó correctamente
     */
    public function actualizarUltimaConexion($id_usuario) {
        $sql = "UPDATE usuarios SET ultima_conexion = NOW() WHERE id_usuario = :id";
        return $this->db->execute($sql, [':id' => $id_usuario]);
    }

    /**
     * Obtiene el token de activación para un usuario
     *
     * @param int $id_usuario ID del usuario
     * @return string|false Token de activación o false
     */
    public function obtenerTokenActivacion($id_usuario) {
        $sql = "SELECT token_activacion FROM usuarios WHERE id_usuario = :id";
        $result = $this->db->queryOne($sql, [':id' => $id_usuario]);
        return $result ? $result['token_activacion'] : false;
    }

    /**
     * Crea un token de recuperación de contraseña
     *
     * @param string $email Email del usuario
     * @return string|false Token generado o false
     */
    public function crearTokenRecuperacion($email) {
        $usuario = $this->obtenerPorEmail($email);
        if (!$usuario) {
            return false;
        }

        $token = generate_token(32);
        $expiracion = date('Y-m-d H:i:s', strtotime('+' . TOKEN_RECOVERY_EXPIRY . ' hours'));

        $sql = "INSERT INTO tokens_recuperacion (id_usuario, token, fecha_expiracion)
                VALUES (:id_usuario, :token, :expiracion)";

        $params = [
            ':id_usuario' => $usuario['id_usuario'],
            ':token' => $token,
            ':expiracion' => $expiracion
        ];

        if ($this->db->execute($sql, $params)) {
            return $token;
        }

        return false;
    }

    /**
     * Verifica si un token de recuperación es válido
     *
     * @param string $token Token a verificar
     * @return array|false Datos del token si es válido, false si no
     */
    public function verificarTokenRecuperacion($token) {
        $sql = "SELECT * FROM tokens_recuperacion
                WHERE token = :token
                AND usado = 0
                AND fecha_expiracion > NOW()";

        return $this->db->queryOne($sql, [':token' => $token]);
    }

    /**
     * Cambia la contraseña de un usuario usando un token de recuperación
     *
     * @param string $token Token de recuperación
     * @param string $nueva_password Nueva contraseña
     * @return bool True si se cambió correctamente
     */
    public function cambiarPasswordConToken($token, $nueva_password) {
        $token_data = $this->verificarTokenRecuperacion($token);
        if (!$token_data) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            // Actualizar contraseña
            $password_hash = password_hash($nueva_password, PASSWORD_BCRYPT, ['cost' => 12]);
            $sql = "UPDATE usuarios SET password_hash = :password WHERE id_usuario = :id";
            $this->db->execute($sql, [
                ':password' => $password_hash,
                ':id' => $token_data['id_usuario']
            ]);

            // Marcar token como usado
            $sql = "UPDATE tokens_recuperacion SET usado = 1 WHERE id_token = :id";
            $this->db->execute($sql, [':id' => $token_data['id_token']]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Actualiza el plan de un usuario
     *
     * @param int $id_usuario ID del usuario
     * @param int $id_plan ID del nuevo plan
     * @return bool True si se actualizó correctamente
     */
    public function actualizarPlan($id_usuario, $id_plan) {
        $sql = "UPDATE usuarios SET id_plan = :id_plan WHERE id_usuario = :id_usuario";
        return $this->db->execute($sql, [
            ':id_plan' => $id_plan,
            ':id_usuario' => $id_usuario
        ]);
    }

    /**
     * Obtiene todos los usuarios (para administración)
     *
     * @return array Lista de usuarios
     */
    public function obtenerTodos() {
        $sql = "SELECT u.*, p.nombre_plan, p.sesiones_maximas
                FROM usuarios u
                INNER JOIN planes p ON u.id_plan = p.id_plan
                ORDER BY u.fecha_registro DESC";

        return $this->db->query($sql);
    }

    /**
     * Elimina un usuario (soft delete marcando como inactivo)
     *
     * @param int $id_usuario ID del usuario
     * @return bool True si se eliminó correctamente
     */
    public function eliminar($id_usuario) {
        $sql = "UPDATE usuarios SET activo = 0 WHERE id_usuario = :id";
        return $this->db->execute($sql, [':id' => $id_usuario]);
    }

    /**
     * Elimina un usuario permanentemente (solo para admin)
     *
     * @param int $id_usuario ID del usuario
     * @return bool True si se eliminó correctamente
     */
    public function eliminarPermanente($id_usuario) {
        $sql = "DELETE FROM usuarios WHERE id_usuario = :id";
        return $this->db->execute($sql, [':id' => $id_usuario]);
    }

    /**
     * Verifica si un usuario es administrador
     *
     * @param int $id_usuario ID del usuario
     * @return bool True si es admin
     */
    public function esAdmin($id_usuario) {
        $sql = "SELECT rol FROM usuarios WHERE id_usuario = :id";
        $result = $this->db->queryOne($sql, [':id' => $id_usuario]);
        return $result && $result['rol'] === 'admin';
    }

    /**
     * Actualiza los datos de un usuario (para admin)
     *
     * @param int $id_usuario ID del usuario
     * @param array $datos Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function actualizar($id_usuario, $datos) {
        $campos = [];
        $params = [':id' => $id_usuario];

        if (isset($datos['nombre'])) {
            $campos[] = "nombre = :nombre";
            $params[':nombre'] = $datos['nombre'];
        }

        if (isset($datos['apellido'])) {
            $campos[] = "apellido = :apellido";
            $params[':apellido'] = $datos['apellido'];
        }

        if (isset($datos['email'])) {
            $campos[] = "email = :email";
            $params[':email'] = $datos['email'];
        }

        if (isset($datos['id_plan'])) {
            $campos[] = "id_plan = :id_plan";
            $params[':id_plan'] = $datos['id_plan'];
        }

        if (isset($datos['activo'])) {
            $campos[] = "activo = :activo";
            $params[':activo'] = $datos['activo'];
        }

        if (isset($datos['rol'])) {
            $campos[] = "rol = :rol";
            $params[':rol'] = $datos['rol'];
        }

        if (empty($campos)) {
            return false;
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id_usuario = :id";
        return $this->db->execute($sql, $params);
    }

    /**
     * Obtiene estadísticas de usuarios (para dashboard admin)
     *
     * @return array Estadísticas
     */
    public function obtenerEstadisticas() {
        $sql = "SELECT
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as usuarios_activos,
                    SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as usuarios_inactivos,
                    SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as total_admins
                FROM usuarios";

        return $this->db->queryOne($sql);
    }
}
