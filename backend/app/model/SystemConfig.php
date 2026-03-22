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

    private const GROUPED_TABLE = 'tc_system_configs';
    private const LEGACY_TABLE = 'system_config';
    private const LEGACY_TC_TABLE = 'tc_system_config';

    private static ?string $legacyConfigTable = null;

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
    public static function getGroupValue(string $group, string $key, $default = null)
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
    public static function setValue(string $groupOrKey, $keyOrValue = null, $value = null, bool $encrypt = false): bool
    {
        if (func_num_args() <= 2) {
            return self::setByKey($groupOrKey, $keyOrValue);
        }

        $group = $groupOrKey;
        $key = (string) $keyOrValue;

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
                'mch_id' => self::getGroupValue($group, 'wechat_mch_id'),
                'app_id' => self::getGroupValue($group, 'wechat_app_id'),
                'api_key' => self::getGroupValue($group, 'wechat_api_key'),
                'api_cert' => self::getGroupValue($group, 'wechat_api_cert'),
                'api_key_pem' => self::getGroupValue($group, 'wechat_api_key_pem'),
                'notify_url' => self::getGroupValue($group, 'wechat_notify_url'),
                'is_enabled' => (bool) self::getGroupValue($group, 'wechat_is_enabled', false),
            ];
        }

        if ($type === 'alipay') {
            return [
                'app_id' => self::getGroupValue($group, 'alipay_app_id'),
                'private_key' => self::getGroupValue($group, 'alipay_private_key'),
                'public_key' => self::getGroupValue($group, 'alipay_public_key'),
                'notify_url' => self::getGroupValue($group, 'alipay_notify_url'),
                'return_url' => self::getGroupValue($group, 'alipay_return_url'),
                'is_enabled' => (bool) self::getGroupValue($group, 'alipay_is_enabled', false),
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
     * 兼容旧配置服务：按 key 获取配置值
     */
    public static function getByKey(string $key, $default = null)
    {
        $legacyTable = self::resolveLegacyConfigTable();
        if ($legacyTable !== null) {
            try {
                $keyField = $legacyTable === self::LEGACY_TABLE ? 'config_key' : 'key';
                $valueField = $legacyTable === self::LEGACY_TABLE ? 'config_value' : 'value';
                $typeField = $legacyTable === self::LEGACY_TABLE ? 'config_type' : 'type';

                $config = Db::table($legacyTable)
                    ->where($keyField, $key)
                    ->find();

                if ($config) {
                    return self::parseConfigValue(
                        (string) ($config[$valueField] ?? ''),
                        (string) ($config[$typeField] ?? 'string'),
                        false
                    );
                }
            } catch (\Throwable) {
            }
        }

        try {
            $config = self::where('config_key', $key)
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
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * 兼容旧配置服务：按分类获取配置
     */
    public static function getByCategory(string $category): array
    {
        $legacyTable = self::resolveLegacyConfigTable();
        if ($legacyTable !== null) {
            try {
                $groupField = $legacyTable === self::LEGACY_TABLE ? 'category' : 'group';
                $keyField = $legacyTable === self::LEGACY_TABLE ? 'config_key' : 'key';
                $valueField = $legacyTable === self::LEGACY_TABLE ? 'config_value' : 'value';
                $typeField = $legacyTable === self::LEGACY_TABLE ? 'config_type' : 'type';
                $sortField = $legacyTable === self::LEGACY_TABLE ? 'sort_order' : 'sort';

                $rows = Db::table($legacyTable)
                    ->where($groupField, $category)
                    ->order($sortField, 'asc')
                    ->select()
                    ->toArray();

                $result = [];
                foreach ($rows as $row) {
                    $result[$row[$keyField]] = self::parseConfigValue(
                        (string) ($row[$valueField] ?? ''),
                        (string) ($row[$typeField] ?? 'string'),
                        false
                    );
                }

                return $result;
            } catch (\Throwable) {
            }
        }

        return self::getGroupConfig($category);
    }

    /**
     * 兼容旧配置服务：批量设置
     */
    public static function setValues(array $configs): array
    {
        $results = [];
        foreach ($configs as $key => $value) {
            $results[$key] = self::setByKey((string) $key, $value);
        }

        return $results;
    }

    /**
     * 兼容旧配置服务：获取所有配置并按组返回
     */
    public static function getAllGrouped(): array
    {
        $legacyTable = self::resolveLegacyConfigTable();
        if ($legacyTable !== null) {
            try {
                $groupField = $legacyTable === self::LEGACY_TABLE ? 'category' : 'group';
                $keyField = $legacyTable === self::LEGACY_TABLE ? 'config_key' : 'key';
                $valueField = $legacyTable === self::LEGACY_TABLE ? 'config_value' : 'value';
                $typeField = $legacyTable === self::LEGACY_TABLE ? 'config_type' : 'type';
                $sortField = $legacyTable === self::LEGACY_TABLE ? 'sort_order' : 'sort';

                $rows = Db::table($legacyTable)
                    ->order($groupField, 'asc')
                    ->order($sortField, 'asc')
                    ->select()
                    ->toArray();

                $result = [];
                foreach ($rows as $row) {
                    $group = (string) ($row[$groupField] ?? 'general');
                    $result[$group][$row[$keyField]] = self::parseConfigValue(
                        (string) ($row[$valueField] ?? ''),
                        (string) ($row[$typeField] ?? 'string'),
                        false
                    );
                }

                return $result;
            } catch (\Throwable) {
            }
        }

        $rows = self::order('config_group', 'asc')
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();

        $result = [];
        foreach ($rows as $row) {
            $group = (string) ($row['config_group'] ?? 'general');
            $result[$group][$row['config_key']] = self::parseConfigValue(
                (string) ($row['config_value'] ?? ''),
                (string) ($row['config_type'] ?? 'string'),
                (bool) ($row['is_encrypted'] ?? false)
            );
        }

        return $result;
    }

    /**
     * 兼容旧配置服务：获取积分消耗配置
     */
    public static function getPointsCosts(): array
    {
        $defaults = [
            'bazi' => 10,
            'bazi_simple' => 10,
            'bazi_pro' => 50,
            'bazi_ai' => 30,
            'tarot' => 5,
            'tarot_ai' => 20,
            'liuyao' => 15,
            'hehun' => 80,
            'hehun_export' => 30,
            'daily' => 0,
        ];

        $result = [];
        foreach ($defaults as $key => $default) {
            $result[$key] = (int) self::getByKey('points_cost_' . $key, $default);
        }

        // Backward compatibility: ensure bazi corresponds to bazi_simple if not set explicitly, or just keep it as base
        if (!isset($result['bazi_simple'])) {
            $result['bazi_simple'] = $result['bazi'];
        }

        return $result;
    }

    /**
     * 兼容 ConfigService 中的 typed_value 访问
     */
    public function getTypedValueAttr($value, array $data): mixed
    {
        return self::parseConfigValue(
            (string) ($data['config_value'] ?? ''),
            (string) ($data['config_type'] ?? 'string'),
            (bool) ($data['is_encrypted'] ?? false)
        );
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
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true) ?: [],
            default => $value,
        };
    }

    protected static function resolveLegacyConfigTable(): ?string
    {
        if (self::$legacyConfigTable !== null) {
            return self::$legacyConfigTable;
        }

        foreach ([self::LEGACY_TABLE, self::LEGACY_TC_TABLE] as $table) {
            if (self::tableExists($table)) {
                self::$legacyConfigTable = $table;
                return self::$legacyConfigTable;
            }
        }

        self::$legacyConfigTable = '';
        return null;
    }

    protected static function tableExists(string $table): bool
    {
        $escaped = addslashes($table);

        try {
            return !empty(Db::query("SHOW TABLES LIKE '{$escaped}'"));
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function setByKey(string $key, $value): bool
    {
        $legacyTable = self::resolveLegacyConfigTable();
        if ($legacyTable !== null) {
            try {
                $keyField = $legacyTable === self::LEGACY_TABLE ? 'config_key' : 'key';
                $valueField = $legacyTable === self::LEGACY_TABLE ? 'config_value' : 'value';
                $typeField = $legacyTable === self::LEGACY_TABLE ? 'config_type' : 'type';
                $groupField = $legacyTable === self::LEGACY_TABLE ? 'category' : 'group';
                $sortField = $legacyTable === self::LEGACY_TABLE ? 'sort_order' : 'sort';
                $editableField = $legacyTable === self::LEGACY_TABLE ? 'is_editable' : 'is_public';

                $existing = Db::table($legacyTable)->where($keyField, $key)->find();
                $payload = [
                    $keyField => $key,
                    $valueField => self::normalizeValue($value),
                    $typeField => self::detectConfigType($value),
                    $groupField => $existing[$groupField] ?? self::guessConfigGroup($key),
                    'description' => $existing['description'] ?? $key,
                    $sortField => (int) ($existing[$sortField] ?? 0),
                    $editableField => (int) ($existing[$editableField] ?? 1),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if ($existing) {
                    return Db::table($legacyTable)
                        ->where('id', (int) $existing['id'])
                        ->update($payload) !== false;
                }

                $payload['created_at'] = date('Y-m-d H:i:s');
                return Db::table($legacyTable)->insert($payload) > 0;
            } catch (\Throwable) {
            }
        }

        $existing = self::where('config_key', $key)->find();
        if ($existing) {
            $existing->config_value = self::normalizeValue($value);
            $existing->config_type = self::detectConfigType($value);
            return $existing->save() !== false;
        }

        return self::create([
            'config_group' => self::guessConfigGroup($key),
            'config_key' => $key,
            'config_value' => self::normalizeValue($value),
            'config_type' => self::detectConfigType($value),
            'is_encrypted' => 0,
            'is_enabled' => 1,
        ]) !== false;
    }

    protected static function normalizeValue($value): string
    {
        if (is_array($value) || is_object($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    protected static function detectConfigType($value): string
    {
        return match (true) {
            is_bool($value) => 'bool',
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_array($value), is_object($value) => 'json',
            default => 'string',
        };
    }

    protected static function guessConfigGroup(string $key): string
    {
        foreach ([
            'feature_' => 'feature',
            'points_cost_' => 'points_cost',
            'points_' => 'points',
            'vip_' => 'vip',
            'new_user_' => 'new_user',
            'limited_offer_' => 'limited_offer',
            'recharge_' => 'recharge',
            'report_tier_' => 'report_tier',
            'site_' => 'site',
            'contact_' => 'contact',
            'ai_' => 'ai',
            'sms_' => 'sms',
            'push_' => 'push',
            'wechat_' => 'payment',
            'alipay_' => 'payment',
        ] as $prefix => $group) {
            if (str_starts_with($key, $prefix)) {
                return $group;
            }
        }

        return 'general';
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
