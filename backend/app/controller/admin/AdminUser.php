<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\AdminAuthService;
use app\service\SchemaInspector;
use think\Request;
use think\facade\Log;
use think\facade\Db;

/**
 * 管理员账号管理控制器
 */
class AdminUser extends BaseController
{
    /**
     * 获取管理员列表
     */
    public function getAdminUsers(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限查看管理员列表', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', 20)
            );
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $keyword = trim((string) $request->get('keyword', ''));
            $status = $request->get('status', '');

            $adminTable = $this->resolveCompatibleTable(['tc_admin', 'admin'], 'tc_admin');
            if (!SchemaInspector::tableExists($adminTable)) {
                return $this->error('管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql', 500);
            }

            if (!SchemaInspector::tableExists('tc_admin_role') || !SchemaInspector::tableExists('tc_admin_user_role')) {
                return $this->error('管理员角色表不存在，请先执行 database/20260317_create_admin_users_table.sql', 500);
            }

            $columns = SchemaInspector::getTableColumns($adminTable);
            $nicknameField = isset($columns['nickname']) ? 'a.nickname' : "''";
            $statusField = isset($columns['status']) ? 'a.status' : '1';
            $lastLoginField = isset($columns['last_login_at']) ? 'a.last_login_at' : 'NULL';

            $query = Db::table($adminTable)
                ->alias('a')
                ->leftJoin('tc_admin_user_role aur', 'aur.admin_id = a.id')
                ->leftJoin('tc_admin_role r', 'r.id = aur.role_id')
                ->field(implode(',', [
                    'a.id',
                    'a.username',
                    $nicknameField . ' as nickname',
                    $statusField . ' as status',
                    $lastLoginField . ' as last_login_at',
                    'r.name as role_name',
                    'r.code as role_code',
                ]))
                ->group('a.id')
                ->order('a.id', 'desc');

            if ($keyword !== '') {
                $escapedKeyword = '%' . addcslashes($keyword, '%_\\') . '%';
                $query->where(function ($q) use ($escapedKeyword, $columns) {
                    $q->whereLike('a.username', $escapedKeyword);
                    if (isset($columns['nickname'])) {
                        $q->whereOrLike('a.nickname', $escapedKeyword);
                    }
                });
            }
            if ($status !== '' && isset($columns['status'])) {
                $query->where('a.status', (int) $status);
            }

            $rows = $query->select()->toArray();
            $total = count($rows);
            $list = array_slice($rows, ($page - 1) * $pageSize, $pageSize);

            foreach ($list as &$item) {
                $role = $this->normalizeAdminRoleCode((string) ($item['role_code'] ?? ''));
                $item['role'] = $role !== '' ? $role : 'operator';
                $item['role_name'] = $item['role_name'] ?: $this->resolveAdminRoleName($item['role']);
                $item['role_code'] = $item['role_code'] ?: '';
                $item['nickname'] = $item['nickname'] ?: $item['username'];
                $item['status'] = (int) ($item['status'] ?? 0);
            }
            unset($item);

            return $this->success([
                'list'  => array_values($list),
                'total' => $total,
            ], '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取管理员列表失败: ' . $e->getMessage(), [
                'admin_id' => $this->getAdminId(),
            ]);
            return $this->error('获取管理员列表失败，请稍后重试', 500);
        }
    }

    /**
     * 保存管理员（新增或更新）
     */
    public function saveAdminUser(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限管理管理员账号', 403);
        }

        $data = $request->post();
        $id = (int) ($data['id'] ?? 0);
        $username = trim((string) ($data['username'] ?? ''));
        $nickname = trim((string) ($data['nickname'] ?? ''));
        $password = trim((string) ($data['password'] ?? ''));
        $status = isset($data['status']) ? (int) $data['status'] : 1;
        $roleCode = trim((string) ($data['role'] ?? 'operator'));

        if ($id <= 0 && $username === '') {
            return $this->error('用户名不能为空', 422);
        }
        if ($id <= 0 && $password === '') {
            return $this->error('密码不能为空', 422);
        }

        $adminTable = $this->resolveCompatibleTable(['tc_admin', 'admin'], 'tc_admin');
        if (!SchemaInspector::tableExists($adminTable)) {
            return $this->error('管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql', 500);
        }

        $role = $this->resolveAdminRoleRecord($roleCode);
        if ($role === null) {
            return $this->error('角色不存在，请先初始化管理员角色数据', 422);
        }

        $columns = SchemaInspector::getTableColumns($adminTable);

        Db::startTrans();
        try {
            $existing = null;
            if ($id > 0) {
                $existing = Db::table($adminTable)->where('id', $id)->lock(true)->find();
                if (!$existing) {
                    Db::rollback();
                    return $this->error('管理员不存在', 404);
                }
            }

            $targetUsername = $id > 0
                ? trim((string) ($username !== '' ? $username : ($existing['username'] ?? '')))
                : $username;
            $targetNickname = $nickname !== '' ? $nickname : $targetUsername;

            $duplicateQuery = Db::table($adminTable)->where('username', $targetUsername);
            if ($id > 0) {
                $duplicateQuery->where('id', '<>', $id);
            }
            if ($duplicateQuery->find()) {
                Db::rollback();
                return $this->error('用户名已存在', 422);
            }

            $payload = [];
            if (isset($columns['username'])) {
                $payload['username'] = $targetUsername;
            }
            if (isset($columns['nickname'])) {
                $payload['nickname'] = $targetNickname;
            }
            if (isset($columns['status'])) {
                $payload['status'] = $status === 1 ? 1 : 0;
            }
            if ($password !== '' && isset($columns['password'])) {
                $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            if ($id > 0) {
                if (!empty($payload)) {
                    Db::table($adminTable)->where('id', $id)->update($payload);
                }
            } else {
                $id = (int) Db::table($adminTable)->insertGetId($payload);
            }

            $this->syncAdminRoleBinding($adminTable, $id, (int) $role['id']);

            $this->logOperation($existing ? 'update' : 'create', 'system', [
                'target_id'   => $id,
                'target_type' => 'admin',
                'detail'      => ($existing ? '更新' : '创建') . '管理员账号：' . $targetUsername,
                'before_data' => $existing ? [
                    'username' => $existing['username'] ?? '',
                    'nickname' => $existing['nickname'] ?? '',
                    'status'   => (int) ($existing['status'] ?? 0),
                ] : null,
                'after_data' => [
                    'username' => $targetUsername,
                    'nickname' => $targetNickname,
                    'status'   => $payload['status'] ?? (int) ($existing['status'] ?? 1),
                    'role'     => $this->normalizeAdminRoleCode((string) ($role['code'] ?? $roleCode)),
                ],
            ]);

            Db::commit();
            return $this->success(['id' => $id], '保存成功');
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('保存管理员失败: ' . $e->getMessage(), [
                'admin_id'        => $this->getAdminId(),
                'target_admin_id' => $id,
            ]);
            return $this->error('保存管理员失败，请稍后重试', 500);
        }
    }

    /**
     * 删除管理员
     */
    public function deleteAdminUser($id)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限删除管理员账号', 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->error('管理员ID无效', 422);
        }
        if ($id === $this->getAdminId()) {
            return $this->error('不能删除当前登录管理员', 422);
        }

        $adminTable = $this->resolveCompatibleTable(['tc_admin', 'admin'], 'tc_admin');
        if (!SchemaInspector::tableExists($adminTable)) {
            return $this->error('管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql', 500);
        }

        Db::startTrans();
        try {
            $admin = Db::table($adminTable)->where('id', $id)->lock(true)->find();
            if (!$admin) {
                Db::rollback();
                return $this->error('管理员不存在', 404);
            }

            Db::table($adminTable)->where('id', $id)->delete();
            if (SchemaInspector::tableExists('tc_admin_user_role')) {
                Db::table('tc_admin_user_role')->where('admin_id', $id)->delete();
            }
            AdminAuthService::clearPermissionCache($id);

            $this->logOperation('delete', 'system', [
                'target_id'   => $id,
                'target_type' => 'admin',
                'detail'      => '删除管理员账号：' . (string) ($admin['username'] ?? $id),
                'before_data' => [
                    'username' => $admin['username'] ?? '',
                    'nickname' => $admin['nickname'] ?? '',
                    'status'   => (int) ($admin['status'] ?? 0),
                ],
            ]);

            Db::commit();
            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('删除管理员失败: ' . $e->getMessage(), [
                'admin_id'        => $this->getAdminId(),
                'target_admin_id' => $id,
            ]);
            return $this->error('删除管理员失败，请稍后重试', 500);
        }
    }

    /**
     * 重置管理员密码
     */
    public function resetAdminPassword($id)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限重置管理员密码', 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->error('管理员ID无效', 422);
        }

        $newPassword = $this->request->post('new_password', '');
        if (empty($newPassword)) {
            return $this->error('新密码不能为空', 422);
        }
        if (strlen($newPassword) < 6) {
            return $this->error('密码长度不能少于6位', 422);
        }

        $adminTable = $this->resolveCompatibleTable(['tc_admin', 'admin'], 'tc_admin');
        if (!SchemaInspector::tableExists($adminTable)) {
            return $this->error('管理员账号表不存在', 500);
        }

        $admin = Db::table($adminTable)->where('id', $id)->find();
        if (!$admin) {
            return $this->error('管理员不存在', 404);
        }

        try {
            Db::table($adminTable)->where('id', $id)->update([
                'password'   => password_hash($newPassword, PASSWORD_DEFAULT),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->logOperation('update', 'system', [
                'target_id'   => $id,
                'target_type' => 'admin',
                'detail'      => '重置管理员密码：' . (string) ($admin['username'] ?? $id),
            ]);

            return $this->success(null, '密码重置成功');
        } catch (\Throwable $e) {
            Log::error('重置管理员密码失败: ' . $e->getMessage());
            return $this->error('重置密码失败，请稍后重试', 500);
        }
    }

    // ─── 私有辅助方法 ────────────────────────────────────────────────────────

    /**
     * 统一前端使用的管理员角色编码
     */
    private function normalizeAdminRoleCode(string $roleCode): string
    {
        $roleCode = strtolower(trim($roleCode));

        return match ($roleCode) {
            'super_admin', 'admin' => 'admin',
            'operator', 'normal_admin', 'customer_service' => 'operator',
            default => $roleCode,
        };
    }

    /**
     * 管理员角色显示名
     */
    private function resolveAdminRoleName(string $roleCode): string
    {
        return match ($this->normalizeAdminRoleCode($roleCode)) {
            'admin'    => '超级管理员',
            'operator' => '运营人员',
            default    => '未分配角色',
        };
    }

    /**
     * 解析管理员角色记录
     */
    private function resolveAdminRoleRecord(string $roleCode): ?array
    {
        if (!SchemaInspector::tableExists('tc_admin_role')) {
            return null;
        }

        $normalizedRole = $this->normalizeAdminRoleCode($roleCode);
        $candidates = match ($normalizedRole) {
            'admin'    => ['super_admin', 'admin'],
            'operator' => ['operator', 'normal_admin', 'customer_service'],
            default    => [$normalizedRole],
        };

        $rows = Db::table('tc_admin_role')
            ->whereIn('code', $candidates)
            ->select()
            ->toArray();

        foreach ($candidates as $candidate) {
            foreach ($rows as $row) {
                if (($row['code'] ?? '') === $candidate) {
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * 同步管理员角色绑定
     */
    private function syncAdminRoleBinding(string $adminTable, int $adminId, int $roleId): void
    {
        if ($adminId <= 0) {
            return;
        }

        if (SchemaInspector::tableExists('tc_admin_user_role')) {
            Db::table('tc_admin_user_role')->where('admin_id', $adminId)->delete();

            if ($roleId > 0) {
                $payload = [
                    'admin_id' => $adminId,
                    'role_id'  => $roleId,
                ];
                $rolePivotColumns = SchemaInspector::getTableColumns('tc_admin_user_role');
                if (isset($rolePivotColumns['created_at'])) {
                    $payload['created_at'] = date('Y-m-d H:i:s');
                }
                Db::table('tc_admin_user_role')->insert($payload);
            }
        }

        $adminColumns = SchemaInspector::getTableColumns($adminTable);
        if (isset($adminColumns['role_id'])) {
            Db::table($adminTable)
                ->where('id', $adminId)
                ->update(['role_id' => $roleId]);
        }

        AdminAuthService::clearPermissionCache($adminId);
    }

    /**
     * 解析兼容表名（优先使用存在的表）
     */
    private function resolveCompatibleTable(array $candidates, string $fallback): string
    {
        foreach ($candidates as $table) {
            if (SchemaInspector::tableExists($table)) {
                return $table;
            }
        }

        return $fallback;
    }
}
