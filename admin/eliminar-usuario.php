<?php
/**
 * Eliminar Usuario
 * Solo accesible para administradores
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

$middleware = new AuthMiddleware();
$middleware->requiereAdmin();

$usuario_model = new Usuario();

// Obtener ID del usuario a eliminar
$id_usuario = $_GET['id'] ?? 0;

if (!$id_usuario) {
    $_SESSION['mensaje_error'] = 'Usuario no especificado';
    redirect(base_url('admin/usuarios.php'));
}

// Verificar que el usuario exista
$usuario = $usuario_model->obtenerPorId($id_usuario);

if (!$usuario) {
    $_SESSION['mensaje_error'] = 'Usuario no encontrado';
    redirect(base_url('admin/usuarios.php'));
}

// Verificar que no se esté intentando eliminar a sí mismo
$usuario_actual = $middleware->obtenerUsuarioActual();
if ($id_usuario == $usuario_actual['id_usuario']) {
    $_SESSION['mensaje_error'] = 'No puedes eliminar tu propia cuenta';
    redirect(base_url('admin/usuarios.php'));
}

// Confirmar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_eliminacion = $_POST['tipo'] ?? 'soft';

    if ($tipo_eliminacion === 'permanente') {
        // Eliminación permanente
        if ($usuario_model->eliminarPermanente($id_usuario)) {
            $_SESSION['mensaje_exito'] = 'Usuario eliminado permanentemente';
        } else {
            $_SESSION['mensaje_error'] = 'Error al eliminar el usuario';
        }
    } else {
        // Soft delete (desactivar)
        if ($usuario_model->eliminar($id_usuario)) {
            $_SESSION['mensaje_exito'] = 'Usuario desactivado correctamente';
        } else {
            $_SESSION['mensaje_error'] = 'Error al desactivar el usuario';
        }
    }

    redirect(base_url('admin/usuarios.php'));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Usuario - Admin Panel</title>
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
                <i class="fas fa-user-times mr-3"></i>Eliminar Usuario
            </h1>
            <p class="text-gray-600 mt-2">Confirma la eliminación del usuario</p>
        </div>

        <!-- Información del Usuario -->
        <div class="bg-white rounded-lg shadow p-8 mb-6">
            <div class="flex items-center mb-6">
                <div class="flex-shrink-0 h-20 w-20 bg-indigo-100 rounded-full flex items-center justify-center">
                    <span class="text-indigo-600 font-bold text-2xl">
                        <?= strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)) ?>
                    </span>
                </div>
                <div class="ml-6">
                    <h2 class="text-2xl font-bold text-gray-900">
                        <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>
                    </h2>
                    <p class="text-gray-600"><?= htmlspecialchars($usuario['email']) ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
                <div>
                    <span class="text-gray-500 text-sm">ID:</span>
                    <span class="font-medium ml-2">#<?= $usuario['id_usuario'] ?></span>
                </div>
                <div>
                    <span class="text-gray-500 text-sm">Plan:</span>
                    <span class="font-medium ml-2"><?= htmlspecialchars($usuario['nombre_plan']) ?></span>
                </div>
                <div>
                    <span class="text-gray-500 text-sm">Rol:</span>
                    <span class="font-medium ml-2"><?= htmlspecialchars($usuario['rol'] ?? 'usuario') ?></span>
                </div>
                <div>
                    <span class="text-gray-500 text-sm">Estado:</span>
                    <span class="font-medium ml-2">
                        <?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>
                <div>
                    <span class="text-gray-500 text-sm">Fecha de Registro:</span>
                    <span class="font-medium ml-2"><?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></span>
                </div>
                <?php if ($usuario['ultima_conexion']): ?>
                <div>
                    <span class="text-gray-500 text-sm">Última Conexión:</span>
                    <span class="font-medium ml-2"><?= date('d/m/Y H:i', strtotime($usuario['ultima_conexion'])) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Opciones de Eliminación -->
        <div class="bg-white rounded-lg shadow p-8">
            <h3 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                ¿Cómo deseas eliminar este usuario?
            </h3>

            <div class="space-y-4">
                <!-- Opción 1: Soft Delete -->
                <div class="border border-gray-300 rounded-lg p-6 hover:border-indigo-500 transition">
                    <form method="POST" action="">
                        <input type="hidden" name="tipo" value="soft">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 mb-2">
                                    <i class="fas fa-user-slash text-yellow-600 mr-2"></i>
                                    Desactivar Usuario (Recomendado)
                                </h4>
                                <p class="text-gray-600 text-sm mb-4">
                                    El usuario será marcado como inactivo y no podrá iniciar sesión.
                                    Sus datos se conservarán en el sistema y podrá ser reactivado más tarde.
                                </p>
                                <ul class="text-sm text-gray-500 space-y-1 mb-4">
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Los datos se conservan</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Puede ser reactivado</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Historial preservado</li>
                                </ul>
                            </div>
                            <button
                                type="submit"
                                class="ml-4 px-6 py-3 bg-yellow-500 hover:bg-yellow-600 text-white font-bold rounded-lg transition"
                                onclick="return confirm('¿Desactivar este usuario?')">
                                <i class="fas fa-user-slash mr-2"></i>Desactivar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Opción 2: Eliminación Permanente -->
                <div class="border border-red-300 rounded-lg p-6 hover:border-red-500 transition bg-red-50">
                    <form method="POST" action="">
                        <input type="hidden" name="tipo" value="permanente">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="font-bold text-red-900 mb-2">
                                    <i class="fas fa-trash-alt text-red-600 mr-2"></i>
                                    Eliminar Permanentemente
                                </h4>
                                <p class="text-red-700 text-sm mb-4">
                                    <strong>¡PELIGRO!</strong> Esta acción es irreversible.
                                    El usuario y todos sus datos relacionados serán eliminados permanentemente de la base de datos.
                                </p>
                                <ul class="text-sm text-red-600 space-y-1 mb-4">
                                    <li><i class="fas fa-times text-red-500 mr-2"></i>Eliminación permanente</li>
                                    <li><i class="fas fa-times text-red-500 mr-2"></i>No se puede deshacer</li>
                                    <li><i class="fas fa-times text-red-500 mr-2"></i>Se pierden todos los datos</li>
                                </ul>
                            </div>
                            <button
                                type="submit"
                                class="ml-4 px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition"
                                onclick="return confirm('⚠️ ADVERTENCIA ⚠️\n\n¿Estás ABSOLUTAMENTE SEGURO de eliminar permanentemente este usuario?\n\nEsta acción NO SE PUEDE DESHACER.\n\nTodos los datos del usuario serán eliminados permanentemente.')">
                                <i class="fas fa-trash-alt mr-2"></i>Eliminar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Botón Cancelar -->
            <div class="mt-8 text-center">
                <a href="<?= base_url('admin/usuarios.php') ?>"
                   class="inline-block px-8 py-3 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>Cancelar
                </a>
            </div>
        </div>

    </div>

</body>
</html>
