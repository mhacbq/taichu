<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\SensitiveConfigCrypt;
use tests\TestCase;

class SensitiveConfigCryptTest extends TestCase
{
    public static function run(): void
    {
        putenv('SENSITIVE_CONFIG_KEY=unit-test-sensitive-config-key-20260318');
        $_ENV['SENSITIVE_CONFIG_KEY'] = 'unit-test-sensitive-config-key-20260318';

        $plainText = 'secret-value-123';
        $encrypted = SensitiveConfigCrypt::encrypt($plainText);

        self::assertTrue(SensitiveConfigCrypt::isEncrypted($encrypted), '加密结果应携带统一密文前缀');
        self::assertTrue($encrypted !== $plainText, '密文不应与原始明文一致');
        self::assertSame($plainText, SensitiveConfigCrypt::decrypt($encrypted, true), '密文应能够正确解密回原值');
        self::assertSame('raw-value', SensitiveConfigCrypt::decrypt('raw-value', true), '历史明文配置应保持兼容读取');
    }
}
