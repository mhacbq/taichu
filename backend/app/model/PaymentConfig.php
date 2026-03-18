<?php
declare(strict_types=1);

namespace app\model;

use app\service\SensitiveConfigCrypt;
use think\Model;

/**
 * 微信支付配置模型
 */
class PaymentConfig extends Model
{
    protected const ENCRYPTED_FIELDS = ['mch_id', 'api_key', 'api_cert', 'api_key_pem'];

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

        $values = self::decryptSensitiveValues($config, true);

        return [
            'mch_id' => $values['mch_id'],
            'app_id' => (string) $config->app_id,
            'api_key' => $values['api_key'],
            'api_cert' => $values['api_cert'],
            'api_key_pem' => $values['api_key_pem'],
            'notify_url' => (string) $config->notify_url,
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

        $values = self::decryptSensitiveValues($config, false);
        $maskedKey = self::maskValue($values['api_key'], 6, 6);

        return [
            'id' => $config->id,
            'mch_id' => $values['mch_id'],
            'app_id' => (string) $config->app_id,
            'api_key' => $maskedKey,
            'api_key_masked' => $maskedKey !== '',
            'has_cert' => $values['api_cert'] !== '',
            'has_key_pem' => $values['api_key_pem'] !== '',
            'notify_url' => (string) $config->notify_url,
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

        if (array_key_exists('mch_id', $data) && trim((string) $data['mch_id']) !== '') {
            $config->mch_id = SensitiveConfigCrypt::encrypt(trim((string) $data['mch_id']));
        }
        if (array_key_exists('app_id', $data) && trim((string) $data['app_id']) !== '') {
            $config->app_id = trim((string) $data['app_id']);
        }
        if (!empty($data['api_key']) && !($data['api_key_masked'] ?? false)) {
            $config->api_key = SensitiveConfigCrypt::encrypt(trim((string) $data['api_key']));
        }
        if (array_key_exists('api_cert', $data) && (string) $data['api_cert'] !== '') {
            $config->api_cert = SensitiveConfigCrypt::encrypt((string) $data['api_cert']);
        }
        if (array_key_exists('api_key_pem', $data) && (string) $data['api_key_pem'] !== '') {
            $config->api_key_pem = SensitiveConfigCrypt::encrypt((string) $data['api_key_pem']);
        }
        if (array_key_exists('notify_url', $data) && trim((string) $data['notify_url']) !== '') {
            $config->notify_url = trim((string) $data['notify_url']);
        }
        if (isset($data['is_enabled'])) {
            $config->is_enabled = $data['is_enabled'] ? 1 : 0;
        }

        return (bool) $config->save();
    }

    protected static function decryptSensitiveValues(self $config, bool $strict): array
    {
        $values = [];
        foreach (self::ENCRYPTED_FIELDS as $field) {
            $values[$field] = SensitiveConfigCrypt::decrypt((string) ($config->$field ?? ''), $strict);
        }

        return $values;
    }

    protected static function maskValue(string $value, int $prefixLength = 2, int $suffixLength = 2): string
    {
        if ($value === '') {
            return '';
        }

        $length = strlen($value);
        if ($length <= ($prefixLength + $suffixLength)) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, $prefixLength)
            . str_repeat('*', max(4, $length - $prefixLength - $suffixLength))
            . substr($value, -$suffixLength);
    }
}
