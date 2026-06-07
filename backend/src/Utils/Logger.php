<?php
/**
 * 日志工具类
 */

namespace App\Utils;

class Logger
{
    private const LOG_LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3
    ];
    
    /**
     * 记录调试日志
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }
    
    /**
     * 记录信息日志
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }
    
    /**
     * 记录警告日志
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }
    
    /**
     * 记录错误日志
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }
    
    /**
     * 记录日志
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}";
        
        // 输出到标准错误（Docker日志）
        error_log($logMessage);
    }
}
