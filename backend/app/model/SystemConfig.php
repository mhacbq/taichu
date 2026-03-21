<?php
declare(strict_types=1);

namespace app\model;

use app\service\SensitiveConfigCrypt;
use think\Model;
use think\facade\Db;

/**
 * 系统统一配置模型
 * 用于管理支付、AI、推送、短信等系统配置
 */
class SystemConfig extends Model
{
    protected $table = 'tc_system_configs';

    protected $autoWriteTimestamp = true;

    protected $schema = [
        'id' => 'int',
        'config_group' => 'string',
        'config_key' => 'string',
        'config_value' => 'text',
        'config_type' => 'string',
        'is_encrypted' => 'boolean',
        'is_sensitive' => 'boolean',
        'description' => 'string',
        'sort_order' => 'int',
        'is_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 获取指定分组的配置
     */
    public static function getGroupConfig(string $group, bool $onlyEnabled = true): array
    {
        $query = self::where('config_group', $group);

        if ($onlyEnabled) {
            $query->where('is_enabled', 1);
        }

        $configs = $query->order('sort_order', 'asc')->select()->toArray();

        $result = [];
        foreach ($configs as $config) {
            $key = $config['config_key'];
            $value = self::parseConfigValue(
                $config['config_value'] ?? '',
                $config['config_type'] ?? 'string',
                (bool) ($config['is_encrypted'] ?? false)
            );
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * 获取单个配置值
     */
    public static function getValue(string $group, string $key, $default = null)
    {
        $config = self::where('config_group', $group)
            ->where('config_key', $key)
            ->where('is_enabled', 1)
            ->find();

        if (!$config) {
            return $default;
        }

        return self::parseConfigValue(
            $config->config_value ?? '',
            $config->config_type ?? 'string',
            (bool) $config->is_encrypted
        );
    }

    /**
     * 设置配置值
     */
    public static function setValue(string $group, string $key, $value, bool $encrypt = false): bool
    {
        $config = self::where('config_group', $group)
            ->where('config_key', $key)
            ->find();

        if ($config) {
            $config->config_value = $encrypt ? SensitiveConfigCrypt::encrypt((string) $value) : (string) $value;
            $config->is_encrypted = $encrypt ? 1 : 0;
            return $config->save() !== false;
        }

        return self::create([
            'config_group' => $group,
            'config_key' => $key,
            'config_value' => $encrypt ? SensitiveConfigCrypt::encrypt((string) $value) : (string) $value,
            'config_type' => is_bool($value) ? 'boolean' : (is_int($value) ? 'int' : 'string'),
            'is_encrypted' => $encrypt ? 1 : 0,
            'is_enabled' => 1,
        ]) !== false;
    }

    /**
     * 获取支付配置(兼容旧代码)
     */
    public static function getPaymentConfig(string $type = 'wechat'): ?array
    {
        $group = 'payment';

        if ($type === 'wechat') {
            return [
                'mch_id' => self::getValue($group, 'wechat_mch_id'),
                'app_id' => self::getValue($group, 'wechat_app_id'),
                'api_key' => self::getValue($group, 'wechat_api_key'),
                'api_cert' => self::getValue($group, 'wechat_api_cert'),
                'api_key_pem' => self::getValue($group, 'wechat_api_key_pem'),
                'notify_url' => self::getValue($group, 'wechat_notify_url'),
                'is_enabled' => (bool) self::getValue($group, 'wechat_is_enabled', false),
            ];
        }

        if ($type === 'alipay') {
            return [
                'app_id' => self::getValue($group, 'alipay_app_id'),
                'private_key' => self::getValue($group, 'alipay_private_key'),
                'public_key' => self::getValue($group, 'alipay_public_key'),
                'notify_url' => self::getValue($group, 'alipay_notify_url'),
                'return_url' => self::getValue($group, 'alipay_return_url'),
                'is_enabled' => (bool) self::getValue($group, 'alipay_is_enabled', false),
            ];
        }

        return null;
    }

    /**
     * 获取AI服务配置
     */
    public static function getAIConfig(): array
    {
        return self::getGroupConfig('ai');
    }

    /**
     * 获取推送服务配置
     */
    public static function getPushConfig(): array
    {
        return self::getGroupConfig('push');
    }

    /**
     * 获取短信服务配置
     */
    public static function getSmsConfig(): array
    {
        return self::getGroupConfig('sms');
    }

    /**
     * 获取管理端展示的配置(隐藏敏感信息)
     */
    public static function getSafeConfigs(string $group): array
    {
        $configs = self::where('config_group', $group)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();

        foreach ($configs as &$config) {
            if ($config['is_sensitive'] && $config['config_value']) {
                $config['config_value'] = self::maskSensitiveValue($config['config_value']);
                $config['is_masked'] = true;
            } else {
                $config['is_masked'] = false;
            }
        }

        return $configs;
    }

    /**
     * 保存配置(批量)
     */
    public static function saveConfigs(string $group, array $configs): bool
    {
        Db::startTrans();
        try {
            foreach ($configs as $key => $value) {
                $config = self::where('config_group', $group)
                    ->where('config_key', $key)
                    ->find();

                $isEncrypted = (bool) self::where('config_group', $group)
                    ->where('config_key', $key)
                    ->value('is_encrypted');

                if ($config) {
                    // 如果是敏感信息且值为masked,则不更新
                    if (isset($value['is_masked']) && $value['is_masked']) {
                        continue;
                    }

                    $config->config_value = $isEncrypted
                        ? SensitiveConfigCrypt::encrypt((string) $value)
                        : (string) $value;
                    $config->save();
                }
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 解析配置值
     */
    protected static function parseConfigValue(string $value, string $type, bool $encrypted): mixed
    {
        if ($encrypted) {
            $value = SensitiveConfigCrypt::decrypt($value);
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            'json' => json_decode($value, true) ?: [],
            default => $value,
        };
    }

    /**
     * 掩码敏感信息
     */
    protected static function maskSensitiveValue(string $value, int $showLength = 4): string
    {
        if (empty($value)) {
            return '';
        }

        $length = strlen($value);
        if ($length <= $showLength * 2) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, $showLength)
            . str_repeat('*', max(4, $length - $showLength * 2))
            . substr($value, -$showLength);
    }
}
