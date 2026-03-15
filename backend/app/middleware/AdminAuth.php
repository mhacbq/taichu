<?php
declare(strict_types=1);

namespace app\middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\Request;
use think\Response;

/**
 * 后台管理认证中间件
 */
class AdminAuth
{
    // JWT密钥
    protected $jwtKey = 'your-admin-jwt-secret-key-change-in-production';
    
    public function handle(Request $request, \Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return json(['code' => 401, 'message' => '未授权，请先登录']);
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
            return json(['code' => 401, 'message' => '登录已过期，请重新登录']);
        } catch (\Exception $e) {
            return json(['code' => 401, 'message' => '无效的Token']);
        }
    }
    
    /**
     * 记录操作日志
     */
    protected function logOperation(Request $request)
    {
        $data = [
            'admin_id' => $request->adminUser['id'] ?? 0,
            'admin_name' => $request->adminUser['username'] ?? '',
            'module' => $request->controller(),
            'action' => $request->action(),
            'method' => $request->method(),
            'url' => $request->url(),
            'ip' => $request->ip(),
            'params' => json_encode($request->param()),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // 异步记录日志（可以使用队列）
        // \think\facade\Queue::push('app\job\OperationLog', $data);
    }
}
