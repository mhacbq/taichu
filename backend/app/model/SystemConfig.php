<?php
declare(strict_types=1);

namespace app\model;

use app\service\SchemaInspector;
use think\Model;

/**
 * 系统配置模型
 */
class SystemConfig extends Model
{
    protected $table = 'tc_system_config';

    protected $pk = 'id';

    protected $schema = [
        'id' => 'int',
        'config_key' => 'string',
        'config_value' => 'string',
        'config_type' => 'string',
        'description' => 'string',
        'category' => 'string',
        'is_editable' => 'int',
        'sort_order' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function __construct(array $data = [])
    {
        $this->table = self::resolveTableName();
        parent::__construct($data);
    }


    /**
     * 获取配置值（自动转换类型）
     */
    public function getTypedValueAttribute($value, $data)
    {
        $val = $data['config_value'] ?? '';
        $type = self::normalizeConfigType((string) ($data['config_type'] ?? 'string'));

        switch ($type) {
            case 'int':
                return (int) $val;
            case 'float':
                return (float) $val;
            case 'bool':
                return self::normalizeBoolValue($val);
            case 'json':
                if (is_array($val)) {
                    return $val;
                }

                $decoded = json_decode((string) $val, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            default:
                return $val;
        }
    }


    /**
     * 根据键获取配置
     */
    public static function getByKey(string $key, $default = null)
    {
        $config = (new static())
            ->where('config_key', $key)
            ->order('id', 'desc')
            ->find();

        if (!$config) {
            return $default;
        }

        return $config->typed_value;
    }

    /**
     * 根据分类获取配置列表
     */
    public static function getByCategory(string $category): array
    {
        $configs = (new static())
            ->where('category', $category)
            ->order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select();

        $result = [];
        foreach ($configs as $config) {
            $result[$config->config_key] = $config->typed_value;
        }

        return $result;
    }

    /**
     * 获取所有配置（按分类组织）
     */
    public static function getAllGrouped(): array
    {
        $configs = (new static())
            ->order('category', 'asc')
            ->order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select();

        $result = [];
        foreach ($configs as $config) {
            $category = $config->category;
            if (!isset($result[$category])) {
                $result[$category] = [];
            }
            $result[$category][] = [
                'key' => $config->config_key,
                'value' => $config->typed_value,
                'type' => $config->config_type,
                'description' => $config->description,
                'editable' => (bool) $config->is_editable,
            ];

        }

        return $result;
    }

    /**
     * 更新配置值
     */
    public static function setValue(string $key, $value): bool
    {
        $config = (new static())
            ->where('config_key', $key)
            ->order('id', 'desc')
            ->find();

        if (!$config) {
            return false;
        }

        $configType = self::normalizeConfigType((string) ($config->config_type ?? 'string'));
        if ($configType === 'json' && is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if ($configType === 'bool') {
            $value = self::normalizeBoolValue($value) ? '1' : '0';
        }

        $config->config_type = $configType;
        $config->config_value = (string) $value;
        return $config->save();
    }


    /**
     * 批量更新配置
     */
    public static function setValues(array $configs): array
    {
        $results = [];
        foreach ($configs as $key => $value) {
            $results[$key] = self::setValue($key, $value);
        }
        return $results;
    }

    /**
     * 检查功能是否开启
     */
    public static function isFeatureEnabled(string $featureKey): bool
    {
        $value = self::getByKey("feature_{$featureKey}_enabled", true);

        if ($value === null) {
            return true;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value !== 0;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取积分消耗配置
     */
    public static function getPointsCosts(): array
    {
        $configs = (new static())
            ->where('config_key', 'like', 'points_cost_%')
            ->order('id', 'asc')
            ->select();

        $result = [];
        foreach ($configs as $config) {
            $key = str_replace('points_cost_', '', $config->config_key);
            $result[$key] = (int) $config->config_value;
        }

        return $result;
    }

    /**
     * 获取VIP配置
     */
    public static function getVipConfig(): array
    {
        return self::getByCategory('vip');
    }

    protected static function normalizeConfigType(string $type): string
    {
        $normalized = strtolower(trim($type));

        return match ($normalized) {
            'boolean' => 'bool',
            'integer' => 'int',
            'double', 'decimal' => 'float',
            default => $normalized !== '' ? $normalized : 'string',
        };
    }

    protected static function normalizeBoolValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value !== 0;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }

    protected static function resolveTableName(): string
    {
        foreach (['system_config', 'tc_system_config'] as $table) {
            if (SchemaInspector::tableExists($table)) {
                return $table;
            }
        }

        return 'system_config';
    }
}

