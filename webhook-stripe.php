<?php
/**
 * Webhook de Stripe
 * Endpoint para recibir eventos de Stripe
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

// Archivo de log para debugging
$log_file = __DIR__ . '/stripe-webhook-log.txt';

function log_webhook($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

log_webhook("=== NUEVO WEBHOOK RECIBIDO ===");

// Obtener el payload y la firma del webhook
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

log_webhook("Payload recibido: " . strlen($payload) . " bytes");
log_webhook("Signature header: " . substr($sig_header, 0, 30) . "...");

if (empty($payload)) {
    log_webhook("ERROR: Payload vacío");
    http_response_code(400);
    exit();
}

if (empty($sig_header)) {
    log_webhook("ERROR: Sin firma de Stripe");
    http_response_code(400);
    exit();
}

try {
    log_webhook("Intentando procesar webhook...");

    // Procesar el webhook con el controlador
    $pago_controller = new PagoController();
    $pago_controller->procesarWebhook($payload, $sig_header);

    log_webhook("✓ Webhook procesado exitosamente");

} catch (Exception $e) {
    log_webhook("ERROR al procesar webhook: " . $e->getMessage());
    log_webhook("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
}
