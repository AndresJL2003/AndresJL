<?php
/**
 * Endpoint API: Contar items en el carrito
 * Método: GET
 * Respuesta: {"count": int}
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

// Configurar respuesta JSON
header('Content-Type: application/json');

// Verificar autenticación
$middleware = new AuthMiddleware();
if (!$middleware->verificarAutenticacion(false)) {
    echo json_encode(['count' => 0]);
    exit;
}

// Obtener contador
$carrito_controller = new CarritoController();
$count = $carrito_controller->contarItems();

echo json_encode(['count' => $count]);
