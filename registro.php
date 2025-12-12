<?php
/**
 * Página de Registro
 * Permite a nuevos usuarios crear una cuenta
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

// Crear instancia del middleware
$middleware = new AuthMiddleware();

// Si ya está logueado, redirigir al dashboard
$middleware->redirigirSiEstaLogueado();

// Obtener planes disponibles
$plan_model = new Plan();
$planes = $plan_model->obtenerTodos();

// Procesar formulario de registro
$mensaje_error = '';
$mensaje_exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth_controller = new AuthController();
    $resultado = $auth_controller->registrar($_POST);

    if ($resultado['success']) {
        $mensaje_exito = $resultado['message'];
        // Limpiar el formulario
        $_POST = [];
    } else {
        $mensaje_error = $resultado['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Plataforma Educativa</title>

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
            <div class="hidden md:block order-2 md:order-1">
                <div class="bg-white/10 backdrop-blur-sm rounded-3xl p-8 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&h=500&fit=crop" alt="Estudiantes aprendiendo" class="w-full h-auto rounded-2xl shadow-2xl" loading="lazy">
                    <div class="mt-6 text-center">
                        <h3 class="text-2xl font-bold text-white mb-2">Únete a nosotros</h3>
                        <p class="text-white/90 text-lg">Miles de estudiantes ya están aprendiendo</p>
                        <div class="flex items-center justify-center gap-6 mt-4">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-white">10K+</p>
                                <p class="text-white/80 text-sm">Estudiantes</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-white">50+</p>
                                <p class="text-white/80 text-sm">Cursos</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-white">4.8★</p>
                                <p class="text-white/80 text-sm">Rating</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha - Formulario -->
            <div class="w-full order-1 md:order-2">
                <!-- Logo móvil -->
                <div class="text-center mb-8 md:hidden">
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <i class="fas fa-graduation-cap"></i> Plataforma Educativa
                    </h1>
                    <p class="text-white/80">Crea tu cuenta y comienza a aprender hoy</p>
                </div>

                <!-- Tarjeta de Registro -->
                <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Crear Cuenta</h2>

            <!-- Mensajes -->
            <?php if ($mensaje_error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <div class="text-red-700"><?= $mensaje_error ?></div>
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

            <!-- Formulario de Registro -->
            <form method="POST" action="registro.php" class="space-y-4">
                <!-- Nombre y Apellido (en una fila) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="nombre" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Nombre
                        </label>
                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                            placeholder="Juan"
                            value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>"
                        >
                    </div>

                    <div>
                        <label for="apellido" class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Apellido
                        </label>
                        <input
                            type="text"
                            id="apellido"
                            name="apellido"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                            placeholder="Pérez"
                            value="<?= isset($_POST['apellido']) ? htmlspecialchars($_POST['apellido']) : '' ?>"
                        >
                    </div>
                </div>

                <!-- Email -->
                <div>
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
                <div>
                    <label for="password" class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-lock mr-2"></i>Contraseña
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

                <!-- Confirmar Contraseña -->
                <div>
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

                <!-- Selección de Plan -->
                <div>
                    <label class="block text-gray-700 font-medium mb-3">
                        <i class="fas fa-crown mr-2"></i>Selecciona tu Plan
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php foreach ($planes as $plan): ?>
                        <label class="relative cursor-pointer">
                            <input
                                type="radio"
                                name="id_plan"
                                value="<?= $plan['id_plan'] ?>"
                                <?= $plan['nombre_plan'] === 'Basico' ? 'checked' : '' ?>
                                class="peer sr-only"
                            >
                            <div class="border-2 border-gray-300 rounded-lg p-4 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 hover:border-indigo-400 transition">
                                <div class="text-center">
                                    <h3 class="font-bold text-lg mb-2"><?= htmlspecialchars($plan['nombre_plan']) ?></h3>
                                    <p class="text-2xl font-bold text-indigo-600 mb-2">
                                        <?= $plan['precio'] > 0 ? '$' . number_format($plan['precio'], 2) . '/mes' : 'Gratis' ?>
                                    </p>
                                    <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($plan['descripcion']) ?></p>
                                    <p class="text-xs text-gray-500">
                                        <i class="fas fa-desktop mr-1"></i><?= $plan['sesiones_maximas'] ?> sesión<?= $plan['sesiones_maximas'] > 1 ? 'es' : '' ?>
                                    </p>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Términos y Condiciones -->
                <div class="flex items-start">
                    <input
                        type="checkbox"
                        id="terminos"
                        name="terminos"
                        required
                        class="w-4 h-4 mt-1 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    >
                    <label for="terminos" class="ml-2 text-sm text-gray-600">
                        Acepto los <a href="#" class="text-indigo-600 hover:underline">términos y condiciones</a>
                        y la <a href="#" class="text-indigo-600 hover:underline">política de privacidad</a>
                    </label>
                </div>

                <!-- Botón de Submit -->
                <button
                    type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105 shadow-lg"
                >
                    <i class="fas fa-user-plus mr-2"></i>Crear Cuenta
                </button>
            </form>

            <!-- Link a Login -->
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    ¿Ya tienes cuenta?
                    <a href="login.php" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                        Inicia sesión aquí
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

</body>
</html>
