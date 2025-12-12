<?php
/**
 * Editar Usuario
 * Solo accesible para administradores
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

$middleware = new AuthMiddleware();
$middleware->requiereAdmin();

$usuario_model = new Usuario();
$plan_model = new Plan();

// Obtener ID del usuario a editar
$id_usuario = $_GET['id'] ?? 0;

if (!$id_usuario) {
    $_SESSION['mensaje_error'] = 'Usuario no especificado';
    redirect(base_url('admin/usuarios.php'));
}

$usuario = $usuario_model->obtenerPorId($id_usuario);

if (!$usuario) {
    $_SESSION['mensaje_error'] = 'Usuario no encontrado';
    redirect(base_url('admin/usuarios.php'));
}

// Obtener planes disponibles
$planes = $plan_model->obtenerTodos();

// Procesar formulario
$mensaje_error = '';
$mensaje_exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre' => $_POST['nombre'] ?? '',
        'apellido' => $_POST['apellido'] ?? '',
        'email' => $_POST['email'] ?? '',
        'id_plan' => $_POST['id_plan'] ?? 1,
        'activo' => isset($_POST['activo']) ? 1 : 0,
        'rol' => $_POST['rol'] ?? 'usuario'
    ];

    // Validar datos
    if (empty($datos['nombre']) || empty($datos['apellido']) || empty($datos['email'])) {
        $mensaje_error = 'Todos los campos son obligatorios';
    } elseif (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = 'El email no es válido';
    } else {
        // Verificar si el email ya existe (excepto el actual)
        $usuario_existente = $usuario_model->obtenerPorEmail($datos['email']);
        if ($usuario_existente && $usuario_existente['id_usuario'] != $id_usuario) {
            $mensaje_error = 'El email ya está registrado con otro usuario';
        } else {
            // Actualizar usuario
            if ($usuario_model->actualizar($id_usuario, $datos)) {
                $_SESSION['mensaje_exito'] = 'Usuario actualizado correctamente';
                redirect(base_url('admin/usuarios.php'));
            } else {
                $mensaje_error = 'Error al actualizar el usuario';
            }
        }
    }

    // Si hay error, mantener los datos ingresados
    if ($mensaje_error) {
        $usuario = array_merge($usuario, $datos);
    }
}

$usuario_actual = $middleware->obtenerUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-indigo-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <i class="fas fa-graduation-cap text-2xl mr-3"></i>
                    <span class="font-bold text-xl">Admin Panel</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?= base_url('admin/usuarios.php') ?>" class="hover:text-indigo-200">
                        <i class="fas fa-arrow-left mr-2"></i>Volver a Usuarios
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Título -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-user-edit mr-3"></i>Editar Usuario
            </h1>
            <p class="text-gray-600 mt-2">Modifica la información del usuario</p>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje_error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <p class="text-red-700"><?= htmlspecialchars($mensaje_error) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="bg-white rounded-lg shadow p-8">
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Nombre -->
                    <div>
                        <label for="nombre" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Nombre
                        </label>
                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            value="<?= htmlspecialchars($usuario['nombre']) ?>"
                        >
                    </div>

                    <!-- Apellido -->
                    <div>
                        <label for="apellido" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Apellido
                        </label>
                        <input
                            type="text"
                            id="apellido"
                            name="apellido"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            value="<?= htmlspecialchars($usuario['apellido']) ?>"
                        >
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            value="<?= htmlspecialchars($usuario['email']) ?>"
                        >
                    </div>

                    <!-- Plan -->
                    <div>
                        <label for="id_plan" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-crown mr-2"></i>Plan
                        </label>
                        <select
                            id="id_plan"
                            name="id_plan"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <?php foreach ($planes as $plan): ?>
                            <option value="<?= $plan['id_plan'] ?>"
                                <?= $usuario['id_plan'] == $plan['id_plan'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($plan['nombre_plan']) ?> -
                                <?= $plan['sesiones_maximas'] ?> sesión(es)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Rol -->
                    <div>
                        <label for="rol" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-user-shield mr-2"></i>Rol
                        </label>
                        <select
                            id="rol"
                            name="rol"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="usuario" <?= ($usuario['rol'] ?? 'usuario') === 'usuario' ? 'selected' : '' ?>>
                                Usuario
                            </option>
                            <option value="admin" <?= ($usuario['rol'] ?? 'usuario') === 'admin' ? 'selected' : '' ?>>
                                Administrador
                            </option>
                        </select>
                    </div>

                    <!-- Estado Activo -->
                    <div>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                name="activo"
                                class="w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                <?= $usuario['activo'] ? 'checked' : '' ?>
                            >
                            <span class="ml-3 text-gray-700 font-medium">
                                <i class="fas fa-check-circle mr-2"></i>Cuenta Activa
                            </span>
                        </label>
                        <p class="text-sm text-gray-500 mt-1 ml-8">
                            Si está desactivada, el usuario no podrá iniciar sesión
                        </p>
                    </div>

                </div>

                <!-- Información adicional -->
                <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-semibold text-gray-700 mb-2">Información del Usuario</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">ID:</span>
                            <span class="font-medium ml-2">#<?= $usuario['id_usuario'] ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Fecha de Registro:</span>
                            <span class="font-medium ml-2"><?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?></span>
                        </div>
                        <?php if ($usuario['ultima_conexion']): ?>
                        <div>
                            <span class="text-gray-500">Última Conexión:</span>
                            <span class="font-medium ml-2"><?= date('d/m/Y H:i', strtotime($usuario['ultima_conexion'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Botones -->
                <div class="mt-8 flex justify-between">
                    <a href="<?= base_url('admin/usuarios.php') ?>"
                       class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg transition">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                    <button
                        type="submit"
                        class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

    </div>

</body>
</html>
