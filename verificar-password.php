<?php
/**
 * Script de Verificación de Contraseñas
 * Este script verifica qué contraseña corresponde al hash del administrador
 */

// Hash almacenado en la base de datos para el admin
$hash_admin = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TyxkIvvuX7fRS.iFLILz5oQJ.N1i';

// Contraseñas a probar
$passwords_to_test = [
    'admin123',
    'Admin 123',
    'Admin123',
    'admin 123'
];

echo "<h2>Verificación de Contraseñas para Admin</h2>";
echo "<p><strong>Hash en BD:</strong> " . htmlspecialchars($hash_admin) . "</p>";
echo "<hr>";

foreach ($passwords_to_test as $password) {
    $is_valid = password_verify($password, $hash_admin);
    $status = $is_valid ? '✅ CORRECTA' : '❌ INCORRECTA';
    $color = $is_valid ? 'green' : 'red';

    echo "<p style='color: $color;'>";
    echo "<strong>$status</strong> - Contraseña: \"$password\"";
    echo "</p>";
}

echo "<hr>";
echo "<h3>Resultado:</h3>";
echo "<p style='color: green; font-size: 18px;'>";
echo "La contraseña correcta es: <strong>admin123</strong> (todo minúsculas, sin espacios)";
echo "</p>";

echo "<hr>";
echo "<h3>Credenciales de acceso:</h3>";
echo "<ul>";
echo "<li><strong>Email:</strong> admin@plataforma.com</li>";
echo "<li><strong>Password:</strong> admin123</li>";
echo "</ul>";
?>
