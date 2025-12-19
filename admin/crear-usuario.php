<?php
/**
 * Crear Usuario
 * Solo accesible para administradores
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

$middleware = new AuthMiddleware();
$middleware->requiereAdmin();

$usuario_model = new Usuario();
$plan_model = new Plan();

// Obtener planes disponibles
$planes = $plan_model->obtenerTodos();

// Procesar formulario
$mensaje_error = '';
$mensaje_exito = '';
$datos = [
    'nombre' => '',
    'apellido' => '',
    'email' => '',
    'password' => '',
    'id_plan' => 1,
    'rol' => 'usuario',
    'activo' => true
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'apellido' => trim($_POST['apellido'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
        'id_plan' => $_POST['id_plan'] ?? 1,
        'rol' => $_POST['rol'] ?? 'usuario',
        'activo' => isset($_POST['activo']) ? 1 : 0
    ];

    // Validaciones
    if (empty($datos['nombre']) || empty($datos['apellido']) || empty($datos['email']) || empty($datos['password'])) {
        $mensaje_error = 'Todos los campos son obligatorios';
    } elseif (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = 'El email no es válido';
    } elseif (strlen($datos['password']) < 6) {
        $mensaje_error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($datos['password'] !== $datos['password_confirm']) {
        $mensaje_error = 'Las contraseñas no coinciden';
    } else {
        // Verificar si el email ya existe
        $usuario_existente = $usuario_model->obtenerPorEmail($datos['email']);
        if ($usuario_existente) {
            $mensaje_error = 'El email ya está registrado';
        } else {
            // Crear usuario usando el método del modelo
            $id_usuario = $usuario_model->crear($datos);

            if ($id_usuario) {
                // Si el usuario debe estar activo desde el inicio o tiene rol admin, activarlo
                if ($datos['activo'] || $datos['rol'] === 'admin') {
                    $db = Database::getInstance();

                    // Actualizar estado activo y rol
                    $sql = "UPDATE usuarios
                            SET activo = :activo,
                                rol = :rol,
                                token_activacion = NULL
                            WHERE id_usuario = :id";

                    $db->execute($sql, [
                        ':activo' => $datos['activo'],
                        ':rol' => $datos['rol'],
                        ':id' => $id_usuario
                    ]);
                }

                $_SESSION['mensaje_exito'] = 'Usuario creado correctamente';
                redirect(base_url('admin/usuarios.php'));
            } else {
                $mensaje_error = 'Error al crear el usuario';
            }
        }
    }
}

$usuario_actual = $middleware->obtenerUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario - Admin Panel</title>
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
                <i class="fas fa-user-plus mr-3"></i>Crear Nuevo Usuario
            </h1>
            <p class="text-gray-600 mt-2">Agrega un nuevo usuario a la plataforma</p>
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
                            <i class="fas fa-user mr-2"></i>Nombre <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            value="<?= htmlspecialchars($datos['nombre']) ?>"
                            placeholder="Ej: Juan"
                        >
                    </div>

                    <!-- Apellido -->
                    <div>
                        <label for="apellido" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Apellido <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="apellido"
                            name="apellido"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            value="<?= htmlspecialchars($datos['apellido']) ?>"
                            placeholder="Ej: Pérez"
                        >
                    </div>

                    <!-- Email -->
                    <div class="md:col-span-2">
                        <label for="email" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            value="<?= htmlspecialchars($datos['email']) ?>"
                            placeholder="usuario@ejemplo.com"
                        >
                    </div>

                    <!-- Contraseña -->
                    <div>
                        <label for="password" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Contraseña <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            minlength="6"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="Mínimo 6 caracteres"
                        >
                        <p class="text-sm text-gray-500 mt-1">Mínimo 6 caracteres</p>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div>
                        <label for="password_confirm" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Confirmar Contraseña <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            required
                            minlength="6"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="Repite la contraseña"
                        >
                    </div>

                    <!-- Plan -->
                    <div>
                        <label for="id_plan" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-crown mr-2"></i>Plan de Suscripción
                        </label>
                        <select
                            id="id_plan"
                            name="id_plan"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <?php foreach ($planes as $plan): ?>
                            <option value="<?= $plan['id_plan'] ?>"
                                <?= $datos['id_plan'] == $plan['id_plan'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($plan['nombre_plan']) ?> -
                                <?= $plan['sesiones_maximas'] ?> sesión(es) -
                                $<?= number_format($plan['precio'], 2) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Rol -->
                    <div>
                        <label for="rol" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-user-shield mr-2"></i>Rol del Usuario
                        </label>
                        <select
                            id="rol"
                            name="rol"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="usuario" <?= $datos['rol'] === 'usuario' ? 'selected' : '' ?>>
                                Usuario Regular
                            </option>
                            <option value="admin" <?= $datos['rol'] === 'admin' ? 'selected' : '' ?>>
                                Administrador
                            </option>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">
                            Los administradores tienen acceso total al sistema
                        </p>
                    </div>

                    <!-- Estado Activo -->
                    <div class="md:col-span-2">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                name="activo"
                                class="w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                <?= $datos['activo'] ? 'checked' : '' ?>
                            >
                            <span class="ml-3 text-gray-700 font-medium">
                                <i class="fas fa-check-circle mr-2"></i>Activar cuenta inmediatamente
                            </span>
                        </label>
                        <p class="text-sm text-gray-500 mt-1 ml-8">
                            Si está marcado, el usuario podrá iniciar sesión inmediatamente sin verificar su email.
                            Los administradores se activan automáticamente.
                        </p>
                    </div>

                </div>

                <!-- Información -->
                <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                        <div class="text-sm text-blue-700">
                            <p class="font-semibold mb-2">Información importante:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>El email debe ser único en el sistema</li>
                                <li>La contraseña se guardará de forma segura (hash bcrypt)</li>
                                <li>Los usuarios con rol "Administrador" se activan automáticamente</li>
                                <li>Si no activas la cuenta, el usuario recibirá un email de activación</li>
                            </ul>
                        </div>
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
                        <i class="fas fa-user-plus mr-2"></i>Crear Usuario
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- Script para validación de contraseñas -->
    <script>
        // Validar que las contraseñas coincidan
        const password = document.getElementById('password');
        const passwordConfirm = document.getElementById('password_confirm');

        function validatePasswords() {
            if (password.value !== passwordConfirm.value) {
                passwordConfirm.setCustomValidity('Las contraseñas no coinciden');
            } else {
                passwordConfirm.setCustomValidity('');
            }
        }

        password.addEventListener('change', validatePasswords);
        passwordConfirm.addEventListener('keyup', validatePasswords);

        // Auto-activar si el rol es admin
        const rolSelect = document.getElementById('rol');
        const activoCheckbox = document.querySelector('input[name="activo"]');

        rolSelect.addEventListener('change', function() {
            if (this.value === 'admin') {
                activoCheckbox.checked = true;
            }
        });
    </script>

</body>
</html>
