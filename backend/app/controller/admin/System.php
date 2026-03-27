<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\AdminUser;
use think\facade\Db;
use think\Request;

class System extends BaseController
{
    // ===================== 角色管理 =====================

    /**
     * 获取角色列表
     */
    public function getRoles()
    {
        try {
            $roles = Db::name('admin_role')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            return $this->success($roles);
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_get_roles', $e, '获取角色列表失败');
        }
    }

    /**
     * 创建角色
     */
    public function createRole(Request $request)
    {
        if (!$this->hasAdminPermission('system_manage')) {
            return $this->error('无权限操作', 403);
        }

        $name = trim($request->post('name', ''));
        $code = trim($request->post('code', ''));
        $description = trim($request->post('description', ''));

        if (empty($name) || empty($code)) {
            return $this->error('角色名称和标识不能为空');
        }

        try {
            $exists = Db::name('admin_role')->where('code', $code)->find();
            if ($exists) {
                return $this->error('角色标识已存在');
            }

            $id = Db::name('admin_role')->insertGetId([
                'name'        => $name,
                'code'        => $code,
                'description' => $description,
                'is_super'    => 0,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            $this->logOperation('创建角色', 'system', ['detail' => "角色：{$name}({$code})", 'target_id' => $id]);

            return $this->success(['id' => $id], '创建成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_create_role', $e, '创建角色失败');
        }
    }

    /**
     * 更新角色
     */
    public function updateRole(Request $request, $id)
    {
        if (!$this->hasAdminPermission('system_manage')) {
            return $this->error('无权限操作', 403);
        }

        try {
            $role = Db::name('admin_role')->find($id);
            if (!$role) {
                return $this->error('角色不存在', 404);
            }

            if ($role['is_super']) {
                return $this->error('超级管理员角色不允许修改');
            }

            $data = array_filter([
                'name'        => trim($request->post('name', '')),
                'description' => $request->post('description', null),
            ], fn($v) => $v !== null && $v !== '');

            if (empty($data)) {
                return $this->error('没有需要更新的字段');
            }

            Db::name('admin_role')->where('id', $id)->update($data);
            $this->logOperation('更新角色', 'system', ['detail' => "角色ID：{$id}", 'target_id' => $id]);

            return $this->success([], '更新成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_update_role', $e, '更新角色失败');
        }
    }

    /**
     * 删除角色
     */
    public function deleteRole($id)
    {
        if (!$this->hasAdminPermission('system_manage')) {
            return $this->error('无权限操作', 403);
        }

        try {
            $role = Db::name('admin_role')->find($id);
            if (!$role) {
                return $this->error('角色不存在', 404);
            }

            if ($role['is_super']) {
                return $this->error('超级管理员角色不允许删除');
            }

            // 检查是否有管理员使用该角色
            $inUse = Db::name('admin_user_role')->where('role_id', $id)->count();
            if ($inUse > 0) {
                return $this->error('该角色下还有管理员，无法删除');
            }

            Db::name('admin_role')->where('id', $id)->delete();
            Db::name('admin_role_permission')->where('role_id', $id)->delete();
            $this->logOperation('删除角色', 'system', ['detail' => "角色：{$role['name']}", 'target_id' => $id]);

            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_delete_role', $e, '删除角色失败');
        }
    }

    // ===================== 权限管理 =====================

    /**
     * 获取权限列表
     */
    public function getPermissions()
    {
        try {
            $permissions = Db::name('admin_permission')
                ->order('module asc, id asc')
                ->select()
                ->toArray();

            // 按模块分组
            $grouped = [];
            foreach ($permissions as $perm) {
                $grouped[$perm['module']][] = $perm;
            }

            return $this->success([
                'list'    => $permissions,
                'grouped' => $grouped,
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_get_permissions', $e, '获取权限列表失败');
        }
    }

    /**
     * 获取角色的权限
     */
    public function getRolePermissions($id)
    {
        try {
            $role = Db::name('admin_role')->find($id);
            if (!$role) {
                return $this->error('角色不存在', 404);
            }

            $permissionIds = Db::name('admin_role_permission')
                ->where('role_id', $id)
                ->column('permission_id');

            return $this->success(['permission_ids' => $permissionIds]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_get_role_permissions', $e, '获取角色权限失败');
        }
    }

    /**
     * 更新角色权限
     */
    public function updateRolePermissions(Request $request, $id)
    {
        if (!$this->hasAdminPermission('system_manage')) {
            return $this->error('无权限操作', 403);
        }

        try {
            $role = Db::name('admin_role')->find($id);
            if (!$role) {
                return $this->error('角色不存在', 404);
            }

            if ($role['is_super']) {
                return $this->error('超级管理员拥有所有权限，无需单独配置');
            }

            $permissionIds = $request->post('permission_ids', []);
            if (!is_array($permissionIds)) {
                return $this->error('权限ID格式错误');
            }

            Db::startTrans();
            Db::name('admin_role_permission')->where('role_id', $id)->delete();

            if (!empty($permissionIds)) {
                $insertData = array_map(fn($pid) => [
                    'role_id'       => (int) $id,
                    'permission_id' => (int) $pid,
                    'created_at'    => date('Y-m-d H:i:s'),
                ], $permissionIds);
                Db::name('admin_role_permission')->insertAll($insertData);
            }

            Db::commit();
            $this->logOperation('更新角色权限', 'system', ['detail' => "角色ID：{$id}，权限数：" . count($permissionIds), 'target_id' => $id]);

            return $this->success([], '权限更新成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('system_update_role_permissions', $e, '更新角色权限失败');
        }
    }

    // ===================== 字典管理 =====================

    /**
     * 获取字典类型列表
     */
    public function getDictTypes()
    {
        try {
            $types = Db::name('dict_type')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            return $this->success($types);
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_get_dict_types', $e, '获取字典类型失败');
        }
    }

    /**
     * 创建字典类型
     */
    public function createDictType(Request $request)
    {
        if (!$this->hasAdminPermission('system_manage')) {
            return $this->error('无权限操作', 403);
        }

        $name = trim($request->post('name', ''));
        $type = trim($request->post('type', ''));
        $remark = trim($request->post('remark', ''));

        if (empty($name) || empty($type)) {
            return $this->error('字典名称和类型标识不能为空');
        }

        try {
            $exists = Db::name('dict_type')->where('type', $type)->find();
            if ($exists) {
                return $this->error('字典类型标识已存在');
            }

            $id = Db::name('dict_type')->insertGetId([
                'name'       => $name,
                'type'       => $type,
                'remark'     => $remark,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->success(['id' => $id], '创建成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_create_dict_type', $e, '创建字典类型失败');
        }
    }

    /**
     * 更新字典类型
     */
    public function updateDictType(Request $request, $id)
    {
        if (!$this->hasAdminPermission('system_manage')) {
            return $this->error('无权限操作', 403);
        }

        try {
            $dictType = Db::name('dict_type')->find($id);
            if (!$dictType) {
                return $this->error('字典类型不存在', 404);
            }

            $data = [];
            if ($request->has('name')) {
                $data['name'] = trim($request->post('name', ''));
            }
            if ($request->has('remark')) {
                $data['remark'] = trim($request->post('remark', ''));
            }

            if (empty($data)) {
                return $this->error('没有需要更新的字段');
            }

            Db::name('dict_type')->where('id', $id)->update($data);

            return $this->success([], '更新成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_update_dict_type', $e, '更新字典类型失败');
        }
    }

    /**
     * 删除字典类型
     */
    public function deleteDictType($id)
    {
        if (!$this->hasAdminPermission('system_manage')) {
            return $this->error('无权限操作', 403);
        }

        try {
            $dictType = Db::name('dict_type')->find($id);
            if (!$dictType) {
                return $this->error('字典类型不存在', 404);
            }

            Db::name('dict_type')->where('id', $id)->delete();
            Db::name('dict_data')->where('dict_type', $dictType['type'])->delete();

            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_delete_dict_type', $e, '删除字典类型失败');
        }
    }

    /**
     * 获取字典数据
     */
    public function getDictData(Request $request)
    {
        $type = trim($request->get('type', ''));
        if (empty($type)) {
            return $this->error('字典类型不能为空');
        }

        try {
            $data = Db::name('dict_data')
                ->where('dict_type', $type)
                ->order('sort asc, id asc')
                ->select()
                ->toArray();

            return $this->success($data);
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_get_dict_data', $e, '获取字典数据失败');
        }
    }

    /**
     * 保存字典数据（新增或更新）
     */
    public function saveDictData(Request $request)
    {
        if (!$this->hasAdminPermission('system_manage')) {
            return $this->error('无权限操作', 403);
        }

        $id       = $request->post('id', 0);
        $dictType = trim($request->post('dict_type', ''));
        $label    = trim($request->post('label', ''));
        $value    = trim($request->post('value', ''));
        $sort     = (int) $request->post('sort', 0);
        $remark   = trim($request->post('remark', ''));

        if (empty($dictType) || empty($label) || $value === '') {
            return $this->error('字典类型、标签和值不能为空');
        }

        try {
            if ($id) {
                Db::name('dict_data')->where('id', $id)->update([
                    'label'  => $label,
                    'value'  => $value,
                    'sort'   => $sort,
                    'remark' => $remark,
                ]);
                return $this->success([], '更新成功');
            } else {
                $newId = Db::name('dict_data')->insertGetId([
                    'dict_type'  => $dictType,
                    'label'      => $label,
                    'value'      => $value,
                    'sort'       => $sort,
                    'remark'     => $remark,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                return $this->success(['id' => $newId], '创建成功');
            }
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_save_dict_data', $e, '保存字典数据失败');
        }
    }

    /**
     * 删除字典数据
     */
    public function deleteDictData($id)
    {
        if (!$this->hasAdminPermission('system_manage')) {
            return $this->error('无权限操作', 403);
        }

        try {
            $item = Db::name('dict_data')->find($id);
            if (!$item) {
                return $this->error('字典数据不存在', 404);
            }

            Db::name('dict_data')->where('id', $id)->delete();

            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_delete_dict_data', $e, '删除字典数据失败');
        }
    }

    // ===================== 管理员列表（兼容旧调用）=====================

    /**
     * 获取管理员列表
     */
    public function adminList()
    {
        if (!$this->hasAdminPermission('user_manage')) {
            return $this->error('无权限查看管理员列表', 403);
        }

        try {
            $admins = AdminUser::where('status', 1)
                ->field('id,username,nickname,role_id')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            return $this->success($admins, '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_admin_list', $e, '获取管理员列表失败');
        }
    }
}
