<?php
/**
 * 用户管理控制器
 */

namespace App\Controllers;

use App\Models\User;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;
use App\Middleware\AuthMiddleware;

class UserController
{
    private User $userModel;
    
    public function __construct()
    {
        $this->userModel = new User();
    }
    
    /**
     * 获取用户列表
     */
    public function index(array $params): void
    {
        // 检查管理员权限
        if (!AuthMiddleware::isAdmin($params['_user'])) {
            Response::error('没有权限访问', 403);
            return;
        }
        
        $page = (int) ($_GET['page'] ?? 1);
        $pageSize = (int) ($_GET['pageSize'] ?? 20);
        $keyword = $_GET['keyword'] ?? null;
        $role = $_GET['role'] ?? null;
        
        $result = $this->userModel->search($page, $pageSize, $keyword, $role);
        
        Response::paginate($result['items'], $result['total'], $result['page'], $result['pageSize']);
    }
    
    /**
     * 获取用户详情
     */
    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $user = $this->userModel->find($id);
        
        if (!$user) {
            Response::error('用户不存在', 404);
            return;
        }
        
        unset($user['password']);
        Response::success($user);
    }
    
    /**
     * 创建用户
     */
    public function store(array $params): void
    {
        if (!AuthMiddleware::isAdmin($params['_user'])) {
            Response::error('没有权限操作', 403);
            return;
        }
        
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('username', '用户名不能为空')
                  ->minLength('username', 3, '用户名至少3个字符')
                  ->required('password', '密码不能为空')
                  ->minLength('password', 6, '密码至少6个字符')
                  ->email('email', '邮箱格式不正确')
                  ->phone('phone', '手机号格式不正确')
                  ->in('role', ['admin', 'teacher'], '角色值无效');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        if ($this->userModel->exists('username', $data['username'])) {
            Response::error('用户名已存在', 400);
            return;
        }
        
        $userId = $this->userModel->create([
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'real_name' => $data['realName'] ?? $data['username'],
            'role' => $data['role'] ?? 'teacher',
            'status' => $data['status'] ?? 1
        ]);
        
        Logger::info("Admin created user: {$data['username']}");
        
        Response::success(['id' => $userId], '创建成功');
    }
    
    /**
     * 更新用户
     */
    public function update(array $params): void
    {
        $currentUser = $params['_user'];
        $id = (int) $params['id'];
        
        // 只有管理员能修改其他用户，且不能修改自己的角色
        if (!AuthMiddleware::isAdmin($currentUser) && $currentUser['id'] !== $id) {
            Response::error('没有权限操作', 403);
            return;
        }
        
        $user = $this->userModel->find($id);
        if (!$user) {
            Response::error('用户不存在', 404);
            return;
        }
        
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->email('email', '邮箱格式不正确')
                  ->phone('phone', '手机号格式不正确');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        $updateData = [];
        
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        if (isset($data['phone'])) {
            $updateData['phone'] = $data['phone'];
        }
        if (isset($data['realName'])) {
            $updateData['real_name'] = $data['realName'];
        }
        
        // 只有管理员可以修改角色和状态
        if (AuthMiddleware::isAdmin($currentUser)) {
            // 管理员不能修改自己的角色和状态
            if ($currentUser['id'] !== $id) {
                if (isset($data['role'])) {
                    $updateData['role'] = $data['role'];
                }
                if (isset($data['status'])) {
                    $updateData['status'] = $data['status'];
                }
            }
            
            // 修改密码
            if (!empty($data['password'])) {
                $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
        }
        
        if (!empty($updateData)) {
            $this->userModel->update($id, $updateData);
        }
        
        Logger::info("User updated: {$user['username']}");
        
        Response::success(null, '更新成功');
    }
    
    /**
     * 删除用户
     */
    public function destroy(array $params): void
    {
        $currentUser = $params['_user'];
        
        if (!AuthMiddleware::isAdmin($currentUser)) {
            Response::error('没有权限操作', 403);
            return;
        }
        
        $id = (int) $params['id'];
        
        // 不能删除自己
        if ($currentUser['id'] === $id) {
            Response::error('不能删除当前登录用户', 400);
            return;
        }
        
        $user = $this->userModel->find($id);
        if (!$user) {
            Response::error('用户不存在', 404);
            return;
        }
        
        $this->userModel->delete($id);
        
        Logger::info("Admin deleted user: {$user['username']}");
        
        Response::success(null, '删除成功');
    }
    
    private function getInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
}
