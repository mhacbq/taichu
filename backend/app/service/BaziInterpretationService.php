<?php
declare(strict_types=1);

namespace app\service;

/**
 * 八字命理解读服务
 * 
 * 提供专业、精准的八字分析解读
 */
class BaziInterpretationService
{
    /**
     * 十天干详细特性
     */
    protected $ganCharacteristics = [
        '甲' => [
            'nature' => '阳木',
            'symbol' => '参天大树',
            'traits' => ['正直', '仁慈', '有领导力', '有主见', '上进心强'],
            'strengths' => '有领导才能，做事有魄力，有远大理想，正直善良',
            'weaknesses' => '有时过于固执，容易独断专行，不够灵活变通',
            'career' => ['管理者', '创业者', '教育工作者', '政治家', '企业高管'],
        ],
        '乙' => [
            'nature' => '阴木',
            'symbol' => '花草藤蔓',
            'traits' => ['温柔', '细腻', '有韧性', '善解人意', '适应力强'],
            'strengths' => '温柔体贴，善于协调，有耐心和韧性，适应能力强',
            'weaknesses' => '有时过于柔顺，缺乏主见，容易受他人影响',
            'career' => ['设计师', '咨询师', '文艺工作者', '秘书', '协调员'],
        ],
        '丙' => [
            'nature' => '阳火',
            'symbol' => '太阳',
            'traits' => ['热情', '开朗', '大方', '积极', '有感染力'],
            'strengths' => '热情洋溢，有感染力，光明磊落，乐于助人，有创造力',
            'weaknesses' => '有时过于急躁，容易冲动，情绪波动大，爱面子',
            'career' => ['演艺人员', '销售', '讲师', '公关', '创意总监'],
        ],
        '丁' => [
            'nature' => '阴火',
            'symbol' => '烛火星光',
            'traits' => ['温和', '内敛', '细腻', '专注', '有洞察力'],
            'strengths' => '温和有礼，心思细腻，专注力强，善于观察，有艺术天赋',
            'weaknesses' => '有时过于敏感，容易多虑，情绪内敛不易表达',
            'career' => ['艺术家', '研究员', '心理咨询师', '编辑', '手艺人'],
        ],
        '戊' => [
            'nature' => '阳土',
            'symbol' => '高山城墙',
            'traits' => ['稳重', '诚实', '可靠', '包容', '有责任感'],
            'strengths' => '稳重可靠，诚实守信，有责任感，包容心强，踏实肯干',
            'weaknesses' => '有时过于保守，反应较慢，不善于变通，容易固执',
            'career' => ['金融从业者', '房地产', '管理者', '工程师', '公务员'],
        ],
        '己' => [
            'nature' => '阴土',
            'symbol' => '田园之土',
            'traits' => ['温和', '细心', '有耐心', '善于规划', '务实'],
            'strengths' => '细心周到，有耐心，善于规划，务实稳重，有奉献精神',
            'weaknesses' => '有时过于谨慎，缺乏冒险精神，容易犹豫不决',
            'career' => ['会计师', '规划师', '营养师', '园艺师', '档案管理'],
        ],
        '庚' => [
            'nature' => '阳金',
            'symbol' => '刀剑利器',
            'traits' => ['果断', '刚强', '正义', '有原则', '执行力强'],
            'strengths' => '果断决绝，执行力强，有正义感，重情重义，讲义气',
            'weaknesses' => '有时过于刚硬，不够圆滑，容易得罪人，过于直接',
            'career' => ['律师', '警察', '军人', '外科医生', '技术专家'],
        ],
        '辛' => [
            'nature' => '阴金',
            'symbol' => '珠玉首饰',
            'traits' => ['精致', '优雅', '有品味', '要求高', '追求完美'],
            'strengths' => '精致优雅，有品位，追求完美，善于修饰，有艺术感',
            'weaknesses' => '有时过于挑剔，容易纠结细节，虚荣心较强',
            'career' => ['珠宝设计师', '时尚编辑', '美容师', '质检员', '精密技工'],
        ],
        '壬' => [
            'nature' => '阳水',
            'symbol' => '江河湖海',
            'traits' => ['聪明', '灵活', '善变', '有谋略', '适应力强'],
            'strengths' => '聪明机智，适应力强，善于谋略，有包容心，学识渊博',
            'weaknesses' => '有时过于善变，不够专一，容易随波逐流，情绪起伏',
            'career' => ['咨询师', '投资人', '航海', '旅游业', '媒体人'],
        ],
        '癸' => [
            'nature' => '阴水',
            'symbol' => '雨露泉水',
            'traits' => ['温柔', '聪慧', '细腻', '直觉强', '有灵性'],
            'strengths' => '温柔聪慧，直觉敏锐，有灵性，善于倾听，富有同情心',
            'weaknesses' => '有时过于敏感，容易情绪化，缺乏主见，容易受影响',
            'career' => ['心理咨询师', '护士', '社工', '灵性导师', '作家'],
        ],
    ];

    /**
     * 十二地支藏干及特性
     */
    protected $zhiCharacteristics = [
        '子' => ['藏干' => ['癸'], '特性' => '智慧灵动，适应力强'],
        '丑' => ['藏干' => ['己', '癸', '辛'], '特性' => '稳重内敛，有耐心'],
        '寅' => ['藏干' => ['甲', '丙', '戊'], '特性' => '积极向上，有领导力'],
        '卯' => ['藏干' => ['乙'], '特性' => '温柔细腻，有韧性'],
        '辰' => ['藏干' => ['戊', '乙', '癸'], '特性' => '包容稳重，善于积累'],
        '巳' => ['藏干' => ['丙', '庚', '戊'], '特性' => '热情积极，有进取心'],
        '午' => ['藏干' => ['丁', '己'], '特性' => '光明磊落，有感染力'],
        '未' => ['藏干' => ['己', '丁', '乙'], '特性' => '温和务实，有包容心'],
        '申' => ['藏干' => ['庚', '壬', '戊'], '特性' => '机智灵活，有决断力'],
        '酉' => ['藏干' => ['辛'], '特性' => '精致优雅，追求完美'],
        '戌' => ['藏干' => ['戊', '辛', '丁'], '特性' => '忠诚可靠，有责任感'],
        '亥' => ['藏干' => ['壬', '甲'], '特性' => '聪慧善良，有想象力'],
    ];

