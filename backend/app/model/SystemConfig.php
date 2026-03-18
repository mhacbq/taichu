<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 系统配置模型
 */
class SystemConfig extends Model
{
    protected $table = 'system_config';
    
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
    
    protected $json = ['config_value'];
    
    protected $jsonAssoc = true;
    
    /**
     * 获取配置值（自动转换类型）
     */
    public function getTypedValueAttribute($value, $data)
    {
        $val = $data['config_value'] ?? '';
        $type = $data['config_type'] ?? 'string';
        
        switch ($type) {
            case 'int':
                return (int) $val;
            case 'float':
                return (float) $val;
            case 'bool':
                return in_array($val, ['1', 'true', 'on', 'yes'], true);
            case 'json':
                return json_decode($val, true);
            default:
                return $val;
        }
    }
    
    /**
     * 根据键获取配置
     */
    public static function getByKey(string $key, $default = null)
    {
        $config = self::where('config_key', $key)->find();
        
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
        $configs = self::where('category', $category)
            ->order('sort_order', 'asc')
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
        $configs = self::order('category', 'asc')
            ->order('sort_order', 'asc')
            ->select();
        
        $result = [];
        foreach ($configs as $config) {
            $category = $config->category;
            if (!isset($result[$category])) {
                $result[$category] = [];
            }
            $result[$category][] = [
                'key' => $config->config_key,
                'value' => $config->config_value,
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
        $config = self::where('config_key', $key)->find();
        
        if (!$config) {
            return false;
        }
        
        if ($config->config_type === 'json' && is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
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
            return (int)$value !== 0;
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
        $configs = self::where('config_key', 'like', 'points_cost_%')->select();
        
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
}