<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use think\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * 后台认证控制器
 */
class AdminAuth extends BaseController
{
    // JWT密钥
    protected $jwtKey = 'your-admin-jwt-secret-key-change-in-production';
    
    /**
     * 管理员登录
     */
    public function login(Request $request)
    {
        $username = $request->post('username');
        $password = $request->post('password');

        // 验证管理员账号（实际应从数据库验证）
        if ($username !== 'admin' || $password !== 'admin123') {
            return json(['code' => 401, 'message' => '用户名或密码错误']);
        }

        // 生成JWT Token
        $payload = [
            'iss' => 'taichu-admin',
            'iat' => time(),
            'exp' => time() + 86400, // 24小时过期
            'sub' => 1,
            'username' => $username,
            'roles' => ['admin'],
            'is_admin' => true
        ];

        $token = JWT::encode($payload, $this->jwtKey, 'HS256');

        return json([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'expires_in' => 86400
            ]
        ]);
    }

    /**
     * 获取管理员信息
     */
    public function info(Request $request)
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return json(['code' => 401, 'message' => '未授权']);
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = JWT::decode($token, new Key($this->jwtKey, 'HS256'));
            
            return json([
                'code' => 200,
                'data' => [
                    'id' => $decoded->sub,
                    'username' => $decoded->username,
                    'avatar' => '',
                    'roles' => $decoded->roles,
                    'permissions' => ['*']
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 401, 'message' => 'Token无效']);
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        return json(['code' => 200, 'message' => '退出成功']);
    }
}
