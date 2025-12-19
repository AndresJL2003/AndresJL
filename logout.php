<?php
/**
 * Página de Cierre de Sesión
 * Cierra la sesión del usuario actual
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

// Cerrar sesión
$middleware = new AuthMiddleware();
$middleware->cerrarSesionActual();

// Establecer mensaje de éxito
$_SESSION['mensaje_exito'] = 'Has cerrado sesión correctamente';

// Redirigir al login
redirect(base_url('login.php'));
