<?php
/**
 * Database Connection using PDO
 */

// Support environment variables or external production config (e.g. ServerByte)
if (file_exists(__DIR__ . '/db.prod.php')) {
    require_once __DIR__ . '/db.prod.php';
}

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'bus_booking_db');

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            try {
                // Try to ensure database exists if permissions allow (e.g. local XAMPP)
                try {
                    $dsnWithoutDb = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
                    $pdoInit = new PDO($dsnWithoutDb, DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
                    ]);
                    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                } catch (Throwable $ignored) {
                    // Shared hosting users might not have CREATE DATABASE privilege, proceed directly
                }

                // Connect to database
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (PDOException $e) {
                die("Database Connection Error: " . $e->getMessage() . " (Please check database configuration in config/db.php or config/db.prod.php)");
            }
        }
        return self::$instance;
    }
}

function getDB() {
    return Database::getConnection();
}
