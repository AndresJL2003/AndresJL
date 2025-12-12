<?php
/**
 * Página de Confirmación de Orden
 * Muestra la confirmación después de un pago exitoso
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

// Proteger la ruta
$middleware = new AuthMiddleware();
$middleware->protegerRuta();

// Obtener usuario actual
$usuario = $middleware->obtenerUsuarioActual();

// Verificar que se recibió el session_id de Stripe
$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
    $_SESSION['mensaje_error'] = 'No se pudo verificar la sesión de pago.';
    redirect(base_url('carrito.php'));
    exit;
}

// Obtener información del pago
$pago_model = new Pago();
$pago = $pago_model->obtenerPagoPorSession($session_id);

if (!$pago) {
    $_SESSION['mensaje_error'] = 'No se encontró información del pago.';
    redirect(base_url('carrito.php'));
    exit;
}

// Verificar que el pago pertenece al usuario actual
if ($pago['id_usuario'] != $usuario['id_usuario']) {
    $_SESSION['mensaje_error'] = 'No tienes permiso para ver esta orden.';
    redirect(base_url('dashboard.php'));
    exit;
}

// Obtener información de la orden
$orden_model = new Orden();
$orden = $orden_model->obtenerOrden($pago['id_orden']);
$items_orden = $orden_model->obtenerItemsOrden($pago['id_orden']);

if (!$orden) {
    $_SESSION['mensaje_error'] = 'No se encontró información de la orden.';
    redirect(base_url('dashboard.php'));
    exit;
}

// ============================================================
// VERIFICACIÓN AUTOMÁTICA DEL PAGO EN STRIPE
// (Solución alternativa si el webhook no funciona)
// ============================================================
if ($pago['estado'] === 'pendiente') {
    // Cargar Stripe SDK
    require_once ROOT_PATH . '/vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    try {
        // Consultar el estado real de la sesión en Stripe
        $stripe_session = \Stripe\Checkout\Session::retrieve($session_id);

        // Si el pago fue exitoso en Stripe, actualizar nuestro sistema
        if ($stripe_session->payment_status === 'paid') {

            // Log para debugging
            error_log("Procesando pago completado manualmente para sesión: " . $session_id);

            // Actualizar estado del pago en la BD
            $pago_model->actualizarEstado($pago['id_pago'], 'completado', [
                'payment_intent_id' => $stripe_session->payment_intent,
                'customer_id' => $stripe_session->customer ?? null
            ]);

            // Actualizar estado de la orden
            $orden_model->actualizarEstado($pago['id_orden'], 'pagada', 'completado');

            // Procesar inscripciones a los cursos automáticamente
            $orden_model->procesarInscripciones($pago['id_orden']);

            // Vaciar el carrito del usuario
            $carrito_model = new Carrito();
            $carrito_model->vaciarCarrito($pago['id_usuario']);

            // Enviar email de confirmación
            try {
                $email_service = new EmailService();
                $nombre = explode(' ', $orden['nombre_facturacion'])[0];
                $email_enviado = $email_service->enviarConfirmacionCompra(
                    $orden['email_facturacion'],
                    $nombre,
                    $orden,
                    $items_orden
                );

                if ($email_enviado) {
                    error_log("Email de confirmación enviado exitosamente a: " . $orden['email_facturacion']);
                } else {
                    error_log("ERROR: No se pudo enviar el email de confirmación");
                }
            } catch (Exception $email_error) {
                error_log("Error al enviar email de confirmación: " . $email_error->getMessage());
            }

            // Recargar datos actualizados desde la BD
            $pago = $pago_model->obtenerPagoPorSession($session_id);
            $orden = $orden_model->obtenerOrden($pago['id_orden']);

            error_log("Pago procesado exitosamente. Estado: " . $pago['estado']);
        }

    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Error de Stripe al verificar pago: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Error general al verificar pago: " . $e->getMessage());
    }
}
// ============================================================
// FIN DE VERIFICACIÓN AUTOMÁTICA
// ============================================================

// Determinar el estado del pago para mostrar mensaje apropiado
$pago_exitoso = ($pago['estado'] === 'completado' && $orden['estado_pago'] === 'completado');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Orden - Plataforma Educativa</title>
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
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <?php if ($pago_exitoso): ?>
            <!-- Confirmación Exitosa -->
            <div class="text-center mb-8">
                <div class="inline-block p-4 bg-green-100 rounded-full mb-4">
                    <i class="fas fa-check-circle text-green-600 text-6xl"></i>
                </div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">¡Pago Exitoso!</h1>
                <p class="text-xl text-gray-600">Tu compra se ha procesado correctamente</p>
            </div>

            <!-- Información de la Orden -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-6">
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Detalles de tu Orden</h2>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Número de Orden</p>
                            <p class="text-lg font-bold text-indigo-600"><?= htmlspecialchars($orden['numero_orden']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Fecha</p>
                            <p class="text-lg font-semibold text-gray-800">
                                <?= date('d/m/Y H:i', strtotime($orden['fecha_creacion'])) ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Pagado</p>
                            <p class="text-2xl font-bold text-green-600"><?= format_price($orden['total']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Cursos Adquiridos -->
                <div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-graduation-cap mr-2 text-indigo-600"></i>Cursos Adquiridos
                    </h3>
                    <div class="space-y-4">
                        <?php foreach ($items_orden as $item): ?>
                        <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-book text-indigo-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($item['titulo_curso']) ?></h4>
                                <p class="text-sm text-gray-500">Acceso completo al curso</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-indigo-600"><?= format_price($item['precio_unitario']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 text-2xl mr-4 mt-1"></i>
                    <div>
                        <h3 class="font-bold text-blue-900 mb-2">¿Qué sigue?</h3>
                        <ul class="text-blue-800 space-y-1">
                            <li><i class="fas fa-check mr-2"></i>Ya estás inscrito en los cursos</li>
                            <li><i class="fas fa-check mr-2"></i>Recibirás un email de confirmación</li>
                            <li><i class="fas fa-check mr-2"></i>Puedes comenzar a estudiar inmediatamente</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="grid md:grid-cols-2 gap-4">
                <a href="dashboard.php"
                   class="block text-center bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-lg transition">
                    <i class="fas fa-play-circle mr-2"></i>Ir a Mis Cursos
                </a>
                <a href="mis-compras.php"
                   class="block text-center bg-gray-600 hover:bg-gray-700 text-white font-bold py-4 rounded-lg transition">
                    <i class="fas fa-history mr-2"></i>Ver Historial de Compras
                </a>
            </div>

        <?php else: ?>
            <!-- Pago Pendiente o Fallido -->
            <div class="text-center mb-8">
                <div class="inline-block p-4 bg-yellow-100 rounded-full mb-4">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-6xl"></i>
                </div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">Pago Pendiente</h1>
                <p class="text-xl text-gray-600">Tu pago está siendo procesado</p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-8 mb-6">
                <div class="text-center">
                    <p class="text-gray-700 mb-4">
                        Hemos recibido tu orden <strong class="text-indigo-600"><?= htmlspecialchars($orden['numero_orden']) ?></strong>
                        y está siendo procesada.
                    </p>
                    <p class="text-gray-600 mb-6">
                        Recibirás una confirmación por email una vez que el pago sea completado.
                        Esto puede tomar unos minutos.
                    </p>

                    <div class="flex items-center justify-center space-x-2 text-gray-500">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Procesando pago...</span>
                    </div>
                </div>
            </div>

            <div class="text-center space-x-4">
                <a href="dashboard.php"
                   class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-8 py-3 rounded-lg transition">
                    <i class="fas fa-home mr-2"></i>Ir al Dashboard
                </a>
                <button onclick="location.reload()"
                        class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-bold px-8 py-3 rounded-lg transition">
                    <i class="fas fa-sync mr-2"></i>Actualizar Estado
                </button>
            </div>
        <?php endif; ?>

        <!-- Soporte -->
        <div class="mt-8 text-center">
            <p class="text-gray-500 text-sm">
                ¿Tienes algún problema?
                <a href="mailto:soporte@plataforma.com" class="text-indigo-600 hover:underline">Contacta a soporte</a>
            </p>
        </div>

    </div>

    <!-- Script para confetti (efecto de celebración) -->
    <script>
        <?php if ($pago_exitoso): ?>
        // Mostrar animación de celebración si el pago fue exitoso
        document.addEventListener('DOMContentLoaded', function() {
            // Aquí podrías agregar una biblioteca de confetti como canvas-confetti
            console.log('¡Pago exitoso! 🎉');
        });
        <?php endif; ?>
    </script>

</body>
</html>