    /**
     * 十神解读
     */
    protected $shishenMeanings = [
        '比肩' => [
            '含义' => '同辈、兄弟姐妹、朋友、同事',
            '正面' => '独立自主，有主见，自尊心强，有竞争力',
            '负面' => '固执己见，容易与人争执，过于自我',
        ],
        '劫财' => [
            '含义' => '同辈、竞争、争夺、消耗',
            '正面' => '行动力强，敢于冒险，有拼搏精神',
            '负面' => '容易破财，人际关系复杂，易有竞争',
        ],
        '食神' => [
            '含义' => '才华、口福、享受、输出',
            '正面' => '聪明有才华，善于表达，有口福，心态好',
            '负面' => '过于随性，缺乏进取心，容易满足现状',
        ],
        '伤官' => [
            '含义' => '才华、创意、叛逆、口才',
            '正面' => '才华横溢，创意十足，口才好，有主见',
            '负面' => '容易叛逆，口舌是非多，不服管束',
        ],
        '偏财' => [
            '含义' => '横财、父亲、情人、流动资产',
            '正面' => '善于理财，人缘好，慷慨大方，有商业头脑',
            '负面' => '容易投机，感情复杂，花钱大手大脚',
        ],
        '正财' => [
            '含义' => '正经收入、妻子、固定资产',
            '正面' => '勤俭持家，务实稳重，重视承诺，脚踏实地',
            '负面' => '过于保守，缺乏冒险精神，容易吝啬',
        ],
        '七杀' => [
            '含义' => '压力、挑战、权威、偏夫',
            '正面' => '有魄力，敢担当，有领导才能，抗压能力强',
            '负面' => '压力大，易遇小人，性格急躁，易有官非',
        ],
        '正官' => [
            '含义' => '事业、名誉、丈夫、约束力',
            '正面' => '正直守法，有责任感，重视名誉，事业心强',
            '负面' => '过于拘谨，缺乏变通，容易受约束',
        ],
        '偏印' => [
            '含义' => '偏门学问、继母、特殊才能',
            '正面' => '思维独特，有专长，直觉敏锐，善于思考',
            '负面' => '容易孤僻，想太多，与人格格不入',
        ],
        '正印' => [
            '含义' => '学业、母亲、贵人、保护',
            '正面' => '心地善良，有学识，易得贵人帮助，有包容心',
            '负面' => '容易依赖，缺乏独立性，有时过于理想化',
        ],
    ];

    /**
     * 纳音五行解读
     */
    protected $nayinMeanings = [
        '海中金' => '深藏不露，内秀型，有才华但不张扬',
        '炉中火' => '温暖人心，乐于助人，有奉献精神',
        '大林木' => '根基深厚，成长潜力大，需要耐心等待',
        '路旁土' => '平易近人，随和包容，善于帮助他人',
        '剑锋金' => '锋芒毕露，有锐气，适合竞争性行业',
        '山头火' => '光明正大，有号召力，但需注意控制脾气',
        '涧下水' => '细水长流，源源不断，有持久力',
        '城头土' => '稳重可靠，有防御力，重视安全感',
        '白蜡金' => '需要打磨，有潜力但需历练',
        '杨柳木' => '柔韧有余，适应力强，但需有主见',
        '泉中水' => '源源不断，有智慧，善于思考',
        '屋上土' => '高高在上，有地位，但需脚踏实地',
        '霹雳火' => '爆发力強，有冲劲，但需控制脾气',
        '松柏木' => '坚韧不拔，有毅力，适合长期事业',
        '长流水' => '源远流长，有持久力，适合长期发展',
        '沙中金' => '需要淘洗，有潜力但需发现',
        '山下火' => '内敛温和，有内涵，但不张扬',
        '平地木' => '平易近人，朴实无华，脚踏实地',
        '壁上土' => '依附性强，需要平台，但踏实可靠',
        '金箔金' => '外表光鲜，注重形象，有艺术感',
        '覆灯火' => '照亮他人，有智慧，但需注意身体',
        '天河水' => '高高在上，有格局，但需接地气',
        '大驿土' => '交通要冲，善于连接，有流动性',
        '钗钏金' => '精致优雅，有品位，注重细节',
        '桑柘木' => '默默奉献，有耐心，适合幕后工作',
        '大溪水' => '奔流不息，有活力，适合流动性行业',
        '沙中土' => '包容万物，有涵养，但需明确方向',
        '天上火' => '光芒万丈，有影响力，但需谦虚',
        '石榴木' => '硕果累累，有收获，适合结果导向工作',
        '大海水' => '胸怀宽广，有包容力，适合大格局工作',
    ];

    /**
     * 天干五行映射
     */
    protected $ganWuXing = [
        '甲' => '木', '乙' => '木', '丙' => '火', '丁' => '火', '戊' => '土',
        '己' => '土', '庚' => '金', '辛' => '金', '壬' => '水', '癸' => '水'
    ];

    /**
     * 五行生克关系
     */
    protected $wuxingRelations = [
        '金' => ['生' => '水', '克' => '木', '被生' => '土', '被克' => '火'],
        '木' => ['生' => '火', '克' => '土', '被生' => '水', '被克' => '金'],
        '水' => ['生' => '木', '克' => '火', '被生' => '金', '被克' => '土'],
        '火' => ['生' => '土', '克' => '金', '被生' => '木', '被克' => '水'],
        '土' => ['生' => '金', '克' => '水', '被生' => '火', '被克' => '木'],
    ];

    /**
     * 生成完整八字解读 - 增强版
     * 支持分级分析和智能质量评估
     * 新增格局分析和命理定语功能
     */
    public function generateFullInterpretation(array $bazi, string $gender, array $config = []): array
    {
        $defaultConfig = [
            'analysis_depth' => 'professional', // 分析深度：basic/standard/professional/expert
            'include_hehun' => false, // 是否包含合婚分析
            'quality_threshold' => 0.8, // 质量阈值
            'max_length' => 2000, // 最大分析长度
            'include_pattern' => true, // 是否包含格局分析
        ];
        $config = array_merge($defaultConfig, $config);
        
        $dayMaster = $bazi['day']['gan'] ?? '甲';
        if (!isset($this->ganCharacteristics[$dayMaster])) {
            $dayMaster = '甲';
        }

        $dayMasterWuxing = $bazi['day']['gan_wuxing'] ?? ($this->ganWuXing[$dayMaster] ?? '木');
        $bazi['day']['gan_wuxing'] = $dayMasterWuxing;

        $wuxingStats = $this->normalizeWuxingStats($bazi['wuxing_stats'] ?? []);
        
        // 分析五行强弱 - 根据深度调整分析精度
        $wuxingAnalysis = $this->analyzeWuxingStrength($wuxingStats, $config['analysis_depth']);
        
        // 分析喜用神 - 根据深度调整分析深度
        $yongshen = $this->determineYongshen($bazi, $wuxingStats, $config['analysis_depth']);
        
        // 分析日主特性 - 增强版分析
        $dayMasterAnalysis = $this->analyzeDayMasterEnhanced($dayMaster, $bazi, $config['analysis_depth']);
        
        // 分析十神配置
        $shishenAnalysis = $this->analyzeShishen($bazi);
        
        // 分析纳音
        $nayinAnalysis = $this->analyzeNayin($bazi);
        
        // 格局分析（新增）
        $patternAnalysis = null;
        $mingliDingyu = [];
        if ($config['include_pattern'] && isset($bazi['pattern']) && is_array($bazi['pattern'])) {
            $patternAnalysis = $bazi['pattern'];
            $mingliDingyu = $patternAnalysis['mingli_dingyu'] ?? [];
        }
        
        // 生成各方面解读
        $personality = $this->generatePersonalityAnalysis($dayMaster, $dayMasterWuxing, $shishenAnalysis, $wuxingAnalysis, $mingliDingyu);
        $career = $this->generateCareerAnalysis($dayMaster, $dayMasterWuxing, $yongshen, $shishenAnalysis, $patternAnalysis);
        $wealth = $this->generateWealthAnalysis($bazi, $shishenAnalysis, $yongshen, $patternAnalysis);
        $relationship = $this->generateRelationshipAnalysis($bazi, $gender, $shishenAnalysis, $patternAnalysis);
        $health = $this->generateHealthAnalysis($wuxingStats, $yongshen);
        $advice = $this->generateComprehensiveAdvice($yongshen, $wuxingStats, $dayMasterWuxing, $patternAnalysis);
        
        $result = [
            'basic' => [
                'day_master' => $dayMaster,
                'day_master_nature' => $this->ganCharacteristics[$dayMaster]['nature'],
                'day_master_symbol' => $this->ganCharacteristics[$dayMaster]['symbol'],
                'traits' => $this->ganCharacteristics[$dayMaster]['traits'],
            ],
            'wuxing' => $wuxingAnalysis,
            'yongshen' => $yongshen,
            'shishen' => $shishenAnalysis,
            'nayin' => $nayinAnalysis,
            'personality' => $personality,
            'career' => $career,
            'wealth' => $wealth,
            'relationship' => $relationship,
            'health' => $health,
            'advice' => $advice,
        ];
        
        // 添加格局分析到结果中
        if ($config['include_pattern'] && $patternAnalysis !== null) {
            $result['pattern'] = $patternAnalysis;
        }
        
        return $result;
    }

