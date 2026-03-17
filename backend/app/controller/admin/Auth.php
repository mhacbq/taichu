<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\Request;
use think\facade\Env;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * 后台认证控制器
 */
class Auth extends BaseController
{
    // JWT密钥
    protected $jwtKey;
    
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->jwtKey = Env::get('ADMIN_JWT_SECRET');
        if (empty($this->jwtKey)) {
            $this->jwtKey = 'taichu_admin_default_secret_2024'; // 默认密钥，建议生产环境环境变量覆盖
        }
    }
    
    /**
     * 管理员登录
     */
    public function login(Request $request)
    {
        $username = $request->post('username');
        $password = $request->post('password');

        // 验证管理员账号（从数据库验证）
        $admin = \think\facade\Db::name('admin')
            ->where('username', $username)
            ->where('status', 1)
            ->find();
        
        if (!$admin || !password_verify($password, $admin['password'])) {
            return $this->error('用户名或密码错误', 401);
        }

        // 生成JWT Token
        $payload = [
            'iss' => 'taichu-admin',
            'iat' => time(),
            'exp' => time() + 86400, // 24小时过期
            'sub' => $admin['id'],
            'username' => $username,
            'roles' => ['admin'],
            'is_admin' => true
        ];

        $token = JWT::encode($payload, $this->jwtKey, 'HS256');

        return $this->success([
            'token' => $token,
            'expires_in' => 86400
        ], '登录成功');
    }

    /**
     * 获取管理员信息
     */
    public function info(Request $request)
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->error('未授权', 401);
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = JWT::decode($token, new Key($this->jwtKey, 'HS256'));
            
            return $this->success([
                'id' => $decoded->sub,
                'username' => $decoded->username,
                'avatar' => '',
                'roles' => $decoded->roles,
                'permissions' => ['*']
            ], '获取成功');
        } catch (\Exception $e) {
            return $this->error('Token无效', 401);
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        return $this->success(null, '退出成功');
    }
}
