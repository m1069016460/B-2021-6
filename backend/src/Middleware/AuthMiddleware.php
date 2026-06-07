<?php
/**
 * 认证中间件
 */

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Config\App;
use App\Utils\Response;
use App\Utils\Logger;

class AuthMiddleware
{
    /**
     * 处理认证
     */
    public static function handle(): ?array
    {
        $token = self::getToken();
        
        if (!$token) {
            Response::error('未登录或登录已过期', 401);
            return null;
        }
        
        try {
            $decoded = JWT::decode($token, new Key(App::getJwtSecret(), 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            Logger::warning('Token验证失败: ' . $e->getMessage());
            Response::error('登录已过期，请重新登录', 401);
            return null;
        }
    }
    
    /**
     * 获取Token
     */
    private static function getToken(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return $_GET['token'] ?? null;
    }
    
    /**
     * 检查是否为管理员
     */
    public static function isAdmin(array $user): bool
    {
        return ($user['role'] ?? '') === 'admin';
    }
}