    /**
     * 归一化五行统计，确保始终包含金木水火土五个键
     */
    protected function normalizeWuxingStats(array $wuxingStats): array
    {
        $normalized = [
            '金' => 0.0,
            '木' => 0.0,
            '水' => 0.0,
            '火' => 0.0,
            '土' => 0.0,
        ];

        foreach ($normalized as $wx => $defaultValue) {
            $normalized[$wx] = isset($wuxingStats[$wx]) ? round(max(0, (float)$wuxingStats[$wx]), 2) : $defaultValue;
        }

        return $normalized;
    }

    /**
     * 基于加权五行分值生成统一强弱画像，避免“统计口径已改、解释阈值仍是旧整数”的错位。
     */
    protected function buildWuxingProfile(array $wuxingStats): array
    {
        $stats = $this->normalizeWuxingStats($wuxingStats);
        $total = array_sum($stats);
        $details = [];
        $lack = [];
        $weak = [];

        if ($total <= 0.001) {
            foreach ($stats as $wx => $count) {
                $details[$wx] = [
                    'count' => round($count, 2),
                    'percentage' => 0.0,
                    'strength' => '未知',
                    'desc' => '无有效数据',
                ];
            }

            return [
                'stats' => $stats,
                'details' => $details,
                'dominant' => '未知',
                'weakest' => '未知',
                'lack' => $lack,
                'weak' => $weak,
            ];
        }

        $ideal = $total / 5;
        foreach ($stats as $wx => $count) {
            $percentage = ($count / $total) * 100;
            $lackThreshold = max(0.08, $ideal * 0.18);
            $weakThreshold = max(0.45, $ideal * 0.72);
            $strongThreshold = $ideal * 1.25;
            $overStrongThreshold = $ideal * 1.55;

            if ($count <= $lackThreshold && $percentage < 4) {
                $strength = '缺';
                $desc = '明显不足';
                $lack[] = $wx;
            } elseif ($percentage >= 30 || $count >= $overStrongThreshold) {
                $strength = '旺';
                $desc = '过旺';
            } elseif ($percentage >= 24 || $count >= $strongThreshold) {
                $strength = '强';
                $desc = '偏强';
            } elseif ($percentage >= 12 || $count >= $weakThreshold) {
                $strength = '平';
                $desc = '中和';
            } else {
                $strength = '弱';
                $desc = '偏弱';
                $weak[] = $wx;
            }

            $details[$wx] = [
                'count' => round($count, 2),
                'percentage' => round($percentage, 1),
                'strength' => $strength,
                'desc' => $desc,
            ];
        }

        $max = max($stats);
        $min = min($stats);

        return [
            'stats' => $stats,
            'details' => $details,
            'dominant' => $this->findWuxingByValue($stats, $max),
            'weakest' => $this->findWuxingByValue($stats, $min),
            'lack' => $lack,
            'weak' => $weak,
        ];
    }


    /**
     * 根据分值取对应五行；若并列，则显式标记并列，避免把平票误说成单一最旺/最弱。
     */
    protected function findWuxingByValue(array $wuxingStats, float $target): string
    {
        $matched = [];
        foreach ($wuxingStats as $wx => $value) {
            if (abs($value - $target) < 0.0001) {
                $matched[] = $wx;
            }
        }

        if (empty($matched)) {
            return '未知';
        }

        if (count($matched) === 1) {
            return $matched[0];
        }

        return implode('、', $matched) . '（并列）';
    }


    /**
     * 分析五行强弱
     */
    protected function analyzeWuxingStrength(array $wuxingStats): array
    {
        $profile = $this->buildWuxingProfile($wuxingStats);

        return [
            'details' => $profile['details'],
            'dominant' => $profile['dominant'],
            'weakest' => $profile['weakest'],
            'balance_score' => $this->calculateBalanceScore($profile['stats']),
            'lack' => $profile['lack'],
            'weak' => $profile['weak'],
        ];
    }

    /**
     * 计算五行平衡度
     */
    protected function calculateBalanceScore(array $wuxingStats): int
    {
        $wuxingStats = $this->normalizeWuxingStats($wuxingStats);
        $total = array_sum($wuxingStats);
        if ($total <= 0.001) {
            return 0;
        }

        $percentages = array_map(
            static fn(float $value): float => ($value / $total) * 100,
            $wuxingStats
        );
        $values = array_values($percentages);
        $average = 20.0;
        $meanAbsoluteDeviation = array_sum(array_map(
            static fn(float $value): float => abs($value - $average),
            $values
        )) / count($values);
        $maxGap = max($values) - min($values);
        $lackCount = count(array_filter($values, static fn(float $value): bool => $value < 6));
        $overStrongCount = count(array_filter($values, static fn(float $value): bool => $value > 34));

        $score = 100
            - ($meanAbsoluteDeviation * 2.1)
            - ($maxGap * 0.35)
            - ($lackCount * 5)
            - ($overStrongCount * 3);

        // 偏枯命局不应被强行抬到 20 分，否则会掩盖加权五行已经明显失衡的事实。
        return (int) round(max(0, min(98, $score)));
    }



