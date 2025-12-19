<?php
/**
 * Página Principal (Index)
 * Redirige al dashboard si está logueado, o al login si no lo está
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

$middleware = new AuthMiddleware();

// Si está logueado, ir al dashboard
if ($middleware->verificarAutenticacion(false)) {
    redirect(base_url('dashboard.php'));
} else {
    // Si no está logueado, ir al login
    redirect(base_url('login.php'));
}
