<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;
use think\facade\Log;

class System extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取角色列表
     */
    public function getRoles()
    {
        $list = Db::name('tc_admin_role')->select();
        return $this->success($list);
    }

    /**
     * 创建角色
     */
    public function createRole()
    {
        $data = $this->request->post();
        if (empty($data['name']) || empty($data['code'])) {
            return $this->error('角色名称和编码不能为空');
        }

        $id = Db::name('tc_admin_role')->insertGetId([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? '',
            'status' => $data['status'] ?? 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->success(['id' => $id], '创建成功');
    }

    /**
     * 更新角色
     */
    public function updateRole()
    {
        $data = $this->request->put();
        $id = $this->request->param('id');
        if (empty($id)) return $this->error('ID不能为空');

        Db::name('tc_admin_role')->where('id', $id)->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? '',
            'status' => $data['status'] ?? 1
        ]);

        return $this->success([], '更新成功');
    }

    /**
     * 删除角色
     */
    public function deleteRole()
    {
        $id = $this->request->param('id');
        if (empty($id)) return $this->error('ID不能为空');

        // 超级管理员角色不能删除
        $role = Db::name('tc_admin_role')->where('id', $id)->find();
        if ($role && $role['is_super']) {
            return $this->error('超级管理员角色不能删除');
        }

        Db::name('tc_admin_role')->where('id', $id)->delete();
        Db::name('tc_admin_role_permission')->where('role_id', $id)->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 获取所有权限树
     */
    public function getPermissions()
    {
        // 这里返回系统中定义的所有权限
        // 实际项目中可以从数据库 tc_admin_permission 表读取
        $permissions = Db::name('tc_admin_permission')->select();
        
        // 简单的权限分类
        $tree = [];
        $modules = [];
        foreach ($permissions as $p) {
            $module = $p['module'];
            if (!isset($modules[$module])) {
                $modules[$module] = [
                    'id' => 'm_' . $module,
                    'name' => $this->getModuleName($module),
                    'children' => []
                ];
            }
            $modules[$module]['children'][] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'code' => $p['code']
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
        $roleId = $this->request->param('id');
        if (empty($roleId)) return $this->error('角色ID不能为空');

        $permissions = Db::name('tc_admin_role_permission')
            ->where('role_id', $roleId)
            ->column('permission_id');

        return $this->success($permissions);
    }

    /**
     * 更新角色权限
     */
    public function updateRolePermissions()
    {
        $roleId = $this->request->param('id');
        $permissionIds = $this->request->post('permission_ids/a', []);

        if (empty($roleId)) return $this->error('角色ID不能为空');

        Db::startTrans();
        try {
            Db::name('tc_admin_role_permission')->where('role_id', $roleId)->delete();
            
            $data = [];
            foreach ($permissionIds as $pid) {
                if (is_numeric($pid)) { // 过滤掉模块ID（m_...）
                    $data[] = [
                        'role_id' => $roleId,
                        'permission_id' => $pid,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                }
            }
            
            if (!empty($data)) {
                Db::name('tc_admin_role_permission')->insertAll($data);
            }
            
            Db::commit();
            return $this->success([], '权限保存成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('保存失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取字典类型列表
     */
    public function getDictTypes()
    {
        $list = Db::name('tc_admin_dict_type')->select();
        return $this->success($list);
    }

    /**
     * 创建字典类型
     */
    public function createDictType()
    {
        $data = $this->request->post();
        if (empty($data['name']) || empty($data['type'])) {
            return $this->error('名称和类型不能为空');
        }

        $id = Db::name('tc_admin_dict_type')->insertGetId([
            'name' => $data['name'],
            'type' => $data['type'],
            'status' => $data['status'] ?? 1,
            'remark' => $data['remark'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->success(['id' => $id], '创建成功');
    }

    /**
     * 更新字典类型
     */
    public function updateDictType()
    {
        $data = $this->request->put();
        $id = $this->request->param('id');
        if (empty($id)) return $this->error('ID不能为空');

        Db::name('tc_admin_dict_type')->where('id', $id)->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'status' => $data['status'] ?? 1,
            'remark' => $data['remark'] ?? ''
        ]);

        return $this->success([], '更新成功');
    }

    /**
     * 删除字典类型
     */
    public function deleteDictType()
    {
        $id = $this->request->param('id');
        if (empty($id)) return $this->error('ID不能为空');

        $type = Db::name('tc_admin_dict_type')->where('id', $id)->find();
        if ($type) {
            Db::name('tc_admin_dict_data')->where('dict_type', $type['type'])->delete();
            Db::name('tc_admin_dict_type')->where('id', $id)->delete();
        }

        return $this->success([], '删除成功');
    }

    /**
     * 获取字典数据列表
     */
    public function getDictData()
    {
        $dictType = $this->request->param('type');
        if (empty($dictType)) return $this->error('字典类型不能为空');

        $list = Db::name('tc_admin_dict_data')
            ->where('dict_type', $dictType)
            ->order('sort_order', 'asc')
            ->select();
            
        return $this->success($list);
    }

    /**
     * 保存字典数据
     */
    public function saveDictData()
    {
        $data = $this->request->post();
        if (empty($data['dict_type']) || empty($data['label']) || empty($data['value'])) {
            return $this->error('参数缺失');
        }

        if (isset($data['id']) && $data['id']) {
            Db::name('tc_admin_dict_data')->where('id', $data['id'])->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            Db::name('tc_admin_dict_data')->insert($data);
        }

        return $this->success([], '保存成功');
    }

    /**
     * 删除字典数据
     */
    public function deleteDictData()
    {
        $id = $this->request->param('id');
        if (empty($id)) return $this->error('ID不能为空');

        Db::name('tc_admin_dict_data')->where('id', $id)->delete();
        return $this->success([], '删除成功');
    }
}

