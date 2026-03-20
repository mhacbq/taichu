<?php
declare(strict_types=1);

namespace app\service;

/**
 * 八字格局分析服务
 * 
 * 提供传统命理格局分析，包括：
 * - 八格分析（正格）
 * - 特殊格局
 * - 神煞分析
 * - 命理定语生成
 */
class BaziPatternService
{
    /**
     * 十天干
     */
    protected $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
    
    /**
     * 十二地支
     */
    protected $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
    
    /**
     * 天干五行
     */
    protected $ganWuXing = [
        '甲' => '木', '乙' => '木', '丙' => '火', '丁' => '火', '戊' => '土',
        '己' => '土', '庚' => '金', '辛' => '金', '壬' => '水', '癸' => '水'
    ];
    
    /**
     * 地支五行
     */
    protected $zhiWuXing = [
        '子' => '水', '丑' => '土', '寅' => '木', '卯' => '木', '辰' => '土', '巳' => '火',
        '午' => '火', '未' => '土', '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水'
    ];
    
    /**
     * 十神关系表
     */
    protected $shiShenTable = [
        '甲' => ['甲' => '比肩', '乙' => '劫财', '丙' => '食神', '丁' => '伤官', '戊' => '偏财', '己' => '正财', '庚' => '七杀', '辛' => '正官', '壬' => '偏印', '癸' => '正印'],
        '乙' => ['甲' => '劫财', '乙' => '比肩', '丙' => '伤官', '丁' => '食神', '戊' => '正财', '己' => '偏财', '庚' => '正官', '辛' => '七杀', '壬' => '正印', '癸' => '偏印'],
        '丙' => ['甲' => '偏印', '乙' => '正印', '丙' => '比肩', '丁' => '劫财', '戊' => '食神', '己' => '伤官', '庚' => '偏财', '辛' => '正财', '壬' => '七杀', '癸' => '正官'],
        '丁' => ['甲' => '正印', '乙' => '偏印', '丙' => '劫财', '丁' => '比肩', '戊' => '伤官', '己' => '食神', '庚' => '正财', '辛' => '偏财', '壬' => '正官', '癸' => '七杀'],
        '戊' => ['甲' => '七杀', '乙' => '正官', '丙' => '偏印', '丁' => '正印', '戊' => '比肩', '己' => '劫财', '庚' => '食神', '辛' => '伤官', '壬' => '偏财', '癸' => '正财'],
        '己' => ['甲' => '正官', '乙' => '七杀', '丙' => '正印', '丁' => '偏印', '戊' => '劫财', '己' => '比肩', '庚' => '伤官', '辛' => '食神', '壬' => '正财', '癸' => '偏财'],
        '庚' => ['甲' => '偏财', '乙' => '正财', '丙' => '七杀', '丁' => '正官', '戊' => '偏印', '己' => '正印', '庚' => '比肩', '辛' => '劫财', '壬' => '食神', '癸' => '伤官'],
        '辛' => ['甲' => '正财', '乙' => '偏财', '丙' => '正官', '丁' => '七杀', '戊' => '正印', '己' => '偏印', '庚' => '劫财', '辛' => '比肩', '壬' => '伤官', '癸' => '食神'],
        '壬' => ['甲' => '食神', '乙' => '伤官', '丙' => '偏财', '丁' => '正财', '戊' => '七杀', '己' => '正官', '庚' => '偏印', '辛' => '正印', '壬' => '比肩', '癸' => '劫财'],
        '癸' => ['甲' => '伤官', '乙' => '食神', '丙' => '正财', '丁' => '偏财', '戊' => '正官', '己' => '七杀', '庚' => '正印', '辛' => '偏印', '壬' => '劫财', '癸' => '比肩']
    ];
    
    /**
     * 地支藏干
     */
    protected $zhiCangGan = [
        '子' => ['癸'], '丑' => ['己', '癸', '辛'], '寅' => ['甲', '丙', '戊'],
        '卯' => ['乙'], '辰' => ['戊', '乙', '癸'], '巳' => ['丙', '庚', '戊'],
        '午' => ['丁', '己'], '未' => ['己', '丁', '乙'], '申' => ['庚', '壬', '戊'],
        '酉' => ['辛'], '戌' => ['戊', '辛', '丁'], '亥' => ['壬', '甲']
    ];
    
