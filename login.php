<?php
/**
 * Página de Inicio de Sesión
 * Permite a los usuarios autenticarse en la plataforma
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

// Crear instancia del middleware
$middleware = new AuthMiddleware();

// Si ya está logueado, redirigir al dashboard
$middleware->redirigirSiEstaLogueado();

// Procesar formulario de login
$mensaje_error = '';
$mensaje_exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth_controller = new AuthController();
    $resultado = $auth_controller->login($_POST);

    if ($resultado['success']) {
        redirect($resultado['redirect']);
    } else {
        $mensaje_error = $resultado['message'];
    }
}

// Mostrar mensajes de sesión
if (isset($_SESSION['mensaje_error'])) {
    $mensaje_error = $_SESSION['mensaje_error'];
    unset($_SESSION['mensaje_error']);
}

if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Plataforma Educativa</title>

    <!-- Preconnect para mejorar velocidad -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://illustrations.popsy.co">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-6xl">
        <div class="grid md:grid-cols-2 gap-8 items-center">
            <!-- Columna Izquierda - Ilustración -->
            <div class="hidden md:block">
                <div class="text-center mb-8">
                    <h1 class="text-5xl font-bold text-white mb-4">
                        <i class="fas fa-graduation-cap"></i> Plataforma Educativa
                    </h1>
                    <p class="text-white/90 text-xl">Aprende, crece y alcanza tus metas</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-3xl p-8">
                    <img src="https://illustrations.popsy.co/amber/student-going-to-school.svg" alt="Estudiante" class="w-full h-auto" loading="lazy">
                </div>
            </div>

            <!-- Columna Derecha - Formulario -->
            <div class="w-full">
                <!-- Logo móvil -->
                <div class="text-center mb-8 md:hidden">
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <i class="fas fa-graduation-cap"></i> Plataforma Educativa
                    </h1>
                    <p class="text-white/80">Inicia sesión para continuar aprendiendo</p>
                </div>

                <!-- Tarjeta de Login -->
                <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Iniciar Sesión</h2>

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

            <!-- Formulario de Login -->
            <form method="POST" action="login.php">
                <!-- Email -->
                <div class="mb-4">
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
                        value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                    >
                </div>

                <!-- Contraseña -->
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-lock mr-2"></i>Contraseña
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition pr-12"
                            placeholder="••••••••"
                        >
                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                        >
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Recordarme / Olvidé contraseña -->
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                    </label>
                    <a href="recuperar.php" class="text-sm text-indigo-600 hover:text-indigo-800">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <!-- Botón de Submit -->
                <button
                    type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105 shadow-lg"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                </button>
            </form>

            <!-- Link a Registro -->
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    ¿No tienes cuenta?
                    <a href="registro.php" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                        Regístrate aquí
                    </a>
                </p>
            </div>
        </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-white/80 text-sm">
            <p>&copy; <?= date('Y') ?> Plataforma Educativa. Todos los derechos reservados.</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
