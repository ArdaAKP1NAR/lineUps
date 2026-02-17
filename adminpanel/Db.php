<?php
/**
 * PDO Database connection - LineUp
 * Roles, Users, Sessions, Videos, Categories
 */

class Db
{
    /** @var PDO|null */
    private static $pdo = null;

    /**
     * @return PDO
     * @throws PDOException
     */
    public static function get(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
        if (!is_file($configFile)) {
            throw new PDOException('config.php not found. Copy config.php.example to config.php.');
        }

        $config = require $configFile;
        $db = $config['db'] ?? null;
        if (!$db) {
            throw new PDOException('config.php must return array with "db" key.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'] ?? 'localhost',
            $db['port'] ?? 3306,
            $db['dbname'] ?? 'lineup',
            $db['charset'] ?? 'utf8mb4'
        );

        self::$pdo = new PDO(
            $dsn,
            $db['username'] ?? 'root',
            $db['password'] ?? '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        return self::$pdo;
    }

    /**
     * Reset connection (e.g. for tests)
     */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}