    /**
     * 神煞表（扩展版）
     */
    protected $shenSha = [
        'tianyi_guiren' => [
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
        ],
        'tiande' => [
            '子' => '巳', '丑' => '午', '寅' => '未', '卯' => '申', '辰' => '亥',
            '巳' => '子', '午' => '丑', '未' => '寅', '申' => '卯', '酉' => '辰',
            '戌' => '亥', '亥' => '辰',
        ],
        'yuande' => [
            '子' => '巳', '丑' => '午', '寅' => '未', '卯' => '申', '辰' => '亥',
            '巳' => '子', '午' => '丑', '未' => '寅', '申' => '卯', '酉' => '辰',
            '戌' => '亥', '亥' => '辰',
        ],
        'wenchang' => [
            '甲' => '巳', '乙' => '午', '丙' => '申', '丁' => '酉', '戊' => '申',
            '己' => '酉', '庚' => '亥', '辛' => '子', '壬' => '寅', '癸' => '卯',
        ],
        'yima' => [
            '寅' => '申', '午' => '寅', '戌' => '辰',
            '申' => '寅', '子' => '午', '辰' => '戌',
            '巳' => '亥', '酉' => '卯', '丑' => '未',
            '亥' => '巳', '卯' => '酉', '未' => '丑',
        ],
        'taohua' => [
            '寅' => '卯', '午' => '卯', '戌' => '酉',
            '申' => '酉', '子' => '酉', '辰' => '酉',
            '巳' => '午', '酉' => '午', '丑' => '午',
            '亥' => '子', '卯' => '子', '未' => '子',
        ],
        'huagai' => [
            '寅' => '戌', '午' => '戌', '戌' => '戌',
            '申' => '辰', '子' => '辰', '辰' => '辰',
            '巳' => '丑', '酉' => '丑', '丑' => '丑',
            '亥' => '未', '卯' => '未', '未' => '未',
        ],
        'guchen' => [
            '寅' => '亥', '午' => '亥', '戌' => '亥',
            '申' => '巳', '子' => '巳', '辰' => '巳',
            '巳' => '辰', '酉' => '辰', '丑' => '辰',
            '亥' => '戌', '卯' => '戌', '未' => '戌',
        ],
        'guashu' => [
            '寅' => '寅', '午' => '寅', '戌' => '寅',
            '申' => '申', '子' => '申', '辰' => '申',
            '巳' => '巳', '酉' => '巳', '丑' => '巳',
            '亥' => '亥', '卯' => '亥', '未' => '亥',
        ],
        'wuming' => [
            '寅' => '寅', '午' => '寅', '戌' => '寅',
            '申' => '申', '子' => '申', '辰' => '申',
            '巳' => '巳', '酉' => '巳', '丑' => '巳',
            '亥' => '亥', '卯' => '亥', '未' => '亥',
        ],
        'liuxia' => [
            '申' => '午', '子' => '午', '辰' => '午',
            '寅' => '巳', '午' => '巳', '戌' => '巳',
            '亥' => '辰', '卯' => '辰', '未' => '辰',
            '巳' => '亥', '酉' => '亥', '丑' => '亥',
        ],
        'hongyan' => [
            '甲' => '午', '乙' => '午', '丙' => '寅', '丁' => '寅',
            '戊' => '卯', '己' => '卯', '庚' => '戌', '辛' => '戌',
            '壬' => '酉', '癸' => '酉',
        ],
        'wangshen' => [
            '寅' => '亥', '午' => '亥', '戌' => '亥',
            '申' => '巳', '子' => '巳', '辰' => '巳',
            '巳' => '辰', '酉' => '辰', '丑' => '辰',
            '亥' => '戌', '卯' => '戌', '未' => '戌',
        ],
        'jiesha' => [
            '寅' => '亥', '午' => '亥', '戌' => '亥',
            '申' => '巳', '子' => '巳', '辰' => '巳',
            '巳' => '辰', '酉' => '辰', '丑' => '辰',
            '亥' => '戌', '卯' => '戌', '未' => '戌',
        ],
        'shima' => [
            '寅' => '巳', '午' => '巳', '戌' => '巳',
            '申' => '亥', '子' => '亥', '辰' => '亥',
            '巳' => '申', '酉' => '申', '丑' => '申',
            '亥' => '寅', '卯' => '寅', '未' => '寅',
        ],
        'shidabai' => [
            '甲辰' => true, '甲戌' => true, '乙巳' => true, '乙丑' => true,
            '丙申' => true, '丁亥' => true, '戊辰' => true, '己巳' => true,
            '庚午' => true, '辛巳' => true, '壬寅' => true, '癸未' => true,
        ],
    ];
    
    /**
     * 羊刃地支
     */
    protected $yangren = [
        '甲' => '卯', '乙' => '辰', '丙' => '午', '丁' => '未', 
        '戊' => '午', '己' => '未', '庚' => '酉', '辛' => '戌',
        '壬' => '子', '癸' => '丑'
    ];
    
    /**
     * 分析八字格局（完整版）
     */
    public function analyzePattern(array $bazi, string $gender): array
    {
        $dayMaster = $bazi['day']['gan'] ?? '甲';
        $dayMasterWuxing = $bazi['day']['gan_wuxing'] ?? ($this->ganWuXing[$dayMaster] ?? '木');
        
        // 分析八格
        $eightPatterns = $this->analyzeEightPatterns($bazi);
        
        // 分析特殊格局
        $specialPatterns = $this->analyzeSpecialPatterns($bazi);
        
        // 分析神煞
        $shenShaAnalysis = $this->analyzeShenSha($bazi, $gender);
        
        // 生成命理定语
        $mingliDingyu = $this->generateMingliDingyu($bazi, $eightPatterns, $specialPatterns, $shenShaAnalysis);
        
        // 分析格局层次
        $patternLevel = $this->analyzePatternLevel($bazi, $eightPatterns, $specialPatterns);
        
        return [
            'eight_patterns' => $eightPatterns,
            'special_patterns' => $specialPatterns,
            'shen_sha' => $shenShaAnalysis,
            'mingli_dingyu' => $mingliDingyu,
            'pattern_level' => $patternLevel,
            'pattern_summary' => $this->generatePatternSummary($eightPatterns, $specialPatterns, $patternLevel),
        ];
    }
    
    /**
     * 分析大运格局（新增）
     * 分析大运期间的格局变化
     */
    public function analyzeDayunPattern(array $bazi, array $dayun, int $dayunIndex): array
    {
        $baziWithDayun = $bazi;
        
        // 将大运天干地支加入月柱位置
        $baziWithDayun['month']['gan'] = $dayun['gan'] ?? '';
        $baziWithDayun['month']['zhi'] = $dayun['zhi'] ?? '';
        
        // 重新计算五行统计
        $baziWithDayun['wuxing_stats'] = $this->recalculateWuxingStats($baziWithDayun);
        
        // 重新计算十神
        $baziWithDayun = $this->recalculateShishen($baziWithDayun);
        
        // 重新分析格局
        $pattern = $this->analyzePattern($baziWithDayun, $bazi['gender'] ?? '');
        
        // 计算大运评分
        $dayunScore = $this->calculateDayunScore($bazi, $dayun, $pattern);
        
        return [
            'dayun' => $dayun,
            'dayun_index' => $dayunIndex,
            'pattern' => $pattern,
            'dayun_score' => $dayunScore['score'],
            'dayun_description' => $dayunScore['description'],
            'is_favorable' => $dayunScore['is_favorable'],
            'advice' => $dayunScore['advice'],
        ];
    }
    
    /**
     * 分析流年格局（新增）
     * 分析流年期间的格局变化
     */
    public function analyzeLiunianPattern(array $bazi, array $liunian, int $liunianYear): array
    {
        $baziWithLiunian = $bazi;
        
        // 将流年天干地支加入年柱位置（模拟）
        $originalYear = $baziWithLiunian['year'];
        $baziWithLiunian['year']['gan'] = $liunian['gan'] ?? '';
        $baziWithLiunian['year']['zhi'] = $liunian['zhi'] ?? '';
        
        // 重新计算五行统计
        $baziWithLiunian['wuxing_stats'] = $this->recalculateWuxingStats($baziWithLiunian);
        
        // 重新计算十神
        $baziWithLiunian = $this->recalculateShishen($baziWithLiunian);
        
        // 重新分析格局
        $pattern = $this->analyzePattern($baziWithLiunian, $bazi['gender'] ?? '');
        
        // 计算流年评分
        $liunianScore = $this->calculateLiunianScore($bazi, $originalYear, $liunian, $pattern);
        
        return [
            'liunian' => $liunian,
            'liunian_year' => $liunianYear,
            'pattern' => $pattern,
            'liunian_score' => $liunianScore['score'],
            'liunian_description' => $liunianScore['description'],
            'is_favorable' => $liunianScore['is_favorable'],
            'advice' => $liunianScore['advice'],
        ];
    }
    
