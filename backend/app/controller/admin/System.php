<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\AdminAuthService;
use app\service\SchemaInspector;
use think\facade\Db;


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

    protected function ensureSystemReadAccess()
    {
        if ($this->checkPermission('config_manage')) {
            return null;
        }

        return $this->error('无权限查看系统管理配置', 403);
    }

    protected function ensureSystemWriteAccess(string $message)
    {
        if ($this->checkPermission('config_manage')) {
            return null;
        }

        return $this->error($message, 403);
    }

    /**
     * 获取角色列表
     */
    public function getRoles()
    {
        if ($response = $this->ensureSystemReadAccess()) {
            return $response;
        }

        $columns = $this->getRoleColumns();
        $list = Db::table(self::TABLE_ADMIN_ROLE)
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $list = array_map(fn (array $row): array => $this->normalizeRoleRow($row, $columns), $list);

        return $this->success($list);
    }


    /**
     * 创建角色
     */
    public function createRole()
    {
        if ($response = $this->ensureSystemWriteAccess('无权限创建角色')) {
            return $response;
        }

        try {
            $payload = $this->validateRolePayload($this->request->post());
            $columns = $this->getRoleColumns();
            $this->assertRoleCodeUnique($payload['code'], 0, $columns);

            $id = Db::table(self::TABLE_ADMIN_ROLE)->insertGetId(
                $this->buildRoleSaveData($payload, $columns, true)
            );

            return $this->success(['id' => $id], '创建成功');
        } catch (\InvalidArgumentException $e) {
            return $this->respondBusinessException($e, 'system_create_role', '创建角色参数无效', 400, [
                'name' => $this->request->post('name', ''),
                'code' => $this->request->post('code', ''),
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_create_role', $e, '创建失败，请稍后重试', [
                'name' => $this->request->post('name', ''),
                'code' => $this->request->post('code', ''),
            ]);
        }
    }

    /**
     * 更新角色
     */
    public function updateRole()
    {
        if ($response = $this->ensureSystemWriteAccess('无权限修改角色')) {
            return $response;
        }

        try {
            $id = $this->requirePositiveId($this->request->param('id'), '角色ID');
            $payload = $this->validateRolePayload($this->request->put());
            $columns = $this->getRoleColumns();
            $role = Db::table(self::TABLE_ADMIN_ROLE)->where('id', $id)->find();
            if (!$role) {
                return $this->error('角色不存在', 404);
            }

            $this->assertRoleCodeUnique($payload['code'], $id, $columns);

            Db::table(self::TABLE_ADMIN_ROLE)
                ->where('id', $id)
                ->update($this->buildRoleSaveData($payload, $columns, false, $role));

            return $this->success([], '更新成功');
        } catch (\InvalidArgumentException $e) {
            return $this->respondBusinessException($e, 'system_update_role', '更新角色参数无效', 400, [
                'id' => $this->request->param('id'),
                'name' => $this->request->put('name', ''),
                'code' => $this->request->put('code', ''),
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_update_role', $e, '更新失败，请稍后重试', [
                'id' => $this->request->param('id'),
                'name' => $this->request->put('name', ''),
                'code' => $this->request->put('code', ''),
            ]);
        }
    }

    /**
     * 删除角色
     */
    public function deleteRole()
    {
        if ($response = $this->ensureSystemWriteAccess('无权限删除角色')) {
            return $response;
        }

        try {
            $id = $this->requirePositiveId($this->request->param('id'), '角色ID');
            $columns = $this->getRoleColumns();

            $role = Db::table(self::TABLE_ADMIN_ROLE)->where('id', $id)->find();
            if (!$role) {
                return $this->error('角色不存在', 404);
            }
            if ($this->isSuperRole($role, $columns)) {
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
            return $this->respondBusinessException($e, 'system_delete_role', '删除角色参数无效', 400, [
                'id' => $this->request->param('id'),
            ]);
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('system_delete_role', $e, '删除失败，请稍后重试', [
                'id' => $this->request->param('id'),
            ]);
        }
    }

    /**
     * 获取角色表字段信息
     */
    protected function getRoleColumns(): array
    {
        return SchemaInspector::getTableColumns(self::TABLE_ADMIN_ROLE);
    }

    /**
     * 解析角色名称字段
     */
    protected function resolveRoleNameColumn(array $columns): string
    {
        return isset($columns['role_name']) ? 'role_name' : 'name';
    }

    /**
     * 解析角色编码字段
     */
    protected function resolveRoleCodeColumn(array $columns): string
    {
        return isset($columns['role_code']) ? 'role_code' : 'code';
    }

    /**
     * 统一角色记录输出结构
     */
    protected function normalizeRoleRow(array $row, ?array $columns = null): array
    {
        $columns ??= $this->getRoleColumns();
        $nameColumn = $this->resolveRoleNameColumn($columns);
        $codeColumn = $this->resolveRoleCodeColumn($columns);
        $status = isset($columns['status'])
            ? $this->normalizeStatus($row['status'] ?? 1)
            : 1;

        return array_merge($row, [
            'name' => trim((string) ($row[$nameColumn] ?? $row['name'] ?? '')),
            'code' => trim((string) ($row[$codeColumn] ?? $row['code'] ?? '')),
            'status' => $status,
            'is_super' => $this->isSuperRole($row, $columns) ? 1 : 0,
        ]);
    }

    /**
     * 构建角色写入数据
     */
    protected function buildRoleSaveData(array $payload, array $columns, bool $isCreate, ?array $existing = null): array
    {
        $saveData = [
            $this->resolveRoleNameColumn($columns) => $payload['name'],
            $this->resolveRoleCodeColumn($columns) => $payload['code'],
            'description' => $payload['description'],
        ];

        if (isset($columns['status'])) {
            $saveData['status'] = $payload['status'];
        }
        if (!$isCreate && isset($columns['updated_at'])) {
            $saveData['updated_at'] = date('Y-m-d H:i:s');
        }
        if ($isCreate && isset($columns['created_at'])) {
            $saveData['created_at'] = date('Y-m-d H:i:s');
        }
        if ($isCreate && isset($columns['updated_at']) && !isset($saveData['updated_at'])) {
            $saveData['updated_at'] = date('Y-m-d H:i:s');
        }
        if (isset($columns['is_super']) && $existing && array_key_exists('is_super', $existing)) {
            $saveData['is_super'] = (int) $existing['is_super'];
        }

        return $saveData;
    }

    /**
     * 判断是否为超级管理员角色
     */
    protected function isSuperRole(array $role, ?array $columns = null): bool
    {
        $columns ??= $this->getRoleColumns();
        if (isset($columns['is_super']) && !empty($role['is_super'])) {
            return true;
        }

        $codeColumn = $this->resolveRoleCodeColumn($columns);
        $code = strtolower(trim((string) ($role[$codeColumn] ?? $role['code'] ?? '')));

        return $code === 'super_admin';
    }

    /**
     * 获取所有权限树
     */
    public function getPermissions()
    {
        if ($response = $this->ensureSystemReadAccess()) {
            return $response;
        }

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
        if ($response = $this->ensureSystemReadAccess()) {
            return $response;
        }

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
            return $this->respondBusinessException($e, 'system_get_role_permissions', '角色权限参数无效', 400, [
                'role_id' => $this->request->param('id'),
            ]);
        }
    }

    /**
     * 更新角色权限
     */
    public function updateRolePermissions()
    {
        if ($response = $this->ensureSystemWriteAccess('无权限修改角色权限')) {
            return $response;
        }

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
            return $this->respondBusinessException($e, 'system_update_role_permissions', '权限参数无效', 400, [
                'role_id' => $this->request->param('id'),
                'permission_ids' => $this->request->post('permission_ids/a', []),
            ]);
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('system_update_role_permissions', $e, '权限保存失败，请稍后重试', [
                'role_id' => $this->request->param('id'),
                'permission_ids' => $this->request->post('permission_ids/a', []),
            ]);
        }
    }


    /**
     * 获取字典类型列表
     */
    public function getDictTypes()
    {
        if ($response = $this->ensureSystemReadAccess()) {
            return $response;
        }

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
        if ($response = $this->ensureSystemWriteAccess('无权限创建字典类型')) {
            return $response;
        }

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
            return $this->respondBusinessException($e, 'system_create_dict_type', '创建字典类型参数无效', 400, [
                'name' => $this->request->post('name', ''),
                'type' => $this->request->post('type', ''),
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_create_dict_type', $e, '创建失败，请稍后重试', [
                'name' => $this->request->post('name', ''),
                'type' => $this->request->post('type', ''),
            ]);
        }
    }

    /**
     * 更新字典类型
     */
    public function updateDictType()
    {
        if ($response = $this->ensureSystemWriteAccess('无权限修改字典类型')) {
            return $response;
        }

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
            return $this->respondBusinessException($e, 'system_update_dict_type', '更新字典类型参数无效', 400, [
                'id' => $this->request->param('id'),
                'name' => $this->request->put('name', ''),
                'type' => $this->request->put('type', ''),
            ]);
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('system_update_dict_type', $e, '更新失败，请稍后重试', [
                'id' => $this->request->param('id'),
                'name' => $this->request->put('name', ''),
                'type' => $this->request->put('type', ''),
            ]);
        }
    }

    /**
     * 删除字典类型
     */
    public function deleteDictType()
    {
        if ($response = $this->ensureSystemWriteAccess('无权限删除字典类型')) {
            return $response;
        }

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
            return $this->respondBusinessException($e, 'system_delete_dict_type', '删除字典类型参数无效', 400, [
                'id' => $this->request->param('id'),
            ]);
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('system_delete_dict_type', $e, '删除失败，请稍后重试', [
                'id' => $this->request->param('id'),
            ]);
        }
    }

    /**
     * 获取字典数据列表
     */
    public function getDictData()
    {
        if ($response = $this->ensureSystemReadAccess()) {
            return $response;
        }

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
        if ($response = $this->ensureSystemWriteAccess('无权限保存字典数据')) {
            return $response;
        }

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
            return $this->respondBusinessException($e, 'system_save_dict_data', '保存字典数据参数无效', 400, [
                'id' => $this->request->post('id', null),
                'dict_type' => $this->request->post('dict_type', ''),
                'value' => $this->request->post('value', ''),
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_save_dict_data', $e, '保存失败，请稍后重试', [
                'id' => $this->request->post('id', null),
                'dict_type' => $this->request->post('dict_type', ''),
                'value' => $this->request->post('value', ''),
            ]);
        }
    }

    /**
     * 删除字典数据
     */
    public function deleteDictData()
    {
        if ($response = $this->ensureSystemWriteAccess('无权限删除字典数据')) {
            return $response;
        }

        try {
            $id = $this->requirePositiveId($this->request->param('id'), '字典数据ID');

            $exists = Db::table(self::TABLE_ADMIN_DICT_DATA)->where('id', $id)->find();
            if (!$exists) {
                return $this->error('字典数据不存在', 404);
            }

            Db::table(self::TABLE_ADMIN_DICT_DATA)->where('id', $id)->delete();
            return $this->success([], '删除成功');
        } catch (\InvalidArgumentException $e) {
            return $this->respondBusinessException($e, 'system_delete_dict_data', '删除字典数据参数无效', 400, [
                'id' => $this->request->param('id'),
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_delete_dict_data', $e, '删除失败，请稍后重试', [
                'id' => $this->request->param('id'),
            ]);
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
    protected function assertRoleCodeUnique(string $code, int $excludeId = 0, ?array $columns = null): void
    {
        $columns ??= $this->getRoleColumns();
        $codeColumn = $this->resolveRoleCodeColumn($columns);

        $query = Db::table(self::TABLE_ADMIN_ROLE)->where($codeColumn, $code);
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
