<?php
/**
 * Página del Carrito de Compras
 * Muestra los cursos agregados al carrito y permite proceder al checkout
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

// Proteger la ruta
$middleware = new AuthMiddleware();
$middleware->protegerRuta();

// Obtener usuario actual
$usuario = $middleware->obtenerUsuarioActual();

// Obtener items del carrito
$carrito_model = new Carrito();
$items_carrito = $carrito_model->obtenerItemsDetallados($usuario['id_usuario']);
$total = $carrito_model->obtenerTotal($usuario['id_usuario']);

// Procesar eliminación de items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    $id_curso = (int)$_POST['id_curso'];
    if ($carrito_model->eliminarItem($usuario['id_usuario'], $id_curso)) {
        $_SESSION['mensaje_exito'] = 'Curso eliminado del carrito';
        redirect(base_url('carrito.php'));
    } else {
        $_SESSION['mensaje_error'] = 'Error al eliminar el curso';
    }
}

// Mensajes
$mensaje_error = $_SESSION['mensaje_error'] ?? '';
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
unset($_SESSION['mensaje_error'], $_SESSION['mensaje_exito']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito - Plataforma Educativa</title>
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
                    <a href="carrito.php" class="relative text-indigo-600 px-3 py-2 rounded-md font-semibold">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
                            <?= count($items_carrito) ?>
                        </span>
                    </a>
                    <a href="dashboard.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md transition">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="cursos.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md transition">
                        <i class="fas fa-book mr-2"></i>Cursos
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
            <i class="fas fa-shopping-cart mr-3 text-indigo-600"></i>Mi Carrito de Compras
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

        <?php if (empty($items_carrito)): ?>
            <!-- Carrito vacío -->
            <div class="text-center py-16">
                <i class="fas fa-shopping-cart text-gray-300 text-8xl mb-4"></i>
                <h2 class="text-2xl font-semibold text-gray-600 mb-4">Tu carrito está vacío</h2>
                <p class="text-gray-500 mb-6">Explora nuestro catálogo y agrega cursos a tu carrito</p>
                <a href="cursos.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-8 py-3 rounded-lg transition">
                    <i class="fas fa-book mr-2"></i>Explorar Cursos
                </a>
            </div>
        <?php else: ?>
            <!-- Grid: Items + Resumen -->
            <div class="grid lg:grid-cols-3 gap-8">

                <!-- Columna izquierda: Lista de items (2/3) -->
                <div class="lg:col-span-2 space-y-4">
                    <?php foreach ($items_carrito as $item): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 flex flex-col sm:flex-row gap-4">
                        <img src="<?= htmlspecialchars($item['imagen_url']) ?>"
                             alt="<?= htmlspecialchars($item['titulo']) ?>"
                             class="w-full sm:w-32 h-32 object-cover rounded-lg">

                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($item['titulo']) ?></h3>
                            <p class="text-gray-600 text-sm mb-2">
                                <i class="fas fa-user-tie mr-1"></i>
                                <?= htmlspecialchars($item['instructor']) ?>
                            </p>
                            <p class="text-gray-600 text-sm mb-2">
                                <i class="fas fa-clock mr-1"></i>
                                <?= $item['duracion_horas'] ?> horas
                            </p>
                            <span class="inline-block text-xs font-semibold text-indigo-600 bg-indigo-100 px-3 py-1 rounded-full">
                                <?= htmlspecialchars($item['nivel']) ?>
                            </span>
                        </div>

                        <div class="text-right flex flex-col justify-between">
                            <p class="text-2xl font-bold text-indigo-600 mb-4">
                                <?= format_price($item['precio_agregado']) ?>
                            </p>
                            <form method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este curso?');">
                                <input type="hidden" name="id_curso" value="<?= $item['id_curso'] ?>">
                                <button type="submit" name="eliminar"
                                        class="text-red-600 hover:text-red-800 text-sm font-medium">
                                    <i class="fas fa-trash mr-1"></i>Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Columna derecha: Resumen de orden (1/3) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                        <h2 class="text-xl font-bold mb-4 text-gray-800">Resumen de Orden</h2>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal (<?= count($items_carrito) ?> <?= count($items_carrito) === 1 ? 'curso' : 'cursos' ?>)</span>
                                <span class="font-semibold"><?= format_price($total) ?></span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>IVA (0%)</span>
                                <span class="font-semibold">$0.00</span>
                            </div>
                            <hr class="my-4">
                            <div class="flex justify-between text-xl font-bold text-gray-800">
                                <span>Total</span>
                                <span class="text-indigo-600"><?= format_price($total) ?></span>
                            </div>
                        </div>

                        <a href="checkout.php"
                           class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition mb-3">
                            Proceder al Pago
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>

                        <a href="cursos.php"
                           class="block w-full text-center text-gray-600 hover:text-indigo-600 font-medium py-2">
                            <i class="fas fa-arrow-left mr-2"></i>Seguir Comprando
                        </a>

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <p class="text-xs text-gray-500 text-center">
                                <i class="fas fa-shield-alt mr-1"></i>
                                Pago seguro con cifrado SSL
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>

</body>
</html>
