<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 短信配置模型
 */
class SmsConfig extends Model
{
    protected $name = 'sms_configs';
    
    protected $autoWriteTimestamp = true;
    
    protected $schema = [
        'id' => 'int',
        'provider' => 'string',
        'secret_id' => 'string',
        'secret_key' => 'string',
        'sdk_app_id' => 'string',
        'sign_name' => 'string',
        'template_code' => 'string',
        'template_register' => 'string',
        'template_reset' => 'string',
        'is_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * 获取腾讯云短信配置
     */
    public static function getTencentConfig(): ?array
    {
        $config = self::where('provider', 'tencent')
            ->where('is_enabled', 1)
            ->find();
            
        if (!$config) {
            return null;
        }
        
        return [
            'secret_id' => $config->secret_id,
            'secret_key' => $config->secret_key,
            'sdk_app_id' => $config->sdk_app_id,
            'sign_name' => $config->sign_name,
            'template_code' => $config->template_code,
            'template_register' => $config->template_register,
            'template_reset' => $config->template_reset,
        ];
    }
    
    /**
     * 获取后台展示的配置（隐藏敏感信息）
     */
    public static function getSafeConfig(): ?array
    {
        $config = self::where('provider', 'tencent')->find();
        
        if (!$config) {
            return null;
        }
        
        // 隐藏敏感信息
        $mask = function($str, $start = 6, $end = 6) {
            if (empty($str)) return '';
            $len = strlen($str);
            if ($len <= $start + $end) return str_repeat('*', $len);
            return substr($str, 0, $start) . str_repeat('*', $len - $start - $end) . substr($str, -$end);
        };
        
        return [
            'id' => $config->id,
            'provider' => $config->provider,
            'secret_id' => $mask($config->secret_id),
            'secret_key' => $config->secret_key ? '********' : '',
            'secret_id_masked' => true,
            'secret_key_masked' => true,
            'sdk_app_id' => $config->sdk_app_id,
            'sign_name' => $config->sign_name,
            'template_code' => $config->template_code,
            'template_register' => $config->template_register,
            'template_reset' => $config->template_reset,
            'is_enabled' => $config->is_enabled,
            'created_at' => $config->created_at,
            'updated_at' => $config->updated_at,
        ];
    }
    
    /**
     * 保存或更新配置
     */
    public static function saveConfig(array $data): bool
    {
        $config = self::where('provider', $data['provider'] ?? 'tencent')->find();
        
        if (!$config) {
            $config = new self();
            $config->provider = $data['provider'] ?? 'tencent';
        }
        
        $fields = ['secret_id', 'secret_key', 'sdk_app_id', 'sign_name', 'template_code', 'template_register', 'template_reset'];
        
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                // 如果值是脱敏的，则不更新
                if (strpos($data[$field], '***') === false) {
                    $config->$field = $data[$field];
                }
            }
        }
        
        if (isset($data['is_enabled'])) {
            $config->is_enabled = $data['is_enabled'] ? 1 : 0;
        }
        
        return $config->save();
    }
}