    /**
     * 重新计算五行统计（辅助方法）
     */
    protected function recalculateWuxingStats(array $bazi): array
    {
        $wuxingCount = ['金' => 0, '木' => 0, '水' => 0, '火' => 0, '土' => 0];
        
        // 天干五行
        $pillars = ['year', 'month', 'day', 'hour'];
        foreach ($pillars as $pillar) {
            if (isset($bazi[$pillar]['gan'])) {
                $ganWuxing = $this->ganWuXing[$bazi[$pillar]['gan']] ?? '';
                if ($ganWuxing !== '') {
                    $wuxingCount[$ganWuxing] += 1;
                }
            }
            if (isset($bazi[$pillar]['zhi'])) {
                $zhiWuxing = $this->zhiWuXing[$bazi[$pillar]['zhi']] ?? '';
                if ($zhiWuxing !== '') {
                    $wuxingCount[$zhiWuxing] += 1.2;
                }
            }
        }
        
        // 归一化
        $total = array_sum($wuxingCount);
        if ($total > 0) {
            foreach ($wuxingCount as $key => $value) {
                $wuxingCount[$key] = round($value / $total * 8, 1);
            }
        }
        
        return $wuxingCount;
    }
    
    /**
     * 重新计算十神（辅助方法）
     */
    protected function recalculateShishen(array $bazi): array
    {
        $dayMaster = $bazi['day']['gan'] ?? '甲';
        $pillars = ['year', 'month', 'day', 'hour'];
        
        foreach ($pillars as $pillar) {
            if (isset($bazi[$pillar]['gan'])) {
                $gan = $bazi[$pillar]['gan'];
                $bazi[$pillar]['shishen'] = $this->shiShenTable[$dayMaster][$gan] ?? '';
            }
        }
        
        return $bazi;
    }
    
    /**
     * 计算大运评分（新增）
     */
    protected function calculateDayunScore(array $bazi, array $dayun, array $pattern): array
    {
        $yongshen = $bazi['yongshen'] ?? [];
        $yongshenShen = $yongshen['shen'] ?? '';
        
        $score = 0;
        $description = '';
        
        // 检查大运五行是否为喜用神
        $dayunGanWuxing = $this->ganWuXing[$dayun['gan']] ?? '';
        $dayunZhiWuxing = $this->zhiWuXing[$dayun['zhi']] ?? '';
        
        if ($yongshenShen !== '') {
            if ($dayunGanWuxing === $yongshenShen) {
                $score += 30;
                $description .= "大运天干为喜用神（{$yongshenShen}），运势较好。";
            }
            if ($dayunZhiWuxing === $yongshenShen) {
                $score += 25;
                $description .= "大运地支为喜用神（{$yongshenShen}），运势不错。";
            }
        }
        
        // 检查格局层次
        $patternLevel = $pattern['pattern_level']['level'] ?? '';
        $patternScore = $pattern['pattern_level']['score'] ?? 0;
        
        if ($patternLevel === '上上') {
            $score += 20;
            $description .= '格局层次很高，事业有成。';
        } elseif ($patternLevel === '上') {
            $score += 15;
            $description .= '格局层次较高，事业顺遂。';
        } elseif ($patternLevel === '中上') {
            $score += 10;
            $description .= '格局层次中上，事业平稳。';
        } elseif ($patternLevel === '中') {
            $score += 5;
            $description .= '格局层次中等，需要努力。';
        } else {
            $description .= '格局层次较低，需要谨慎。';
        }
        
        // 检查神煞
        $goodShenSha = array_filter($pattern['shen_sha'] ?? [], fn($item) => ($item['quality'] ?? '') === '吉');
        if (count($goodShenSha) > 0) {
            $score += count($goodShenSha) * 5;
            $description .= ' 有贵人相助。';
        }
        
        // 判断是否为吉运
        $isFavorable = $score >= 50;
        
        // 生成建议
        $advice = $isFavorable 
            ? '大运期间运势较好，可以积极进取，把握机会。但要保持谦虚，避免骄傲自满。'
            : '大运期间运势一般，需要谨慎行事，稳健发展。建议修身养性，积累实力。';
        
        return [
            'score' => $score,
            'description' => $description,
            'is_favorable' => $isFavorable,
            'advice' => $advice,
        ];
    }
    
    /**
     * 计算流年评分（新增）
     */
    protected function calculateLiunianScore(array $bazi, array $originalYear, array $liunian, array $pattern): array
    {
        $yongshen = $bazi['yongshen'] ?? [];
        $yongshenShen = $yongshen['shen'] ?? '';
        
        $score = 0;
        $description = '';
        
        // 检查流年五行是否为喜用神
        $liunianGanWuxing = $this->ganWuXing[$liunian['gan']] ?? '';
        $liunianZhiWuxing = $this->zhiWuXing[$liunian['zhi']] ?? '';
        
        if ($yongshenShen !== '') {
            if ($liunianGanWuxing === $yongshenShen) {
                $score += 30;
                $description .= "流年天干为喜用神（{$yongshenShen}），运势较好。";
            }
            if ($liunianZhiWuxing === $yongshenShen) {
                $score += 25;
                $description .= "流年地支为喜用神（{$yongshenShen}），运势不错。";
            }
        }
        
        // 检查与原局的关系
        $dayMaster = $bazi['day']['gan'] ?? '';
        $dayMasterWuxing = $this->ganWuXing[$dayMaster] ?? '';
        
        // 检查是否有冲克
        $clashRelations = [
            '子' => '午', '午' => '子',
            '丑' => '未', '未' => '丑',
            '寅' => '申', '申' => '寅',
            '卯' => '酉', '酉' => '卯',
            '辰' => '戌', '戌' => '辰',
            '巳' => '亥', '亥' => '巳',
        ];
        
        $liunianZhi = $liunian['zhi'] ?? '';
        $dayZhi = $bazi['day']['zhi'] ?? '';
        
        if (isset($clashRelations[$liunianZhi]) && $clashRelations[$liunianZhi] === $dayZhi) {
            $score -= 20;
            $description .= ' 流年地支与日支相冲，感情生活可能有波动。';
        }
        
        // 检查格局层次
        $patternLevel = $pattern['pattern_level']['level'] ?? '';
        if ($patternLevel === '上上' || $patternLevel === '上') {
            $score += 10;
        }
        
        // 检查神煞
        $goodShenSha = array_filter($pattern['shen_sha'] ?? [], fn($item) => ($item['quality'] ?? '') === '吉');
        if (count($goodShenSha) > 0) {
            $score += count($goodShenSha) * 5;
            $description .= ' 有贵人相助。';
        }
        
        // 判断是否为吉年
        $isFavorable = $score >= 50;
        
        // 生成建议
        $advice = $isFavorable 
            ? '流年运势较好，可以积极进取，把握机会。但要保持谦虚，避免骄傲自满。'
            : '流年运势一般，需要谨慎行事，稳健发展。建议修身养性，积累实力。';
        
        return [
            'score' => $score,
            'description' => $description,
            'is_favorable' => $isFavorable,
            'advice' => $advice,
        ];
    }
    
