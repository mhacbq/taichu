<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\SystemConfig;
use think\Request;
use think\facade\Db;

class SystemSettings extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取系统配置
     */
    public function getSettings()
    {
        if (!$this->hasAdminPermission('config_manage')) {
            return $this->error('无权限查看系统配置', 403);
        }

        $params = $this->request->get();
        $group = $params['group'] ?? '';

        try {
            $query = SystemConfig::where('is_deleted', 0);

            if ($group) {
                $query->where('category', $group);
            }

            $settings = $query->select()->toArray();

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting['key']] = $setting['value'];
            }

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_system_settings', $e, '获取系统配置失败');
        }
    }

    /**
     * 保存系统配置
     */
    public function saveSettings()
    {
        if (!$this->hasAdminPermission('config_manage')) {
            return $this->error('无权限修改系统配置', 403);
        }

        $data = $this->request->post();
        $settings = $data['settings'] ?? [];

        if (empty($settings)) {
            return $this->error('配置数据不能为空');
        }

        try {
            Db::startTrans();

            foreach ($settings as $key => $value) {
                $config = SystemConfig::where('key', $key)->find();
                if ($config) {
                    $config->value = $value;
                    $config->save();
                } else {
                    SystemConfig::create([
                        'key' => $key,
                        'value' => $value,
                        'category' => $data['category'] ?? 'general',
                        'description' => '',
                        'is_deleted' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            $this->logOperation('save_system_settings', 'system', [
                'category' => $data['category'] ?? 'general',
                'settings' => array_keys($settings)
            ]);

            Db::commit();

            return $this->success([], '保存成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('admin_system_settings_save', $e, '保存系统配置失败');
        }
    }

    /**
     * 获取积分规则
     */
    public function getPointsRules()
    {
        try {
            $rules = SystemConfig::where('category', 'points')
                ->where('is_deleted', 0)
                ->select()
                ->toArray();

            return $this->success($rules);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_points_rules', $e, '获取积分规则失败');
        }
    }

    /**
     * 保存积分规则
     */
    public function savePointsRules()
    {
        if (!$this->hasAdminPermission('config_manage')) {
            return $this->error('无权限修改积分规则', 403);
        }

        $data = $this->request->post();

        try {
            if (isset($data['id']) && $data['id']) {
                $rule = SystemConfig::find($data['id']);
                if (!$rule) {
                    return $this->error('规则不存在', 404);
                }
                $rule->save($data);
            } else {
                $rule = SystemConfig::create($data);
            }

            return $this->success($rule, '保存成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_points_rules_save', $e, '保存积分规则失败');
        }
    }

    /**
     * 删除积分规则
     */
    public function deletePointsRule($id)
    {
        if (!$this->hasAdminPermission('config_manage')) {
            return $this->error('无权限删除积分规则', 403);
        }

        try {
            $rule = SystemConfig::find($id);
            if (!$rule) {
                return $this->error('规则不存在', 404);
            }

            $rule->is_deleted = 1;
            $rule->save();

            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_points_rules_delete', $e, '删除积分规则失败');
        }
    }
}
