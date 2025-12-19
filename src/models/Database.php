<?php
/**
 * Clase Database para MySQL/MariaDB
 * Versión actualizada para XAMPP con MySQL
 */

class Database {
    private static $instance = null;
    private $conn;

    /**
     * Constructor privado para evitar instanciación directa
     */
    private function __construct() {
        try {
            // Configurar opciones de PDO
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            // Crear conexión PDO a MySQL
            $this->conn = new PDO(
                DB_DSN,
                DB_USER,
                DB_PASS,
                $options
            );
        } catch (PDOException $e) {
            // En producción, registrar el error en un log en lugar de mostrarlo
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                die("Error de conexión a la base de datos: " . $e->getMessage());
            } else {
                // Log el error
                error_log("Database Error: " . $e->getMessage());
                die("Error de conexión a la base de datos. Por favor contacte al administrador.");
            }
        }
    }

    /**
     * Obtiene la instancia única de la base de datos (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtiene la conexión PDO
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Ejecuta una consulta SELECT y devuelve todos los resultados
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e, $sql);
            return [];
        }
    }

    /**
     * Ejecuta una consulta SELECT y devuelve un solo registro
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->handleError($e, $sql);
            return false;
        }
    }

    /**
     * Ejecuta una consulta INSERT, UPDATE o DELETE
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->handleError($e, $sql);
            return false;
        }
    }

    /**
     * Ejecuta un INSERT y devuelve el ID generado
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            // En MySQL, usar lastInsertId() directamente
            return (int) $this->conn->lastInsertId();
        } catch (PDOException $e) {
            $this->handleError($e, $sql);
            return false;
        }
    }

    /**
     * Inicia una transacción
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Confirma una transacción
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Revierte una transacción
     */
    public function rollback() {
        return $this->conn->rollBack();
    }

    /**
     * Maneja errores de base de datos
     */
    private function handleError($e, $sql = '') {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "<div style='background: #ffebee; padding: 10px; border-left: 4px solid #f44336; margin: 10px;'>";
            echo "<strong>Error de Base de Datos:</strong><br>";
            echo "<strong>Mensaje:</strong> " . $e->getMessage() . "<br>";
            if ($sql) {
                echo "<strong>SQL:</strong> " . htmlspecialchars($sql) . "<br>";
            }
            echo "<strong>Archivo:</strong> " . $e->getFile() . ":" . $e->getLine();
            echo "</div>";
        } else {
            // En producción, registrar en un archivo de log
            error_log("Database Error: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }

    /**
     * Previene la clonación del objeto
     */
    private function __clone() {}

    /**
     * Previente la deserialización
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
