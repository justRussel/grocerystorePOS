<?php
/**
 * GroceryPOS - Database PDO Singleton
 * Provides a single shared PDO connection for all models.
 */

class Database
{
    /** @var PDO|null */
    private static ?PDO $instance = null;

    /**
     * Private constructor — prevents direct instantiation.
     */
    private function __construct() {}

    /**
     * Prevent cloning of the singleton instance.
     */
    private function __clone() {}

    /**
     * Returns the shared PDO instance, creating it on first call.
     *
     * @return PDO
     * @throws RuntimeException if the connection cannot be established
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect();
        }

        return self::$instance;
    }

    /**
     * Opens the PDO connection using constants from config.php.
     *
     * @return PDO
     */
    private static function connect(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            // Log to PHP error log; never expose credentials in output
            error_log('Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed. Please check your configuration.');
        }
    }
}
