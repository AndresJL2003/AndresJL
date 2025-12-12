<?php
/**
 * Test simple del webhook
 * Este archivo ayuda a diagnosticar si Stripe puede acceder al webhook
 */

// Registrar todo en un archivo de log
$log_file = __DIR__ . '/webhook-log.txt';
$timestamp = date('Y-m-d H:i:s');

// Capturar información
$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$payload = file_get_contents('php://input');
$headers = getallheaders();

$log_entry = "=== WEBHOOK TEST ===\n";
$log_entry .= "Timestamp: $timestamp\n";
$log_entry .= "Method: $method\n";
$log_entry .= "Headers: " . json_encode($headers) . "\n";
$log_entry .= "Payload: " . substr($payload, 0, 200) . "...\n";
$log_entry .= "===================\n\n";

// Escribir en el log
file_put_contents($log_file, $log_entry, FILE_APPEND);

// Responder con éxito
http_response_code(200);
echo json_encode(['status' => 'ok', 'message' => 'Webhook test received']);
