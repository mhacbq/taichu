<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/TestCase.php';
require __DIR__ . '/Unit/SensitiveConfigCryptTest.php';
require __DIR__ . '/Unit/PointsRecordNormalizationTest.php';

$tests = [
    tests\Unit\SensitiveConfigCryptTest::class,
    tests\Unit\PointsRecordNormalizationTest::class,
];

$failures = [];
foreach ($tests as $testClass) {
    try {
        $testClass::run();
        echo '[PASS] ' . $testClass . PHP_EOL;
    } catch (\Throwable $e) {
        $failures[] = sprintf('[FAIL] %s - %s', $testClass, $e->getMessage());
    }
}

if (!empty($failures)) {
    foreach ($failures as $failure) {
        fwrite(STDERR, $failure . PHP_EOL);
    }
    exit(1);
}

echo 'All backend smoke tests passed.' . PHP_EOL;