    /**
     * 分析八格（正格）
     */
    protected function analyzeEightPatterns(array $bazi): array
    {
        $patterns = [];
        $dayMaster = $bazi['day']['gan'];
        $dayMasterWuxing = $bazi['day']['gan_wuxing'];
        $strength = $bazi['strength'] ?? [];
        $isStrong = in_array($strength['status'] ?? '', ['身旺', '中和偏旺'], true);
        
        // 获取所有十神
        $allShishen = [
            'year' => $bazi['year']['shishen'] ?? '',
            'month' => $bazi['month']['shishen'] ?? '',
            'day' => $bazi['day']['shishen'] ?? '比肩',
            'hour' => $bazi['hour']['shishen'] ?? '',
        ];
        
        // 获取所有地支
        $allZhi = [
            'year' => $bazi['year']['zhi'],
            'month' => $bazi['month']['zhi'],
            'day' => $bazi['day']['zhi'],
            'hour' => $bazi['hour']['zhi'],
        ];
        
        $shishenCount = array_count_values($allShishen);
        
        // 正官格（月令为正官且无冲克）
        if ($allShishen['month'] === '正官') {
            $hasConflict = false;
            foreach ($allShishen as $pos => $shishen) {
                if ($shishen === '伤官') {
                    $hasConflict = true;
                    break;
                }
            }
            
            if (!$hasConflict) {
                $patterns[] = [
                    'name' => '正官格',
                    'description' => '月令正官为提纲，性格正直守信，重视名誉，事业心强。适合走仕途或管理岗位，有贵人相助。',
                    'quality' => $isStrong ? '上格' : '中格',
                    'favorable' => ['财星', '印星'],
                    'unfavorable' => ['伤官'],
                    'level' => 5,
                ];
            }
        }
        
        // 七杀格（月令为七杀且有制化）
        if ($allShishen['month'] === '七杀') {
            // 检查是否有食神或印星
            $hasFood = in_array('食神', $allShishen, true);
            $hasYin = in_array('正印', $allShishen, true) || in_array('偏印', $allShishen, true);
            
            if ($hasFood || $hasYin) {
                if ($hasFood && $hasYin) {
                    // 食神制杀配印绶
                    $patterns[] = [
                        'name' => '食神制杀格（配印）',
                        'description' => '七杀有制有化，刚柔并济。有魄力，能担当，有领导才能，抗压能力强，且有智慧，善于谋略。是大将之才。',
                        'quality' => '上上格',
                        'favorable' => ['食神', '印星', '财星'],
                        'unfavorable' => ['比劫'],
                        'level' => 9,
                    ];
                } elseif ($hasFood) {
                    // 食神制杀
                    $patterns[] = [
                        'name' => '食神制杀格',
                        'description' => '七杀得食神制伏，威猛有制。有魄力，敢担当，有领导才能，抗压能力强，适合竞争性行业。',
                        'quality' => '上格',
                        'favorable' => ['食神', '财星'],
                        'unfavorable' => ['比劫'],
                        'level' => 8,
                    ];
                } else {
                    // 杀印相生
                    $patterns[] = [
                        'name' => '杀印相生格',
                        'description' => '七杀得印星化解，威猛转为文治。有魄力且智慧，善于谋略，能够化压力为动力，适合担任要职。',
                        'quality' => '上格',
                        'favorable' => ['印星', '财星'],
                        'unfavorable' => ['比劫', '食伤'],
                        'level' => 8,
                    ];
                }
            } else {
                $patterns[] = [
                    'name' => '七杀格（无制化）',
                    'description' => '月令七杀但无制化，性格急躁，压力大，容易遇小人。需要借助外力，修身养性。',
                    'quality' => '偏格',
                    'favorable' => ['食神', '印星'],
                    'unfavorable' => ['比劫', '财星'],
                    'level' => 3,
                ];
            }
        }
        
        // 正财格（月令为正财）
        if ($allShishen['month'] === '正财') {
            if ($isStrong) {
                $patterns[] = [
                    'name' => '正财格',
                    'description' => '月令正财为提纲，勤俭持家，务实稳重，重视承诺，脚踏实地。有商业头脑，财运稳定，适合经商。',
                    'quality' => '上格',
                    'favorable' => ['官杀', '食伤'],
                    'unfavorable' => ['比劫'],
                    'level' => 6,
                ];
            } else {
                $patterns[] = [
                    'name' => '正财格（身弱）',
                    'description' => '月令正财但身弱，财多身弱，容易财来财去。需要帮扶，稳健理财，不宜大额投资。',
                    'quality' => '中格',
                    'favorable' => ['比劫', '印星'],
                    'unfavorable' => ['食伤', '财星'],
                    'level' => 4,
                ];
            }
        }
        
        // 偏财格（月令为偏财）
        if ($allShishen['month'] === '偏财') {
            if ($isStrong) {
                $patterns[] = [
                    'name' => '偏财格',
                    'description' => '月令偏财为提纲，善于理财，人缘好，慷慨大方，有商业头脑，有偏财运。适合投资理财。',
                    'quality' => '上格',
                    'favorable' => ['食伤', '比劫'],
                    'unfavorable' => ['印星'],
                    'level' => 6,
                ];
            }
        }
        
        // 正印格（月令为正印）
        if ($allShishen['month'] === '正印') {
            $patterns[] = [
                'name' => '正印格',
                'description' => '月令正印为提纲，心地善良，有学识，易得贵人帮助，有包容心。性格温和，重情重义。',
                'quality' => '上格',
                'favorable' => ['官杀', '比劫'],
                'unfavorable' => ['财星'],
                'level' => 7,
            ];
        }
        
        // 食神格（月令为食神）
        if ($allShishen['month'] === '食神') {
            $patterns[] = [
                'name' => '食神格',
                'description' => '月令食神为提纲，聪明有才华，善于表达，有口福，心态好。有艺术天赋，乐观开朗。',
                'quality' => '上格',
                'favorable' => ['财星', '比劫'],
                'unfavorable' => ['印星'],
                'level' => 6,
            ];
        }
        
        // 检查组合格局
        // 官印相生
        $hasGuan = in_array('正官', $allShishen, true);
        $hasYin = in_array('正印', $allShishen, true);
        if ($hasGuan && $hasYin) {
            $patterns[] = [
                'name' => '官印相生格',
                'description' => '官星与印星相生，有事业心且有学识，能够得到贵人相助，事业有成，名利双收。',
                'quality' => '上上格',
                'favorable' => ['财星', '比劫'],
                'unfavorable' => ['伤官'],
                'level' => 9,
            ];
        }
        
        // 伤官驾杀
        $hasShangguan = in_array('伤官', $allShishen, true);
        $hasQisha = in_array('七杀', $allShishen, true);
        if ($hasShangguan && $hasQisha) {
            $patterns[] = [
                'name' => '伤官驾杀格',
                'description' => '伤官驾御七杀，才华与魄力并存，既有创意又有决断力。能够化压力为动力，适合创业或担任要职。',
                'quality' => '上格',
                'favorable' => ['财星', '印星'],
                'unfavorable' => ['比劫'],
                'level' => 7,
            ];
        }
        
        // 食伤生财
        $hasShiShang = in_array('食神', $allShishen, true) || in_array('伤官', $allShishen, true);
        $hasCai = in_array('正财', $allShishen, true) || in_array('偏财', $allShishen, true);
        if ($hasShiShang && $hasCai && $isStrong) {
            $patterns[] = [
                'name' => '食伤生财格',
                'description' => '食伤生财，才华能够转化为财富。有创造力和商业头脑，适合技术、艺术或创业。',
                'quality' => '上格',
                'favorable' => ['财星', '比劫'],
                'unfavorable' => ['印星'],
                'level' => 7,
            ];
        }
        
        // 比劫帮身
        $bijieCount = $shishenCount['比肩'] ?? 0;
        $jiecaiCount = $shishenCount['劫财'] ?? 0;
        $bijieTotal = $bijieCount + $jiecaiCount;
        if ($bijieTotal >= 2 && !$isStrong) {
            $patterns[] = [
                'name' => '比劫帮身格',
                'description' => '比劫多而身弱，能够得到朋友和兄弟姐妹的帮助。人际关系好，团队合作能力强。',
                'quality' => '中格',
                'favorable' => ['印星', '比劫'],
                'unfavorable' => ['财星', '官杀'],
                'level' => 5,
            ];
        }
        
        // 羊刃格（新增）
        $yangrenZhi = $this->yangren[$dayMaster] ?? '';
        if ($yangrenZhi !== '') {
            foreach ($allZhi as $pos => $zhi) {
                if ($zhi === $yangrenZhi) {
                    // 检查是否有七杀
                    $hasQisha = in_array('七杀', $allShishen, true);
                    if ($hasQisha) {
                        // 羊刃驾杀
                        $patterns[] = [
                            'name' => '羊刃驾杀格',
                            'description' => '羊刃驾御七杀，有威猛之气，有魄力有担当。适合武职或竞争性行业，但要控制脾气。',
                            'quality' => '上格',
                            'favorable' => ['印星', '食神'],
                            'unfavorable' => ['财星'],
                            'level' => 8,
                        ];
                    } else {
                        // 羊刃格（无杀）
                        $patterns[] = [
                            'name' => '羊刃格',
                            'description' => '地支见羊刃，性格刚烈，有魄力，但也容易冲动。需要修身养性，控制脾气。',
                            'quality' => '中格',
                            'favorable' => ['印星', '七杀'],
                            'unfavorable' => ['财星', '食伤'],
                            'level' => 5,
                        ];
                    }
                    break;
                }
            }
        }
        
        // 建禄格（新增）
        $jianluMap = [
            '甲' => '寅', '乙' => '卯', '丙' => '巳', '丁' => '午',
            '戊' => '巳', '己' => '午', '庚' => '申', '辛' => '酉',
            '壬' => '亥', '癸' => '子',
        ];
        $jianluZhi = $jianluMap[$dayMaster] ?? '';
        if ($jianluZhi !== '' && $allZhi['year'] === $jianluZhi) {
            // 年支为建禄
            if ($isStrong) {
                $patterns[] = [
                    'name' => '建禄格（年）',
                    'description' => '年支见建禄，祖上有贵气，能够继承家业或得到长辈帮助。有领导才能，适合从政或管理。',
                    'quality' => '上格',
                    'favorable' => ['官杀', '财星'],
                    'unfavorable' => ['比劫'],
                    'level' => 7,
                ];
            } else {
                $patterns[] = [
                    'name' => '建禄格（年，身弱）',
                    'description' => '年支见建禄但身弱，有祖荫但需要努力。建议借助外力，培养领导能力。',
                    'quality' => '中格',
                    'favorable' => ['印星', '比劫'],
                    'unfavorable' => ['财星', '官杀'],
                    'level' => 4,
                ];
            }
        }
        
        // 禄神格（新增）
        $lushenMap = [
            '甲' => '寅', '乙' => '卯', '丙' => '巳', '丁' => '午',
            '戊' => '巳', '己' => '午', '庚' => '申', '辛' => '酉',
            '壬' => '亥', '癸' => '子',
        ];
        $lushenZhi = $lushenMap[$dayMaster] ?? '';
        $lushenCount = 0;
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $lushenZhi) {
                $lushenCount++;
            }
        }
        if ($lushenCount >= 2) {
            $patterns[] = [
                'name' => '双禄格',
                'description' => '地支多处见禄神，福气深厚，事业顺利。有贵人相助，生活富足。',
                'quality' => '上格',
                'favorable' => ['财星', '官杀'],
                'unfavorable' => ['比劫'],
                'level' => 8,
            ];
        }
        
        // 魁罡格（新增）
        $kuiGangZhi = ['辰', '戌'];
        $kuiGangCount = 0;
        foreach ($allZhi as $pos => $zhi) {
            if (in_array($zhi, $kuiGangZhi, true)) {
                $kuiGangCount++;
            }
        }
        if ($kuiGangCount >= 2) {
            $patterns[] = [
                'name' => '魁罡格',
                'description' => '地支多处见魁罡，聪明刚毅，有决断力。性格强势，有领导才能，适合创业或担任要职。',
                'quality' => '上格',
                'favorable' => ['财星', '官杀'],
                'unfavorable' => ['比劫'],
                'level' => 7,
            ];
        }
        
        // 金神格（新增）
        $jinShenGanZhi = [
            '甲' => '巳', '乙' => '巳', '己' => '巳', '庚' => '丑', '辛' => '丑', '壬' => '丑',
            '癸' => '丑', '丁' => '丑',
        ];
        $jinShenZhi = $jinShenGanZhi[$dayMaster] ?? '';
        if ($jinShenZhi !== '') {
            foreach ($allZhi as $pos => $zhi) {
                if ($zhi === $jinShenZhi) {
                    $patterns[] = [
                        'name' => '金神格',
                        'description' => '地支见金神，性格刚烈果断，有威严之气。适合武职或竞争性行业，但要注意控制脾气。',
                        'quality' => '中格',
                        'favorable' => ['火', '土'],
                        'unfavorable' => ['金', '木'],
                        'level' => 6,
                    ];
                    break;
                }
            }
        }
        
        return $patterns;
    }
    
    /**
     * 分析特殊格局
     */
    protected function analyzeSpecialPatterns(array $bazi): array
    {
        $patterns = [];
        $wuxingStats = $bazi['wuxing_stats'] ?? [];
        $strength = $bazi['strength'] ?? [];
        
        // 检查是否三会或三合成局
        $branchValues = [
            'year' => $bazi['year']['zhi'],
            'month' => $bazi['month']['zhi'],
            'day' => $bazi['day']['zhi'],
            'hour' => $bazi['hour']['zhi'],
        ];
        
        $branchArray = array_values($branchValues);
        
        // 三合局
        $sanhe = [
            '申子辰' => ['element' => '水', 'branches' => ['申', '子', '辰']],
            '亥卯未' => ['element' => '木', 'branches' => ['亥', '卯', '未']],
            '寅午戌' => ['element' => '火', 'branches' => ['寅', '午', '戌']],
            '巳酉丑' => ['element' => '金', 'branches' => ['巳', '酉', '丑']],
        ];
        
        foreach ($sanhe as $patternName => $info) {
            if (count(array_intersect($info['branches'], $branchArray)) === 3) {
                $patterns[] = [
                    'name' => $patternName . '三合局',
                    'description' => "地支{$info['element']}三合成局，气势强旺，{$info['element']}之力倍增。格局层次较高，有贵人相助，事业有成。",
                    'quality' => '上格',
                    'element' => $info['element'],
                    'level' => 8,
                ];
            }
        }
        
        // 三会方
        $sanhui = [
            '寅卯辰' => ['element' => '木', 'branches' => ['寅', '卯', '辰']],
            '巳午未' => ['element' => '火', 'branches' => ['巳', '午', '未']],
            '申酉戌' => ['element' => '金', 'branches' => ['申', '酉', '戌']],
            '亥子丑' => ['element' => '水', 'branches' => ['亥', '子', '丑']],
        ];
        
        foreach ($sanhui as $patternName => $info) {
            if (count(array_intersect($info['branches'], $branchArray)) === 3) {
                $patterns[] = [
                    'name' => $patternName . '三会方',
                    'description' => "地支{$info['element']}三会成方，气势磅礴，{$info['element']}之力极强。格局层次很高，有贵人相助，事业大成。",
                    'quality' => '上上格',
                    'element' => $info['element'],
                    'level' => 9,
                ];
            }
        }
        
        // 从格判断
        $dayMasterWuxing = $bazi['day']['gan_wuxing'];
        $dayMasterStrength = $wuxingStats[$dayMasterWuxing] ?? 0;
        $totalStrength = array_sum($wuxingStats);
        
        // 假从格（日主极弱，其他五行极强）
        if ($dayMasterStrength < 0.5 && $totalStrength > 5) {
            // 找出最强的五行
            $maxWuxing = '';
            $maxStrength = 0;
            foreach ($wuxingStats as $wx => $value) {
                if ($wx !== $dayMasterWuxing && $value > $maxStrength) {
                    $maxStrength = $value;
                    $maxWuxing = $wx;
                }
            }
            
            if ($maxWuxing !== '') {
                $patterns[] = [
                    'name' => '假从格',
                    'description' => "日主极弱，{$maxWuxing}极强，不得不从。性格随和，善于适应，能够借助外力成就事业。需要顺应环境，顺势而为。",
                    'quality' => '上格',
                    'favorable' => [$maxWuxing],
                    'unfavorable' => [$dayMasterWuxing],
                    'level' => 7,
                ];
            }
        }
        
        // 真从格（日主无根，其他五行极强）
        $hasRoot = false;
        foreach ($branchValues as $pos => $zhi) {
            $canggan = $this->zhiCangGan[$zhi] ?? [];
            foreach ($canggan as $gan) {
                if ($this->ganWuXing[$gan] === $dayMasterWuxing) {
                    $hasRoot = true;
                    break;
                }
            }
            if ($hasRoot) break;
        }
        
        if (!$hasRoot && $dayMasterStrength < 0.3 && $totalStrength > 6) {
            // 找出最强的五行
            $maxWuxing = '';
            $maxStrength = 0;
            foreach ($wuxingStats as $wx => $value) {
                if ($wx !== $dayMasterWuxing && $value > $maxStrength) {
                    $maxStrength = $value;
                    $maxWuxing = $wx;
                }
            }
            
            if ($maxWuxing !== '') {
                $patterns[] = [
                    'name' => '真从格',
                    'description' => "日主无根，{$maxWuxing}极强，真从无疑。性格极端，要么极好要么极差，需要顺势而为，能够成就大事业。",
                    'quality' => '上上格',
                    'favorable' => [$maxWuxing],
                    'unfavorable' => [$dayMasterWuxing],
                    'level' => 9,
                ];
            }
        }
        
        return $patterns;
    }
    
    /**
     * 分析神煞
     */
    protected function analyzeShenSha(array $bazi, string $gender): array
    {
        $dayMaster = $bazi['day']['gan'];
        $allZhi = [
            'year' => $bazi['year']['zhi'],
            'month' => $bazi['month']['zhi'],
            'day' => $bazi['day']['zhi'],
            'hour' => $bazi['hour']['zhi'],
        ];
        
        $shenShaList = [];
        
        // 天乙贵人
        $tianyiZhi = $this->shenSha['tianyi_guiren'][$dayMaster] ?? [];
        foreach ($allZhi as $pos => $zhi) {
            if (in_array($zhi, $tianyiZhi, true)) {
                $shenShaList[] = [
                    'name' => '天乙贵人',
                    'position' => $pos,
                    'description' => "{$pos}柱遇天乙贵人，一生有贵人相助，逢凶化吉，遇难呈祥。事业上易得提携，有贵人扶持。",
                    'quality' => '吉',
                    'level' => 9,
                ];
            }
        }
        
        // 文昌
        $wenchangZhi = $this->shenSha['wenchang'][$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $wenchangZhi) {
                $shenShaList[] = [
                    'name' => '文昌',
                    'position' => $pos,
                    'description' => "{$pos}柱遇文昌，聪明有才华，善于学习，有考试运。适合从事教育、文化、研究工作。",
                    'quality' => '吉',
                    'level' => 8,
                ];
            }
        }
        
        // 驿马
        $yimaZhi = $this->shenSha['yima'][$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $yimaZhi) {
                $shenShaList[] = [
                    'name' => '驿马',
                    'position' => $pos,
                    'description' => "{$pos}柱遇驿马，一生奔波劳碌，但也因此能够见识广博，经验丰富。适合从事流动性强的工作，如贸易、旅游、交通。",
                    'quality' => '中',
                    'level' => 6,
                ];
            }
        }
        
        // 桃花
        $taohuaZhi = $this->shenSha['taohua'][$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $taohuaZhi) {
                $shenShaList[] = [
                    'name' => '桃花',
                    'position' => $pos,
                    'description' => "{$pos}柱遇桃花，异性缘好，魅力十足。感情运势佳，但也容易陷入感情纠葛。需要理智对待感情。",
                    'quality' => '中',
                    'level' => 6,
                ];
            }
        }
        
        // 羊刃
        $yangrenZhi = $this->yangren[$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $yangrenZhi) {
                $shenShaList[] = [
                    'name' => '羊刃',
                    'position' => $pos,
                    'description' => "{$pos}柱遇羊刃，性格刚烈，有魄力，但也容易冲动。需要控制脾气，遇事冷静思考。有武职之运。",
                    'quality' => '凶',
                    'level' => 5,
                ];
            }
        }
        
        // 华盖（新增）
        $huagaiZhi = $this->shenSha['huagai'][$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $huagaiZhi) {
                $shenShaList[] = [
                    'name' => '华盖',
                    'position' => $pos,
                    'description' => "{$pos}柱遇华盖，聪明有艺术天赋，有文学才能。但性格孤高，容易孤独。适合从事艺术、文化工作。",
                    'quality' => '中',
                    'level' => 6,
                ];
            }
        }
        
        // 孤辰（新增）
        $guchenZhi = $this->shenSha['guchen'][$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $guchenZhi) {
                $shenShaList[] = [
                    'name' => '孤辰',
                    'position' => $pos,
                    'description' => "{$pos}柱遇孤辰，性格孤僻，不善于社交。需要主动结交朋友，培养社交能力。",
                    'quality' => '凶',
                    'level' => 4,
                ];
            }
        }
        
        // 寡宿（新增）
        $guasuZhi = $this->shenSha['guashu'][$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $guasuZhi) {
                $shenShaList[] = [
                    'name' => '寡宿',
                    'position' => $pos,
                    'description' => "{$pos}柱遇寡宿，感情生活平淡，容易孤独。需要主动经营感情，保持乐观心态。",
                    'quality' => '凶',
                    'level' => 4,
                ];
            }
        }
        
        // 亡神（新增）
        $wangshenZhi = $this->shenSha['wangshen'][$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $wangshenZhi) {
                $shenShaList[] = [
                    'name' => '亡神',
                    'position' => $pos,
                    'description' => "{$pos}柱遇亡神，需要特别注意安全，做事要谨慎。建议修身养性，避免冲动行事。",
                    'quality' => '凶',
                    'level' => 3,
                ];
            }
        }
        
        // 劫煞（新增）
        $jieshaZhi = $this->shenSha['jiesha'][$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $jieshaZhi) {
                $shenShaList[] = [
                    'name' => '劫煞',
                    'position' => $pos,
                    'description' => "{$pos}柱遇劫煞，容易遭遇损失，需要谨慎理财。建议稳健投资，避免高风险操作。",
                    'quality' => '凶',
                    'level' => 4,
                ];
            }
        }
        
        // 十恶大败（新增）
        $yearGanZhi = ($bazi['year']['gan'] ?? '') . ($bazi['year']['zhi'] ?? '');
        $monthGanZhi = ($bazi['month']['gan'] ?? '') . ($bazi['month']['zhi'] ?? '');
        $dayGanZhi = ($bazi['day']['gan'] ?? '') . ($bazi['day']['zhi'] ?? '');
        $hourGanZhi = ($bazi['hour']['gan'] ?? '') . ($bazi['hour']['zhi'] ?? '');
        
        $allGanZhi = [
            'year' => $yearGanZhi,
            'month' => $monthGanZhi,
            'day' => $dayGanZhi,
            'hour' => $hourGanZhi,
        ];
        
        $shidabaiList = $this->shenSha['shidabai'] ?? [];
        foreach ($allGanZhi as $pos => $ganZhi) {
            if (isset($shidabaiList[$ganZhi]) && $shidabaiList[$ganZhi]) {
                $shenShaList[] = [
                    'name' => '十恶大败',
                    'position' => $pos,
                    'description' => "{$pos}柱十恶大败，需要特别谨慎，避免决策失误。建议多听取他人意见，三思而后行。",
                    'quality' => '凶',
                    'level' => 2,
                ];
            }
        }
        
        // 流霞（新增）
        $liuxiaZhi = $this->shenSha['liuxia'][$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $liuxiaZhi) {
                $shenShaList[] = [
                    'name' => '流霞',
                    'position' => $pos,
                    'description' => "{$pos}柱遇流霞，需要特别注意交通安全，出行要谨慎。建议避免夜间行车，保持车辆良好状态。",
                    'quality' => '凶',
                    'level' => 3,
                ];
            }
        }
        
        // 红艳（新增）
        $hongyanZhi = $this->shenSha['hongyan'][$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $hongyanZhi) {
                $shenShaList[] = [
                    'name' => '红艳',
                    'position' => $pos,
                    'description' => "{$pos}柱遇红艳，异性缘极佳，魅力四射。感情运势佳，但也容易招惹桃花劫。需要理智对待感情。",
                    'quality' => '中',
                    'level' => 6,
                ];
            }
        }
        
        // 天德（新增）
        $tiandeZhi = $this->shenSha['tiande'][$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $tiandeZhi) {
                $shenShaList[] = [
                    'name' => '天德',
                    'position' => $pos,
                    'description' => "{$pos}柱遇天德，有上天庇佑，能够逢凶化吉。为人善良，有慈悲心，容易得到贵人相助。",
                    'quality' => '吉',
                    'level' => 8,
                ];
            }
        }
        
        // 月德（新增）
        $yuandeZhi = $this->shenSha['yuande'][$dayMaster] ?? '';
        foreach ($allZhi as $pos => $zhi) {
            if ($zhi === $yuandeZhi) {
                $shenShaList[] = [
                    'name' => '月德',
                    'position' => $pos,
                    'description' => "{$pos}柱遇月德，有月神庇佑，一生平安顺遂。为人温和，有涵养，生活幸福。",
                    'quality' => '吉',
                    'level' => 8,
                ];
            }
        }
        
        return $shenShaList;
    }
    
    /**
     * 生成命理定语
     */
    protected function generateMingliDingyu(array $bazi, array $eightPatterns, array $specialPatterns, array $shenSha): array
    {
        $dingyu = [];
        $strength = $bazi['strength'] ?? [];
        $status = $strength['status'] ?? '';
        
        // 日主强弱定语
        if ($status === '身旺') {
            $dingyu[] = '日主身旺，精力充沛，有魄力，做事果断。但要注意控制脾气，避免过于强势。';
        } elseif ($status === '中和偏旺') {
            $dingyu[] = '日主偏旺，精力较充沛，做事有主见。需要保持平衡，避免过度消耗。';
        } elseif ($status === '中和偏弱') {
            $dingyu[] = '日主偏弱，需要扶助，建议多借助外力，培养自信心。做事要量力而行。';
        } elseif ($status === '身弱') {
            $dingyu[] = '日主身弱，需要大力扶助，建议多结交贵人，借助外力。要注意保重身体，避免过度劳累。';
        }
        
        // 格局定语
        $mainPattern = $this->getMainPattern($eightPatterns, $specialPatterns);
        if ($mainPattern) {
            $dingyu[] = $mainPattern['description'];
        }
        
        // 神煞定语
        $goodShenSha = array_filter($shenSha, fn($item) => $item['quality'] === '吉' || $item['quality'] === '中');
        if (!empty($goodShenSha)) {
            $topShenSha = array_values($goodShenSha)[0];
            $dingyu[] = $topShenSha['description'];
        }
        
        // 五行定语
        $wuxingStats = $bazi['wuxing_stats'] ?? [];
        $maxWuxing = '';
        $maxValue = 0;
        foreach ($wuxingStats as $wx => $value) {
            if ($value > $maxValue) {
                $maxValue = $value;
                $maxWuxing = $wx;
            }
        }
        
        if ($maxWuxing !== '') {
            $wuxingDingyu = [
                '金' => '命局金旺，性格刚毅果断，有正义感。但要注意控制脾气，避免过于刚硬。',
                '木' => '命局木旺，性格仁慈正直，有上进心。但要注意保持耐心，避免过于急躁。',
                '水' => '命局水旺，性格聪明灵活，有智慧。但要注意保持专注，避免过于善变。',
                '火' => '命局火旺，性格热情开朗，有感染力。但要注意控制情绪，避免过于冲动。',
                '土' => '命局土旺，性格稳重可靠，有责任感。但要注意保持灵活性，避免过于固执。',
            ];
            if (isset($wuxingDingyu[$maxWuxing])) {
                $dingyu[] = $wuxingDingyu[$maxWuxing];
            }
        }
        
        return $dingyu;
    }
    
    /**
     * 获取主要格局
     */
    protected function getMainPattern(array $eightPatterns, array $specialPatterns): ?array
    {
        $allPatterns = array_merge($eightPatterns, $specialPatterns);
        if (empty($allPatterns)) {
            return null;
        }
        
        // 按等级排序，返回最高等级的格局
        usort($allPatterns, fn($a, $b) => ($b['level'] ?? 0) <=> ($a['level'] ?? 0));
        return $allPatterns[0];
    }
    
    /**
     * 分析格局层次
     */
    protected function analyzePatternLevel(array $bazi, array $eightPatterns, array $specialPatterns): array
    {
        $strength = $bazi['strength'] ?? [];
        $score = $strength['score'] ?? 0;
        $status = $strength['status'] ?? '';
        
        $allPatterns = array_merge($eightPatterns, $specialPatterns);
        
        // 计算格局总分
        $patternScore = 0;
        foreach ($allPatterns as $pattern) {
            $patternScore += $pattern['level'] ?? 0;
        }
        
        // 综合评分
        $totalScore = $score + ($patternScore * 2);
        
        // 判断层次
        if ($totalScore >= 150) {
            $level = '上上';
            $description = '格局层次很高，富贵可期。有贵人相助，事业有成，名利双收。';
        } elseif ($totalScore >= 120) {
            $level = '上';
            $description = '格局层次较高，事业有成。有贵人相助，能够成就一番事业。';
        } elseif ($totalScore >= 90) {
            $level = '中上';
            $description = '格局层次中上，事业顺遂。努力奋斗，能够有所成就。';
        } elseif ($totalScore >= 60) {
            $level = '中';
            $description = '格局层次中等，事业平稳。需要努力奋斗，才能有所成就。';
        } else {
            $level = '下';
            $description = '格局层次较低，需要付出更多努力。建议修身养性，循序渐进。';
        }
        
        return [
            'level' => $level,
            'score' => $totalScore,
            'description' => $description,
        ];
    }
    
    /**
     * 生成格局总结
     */
    protected function generatePatternSummary(array $eightPatterns, array $specialPatterns, array $patternLevel): string
    {
        $summary = '';
        
        if (!empty($eightPatterns)) {
            $mainPattern = $eightPatterns[0];
            $summary .= "你的八字格局为【{$mainPattern['name']}】，";
        }
        
        if (!empty($specialPatterns)) {
            $specialPattern = $specialPatterns[0];
            $summary .= "兼有【{$specialPattern['name']}】。";
        }
        
        $summary .= $patternLevel['description'];
        
        return $summary;
    }
}
