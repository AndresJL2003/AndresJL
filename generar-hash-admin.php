<?php
/**
 * Generador de Hash para Usuario Admin
 * Este script te permite generar el hash correcto para cualquier contraseña
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

$resultado = '';
$hash_generado = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generar_hash'])) {
        $password = $_POST['password'];
        $hash_generado = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $resultado = 'hash_generado';
    } elseif (isset($_POST['actualizar_bd'])) {
        try {
            $db = Database::getInstance();
            $password = $_POST['password'];
            $email = $_POST['email'];

            // Generar nuevo hash
            $nuevo_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Verificar si el usuario existe
            $sql_check = "SELECT id_usuario FROM usuarios WHERE email = :email";
            $usuario_existe = $db->queryOne($sql_check, [':email' => $email]);

            if ($usuario_existe) {
                // Actualizar usuario existente
                $sql = "UPDATE usuarios
                        SET password_hash = :hash,
                            activo = 1,
                            rol = 'admin'
                        WHERE email = :email";

                if ($db->execute($sql, [':hash' => $nuevo_hash, ':email' => $email])) {
                    $resultado = 'actualizado';
                } else {
                    $resultado = 'error_actualizar';
                }
            } else {
                // Crear nuevo usuario admin
                $sql = "INSERT INTO usuarios (nombre, apellido, email, password_hash, id_plan, rol, activo)
                        VALUES ('Admin', 'Sistema', :email, :hash, 3, 'admin', 1)";

                if ($db->execute($sql, [':email' => $email, ':hash' => $nuevo_hash])) {
                    $resultado = 'creado';
                } else {
                    $resultado = 'error_crear';
                }
            }
        } catch (Exception $e) {
            $resultado = 'error_conexion';
            $error_mensaje = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Hash Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        input[type="email"],
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        input[type="email"]:focus,
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(56, 239, 125, 0.4);
        }
        .hash-output {
            background-color: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
            color: #333;
        }
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e0e0e0, transparent);
            margin: 30px 0;
        }
        .info-box {
            background-color: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
        }
        .info-box strong {
            color: #667eea;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .toggle-password {
            position: relative;
        }
        .toggle-password button {
            position: absolute;
            right: 10px;
            top: 38px;
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Generador de Hash para Admin</h1>
        <p class="subtitle">Configura las credenciales de tu usuario administrador</p>

        <?php if ($resultado === 'hash_generado'): ?>
            <div class="alert alert-success">
                ✅ Hash generado exitosamente
            </div>
            <div class="hash-output">
                <strong>Hash generado:</strong><br>
                <?= htmlspecialchars($hash_generado) ?>
            </div>
            <div class="info-box">
                <strong>📝 Copia este hash y ejecútalo en phpMyAdmin:</strong><br><br>
                <code style="background: white; padding: 10px; display: block; border-radius: 5px;">
                UPDATE usuarios SET password_hash = '<?= htmlspecialchars($hash_generado) ?>', activo = 1 WHERE email = '<?= htmlspecialchars($_POST['email']) ?>';
                </code>
            </div>
        <?php elseif ($resultado === 'actualizado'): ?>
            <div class="alert alert-success">
                ✅ ¡Contraseña actualizada exitosamente!<br><br>
                <strong>Credenciales:</strong><br>
                Email: <?= htmlspecialchars($_POST['email']) ?><br>
                Password: <?= htmlspecialchars($_POST['password']) ?><br><br>
                <a href="login.php" style="color: #155724; font-weight: bold;">→ Ir al Login</a>
            </div>
        <?php elseif ($resultado === 'creado'): ?>
            <div class="alert alert-success">
                ✅ ¡Usuario administrador creado exitosamente!<br><br>
                <strong>Credenciales:</strong><br>
                Email: <?= htmlspecialchars($_POST['email']) ?><br>
                Password: <?= htmlspecialchars($_POST['password']) ?><br><br>
                <a href="login.php" style="color: #155724; font-weight: bold;">→ Ir al Login</a>
            </div>
        <?php elseif ($resultado === 'error_actualizar' || $resultado === 'error_crear'): ?>
            <div class="alert alert-error">
                ❌ Error al actualizar/crear el usuario. Intenta usar el hash manual en phpMyAdmin.
            </div>
        <?php elseif ($resultado === 'error_conexion'): ?>
            <div class="alert alert-error">
                ❌ Error de conexión: <?= htmlspecialchars($error_mensaje ?? 'Desconocido') ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email del Administrador</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="admin@plataforma.com"
                    required
                    placeholder="admin@plataforma.com"
                >
            </div>

            <div class="form-group toggle-password">
                <label for="password">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="Ingresa la contraseña que deseas usar"
                    value="<?= isset($_POST['password']) ? htmlspecialchars($_POST['password']) : '' ?>"
                >
                <button type="button" onclick="togglePassword()" title="Mostrar/Ocultar contraseña">
                    👁️
                </button>
            </div>

            <div class="info-box">
                <strong>💡 Contraseñas comunes:</strong><br>
                • admin123<br>
                • Admin123<br>
                • Admin 123<br>
                <br>
                Escribe la contraseña exacta que quieres usar (incluyendo espacios y mayúsculas).
            </div>

            <button type="submit" name="actualizar_bd" class="btn btn-success">
                🔧 Actualizar Directamente en la Base de Datos
            </button>

            <div class="divider"></div>

            <button type="submit" name="generar_hash" class="btn btn-primary">
                📋 Solo Generar Hash (para copiar manualmente)
            </button>
        </form>

        <a href="login.php" class="back-link">← Volver al Login</a>
        <a href="diagnostico-admin.php" class="back-link">🔍 Ver Diagnóstico Completo</a>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
            } else {
                passwordField.type = 'password';
            }
        }
    </script>
</body>
</html>
