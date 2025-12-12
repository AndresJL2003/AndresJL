<?php
/**
 * Panel de Administración - Dashboard Principal
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
$usuario_model = new Usuario();
$curso_model = new Curso();
$orden_model = new Orden();
$pago_model = new Pago();

// Obtener estadísticas
$total_usuarios = count($usuario_model->obtenerTodos());
$total_cursos = count($curso_model->obtenerTodos());
$total_ordenes = $orden_model->contarTodas();
$estadisticas_pagos = $pago_model->obtenerEstadisticas();

// Usuarios recientes
$usuarios_recientes = array_slice($usuario_model->obtenerTodos(), -5);
$usuarios_recientes = array_reverse($usuarios_recientes);

// Órdenes recientes
$ordenes_recientes = $orden_model->obtenerRecientes(5);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
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
                    <a href="dashboard.php" class="text-white bg-white/20 px-3 py-2 rounded-md">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <a href="usuarios.php" class="text-white hover:bg-white/20 px-3 py-2 rounded-md transition">
                        <i class="fas fa-users mr-2"></i>Usuarios
                    </a>
                    <a href="cursos.php" class="text-white hover:bg-white/20 px-3 py-2 rounded-md transition">
                        <i class="fas fa-book mr-2"></i>Cursos
                    </a>
                    <a href="ordenes.php" class="text-white hover:bg-white/20 px-3 py-2 rounded-md transition">
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
                <i class="fas fa-tachometer-alt text-purple-600 mr-3"></i>Panel de Administración
            </h1>
            <p class="text-gray-600 mt-2">Bienvenido, <?= htmlspecialchars($usuario_actual['nombre']) ?></p>
        </div>

        <!-- Estadísticas Principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Usuarios -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Usuarios</p>
                        <p class="text-3xl font-bold text-purple-600 mt-2"><?= $total_usuarios ?></p>
                        <a href="usuarios.php" class="text-purple-600 text-sm hover:underline mt-2 inline-block">
                            Ver todos <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-users text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Cursos -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Cursos</p>
                        <p class="text-3xl font-bold text-indigo-600 mt-2"><?= $total_cursos ?></p>
                        <a href="cursos.php" class="text-indigo-600 text-sm hover:underline mt-2 inline-block">
                            Ver todos <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <i class="fas fa-book text-indigo-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Órdenes -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Órdenes</p>
                        <p class="text-3xl font-bold text-green-600 mt-2"><?= $total_ordenes ?></p>
                        <a href="ordenes.php" class="text-green-600 text-sm hover:underline mt-2 inline-block">
                            Ver todas <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-shopping-cart text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Ingresos Totales -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Ingresos Totales</p>
                        <p class="text-3xl font-bold text-orange-600 mt-2">
                            <?= format_price($estadisticas_pagos['total_completado'] ?? 0) ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-2">
                            <?= $estadisticas_pagos['total_pagos'] ?? 0 ?> pagos
                        </p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-dollar-sign text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de 2 columnas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- Usuarios Recientes -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-user-plus text-purple-600 mr-2"></i>Usuarios Recientes
                    </h2>
                    <a href="usuarios.php" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                        Ver todos <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="space-y-4">
                    <?php foreach ($usuarios_recientes as $usuario): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="h-10 w-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <span class="text-purple-600 font-bold">
                                    <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                                </span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>
                                </p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($usuario['email']) ?></p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500">
                            <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Órdenes Recientes -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-receipt text-green-600 mr-2"></i>Órdenes Recientes
                    </h2>
                    <a href="ordenes.php" class="text-green-600 hover:text-green-800 text-sm font-medium">
                        Ver todas <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="space-y-4">
                    <?php foreach ($ordenes_recientes as $orden): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($orden['numero_orden']) ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?= htmlspecialchars($orden['nombre_facturacion']) ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-green-600">
                                <?= format_price($orden['total']) ?>
                            </p>
                            <?php if ($orden['estado_pago'] === 'completado'): ?>
                            <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">
                                Pagado
                            </span>
                            <?php else: ?>
                            <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                                Pendiente
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- Acciones Rápidas -->
        <div class="mt-8 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-white mb-6">Acciones Rápidas</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="usuarios.php" class="bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white rounded-lg p-4 transition">
                    <i class="fas fa-user-plus text-3xl mb-2"></i>
                    <p class="font-semibold">Gestionar Usuarios</p>
                </a>
                <a href="cursos.php" class="bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white rounded-lg p-4 transition">
                    <i class="fas fa-book text-3xl mb-2"></i>
                    <p class="font-semibold">Gestionar Cursos</p>
                </a>
                <a href="ordenes.php" class="bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white rounded-lg p-4 transition">
                    <i class="fas fa-chart-line text-3xl mb-2"></i>
                    <p class="font-semibold">Ver Reportes</p>
                </a>
            </div>
        </div>

    </div>

</body>
</html>