    /**
     * 确定喜用神 - 复用核心强弱评分，并纳入地支冲合结果
     */
    protected function determineYongshen(array $bazi, array $wuxingStats): array
    {
        $dayMasterWuxing = $bazi['day']['gan_wuxing'] ?? ($this->ganWuXing[$bazi['day']['gan'] ?? '甲'] ?? '木');
        $relations = $this->wuxingRelations[$dayMasterWuxing] ?? $this->wuxingRelations['木'];
        $strength = $bazi['strength'] ?? [];
        $score = (float)($strength['score'] ?? 0);
        $status = $strength['status'] ?? '';
        $interactionNotes = $strength['details']['branch_interactions'] ?? [];

        $isStrong = in_array($status, ['身旺', '中和偏旺'], true);
        if ($status === '') {
            $isStrong = $score >= 45;
            $status = $isStrong ? '身强' : '身弱';
        }

        $favoriteDetails = array_values(array_filter(
            $strength['favorite_wuxing_details'] ?? [],
            static fn($item): bool => is_array($item) && is_string($item['element'] ?? null) && ($item['element'] ?? '') !== ''
        ));

        if (empty($favoriteDetails)) {
            $favoriteDetails = $isStrong
                ? [
                    ['element' => $relations['被克'], 'relation' => '官杀', 'priority' => 1],
                    ['element' => $relations['生'], 'relation' => '食伤', 'priority' => 2],
                    ['element' => $relations['克'], 'relation' => '财星', 'priority' => 3],
                ]
                : [
                    ['element' => $relations['被生'], 'relation' => '印星', 'priority' => 1],
                    ['element' => $dayMasterWuxing, 'relation' => '比劫', 'priority' => 2],
                ];
        }

        $normalizedStats = $this->normalizeWuxingStats($wuxingStats);
        usort($favoriteDetails, function (array $left, array $right) use ($normalizedStats): int {
            $priorityCompare = ((int) ($left['priority'] ?? 99)) <=> ((int) ($right['priority'] ?? 99));
            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }

            $leftValue = (float) ($normalizedStats[$left['element'] ?? ''] ?? 0);
            $rightValue = (float) ($normalizedStats[$right['element'] ?? ''] ?? 0);
            if (abs($leftValue - $rightValue) >= 0.0001) {
                return $leftValue <=> $rightValue;
            }

            return strcmp((string) ($left['element'] ?? ''), (string) ($right['element'] ?? ''));
        });

        $favoriteCandidates = [];
        foreach ($favoriteDetails as $detail) {
            $element = (string) ($detail['element'] ?? '');
            if ($element !== '' && !in_array($element, $favoriteCandidates, true)) {
                $favoriteCandidates[] = $element;
            }
        }

        if (empty($favoriteCandidates)) {
            $favoriteCandidates = [$dayMasterWuxing];
        }

        $primary = $favoriteCandidates[0];
        $secondary = $favoriteCandidates[1] ?? $primary;
        $favoriteText = implode('、', $favoriteCandidates);
        $strategyText = $isStrong
            ? '依“身强先官杀、次食伤、后财星”的次序疏泄制衡'
            : '依“身弱先印星、次比劫”的次序扶身培元';

        if ($isStrong) {
            $yongshen = [
                'shen' => $primary,
                'xi' => $secondary,
                'type' => '身强',
                'score' => $score,
                'favorable_elements' => $favoriteCandidates,
                'favorable_element_details' => $favoriteDetails,
                'desc' => "综合月令、透干、藏干及地支冲合后，命局强度得分为{$score}，当前偏{$status}。{$strategyText}，宜以{$favoriteText}来疏泄制衡，其中当前更急需先借{$primary}发力。",
            ];
        } else {
            $yongshen = [
                'shen' => $primary,
                'xi' => $secondary,
                'type' => '身弱',
                'score' => $score,
                'favorable_elements' => $favoriteCandidates,
                'favorable_element_details' => $favoriteDetails,
                'desc' => "综合月令、透干、藏干及地支冲合后，命局强度得分为{$score}，当前偏{$status}。{$strategyText}，宜以{$favoriteText}来扶身培元，其中当前更急需先补{$primary}。",
            ];
        }

        if (!empty($interactionNotes)) {
            $yongshen['desc'] .= '地支作用要点：' . implode('；', array_slice($interactionNotes, 0, 3)) . '。';
        }

        $profile = $this->buildWuxingProfile($wuxingStats);
        $priorityElements = array_slice($favoriteCandidates, 0, 2);
        $priorityLack = array_values(array_intersect($profile['lack'], $priorityElements));
        $priorityWeak = array_values(array_intersect($profile['weak'], $priorityElements));
        if (!empty($priorityLack)) {
            $yongshen['que'] = $priorityLack[0];
            $yongshen['desc'] .= "其中{$priorityLack[0]}既属喜用又明显不足，调理时可优先围绕该五行展开。";
        } elseif (!empty($priorityWeak)) {
            $yongshen['weak'] = $priorityWeak[0];
            $yongshen['desc'] .= "其中{$priorityWeak[0]}既属喜用又偏弱，可作为后续调衡的重点。";
        } elseif (!empty($profile['lack'])) {
            $yongshen['imbalance_hint'] = $profile['lack'][0];
            $yongshen['desc'] .= "命局另见{$profile['lack'][0]}不足，但是否取补仍应以扶抑取用次序为先，不宜脱离喜用神机械补缺。";
        } elseif (!empty($profile['weak'])) {
            $yongshen['imbalance_hint'] = $profile['weak'][0];
            $yongshen['desc'] .= "命局另见{$profile['weak'][0]}偏弱，可作结构参考，但调理仍以喜用神先后为主。";
        }

