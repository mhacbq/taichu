<?php
declare(strict_types=1);

namespace app\model;

use app\service\SensitiveConfigCrypt;
use think\Model;

/**
 * 短信配置模型
 */
class SmsConfig extends Model
{
    protected const ENCRYPTED_FIELDS = ['secret_id', 'secret_key', 'sign_name'];

    protected $name = 'tc_sms_config';

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

        $values = self::decryptSensitiveValues($config, true);

        return [
            'secret_id' => $values['secret_id'],
            'secret_key' => $values['secret_key'],
            'sdk_app_id' => (string) $config->sdk_app_id,
            'sign_name' => $values['sign_name'],
            'template_code' => (string) $config->template_code,
            'template_register' => (string) $config->template_register,
            'template_reset' => (string) $config->template_reset,
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

        $values = self::decryptSensitiveValues($config, false);

        return [
            'id' => $config->id,
            'provider' => (string) $config->provider,
            'secret_id' => self::maskValue($values['secret_id'], 6, 6),
            'secret_key' => $values['secret_key'] !== '' ? '********' : '',
            'secret_id_masked' => $values['secret_id'] !== '',
            'secret_key_masked' => $values['secret_key'] !== '',
            'sdk_app_id' => (string) $config->sdk_app_id,
            'sign_name' => $values['sign_name'],
            'template_code' => (string) $config->template_code,
            'template_register' => (string) $config->template_register,
            'template_reset' => (string) $config->template_reset,
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
            if (!array_key_exists($field, $data) || $data[$field] === '') {
                continue;
            }

            $value = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            if (is_string($value) && strpos($value, '***') !== false) {
                continue;
            }

            if (in_array($field, self::ENCRYPTED_FIELDS, true)) {
                $config->$field = SensitiveConfigCrypt::encrypt((string) $value);
                continue;
            }

            $config->$field = $value;
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
