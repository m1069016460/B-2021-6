<?php
/**
 * 数据库配置类
 */

namespace App\Config;

use PDO;
use PDOException;
use App\Utils\Logger;

class Database
{
    private static ?PDO $connection = null;
    
    /**
     * 获取数据库连接
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::connect();
        }
        return self::$connection;
    }
    
    /**
     * 建立数据库连接
     */
    private static function connect(): void
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_DATABASE') ?: 'grade_system';
        $username = getenv('DB_USERNAME') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: 'root';
        
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        $maxRetries = 30;
        $retryDelay = 2;
        
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                self::$connection = new PDO($dsn, $username, $password, $options);
                Logger::info("Database connected successfully");
                return;
            } catch (PDOException $e) {
                Logger::warning("Database connection attempt " . ($i + 1) . " failed: " . $e->getMessage());
                if ($i < $maxRetries - 1) {
                    sleep($retryDelay);
                }
            }
        }
        
        throw new PDOException("Could not connect to database after {$maxRetries} attempts");
    }
    
    /**
     * 关闭数据库连接
     */
    public static function close(): void
    {
        self::$connection = null;
    }
    
    /**
     * 开始事务
     */
    public static function beginTransaction(): void
    {
        self::getConnection()->beginTransaction();
    }
    
    /**
     * 提交事务
     */
    public static function commit(): void
    {
        self::getConnection()->commit();
    }
    
    /**
     * 回滚事务
     */
    public static function rollback(): void
    {
        self::getConnection()->rollBack();
    }
}
