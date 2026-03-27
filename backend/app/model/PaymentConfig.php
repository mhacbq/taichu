<?php
declare(strict_types=1);

namespace app\model;

use app\service\SchemaInspector;
use app\service\SensitiveConfigCrypt;
use think\Model;
use think\facade\Db;

/**
 * 微信支付配置模型
 */
class PaymentConfig extends Model
{
    protected const ENCRYPTED_FIELDS = ['mch_id', 'api_key', 'api_cert', 'api_key_pem'];

    protected $table = 'tc_payment_config';

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
        $config = self::findConfigRow('wechat_jsapi', true);
        if ($config === null) {
            return null;
        }

        $values = self::decryptSensitiveValues($config, true);

        return [
            'mch_id' => $values['mch_id'],
            'app_id' => (string) ($config['app_id'] ?? ''),
            'api_key' => $values['api_key'],
            'api_cert' => $values['api_cert'],
            'api_key_pem' => $values['api_key_pem'],
            'notify_url' => (string) ($config['notify_url'] ?? ''),
        ];
    }

    /**
     * 获取后台展示的配置（隐藏敏感信息�?
     */
    public static function getSafeConfig(): ?array
    {
        $config = self::findConfigRow('wechat_jsapi');
        if ($config === null) {
            return null;
        }

        $values = self::decryptSensitiveValues($config, false);
        $maskedKey = self::maskValue($values['api_key'], 6, 6);

        return [
            'id' => (int) ($config['id'] ?? 0),
            'mch_id' => $values['mch_id'],
            'app_id' => (string) ($config['app_id'] ?? ''),
            'api_key' => $maskedKey,
            'api_key_masked' => $maskedKey !== '',
            'has_cert' => $values['api_cert'] !== '',
            'has_key_pem' => $values['api_key_pem'] !== '',
            'notify_url' => (string) ($config['notify_url'] ?? ''),
            'is_enabled' => !empty($config['is_enabled']),
            'created_at' => $config['created_at'] ?? null,
            'updated_at' => $config['updated_at'] ?? null,
        ];
    }

    /**
     * 保存或更新配�?
     */
    public static function saveConfig(array $data): bool
    {
        $table = self::resolveTable();
        $type = trim((string) ($data['type'] ?? 'wechat_jsapi')) ?: 'wechat_jsapi';
        $existing = self::findConfigRow($type);
        $now = date('Y-m-d H:i:s');

        $payload = [];

        if ($existing === null) {
            $payload['type'] = $type;
            $payload['created_at'] = $now;
        }

        if (array_key_exists('mch_id', $data) && trim((string) $data['mch_id']) !== '') {
            $payload['mch_id'] = SensitiveConfigCrypt::encrypt(trim((string) $data['mch_id']));
        }
        if (array_key_exists('app_id', $data) && trim((string) $data['app_id']) !== '') {
            $payload['app_id'] = trim((string) $data['app_id']);
        }
        if (!empty($data['api_key']) && !($data['api_key_masked'] ?? false)) {
            $payload['api_key'] = SensitiveConfigCrypt::encrypt(trim((string) $data['api_key']));
        }
        if (array_key_exists('api_cert', $data) && (string) $data['api_cert'] !== '') {
            $payload['api_cert'] = SensitiveConfigCrypt::encrypt((string) $data['api_cert']);
        }
        if (array_key_exists('api_key_pem', $data) && (string) $data['api_key_pem'] !== '') {
            $payload['api_key_pem'] = SensitiveConfigCrypt::encrypt((string) $data['api_key_pem']);
        }
        if (array_key_exists('notify_url', $data) && trim((string) $data['notify_url']) !== '') {
            $payload['notify_url'] = trim((string) $data['notify_url']);
        }
        if (isset($data['is_enabled'])) {
            $payload['is_enabled'] = $data['is_enabled'] ? 1 : 0;
        }

        $payload['updated_at'] = $now;

        if ($existing !== null) {
            return Db::table($table)->where('id', (int) $existing['id'])->update($payload) !== false;
        }

        return Db::table($table)->insert($payload) === 1;
    }

    protected static function findConfigRow(string $type, bool $onlyEnabled = false): ?array
    {
        $query = Db::table(self::resolveTable())
            ->where('type', $type);

        if ($onlyEnabled) {
            $query->where('is_enabled', 1);
        }

        $config = $query->find();

        return is_array($config) ? $config : null;
    }

    protected static function resolveTable(): string
    {
        foreach (['tc_payment_config', 'tc_payment_config'] as $table) {
            if (SchemaInspector::tableExists($table)) {
                return $table;
            }
        }

        return 'tc_payment_config';
    }

    protected static function decryptSensitiveValues(array $config, bool $strict): array
    {
        $values = [];
        foreach (self::ENCRYPTED_FIELDS as $field) {
            $values[$field] = SensitiveConfigCrypt::decrypt((string) ($config[$field] ?? ''), $strict);
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
