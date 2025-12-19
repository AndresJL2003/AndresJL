<?php
/**
 * Script de prueba de conexión a base de datos
 * Sube este archivo a tu hosting y accede a él desde el navegador
 */

// Mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Test de Conexión a Base de Datos</h1>";
echo "<hr>";

// Configuración de InfinityFree
$configs = [
    [
        'name' => 'Con localhost (Recomendado para InfinityFree)',
        'host' => 'localhost',
        'dbname' => 'if0_40627984_programacion_web_1',
        'username' => 'if0_40627984',
        'password' => 'TBAOpiQLkSw2CNt'
    ],
    [
        'name' => 'Con host remoto sql305.infinityfree.com',
        'host' => 'sql305.infinityfree.com',
        'dbname' => 'if0_40627984_programacion_web_1',
        'username' => 'if0_40627984',
        'password' => 'TBAOpiQLkSw2CNt'
    ]
];

foreach ($configs as $config) {
    echo "<h2>📋 Probando: {$config['name']}</h2>";
    echo "<pre>";
    echo "Host: {$config['host']}\n";
    echo "Database: {$config['dbname']}\n";
    echo "Username: {$config['username']}\n";
    echo "Password: " . str_repeat('*', strlen($config['password'])) . "\n";
    echo "</pre>";

    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo "<p style='color: green; font-weight: bold;'>✅ CONEXIÓN EXITOSA</p>";

        // Probar query
        $stmt = $pdo->query("SELECT DATABASE() as db_name, NOW() as current_time");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "<p>Base de datos activa: <strong>{$result['db_name']}</strong></p>";
        echo "<p>Hora del servidor: <strong>{$result['current_time']}</strong></p>";

        // Listar tablas
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "<p>Tablas encontradas (" . count($tables) . "):</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>{$table}</li>";
        }
        echo "</ul>";

        $pdo = null;

    } catch (PDOException $e) {
        echo "<p style='color: red; font-weight: bold;'>❌ ERROR DE CONEXIÓN</p>";
        echo "<pre style='background: #fee; padding: 10px; border: 1px solid red;'>";
        echo "Error: " . $e->getMessage() . "\n";
        echo "Código: " . $e->getCode();
        echo "</pre>";
    }

    echo "<hr>";
}

echo "<h2>📊 Información del Servidor</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido') . "\n";
echo "Host: " . ($_SERVER['HTTP_HOST'] ?? 'Desconocido') . "\n";
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
echo "</pre>";

echo "<hr>";
echo "<p><strong>NOTA:</strong> Elimina este archivo después de usarlo por seguridad.</p>";
?>
