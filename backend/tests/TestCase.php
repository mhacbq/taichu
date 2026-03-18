<?php
declare(strict_types=1);

namespace tests;

use RuntimeException;

abstract class TestCase
{
    protected static function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    protected static function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . '，期望：' . var_export($expected, true) . '，实际：' . var_export($actual, true));
        }
    }
}
