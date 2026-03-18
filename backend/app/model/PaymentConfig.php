<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 微信支付配置模型
 */
class PaymentConfig extends Model
{
    // 使用 tc_ 前缀，与数据库表名统一
    protected $name = 'tc_payment_config';
    
    protected $autoWriteTimestamp = true;
    
    protected $schema = [
        'id' => 'int',
        'type' => 'string',
        'mch_id' => 'string',
        'app_id' => 'string',
        'api_key' => 'string',
        'api_cert' => 'string',
        'api_key_pem' => 'string',
        'notify_url' => 'string',
        'is_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * 获取微信支付配置
     */
    public static function getWechatConfig(): ?array
    {
        $config = self::where('type', 'wechat_jsapi')
            ->where('is_enabled', 1)
            ->find();
            
        if (!$config) {
            return null;
        }
        
        return [
            'mch_id' => $config->mch_id,
            'app_id' => $config->app_id,
            'api_key' => $config->api_key,
            'api_cert' => $config->api_cert,
            'api_key_pem' => $config->api_key_pem,
            'notify_url' => $config->notify_url,
        ];
    }
    
    /**
     * 获取后台展示的配置（隐藏敏感信息）
     */
    public static function getSafeConfig(): ?array
    {
        $config = self::where('type', 'wechat_jsapi')->find();
        
        if (!$config) {
            return null;
        }
        
        // 隐藏API密钥中间部分
        $maskedKey = '';
        if ($config->api_key) {
            $len = strlen($config->api_key);
            $maskedKey = substr($config->api_key, 0, 6) . str_repeat('*', $len - 12) . substr($config->api_key, -6);
        }
        
        return [
            'id' => $config->id,
            'mch_id' => $config->mch_id,
            'app_id' => $config->app_id,
            'api_key' => $maskedKey,
            'api_key_masked' => true,
            'has_cert' => !empty($config->api_cert),
            'has_key_pem' => !empty($config->api_key_pem),
            'notify_url' => $config->notify_url,
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
        $config = self::where('type', $data['type'] ?? 'wechat_jsapi')->find();
        
        if (!$config) {
            $config = new self();
            $config->type = $data['type'] ?? 'wechat_jsapi';
        }
        
        if (!empty($data['mch_id'])) {
            $config->mch_id = $data['mch_id'];
        }
        if (!empty($data['app_id'])) {
            $config->app_id = $data['app_id'];
        }
        // 只有当提供了新密钥时才更新
        if (!empty($data['api_key']) && !$data['api_key_masked'] ?? true) {
            $config->api_key = $data['api_key'];
        }
        if (!empty($data['api_cert'])) {
            $config->api_cert = $data['api_cert'];
        }
        if (!empty($data['api_key_pem'])) {
            $config->api_key_pem = $data['api_key_pem'];
        }
        if (!empty($data['notify_url'])) {
            $config->notify_url = $data['notify_url'];
        }
        if (isset($data['is_enabled'])) {
            $config->is_enabled = $data['is_enabled'] ? 1 : 0;
        }
        
        return $config->save();
    }
}
