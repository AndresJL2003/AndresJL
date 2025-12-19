<?php
/**
 * Página de Historial de Compras
 * Muestra todas las órdenes del usuario
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

// Proteger la ruta
$middleware = new AuthMiddleware();
$middleware->protegerRuta();

// Obtener usuario actual
$usuario = $middleware->obtenerUsuarioActual();

// Obtener todas las órdenes del usuario
$orden_model = new Orden();
$ordenes = $orden_model->obtenerOrdenesUsuario($usuario['id_usuario']);

// Si se solicita ver detalles de una orden específica
$orden_detalle = null;
$items_detalle = [];
if (isset($_GET['ver']) && is_numeric($_GET['ver'])) {
    $id_orden = (int)$_GET['ver'];
    $orden_detalle = $orden_model->obtenerOrden($id_orden);

    // Verificar que la orden pertenece al usuario
    if ($orden_detalle && $orden_detalle['id_usuario'] == $usuario['id_usuario']) {
        $items_detalle = $orden_model->obtenerItemsOrden($id_orden);
    } else {
        $orden_detalle = null;
        $_SESSION['mensaje_error'] = 'No tienes permiso para ver esta orden.';
    }
}

// Mensajes
$mensaje_error = $_SESSION['mensaje_error'] ?? '';
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
unset($_SESSION['mensaje_error'], $_SESSION['mensaje_exito']);

// Función para obtener clase CSS según estado
function getEstadoBadgeClass($estado) {
    $clases = [
        'pendiente' => 'bg-yellow-100 text-yellow-800',
        'pagada' => 'bg-green-100 text-green-800',
        'fallida' => 'bg-red-100 text-red-800',
        'cancelada' => 'bg-gray-100 text-gray-800',
        'reembolsada' => 'bg-blue-100 text-blue-800',
        'procesando' => 'bg-blue-100 text-blue-800',
        'completado' => 'bg-green-100 text-green-800',
        'fallido' => 'bg-red-100 text-red-800',
        'reembolsado' => 'bg-blue-100 text-blue-800'
    ];
    return $clases[$estado] ?? 'bg-gray-100 text-gray-800';
}

// Función para obtener icono según estado
function getEstadoIcon($estado) {
    $iconos = [
        'pendiente' => 'fa-clock',
        'pagada' => 'fa-check-circle',
        'fallida' => 'fa-times-circle',
        'cancelada' => 'fa-ban',
        'reembolsada' => 'fa-undo',
        'procesando' => 'fa-spinner fa-spin',
        'completado' => 'fa-check-circle',
        'fallido' => 'fa-times-circle',
        'reembolsado' => 'fa-undo'
    ];
    return $iconos[$estado] ?? 'fa-question-circle';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Compras - Plataforma Educativa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-2xl font-bold text-indigo-600">
                        <i class="fas fa-graduation-cap mr-2"></i>Plataforma Educativa
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="carrito.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md transition">
                        <i class="fas fa-shopping-cart mr-2"></i>Carrito
                    </a>
                    <a href="dashboard.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md transition">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="cursos.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md transition">
                        <i class="fas fa-book mr-2"></i>Cursos
                    </a>
                    <a href="mis-compras.php" class="text-indigo-600 px-3 py-2 rounded-md font-semibold">
                        <i class="fas fa-receipt mr-2"></i>Mis Compras
                    </a>
                    <a href="logout.php" class="text-red-600 hover:text-red-800 px-3 py-2 rounded-md transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <h1 class="text-3xl font-bold mb-8 text-gray-800">
            <i class="fas fa-receipt mr-3 text-indigo-600"></i>Historial de Compras
        </h1>

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

        <?php if ($orden_detalle): ?>
            <!-- Vista Detallada de Orden -->
            <div class="mb-6">
                <a href="mis-compras.php" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al historial
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-8 mb-6">
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Detalles de la Orden</h2>
                    <div class="grid md:grid-cols-4 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Número de Orden</p>
                            <p class="text-lg font-bold text-indigo-600"><?= htmlspecialchars($orden_detalle['numero_orden']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Fecha</p>
                            <p class="text-lg font-semibold text-gray-800">
                                <?= date('d/m/Y H:i', strtotime($orden_detalle['fecha_creacion'])) ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Estado</p>
                            <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full <?= getEstadoBadgeClass($orden_detalle['estado_orden']) ?>">
                                <i class="fas <?= getEstadoIcon($orden_detalle['estado_orden']) ?> mr-1"></i>
                                <?= ucfirst($orden_detalle['estado_orden']) ?>
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total</p>
                            <p class="text-2xl font-bold text-green-600"><?= format_price($orden_detalle['total']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Cursos de la Orden -->
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Cursos Adquiridos</h3>
                    <div class="space-y-4">
                        <?php foreach ($items_detalle as $item): ?>
                        <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-book text-indigo-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($item['titulo_curso']) ?></h4>
                                <p class="text-sm text-gray-500">Cantidad: <?= $item['cantidad'] ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-indigo-600"><?= format_price($item['subtotal']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Datos de Facturación -->
                <?php if (!empty($orden_detalle['nombre_facturacion'])): ?>
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Datos de Facturación</h3>
                    <div class="grid md:grid-cols-2 gap-6 text-sm">
                        <div>
                            <p class="text-gray-500">Nombre:</p>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($orden_detalle['nombre_facturacion']) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500">Email:</p>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($orden_detalle['email_facturacion']) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500">Teléfono:</p>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($orden_detalle['telefono_facturacion']) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500">Dirección:</p>
                            <p class="font-semibold text-gray-800">
                                <?= htmlspecialchars($orden_detalle['direccion_facturacion']) ?><br>
                                <?= htmlspecialchars($orden_detalle['ciudad_facturacion']) ?>, <?= htmlspecialchars($orden_detalle['estado_facturacion']) ?>
                                <?= htmlspecialchars($orden_detalle['codigo_postal_facturacion']) ?><br>
                                <?= htmlspecialchars($orden_detalle['pais_facturacion']) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Totales -->
                <div class="border-t border-gray-200 pt-6 mt-6">
                    <div class="max-w-md ml-auto space-y-2">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal:</span>
                            <span class="font-semibold"><?= format_price($orden_detalle['subtotal']) ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>IVA (0%):</span>
                            <span class="font-semibold">$0.00</span>
                        </div>
                        <div class="flex justify-between text-xl font-bold text-gray-800 border-t border-gray-200 pt-2">
                            <span>Total:</span>
                            <span class="text-green-600"><?= format_price($orden_detalle['total']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Lista de Órdenes -->
            <?php if (empty($ordenes)): ?>
                <div class="text-center py-16">
                    <i class="fas fa-inbox text-gray-300 text-8xl mb-4"></i>
                    <h2 class="text-2xl font-semibold text-gray-600 mb-4">No tienes compras aún</h2>
                    <p class="text-gray-500 mb-6">Explora nuestro catálogo y encuentra cursos increíbles</p>
                    <a href="cursos.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-8 py-3 rounded-lg transition">
                        <i class="fas fa-book mr-2"></i>Explorar Cursos
                    </a>
                </div>
            <?php else: ?>
                <!-- Estadísticas -->
                <div class="grid md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                                <i class="fas fa-shopping-bag text-indigo-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Total de Órdenes</p>
                                <p class="text-2xl font-bold text-gray-800"><?= count($ordenes) ?></p>
                            </div>
                        </div>
                    </div>

                    <?php
                    $total_gastado = array_sum(array_column($ordenes, 'total'));
                    $ordenes_completadas = count(array_filter($ordenes, fn($o) => $o['estado_pago'] === 'completado'));
                    ?>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Órdenes Completadas</p>
                                <p class="text-2xl font-bold text-gray-800"><?= $ordenes_completadas ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                                <i class="fas fa-dollar-sign text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Total Invertido</p>
                                <p class="text-2xl font-bold text-gray-800"><?= format_price($total_gastado) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Órdenes -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Número de Orden
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($ordenes as $orden): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-invoice text-indigo-600 mr-3"></i>
                                            <span class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($orden['numero_orden']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?= date('d/m/Y', strtotime($orden['fecha_creacion'])) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= date('H:i', strtotime($orden['fecha_creacion'])) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full <?= getEstadoBadgeClass($orden['estado_orden']) ?>">
                                            <i class="fas <?= getEstadoIcon($orden['estado_orden']) ?> mr-1"></i>
                                            <?= ucfirst($orden['estado_orden']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-bold text-gray-900"><?= format_price($orden['total']) ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="mis-compras.php?ver=<?= $orden['id_orden'] ?>"
                                           class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-eye mr-1"></i>Ver Detalles
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

</body>
</html>
