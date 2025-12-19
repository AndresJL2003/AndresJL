<?php
/**
 * Página de Checkout
 * Formulario de pago y datos de facturación
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

// Verificar que el carrito no esté vacío
if (empty($items_carrito)) {
    $_SESSION['mensaje_error'] = 'Tu carrito está vacío. Agrega cursos para continuar.';
    redirect(base_url('cursos.php'));
    exit;
}

// Procesar formulario de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_pago'])) {
    // Validar datos requeridos
    $errores = [];

    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $codigo_postal = trim($_POST['codigo_postal'] ?? '');
    $pais = trim($_POST['pais'] ?? 'Bolivia');
    $notas = trim($_POST['notas'] ?? '');

    if (empty($nombre_completo)) $errores[] = 'El nombre completo es requerido';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email válido es requerido';
    if (empty($telefono)) $errores[] = 'El teléfono es requerido';
    if (empty($direccion)) $errores[] = 'La dirección es requerida';
    if (empty($ciudad)) $errores[] = 'La ciudad es requerida';
    if (empty($estado)) $errores[] = 'El estado es requerido';
    if (empty($codigo_postal)) $errores[] = 'El código postal es requerido';

    if (empty($errores)) {
        // Preparar datos de facturación
        $datos_facturacion = [
            'nombre_completo' => $nombre_completo,
            'email' => $email,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'ciudad' => $ciudad,
            'estado' => $estado,
            'codigo_postal' => $codigo_postal,
            'pais' => $pais,
            'notas' => $notas
        ];

        try {
            // Crear sesión de checkout en Stripe
            $pago_controller = new PagoController();
            $resultado = $pago_controller->crearCheckoutSession($datos_facturacion);

            if ($resultado['success']) {
                // Redirigir a Stripe Checkout
                header('Location: ' . $resultado['checkout_url']);
                exit;
            } else {
                $_SESSION['mensaje_error'] = $resultado['message'] ?? 'Error al procesar el pago';
            }
        } catch (Exception $e) {
            $_SESSION['mensaje_error'] = 'Error al procesar el pago: ' . $e->getMessage();
            error_log('Error en checkout: ' . $e->getMessage());
        }
    } else {
        $_SESSION['mensaje_error'] = implode('<br>', $errores);
    }
}

// Verificar si se canceló el pago
if (isset($_GET['canceled']) && $_GET['canceled'] == '1') {
    $_SESSION['mensaje_error'] = 'Pago cancelado. Puedes intentar nuevamente cuando estés listo.';
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
    <title>Checkout - Plataforma Educativa</title>
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
                    <a href="logout.php" class="text-red-600 hover:text-red-800 px-3 py-2 rounded-md transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Progreso del checkout -->
        <div class="mb-8">
            <div class="flex items-center justify-center space-x-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center font-bold">
                        <i class="fas fa-check"></i>
                    </div>
                    <span class="ml-2 text-gray-700 font-medium">Carrito</span>
                </div>
                <div class="w-16 h-1 bg-indigo-600"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">
                        2
                    </div>
                    <span class="ml-2 text-indigo-600 font-medium">Pago</span>
                </div>
                <div class="w-16 h-1 bg-gray-300"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center font-bold">
                        3
                    </div>
                    <span class="ml-2 text-gray-500 font-medium">Confirmación</span>
                </div>
            </div>
        </div>

        <h1 class="text-3xl font-bold mb-8 text-gray-800">
            <i class="fas fa-credit-card mr-3 text-indigo-600"></i>Finalizar Compra
        </h1>

        <!-- Mensajes -->
        <?php if ($mensaje_error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <p class="text-red-700"><?= $mensaje_error ?></p>
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

        <!-- Grid: Formulario + Resumen -->
        <form method="POST" action="checkout.php" class="grid lg:grid-cols-3 gap-8">

            <!-- Columna izquierda: Formulario de facturación (2/3) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Información Personal -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">
                        <i class="fas fa-user mr-2 text-indigo-600"></i>Información Personal
                    </h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 font-medium mb-2">
                                Nombre Completo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nombre_completo"
                                   value="<?= htmlspecialchars($_POST['nombre_completo'] ?? $usuario['nombre'] ?? '') ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   required>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email"
                                   value="<?= htmlspecialchars($_POST['email'] ?? $usuario['email'] ?? '') ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   required>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                Teléfono <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" name="telefono"
                                   value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
                                   placeholder="Ej: +591 71234567"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   required>
                        </div>
                    </div>
                </div>

                <!-- Dirección de Facturación -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">
                        <i class="fas fa-map-marker-alt mr-2 text-indigo-600"></i>Dirección de Facturación
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                Dirección <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="direccion"
                                   value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>"
                                   placeholder="Calle, número exterior, número interior"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   required>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    Ciudad <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="ciudad"
                                       value="<?= htmlspecialchars($_POST['ciudad'] ?? '') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                       required>
                            </div>

                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    Estado <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="estado"
                                       value="<?= htmlspecialchars($_POST['estado'] ?? '') ?>"
                                       placeholder="Ej: La Paz"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                       required>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    Código Postal <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="codigo_postal"
                                       value="<?= htmlspecialchars($_POST['codigo_postal'] ?? '') ?>"
                                       placeholder="Ej: 1234"
                                       maxlength="10"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                       required>
                            </div>

                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    País <span class="text-red-500">*</span>
                                </label>
                                <select name="pais"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        required>
                                    <option value="Bolivia" selected>Bolivia</option>
                                    <option value="Argentina">Argentina</option>
                                    <option value="Chile">Chile</option>
                                    <option value="Colombia">Colombia</option>
                                    <option value="España">España</option>
                                    <option value="Estados Unidos">Estados Unidos</option>
                                    <option value="México">México</option>
                                    <option value="Perú">Perú</option>
                                    <option value="Venezuela">Venezuela</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notas Adicionales -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">
                        <i class="fas fa-sticky-note mr-2 text-indigo-600"></i>Notas Adicionales
                    </h2>

                    <div>
                        <label class="block text-gray-700 font-medium mb-2">
                            Comentarios o instrucciones especiales (opcional)
                        </label>
                        <textarea name="notas" rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                  placeholder="¿Alguna información adicional que debamos saber?"><?= htmlspecialchars($_POST['notas'] ?? '') ?></textarea>
                    </div>
                </div>

            </div>

            <!-- Columna derecha: Resumen de orden (1/3) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Resumen de Orden</h2>

                    <!-- Lista de cursos -->
                    <div class="space-y-3 mb-6 max-h-64 overflow-y-auto">
                        <?php foreach ($items_carrito as $item): ?>
                        <div class="flex items-start space-x-3 pb-3 border-b border-gray-200">
                            <img src="<?= htmlspecialchars($item['imagen_url']) ?>"
                                 alt="<?= htmlspecialchars($item['titulo']) ?>"
                                 class="w-16 h-16 object-cover rounded">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">
                                    <?= htmlspecialchars($item['titulo']) ?>
                                </p>
                                <p class="text-sm text-indigo-600 font-bold">
                                    Bs. <?= number_format($item['precio_agregado'], 2, '.', ',') ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Totales -->
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal (<?= count($items_carrito) ?> <?= count($items_carrito) === 1 ? 'curso' : 'cursos' ?>)</span>
                            <span class="font-semibold">Bs. <?= number_format($total, 2, '.', ',') ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>IVA (0%)</span>
                            <span class="font-semibold">Bs. 0.00</span>
                        </div>
                        <hr class="my-4">
                        <div class="flex justify-between text-xl font-bold text-gray-800">
                            <span>Total</span>
                            <span class="text-indigo-600">Bs. <?= number_format($total, 2, '.', ',') ?></span>
                        </div>
                    </div>

                    <!-- Botón de pago -->
                    <button type="submit" name="procesar_pago"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition mb-3">
                        <i class="fas fa-lock mr-2"></i>Proceder al Pago Seguro
                    </button>

                    <a href="carrito.php"
                       class="block w-full text-center text-gray-600 hover:text-indigo-600 font-medium py-2">
                        <i class="fas fa-arrow-left mr-2"></i>Volver al Carrito
                    </a>

                    <!-- Seguridad -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-center space-x-3 text-xs text-gray-500">
                            <i class="fas fa-shield-alt text-green-600 text-lg"></i>
                            <div>
                                <p class="font-semibold">Pago 100% seguro</p>
                                <p>Cifrado SSL y procesado por Stripe</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-center space-x-2 mt-3">
                            <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
                            <i class="fab fa-cc-mastercard text-2xl text-red-600"></i>
                            <i class="fab fa-cc-amex text-2xl text-blue-500"></i>
                        </div>
                    </div>
                </div>
            </div>

        </form>

    </div>

</body>
</html>