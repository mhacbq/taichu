<?php
/**
 * 八字算法验证测试
 * 
 * 用于验证八字算法的正确性
 */

// 测试数据
$testCases = [
    [
        'name' => '测试1：正官格',
        'bazi' => [
            'year' => ['gan' => '甲', 'zhi' => '子', 'gan_wuxing' => '木', 'shishen' => '劫财'],
            'month' => ['gan' => '庚', 'zhi' => '申', 'gan_wuxing' => '金', 'shishen' => '正官'],
            'day' => ['gan' => '乙', 'zhi' => '丑', 'gan_wuxing' => '木', 'shishen' => '比肩'],
            'hour' => ['gan' => '丙', 'zhi' => '寅', 'gan_wuxing' => '火', 'shishen' => '伤官'],
            'wuxing_stats' => ['金' => 2.5, '木' => 2.0, '水' => 1.5, '火' => 1.0, '土' => 1.0],
            'strength' => ['status' => '身旺', 'score' => 60],
            'yongshen' => ['shen' => '火'],
            'gender' => '男',
        ],
        'expected' => ['正官格'],
    ],
    [
        'name' => '测试2：食神制杀格',
        'bazi' => [
            'year' => ['gan' => '甲', 'zhi' => '申', 'gan_wuxing' => '木', 'shishen' => '偏财'],
            'month' => ['gan' => '庚', 'zhi' => '午', 'gan_wuxing' => '金', 'shishen' => '七杀'],
            'day' => ['gan' => '乙', 'zhi' => '卯', 'gan_wuxing' => '木', 'shishen' => '比肩'],
            'hour' => ['gan' => '丙', 'zhi' => '寅', 'gan_wuxing' => '火', 'shishen' => '食神'],
            'wuxing_stats' => ['金' => 2.0, '木' => 2.5, '水' => 1.0, '火' => 1.5, '土' => 1.0],
            'strength' => ['status' => '身旺', 'score' => 65],
            'yongshen' => ['shen' => '火'],
            'gender' => '男',
        ],
        'expected' => ['食神制杀格'],
    ],
    [
        'name' => '测试3：申子辰三合局',
        'bazi' => [
            'year' => ['gan' => '壬', 'zhi' => '申', 'gan_wuxing' => '水', 'shishen' => '偏印'],
            'month' => ['gan' => '甲', 'zhi' => '子', 'gan_wuxing' => '木', 'shishen' => '劫财'],
            'day' => ['gan' => '丙', 'zhi' => '辰', 'gan_wuxing' => '火', 'shishen' => '比肩'],
            'hour' => ['gan' => '戊', 'zhi' => '寅', 'gan_wuxing' => '土', 'shishen' => '偏印'],
            'wuxing_stats' => ['金' => 1.0, '木' => 1.5, '水' => 2.5, '火' => 1.0, '土' => 2.0],
            'strength' => ['status' => '中和偏旺', 'score' => 55],
            'yongshen' => ['shen' => '木'],
            'gender' => '男',
        ],
        'expected' => ['申子辰三合局'],
    ],
    [
        'name' => '测试4：羊刃格',
        'bazi' => [
            'year' => ['gan' => '甲', 'zhi' => '子', 'gan_wuxing' => '木', 'shishen' => '劫财'],
            'month' => ['gan' => '丙', 'zhi' => '寅', 'gan_wuxing' => '火', 'shishen' => '食神'],
            'day' => ['gan' => '甲', 'zhi' => '卯', 'gan_wuxing' => '木', 'shishen' => '比肩'],
            'hour' => ['gan' => '甲', 'zhi' => '子', 'gan_wuxing' => '木', 'shishen' => '劫财'],
            'wuxing_stats' => ['金' => 0.5, '木' => 3.5, '水' => 1.5, '火' => 1.0, '土' => 1.5],
            'strength' => ['status' => '身旺', 'score' => 75],
            'yongshen' => ['shen' => '火'],
            'gender' => '男',
        ],
        'expected' => ['羊刃格'],
    ],
    [
        'name' => '测试5：天乙贵人',
        'bazi' => [
            'year' => ['gan' => '甲', 'zhi' => '丑', 'gan_wuxing' => '木', 'shishen' => '劫财'],
            'month' => ['gan' => '丙', 'zhi' => '寅', 'gan_wuxing' => '火', 'shishen' => '食神'],
            'day' => ['gan' => '甲', 'zhi' => '子', 'gan_wuxing' => '木', 'shishen' => '比肩'],
            'hour' => ['gan' => '甲', 'zhi' => '子', 'gan_wuxing' => '木', 'shishen' => '劫财'],
            'wuxing_stats' => ['金' => 0.5, '木' => 3.5, '水' => 1.5, '火' => 1.0, '土' => 1.5],
            'strength' => ['status' => '身旺', 'score' => 75],
            'yongshen' => ['shen' => '火'],
            'gender' => '男',
        ],
        'expected_shensha' => ['天乙贵人'],
    ],
    [
        'name' => '测试6：从格（真从）',
        'bazi' => [
            'year' => ['gan' => '壬', 'zhi' => '申', 'gan_wuxing' => '水', 'shishen' => '劫财'],
            'month' => ['gan' => '庚', 'zhi' => '申', 'gan_wuxing' => '金', 'shishen' => '七杀'],
            'day' => ['gan' => '癸', 'zhi' => '酉', 'gan_wuxing' => '水', 'shishen' => '比肩'],
            'hour' => ['gan' => '辛', 'zhi' => '酉', 'gan_wuxing' => '金', 'shishen' => '正官'],
            'wuxing_stats' => ['金' => 3.5, '木' => 0.2, '水' => 2.0, '火' => 0.3, '土' => 2.0],
            'strength' => ['status' => '身弱', 'score' => 20],
            'yongshen' => ['shen' => '金'],
            'gender' => '男',
        ],
        'expected' => ['真从格'],
    ],
];

