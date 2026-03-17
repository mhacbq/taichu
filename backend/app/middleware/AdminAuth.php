<?php
declare(strict_types=1);

namespace app\middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\Request;
use think\Response;
use think\facade\Env;
use think\facade\Log;

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
                'code' => 401,
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
                'code' => 401,
                'message' => '登录已过期，请重新登录',
                'data' => null
            ], 401);
        } catch (\Exception $e) {
            return json([
                'code' => 401,
                'message' => '无效的Token',
                'data' => null
            ], 401);
        }
    }
    
    /**
     * 记录操作日志
     */
    protected function logOperation(Request $request): void
    {
        $params = $this->sanitizeParams($request->param());

        $data = [
            'admin_id' => $request->adminUser['id'] ?? 0,
            'admin_name' => $request->adminUser['username'] ?? '',
            'module' => $request->controller(),
            'action' => $request->action(),
            'method' => $request->method(),
            'url' => $request->url(),
            'ip' => $request->ip(),
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            \think\facade\Db::name('admin_log')->insert($data);
        } catch (\Exception $e) {
            Log::error('后台操作日志写入失败: ' . $e->getMessage(), [
                'url' => $request->url(),
                'method' => $request->method(),
            ]);
        }
    }

    /**
     * 递归脱敏请求参数
     */
    protected function sanitizeParams(mixed $value, ?string $field = null): mixed
    {
        if ($field !== null && $this->isSensitiveField($field)) {
            return '***';
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = $this->sanitizeParams($item, is_string($key) ? $key : null);
            }
            return $sanitized;
        }

        if (is_object($value)) {
            return $this->sanitizeParams(get_object_vars($value), $field);
        }

        return $value;
    }

    /**
     * 判断字段名是否包含敏感信息
     */
    protected function isSensitiveField(string $field): bool
    {
        $normalized = strtolower($field);
        $compact = preg_replace('/[^a-z0-9]/', '', $normalized) ?: '';

        if (in_array($compact, ['password', 'pwd', 'token', 'secret', 'key', 'authorization'], true)) {
            return true;
        }

        foreach (['secret', 'token', 'apikey', 'appkey', 'privatekey', 'publickey', 'accesskey', 'secretid', 'cert', 'pem', 'signature'] as $keyword) {
            if (str_contains($compact, $keyword)) {
                return true;
            }
        }

        return false;
    }
}

