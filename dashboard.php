<?php
/**
 * Dashboard Principal
 * Página principal después del inicio de sesión
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

// Crear instancia del middleware y proteger la ruta
$middleware = new AuthMiddleware();
$middleware->protegerRuta();

// Obtener información del usuario actual
$usuario = $middleware->obtenerUsuarioActual();

// Obtener información de sesiones
$info_sesiones = $middleware->obtenerInfoSesiones();

// Obtener cursos del usuario
$curso_model = new Curso();
$mis_cursos = $curso_model->obtenerCursosUsuario($usuario['id_usuario']);
$estadisticas = $curso_model->obtenerEstadisticasUsuario($usuario['id_usuario']);

// Obtener cursos disponibles (no inscritos)
$todos_cursos = $curso_model->obtenerTodos();
$cursos_disponibles = array_filter($todos_cursos, function($curso) use ($curso_model, $usuario) {
    return !$curso_model->estaInscrito($usuario['id_usuario'], $curso['id_curso']);
});
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Plataforma Educativa</title>

    <!-- Preconnect para mejorar velocidad -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://illustrations.popsy.co">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-indigo-600">
                        <i class="fas fa-graduation-cap mr-2"></i>Plataforma Educativa
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="cursos.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md transition">
                        <i class="fas fa-book mr-2"></i>Cursos
                    </a>
                    <div class="relative">
                        <button id="userMenuButton" class="flex items-center text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md transition">
                            <i class="fas fa-user-circle mr-2"></i>
                            <?= htmlspecialchars($usuario['nombre']) ?>
                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                        </button>
                        <div id="userMenuDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200">
                            <div class="px-4 py-3 text-sm text-gray-700 border-b border-gray-200 bg-gray-50">
                                <div class="font-semibold"><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?></div>
                                <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($usuario['email']) ?></div>
                                <div class="text-xs text-indigo-600 font-semibold mt-1">
                                    <i class="fas fa-crown mr-1"></i>Plan <?= htmlspecialchars($usuario['nombre_plan']) ?>
                                </div>
                            </div>
                            <a href="admin/perfil.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                                <i class="fas fa-user mr-2"></i>Mi Perfil
                            </a>
                            <a href="admin/sesiones.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                                <i class="fas fa-desktop mr-2"></i>Mis Sesiones
                            </a>
                            <?php if ($middleware->esAdmin()): ?>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="admin/usuarios.php" class="block px-4 py-2 text-sm text-purple-600 hover:bg-purple-50 font-semibold transition">
                                <i class="fas fa-users-cog mr-2"></i>Panel de Admin
                            </a>
                            <?php endif; ?>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100 transition">
                                <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Banner de Bienvenida -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl shadow-lg p-8 mb-8 relative overflow-hidden">
            <div class="grid md:grid-cols-2 gap-8 items-center relative z-10">
                <div class="text-white">
                    <h2 class="text-4xl font-bold mb-3">
                        ¡Hola, <?= htmlspecialchars($usuario['nombre']) ?>! 👋
                    </h2>
                    <p class="text-white/90 text-lg mb-4">Continúa tu aprendizaje donde lo dejaste</p>
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                            <span class="text-sm">Plan:</span>
                            <span class="font-bold ml-2"><?= htmlspecialchars($usuario['nombre_plan']) ?></span>
                        </div>
                        <?php if ($middleware->esAdmin()): ?>
                        <div class="bg-yellow-500/30 backdrop-blur-sm rounded-lg px-4 py-2 border border-yellow-400">
                            <i class="fas fa-crown mr-1"></i>
                            <span class="font-bold text-sm">Administrador</span>
                        </div>
                        <?php endif; ?>
                        <a href="cursos.php" class="bg-white text-indigo-600 px-6 py-2 rounded-lg font-semibold hover:bg-gray-100 transition">
                            Explorar Cursos <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="relative">
                        <img src="https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=500&h=400&fit=crop" alt="Estudiante aprendiendo" class="w-full h-auto rounded-2xl shadow-2xl transform hover:scale-105 transition duration-300" loading="lazy">
                        <div class="absolute -bottom-4 -right-4 bg-white rounded-xl shadow-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="bg-green-100 p-3 rounded-full">
                                    <i class="fas fa-check text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-xs">Progreso Total</p>
                                    <p class="text-2xl font-bold text-gray-800"><?= $estadisticas['progreso_promedio'] ?? 0 ?>%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Decoración de fondo mejorada -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24 blur-3xl"></div>
            <div class="absolute top-1/2 right-1/4 w-32 h-32 bg-yellow-400/20 rounded-full blur-2xl"></div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Cursos Inscritos -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Cursos Inscritos</p>
                        <p class="text-3xl font-bold text-indigo-600 mt-2"><?= $estadisticas['total_cursos'] ?? 0 ?></p>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <i class="fas fa-book text-indigo-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Cursos Completados -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Completados</p>
                        <p class="text-3xl font-bold text-green-600 mt-2"><?= $estadisticas['cursos_completados'] ?? 0 ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Progreso Promedio -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Progreso Promedio</p>
                        <p class="text-3xl font-bold text-purple-600 mt-2"><?= round($estadisticas['progreso_promedio'] ?? 0) ?>%</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Sesiones Activas -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Sesiones Activas</p>
                        <p class="text-3xl font-bold text-orange-600 mt-2">
                            <?= $info_sesiones['sesiones_activas'] ?>/<?= $info_sesiones['sesiones_maximas'] ?>
                        </p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-desktop text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mis Cursos en Progreso -->
        <?php if (!empty($mis_cursos)): ?>
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-2xl font-bold text-gray-800">Mis Cursos</h3>
                <a href="cursos.php" class="text-indigo-600 hover:text-indigo-800 font-medium">
                    Ver todos <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach (array_slice($mis_cursos, 0, 3) as $curso): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                    <img src="<?= htmlspecialchars($curso['imagen_url']) ?>" alt="<?= htmlspecialchars($curso['titulo']) ?>" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-indigo-600 bg-indigo-100 px-2 py-1 rounded">
                                <?= htmlspecialchars($curso['nivel']) ?>
                            </span>
                            <?php if ($curso['completado']): ?>
                            <span class="text-xs font-semibold text-green-600 bg-green-100 px-2 py-1 rounded">
                                <i class="fas fa-check mr-1"></i>Completado
                            </span>
                            <?php endif; ?>
                        </div>
                        <h4 class="text-lg font-bold text-gray-800 mb-2"><?= htmlspecialchars($curso['titulo']) ?></h4>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?= htmlspecialchars(substr($curso['descripcion'], 0, 100)) ?>...</p>
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Progreso</span>
                                <span class="font-semibold"><?= $curso['progreso'] ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: <?= $curso['progreso'] ?>%"></div>
                            </div>
                        </div>
                        <a href="curso.php?id=<?= $curso['id_curso'] ?>" class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 rounded-lg transition">
                            Continuar Curso
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cursos Disponibles -->
        <?php if (!empty($cursos_disponibles)): ?>
        <div>
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Explora Nuevos Cursos</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach (array_slice($cursos_disponibles, 0, 4) as $curso): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                    <img src="<?= htmlspecialchars($curso['imagen_url']) ?>" alt="<?= htmlspecialchars($curso['titulo']) ?>" class="w-full h-40 object-cover">
                    <div class="p-4">
                        <span class="text-xs font-semibold text-indigo-600 bg-indigo-100 px-2 py-1 rounded">
                            <?= htmlspecialchars($curso['nivel']) ?>
                        </span>
                        <h4 class="text-md font-bold text-gray-800 mt-2 mb-2"><?= htmlspecialchars($curso['titulo']) ?></h4>
                        <p class="text-gray-600 text-xs mb-3">
                            <i class="fas fa-user-tie mr-1"></i><?= htmlspecialchars($curso['instructor']) ?>
                        </p>
                        <a href="cursos.php" class="block w-full text-center bg-gray-100 hover:bg-indigo-600 hover:text-white text-gray-800 font-medium py-2 rounded-lg transition text-sm">
                            Ver Detalles
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Script para menú desplegable -->
    <script>
        // Toggle del menú de usuario
        const userMenuButton = document.getElementById('userMenuButton');
        const userMenuDropdown = document.getElementById('userMenuDropdown');

        userMenuButton.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenuDropdown.classList.toggle('hidden');
        });

        // Cerrar menú al hacer click fuera
        document.addEventListener('click', function(e) {
            if (!userMenuButton.contains(e.target) && !userMenuDropdown.contains(e.target)) {
                userMenuDropdown.classList.add('hidden');
            }
        });

        // Prevenir que el menú se cierre al hacer click dentro de él
        userMenuDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>

</body>
</html>
