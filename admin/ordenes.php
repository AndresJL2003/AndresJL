<?php
/**
 * Panel de Administración - Gestión de Órdenes
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

// Proteger la ruta - solo administradores
$middleware = new AuthMiddleware();
$middleware->protegerRuta();
$middleware->requiereAdmin();

// Obtener usuario actual
$usuario_actual = $middleware->obtenerUsuarioActual();

// Modelos
$orden_model = new Orden();
$pago_model = new Pago();

// Filtros
$filtro_estado = $_GET['estado'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Obtener órdenes
if ($filtro_estado || $busqueda) {
    $ordenes = $orden_model->buscar($busqueda, $filtro_estado);
} else {
    $ordenes = $orden_model->obtenerTodas();
}

// Estadísticas
$total_ordenes = count($ordenes);
$total_completadas = count(array_filter($ordenes, fn($o) => $o['estado_pago'] === 'completado'));
$total_pendientes = count(array_filter($ordenes, fn($o) => $o['estado_pago'] === 'pendiente'));
$total_ingresos = array_sum(array_map(fn($o) => $o['estado_pago'] === 'completado' ? $o['total'] : 0, $ordenes));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Órdenes - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">

    <!-- Navbar Admin -->
    <nav class="bg-gradient-to-r from-purple-600 to-indigo-600 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../dashboard.php" class="text-white text-2xl font-bold">
                        <i class="fas fa-graduation-cap mr-2"></i>Plataforma Educativa
                    </a>
                    <span class="ml-4 px-3 py-1 bg-yellow-400 text-purple-900 text-xs font-bold rounded-full">
                        <i class="fas fa-crown mr-1"></i>ADMIN
                    </span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-white hover:bg-white/20 px-3 py-2 rounded-md transition">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <a href="usuarios.php" class="text-white hover:bg-white/20 px-3 py-2 rounded-md transition">
                        <i class="fas fa-users mr-2"></i>Usuarios
                    </a>
                    <a href="cursos.php" class="text-white hover:bg-white/20 px-3 py-2 rounded-md transition">
                        <i class="fas fa-book mr-2"></i>Cursos
                    </a>
                    <a href="ordenes.php" class="text-white bg-white/20 px-3 py-2 rounded-md">
                        <i class="fas fa-shopping-cart mr-2"></i>Órdenes
                    </a>
                    <a href="../dashboard.php" class="text-white hover:bg-white/20 px-3 py-2 rounded-md transition">
                        <i class="fas fa-arrow-left mr-2"></i>Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-shopping-cart text-purple-600 mr-3"></i>Gestión de Órdenes
            </h1>
            <p class="text-gray-600 mt-2">Administra las órdenes de compra de la plataforma</p>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Órdenes</p>
                        <p class="text-3xl font-bold text-purple-600 mt-2"><?= $total_ordenes ?></p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-receipt text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Completadas</p>
                        <p class="text-3xl font-bold text-green-600 mt-2"><?= $total_completadas ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pendientes</p>
                        <p class="text-3xl font-bold text-yellow-600 mt-2"><?= $total_pendientes ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Ingresos</p>
                        <p class="text-2xl font-bold text-indigo-600 mt-2"><?= format_price($total_ingresos) ?></p>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <i class="fas fa-dollar-sign text-indigo-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                    <input type="text" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>"
                           placeholder="Número de orden, nombre..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Todos los estados</option>
                        <option value="completado" <?= $filtro_estado === 'completado' ? 'selected' : '' ?>>Completado</option>
                        <option value="pendiente" <?= $filtro_estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="fallido" <?= $filtro_estado === 'fallido' ? 'selected' : '' ?>>Fallido</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 rounded-lg transition">
                        <i class="fas fa-search mr-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla de Órdenes -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orden</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado Pago</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($ordenes as $orden): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-purple-600">
                                    <?= htmlspecialchars($orden['numero_orden']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($orden['nombre_facturacion']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($orden['email_facturacion']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                                <?= format_price($orden['total']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($orden['estado_pago'] === 'completado'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Completado
                                </span>
                                <?php elseif ($orden['estado_pago'] === 'pendiente'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-1"></i>Pendiente
                                </span>
                                <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle mr-1"></i>Fallido
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d/m/Y H:i', strtotime($orden['fecha_creacion'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="../mis-compras.php?orden=<?= $orden['id_orden'] ?>" class="text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
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