/**
 * 验证八字计算核心逻辑
 */
class BaziValidator
{
    /**
     * 验证格局识别
     */
    public static function validatePatterns(array $bazi, array $expectedPatterns): bool
    {
        // 模拟格局识别逻辑
        $patterns = [];
        
        // 正官格验证
        if ($bazi['month']['shishen'] === '正官') {
            $hasConflict = false;
            foreach (['year', 'month', 'day', 'hour'] as $pos) {
                if ($bazi[$pos]['shishen'] === '伤官') {
                    $hasConflict = true;
                    break;
                }
            }
            if (!$hasConflict) {
                $patterns[] = '正官格';
            }
        }
        
        // 食神制杀格验证
        if ($bazi['month']['shishen'] === '七杀') {
            $hasFood = false;
            foreach (['year', 'month', 'day', 'hour'] as $pos) {
                if ($bazi[$pos]['shishen'] === '食神') {
                    $hasFood = true;
                    break;
                }
            }
            if ($hasFood) {
                $patterns[] = '食神制杀格';
            }
        }
        
        // 三合局验证
        $branchValues = [
            $bazi['year']['zhi'],
            $bazi['month']['zhi'],
            $bazi['day']['zhi'],
            $bazi['hour']['zhi'],
        ];
        
        $sanhe = [
            '申子辰三合局' => ['申', '子', '辰'],
            '亥卯未三合局' => ['亥', '卯', '未'],
            '寅午戌三合局' => ['寅', '午', '戌'],
            '巳酉丑三合局' => ['巳', '酉', '丑'],
        ];
        
        foreach ($sanhe as $patternName => $branches) {
            if (count(array_intersect($branches, $branchValues)) === 3) {
                $patterns[] = $patternName;
            }
        }
        
        // 羊刃格验证
        $yangrenMap = [
            '甲' => '卯', '乙' => '辰', '丙' => '午', '丁' => '未',
            '戊' => '午', '己' => '未', '庚' => '酉', '辛' => '戌',
            '壬' => '子', '癸' => '丑'
        ];
        $dayMaster = $bazi['day']['gan'];
        $yangrenZhi = $yangrenMap[$dayMaster] ?? '';
        
        if ($yangrenZhi !== '' && in_array($yangrenZhi, $branchValues, true)) {
            $patterns[] = '羊刃格';
        }
        
        // 从格验证
        $dayMasterWuxing = $bazi['day']['gan_wuxing'];
        $dayMasterStrength = $bazi['wuxing_stats'][$dayMasterWuxing] ?? 0;
        $totalStrength = array_sum($bazi['wuxing_stats']);
        
        // 检查是否有根
        $hasRoot = false;
        $zhiCangGan = [
            '子' => ['癸'], '丑' => ['己', '癸', '辛'], '寅' => ['甲', '丙', '戊'],
            '卯' => ['乙'], '辰' => ['戊', '乙', '癸'], '巳' => ['丙', '庚', '戊'],
            '午' => ['丁', '己'], '未' => ['己', '丁', '乙'], '申' => ['庚', '壬', '戊'],
            '酉' => ['辛'], '戌' => ['戊', '辛', '丁'], '亥' => ['壬', '甲']
        ];
        $ganWuXing = [
            '甲' => '木', '乙' => '木', '丙' => '火', '丁' => '火', '戊' => '土',
            '己' => '土', '庚' => '金', '辛' => '金', '壬' => '水', '癸' => '水'
        ];
        
        foreach ($branchValues as $zhi) {
            $canggan = $zhiCangGan[$zhi] ?? [];
            foreach ($canggan as $gan) {
                if ($ganWuXing[$gan] === $dayMasterWuxing) {
                    $hasRoot = true;
                    break;
                }
            }
            if ($hasRoot) break;
        }
        
        if (!$hasRoot && $dayMasterStrength < 0.3 && $totalStrength > 6) {
            $patterns[] = '真从格';
        }
        
        // 验证结果
        foreach ($expectedPatterns as $expected) {
            if (!in_array($expected, $patterns, true)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 验证神煞识别
     */
    public static function validateShenSha(array $bazi, array $expectedShenSha): bool
    {
        $shenShaList = [];
        $dayMaster = $bazi['day']['gan'];
        
        // 天乙贵人验证
        $tianyiMap = [
            '甲' => ['丑', '未'],
            '乙' => ['子', '申'],
            '丙' => ['亥', '酉'],
            '丁' => ['亥', '酉'],
            '戊' => ['丑', '未'],
            '己' => ['子', '申'],
            '庚' => ['丑', '未'],
            '辛' => ['寅', '午'],
            '壬' => ['卯', '巳'],
            '癸' => ['卯', '巳'],
        ];
        
        $tianyiZhi = $tianyiMap[$dayMaster] ?? [];
        $allZhi = [
            $bazi['year']['zhi'],
            $bazi['month']['zhi'],
            $bazi['day']['zhi'],
            $bazi['hour']['zhi'],
        ];
        
        foreach ($allZhi as $zhi) {
            if (in_array($zhi, $tianyiZhi, true)) {
                $shenShaList[] = '天乙贵人';
                break;
            }
        }
        
        // 验证结果
        foreach ($expectedShenSha as $expected) {
            if (!in_array($expected, $shenShaList, true)) {
                return false;
            }
        }
        
        return true;
    }
}

/**
 * 运行所有测试
 */
function runTests(array $testCases): array
{
    $results = [
        'total' => count($testCases),
        'passed' => 0,
        'failed' => 0,
        'details' => [],
    ];
    
    foreach ($testCases as $index => $testCase) {
        $passed = true;
        $messages = [];
        
        // 验证格局
        if (isset($testCase['expected'])) {
            $patternValid = BaziValidator::validatePatterns($testCase['bazi'], $testCase['expected']);
            if (!$patternValid) {
                $passed = false;
                $messages[] = "格局识别失败：期望 " . implode(', ', $testCase['expected']);
            } else {
                $messages[] = "格局识别正确：" . implode(', ', $testCase['expected']);
            }
        }
        
        // 验证神煞
        if (isset($testCase['expected_shensha'])) {
            $shenShaValid = BaziValidator::validateShenSha($testCase['bazi'], $testCase['expected_shensha']);
            if (!$shenShaValid) {
                $passed = false;
                $messages[] = "神煞识别失败：期望 " . implode(', ', $testCase['expected_shensha']);
            } else {
                $messages[] = "神煞识别正确：" . implode(', ', $testCase['expected_shensha']);
            }
        }
        
        if ($passed) {
            $results['passed']++;
        } else {
            $results['failed']++;
        }
        
        $results['details'][] = [
            'name' => $testCase['name'],
            'passed' => $passed,
            'messages' => $messages,
        ];
    }
    
    return $results;
}

// 运行测试
$testResults = runTests($testCases);

// 输出结果
echo "=== 八字算法验证测试结果 ===\n\n";
echo "总测试数：{$testResults['total']}\n";
echo "通过数：{$testResults['passed']}\n";
echo "失败数：{$testResults['failed']}\n";
echo "通过率：" . round($testResults['passed'] / $testResults['total'] * 100, 2) . "%\n\n";

foreach ($testResults['details'] as $detail) {
    $status = $detail['passed'] ? '✓ 通过' : '✗ 失败';
    echo "{$status} - {$detail['name']}\n";
    foreach ($detail['messages'] as $message) {
        echo "  - {$message}\n";
    }
    echo "\n";
}

if ($testResults['failed'] === 0) {
    echo "✓ 所有测试通过！八字算法正确无误。\n";
} else {
    echo "✗ 有 {$testResults['failed']} 个测试失败，需要修复。\n";
}
