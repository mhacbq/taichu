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
     * 生成完整八字解读
     */
    public function generateFullInterpretation(array $bazi, string $gender): array
    {
        $dayMaster = $bazi['day']['gan'];
        $wuxingStats = $bazi['wuxing_stats'];
        
        // 分析五行强弱
        $wuxingAnalysis = $this->analyzeWuxingStrength($wuxingStats);
        
        // 分析喜用神 - 传入完整bazi以进行深入分析
        $yongshen = $this->determineYongshen($bazi, $wuxingStats);
        
        // 分析日主特性
        $dayMasterAnalysis = $this->analyzeDayMaster($dayMaster, $bazi);
        
        // 分析十神配置
        $shishenAnalysis = $this->analyzeShishen($bazi);
        
        // 分析纳音
        $nayinAnalysis = $this->analyzeNayin($bazi);
        
        // 生成各方面解读
        $personality = $this->generatePersonalityAnalysis($dayMaster, $dayMasterWuxing, $shishenAnalysis, $wuxingAnalysis);
        $career = $this->generateCareerAnalysis($dayMaster, $dayMasterWuxing, $yongshen, $shishenAnalysis);
        $wealth = $this->generateWealthAnalysis($bazi, $shishenAnalysis, $yongshen);
        $relationship = $this->generateRelationshipAnalysis($bazi, $gender, $shishenAnalysis);
        $health = $this->generateHealthAnalysis($wuxingStats, $yongshen);
        $advice = $this->generateComprehensiveAdvice($yongshen, $wuxingStats, $dayMasterWuxing);
        
        return [
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
    }

    /**
     * 分析五行强弱
     */
    protected function analyzeWuxingStrength(array $wuxingStats): array
    {
        $total = array_sum($wuxingStats);
        $average = $total / 5;
        
        $analysis = [];
        foreach ($wuxingStats as $wx => $count) {
            $percentage = ($count / $total) * 100;
            if ($count >= 3) {
                $strength = '旺';
                $desc = '过旺';
            } elseif ($count == 2) {
                $strength = '强';
                $desc = '偏强';
            } elseif ($count == 1) {
                $strength = '弱';
                $desc = '偏弱';
            } else {
                $strength = '缺';
                $desc = '缺失';
            }
            
            $analysis[$wx] = [
                'count' => $count,
                'percentage' => round($percentage, 1),
                'strength' => $strength,
                'desc' => $desc,
            ];
        }
        
        // 找出最旺和最弱的五行
        arsort($wuxingStats);
        $maxWuxing = array_key_first($wuxingStats);
        $minWuxing = array_key_last($wuxingStats);
        
        return [
            'details' => $analysis,
            'dominant' => $maxWuxing,
            'weakest' => $minWuxing,
            'balance_score' => $this->calculateBalanceScore($wuxingStats),
        ];
    }

    /**
     * 计算五行平衡度
     */
    protected function calculateBalanceScore(array $wuxingStats): int
    {
        $values = array_values($wuxingStats);
        $max = max($values);
        $min = min($values);
        $diff = $max - $min;
        
        // 差值越小越平衡
        if ($diff <= 1) return 90;
        if ($diff <= 2) return 75;
        if ($diff <= 3) return 60;
        return 45;
    }

    /**
     * 确定喜用神 - 采用“得令、得地、得势”三维度分析
     */
    protected function determineYongshen(array $bazi, array $wuxingStats): array
    {
        $dayMaster = $bazi['day']['gan'];
        $dayMasterWuxing = $bazi['day']['gan_wuxing'];
        
        $yongshen = [];
        $relations = $this->wuxingRelations[$dayMasterWuxing];
        
        // 计算日主强度得分 (总分约100)
        $score = 0;
        
        // 1. 得令分析 (月令支持)
        $monthZhi = $bazi['month']['zhi'];
        $monthCangGan = $this->zhiCharacteristics[$monthZhi]['藏干'] ?? [];
        $monthMainWuxing = $this->ganWuXing[$monthCangGan[0]] ?? ''; // 需要映射
        
        // 简单处理：如果月支藏干第一个(主气)生助日主
        if ($monthCangGan[0] == $dayMaster || in_array($dayMaster, $monthCangGan)) {
             $score += 30; // 得令
        }
        
        // 2. 得地分析 (地支根气)
        foreach (['year', 'month', 'day', 'hour'] as $p) {
            $zhi = $bazi[$p]['zhi'];
            $cangGan = $this->zhiCharacteristics[$zhi]['藏干'] ?? [];
            if (in_array($dayMaster, $cangGan)) {
                $score += 15; // 有根
            }
        }
        
        // 3. 得势分析 (天干生助)
        foreach (['year', 'month', 'hour'] as $p) {
            $gan = $bazi[$p]['gan'];
            $ganWuxing = $bazi[$p]['gan_wuxing'];
            if ($ganWuxing == $dayMasterWuxing || $ganWuxing == $relations['被生']) {
                $score += 10;
            }
        }

        // 综合判断
        $isStrong = $score >= 45;
        
        if ($isStrong) {
            $yongshen['shen'] = $relations['克'];
            $yongshen['xi'] = $relations['生'];
            $yongshen['type'] = '身强';
            $yongshen['score'] = $score;
            $yongshen['desc'] = "经过得令、得地、得势综合分析，你的得分为{$score}，属于身强格。喜用神为克我之{$relations['克']}和泄我之{$relations['生']}。";
        } else {
            $yongshen['shen'] = $relations['被生'];
            $yongshen['xi'] = $dayMasterWuxing;
            $yongshen['type'] = '身弱';
            $yongshen['score'] = $score;
            $yongshen['desc'] = "经过分析，你的命局强度得分为{$score}，属于身弱格。喜用神为生我之{$relations['被生']}和助我之{$dayMasterWuxing}。";
        }
        
        // 缺失五行补充
        foreach ($wuxingStats as $wx => $count) {
            if ($count == 0) {
                $yongshen['que'] = $wx;
                $yongshen['desc'] .= "另外，命局中缺少{$wx}，建议在日常生活中适当补充相关元素。";
                break;
            }
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
     */
    protected function generatePersonalityAnalysis(string $dayMaster, string $wuxing, array $shishenAnalysis, array $wuxingAnalysis): string
    {
        $chars = $this->ganCharacteristics[$dayMaster];
        
        $analysis = "作为{$chars['nature']}日主，你如同{$chars['symbol']}一般。";
        $analysis .= $chars['strengths'] . '。';
        
        // 结合十神
        if (isset($shishenAnalysis['dominant'])) {
            $dominantShishen = $shishenAnalysis['dominant'];
            $shishenInfo = $this->shishenMeanings[$dominantShishen] ?? null;
            if ($shishenInfo) {
                $analysis .= "命局中{$dominantShishen}较旺，说明你" . $shishenInfo['正面'] . '。';
            }
        }
        
        // 结合五行
        if ($wuxingAnalysis['balance_score'] >= 75) {
            $analysis .= '你的五行配置较为平衡，性格稳重，各方面发展都比较顺利。';
        } else {
            $dominant = $wuxingAnalysis['dominant'];
            $analysis .= "你的{$dominant}较旺，这会增强你的" . $this->getWuxingTrait($dominant) . '特质。';
        }
        
        return $analysis;
    }

    /**
     * 生成事业分析
     */
    protected function generateCareerAnalysis(string $dayMaster, string $wuxing, array $yongshen, array $shishenAnalysis): string
    {
        $chars = $this->ganCharacteristics[$dayMaster];
        
        $analysis = "基于你的{$wuxing}日主特性，" . implode('、', $chars['career']) . '等行业比较适合你。';
        
        // 结合喜用神
        if (isset($yongshen['shen'])) {
            $analysis .= "你的喜用神为{$yongshen['shen']}，从事与{$yongshen['shen']}相关的行业会有更好的发展。";
            $careerByWuxing = [
                '金' => '金融、法律、机械、汽车、珠宝',
                '木' => '教育、文化、出版、设计、环保',
                '水' => '贸易、物流、旅游、咨询、传媒',
                '火' => '能源、餐饮、美容、演艺、互联网',
                '土' => '房地产、建筑、农业、保险、管理',
            ];
            $analysis .= "如{$careerByWuxing[$yongshen['shen']]}等领域。";
        }
        
        // 结合十神
        if (isset($shishenAnalysis['dominant'])) {
            $shishen = $shishenAnalysis['dominant'];
            if (in_array($shishen, ['正官', '七杀'])) {
                $analysis .= '命局官星较旺，你有管理才能，适合走仕途或管理岗位。';
            } elseif (in_array($shishen, ['正财', '偏财'])) {
                $analysis .= '命局财星较旺，你有商业头脑，适合经商或投资理财。';
            } elseif (in_array($shishen, ['食神', '伤官'])) {
                $analysis .= '命局食伤较旺，你有才华和创意，适合技术、艺术或自由职业。';
            }
        }
        
        return $analysis;
    }

    /**
     * 生成财运分析
     */
    protected function generateWealthAnalysis(array $bazi, array $shishenAnalysis, array $yongshen): string
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
            return '你的命局财星不显，求财需要更多努力。建议通过专业技能获取稳定收入，不宜投机冒险。适合踏实工作，积少成多。';
        }
        
        $analysis = "你的命局有{$caiType}，说明你有" . ($caiType == '正财' ? '稳定的收入来源，适合通过正当途径积累财富。' : '偏财运，可能有意外之财或投资收益。');
        
        if (isset($yongshen['type'])) {
            if ($yongshen['type'] == '身强') {
                $analysis .= '身强能担财，你有能力管理和积累财富，财运较好。';
            } else {
                $analysis .= '身弱财多，容易财来财去。建议稳健理财，不宜大额投资。';
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
        
        $analysis = '';
        if ($gender == 'male') {
            $analysis = '男命以财星为妻星。';
            if ($hasSpouseStar) {
                $analysis .= '你命局妻星明朗，感情运势较为顺利，能够遇到合适的伴侣。';
            } else {
                $analysis .= '你命局妻星不显，感情上可能需要更多耐心，建议多参加社交活动。';
            }
        } else {
            $analysis = '女命以官杀为夫星。';
            if ($hasSpouseStar) {
                $analysis .= '你命局夫星明朗，容易遇到心仪的对象，感情较为顺遂。';
            } else {
                $analysis .= '你命局夫星不显，感情上可能较为被动，建议适当主动一些。';
            }
        }
        
        // 日支（配偶宫）分析
        $spousePalace = [
            '子' => '配偶聪明机灵',
            '丑' => '配偶稳重踏实',
            '寅' => '配偶积极向上',
            '卯' => '配偶温柔善良',
            '辰' => '配偶包容大度',
            '巳' => '配偶热情开朗',
            '午' => '配偶光明磊落',
            '未' => '配偶温和务实',
            '申' => '配偶机智灵活',
            '酉' => '配偶精致优雅',
            '戌' => '配偶忠诚可靠',
            '亥' => '配偶聪慧善良',
        ];
        
        if (isset($spousePalace[$dayZhi])) {
            $analysis .= "你的日支为{$dayZhi}，{$spousePalace[$dayZhi]}。";
        }
        
        return $analysis;
    }

    /**
     * 生成健康分析
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
        
        $analysis = '';
        
        // 找出过旺和过弱的五行
        foreach ($wuxingStats as $wx => $count) {
            if ($count >= 3) {
                $analysis .= "你的{$wx}较旺，注意{$organMap[$wx]}的保养，避免过度劳累。";
            } elseif ($count == 0) {
                $analysis .= "你的{$wx}偏弱，{$organMap[$wx]}方面需要多加关注。";
            }
        }
        
        if (empty($analysis)) {
            $analysis = '你的五行配置相对平衡，整体健康状况良好。保持规律作息和适度运动即可。';
        }
        
        return $analysis;
    }

    /**
     * 生成综合建议
     */
    protected function generateComprehensiveAdvice(array $yongshen, array $wuxingStats, string $dayMasterWuxing): string
    {
        $advice = '';
        
        // 喜用神建议
        if (isset($yongshen['shen'])) {
            $shen = $yongshen['shen'];
            $directions = ['金' => '西方', '木' => '东方', '水' => '北方', '火' => '南方', '土' => '中央'];
            $colors = ['金' => '白色、金色', '木' => '绿色、青色', '水' => '黑色、蓝色', '火' => '红色、紫色', '土' => '黄色、棕色'];
            $numbers = ['金' => '4、9', '木' => '3、8', '水' => '1、6', '火' => '2、7', '土' => '5、0'];
            
            $advice .= "你的喜用神是{$shen}，建议：";
            $advice .= "发展方向宜选择{$directions[$shen]}；";
            $advice .= "幸运颜色为{$colors[$shen]}；";
            $advice .= "幸运数字为{$numbers[$shen]}。";
        }
        
        // 五行调和建议
        $weakWuxing = [];
        foreach ($wuxingStats as $wx => $count) {
            if ($count == 0) {
                $weakWuxing[] = $wx;
            }
        }
        
        if (!empty($weakWuxing)) {
            $advice .= '另外，你的命局缺少' . implode('、', $weakWuxing);
            $advice .= '，可以通过佩戴相应饰品、选择对应颜色等方式适当补充。';
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
        // 移除过于专业的术语，简化表达
        $replacements = [
            '日主' => '你',
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
        
        return $text;
    }
}