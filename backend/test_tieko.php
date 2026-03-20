<?php
/**
 * 铁口直断功能测试脚本
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/app.php';
$app->initialize();

// 测试八字数据（身旺配七杀）
$testBazi1 = [
    'year' => ['gan' => '甲', 'zhi' => '寅'],
    'month' => ['gan' => '丙', 'zhi' => '寅'],
    'day' => ['gan' => '庚', 'zhi' => '申'],
    'hour' => ['gan' => '壬', 'zhi' => '午'],
    'dayGan' => '庚',
    'dayZhi' => '申',
];

// 测试八字数据（官印相生）
$testBazi2 = [
    'year' => ['gan' => '壬', 'zhi' => '子'],
    'month' => ['gan' => '丁', 'zhi' => '巳'],
    'day' => ['gan' => '庚', 'zhi' => '申'],
    'hour' => ['gan' => '甲', 'zhi' => '寅'],
    'dayGan' => '庚',
    'dayZhi' => '申',
];

// 测试八字数据（身弱）
$testBazi3 = [
    'year' => ['gan' => '壬', 'zhi' => '午'],
    'month' => ['gan' => '丁', 'zhi' => '巳'],
    'day' => ['gan' => '庚', 'zhi' => '申'],
    'hour' => ['gan' => '甲', 'zhi' => '寅'],
    'dayGan' => '庚',
    'dayZhi' => '申',
];

$service = new \app\service\BaziPatternService();

echo "=== 测试 1: 身旺配七杀 ===\n";
$result1 = $service->analyzePattern($testBazi1, 'full');
print_r($result1['tiekoDingyu'] ?? []);
echo "匹配数量: " . ($result1['tiekoMatchCount'] ?? 0) . "\n";
echo "准确度: " . ($result1['tiekoAccuracy'] ?? 0) . "%\n";
echo "等级: " . ($result1['tiekoMatchLevel'] ?? 'unknown') . "\n\n";

echo "=== 测试 2: 官印相生 ===\n";
$result2 = $service->analyzePattern($testBazi2, 'full');
print_r($result2['tiekoDingyu'] ?? []);
echo "匹配数量: " . ($result2['tiekoMatchCount'] ?? 0) . "\n";
echo "准确度: " . ($result2['tiekoAccuracy'] ?? 0) . "%\n";
echo "等级: " . ($result2['tiekoMatchLevel'] ?? 'unknown') . "\n\n";

echo "=== 测试 3: 身弱 ===\n";
$result3 = $service->analyzePattern($testBazi3, 'full');
print_r($result3['tiekoDingyu'] ?? []);
echo "匹配数量: " . ($result3['tiekoMatchCount'] ?? 0) . "\n";
echo "准确度: " . ($result3['tiekoAccuracy'] ?? 0) . "%\n";
echo "等级: " . ($result3['tiekoMatchLevel'] ?? 'unknown') . "\n";

echo "\n测试完成！\n";
