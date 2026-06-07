<?php
/**
 * 路由类
 */

namespace App;

use App\Utils\Response;
use App\Utils\Logger;
use App\Middleware\AuthMiddleware;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    
    public function __construct()
    {
        $this->registerRoutes();
    }
    
    /**
     * 注册路由
     */
    private function registerRoutes(): void
    {
        // 健康检查
        $this->addRoute('GET', '/health', 'HealthController', 'check');
        $this->addRoute('GET', '/', 'HealthController', 'check');
        
        // 认证路由
        $this->addRoute('POST', '/auth/login', 'AuthController', 'login');
        $this->addRoute('POST', '/auth/register', 'AuthController', 'register');
        $this->addRoute('GET', '/auth/profile', 'AuthController', 'profile', true);
        $this->addRoute('PUT', '/auth/profile', 'AuthController', 'updateProfile', true);
        $this->addRoute('PUT', '/auth/password', 'AuthController', 'changePassword', true);
        
        // 用户管理路由
        $this->addRoute('GET', '/users', 'UserController', 'index', true);
        $this->addRoute('GET', '/users/{id}', 'UserController', 'show', true);
        $this->addRoute('POST', '/users', 'UserController', 'store', true);
        $this->addRoute('PUT', '/users/{id}', 'UserController', 'update', true);
        $this->addRoute('DELETE', '/users/{id}', 'UserController', 'destroy', true);
        
        // 班级管理路由
        $this->addRoute('GET', '/classes', 'ClassController', 'index', true);
        $this->addRoute('GET', '/classes/all', 'ClassController', 'all', true);
        $this->addRoute('GET', '/classes/{id}', 'ClassController', 'show', true);
        $this->addRoute('POST', '/classes', 'ClassController', 'store', true);
        $this->addRoute('PUT', '/classes/{id}', 'ClassController', 'update', true);
        $this->addRoute('DELETE', '/classes/{id}', 'ClassController', 'destroy', true);
        $this->addRoute('GET', '/classes/{id}/students', 'ClassController', 'students', true);
        
        // 学生管理路由
        $this->addRoute('GET', '/students', 'StudentController', 'index', true);
        $this->addRoute('GET', '/students/export', 'StudentController', 'export', true);
        $this->addRoute('GET', '/students/template', 'StudentController', 'template', true);
        $this->addRoute('POST', '/students/import', 'StudentController', 'import', true);
        $this->addRoute('POST', '/students/paste-import', 'StudentController', 'pasteImport', true);
        $this->addRoute('GET', '/students/{id}', 'StudentController', 'show', true);
        $this->addRoute('POST', '/students', 'StudentController', 'store', true);
        $this->addRoute('PUT', '/students/{id}', 'StudentController', 'update', true);
        $this->addRoute('DELETE', '/students/{id}', 'StudentController', 'destroy', true);
        
        // 课程管理路由
        $this->addRoute('GET', '/courses', 'CourseController', 'index', true);
        $this->addRoute('GET', '/courses/all', 'CourseController', 'all', true);
        $this->addRoute('GET', '/courses/{id}', 'CourseController', 'show', true);
        $this->addRoute('POST', '/courses', 'CourseController', 'store', true);
        $this->addRoute('PUT', '/courses/{id}', 'CourseController', 'update', true);
        $this->addRoute('DELETE', '/courses/{id}', 'CourseController', 'destroy', true);
        
        // 成绩管理路由
        $this->addRoute('GET', '/grades', 'GradeController', 'index', true);
        $this->addRoute('GET', '/grades/export', 'GradeController', 'export', true);
        $this->addRoute('GET', '/grades/template', 'GradeController', 'template', true);
        $this->addRoute('POST', '/grades/import', 'GradeController', 'import', true);
        $this->addRoute('POST', '/grades/paste-import', 'GradeController', 'pasteImport', true);
        $this->addRoute('GET', '/grades/student/{studentId}', 'GradeController', 'studentGrades', true);
        $this->addRoute('GET', '/grades/{id}', 'GradeController', 'show', true);
        $this->addRoute('POST', '/grades', 'GradeController', 'store', true);
        $this->addRoute('PUT', '/grades/{id}', 'GradeController', 'update', true);
        $this->addRoute('DELETE', '/grades/{id}', 'GradeController', 'destroy', true);
        
        // 成绩修改申请路由
        $this->addRoute('GET', '/grade-change-requests', 'GradeChangeRequestController', 'index', true);
        $this->addRoute('GET', '/grade-change-requests/{id}', 'GradeChangeRequestController', 'show', true);
        $this->addRoute('POST', '/grade-change-requests', 'GradeChangeRequestController', 'store', true);
        $this->addRoute('PUT', '/grade-change-requests/{id}/approve', 'GradeChangeRequestController', 'approve', true);
        $this->addRoute('PUT', '/grade-change-requests/{id}/reject', 'GradeChangeRequestController', 'reject', true);
        $this->addRoute('PUT', '/grade-change-requests/{id}/resubmit', 'GradeChangeRequestController', 'resubmit', true);
        
        // 统计分析路由
        $this->addRoute('GET', '/statistics/overview', 'StatisticsController', 'overview', true);
        $this->addRoute('GET', '/statistics/class/{classId}', 'StatisticsController', 'classStats', true);
        $this->addRoute('GET', '/statistics/course/{courseId}', 'StatisticsController', 'courseStats', true);
        $this->addRoute('GET', '/statistics/student/{studentId}', 'StatisticsController', 'studentStats', true);
        $this->addRoute('GET', '/statistics/ranking', 'StatisticsController', 'ranking', true);
        $this->addRoute('GET', '/statistics/distribution', 'StatisticsController', 'distribution', true);
        $this->addRoute('GET', '/statistics/trend', 'StatisticsController', 'trend', true);
    }
    
    /**
     * 添加路由
     */
    private function addRoute(string $method, string $path, string $controller, string $action, bool $auth = false): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'auth' => $auth
        ];
    }
    
    /**
     * 分发请求
     */
    public function dispatch(string $method, string $uri): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $params = $this->matchRoute($route['path'], $uri);
            if ($params !== false) {
                // 认证检查
                if ($route['auth']) {
                    $user = AuthMiddleware::handle();
                    if (!$user) {
                        return;
                    }
                    $params['_user'] = $user;
                }
                
                // 执行控制器
                $this->executeController($route['controller'], $route['action'], $params);
                return;
            }
        }
        
        Response::error('接口不存在', 404);
    }
    
    /**
     * 匹配路由
     */
    private function matchRoute(string $pattern, string $uri): array|false
    {
        // 将路由模式转换为正则
        $regex = preg_replace('/\{([a-zA-Z]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        if (preg_match($regex, $uri, $matches)) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }
        
        return false;
    }
    
    /**
     * 执行控制器
     */
    private function executeController(string $controller, string $action, array $params): void
    {
        $controllerClass = "App\\Controllers\\{$controller}";
        
        if (!class_exists($controllerClass)) {
            Logger::error("Controller not found: {$controllerClass}");
            Response::error('控制器不存在', 500);
            return;
        }
        
        $instance = new $controllerClass();
        
        if (!method_exists($instance, $action)) {
            Logger::error("Action not found: {$controller}::{$action}");
            Response::error('方法不存在', 500);
            return;
        }
        
        $instance->$action($params);
    }
}