        return $yongshen;
    }


    /**
     * 分析日主
     */
    protected function analyzeDayMaster(string $dayMaster, array $bazi): array
    {
        $chars = $this->ganCharacteristics[$dayMaster];
        $dayZhi = $bazi['day']['zhi'];
        $dayZhiChars = $this->zhiCharacteristics[$dayZhi];
        
        return [
            'nature' => $chars['nature'],
            'symbol' => $chars['symbol'],
            'traits' => $chars['traits'],
            'strengths' => $chars['strengths'],
            'weaknesses' => $chars['weaknesses'],
            'day_zhi_effect' => $dayZhiChars['特性'],
        ];
    }

    /**
     * 增强版日主分析 - 支持分级分析深度
     */
    protected function analyzeDayMasterEnhanced(string $dayMaster, array $bazi, string $depth = 'standard'): array
    {
        $basicAnalysis = $this->analyzeDayMaster($dayMaster, $bazi);
        
        $enhancedAnalysis = [
            'basic' => $basicAnalysis,
            'depth_level' => $depth,
            'comprehensive_analysis' => '',
            'career_recommendations' => [],
            'relationship_insights' => [],
            'health_considerations' => [],
            'development_advice' => ''
        ];
        
        // 根据分析深度提供不同级别的分析
        switch ($depth) {
            case 'basic':
                $enhancedAnalysis['comprehensive_analysis'] = $this->generateBasicDayMasterAnalysis($dayMaster, $bazi);
                break;
                
            case 'standard':
                $enhancedAnalysis['comprehensive_analysis'] = $this->generateStandardDayMasterAnalysis($dayMaster, $bazi);
                $enhancedAnalysis['career_recommendations'] = $this->getCareerRecommendations($dayMaster);
                break;
                
            case 'professional':
                $enhancedAnalysis['comprehensive_analysis'] = $this->generateProfessionalDayMasterAnalysis($dayMaster, $bazi);
                $enhancedAnalysis['career_recommendations'] = $this->getCareerRecommendations($dayMaster);
                $enhancedAnalysis['relationship_insights'] = $this->getRelationshipInsights($dayMaster, $bazi);
                break;
                
            case 'expert':
                $enhancedAnalysis['comprehensive_analysis'] = $this->generateExpertDayMasterAnalysis($dayMaster, $bazi);
                $enhancedAnalysis['career_recommendations'] = $this->getCareerRecommendations($dayMaster);
                $enhancedAnalysis['relationship_insights'] = $this->getRelationshipInsights($dayMaster, $bazi);
                $enhancedAnalysis['health_considerations'] = $this->getHealthConsiderations($dayMaster, $bazi);
                $enhancedAnalysis['development_advice'] = $this->getDevelopmentAdvice($dayMaster, $bazi);
                break;
                
            default:
                $enhancedAnalysis['comprehensive_analysis'] = $this->generateStandardDayMasterAnalysis($dayMaster, $bazi);
                $enhancedAnalysis['career_recommendations'] = $this->getCareerRecommendations($dayMaster);
        }
        
        return $enhancedAnalysis;
    }

    /**
     * 生成基础日主分析
     */
    protected function generateBasicDayMasterAnalysis(string $dayMaster, array $bazi): string
    {
        $chars = $this->ganCharacteristics[$dayMaster];
        return "你的日主为{$dayMaster}（{$chars['nature']}），气质像{$chars['symbol']}。主要特点：" . implode('、', $chars['traits']) . "。";
    }

    /**
     * 生成标准日主分析
     */
    protected function generateStandardDayMasterAnalysis(string $dayMaster, array $bazi): string
    {
        $basicAnalysis = $this->generateBasicDayMasterAnalysis($dayMaster, $bazi);
        $chars = $this->ganCharacteristics[$dayMaster];
        
        $analysis = $basicAnalysis;
        $analysis .= "你的优势在于{$chars['strengths']}，需要注意{$chars['weaknesses']}。";
        
        // 结合日支影响
        $dayZhi = $bazi['day']['zhi'];
        $dayZhiChars = $this->zhiCharacteristics[$dayZhi];
        $analysis .= "日支为{$dayZhi}，会增强你{$dayZhiChars['特性']}的特点。";
        
        return $analysis;
    }

    /**
     * 生成专业日主分析
     */
    protected function generateProfessionalDayMasterAnalysis(string $dayMaster, array $bazi): string
    {
        $standardAnalysis = $this->generateStandardDayMasterAnalysis($dayMaster, $bazi);
        
        // 分析五行平衡对日主的影响
        $wuxingStats = $bazi['wuxing_stats'] ?? [];
        $dayMasterWuxing = $this->ganWuXing[$dayMaster];
        $dayMasterStrength = $wuxingStats[$dayMasterWuxing] ?? 0;
        
        $analysis = $standardAnalysis;
        
        if ($dayMasterStrength >= 2.0) {
            $analysis .= "你的{$dayMasterWuxing}气较旺，日主有力，做事有魄力，但要注意避免过于强势。";
        } elseif ($dayMasterStrength <= 1.0) {
            $analysis .= "你的{$dayMasterWuxing}气偏弱，日主需要扶助，建议多借助外力，培养自信心。";
        } else {
            $analysis .= "你的{$dayMasterWuxing}气适中，日主平衡，能够较好地发挥自身优势。";
        }
        
        return $analysis;
    }

    /**
     * 生成专家级日主分析
     */
    protected function generateExpertDayMasterAnalysis(string $dayMaster, array $bazi): string
    {
        $professionalAnalysis = $this->generateProfessionalDayMasterAnalysis($dayMaster, $bazi);
        
        // 分析十神配置对日主的影响
        $shishenAnalysis = $this->analyzeShishen($bazi);
        $dominantShishen = $shishenAnalysis['dominant'] ?? '';
        
        $analysis = $professionalAnalysis;
        
        if ($dominantShishen) {
            $shishenMeaning = $this->shishenMeanings[$dominantShishen] ?? null;
            if ($shishenMeaning) {
                $analysis .= "命局中{$dominantShishen}突出，说明你{$shishenMeaning['正面']}，这对你的性格发展有重要影响。";
            }
        }
        
        return $analysis;
    }

    /**
     * 获取职业推荐
     */
    protected function getCareerRecommendations(string $dayMaster): array
    {
        $chars = $this->ganCharacteristics[$dayMaster];
        return $chars['career'];
    }

    /**
     * 获取关系洞察
     */
    protected function getRelationshipInsights(string $dayMaster, array $bazi): array
    {
        $chars = $this->ganCharacteristics[$dayMaster];
        $insights = [];
        
        // 根据日主特性提供关系建议
        if (in_array($dayMaster, ['甲', '丙', '戊', '庚', '壬'])) {
            // 阳干日主
            $insights[] = '你在关系中比较主动，喜欢主导，但要注意给对方足够的空间。';
            $insights[] = '你的直率性格容易赢得信任，但有时需要更细腻的表达方式。';
        } else {
            // 阴干日主
            $insights[] = '你在关系中比较细腻，善于察言观色，但需要学会表达自己的真实想法。';
            $insights[] = '你的温和性格容易建立和谐关系，但要注意保持自己的立场。';
        }
        
        return $insights;
    }

    /**
     * 获取健康考虑
     */
    protected function getHealthConsiderations(string $dayMaster, array $bazi): array
    {
        $dayMasterWuxing = $this->ganWuXing[$dayMaster];
        $considerations = [];
        
        // 根据日主五行提供健康建议
        $healthMapping = [
            '木' => ['肝胆系统', '眼睛', '筋骨'],
            '火' => ['心脏', '血液循环', '小肠'],
            '土' => ['脾胃', '消化系统', '肌肉'],
            '金' => ['肺脏', '呼吸系统', '皮肤'],
            '水' => ['肾脏', '泌尿系统', '骨骼']
        ];
        
        if (isset($healthMapping[$dayMasterWuxing])) {
            $organs = implode('、', $healthMapping[$dayMasterWuxing]);
            $considerations[] = "你的日主属{$dayMasterWuxing}，需要特别关注{$organs}的健康。";
        }
        
        return $considerations;
    }

    /**
     * 获取发展建议
     */
    protected function getDevelopmentAdvice(string $dayMaster, array $bazi): string
    {
        $chars = $this->ganCharacteristics[$dayMaster];
        
        $advice = "基于你的日主特性，建议：\n";
        $advice .= "1. 充分发挥你的优势：{$chars['strengths']}\n";
        $advice .= "2. 注意改善你的弱点：{$chars['weaknesses']}\n";
        $advice .= "3. 在职业发展上，可以考虑：" . implode('、', $chars['career']) . "\n";
        $advice .= "4. 人际关系中，保持你的" . implode('、', array_slice($chars['traits'], 0, 2)) . "特质\n";
        
        return $advice;
    }

    /**
     * 分析十神配置
     */
    protected function analyzeShishen(array $bazi): array
    {
        $shishenList = [
            'year' => $bazi['year']['shishen'],
            'month' => $bazi['month']['shishen'],
            'hour' => $bazi['hour']['shishen'],
        ];
        
        $analysis = [];
        $shishenCount = array_count_values($shishenList);
        
        foreach ($shishenList as $position => $shishen) {
            $meaning = $this->shishenMeanings[$shishen] ?? null;
            if ($meaning) {
                $analysis[$position] = [
                    'name' => $shishen,
                    'meaning' => $meaning['含义'],
                    'effect' => $meaning['正面'],
                ];
            }
        }
        
        // 找出最突出的十神
        arsort($shishenCount);
        $dominantShishen = array_key_first($shishenCount);
        
        return [
            'details' => $analysis,
            'dominant' => $dominantShishen,
            'count' => $shishenCount,
        ];
    }

    /**
     * 分析纳音
     */
    protected function analyzeNayin(array $bazi): array
    {
        $nayins = [
            'year' => $bazi['year']['nayin'],
            'month' => $bazi['month']['nayin'],
            'day' => $bazi['day']['nayin'],
            'hour' => $bazi['hour']['nayin'],
        ];
        
        $analysis = [];
        foreach ($nayins as $position => $nayin) {
            if ($nayin && isset($this->nayinMeanings[$nayin])) {
                $analysis[$position] = [
                    'name' => $nayin,
                    'meaning' => $this->nayinMeanings[$nayin],
                ];
            }
        }
        
        return $analysis;
    }

    /**
     * 生成性格分析
     * 新增命理定语集成 + 巴纳姆效应语言风格
     */
    protected function generatePersonalityAnalysis(string $dayMaster, string $wuxing, array $shishenAnalysis, array $wuxingAnalysis, array $mingliDingyu = []): string
    {
        $chars = $this->ganCharacteristics[$dayMaster];

        // 巴纳姆开场：普遍性 + 内心矛盾感
        $barnum_openers = [
            "你是一个内心世界相当丰富的人，外表呈现出来的，往往只是你真实自我的一部分。",
            "你有一种独特的气质——在人群中不算最张扬，却总能让人记住你。",
            "你对自己的要求比别人想象的要高得多，这种内在标准有时会让你感到压力，但也正是你不断进步的动力。",
        ];
        $opener = $barnum_openers[abs(crc32($dayMaster)) % count($barnum_openers)];

        $analysis = $opener;
        $analysis .= "从八字来看，你的日主属{$chars['nature']}，气质如{$chars['symbol']}，" . $chars['strengths'] . '。';

        // 巴纳姆：正负并存
        $barnum_duality = [
            '甲' => '你有很强的主见，但有时也会因为过于坚持自己的想法而显得固执，这一点你自己心里其实清楚。',
            '乙' => '你表面温和，但内心有一条不轻易妥协的底线，只是很少有人真正了解这一面的你。',
            '丙' => '你热情开朗，容易感染周围的人，但偶尔也会有一种旁人难以察觉的疲惫感。',
            '丁' => '你细腻敏感，能感受到别人注意不到的细节，但有时这种敏感也会让你想太多。',
            '戊' => '你给人稳重可靠的印象，但内心其实渴望更多的变化和突破，只是不轻易表露。',
            '己' => '你善于照顾他人的感受，但有时会因为太顾及别人而忽略了自己真正想要的东西。',
            '庚' => '你做事果断，执行力强，但有时会因为太直接而在人际关系上走一些弯路。',
            '辛' => '你对品质有很高的追求，不将就，但这种完美主义有时也会让你在决策时犹豫不决。',
            '壬' => '你思维活跃，适应力强，但有时想法太多反而难以聚焦，容易分散精力。',
            '癸' => '你内敛而深邃，观察力极强，但有时会因为想得太深而迟迟不肯迈出第一步。',
        ];
        if (isset($barnum_duality[$dayMaster])) {
            $analysis .= $barnum_duality[$dayMaster];
        }

        // 结合十神
        if (isset($shishenAnalysis['dominant'])) {
            $dominantShishen = $shishenAnalysis['dominant'];
            $shishenInfo = $this->shishenMeanings[$dominantShishen] ?? null;
            if ($shishenInfo) {
                $analysis .= "命局中{$dominantShishen}较旺，说明你" . $shishenInfo['正面'] . '，这一点在你的日常行为中应该有所体现。';
            }
        }

        // 结合五行 + 巴纳姆：潜力暗示
        if ($wuxingAnalysis['balance_score'] >= 75) {
            $analysis .= '你的五行配置较为平衡，性格中有一种难得的稳定感，不容易被外界情绪左右，这是很多人羡慕却难以做到的特质。';
        } else {
            $dominant = $wuxingAnalysis['dominant'];
            $analysis .= "你的{$dominant}之气偏旺，" . $this->getWuxingTrait($dominant) . '的特质在你身上尤为突出，这既是你的优势，也是你需要有意识去平衡的地方。';
        }

        // 巴纳姆：未被发掘的潜力
        $analysis .= '你身上有一些潜力，连你自己可能都还没有完全意识到，只要找到合适的方向，往往能超出自己的预期。';

        // 添加命理定语（新增）
        if (!empty($mingliDingyu)) {
            $analysis .= ' ' . implode(' ', $mingliDingyu);
        }

        return $analysis;
    }


    /**
     * 生成事业分析
     * 新增格局分析集成 + 巴纳姆效应语言风格
     */
    protected function generateCareerAnalysis(string $dayMaster, string $wuxing, array $yongshen, array $shishenAnalysis, ?array $patternAnalysis = null): string
    {
        $chars = $this->ganCharacteristics[$dayMaster];

        // 巴纳姆：普遍性的职业困惑感
        $analysis = '你在职业选择上，可能曾经有过一段时间的迷茫——不是因为能力不够，而是因为你的潜力其实可以胜任多个方向，反而让你难以取舍。';
        $analysis .= "从{$wuxing}属性来看，" . implode('、', $chars['career']) . '等领域与你的天赋气质最为契合，在这些方向上你往往能事半功倍。';

        // 结合喜用神
        if (isset($yongshen['shen'])) {
            $analysis .= "命局喜{$yongshen['shen']}，接触与{$yongshen['shen']}相关的行业更容易进入状态、发挥长处。";
            $careerByWuxing = [
                '金' => '金融、法律、机械、汽车、珠宝',
                '木' => '教育、文化、出版、设计、环保',
                '水' => '贸易、物流、旅游、咨询、传媒',
                '火' => '能源、餐饮、美容、演艺、互联网',
                '土' => '房地产、建筑、农业、保险、管理',
            ];
            $analysis .= "例如{$careerByWuxing[$yongshen['shen']]}等方向，都值得认真考虑。";
        }

        // 结合十神 + 巴纳姆：认可感
        if (isset($shishenAnalysis['dominant'])) {
            $shishen = $shishenAnalysis['dominant'];
            if (in_array($shishen, ['正官', '七杀'])) {
                $analysis .= '命局官星较旺，你有天然的管理气场，身边的人往往会不自觉地听从你的意见，适合走仕途或管理岗位，你的领导力比你自己意识到的要强。';
            } elseif (in_array($shishen, ['正财', '偏财'])) {
                $analysis .= '命局财星较旺，你有商业直觉，对机会的嗅觉比一般人灵敏，适合经商或投资理财，只要方向对了，财富积累的速度往往会超出预期。';
            } elseif (in_array($shishen, ['食神', '伤官'])) {
                $analysis .= '命局食伤较旺，你有才华和创意，很多时候你的想法比周围人超前，适合技术、艺术或自由职业，在能发挥个人风格的环境里你会如鱼得水。';
            }
        }

        // 巴纳姆：努力与时机的普遍共鸣
        $analysis .= '值得注意的是，你的事业运并非一帆风顺，中间可能会有一段需要蛰伏积累的时期，但这段时期往往是为后来的爆发做准备，不必过于焦虑。';

        // 添加格局分析（新增）
        if ($patternAnalysis !== null && !empty($patternAnalysis['eight_patterns'])) {
            $mainPattern = $patternAnalysis['eight_patterns'][0] ?? null;
            if ($mainPattern && isset($mainPattern['name'])) {
                $analysis .= " 你的八字格局为【{$mainPattern['name']}】，{$mainPattern['description']}";
            }
        }

        return $analysis;
    }


    /**
     * 生成财运分析
     * 新增格局分析集成 + 巴纳姆效应语言风格
     */
    protected function generateWealthAnalysis(array $bazi, array $shishenAnalysis, array $yongshen, ?array $patternAnalysis = null): string
    {
        $hasCai = false;
        $caiType = '';

        // 检查是否有财星
        foreach ($shishenAnalysis['details'] ?? [] as $pos => $info) {
            if (in_array($info['name'], ['正财', '偏财'])) {
                $hasCai = true;
                $caiType = $info['name'];
                break;
            }
        }

        if (!$hasCai) {
            // 巴纳姆：将"财星不显"转化为正面激励，而非直接否定
            return '你的命局财星不显，这并不意味着你与财富无缘，而是说明你的财富更多来自于自身的积累与努力，而非偶然的横财。事实上，很多通过踏实工作积累财富的人，反而比依赖偏财的人走得更稳。建议通过专业技能建立稳定收入，不宜投机冒险，你的财富曲线可能不是一夜暴富型，但会是持续上升型。';
        }

        // 巴纳姆：财星存在时的正面强化
        $analysis = "你的命局有{$caiType}，" . ($caiType == '正财' ? '说明你有稳定的财富积累能力，适合通过正当途径建立财务基础，你对金钱的态度比较务实，不会轻易被高风险诱惑。' : '说明你有偏财运，对投资机会的嗅觉比一般人灵敏，可能会有意外之财或投资收益，但也要注意来得快去得也快的风险。');

        if (isset($yongshen['type'])) {
            if ($yongshen['type'] == '身强') {
                $analysis .= '身强能担财，你有能力管理和积累财富，财运较好，只要方向选对，财富积累的速度往往会超出你自己的预期。';
            } else {
                $analysis .= '身弱财多，容易财来财去，这一点你可能自己也有所感受。建议稳健理财，不宜大额投资，先把基础打牢，财富自然会慢慢积累。';
            }
        }

        return $analysis;
    }

    /**
     * 生成感情分析
     */
    protected function generateRelationshipAnalysis(array $bazi, string $gender, array $shishenAnalysis): string
    {
        $spouseStar = $gender == 'male' ? ['正财', '偏财'] : ['正官', '七杀'];
        $hasSpouseStar = false;
        
        foreach ($shishenAnalysis['details'] ?? [] as $pos => $info) {
            if (in_array($info['name'], $spouseStar)) {
                $hasSpouseStar = true;
                break;
            }
        }
        
        $dayZhi = $bazi['day']['zhi'];

        // 巴纳姆：感情上的普遍矛盾感
        $analysis = '在感情方面，你是一个有自己想法的人——你渴望真正的情感连接，而不只是表面的陪伴，但有时候又会因为太在意对方的感受而压抑自己的真实需求。';

        if ($gender == 'male') {
            if ($hasSpouseStar) {
                $analysis .= '男命以财星为妻星，你命局妻星明朗，感情运势较为顺利，生命中不乏有缘人，关键在于你是否愿意迈出那一步。';
            } else {
                $analysis .= '男命以财星为妻星，你命局妻星不显，感情上可能需要比别人多一些耐心，但这并不意味着缘分不好，只是你的另一半可能出现得比你预期的晚一些，或者以一种你意想不到的方式出现。';
            }
        } else {
            if ($hasSpouseStar) {
                $analysis .= '女命以官杀为夫星，你命局夫星明朗，容易遇到心仪的对象，感情较为顺遂，你身上有一种让人想要靠近的气质。';
            } else {
                $analysis .= '女命以官杀为夫星，你命局夫星不显，感情上可能较为被动，有时候明明心里有感觉，却不知道如何开口，建议适当主动一些，缘分有时候需要自己去推一把。';
            }
        }

        // 日支（配偶宫）分析 + 巴纳姆：配偶描述加入共鸣感
        $spousePalace = [
            '子' => '配偶聪明机灵，思维活跃，能给你带来新鲜感',
            '丑' => '配偶稳重踏实，是那种让你感到安心的类型',
            '寅' => '配偶积极向上，有冲劲，能激励你不断前进',
            '卯' => '配偶温柔善良，细腻体贴，懂得照顾你的情绪',
            '辰' => '配偶包容大度，不计较小事，是很好的人生伴侣',
            '巳' => '配偶热情开朗，充满活力，能让你的生活更有色彩',
            '午' => '配偶光明磊落，直来直去，相处起来很省心',
            '未' => '配偶温和务实，不爱争吵，家庭生活会比较和谐',
            '申' => '配偶机智灵活，处事圆滑，能帮你处理很多麻烦',
            '酉' => '配偶精致优雅，有品位，会让你觉得很有面子',
            '戌' => '配偶忠诚可靠，一旦认定就会全心全意，值得托付',
            '亥' => '配偶聪慧善良，内心深邃，越相处越能发现其好',
        ];

        if (isset($spousePalace[$dayZhi])) {
            $analysis .= "你的日支为{$dayZhi}，{$spousePalace[$dayZhi]}。";
        }

        // 巴纳姆：感情建议的普遍共鸣
        $analysis .= '感情上，你最需要的不是完美的另一半，而是一个真正懂你的人。你有时候会在感情里付出很多，但也要记得留一些空间给自己。';

        return $analysis;
    }

    /**
     * 生成健康分析
     * 巴纳姆效应语言风格
     */
    protected function generateHealthAnalysis(array $wuxingStats, array $yongshen): string
    {
        $organMap = [
            '金' => '肺、大肠、呼吸系统',
            '木' => '肝、胆、眼睛',
            '水' => '肾、膀胱、生殖系统',
            '火' => '心、小肠、血液循环',
            '土' => '脾、胃、消化系统',
        ];

        // 巴纳姆：健康上的普遍共鸣开场
        $analysis = '你对自己的身体状况其实有一定的感知，只是有时候会因为忙碌而忽略一些身体发出的小信号。';
        $profile = $this->buildWuxingProfile($wuxingStats);
        $hasSpecific = false;

        foreach ($profile['details'] as $wx => $detail) {
            if (in_array($detail['strength'], ['旺', '强'], true)) {
                $analysis .= "你的{$wx}偏旺，{$organMap[$wx]}方面容易因为过度使用而出现问题，建议注意保养，避免长期透支，这类问题往往是积累出来的，早发现早调整。";
                $hasSpecific = true;
            } elseif ($detail['strength'] === '缺') {
                $analysis .= "你的{$wx}明显不足，{$organMap[$wx]}方面是你需要重点关注的区域，可能偶尔会有一些不适感，建议定期检查，不要等到问题严重了才重视。";
                $hasSpecific = true;
            } elseif ($detail['strength'] === '弱') {
                $analysis .= "你的{$wx}偏弱，{$organMap[$wx]}方面需要多加关注，平时注意调养，不要等身体发出强烈信号才开始重视。";
                $hasSpecific = true;
            }
        }

        if (!$hasSpecific) {
            $analysis .= '你的五行配置相对平衡，整体健康状况良好，这是很多人羡慕的体质基础。保持规律作息和适度运动，你的身体会给你很好的回报。';
        }

        // 巴纳姆：普遍性的健康建议
        $analysis .= '另外，你的情绪状态对身体健康的影响比一般人更大，当你心情好的时候，整个人的状态都会明显不同，所以保持好心情也是一种养生。';

        return $analysis;
    }

    /**
     * 生成综合建议
     * 巴纳姆效应语言风格
     */
    protected function generateComprehensiveAdvice(array $yongshen, array $wuxingStats, string $dayMasterWuxing): string
    {
        // 巴纳姆：开场共鸣
        $advice = '你是一个有自己想法的人，不太喜欢被别人的意见左右，但在某些重要决定面前，你也会希望有一些来自外部的参考和指引。以下是根据你的八字命局，为你量身梳理的调运建议。';

        // 喜用神建议
        if (isset($yongshen['shen'])) {
            $shen = $yongshen['shen'];
            $directions = ['金' => '西方', '木' => '东方', '水' => '北方', '火' => '南方', '土' => '中央'];
            $colors = ['金' => '白色、金色', '木' => '绿色、青色', '水' => '黑色、蓝色', '火' => '红色、紫色', '土' => '黄色、棕色'];
            $numbers = ['金' => '4、9', '木' => '3、8', '水' => '1、6', '火' => '2、7', '土' => '5、0'];

            $advice .= "你的喜用神是{$shen}，这是你命局中最需要借助的力量。建议：";
            $advice .= "发展方向宜选择{$directions[$shen]}，这个方向对你来说有一种天然的亲和力；";
            $advice .= "幸运颜色为{$colors[$shen]}，在重要场合穿戴这类颜色，往往能给你带来更好的状态；";
            $advice .= "幸运数字为{$numbers[$shen]}，在需要做选择的时候，这些数字可以作为参考。";
        }

        $profile = $this->buildWuxingProfile($wuxingStats);
        $focusElements = array_values(array_filter($yongshen['favorable_elements'] ?? [], static fn($item): bool => is_string($item) && $item !== ''));
        if (empty($focusElements) && isset($yongshen['shen'])) {
            $focusElements[] = $yongshen['shen'];
        }
        $focusElements = array_slice($focusElements, 0, 2);
        if (!empty($focusElements)) {
            $advice .= '实际调理宜先围绕' . implode('、', $focusElements) . '展开，以扶抑用神次序为主。';
        }

        $priorityLack = array_values(array_intersect($profile['lack'], $focusElements));
        $priorityWeak = array_values(array_intersect($profile['weak'], $focusElements));
        if (!empty($priorityLack)) {
            $advice .= '其中' . $priorityLack[0] . '恰属喜用且明显不足，可优先从作息、环境与颜色上补其气。';
        } elseif (!empty($priorityWeak)) {
            $advice .= '其中' . $priorityWeak[0] . '恰属喜用而偏弱，可作为后续调衡的重点。';
        } elseif (!empty($profile['lack'])) {
            $advice .= '命局虽见' . implode('、', $profile['lack']) . '不足，但不必脱离喜用神顺序单独追补。';
        } elseif (!empty($profile['weak'])) {
            $advice .= '命局另有' . implode('、', $profile['weak']) . '偏弱，可作为结构参考，但仍以喜用神为先。';
        }

        return $advice;
    }

    /**
     * 获取五行特质
     */
    protected function getWuxingTrait(string $wuxing): string
    {
        $traits = [
            '金' => '果断、刚毅、正义',
            '木' => '仁慈、正直、向上',
            '水' => '智慧、灵活、包容',
            '火' => '热情、积极、光明',
            '土' => '稳重、包容、诚信',
        ];
        return $traits[$wuxing] ?? '';
    }

    /**
     * 生成通俗解读（简化版）
     */
    public function generateSimpleInterpretation(array $bazi, string $gender): array
    {
        $full = $this->generateFullInterpretation($bazi, $gender);
        
        return [
            'personality' => $this->simplifyText($full['personality']),
            'career' => $this->simplifyText($full['career']),
            'wealth' => $this->simplifyText($full['wealth']),
            'relationship' => $this->simplifyText($full['relationship']),
            'health' => $this->simplifyText($full['health']),
            'advice' => $this->simplifyText($full['advice']),
        ];
    }

    /**
     * 简化文本为通俗语言
     */
    protected function simplifyText(string $text): string
    {
        // 移除过于专业的术语，简化表达。
        // 这里不再直接把“日主”替换成“你”，否则很容易产生“阳金你”“金你特性”这类病句。
        $replacements = [
            '命局' => '八字',
            '喜用神' => '有利的五行',
            '身强' => '自身能量强',
            '身弱' => '自身能量弱',
            '官星' => '事业星',
            '财星' => '财运星',
            '食伤' => '才华星',
            '印绶' => '贵人星',
        ];
        
        foreach ($replacements as $old => $new) {
            $text = str_replace($old, $new, $text);
        }

        $text = preg_replace('/，+/', '，', $text) ?? $text;
        $text = preg_replace('/。+/', '。', $text) ?? $text;
        
        return trim($text);
    }

}