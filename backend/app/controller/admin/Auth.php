<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\AdminAuthService;
use app\service\SchemaInspector;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Env;
use think\facade\Log;
use think\Request;

/**
 * 后台认证控制器
 */
class Auth extends BaseController
{
    protected string $jwtKey = '';

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->jwtKey = $this->resolveJwtKey();
    }

    /**
     * 管理员登录
     */
    public function login(Request $request)
    {
        if ($this->jwtKey === '') {
            return $this->error('后台登录配置缺失，请联系管理员', 500);
        }

        $username = trim((string) $request->post('username', ''));
        $password = (string) $request->post('password', '');

        if ($username === '' || $password === '') {
            return $this->error('用户名和密码不能为空', 422);
        }

        $adminTable = AdminAuthService::resolveAdminTable();
        if ($adminTable === null) {
            Log::error('后台登录失败：管理员账号表不存在', [
                'username' => $username,
            ]);
            return $this->error('管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql', 500);
        }

        $admin = Db::table($adminTable)
            ->where('username', $username)
            ->where('status', 1)
            ->find();

        if (!$admin || !password_verify($password, (string) ($admin['password'] ?? ''))) {
            return $this->error('用户名或密码错误', 401);
        }

        $passwordHash = (string) ($admin['password'] ?? '');
        if ($passwordHash !== '' && password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            Db::table($adminTable)
                ->where('id', (int) $admin['id'])
                ->update(['password' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        $columns = SchemaInspector::getTableColumns($adminTable);
        $updatePayload = [];
        if (isset($columns['last_login_at'])) {
            $updatePayload['last_login_at'] = date('Y-m-d H:i:s');
        }
        if (isset($columns['last_login_ip'])) {
            $updatePayload['last_login_ip'] = (string) $request->ip();
        }
        if (!empty($updatePayload)) {
            Db::table($adminTable)
                ->where('id', (int) $admin['id'])
                ->update($updatePayload);
        }

        $adminId = (int) $admin['id'];

        // 登录时主动清除旧的权限缓存，确保获取到最新角色/权限
        AdminAuthService::clearPermissionCache($adminId);

        $roles = $this->normalizeClientRoles(AdminAuthService::getAdminRoleCodes($adminId));
        $payload = [
            'iss' => 'taichu-admin',
            'iat' => time(),
            'exp' => time() + 86400,
            'jti' => bin2hex(random_bytes(16)),  // 唯一Token ID，用于黑名单精确识别
            'sub' => $adminId,
            'username' => (string) ($admin['username'] ?? $username),
            'roles' => $roles,
            'is_admin' => true,
        ];

        $token = JWT::encode($payload, $this->jwtKey, 'HS256');

        return $this->success([
            'token' => $token,
            'expires_in' => 86400,
            'admin' => [
                'id' => $adminId,
                'username' => (string) ($admin['username'] ?? $username),
                'nickname' => (string) (($admin['nickname'] ?? '') ?: ($admin['username'] ?? $username)),
                'roles' => $roles,
                'role' => $roles[0] ?? '',
            ],
        ], '登录成功');
    }

    /**
     * 获取管理员信息
     */
    public function info(Request $request)
    {
        $adminUser = $request->adminUser ?? [];
        $adminId = isset($adminUser['id']) ? (int) $adminUser['id'] : 0;
        if ($adminId <= 0) {
            return $this->error('未授权', 401);
        }

        $admin = AdminAuthService::findActiveAdmin($adminId);
        if (!$admin) {
            return $this->error('管理员账号不存在或已停用', 401);
        }

        $roles = $this->normalizeClientRoles(AdminAuthService::getAdminRoleCodes($adminId));
        $permissions = AdminAuthService::getAdminPermissions($adminId);

        return $this->success([
            'id' => $adminId,
            'username' => (string) (($admin['username'] ?? '') ?: ($adminUser['username'] ?? '')),
            'nickname' => (string) (($admin['nickname'] ?? '') ?: ($admin['username'] ?? '') ?: ($adminUser['username'] ?? '')),
            'avatar' => (string) ($admin['avatar'] ?? ''),
            'roles' => $roles,
            'role' => $roles[0] ?? '',
            'permissions' => $permissions,
            'status' => (int) ($admin['status'] ?? 1),
        ], '获取成功');
    }

    /**
     * 退出登录
     *
     * 将当前 Token 加入黑名单缓存，确保注销后 Token 立即失效。
     * 缓存 TTL 与 Token 剩余有效期一致，避免黑名单无限增长。
     */
    public function logout(Request $request)
    {
        $authHeader = $request->header('Authorization', '');
        $token = preg_replace('/^Bearer\s+/i', '', trim((string) $authHeader));

        if ($token !== '' && $this->jwtKey !== '') {
            try {
                $payload = (array) \Firebase\JWT\JWT::decode($token, new Key($this->jwtKey, 'HS256'));
                $jti = $payload['jti'] ?? md5($token);
                $exp = (int) ($payload['exp'] ?? 0);
                $ttl = max(60, $exp - time());
                Cache::set("admin_token_blacklist:{$jti}", 1, $ttl);
                Log::info('管理员退出，Token已加入黑名单', [
                    'jti' => $jti,
                    'exp' => $exp,
                    'admin_id' => $payload['sub'] ?? 0,
                ]);
            } catch (\Throwable $e) {
                Log::debug('logout: Token解析失败', ['error' => $e->getMessage()]);
            }
        }

        return $this->success(null, '退出成功');
    }

    /**
     * 解析后台 JWT 密钥，兼容 ADMIN_JWT_SECRET 与 JWT_SECRET
     */
    private function resolveJwtKey(): string
    {
        foreach (['ADMIN_JWT_SECRET', 'JWT_SECRET'] as $envKey) {
            $value = trim((string) Env::get($envKey, ''));
            if ($value !== '') {
                return $value;
            }
        }

        Log::error('后台登录缺少 JWT 密钥配置，请设置 ADMIN_JWT_SECRET 或 JWT_SECRET');
        return '';
    }

    /**
     * 归一化返回给前端的角色编码，统一到 admin / operator 口径
     */
    private function normalizeClientRoles(array $roles): array
    {
        $normalized = [];
        foreach ($roles as $role) {
            $code = $this->mapRoleCodeForClient((string) $role);
            if ($code !== '' && !in_array($code, $normalized, true)) {
                $normalized[] = $code;
            }
        }

        return $normalized;
    }

    /**
     * 角色编码映射
     */
    private function mapRoleCodeForClient(string $role): string
    {
        $role = strtolower(trim($role));

        return match ($role) {
            'super_admin', 'admin' => 'admin',
            'operator', 'normal_admin', 'customer_service' => 'operator',
            default => $role,
        };
    }
}
