<?php
declare(strict_types=1);

namespace app\service;

use RuntimeException;
use think\facade\Log;

/**
 * 敏感配置字段加密工具
 */
class SensitiveConfigCrypt
{
    private const PREFIX = 'enc:v1:';
    private const CIPHER = 'aes-256-gcm';
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;

    public static function isEncrypted(?string $value): bool
    {
        $value = (string) $value;
        return $value !== '' && str_starts_with($value, self::PREFIX);
    }

    public static function encrypt(?string $value): string
    {
        $plainText = (string) $value;
        if ($plainText === '' || self::isEncrypted($plainText)) {
            return $plainText;
        }

        if (!function_exists('openssl_encrypt')) {
            throw new RuntimeException('当前 PHP 环境缺少 openssl_encrypt，无法加密敏感配置');
        }

        $key = self::resolveBinaryKey(true);
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';
        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($cipherText === false || $tag === '') {
            throw new RuntimeException('敏感配置加密失败');
        }

        return self::PREFIX . base64_encode($iv . $tag . $cipherText);
    }

    public static function decrypt(?string $value, bool $strict = false): string
    {
        $value = (string) $value;
        if ($value === '' || !self::isEncrypted($value)) {
            return $value;
        }

        if (!function_exists('openssl_decrypt')) {
            if ($strict) {
                throw new RuntimeException('当前 PHP 环境缺少 openssl_decrypt，无法解密敏感配置');
            }

            return '';
        }

        try {
            $key = self::resolveBinaryKey($strict);
            if ($key === null) {
                return '';
            }

            $encodedPayload = substr($value, strlen(self::PREFIX));
            $payload = base64_decode($encodedPayload, true);
            if ($payload === false || strlen($payload) <= (self::IV_LENGTH + self::TAG_LENGTH)) {
                throw new RuntimeException('敏感配置密文格式无效');
            }

            $iv = substr($payload, 0, self::IV_LENGTH);
            $tag = substr($payload, self::IV_LENGTH, self::TAG_LENGTH);
            $cipherText = substr($payload, self::IV_LENGTH + self::TAG_LENGTH);

            $plainText = openssl_decrypt(
                $cipherText,
                self::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($plainText === false) {
                throw new RuntimeException('敏感配置解密失败');
            }

            return $plainText;
        } catch (\Throwable $e) {
            if ($strict) {
                throw new RuntimeException($e->getMessage(), 0, $e);
            }

            Log::warning('敏感配置解密失败，已按空值降级', [
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    private static function resolveBinaryKey(bool $strict): ?string
    {
        $rawKey = trim(self::resolveRawKey());
        if ($rawKey === '') {
            if ($strict) {
                throw new RuntimeException('缺少敏感配置加密密钥，请设置 SENSITIVE_CONFIG_KEY 或 JWT_SECRET');
            }

            return null;
        }

        return hash('sha256', $rawKey, true);
    }

    private static function resolveRawKey(): string
    {
        foreach (['SENSITIVE_CONFIG_KEY', 'JWT_SECRET'] as $envKey) {
            $value = self::readEnvValue($envKey);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function readEnvValue(string $key): string
    {
        $direct = getenv($key);
        if (is_string($direct) && trim($direct) !== '') {
            return trim($direct);
        }

        if (isset($_ENV[$key]) && is_string($_ENV[$key]) && trim($_ENV[$key]) !== '') {
            return trim($_ENV[$key]);
        }

        if (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && trim($_SERVER[$key]) !== '') {
            return trim($_SERVER[$key]);
        }

        if (class_exists('think\\facade\\Env')) {
            $value = \think\facade\Env::get($key, '');
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }
}
