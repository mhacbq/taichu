<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\SystemConfig;
use app\service\ConfigService;
use think\Request;
use think\facade\Log;
use think\facade\Db;

/**
 * 系统设置控制器
 */
class SystemSettings extends BaseController
{
    /**
     * 获取系统设置
     */
    public function getSettings()
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限查看系统设置', 403);
        }

        try {
            $settings = $this->buildSystemSettingsResponse();

            $this->logOperation('view', 'config', [
                'detail' => '查看系统设置',
            ]);

            return $this->success($settings, '获取成功');
        } catch (\Exception $e) {
            Log::error('获取系统设置失败: ' . $e->getMessage());
            return $this->error('获取设置失败，请稍后重试', 500);
        }
    }

    /**
     * 保存系统设置
     */
    public function saveSettings(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限修改系统设置', 403);
        }

        $configKeys = [];
        try {
            $settings = $request->isPut() ? $request->put() : $request->post();

            if (empty($settings) || !is_array($settings)) {
                return $this->error('设置数据不能为空', 400);
            }

            $normalizedSettings = $this->normalizeSystemSettingsInput($settings);
            if (empty($normalizedSettings)) {
                return $this->error('没有可保存的系统设置项', 400);
            }

            $configTable = (new SystemConfig())->getTable();
            $configKeys = array_keys($normalizedSettings);
            $oldSettings = Db::table($configTable)
                ->whereIn('config_key', $configKeys)
                ->column('config_value', 'config_key');

            $updateCount = 0;
            foreach ($normalizedSettings as $key => $value) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                    continue;
                }

                $config = Db::table($configTable)->where('config_key', $key)->find();
                $configType = $this->resolveSystemSettingConfigType($key, $value, (string) ($config['config_type'] ?? 'string'));

                if ($config) {
                    Db::table($configTable)
                        ->where('config_key', $key)
                        ->update([
                            'config_value' => $this->processConfigValue($value, $configType),
                            'config_type'  => $configType,
                            'category'     => $this->resolveSystemSettingCategory($key, (string) ($config['category'] ?? 'custom')),
                            'updated_at'   => date('Y-m-d H:i:s'),
                        ]);
                } else {
                    Db::table($configTable)->insert([
                        'config_key'   => $key,
                        'config_value' => $this->processConfigValue($value, $configType),
                        'config_type'  => $configType,
                        'category'     => $this->resolveSystemSettingCategory($key),
                        'is_editable'  => 1,
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
                }
                $updateCount++;
            }

            ConfigService::clearCache();
            ConfigService::refreshCache();
            $latestSettings = $this->buildSystemSettingsResponse();

            $this->logOperation('update', 'config', [
                'detail'      => '更新系统设置',
                'before_data' => $oldSettings,
                'after_data'  => $latestSettings,
            ]);

            return $this->success([
                'updated_count' => $updateCount,
                'settings'      => $latestSettings,
            ], '保存成功');
        } catch (\Exception $e) {
            return $this->respondSystemException('admin_save_settings', $e, '保存失败，请稍后重试', [
                'config_keys'   => $configKeys,
                'settings_count' => count($configKeys),
            ]);
        }
    }

    // ─── 私有辅助方法 ────────────────────────────────────────────────────────

    /**
     * 构建系统设置响应数据
     */
    private function buildSystemSettingsResponse(): array
    {
        return [
            'site_name'         => (string) ConfigService::get('site_name', '太初命理'),
            'logo'              => (string) ConfigService::get('logo', ''),
            'site_description'  => (string) ConfigService::get('site_description', '专业的命理分析平台'),
            'register_points'   => (int) ConfigService::get('register_points', 100),
            'checkin_points'    => (int) ConfigService::get('checkin_points', ConfigService::get('points_sign_daily', 5)),
            'bazi_cost'         => (int) ConfigService::get('points_cost_bazi', ConfigService::get('bazi_cost', 20)),
            'tarot_cost'        => (int) ConfigService::get('points_cost_tarot', ConfigService::get('tarot_cost', 10)),
            'enable_register'   => $this->normalizeSettingBool(ConfigService::get('feature_register_enabled', ConfigService::get('enable_register', true)), true),
            'enable_daily'      => $this->normalizeSettingBool(ConfigService::get('feature_daily_enabled', ConfigService::get('enable_daily', true)), true),
            'enable_feedback'   => $this->normalizeSettingBool(ConfigService::get('feature_feedback_enabled', ConfigService::get('enable_feedback', true)), true),
            'enable_ai_analysis' => $this->normalizeSettingBool(ConfigService::get('feature_ai_analysis_enabled', ConfigService::get('enable_ai_analysis', true)), true),
        ];
    }

    /**
     * 把后台设置页的旧字段映射为真实业务配置键
     */
    private function normalizeSystemSettingsInput(array $settings): array
    {
        $normalized = [];

        if (array_key_exists('site_name', $settings)) {
            $normalized['site_name'] = trim((string) $settings['site_name']);
        }
        if (array_key_exists('logo', $settings)) {
            $normalized['logo'] = trim((string) $settings['logo']);
        }
        if (array_key_exists('site_description', $settings)) {
            $normalized['site_description'] = trim((string) $settings['site_description']);
        }
        if (array_key_exists('register_points', $settings)) {
            $normalized['register_points'] = max(0, (int) $settings['register_points']);
        }
        if (array_key_exists('checkin_points', $settings)) {
            $checkinPoints = max(0, (int) $settings['checkin_points']);
            $normalized['checkin_points'] = $checkinPoints;
            $normalized['points_sign_daily'] = $checkinPoints;
        }
        if (array_key_exists('bazi_cost', $settings)) {
            $baziCost = max(0, (int) $settings['bazi_cost']);
            $normalized['bazi_cost'] = $baziCost;
            $normalized['points_cost_bazi'] = $baziCost;
        }
        if (array_key_exists('tarot_cost', $settings)) {
            $tarotCost = max(0, (int) $settings['tarot_cost']);
            $normalized['tarot_cost'] = $tarotCost;
            $normalized['points_cost_tarot'] = $tarotCost;
        }
        if (array_key_exists('enable_register', $settings)) {
            $enabled = $this->normalizeSettingBool($settings['enable_register'], true);
            $normalized['enable_register'] = $enabled;
            $normalized['feature_register_enabled'] = $enabled;
        }
        if (array_key_exists('enable_daily', $settings)) {
            $enabled = $this->normalizeSettingBool($settings['enable_daily'], true);
            $normalized['enable_daily'] = $enabled;
            $normalized['feature_daily_enabled'] = $enabled;
        }
        if (array_key_exists('enable_feedback', $settings)) {
            $enabled = $this->normalizeSettingBool($settings['enable_feedback'], true);
            $normalized['enable_feedback'] = $enabled;
            $normalized['feature_feedback_enabled'] = $enabled;
        }
        if (array_key_exists('enable_ai_analysis', $settings)) {
            $enabled = $this->normalizeSettingBool($settings['enable_ai_analysis'], true);
            $normalized['enable_ai_analysis'] = $enabled;
            $normalized['feature_ai_analysis_enabled'] = $enabled;
        }

        return $normalized;
    }

    /**
     * 处理配置值为字符串
     */
    private function processConfigValue(mixed $value, string $type): string
    {
        return match ($type) {
            'json'  => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
            'bool'  => $value ? '1' : '0',
            'int'   => (string) (int) $value,
            'float' => (string) (float) $value,
            default => (string) $value,
        };
    }

    /**
     * 归一化布尔值
     */
    private function normalizeSettingBool(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value !== 0;
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
            return false;
        }

        return $default;
    }

    /**
     * 解析配置类型
     */
    private function resolveSystemSettingConfigType(string $key, mixed $value, string $fallback = 'string'): string
    {
        if (in_array($key, ['site_name', 'logo', 'site_description'], true)) {
            return 'string';
        }
        if (in_array($key, ['register_points', 'checkin_points', 'points_sign_daily', 'bazi_cost', 'points_cost_bazi', 'tarot_cost', 'points_cost_tarot'], true)) {
            return 'int';
        }
        if (in_array($key, ['enable_register', 'feature_register_enabled', 'enable_daily', 'feature_daily_enabled', 'enable_feedback', 'feature_feedback_enabled', 'enable_ai_analysis', 'feature_ai_analysis_enabled'], true)) {
            return 'bool';
        }
        if (is_bool($value)) {
            return 'bool';
        }
        if (is_int($value)) {
            return 'int';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_array($value)) {
            return 'json';
        }

        return $fallback !== '' ? $fallback : 'string';
    }

    /**
     * 解析配置分类
     */
    private function resolveSystemSettingCategory(string $key, string $fallback = 'custom'): string
    {
        if (in_array($key, ['site_name', 'logo', 'site_description'], true)) {
            return 'site';
        }
        if (in_array($key, ['register_points', 'checkin_points', 'points_sign_daily'], true)) {
            return 'points';
        }
        if (in_array($key, ['bazi_cost', 'points_cost_bazi', 'tarot_cost', 'points_cost_tarot'], true)) {
            return 'points_cost';
        }
        if (in_array($key, ['enable_register', 'feature_register_enabled', 'enable_daily', 'feature_daily_enabled', 'enable_feedback', 'feature_feedback_enabled', 'enable_ai_analysis', 'feature_ai_analysis_enabled'], true)) {
            return 'feature';
        }

        return $fallback !== '' ? $fallback : 'custom';
    }
}
