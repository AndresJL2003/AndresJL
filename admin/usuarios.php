<?php
/**
 * Panel de Administración de Usuarios
 * Solo accesible para administradores
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

$middleware = new AuthMiddleware();
$middleware->requiereAdmin(); // Solo admins pueden acceder

$usuario_model = new Usuario();

// Obtener todos los usuarios
$usuarios = $usuario_model->obtenerTodos();
$estadisticas = $usuario_model->obtenerEstadisticas();

// Obtener planes disponibles
$plan_model = new Plan();
$planes = $plan_model->obtenerTodos();

// Mensajes
$mensaje_error = $_SESSION['mensaje_error'] ?? '';
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
unset($_SESSION['mensaje_error'], $_SESSION['mensaje_exito']);

$usuario_actual = $middleware->obtenerUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Usuarios - Plataforma Educativa</title>
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
                    <a href="<?= base_url('dashboard.php') ?>" class="hover:text-indigo-200">
                        <i class="fas fa-arrow-left mr-2"></i>Volver al Dashboard
                    </a>
                    <span><?= htmlspecialchars($usuario_actual['nombre']) ?></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Título -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-users-cog mr-3"></i>Administración de Usuarios
                </h1>
                <p class="text-gray-600 mt-2">Gestiona todos los usuarios de la plataforma</p>
            </div>
            <a href="crear-usuario.php" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg transition shadow-lg">
                <i class="fas fa-user-plus mr-2"></i>Crear Usuario
            </a>
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

        <?php if ($mensaje_exito): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <p class="text-green-700"><?= htmlspecialchars($mensaje_exito) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500 text-sm">Total Usuarios</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $estadisticas['total_usuarios'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-user-check text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500 text-sm">Usuarios Activos</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $estadisticas['usuarios_activos'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-user-times text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500 text-sm">Usuarios Inactivos</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $estadisticas['usuarios_inactivos'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-user-shield text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500 text-sm">Administradores</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $estadisticas['total_admins'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-list mr-2"></i>Lista de Usuarios
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registro</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                #<?= $usuario['id_usuario'] ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <span class="text-indigo-600 font-semibold">
                                            <?= strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)) ?>
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($usuario['email']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full
                                    <?= $usuario['nombre_plan'] === 'Premium' ? 'bg-purple-100 text-purple-800' : '' ?>
                                    <?= $usuario['nombre_plan'] === 'Pro' ? 'bg-blue-100 text-blue-800' : '' ?>
                                    <?= $usuario['nombre_plan'] === 'Basico' ? 'bg-gray-100 text-gray-800' : '' ?>">
                                    <?= htmlspecialchars($usuario['nombre_plan']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full
                                    <?= ($usuario['rol'] ?? 'usuario') === 'admin' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                    <i class="fas fa-<?= ($usuario['rol'] ?? 'usuario') === 'admin' ? 'user-shield' : 'user' ?> mr-1"></i>
                                    <?= htmlspecialchars($usuario['rol'] ?? 'usuario') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($usuario['activo']): ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>Activo
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i>Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="editar-usuario.php?id=<?= $usuario['id_usuario'] ?>"
                                   class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($usuario['id_usuario'] != $usuario_actual['id_usuario']): ?>
                                <a href="eliminar-usuario.php?id=<?= $usuario['id_usuario'] ?>"
                                   class="text-red-600 hover:text-red-900"
                                   onclick="return confirm('¿Estás seguro de eliminar este usuario?')"
                                   title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>
