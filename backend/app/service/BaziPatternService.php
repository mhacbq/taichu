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
     * 神煞表
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
