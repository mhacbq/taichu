<?php
declare(strict_types=1);

namespace app\middleware;

use app\service\AdminAuthService;
use app\service\SchemaInspector;
use app\service\SensitiveDataSanitizer;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Cache;
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
        // ── IP 白名单检测（可选，配置 ADMIN_IP_WHITELIST=x.x.x.x,y.y.y.y 启用）──
        $ipWhitelistRaw = trim((string) \think\facade\Env::get('ADMIN_IP_WHITELIST', ''));
        if ($ipWhitelistRaw !== '') {
            $allowedIps = array_filter(array_map('trim', explode(',', $ipWhitelistRaw)));
            $clientIp   = (string) $request->ip();
            if (!empty($allowedIps) && !in_array($clientIp, $allowedIps, true)) {
                Log::warning('管理后台 IP 访问被拒绝', ['ip' => $clientIp, 'url' => $request->url()]);
                return json(['code' => 403, 'message' => '访问被拒绝', 'data' => null], 403);
            }
        }

        // ── Authorization 头校验 ──────────────────────────────────────────────
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return json([
                'code' => 401,
                'message' => '未授权，请先登录',
                'data' => null,
            ], 401);
        }

        if ($this->jwtKey === '') {
            return json([
                'code' => 500,
                'message' => '后台鉴权配置缺失，请联系管理员',
                'data' => null,
            ], 500);
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = JWT::decode($token, new Key($this->jwtKey, 'HS256'));
            $adminId = isset($decoded->sub) ? (int) $decoded->sub : 0;
            $issuer = trim((string) ($decoded->iss ?? ''));
            $isAdmin = (bool) ($decoded->is_admin ?? false);

            if ($adminId <= 0 || $issuer !== 'taichu-admin' || !$isAdmin) {
                throw new \UnexpectedValueException('后台 Token 声明无效');
            }

            // ── 黑名单校验：检查 Token 是否已被注销 ──────────────────────────
            $jti = $decoded->jti ?? md5($token);
            if (Cache::get("admin_token_blacklist:{$jti}")) {
                return json([
                    'code' => 401,
                    'message' => '登录已过期，请重新登录',
                    'data' => null,
                ], 401);
            }

            $admin = AdminAuthService::findActiveAdmin($adminId);
            if (!$admin) {
                return json([
                    'code' => 401,
                    'message' => '管理员账号不存在或已停用',
                    'data' => null,
                ], 401);
            }

            $roles = $this->normalizeClientRoles(AdminAuthService::getAdminRoleCodes($adminId));
            $permissions = AdminAuthService::getAdminPermissions($adminId);

            $request->adminUser = [
                'id' => $adminId,
                'username' => (string) (($admin['username'] ?? '') ?: ($decoded->username ?? '')),
                'nickname' => (string) (($admin['nickname'] ?? '') ?: ($admin['username'] ?? '') ?: ($decoded->username ?? '')),
                'roles' => $roles,
                'role' => $roles[0] ?? '',
                'permissions' => $permissions,
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

        $params = SensitiveDataSanitizer::getFilteredRequestParams($request);
        $detail = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($detail === false) {
            $detail = '{}';
        }

        $columns = SchemaInspector::getTableColumns($table);
        $data = [
            'admin_id' => $request->adminUser['id'] ?? 0,
            'admin_name' => $request->adminUser['username'] ?? '',
            'module' => (string) $request->controller(),
            'action' => (string) $request->action(),
            'ip' => (string) $request->ip(),
        ];

        if (isset($columns['detail'])) {
            $data['detail'] = $detail;
        } elseif (isset($columns['params'])) {
            $data['params'] = $detail;
        }

        $requestPath = SensitiveDataSanitizer::sanitizeRequestPath((string) $request->url());
        if (isset($columns['request_url'])) {
            $data['request_url'] = $requestPath;
        } elseif (isset($columns['url'])) {
            $data['url'] = $requestPath;
        }

        if (isset($columns['request_method'])) {
            $data['request_method'] = (string) $request->method();
        } elseif (isset($columns['method'])) {
            $data['method'] = (string) $request->method();
        }

        if (isset($columns['user_agent'])) {
            $data['user_agent'] = (string) $request->header('User-Agent', '');
        }

        if (isset($columns['status'])) {
            $data['status'] = 1;
        }

        if (isset($columns['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        try {
            Db::table($table)->insert($data);
        } catch (\Exception $e) {
            Log::error('后台操作日志写入失败', [
                'path' => SensitiveDataSanitizer::sanitizeRequestPath((string) $request->url()),
                'method' => $request->method(),
                'table' => $table,
                'error' => $e->getMessage(),
            ]);
        }
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

        Log::error('后台鉴权缺少 JWT 密钥配置，请设置 ADMIN_JWT_SECRET 或 JWT_SECRET');
        return '';
    }

    /**
     * 解析后台操作日志表
     */
    protected function resolveAdminLogTable(): ?string
    {
        foreach (['tc_admin_log', 'admin_log'] as $table) {
            if (SchemaInspector::tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    /**
     * 统一对外角色编码，兼容 super_admin / normal_admin / customer_service 等历史值
     */
    protected function normalizeClientRoles(array $roles): array
    {
        $normalized = [];
        foreach ($roles as $role) {
            $code = match (strtolower(trim((string) $role))) {
                'super_admin', 'admin' => 'admin',
                'normal_admin', 'operator', 'customer_service' => 'operator',
                default => strtolower(trim((string) $role)),
            };

            if ($code !== '' && !in_array($code, $normalized, true)) {
                $normalized[] = $code;
            }
        }

        return $normalized;
    }
}
