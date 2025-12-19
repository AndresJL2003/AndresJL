<?php
/**
 * Endpoint API: Eliminar curso del carrito
 * Método: POST
 * Body: {"id_curso": int}
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

// Configurar respuesta JSON
header('Content-Type: application/json');

// Verificar autenticación
$middleware = new AuthMiddleware();
if (!$middleware->verificarAutenticacion(false)) {
    echo json_encode([
        'success' => false,
        'message' => 'No autenticado'
    ]);
    exit;
}

// Leer datos del request
$input = json_decode(file_get_contents('php://input'), true);
$id_curso = (int)($input['id_curso'] ?? 0);

if ($id_curso <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de curso inválido'
    ]);
    exit;
}

// Procesar con el controlador
$carrito_controller = new CarritoController();
$resultado = $carrito_controller->eliminarDelCarrito($id_curso);

echo json_encode($resultado);
