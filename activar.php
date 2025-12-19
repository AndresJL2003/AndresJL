<?php
/**
 * Página de Activación de Cuenta
 * Activa la cuenta del usuario mediante token enviado por correo
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

$mensaje_error = '';
$mensaje_exito = '';
$token = $_GET['token'] ?? '';

if (!empty($token)) {
    $auth_controller = new AuthController();
    $resultado = $auth_controller->activarCuenta($token);

    if ($resultado['success']) {
        $mensaje_exito = $resultado['message'];
    } else {
        $mensaje_error = $resultado['message'];
    }
} else {
    $mensaje_error = 'Token de activación no proporcionado';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activar Cuenta - Plataforma Educativa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo / Título -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">
                <i class="fas fa-graduation-cap"></i> Plataforma Educativa
            </h1>
        </div>

        <!-- Tarjeta de Activación -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 text-center">
            <?php if ($mensaje_exito): ?>
                <div class="mb-6">
                    <div class="bg-green-100 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-4">
                        <i class="fas fa-check-circle text-green-600 text-5xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">¡Cuenta Activada!</h2>
                    <p class="text-gray-600 mb-6"><?= htmlspecialchars($mensaje_exito) ?></p>
                    <a href="login.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                    </a>
                </div>
            <?php else: ?>
                <div class="mb-6">
                    <div class="bg-red-100 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-4">
                        <i class="fas fa-times-circle text-red-600 text-5xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Error de Activación</h2>
                    <p class="text-gray-600 mb-6"><?= htmlspecialchars($mensaje_error) ?></p>
                    <div class="space-y-3">
                        <a href="login.php" class="block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition">
                            Ir al Login
                        </a>
                        <a href="registro.php" class="block bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-lg transition">
                            Registrarse de Nuevo
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-white/80 text-sm">
            <p>&copy; <?= date('Y') ?> Plataforma Educativa. Todos los derechos reservados.</p>
        </div>
    </div>

</body>
</html>
