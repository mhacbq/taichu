<?php
declare(strict_types=1);

namespace app\middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Db;
use think\facade\Env;
use think\facade\Log;
use think\Request;
use think\Response;

/**
 * 后台管理认证中间件
 */
class AdminAuth
{
    protected string $jwtKey = '';

    public function __construct()
    {
        $this->jwtKey = $this->resolveJwtKey();
    }

    public function handle(Request $request, \Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return json([
                'code' => 401,
                'message' => '未授权，请先登录',
                'data' => null,
            ], 401);
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = JWT::decode($token, new Key($this->jwtKey, 'HS256'));

            $request->adminUser = [
                'id' => (int) $decoded->sub,
                'username' => (string) $decoded->username,
                'roles' => is_array($decoded->roles ?? null) ? $decoded->roles : ['admin'],
            ];

            $this->logOperation($request);

            return $next($request);
        } catch (\Firebase\JWT\ExpiredException $e) {
            return json([
                'code' => 401,
                'message' => '登录已过期，请重新登录',
                'data' => null,
            ], 401);
        } catch (\Exception $e) {
            Log::warning('后台 Token 校验失败', [
                'url' => $request->url(),
                'error' => $e->getMessage(),
            ]);

            return json([
                'code' => 401,
                'message' => '无效的Token',
                'data' => null,
            ], 401);
        }
    }

    /**
     * 记录操作日志
     */
    protected function logOperation(Request $request): void
    {
        $table = $this->resolveAdminLogTable();
        if ($table === null) {
            return;
        }

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
            'created_at' => date('Y-m-d H:i:s'),
        ];

        try {
            Db::table($table)->insert($data);
        } catch (\Exception $e) {
            Log::error('后台操作日志写入失败', [
                'url' => $request->url(),
                'method' => $request->method(),
                'error' => $e->getMessage(),
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

    /**
     * 解析后台 JWT 密钥，兼容 ADMIN_JWT_SECRET 与 JWT_SECRET
     */
    protected function resolveJwtKey(): string
    {
        foreach (['ADMIN_JWT_SECRET', 'JWT_SECRET'] as $envKey) {
            $value = trim((string) Env::get($envKey, ''));
            if ($value !== '') {
                return $value;
            }
        }

        Log::warning('后台鉴权未配置独立 JWT 密钥，已回退到开发默认值');
        return 'taichu_admin_default_secret_2024';
    }

    /**
     * 解析后台操作日志表
     */
    protected function resolveAdminLogTable(): ?string
    {
        foreach (['tc_admin_log', 'admin_log'] as $table) {
            if ($this->tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    /**
     * 判断表是否存在
     */
    protected function tableExists(string $table): bool
    {
        $escapedTable = addslashes($table);
        return !empty(Db::query("SHOW TABLES LIKE '{$escapedTable}'"));
    }
}
