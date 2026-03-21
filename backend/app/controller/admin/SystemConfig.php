<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\controller\admin\Base;
use app\model\SystemConfig;
use think\facade\Db;
use think\facade\Log;
use think\Response;

/**
 * 系统配置管理控制器
 * 用于管理支付、AI、推送、短信等系统配置
 */
class SystemConfig extends Base
{
    /**
     * 获取配置列表
     */
    public function index(): Response
    {
        $group = $this->request->get('group', 'payment');
        $validGroups = ['payment', 'ai', 'push', 'sms'];

        if (!in_array($group, $validGroups)) {
            return $this->error('无效的配置分组');
        }

        try {
            $configs = SystemConfig::getSafeConfigs($group);

            $groupNames = [
                'payment' => '支付配置',
                'ai' => 'AI服务配置',
                'push' => '推送服务配置',
                'sms' => '短信服务配置',
            ];

            return $this->success([
                'group' => $group,
                'group_name' => $groupNames[$group] ?? '未知配置',
                'configs' => $configs,
            ]);
        } catch (\Exception $e) {
            Log::error('获取系统配置失败: ' . $e->getMessage());
            return $this->error('获取配置失败: ' . $e->getMessage());
        }
    }

    /**
     * 保存配置
     */
    public function save(): Response
    {
        if (!$this->checkPermission('system_config', 'edit')) {
            return $this->error('无权限修改系统配置');
        }

        $group = $this->request->post('group', 'payment');
        $validGroups = ['payment', 'ai', 'push', 'sms'];

        if (!in_array($group, $validGroups)) {
            return $this->error('无效的配置分组');
        }

        $configs = $this->request->post('configs', []);

        if (empty($configs) || !is_array($configs)) {
            return $this->error('配置数据不能为空');
        }

        Db::startTrans();
        try {
            foreach ($configs as $key => $value) {
                $config = \app\model\SystemConfig::where('config_group', $group)
                    ->where('config_key', $key)
                    ->find();

                if (!$config) {
                    continue;
                }

                $isEncrypted = (bool) $config->is_encrypted;

                // 如果是敏感信息且值为masked,则不更新
                if (isset($value['is_masked']) && $value['is_masked']) {
                    continue;
                }

                $config->config_value = $isEncrypted
                    ? \app\service\SensitiveConfigCrypt::encrypt((string) $value)
                    : (string) $value;
                $config->save();
            }

            // 记录操作日志
            $this->logOperation('修改系统配置', [
                'group' => $group,
                'keys' => array_keys($configs),
            ]);

            Db::commit();
            return $this->success('配置保存成功');
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('保存系统配置失败: ' . $e->getMessage());
            return $this->error('保存配置失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试支付配置
     */
    public function testPayment(): Response
    {
        if (!$this->checkPermission('system_config', 'view')) {
            return $this->error('无权限测试配置');
        }

        $type = $this->request->post('type', 'wechat');

        try {
            if ($type === 'wechat') {
                $config = SystemConfig::getPaymentConfig('wechat');

                if (!$config || !$config['is_enabled']) {
                    return $this->error('微信支付未启用或配置不完整');
                }

                $required = ['mch_id', 'app_id', 'api_key', 'notify_url'];
                foreach ($required as $field) {
                    if (empty($config[$field])) {
                        return $this->error("微信支付配置缺少: {$field}");
                    }
                }

                return $this->success([
                    'type' => 'wechat',
                    'status' => 'ready',
                    'message' => '微信支付配置验证通过',
                ]);
            }

            if ($type === 'alipay') {
                $config = SystemConfig::getPaymentConfig('alipay');

                if (!$config || !$config['is_enabled']) {
                    return $this->error('支付宝支付未启用或配置不完整');
                }

                $required = ['app_id', 'private_key', 'public_key', 'notify_url'];
                foreach ($required as $field) {
                    if (empty($config[$field])) {
                        return $this->error("支付宝配置缺少: {$field}");
                    }
                }

                return $this->success([
                    'type' => 'alipay',
                    'status' => 'ready',
                    'message' => '支付宝支付配置验证通过',
                ]);
            }

            return $this->error('不支持的支付类型');
        } catch (\Exception $e) {
            Log::error('测试支付配置失败: ' . $e->getMessage());
            return $this->error('测试失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试AI服务配置
     */
    public function testAI(): Response
    {
        if (!$this->checkPermission('system_config', 'view')) {
            return $this->error('无权限测试配置');
        }

        try {
            $config = SystemConfig::getAIConfig();

            if (empty($config['ai_api_key'])) {
                return $this->error('AI API密钥未配置');
            }

            if (empty($config['ai_api_url'])) {
                return $this->error('AI API地址未配置');
            }

            if (empty($config['ai_is_enabled'])) {
                return $this->error('AI服务未启用');
            }

            return $this->success([
                'status' => 'ready',
                'message' => 'AI服务配置验证通过',
                'model' => $config['ai_model'] ?? '未知',
            ]);
        } catch (\Exception $e) {
            Log::error('测试AI配置失败: ' . $e->getMessage());
            return $this->error('测试失败: ' . $e->getMessage());
        }
    }

    /**
     * 导出配置
     */
    public function export(): Response
    {
        if (!$this->checkPermission('system_config', 'view')) {
            return $this->error('无权限导出配置');
        }

        $group = $this->request->get('group', 'payment');
        $validGroups = ['payment', 'ai', 'push', 'sms'];

        if (!in_array($group, $validGroups)) {
            return $this->error('无效的配置分组');
        }

        try {
            $configs = SystemConfig::getSafeConfigs($group);

            // 转换为JSON格式
            $exportData = [
                'group' => $group,
                'export_time' => date('Y-m-d H:i:s'),
                'configs' => array_map(function ($item) {
                    return [
                        'key' => $item['config_key'],
                        'description' => $item['description'],
                        'value' => $item['config_value'],
                        'is_masked' => $item['is_masked'] ?? false,
                    ];
                }, $configs),
            ];

            $filename = "system_config_{$group}_" . date('YmdHis') . '.json';

            return response(json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            Log::error('导出配置失败: ' . $e->getMessage());
            return $this->error('导出失败: ' . $e->getMessage());
        }
    }
}
