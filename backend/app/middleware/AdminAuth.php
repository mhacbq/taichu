<?php
declare(strict_types=1);

namespace app\middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\Request;
use think\Response;
use think\facade\Env;

/**
 * 后台管理认证中间件
 */
class AdminAuth
{
    // JWT密钥
    protected $jwtKey;
    
    public function __construct()
    {
        $this->jwtKey = Env::get('ADMIN_JWT_SECRET');
        
        if (empty($this->jwtKey)) {
            throw new \Exception('ADMIN_JWT_SECRET environment variable is not set');
        }
    }
    
    public function handle(Request $request, \Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return json([
                'code' => 200,
                'message' => '未授权，请先登录',
                'data' => null
            ], 401);
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = JWT::decode($token, new Key($this->jwtKey, 'HS256'));
            
            // 将用户信息附加到请求
            $request->adminUser = [
                'id' => $decoded->sub,
                'username' => $decoded->username,
                'roles' => $decoded->roles
            ];
            
            // 记录操作日志
            $this->logOperation($request);
            
            return $next($request);
        } catch (\Firebase\JWT\ExpiredException $e) {
            return json([
                'code' => 200,
                'message' => '登录已过期，请重新登录',
                'data' => null
            ], 401);
        } catch (\Exception $e) {
            return json([
                'code' => 200,
                'message' => '无效的Token',
                'data' => null
            ], 401);
        }
    }
    
    /**
     * 记录操作日志
     */
    protected function logOperation(Request $request)
    {
        // 过滤敏感字段
        $params = $request->param();
        $sensitiveFields = ['password', 'pwd', 'token', 'secret', 'key', 'authorization'];
        foreach ($sensitiveFields as $field) {
            if (isset($params[$field])) {
                $params[$field] = '***';
            }
        }
        
        $data = [
            'admin_id' => $request->adminUser['id'] ?? 0,
            'admin_name' => $request->adminUser['username'] ?? '',
            'module' => $request->controller(),
            'action' => $request->action(),
            'method' => $request->method(),
            'url' => $request->url(),
            'ip' => $request->ip(),
            'params' => json_encode($params),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // 写入数据库
        try {
            \think\facade\Db::name('admin_log')->insert($data);
        } catch (\Exception $e) {
            trace('Admin operation log failed: ' . $e->getMessage(), 'error');
        }
    }
}
