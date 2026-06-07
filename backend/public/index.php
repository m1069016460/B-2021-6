<?php
/**
 * 学生成绩管理系统 - API入口
 */

// 错误报告配置
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 自动加载
require_once __DIR__ . '/../vendor/autoload.php';

// 加载配置
require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/Config/App.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

use App\Utils\Response;
use App\Utils\Logger;

try {
    // 获取请求路径
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = rtrim($uri, '/');
    
    // 移除 /api 前缀（如果有）
    if (strpos($uri, '/api') === 0) {
        $uri = substr($uri, 4);
    }
    
    // 如果 URI 为空，设置为根路径
    if (empty($uri)) {
        $uri = '/';
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // 记录请求日志
    Logger::info("Request: {$method} {$uri}");
    
    // 加载路由
    require_once __DIR__ . '/../src/routes.php';
    
    // 路由分发
    $router = new \App\Router();
    $router->dispatch($method, $uri);
    
} catch (\Exception $e) {
    Logger::error('Server Error: ' . $e->getMessage());
    Response::error('服务器内部错误', 500);
}
