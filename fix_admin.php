<?php
/**
 * Script para arreglar el usuario administrador
 * Ejecutar una sola vez y luego ELIMINAR este archivo
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

echo "<h1>🔧 Arreglando Usuario Admin</h1>";

// Generar nuevo hash para "admin123"
$nueva_password = 'admin123';
$nuevo_hash = password_hash($nueva_password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "<h2>Paso 1: Nuevo hash generado</h2>";
echo "<p>Password: <strong>admin123</strong></p>";
echo "<p>Hash generado: <code style='word-break: break-all;'>$nuevo_hash</code></p>";

// Actualizar usuario admin
$db = Database::getInstance();

// Primero verificar si existe
$sql_verificar = "SELECT id_usuario, email, activo, rol FROM usuarios WHERE email = 'admin@plataforma.com'";
$admin_existe = $db->queryOne($sql_verificar);

if (!$admin_existe) {
    echo "<h2>❌ Usuario admin NO existe</h2>";
    echo "<p>Creando usuario admin...</p>";

    $sql_crear = "INSERT INTO usuarios (nombre, apellido, email, password_hash, id_plan, rol, activo, token_activacion)
                  VALUES ('Admin', 'Sistema', 'admin@plataforma.com', :password_hash, 3, 'admin', 1, NULL)";

    $resultado = $db->execute($sql_crear, [':password_hash' => $nuevo_hash]);

    if ($resultado) {
        echo "<p style='color: green;'>✅ Usuario admin creado exitosamente</p>";
    } else {
        echo "<p style='color: red;'>❌ Error al crear usuario admin</p>";
    }
} else {
    echo "<h2>✅ Usuario admin existe</h2>";
    echo "<p>ID: {$admin_existe['id_usuario']}</p>";
    echo "<p>Email: {$admin_existe['email']}</p>";
    echo "<p>Activo: " . ($admin_existe['activo'] ? '✅ SÍ' : '❌ NO') . "</p>";
    echo "<p>Rol: {$admin_existe['rol']}</p>";

    // Actualizar contraseña y asegurar que esté activo
    $sql_actualizar = "UPDATE usuarios
                       SET password_hash = :password_hash,
                           activo = 1,
                           rol = 'admin',
                           id_plan = 3
                       WHERE email = 'admin@plataforma.com'";

    $resultado = $db->execute($sql_actualizar, [':password_hash' => $nuevo_hash]);

    if ($resultado) {
        echo "<p style='color: green;'>✅ Usuario admin actualizado exitosamente</p>";
    } else {
        echo "<p style='color: red;'>❌ Error al actualizar usuario admin</p>";
    }
}

// Verificar el resultado final
echo "<h2>Paso 2: Verificación final</h2>";
$admin_final = $db->queryOne($sql_verificar);

if ($admin_final) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td>ID Usuario</td><td>{$admin_final['id_usuario']}</td></tr>";
    echo "<tr><td>Email</td><td>{$admin_final['email']}</td></tr>";
    echo "<tr><td>Activo</td><td>" . ($admin_final['activo'] ? '✅ SÍ' : '❌ NO') . "</td></tr>";
    echo "<tr><td>Rol</td><td>{$admin_final['rol']}</td></tr>";
    echo "</table>";
}

// Probar login
echo "<h2>Paso 3: Probando login</h2>";
$usuario_model = new Usuario();
$test_login = $usuario_model->verificarCredenciales('admin@plataforma.com', 'admin123');

if ($test_login) {
    echo "<p style='color: green; font-size: 20px;'>✅ ¡LOGIN FUNCIONA CORRECTAMENTE!</p>";
    echo "<h3>Credenciales para entrar:</h3>";
    echo "<p><strong>Email:</strong> admin@plataforma.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p style='color: red;'><strong>⚠️ IMPORTANTE: Elimina este archivo (fix_admin.php) después de usarlo</strong></p>";
} else {
    echo "<p style='color: red; font-size: 20px;'>❌ ERROR: Login NO funciona</p>";
    echo "<p>Por favor contacta al desarrollador.</p>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h1 {
        color: #333;
        border-bottom: 3px solid #4F46E5;
        padding-bottom: 10px;
    }
    h2 {
        color: #4F46E5;
        margin-top: 30px;
    }
    code {
        background: #eee;
        padding: 2px 5px;
        border-radius: 3px;
    }
    table {
        background: white;
        margin: 20px 0;
    }
</style>
