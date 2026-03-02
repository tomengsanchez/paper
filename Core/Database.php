<?php
namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config;

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    self::$config['host'],
                    self::$config['dbname'],
                    self::$config['charset']
                );
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            try {
                if (SystemDebug::isCollecting()) {
                    self::$instance = new LoggingPDO(
                        $dsn,
                        self::$config['username'],
                        self::$config['password'],
                        $options
                    );
                } else {
                    self::$instance = new PDO(
                        $dsn,
                        self::$config['username'],
                        self::$config['password'],
                        $options
                    );
                }
            } catch (PDOException $e) {
                Logger::database($e->getMessage(), [
                    'dsn' => $dsn ?? '',
                    'code' => $e->getCode(),
                ]);
                die('Database connection failed: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
