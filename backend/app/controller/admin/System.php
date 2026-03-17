<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\AdminAuthService;
use think\facade\Db;
use think\facade\Log;

class System extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    protected const TABLE_ADMIN_ROLE = 'tc_admin_role';
    protected const TABLE_ADMIN_PERMISSION = 'tc_admin_permission';
    protected const TABLE_ADMIN_ROLE_PERMISSION = 'tc_admin_role_permission';
    protected const TABLE_ADMIN_USER_ROLE = 'tc_admin_user_role';
    protected const TABLE_ADMIN_DICT_TYPE = 'tc_admin_dict_type';
    protected const TABLE_ADMIN_DICT_DATA = 'tc_admin_dict_data';
    protected const STATUS_VALUES = [0, 1];

    /**
     * 获取角色列表
     */
    public function getRoles()
    {
        $list = Db::table(self::TABLE_ADMIN_ROLE)
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return $this->success($list);
    }

    /**
     * 创建角色
     */
    public function createRole()
    {
        try {
            $payload = $this->validateRolePayload($this->request->post());
            $this->assertRoleCodeUnique($payload['code']);

            $id = Db::table(self::TABLE_ADMIN_ROLE)->insertGetId([
                'name' => $payload['name'],
                'code' => $payload['code'],
                'description' => $payload['description'],
                'status' => $payload['status'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->success(['id' => $id], '创建成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('创建角色失败: ' . $e->getMessage());
            return $this->error('创建失败，请稍后重试', 500);
        }
    }

    /**
     * 更新角色
     */
    public function updateRole()
    {
        try {
            $id = $this->requirePositiveId($this->request->param('id'), '角色ID');
            $payload = $this->validateRolePayload($this->request->put());
            $role = Db::table(self::TABLE_ADMIN_ROLE)->where('id', $id)->find();
            if (!$role) {
                return $this->error('角色不存在', 404);
            }

            $this->assertRoleCodeUnique($payload['code'], $id);

            Db::table(self::TABLE_ADMIN_ROLE)
                ->where('id', $id)
                ->update([
                    'name' => $payload['name'],
                    'code' => $payload['code'],
                    'description' => $payload['description'],
                    'status' => $payload['status'],
                ]);

            return $this->success([], '更新成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('更新角色失败: ' . $e->getMessage());
            return $this->error('更新失败，请稍后重试', 500);
        }
    }

    /**
     * 删除角色
     */
    public function deleteRole()
    {
        try {
            $id = $this->requirePositiveId($this->request->param('id'), '角色ID');
            $role = Db::table(self::TABLE_ADMIN_ROLE)->where('id', $id)->find();
            if (!$role) {
                return $this->error('角色不存在', 404);
            }
            if (!empty($role['is_super'])) {
                return $this->error('超级管理员角色不能删除');
            }

            $boundAdminCount = Db::table(self::TABLE_ADMIN_USER_ROLE)->where('role_id', $id)->count();
            if ($boundAdminCount > 0) {
                return $this->error('该角色仍绑定管理员，无法删除');
            }

            Db::startTrans();
            Db::table(self::TABLE_ADMIN_ROLE_PERMISSION)->where('role_id', $id)->delete();
            Db::table(self::TABLE_ADMIN_ROLE)->where('id', $id)->delete();
            Db::commit();

            return $this->success([], '删除成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('删除角色失败: ' . $e->getMessage());
            return $this->error('删除失败，请稍后重试', 500);
        }
    }

    /**
     * 获取所有权限树
     */
    public function getPermissions()
    {
        $permissions = Db::table(self::TABLE_ADMIN_PERMISSION)
            ->order('module', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        
        $modules = [];
        foreach ($permissions as $permission) {
            $module = (string) ($permission['module'] ?? 'system');
            if (!isset($modules[$module])) {
                $modules[$module] = [
                    'id' => 'm_' . $module,
                    'name' => $this->getModuleName($module),
                    'children' => []
                ];
            }
            $modules[$module]['children'][] = [
                'id' => $permission['id'],
                'name' => $permission['name'],
                'code' => $permission['code']
            ];
        }
        
        return $this->success(array_values($modules));
    }

    /**
     * 获取模块名称
     */
    protected function getModuleName($module)
    {
        $names = [
            'user' => '用户管理',
            'points' => '积分管理',
            'config' => '系统配置',
            'log' => '操作日志',
            'stats' => '统计报表',
            'content' => '内容管理',
            'system' => '系统管理'
        ];
        return $names[$module] ?? $module;
    }

    /**
     * 获取角色权限
     */
    public function getRolePermissions()
    {
        try {
            $roleId = $this->requirePositiveId($this->request->param('id'), '角色ID');
            $role = Db::table(self::TABLE_ADMIN_ROLE)->where('id', $roleId)->find();
            if (!$role) {
                return $this->error('角色不存在', 404);
            }

            $permissions = Db::table(self::TABLE_ADMIN_ROLE_PERMISSION)
                ->where('role_id', $roleId)
                ->column('permission_id');

            return $this->success(array_map('intval', $permissions));
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 更新角色权限
     */
    public function updateRolePermissions()
    {
        try {
            $roleId = $this->requirePositiveId($this->request->param('id'), '角色ID');
            $role = Db::table(self::TABLE_ADMIN_ROLE)->where('id', $roleId)->find();
            if (!$role) {
                return $this->error('角色不存在', 404);
            }

            $permissionIds = $this->normalizePermissionIds($this->request->post('permission_ids/a', []));
            $this->assertPermissionsExist($permissionIds);

            Db::startTrans();
            Db::table(self::TABLE_ADMIN_ROLE_PERMISSION)->where('role_id', $roleId)->delete();

            if (!empty($permissionIds)) {
                $rows = [];
                $now = date('Y-m-d H:i:s');
                foreach ($permissionIds as $permissionId) {
                    $rows[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                    ];
                }
                Db::table(self::TABLE_ADMIN_ROLE_PERMISSION)->insertAll($rows);
            }

            Db::commit();

            $adminIds = Db::table(self::TABLE_ADMIN_USER_ROLE)
                ->where('role_id', $roleId)
                ->column('admin_id');
            foreach ($adminIds as $adminId) {
                AdminAuthService::clearPermissionCache((int) $adminId);
            }

            return $this->success([], '权限保存成功');
        } catch (\InvalidArgumentException $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('保存角色权限失败: ' . $e->getMessage());
            return $this->error('权限保存失败，请稍后重试', 500);
        }
    }

    /**
     * 获取字典类型列表
     */
    public function getDictTypes()
    {
        $list = Db::table(self::TABLE_ADMIN_DICT_TYPE)
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return $this->success($list);
    }

    /**
     * 创建字典类型
     */
    public function createDictType()
    {
        try {
            $payload = $this->validateDictTypePayload($this->request->post());
            $this->assertDictTypeUnique($payload['type']);

            $id = Db::table(self::TABLE_ADMIN_DICT_TYPE)->insertGetId([
                'name' => $payload['name'],
                'type' => $payload['type'],
                'status' => $payload['status'],
                'remark' => $payload['remark'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return $this->success(['id' => $id], '创建成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('创建字典类型失败: ' . $e->getMessage());
            return $this->error('创建失败，请稍后重试', 500);
        }
    }

    /**
     * 更新字典类型
     */
    public function updateDictType()
    {
        try {
            $id = $this->requirePositiveId($this->request->param('id'), '字典类型ID');
            $payload = $this->validateDictTypePayload($this->request->put());
            $dictType = Db::table(self::TABLE_ADMIN_DICT_TYPE)->where('id', $id)->find();
            if (!$dictType) {
                return $this->error('字典类型不存在', 404);
            }

            $this->assertDictTypeUnique($payload['type'], $id);

            Db::startTrans();
            Db::table(self::TABLE_ADMIN_DICT_TYPE)
                ->where('id', $id)
                ->update([
                    'name' => $payload['name'],
                    'type' => $payload['type'],
                    'status' => $payload['status'],
                    'remark' => $payload['remark'],
                ]);

            if ($dictType['type'] !== $payload['type']) {
                Db::table(self::TABLE_ADMIN_DICT_DATA)
                    ->where('dict_type', $dictType['type'])
                    ->update(['dict_type' => $payload['type']]);
            }
            Db::commit();

            return $this->success([], '更新成功');
        } catch (\InvalidArgumentException $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('更新字典类型失败: ' . $e->getMessage());
            return $this->error('更新失败，请稍后重试', 500);
        }
    }

    /**
     * 删除字典类型
     */
    public function deleteDictType()
    {
        try {
            $id = $this->requirePositiveId($this->request->param('id'), '字典类型ID');
            $dictType = Db::table(self::TABLE_ADMIN_DICT_TYPE)->where('id', $id)->find();
            if (!$dictType) {
                return $this->error('字典类型不存在', 404);
            }

            Db::startTrans();
            Db::table(self::TABLE_ADMIN_DICT_DATA)->where('dict_type', $dictType['type'])->delete();
            Db::table(self::TABLE_ADMIN_DICT_TYPE)->where('id', $id)->delete();
            Db::commit();

            return $this->success([], '删除成功');
        } catch (\InvalidArgumentException $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('删除字典类型失败: ' . $e->getMessage());
            return $this->error('删除失败，请稍后重试', 500);
        }
    }

    /**
     * 获取字典数据列表
     */
    public function getDictData()
    {
        $dictType = trim((string) $this->request->param('type'));
        if ($dictType === '') {
            return $this->error('字典类型不能为空');
        }

        $list = Db::table(self::TABLE_ADMIN_DICT_DATA)
            ->where('dict_type', $dictType)
            ->order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
            
        return $this->success($list);
    }

    /**
     * 保存字典数据
     */
    public function saveDictData()
    {
        try {
            $payload = $this->validateDictDataPayload($this->request->post());
            $recordId = isset($payload['id']) ? $this->requirePositiveId($payload['id'], '字典数据ID') : 0;

            $dictType = Db::table(self::TABLE_ADMIN_DICT_TYPE)
                ->where('type', $payload['dict_type'])
                ->find();
            if (!$dictType) {
                return $this->error('字典类型不存在', 404);
            }

            $duplicateQuery = Db::table(self::TABLE_ADMIN_DICT_DATA)
                ->where('dict_type', $payload['dict_type'])
                ->where('value', $payload['value']);
            if ($recordId > 0) {
                $duplicateQuery->where('id', '<>', $recordId);
            }
            if ($duplicateQuery->find()) {
                return $this->error('同一字典类型下的字典值已存在');
            }

            $saveData = [
                'dict_type' => $payload['dict_type'],
                'label' => $payload['label'],
                'value' => $payload['value'],
                'sort_order' => $payload['sort_order'],
                'status' => $payload['status'],
                'remark' => $payload['remark'],
            ];

            if ($recordId > 0) {
                $exists = Db::table(self::TABLE_ADMIN_DICT_DATA)->where('id', $recordId)->find();
                if (!$exists) {
                    return $this->error('字典数据不存在', 404);
                }

                Db::table(self::TABLE_ADMIN_DICT_DATA)
                    ->where('id', $recordId)
                    ->update($saveData);
            } else {
                $saveData['created_at'] = date('Y-m-d H:i:s');
                Db::table(self::TABLE_ADMIN_DICT_DATA)->insert($saveData);
            }

            return $this->success([], '保存成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('保存字典数据失败: ' . $e->getMessage());
            return $this->error('保存失败，请稍后重试', 500);
        }
    }

    /**
     * 删除字典数据
     */
    public function deleteDictData()
    {
        try {
            $id = $this->requirePositiveId($this->request->param('id'), '字典数据ID');
            $exists = Db::table(self::TABLE_ADMIN_DICT_DATA)->where('id', $id)->find();
            if (!$exists) {
                return $this->error('字典数据不存在', 404);
            }

            Db::table(self::TABLE_ADMIN_DICT_DATA)->where('id', $id)->delete();
            return $this->success([], '删除成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('删除字典数据失败: ' . $e->getMessage());
            return $this->error('删除失败，请稍后重试', 500);
        }
    }

    /**
     * 校验角色入参
     */
    protected function validateRolePayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $status = $this->normalizeStatus($data['status'] ?? 1);

        if ($name === '' || $code === '') {
            throw new \InvalidArgumentException('角色名称和编码不能为空');
        }
        if (mb_strlen($name) > 30) {
            throw new \InvalidArgumentException('角色名称不能超过30个字符');
        }
        if (!preg_match('/^[A-Za-z][A-Za-z0-9:_-]{1,49}$/', $code)) {
            throw new \InvalidArgumentException('角色编码格式无效，仅支持字母开头的字母、数字、冒号、下划线和横线');
        }
        if (mb_strlen($description) > 255) {
            throw new \InvalidArgumentException('角色描述不能超过255个字符');
        }

        return [
            'name' => $name,
            'code' => $code,
            'description' => $description,
            'status' => $status,
        ];
    }

    /**
     * 校验字典类型入参
     */
    protected function validateDictTypePayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $type = trim((string) ($data['type'] ?? ''));
        $remark = trim((string) ($data['remark'] ?? ''));
        $status = $this->normalizeStatus($data['status'] ?? 1);

        if ($name === '' || $type === '') {
            throw new \InvalidArgumentException('名称和类型不能为空');
        }
        if (mb_strlen($name) > 30) {
            throw new \InvalidArgumentException('字典名称不能超过30个字符');
        }
        if (!preg_match('/^[A-Za-z][A-Za-z0-9:_-]{1,49}$/', $type)) {
            throw new \InvalidArgumentException('字典类型编码格式无效');
        }
        if (mb_strlen($remark) > 255) {
            throw new \InvalidArgumentException('备注不能超过255个字符');
        }

        return [
            'name' => $name,
            'type' => $type,
            'remark' => $remark,
            'status' => $status,
        ];
    }

    /**
     * 校验字典数据入参
     */
    protected function validateDictDataPayload(array $data): array
    {
        $dictType = trim((string) ($data['dict_type'] ?? ''));
        $label = trim((string) ($data['label'] ?? ''));
        $value = trim((string) ($data['value'] ?? ''));
        $remark = trim((string) ($data['remark'] ?? ''));
        $sortOrder = filter_var($data['sort_order'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
        $status = $this->normalizeStatus($data['status'] ?? 1);

        if ($dictType === '' || $label === '' || $value === '') {
            throw new \InvalidArgumentException('字典类型、标签和值不能为空');
        }
        if (mb_strlen($label) > 50 || mb_strlen($value) > 100) {
            throw new \InvalidArgumentException('字典标签或值长度超出限制');
        }
        if (mb_strlen($remark) > 255) {
            throw new \InvalidArgumentException('备注不能超过255个字符');
        }

        return [
            'id' => $data['id'] ?? null,
            'dict_type' => $dictType,
            'label' => $label,
            'value' => $value,
            'remark' => $remark,
            'sort_order' => max(0, (int) $sortOrder),
            'status' => $status,
        ];
    }

    /**
     * 校验角色编码唯一性
     */
    protected function assertRoleCodeUnique(string $code, int $excludeId = 0): void
    {
        $query = Db::table(self::TABLE_ADMIN_ROLE)->where('code', $code);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        if ($query->find()) {
            throw new \InvalidArgumentException('角色编码已存在');
        }
    }

    /**
     * 校验字典类型唯一性
     */
    protected function assertDictTypeUnique(string $type, int $excludeId = 0): void
    {
        $query = Db::table(self::TABLE_ADMIN_DICT_TYPE)->where('type', $type);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        if ($query->find()) {
            throw new \InvalidArgumentException('字典类型编码已存在');
        }
    }

    /**
     * 将权限ID数组规范化为正整数列表
     */
    protected function normalizePermissionIds(mixed $permissionIds): array
    {
        if (!is_array($permissionIds)) {
            throw new \InvalidArgumentException('权限列表格式无效');
        }

        $normalized = [];
        foreach ($permissionIds as $permissionId) {
            if (is_numeric($permissionId) && (int) $permissionId > 0) {
                $normalized[] = (int) $permissionId;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * 校验权限ID是否全部存在
     */
    protected function assertPermissionsExist(array $permissionIds): void
    {
        if (empty($permissionIds)) {
            return;
        }

        $existingIds = Db::table(self::TABLE_ADMIN_PERMISSION)
            ->whereIn('id', $permissionIds)
            ->column('id');
        $existingIds = array_map('intval', $existingIds);
        sort($existingIds);
        $expectedIds = $permissionIds;
        sort($expectedIds);

        if ($existingIds !== $expectedIds) {
            throw new \InvalidArgumentException('存在无效的权限ID');
        }
    }

    /**
     * 规范状态值
     */
    protected function normalizeStatus(mixed $status): int
    {
        $normalized = filter_var($status, FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
        if (!in_array($normalized, self::STATUS_VALUES, true)) {
            throw new \InvalidArgumentException('状态值无效');
        }

        return (int) $normalized;
    }

    /**
     * 要求正整数ID
     */
    protected function requirePositiveId(mixed $id, string $fieldName): int
    {
        $normalized = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($normalized === false) {
            throw new \InvalidArgumentException($fieldName . '不能为空');
        }

        return (int) $normalized;
    }
}
