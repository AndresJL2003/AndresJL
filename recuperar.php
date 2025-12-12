<?php
/**
 * Página de Recuperación de Contraseña
 * Permite solicitar y restablecer contraseña mediante token
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

$auth_controller = new AuthController();
$mensaje_error = '';
$mensaje_exito = '';
$token = $_GET['token'] ?? '';
$modo = !empty($token) ? 'restablecer' : 'solicitar';

// Procesar formulario de solicitud de recuperación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $modo === 'solicitar') {
    $resultado = $auth_controller->solicitarRecuperacion($_POST['email'] ?? '');

    if ($resultado['success']) {
        $mensaje_exito = $resultado['message'];
    } else {
        $mensaje_error = $resultado['message'];
    }
}

// Procesar formulario de restablecimiento de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $modo === 'restablecer') {
    $resultado = $auth_controller->cambiarPasswordConToken([
        'token' => $token,
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? ''
    ]);

    if ($resultado['success']) {
        $mensaje_exito = $resultado['message'];
        $token = ''; // Limpiar token para mostrar mensaje de éxito
    } else {
        $mensaje_error = $resultado['message'];
    }
}

// Nota: La verificación del token se hace cuando se envía el formulario
// en cambiarPasswordConToken(), no es necesario verificar antes
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Plataforma Educativa</title>
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
            <p class="text-white/80">
                <?= $modo === 'solicitar' ? 'Recupera tu contraseña' : 'Establece una nueva contraseña' ?>
            </p>
        </div>

        <!-- Tarjeta de Recuperación -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <?php if ($mensaje_exito && empty($token)): ?>
                <!-- Mensaje de Éxito -->
                <div class="text-center">
                    <div class="bg-green-100 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-4">
                        <i class="fas fa-check-circle text-green-600 text-5xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">¡Listo!</h2>
                    <p class="text-gray-600 mb-6"><?= htmlspecialchars($mensaje_exito) ?></p>
                    <a href="login.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>Ir al Login
                    </a>
                </div>
            <?php elseif ($modo === 'solicitar'): ?>
                <!-- Formulario de Solicitud -->
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Recuperar Contraseña</h2>

                <!-- Mensajes -->
                <?php if ($mensaje_error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <p class="text-red-700"><?= htmlspecialchars($mensaje_error) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <p class="text-gray-600 mb-6 text-center">
                    Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
                </p>

                <form method="POST" action="recuperar.php">
                    <div class="mb-6">
                        <label for="email" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-envelope mr-2"></i>Correo Electrónico
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                            placeholder="tu@email.com"
                        >
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105 shadow-lg"
                    >
                        <i class="fas fa-paper-plane mr-2"></i>Enviar Enlace de Recuperación
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="login.php" class="text-indigo-600 hover:text-indigo-800 text-sm">
                        <i class="fas fa-arrow-left mr-1"></i>Volver al Login
                    </a>
                </div>

            <?php else: ?>
                <!-- Formulario de Restablecimiento -->
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Nueva Contraseña</h2>

                <!-- Mensajes -->
                <?php if ($mensaje_error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <p class="text-red-700"><?= htmlspecialchars($mensaje_error) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" action="recuperar.php?token=<?= htmlspecialchars($token) ?>">
                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Nueva Contraseña
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            minlength="8"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                            placeholder="Mínimo 8 caracteres"
                        >
                        <p class="text-sm text-gray-500 mt-1">Debe contener al menos 8 caracteres, una letra y un número</p>
                    </div>

                    <div class="mb-6">
                        <label for="password_confirm" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Confirmar Contraseña
                        </label>
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            required
                            minlength="8"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                            placeholder="Repite tu contraseña"
                        >
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105 shadow-lg"
                    >
                        <i class="fas fa-key mr-2"></i>Restablecer Contraseña
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-white/80 text-sm">
            <p>&copy; <?= date('Y') ?> Plataforma Educativa. Todos los derechos reservados.</p>
        </div>
    </div>

</body>
</html>
