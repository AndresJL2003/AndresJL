<?php
/**
 * Página de Cursos
 * Muestra todos los cursos disponibles y permite inscribirse
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

// Proteger la ruta
$middleware = new AuthMiddleware();
$middleware->protegerRuta();

// Obtener usuario actual
$usuario = $middleware->obtenerUsuarioActual();

// Obtener todos los cursos
$curso_model = new Curso();
$todos_cursos = $curso_model->obtenerTodos();

// Procesar inscripción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscribir'])) {
    $id_curso = (int)$_POST['id_curso'];

    if ($curso_model->inscribir($usuario['id_usuario'], $id_curso)) {
        $_SESSION['mensaje_exito'] = 'Te has inscrito al curso exitosamente';
        redirect(base_url('cursos.php'));
    } else {
        $_SESSION['mensaje_error'] = 'No se pudo inscribir al curso. Es posible que ya estés inscrito.';
    }
}

// Filtro por nivel
$filtro_nivel = $_GET['nivel'] ?? 'todos';
if ($filtro_nivel !== 'todos') {
    $todos_cursos = array_filter($todos_cursos, function($curso) use ($filtro_nivel) {
        return strtolower($curso['nivel']) === strtolower($filtro_nivel);
    });
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
    <title>Cursos - Plataforma Educativa</title>
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
                    <!-- Carrito de compras -->
                    <a href="carrito.php" class="relative text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md transition">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span id="cart-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center" style="display: none;">
                            0
                        </span>
                    </a>
                    <a href="dashboard.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md transition">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="cursos.php" class="text-indigo-600 px-3 py-2 rounded-md font-semibold">
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

        <!-- Banner Header -->
        <div class="bg-gradient-to-r from-blue-600 to-cyan-600 rounded-2xl shadow-lg p-8 mb-8">
            <div class="text-white">
                <h1 class="text-4xl font-bold mb-3">📚 Catálogo de Cursos</h1>
                <p class="text-white/90 text-lg mb-4">Descubre cursos increíbles y expande tus conocimientos</p>
                <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2 inline-block">
                    <i class="fas fa-book-open mr-2"></i>
                    <span class="font-semibold"><?php echo count($todos_cursos); ?> Cursos Disponibles</span>
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje_error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <p class="text-red-700"><?php echo htmlspecialchars($mensaje_error); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($mensaje_exito): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <p class="text-green-700"><?php echo htmlspecialchars($mensaje_exito); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex items-center space-x-4">
                <label class="text-gray-700 font-medium">Filtrar por nivel:</label>
                <a href="cursos.php?nivel=todos" class="px-4 py-2 rounded-lg <?php echo $filtro_nivel === 'todos' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition">
                    Todos
                </a>
                <a href="cursos.php?nivel=principiante" class="px-4 py-2 rounded-lg <?php echo $filtro_nivel === 'principiante' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition">
                    Principiante
                </a>
                <a href="cursos.php?nivel=intermedio" class="px-4 py-2 rounded-lg <?php echo $filtro_nivel === 'intermedio' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition">
                    Intermedio
                </a>
                <a href="cursos.php?nivel=avanzado" class="px-4 py-2 rounded-lg <?php echo $filtro_nivel === 'avanzado' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition">
                    Avanzado
                </a>
            </div>
        </div>

        <!-- Grid de Cursos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($todos_cursos as $curso): ?>
                <?php $estaInscrito = $curso_model->estaInscrito($usuario['id_usuario'], $curso['id_curso']); ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                    <img src="<?php echo htmlspecialchars($curso['imagen_url']); ?>" alt="<?php echo htmlspecialchars($curso['titulo']); ?>" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold text-indigo-600 bg-indigo-100 px-3 py-1 rounded-full">
                                <?php echo htmlspecialchars($curso['nivel']); ?>
                            </span>
                            <?php if ($estaInscrito): ?>
                            <span class="text-xs font-semibold text-green-600 bg-green-100 px-3 py-1 rounded-full">
                                <i class="fas fa-check mr-1"></i>Inscrito
                            </span>
                            <?php endif; ?>
                        </div>

                        <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($curso['titulo']); ?></h3>

                        <div class="flex items-center text-sm text-gray-600 mb-3">
                            <i class="fas fa-user-tie mr-2"></i>
                            <span><?php echo htmlspecialchars($curso['instructor']); ?></span>
                        </div>

                        <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo htmlspecialchars($curso['descripcion']); ?></p>

                        <div class="flex items-center justify-between text-sm text-gray-600 mb-4">
                            <span><i class="fas fa-clock mr-1"></i><?php echo $curso['duracion_horas']; ?> horas</span>
                            <?php if ($curso['precio'] > 0): ?>
                            <span class="text-indigo-600 font-bold">$<?php echo number_format($curso['precio'], 2); ?></span>
                            <?php else: ?>
                            <span class="text-green-600 font-bold">Gratis</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($estaInscrito): ?>
                        <a href="dashboard.php" class="block w-full text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 rounded-lg transition">
                            <i class="fas fa-play mr-2"></i>Ir al Curso
                        </a>
                        <?php elseif ($curso['precio'] > 0): ?>
                        <!-- Curso de pago: Agregar al carrito -->
                        <button onclick="agregarAlCarrito(<?php echo $curso['id_curso']; ?>)" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 rounded-lg transition">
                            <i class="fas fa-cart-plus mr-2"></i>Agregar al Carrito
                        </button>
                        <?php else: ?>
                        <!-- Curso gratuito: Inscripción directa -->
                        <form method="POST" action="cursos.php">
                            <input type="hidden" name="id_curso" value="<?php echo $curso['id_curso']; ?>">
                            <button type="submit" name="inscribir" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 rounded-lg transition">
                                <i class="fas fa-plus mr-2"></i>Inscripción Gratis
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($todos_cursos)): ?>
        <div class="text-center py-12">
            <i class="fas fa-inbox text-gray-400 text-6xl mb-4"></i>
            <p class="text-gray-600 text-lg">No hay cursos disponibles para este filtro</p>
        </div>
        <?php endif; ?>

    </div>

    <script>
    // Actualizar contador al cargar
    document.addEventListener('DOMContentLoaded', function() {
        actualizarContador();
    });

    function agregarAlCarrito(idCurso) {
        fetch('api/carrito-agregar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id_curso: idCurso})
        })
        .then(r => r.json())
        .then(d => {
            alert(d.message || 'Procesado');
            if(d.success) actualizarContador();
        })
        .catch(e => alert('Error al agregar al carrito'));
    }

    function actualizarContador() {
        fetch('api/carrito-contar.php')
        .then(r => r.json())
        .then(d => {
            var badge = document.getElementById('cart-badge');
            if(badge && d.count > 0) {
                badge.textContent = d.count;
                badge.style.display = 'flex';
            }
        })
        .catch(e => console.log('Error contador'));
    }
    </script>

</body>
</html>
