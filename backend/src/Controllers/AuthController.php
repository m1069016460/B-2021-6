<?php
/**
 * 认证控制器
 */

namespace App\Controllers;

use Firebase\JWT\JWT;
use App\Config\App;
use App\Models\User;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;

class AuthController
{
    private User $userModel;
    
    public function __construct()
    {
        $this->userModel = new User();
    }
    
    /**
     * 用户登录
     */
    public function login(): void
    {
        $data = $this->getInput();
        
        // 验证输入
        $validator = new Validator($data);
        $validator->required('username', '用户名不能为空')
                  ->required('password', '密码不能为空');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        // 查找用户
        $user = $this->userModel->findByUsername($data['username']);
        
        if (!$user) {
            Response::error('用户名或密码错误', 401);
            return;
        }
        
        // 验证密码
        if (!password_verify($data['password'], $user['password'])) {
            Response::error('用户名或密码错误', 401);
            return;
        }
        
        // 检查用户状态
        if ($user['status'] != 1) {
            Response::error('账户已被禁用', 403);
            return;
        }
        
        // 生成Token
        $token = $this->generateToken($user);
        
        Logger::info("User logged in: {$user['username']}");
        
        Response::success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'realName' => $user['real_name'],
                'role' => $user['role'],
                'phone' => $user['phone'],
                'avatar' => $user['avatar']
            ]
        ], '登录成功');
    }
    
    /**
     * 用户注册
     */
    public function register(): void
    {
        $data = $this->getInput();
        
        // 验证输入
        $validator = new Validator($data);
        $validator->required('username', '用户名不能为空')
                  ->minLength('username', 3, '用户名至少3个字符')
                  ->maxLength('username', 50, '用户名最多50个字符')
                  ->required('password', '密码不能为空')
                  ->minLength('password', 6, '密码至少6个字符')
                  ->email('email', '邮箱格式不正确')
                  ->phone('phone', '手机号格式不正确');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        // 检查用户名是否存在
        if ($this->userModel->exists('username', $data['username'])) {
            Response::error('用户名已存在', 400);
            return;
        }
        
        // 创建用户
        $userId = $this->userModel->create([
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'real_name' => $data['realName'] ?? $data['username'],
            'role' => 'teacher', // 默认为教师角色
            'status' => 1
        ]);
        
        Logger::info("User registered: {$data['username']}");
        
        Response::success(['id' => $userId], '注册成功');
    }
    
    /**
     * 获取当前用户信息
     */
    public function profile(array $params): void
    {
        $user = $params['_user'];
        $userInfo = $this->userModel->find($user['id']);
        
        if (!$userInfo) {
            Response::error('用户不存在', 404);
            return;
        }
        
        unset($userInfo['password']);
        
        Response::success([
            'id' => $userInfo['id'],
            'username' => $userInfo['username'],
            'email' => $userInfo['email'],
            'phone' => $userInfo['phone'],
            'realName' => $userInfo['real_name'],
            'role' => $userInfo['role'],
            'avatar' => $userInfo['avatar'],
            'status' => $userInfo['status'],
            'createdAt' => $userInfo['created_at']
        ]);
    }
    
    /**
     * 更新个人信息
     */
    public function updateProfile(array $params): void
    {
        $user = $params['_user'];
        $data = $this->getInput();
        
        // 验证输入
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
        if (isset($data['avatar'])) {
            $updateData['avatar'] = $data['avatar'];
        }
        
        if (!empty($updateData)) {
            $this->userModel->update($user['id'], $updateData);
        }
        
        Logger::info("User updated profile: {$user['username']}");
        
        Response::success(null, '更新成功');
    }
    
    /**
     * 修改密码
     */
    public function changePassword(array $params): void
    {
        $user = $params['_user'];
        $data = $this->getInput();
        
        // 验证输入
        $validator = new Validator($data);
        $validator->required('oldPassword', '原密码不能为空')
                  ->required('newPassword', '新密码不能为空')
                  ->minLength('newPassword', 6, '新密码至少6个字符');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        // 获取当前用户
        $userInfo = $this->userModel->find($user['id']);
        
        // 验证原密码
        if (!password_verify($data['oldPassword'], $userInfo['password'])) {
            Response::error('原密码错误', 400);
            return;
        }
        
        // 更新密码
        $this->userModel->update($user['id'], [
            'password' => password_hash($data['newPassword'], PASSWORD_DEFAULT)
        ]);
        
        Logger::info("User changed password: {$user['username']}");
        
        Response::success(null, '密码修改成功');
    }
    
    /**
     * 生成JWT Token
     */
    private function generateToken(array $user): string
    {
        $payload = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + App::JWT_EXPIRY
        ];
        
        return JWT::encode($payload, App::getJwtSecret(), 'HS256');
    }
    
    /**
     * 获取输入数据
     */
    private function getInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
}
