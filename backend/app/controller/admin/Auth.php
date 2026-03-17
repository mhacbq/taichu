<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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
        $username = trim((string) $request->post('username', ''));
        $password = (string) $request->post('password', '');

        if ($username === '' || $password === '') {
            return $this->error('用户名和密码不能为空', 422);
        }

        $adminTable = $this->resolveAdminTable();
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

        $columns = $this->getTableColumns($adminTable);
        if (isset($columns['last_login_at'])) {
            Db::table($adminTable)
                ->where('id', (int) $admin['id'])
                ->update(['last_login_at' => date('Y-m-d H:i:s')]);
        }

        $roles = $this->fetchAdminRoles((int) $admin['id']);
        $payload = [
            'iss' => 'taichu-admin',
            'iat' => time(),
            'exp' => time() + 86400,
            'sub' => (int) $admin['id'],
            'username' => (string) ($admin['username'] ?? $username),
            'roles' => $roles,
            'is_admin' => true,
        ];

        $token = JWT::encode($payload, $this->jwtKey, 'HS256');

        return $this->success([
            'token' => $token,
            'expires_in' => 86400,
            'admin' => [
                'id' => (int) $admin['id'],
                'username' => (string) ($admin['username'] ?? $username),
                'nickname' => (string) (($admin['nickname'] ?? '') ?: ($admin['username'] ?? $username)),
                'roles' => $roles,
            ],
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
            $roles = is_array($decoded->roles ?? null) ? $decoded->roles : ['admin'];

            return $this->success([
                'id' => (int) $decoded->sub,
                'username' => (string) $decoded->username,
                'avatar' => '',
                'roles' => $roles,
                'permissions' => ['*'],
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

        return 'taichu_admin_default_secret_2024';
    }

    /**
     * 解析管理员表名
     */
    private function resolveAdminTable(): ?string
    {
        foreach (['tc_admin', 'admin'] as $table) {
            if ($this->tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    /**
     * 查询管理员角色
     */
    private function fetchAdminRoles(int $adminId): array
    {
        if ($adminId <= 0 || !$this->tableExists('tc_admin_user_role') || !$this->tableExists('tc_admin_role')) {
            return ['admin'];
        }

        $rows = Db::table('tc_admin_user_role')
            ->alias('aur')
            ->leftJoin('tc_admin_role ar', 'ar.id = aur.role_id')
            ->where('aur.admin_id', $adminId)
            ->column('ar.code');

        $roles = array_values(array_unique(array_filter(array_map('strval', $rows))));

        return $roles ?: ['admin'];
    }

    /**
     * 判断表是否存在
     */
    private function tableExists(string $table): bool
    {
        $escapedTable = addslashes($table);
        return !empty(Db::query("SHOW TABLES LIKE '{$escapedTable}'"));
    }

    /**
     * 读取表字段
     */
    private function getTableColumns(string $table): array
    {
        $escapedTable = str_replace('`', '``', $table);
        $columns = [];

        foreach (Db::query("SHOW COLUMNS FROM `{$escapedTable}`") as $column) {
            $field = (string) ($column['Field'] ?? '');
            if ($field !== '') {
                $columns[$field] = true;
            }
        }

        return $columns;
    }
}
