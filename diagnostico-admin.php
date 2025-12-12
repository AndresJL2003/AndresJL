<?php
/**
 * Script de Diagnóstico Completo
 * Verifica el usuario administrador en la base de datos
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        table th {
            background-color: #4CAF50;
            color: white;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .fix-button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
        }
        .fix-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico del Usuario Administrador</h1>

        <?php
        try {
            // Crear conexión a base de datos
            $db = Database::getInstance();

            echo '<div class="success">✅ Conexión a base de datos exitosa</div>';

            // Información de la configuración
            echo '<h2>📋 Configuración Actual</h2>';
            echo '<table>';
            echo '<tr><th>Parámetro</th><th>Valor</th></tr>';
            echo '<tr><td>Servidor BD</td><td>' . DB_SERVER . '</td></tr>';
            echo '<tr><td>Nombre BD</td><td>' . DB_NAME . '</td></tr>';
            echo '<tr><td>Usuario BD</td><td>' . DB_USER . '</td></tr>';
            echo '<tr><td>Entorno</td><td>' . (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ? 'LOCAL' : 'PRODUCCIÓN') . '</td></tr>';
            echo '</table>';

            // Buscar usuario admin
            echo '<h2>👤 Búsqueda de Usuario Admin</h2>';

            $sql = "SELECT id_usuario, nombre, apellido, email, password_hash, rol, activo, id_plan, fecha_registro
                    FROM usuarios
                    WHERE email = :email";

            $usuario = $db->queryOne($sql, [':email' => 'admin@plataforma.com']);

            if ($usuario) {
                echo '<div class="success">✅ Usuario admin encontrado en la base de datos</div>';

                echo '<table>';
                echo '<tr><th>Campo</th><th>Valor</th></tr>';
                echo '<tr><td>ID Usuario</td><td>' . $usuario['id_usuario'] . '</td></tr>';
                echo '<tr><td>Nombre</td><td>' . htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) . '</td></tr>';
                echo '<tr><td>Email</td><td>' . htmlspecialchars($usuario['email']) . '</td></tr>';
                echo '<tr><td>Rol</td><td><strong>' . htmlspecialchars($usuario['rol']) . '</strong></td></tr>';
                echo '<tr><td>Activo</td><td>' . ($usuario['activo'] ? '✅ SÍ' : '❌ NO') . '</td></tr>';
                echo '<tr><td>ID Plan</td><td>' . $usuario['id_plan'] . '</td></tr>';
                echo '<tr><td>Fecha Registro</td><td>' . $usuario['fecha_registro'] . '</td></tr>';
                echo '<tr><td>Hash Password</td><td><code style="font-size: 10px;">' . substr($usuario['password_hash'], 0, 50) . '...</code></td></tr>';
                echo '</table>';

                // Verificar si está activo
                if (!$usuario['activo']) {
                    echo '<div class="error">❌ PROBLEMA: El usuario admin NO está activo. Debe activarse para poder iniciar sesión.</div>';
                    echo '<form method="POST">';
                    echo '<input type="hidden" name="action" value="activar_admin">';
                    echo '<button type="submit" class="fix-button">🔧 Activar Usuario Admin</button>';
                    echo '</form>';
                } else {
                    echo '<div class="success">✅ El usuario está activo</div>';
                }

                // Verificar contraseñas
                echo '<h2>🔐 Verificación de Contraseñas</h2>';

                $passwords_to_test = [
                    'admin123',
                    'Admin123',
                    'Admin 123',
                    'admin 123',
                    'ADMIN123'
                ];

                echo '<table>';
                echo '<tr><th>Contraseña</th><th>Resultado</th></tr>';

                $password_correcta = null;
                foreach ($passwords_to_test as $password) {
                    $is_valid = password_verify($password, $usuario['password_hash']);
                    $status = $is_valid ? '✅ CORRECTA' : '❌ Incorrecta';
                    $color = $is_valid ? 'success' : '';

                    echo '<tr class="' . $color . '">';
                    echo '<td><code>' . htmlspecialchars($password) . '</code></td>';
                    echo '<td>' . $status . '</td>';
                    echo '</tr>';

                    if ($is_valid) {
                        $password_correcta = $password;
                    }
                }
                echo '</table>';

                if ($password_correcta) {
                    echo '<div class="success">';
                    echo '<strong>✅ Contraseña correcta encontrada:</strong> <code>' . htmlspecialchars($password_correcta) . '</code>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '❌ PROBLEMA: Ninguna de las contraseñas comunes funciona con el hash almacenado.';
                    echo '<br><br>Esto significa que el hash en la base de datos es diferente al esperado.';
                    echo '</div>';

                    echo '<div class="warning">';
                    echo '<strong>💡 Solución:</strong> Generar un nuevo hash para la contraseña <code>admin123</code>';
                    echo '<form method="POST">';
                    echo '<input type="hidden" name="action" value="resetear_password">';
                    echo '<button type="submit" class="fix-button">🔧 Resetear Password a "admin123"</button>';
                    echo '</form>';
                    echo '</div>';
                }

            } else {
                echo '<div class="error">❌ PROBLEMA: No se encontró el usuario admin@plataforma.com en la base de datos</div>';

                echo '<div class="warning">';
                echo '<strong>💡 Solución:</strong> Crear el usuario administrador';
                echo '<form method="POST">';
                echo '<input type="hidden" name="action" value="crear_admin">';
                echo '<button type="submit" class="fix-button">🔧 Crear Usuario Admin</button>';
                echo '</form>';
                echo '</div>';
            }

            // Verificar todos los usuarios
            echo '<h2>👥 Todos los Usuarios en la Base de Datos</h2>';
            $sql_todos = "SELECT id_usuario, nombre, apellido, email, rol, activo FROM usuarios ORDER BY id_usuario";
            $todos_usuarios = $db->query($sql_todos);

            if ($todos_usuarios && count($todos_usuarios) > 0) {
                echo '<table>';
                echo '<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th></tr>';
                foreach ($todos_usuarios as $u) {
                    echo '<tr>';
                    echo '<td>' . $u['id_usuario'] . '</td>';
                    echo '<td>' . htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) . '</td>';
                    echo '<td>' . htmlspecialchars($u['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($u['rol']) . '</td>';
                    echo '<td>' . ($u['activo'] ? '✅' : '❌') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="warning">⚠️ No hay usuarios en la base de datos</div>';
            }

        } catch (Exception $e) {
            echo '<div class="error">❌ Error de conexión: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="info">';
            echo '<strong>Posibles causas:</strong><br>';
            echo '1. Las credenciales de la base de datos son incorrectas<br>';
            echo '2. El servidor de base de datos no está accesible<br>';
            echo '3. La base de datos no existe<br>';
            echo '</div>';
        }

        // Procesar acciones POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            echo '<hr>';
            echo '<h2>🔧 Ejecutando Reparación</h2>';

            try {
                $db = Database::getInstance();

                switch ($_POST['action']) {
                    case 'activar_admin':
                        $sql = "UPDATE usuarios SET activo = 1 WHERE email = 'admin@plataforma.com'";
                        if ($db->execute($sql)) {
                            echo '<div class="success">✅ Usuario admin activado exitosamente. <a href="">Recargar página</a></div>';
                        } else {
                            echo '<div class="error">❌ Error al activar usuario</div>';
                        }
                        break;

                    case 'resetear_password':
                        $nuevo_hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
                        $sql = "UPDATE usuarios SET password_hash = :hash, activo = 1 WHERE email = 'admin@plataforma.com'";
                        if ($db->execute($sql, [':hash' => $nuevo_hash])) {
                            echo '<div class="success">✅ Contraseña reseteada exitosamente a <code>admin123</code>. <a href="">Recargar página</a></div>';
                        } else {
                            echo '<div class="error">❌ Error al resetear contraseña</div>';
                        }
                        break;

                    case 'crear_admin':
                        $password_hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
                        $sql = "INSERT INTO usuarios (nombre, apellido, email, password_hash, id_plan, rol, activo)
                                VALUES ('Admin', 'Sistema', 'admin@plataforma.com', :hash, 3, 'admin', 1)";
                        if ($db->execute($sql, [':hash' => $password_hash])) {
                            echo '<div class="success">✅ Usuario admin creado exitosamente. <a href="">Recargar página</a></div>';
                        } else {
                            echo '<div class="error">❌ Error al crear usuario admin</div>';
                        }
                        break;
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
        ?>

        <hr>
        <div class="info">
            <strong>📝 Instrucciones:</strong><br>
            1. Revisa los resultados del diagnóstico arriba<br>
            2. Si hay problemas, usa los botones de reparación<br>
            3. Una vez solucionado, intenta iniciar sesión con:<br>
            <strong>Email:</strong> admin@plataforma.com<br>
            <strong>Password:</strong> admin123
        </div>

        <div style="margin-top: 20px; text-align: center;">
            <a href="login.php" style="color: #4CAF50; text-decoration: none; font-weight: bold;">← Volver al Login</a>
        </div>
    </div>
</body>
</html>
