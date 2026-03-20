<?php
declare(strict_types=1);

namespace app\service;

/**
 * 八字格局建议服务
 * 
 * 基于格局分析提供个性化建议
 * 包括事业、财运、感情、健康、开运等建议
 */
class BaziPatternAdviceService
{
    /**
     * 格局建议模板（增强版）
     */
    protected $patternAdvice = [
        // 八格建议
        '正官格' => [
            'career' => [
                'suitable' => '适合从事管理、行政、教育、法律、公务员等需要权威和责任感的职业。',
                'strength' => '领导能力较强，善于统筹规划，适合担任中高层管理职位。',
                'advice' => '建议追求稳定发展，建立良好的职业声誉，注重团队合作。',
                'positions' => '部门经理、校长、律师、法官、公务员、人事主管等',
                'industries' => '政府机构、教育行业、法律行业、行政管理部门',
            ],
            'wealth' => [
                'suitable' => '收入稳定，适合稳健理财。',
                'strength' => '财运平稳，不求暴利但求稳健。',
                'advice' => '建议避免高风险投资，选择低风险、长期稳定的理财产品，如国债、定期存款、指数基金等。',
                'investments' => '国债、定期存款、指数基金、蓝筹股',
                'timing' => '宜在财运平稳期进行投资，避免冲动决策。',
            ],
            'relationship' => [
                'suitable' => '重视承诺和责任，感情稳定。',
                'strength' => '感情专一，有责任感，适合建立长期稳定的关系。',
                'advice' => '建议选择成熟稳重的伴侣，建立长期稳定的关系，注重家庭经营。',
                'partner' => '年龄稍长或性格稳重的异性',
                'timing' => '宜在感情稳定期结婚，晚婚更有利于家庭稳定。',
            ],
            'health' => [
                'suitable' => '身体状况总体良好，但需注意特定方面。',
                'advice' => '注意肝脏健康，避免过度劳累，保持规律作息。',
                'details' => [
                    'organ' => '肝',
                    'attention' => '长期压力可能导致肝气郁结，需注意情绪管理',
                    'prevention' => '适当运动，保持良好作息，避免熬夜',
                ],
            ],
            'lucky' => [
                'direction' => '东方、南方',
                'colors' => '青色、红色',
                'accessories' => '玉石饰品',
                'numbers' => '3、4、9',
                'timing' => '春季、夏季',
            ],
        ],
        '七杀格' => [
            'career' => [
                'suitable' => '适合从事竞争性强、需要魄力和决断力的职业。',
                'strength' => '性格刚毅，有魄力，敢于冒险，适合充满挑战的工作。',
                'advice' => '建议发挥领导才能，勇于担当，但要注意控制冲动情绪。',
                'positions' => '军人、警察、企业家、运动员、创业者、销售经理',
                'industries' => '军警系统、体育行业、创业、销售管理',
            ],
            'wealth' => [
                'suitable' => '有偏财运，但波动较大。',
                'strength' => '敢冒险，有赚大钱的潜力，但也有亏本的风险。',
                'advice' => '建议谨慎投资，分散风险，避免孤注一掷，设置止损点。',
                'investments' => '股票、期货、创业投资、高风险基金',
                'timing' => '宜在运势旺盛期投资，避免低谷期重仓。',
            ],
            'relationship' => [
                'suitable' => '性格直率，容易冲动。',
                'strength' => '感情热烈，但有时过于强势，需要学会控制。',
                'advice' => '建议学会控制情绪，选择能够包容你性格的伴侣。',
                'partner' => '性格温和、包容性强的异性',
                'timing' => '宜在情绪稳定期进入感情，避免冲动决定。',
            ],
            'health' => [
                'suitable' => '精力旺盛，但需注意情绪和压力管理。',
                'advice' => '注意心脏健康，避免过度紧张和压力，学会放松。',
                'details' => [
                    'organ' => '心',
                    'attention' => '长期高压工作可能导致心脏问题',
                    'prevention' => '学会放松，保持心态平和，适度运动',
                ],
            ],
            'lucky' => [
                'direction' => '北方、西方',
                'colors' => '黑色、白色',
                'accessories' => '金属饰品',
                'numbers' => '1、6、7',
                'timing' => '冬季、秋季',
            ],
        ],
        '食神制杀格' => [
            'career' => [
                'suitable' => '有大将之才，适合担任重要领导职位或创业。',
                'strength' => '智慧与魄力并存，有领导才能，善于谋划。',
                'advice' => '建议发挥领导才能，勇于担当，但要注意运用智慧。',
                'positions' => '公司CEO、将军、创业领袖、高级经理',
                'industries' => '企业管理、军警系统、创业投资',
            ],
            'wealth' => [
                'suitable' => '财运亨通，既有正财也有偏财。',
                'strength' => '商业头脑好，善于把握机会，财运旺盛。',
                'advice' => '建议多元化投资，把握商业机会，分散风险。',
                'investments' => '股票、基金、房产、创业投资',
                'timing' => '把握时机，宜在运势上升期扩大投资。',
            ],
            'relationship' => [
                'suitable' => '刚柔并济，既有魄力又有智慧。',
                'strength' => '感情成熟，有担当，能够经营好感情。',
                'advice' => '建议寻找能够欣赏你才华的伴侣，共同成长。',
                'partner' => '有才华、有智慧的异性',
                'timing' => '宜在事业稳定期结婚，有利于家庭和谐。',
            ],
            'health' => [
                'suitable' => '精力充沛，体魄强健。',
                'advice' => '精力充沛，但要注意劳逸结合，避免过度消耗。',
                'details' => [
                    'organ' => '心肾',
                    'attention' => '过度奋斗可能导致身心透支',
                    'prevention' => '适度运动，保持良好体魄，注意休息',
                ],
            ],
            'lucky' => [
                'direction' => '南方、西方',
                'colors' => '红色、白色',
                'accessories' => '金玉饰品',
                'numbers' => '2、7、9',
                'timing' => '夏季、秋季',
            ],
        ],
        '杀印相生格' => [
            'career' => [
                'suitable' => '威猛转为文治，适合担任要职。',
                'strength' => '有谋略和智慧，能够化压力为动力。',
                'advice' => '建议发挥谋略和智慧，能够化压力为动力。',
                'positions' => '战略顾问、高级管理、决策顾问、军师',
                'industries' => '战略咨询、企业管理、政府决策',
            ],
            'wealth' => [
                'suitable' => '财运稳定，有贵人相助。',
                'strength' => '财运稳定，善于运用人脉资源。',
                'advice' => '建议稳健理财，借助人脉资源，把握机会。',
                'investments' => '房产、基金、长期投资',
                'timing' => '利用贵人相助的机会，稳健投资。',
            ],
            'relationship' => [
                'suitable' => '有魄力且智慧，善于谋略。',
                'strength' => '感情成熟，善于经营关系。',
                'advice' => '建议选择能够理解你野心的伴侣，互相支持。',
                'partner' => '有远见、有智慧的异性',
                'timing' => '宜在事业有成期结婚，共同经营家庭。',
            ],
            'health' => [
                'suitable' => '健康状况良好，但需注意精神压力。',
                'advice' => '需要注意精神压力，保持心态平和。',
                'details' => [
                    'organ' => '心脾',
                    'attention' => '长期思虑过度可能导致精神压力',
                    'prevention' => '培养兴趣爱好，缓解压力，保持心态平和',
                ],
            ],
            'lucky' => [
                'direction' => '东方、南方',
                'colors' => '青色、红色',
                'accessories' => '翡翠饰品',
                'numbers' => '3、4、9',
                'timing' => '春季、夏季',
            ],
        ],
        '正财格' => [
            'career' => [
                'suitable' => '适合从事商业、金融、销售、管理等工作。',
                'strength' => '商业头脑好，善于理财，脚踏实地。',
                'advice' => '建议发挥商业头脑，脚踏实地，稳健发展。',
                'positions' => '销售经理、财务经理、银行经理、商人',
                'industries' => '金融行业、销售行业、商业贸易',
            ],
            'wealth' => [
                'suitable' => '财运稳定，勤俭持家。',
                'strength' => '财运稳定，善于积累财富。',
                'advice' => '建议稳健理财，避免过度消费，积累财富。',
                'investments' => '定期存款、国债、房产、指数基金',
                'timing' => '宜在财运稳定期积累财富，避免冲动消费。',
            ],
            'relationship' => [
                'suitable' => '重视承诺，务实稳重。',
                'strength' => '感情务实，注重现实条件。',
                'advice' => '建议选择踏实可靠的伴侣，共同经营家庭。',
                'partner' => '务实稳重、有经济基础的异性',
                'timing' => '宜在经济条件稳定期结婚，有利于家庭生活。',
            ],
            'health' => [
                'suitable' => '健康状况良好，但需注意饮食。',
                'advice' => '注意肠胃健康，饮食要规律。',
                'details' => [
                    'organ' => '脾胃',
                    'attention' => '饮食不规律可能导致肠胃问题',
                    'prevention' => '保持健康饮食习惯，规律进餐',
                ],
            ],
            'lucky' => [
                'direction' => '南方、西方',
                'colors' => '黄色、白色',
                'accessories' => '水晶饰品',
                'numbers' => '5、6、7',
                'timing' => '四季皆宜，尤以夏季、秋季为佳',
            ],
        ],
        '正印格' => [
            'career' => [
                'suitable' => '适合从事教育、文化、医疗、慈善等需要包容心的职业。',
                'strength' => '有学识和善良，适合帮助他人的工作。',
                'advice' => '建议发挥学识和善良，帮助他人。',
                'positions' => '教师、医生、慈善家、文化工作者',
                'industries' => '教育行业、医疗行业、慈善事业、文化事业',
            ],
            'wealth' => [
                'suitable' => '财运平稳，有贵人相助。',
                'strength' => '财运平稳，不求暴利。',
                'advice' => '建议稳健理财，避免追求暴利。',
                'investments' => '定期存款、国债、基金',
                'timing' => '宜在财运平稳期投资，避免风险投资。',
            ],
            'relationship' => [
                'suitable' => '心地善良，有包容心。',
                'strength' => '感情温柔，善于照顾他人。',
                'advice' => '建议选择善良温柔的伴侣，互相扶持。',
                'partner' => '善良温柔、有爱心的异性',
                'timing' => '宜在心境平和期结婚，有利于家庭和谐。',
            ],
            'health' => [
                'suitable' => '健康状况良好，但需注意情绪。',
                'advice' => '注意情绪健康，保持心态平和。',
                'details' => [
                    'organ' => '肝脾',
                    'attention' => '过度担心可能导致情绪问题',
                    'prevention' => '多接触大自然，放松心情，保持乐观',
                ],
            ],
            'lucky' => [
                'direction' => '北方、东方',
                'colors' => '黑色、青色',
                'accessories' => '玛瑙饰品',
                'numbers' => '1、3、6',
                'timing' => '冬季、春季',
            ],
        ],
        '食神格' => [
            'career' => [
                'suitable' => '适合从事艺术、设计、教育、餐饮等需要创造力的职业。',
                'strength' => '有才华和创意，善于表达。',
                'advice' => '建议发挥才华和创意，展现个人魅力。',
                'positions' => '艺术家、设计师、教师、厨师、艺人',
                'industries' => '艺术行业、设计行业、教育行业、餐饮行业',
            ],
            'wealth' => [
                'suitable' => '有才华能够转化为财富。',
                'strength' => '有天赋，能将才华转化为经济价值。',
                'advice' => '建议发挥艺术天赋，创造价值。',
                'investments' => '艺术品投资、基金、创业投资',
                'timing' => '把握才华展示机会，将才华转化为财富。',
            ],
            'relationship' => [
                'suitable' => '聪明有才华，乐观开朗。',
                'strength' => '感情浪漫，有艺术气质。',
                'advice' => '建议选择能够欣赏你才华的伴侣。',
                'partner' => '有艺术品味、欣赏才华的异性',
                'timing' => '宜在艺术创作高峰期结婚，共同追求美好生活。',
            ],
            'health' => [
                'suitable' => '健康状况良好，但需注意饮食。',
                'advice' => '注意饮食习惯，享受美食但要适度。',
                'details' => [
                    'organ' => '脾胃',
                    'attention' => '过于追求美食可能导致体重问题',
                    'prevention' => '保持良好生活方式，适度饮食，多运动',
                ],
            ],
            'lucky' => [
                'direction' => '南方、东方',
                'colors' => '红色、青色',
                'accessories' => '琥珀饰品',
                'numbers' => '2、3、9',
                'timing' => '夏季、春季',
            ],
        ],
        
        // 特殊格局建议
        '申子辰三合局' => [
            'career' => '水旺，适合从事贸易、物流、旅游、咨询、传媒等行业。建议发挥流动性和适应性。',
            'wealth' => '水能生财，财运较好。建议把握流动机会，灵活理财。',
            'relationship' => '性格灵活，善于沟通。建议选择能够理解你多变性格的伴侣。',
            'health' => '注意肾脏健康，避免过度劳累。建议多喝水，保持体内水分平衡。',
            'lucky' => '贵人多在北方、西方，适合佩戴珍珠饰品，颜色以黑色、白色为主。',
        ],
        '亥卯未三合局' => [
            'career' => '木旺，适合从事教育、文化、出版、设计、环保等行业。建议发挥创造力和进取心。',
            'wealth' => '木能生财，财运稳步上升。建议长期投资，耐心等待回报。',
            'relationship' => '性格仁慈正直，有上进心。建议选择同样有上进心的伴侣。',
            'health' => '注意肝脏健康，保持良好的作息。建议多接触绿色植物，放松心情。',
            'lucky' => '贵人多在东方、南方，适合佩戴玉石饰品，颜色以青色、红色为主。',
        ],
        '寅午戌三合局' => [
            'career' => '火旺，适合从事能源、餐饮、美容、演艺、互联网等行业。建议发挥热情和感染力。',
            'wealth' => '火能生财，财运旺盛。建议把握机会，积极进取。',
            'relationship' => '性格热情开朗，有感染力。建议选择同样热情的伴侣。',
            'health' => '注意心脏健康，避免过度激动。建议保持平和心态，适度运动。',
            'lucky' => '贵人多在南方、东方，适合佩戴红宝石饰品，颜色以红色、青色为主。',
        ],
        '巳酉丑三合局' => [
            'career' => '金旺，适合从事金融、法律、机械、汽车、珠宝等行业。建议发挥刚毅和决断力。',
            'wealth' => '金能生财，财运稳定。建议稳健理财，注重长期收益。',
            'relationship' => '性格刚毅果断，有正义感。建议选择能够理解你直爽性格的伴侣。',
            'health' => '注意肺部健康，避免吸入有害物质。建议多呼吸新鲜空气，保持肺活量。',
            'lucky' => '贵人多在西方、南方，适合佩戴黄金饰品，颜色以白色、红色为主。',
        ],
        
        // 从格建议
        '假从格' => [
            'career' => '需要顺应环境，善于适应。建议发挥适应能力，把握环境变化带来的机会。',
            'wealth' => '需要借助外力，稳健理财。建议与人合作，共享资源。',
            'relationship' => '性格随和，善于适应。建议选择性格互补的伴侣。',
            'health' => '保持平和心态，避免过度焦虑。建议学会放松，顺其自然。',
            'lucky' => '贵人多在四方，适合佩戴护身符，颜色以中性为主。',
        ],
        '真从格' => [
            'career' => '需要顺势而为，能够成就大事业。建议把握大势，勇于拼搏。',
            'wealth' => '运势极佳，但也需要谨慎。建议把握机会，但不要冒险。',
            'relationship' => '性格极端，需要理智对待感情。建议寻找能够理解你极端性格的伴侣。',
            'health' => '注意身体，避免过度消耗。建议保持健康生活方式。',
            'lucky' => '贵人多在四方，适合佩戴开光饰品，颜色以中性为主。',
        ],
    ];
    
    /**
     * 生成基于格局的个性化建议
     */
    public function generateAdvice(array $bazi): array
    {
        $pattern = $bazi['pattern'] ?? [];
        $eightPatterns = $pattern['eight_patterns'] ?? [];
        $specialPatterns = $pattern['special_patterns'] ?? [];
        $shenSha = $pattern['shen_sha'] ?? [];
        $patternLevel = $pattern['pattern_level'] ?? [];
        $yongshen = $bazi['yongshen'] ?? [];
        
        // 获取主要格局
        $mainPattern = $this->getMainPattern($eightPatterns, $specialPatterns);
        
        // 生成各维度建议
        $advice = [
            'career' => $this->generateCareerAdvice($bazi, $mainPattern, $yongshen, $shenSha),
            'wealth' => $this->generateWealthAdvice($bazi, $mainPattern, $yongshen, $shenSha),
            'relationship' => $this->generateRelationshipAdvice($bazi, $mainPattern, $shenSha),
            'health' => $this->generateHealthAdvice($bazi, $yongshen, $shenSha),
            'lucky' => $this->generateLuckyAdvice($bazi, $mainPattern, $shenSha),
            'overall' => $this->generateOverallAdvice($patternLevel, $mainPattern, $shenSha),
        ];
        
        return $advice;
    }
    
    /**
     * 获取主要格局
     */
    protected function getMainPattern(array $eightPatterns, array $specialPatterns): ?string
    {
        $allPatterns = array_merge($eightPatterns, $specialPatterns);
        if (empty($allPatterns)) {
            return null;
        }
        
        // 按等级排序，返回最高等级的格局名称
        usort($allPatterns, fn($a, $b) => ($b['level'] ?? 0) <=> ($a['level'] ?? 0));
        return $allPatterns[0]['name'] ?? null;
    }
    
    /**
     * 生成事业建议
     */
    protected function generateCareerAdvice(array $bazi, ?string $mainPattern, array $yongshen, array $shenSha): string
    {
        $advice = '';
        
        // 基于格局的建议（增强版）
        if ($mainPattern !== null && isset($this->patternAdvice[$mainPattern]['career'])) {
            $careerAdvice = $this->patternAdvice[$mainPattern]['career'];
            if (is_array($careerAdvice)) {
                // 新增结构化建议
                $advice .= $careerAdvice['suitable'] ?? '';
                $advice .= $careerAdvice['strength'] ?? '';
                $advice .= $careerAdvice['advice'] ?? '';
                
                // 添加职位方向
                if (isset($careerAdvice['positions'])) {
                    $advice .= " 推荐职位：{$careerAdvice['positions']}。";
                }
                
                // 添加行业方向
                if (isset($careerAdvice['industries'])) {
                    $advice .= " 推荐行业：{$careerAdvice['industries']}。";
                }
            } else {
                // 兼容旧版本
                $advice .= $careerAdvice . ' ';
            }
        }
        
        // 基于喜用神的建议（增强版）
        $yongshenShen = $yongshen['shen'] ?? '';
        if ($yongshenShen !== '') {
            $careerByYongshen = [
                '金' => '命局喜金，适合从事金融、法律、机械、汽车、珠宝等行业。这些行业与金的刚性、珍贵、变革特性相符，能够发挥你的潜质。',
                '木' => '命局喜木，适合从事教育、文化、出版、设计、环保等行业。这些行业与木的生长、向上、仁慈特性相符，能够发挥你的创意和领导力。',
                '水' => '命局喜水，适合从事贸易、物流、旅游、咨询、传媒等行业。这些行业与水的流动、变化、智慧特性相符，能够发挥你的灵活性和适应力。',
                '火' => '命局喜火，适合从事能源、餐饮、美容、演艺、互联网等行业。这些行业与火的热情、光明、向上特性相符，能够发挥你的热情和感染力。',
                '土' => '命局喜土，适合从事房地产、建筑、农业、保险、管理等行业。这些行业与土的稳重、承载、厚重特性相符，能够发挥你的稳重和责任心。',
            ];
            if (isset($careerByYongshen[$yongshenShen])) {
                $advice .= ' ' . $careerByYongshen[$yongshenShen];
            }
        }
        
        // 基于日主强弱的建议
        $strength = $bazi['strength'] ?? [];
        $strengthStatus = $strength['status'] ?? '';
        if ($strengthStatus === '身旺' || $strengthStatus === '中和偏旺') {
            $advice .= ' 命局身旺，适合从事需要魄力和决断力的工作，可以承担较大压力，适合创业或担任管理职位。';
        } elseif ($strengthStatus === '身弱' || $strengthStatus === '中和偏弱') {
            $advice .= ' 命局身弱，适合从事需要耐心和细致的工作，不宜过度冒险，建议稳步发展，积累实力。';
        }
        
        // 基于神煞的建议（增强版）
        $shenShaAdvice = '';
        foreach ($shenSha as $sha) {
            if ($sha['name'] === '文昌') {
                $shenShaAdvice .= '有文昌贵人，有考试运和学术运，适合学习深造，从事学术、教育、科研等工作。易取得学位和认证。';
                break;
            }
            if ($sha['name'] === '天乙贵人') {
                $shenShaAdvice .= '有天乙贵人，事业上有贵人相助，易得提拔。建议多与上司、前辈接触，把握贵人相助的机会。';
                break;
            }
            if ($sha['name'] === '驿马') {
                $shenShaAdvice .= '有驿马星，适合从事流动性强的职业，如销售、贸易、旅游等。工作变动较多，但有利于事业发展。';
                break;
            }
        }
        if ($shenShaAdvice !== '') {
            $advice .= ' ' . $shenShaAdvice;
        }
        
        return trim($advice);
    }
    
    /**
     * 生成财运建议
     */
    protected function generateWealthAdvice(array $bazi, ?string $mainPattern, array $yongshen, array $shenSha): string
    {
        $advice = '';
        
        // 基于格局的建议（增强版）
        if ($mainPattern !== null && isset($this->patternAdvice[$mainPattern]['wealth'])) {
            $wealthAdvice = $this->patternAdvice[$mainPattern]['wealth'];
            if (is_array($wealthAdvice)) {
                // 新增结构化建议
                $advice .= $wealthAdvice['suitable'] ?? '';
                $advice .= $wealthAdvice['strength'] ?? '';
                $advice .= $wealthAdvice['advice'] ?? '';
                
                // 添加投资方向
                if (isset($wealthAdvice['investments'])) {
                    $advice .= " 推荐投资：{$wealthAdvice['investments']}。";
                }
                
                // 添加时机建议
                if (isset($wealthAdvice['timing'])) {
                    $advice .= " " . $wealthAdvice['timing'];
                }
            } else {
                // 兼容旧版本
                $advice .= $wealthAdvice . ' ';
            }
        }
        
        // 基于十神的建议（增强版）
        $shishenAnalysis = $bazi['shishen_analysis'] ?? [];
        $dominantShishen = $shishenAnalysis['dominant'] ?? '';
        if ($dominantShishen === '偏财') {
            $advice .= ' 命局偏财旺，有偏财运，适合投资理财，但要谨慎。偏财代表意外之财，如投资收益、奖金等，但也伴随着风险。建议设置止损点，控制风险。';
        } elseif ($dominantShishen === '正财') {
            $advice .= ' 命局正财旺，财运稳定，适合稳健理财。正财代表固定收入，如工资、生意收益等。建议勤俭持家，积累财富，避免过度消费。';
        } elseif ($dominantShishen === '食神' || $dominantShishen === '伤官') {
            $advice .= ' 命局食伤旺，有才华能够转化为财富，适合技术、艺术或创业。你的创造力可以带来收入，建议发挥才华，将技能变现。';
        }
        
        // 基于喜用神的建议
        $yongshenShen = $yongshen['shen'] ?? '';
        if ($yongshenShen !== '') {
            $wealthByYongshen = [
                '金' => '喜金，财运来自金融、投资、金属行业。适合长期投资，如黄金、白银等贵金属，或金融产品。',
                '木' => '喜木，财运来自教育、文化、环保等行业。适合投资教育、培训、文化创意产业。',
                '水' => '喜水，财运来自贸易、物流、旅游等流动性强的行业。适合投资流动资产，如股票、基金。',
                '火' => '喜火，财运来自能源、科技、互联网等新兴产业。适合投资科技股、互联网公司。',
                '土' => '喜土，财运来自房地产、建筑、农业等传统行业。适合投资房产、土地、农业项目。',
            ];
            if (isset($wealthByYongshen[$yongshenShen])) {
                $advice .= ' ' . $wealthByYongshen[$yongshenShen];
            }
        }
        
        // 基于神煞的建议（增强版）
        $shenShaAdvice = '';
        foreach ($shenSha as $sha) {
            if ($sha['name'] === '羊刃') {
                $shenShaAdvice .= '有羊刃，财运波动较大，需要稳健理财。羊刃代表冲动和冒险，建议控制消费欲望，避免冲动投资。';
                break;
            }
            if ($sha['name'] === '天乙贵人') {
                $shenShaAdvice .= '有天乙贵人，财运有贵人相助。建议多与贵人接触，把握贵人带来的财运机会。';
                break;
            }
            if ($sha['name'] === '桃花') {
                $shenShaAdvice .= '有桃花，有人缘财运。适合从事服务业、娱乐业等行业，可以借助人缘获得收入。';
                break;
            }
        }
        if ($shenShaAdvice !== '') {
            $advice .= ' ' . $shenShaAdvice;
        }
        
        return trim($advice);
    }
    
    /**
     * 生成感情建议
     */
    protected function generateRelationshipAdvice(array $bazi, ?string $mainPattern, array $shenSha): string
    {
        $advice = '';
        
        // 基于格局的建议（增强版）
        if ($mainPattern !== null && isset($this->patternAdvice[$mainPattern]['relationship'])) {
            $relationshipAdvice = $this->patternAdvice[$mainPattern]['relationship'];
            if (is_array($relationshipAdvice)) {
                // 新增结构化建议
                $advice .= $relationshipAdvice['suitable'] ?? '';
                $advice .= $relationshipAdvice['strength'] ?? '';
                $advice .= $relationshipAdvice['advice'] ?? '';
                
                // 添加伴侣特征
                if (isset($relationshipAdvice['partner'])) {
                    $advice .= " 推荐伴侣特征：{$relationshipAdvice['partner']}。";
                }
                
                // 添加时机建议
                if (isset($relationshipAdvice['timing'])) {
                    $advice .= " " . $relationshipAdvice['timing'];
                }
            } else {
                // 兼容旧版本
                $advice .= $relationshipAdvice . ' ';
            }
        }
        
        // 基于神煞的建议（增强版）
        $shenShaAdvice = '';
        $hasTaohua = false;
        $hasGuchen = false;
        foreach ($shenSha as $sha) {
            if ($sha['name'] === '桃花' || $sha['name'] === '红艳') {
                $hasTaohua = true;
                $shenShaAdvice .= '有桃花/红艳，异性缘好，感情运势佳。桃花星代表魅力和吸引力，容易吸引异性。但也要理智对待感情，避免花心。桃花运旺时，可以积极追求心仪的对象。';
                break;
            }
            if ($sha['name'] === '孤辰' || $sha['name'] === '寡宿') {
                $hasGuchen = true;
                $shenShaAdvice .= '有孤辰/寡宿，感情生活可能较为平淡。孤辰寡宿代表孤独和寂寞，需要主动经营感情。建议多参加社交活动，扩大社交圈，主动寻找合适的伴侣。';
                break;
            }
            if ($sha['name'] === '羊刃') {
                $shenShaAdvice .= '有羊刃，感情强烈但可能冲动。羊刃代表强烈的情感，但容易冲动。建议学会控制情绪，在感情中保持理智，避免因冲动而伤害对方。';
                break;
            }
        }
        if ($shenShaAdvice !== '') {
            $advice .= ' ' . $shenShaAdvice;
        }
        
        // 基于日主的建议（增强版）
        $dayMaster = $bazi['day']['gan'] ?? '';
        $dayMasterAdvice = '';
        if ($dayMaster === '甲' || $dayMaster === '乙') {
            $dayMasterAdvice .= '木性人，感情专一，但有时过于固执。木日主的人仁慈正直，对待感情认真专一，但有时会过于固执己见，不善于变通。建议在感情中学会妥协和包容，不要过于坚持己见。';
        } elseif ($dayMaster === '丙' || $dayMaster === '丁') {
            $dayMasterAdvice .= '火性人，热情浪漫，但有时过于冲动。火日主的人热情开朗，感情丰富，但有时会过于冲动，容易做出不理智的决定。建议在感情中保持冷静，不要被感情冲昏头脑。';
        } elseif ($dayMaster === '戊' || $dayMaster === '己') {
            $dayMasterAdvice .= '土性人，稳重踏实，但有时过于保守。土日主的人稳重踏实，对待感情认真，但有时会过于保守，不善表达。建议在感情中主动表达感情，不要过于内敛。';
        } elseif ($dayMaster === '庚' || $dayMaster === '辛') {
            $dayMasterAdvice .= '金性人，刚毅果断，但有时过于冷酷。金日主的人刚毅果断，有原则性，但有时会过于冷酷，不够温柔。建议在感情中多表达温柔的一面，不要总是冷冰冰的。';
        } elseif ($dayMaster === '壬' || $dayMaster === '癸') {
            $dayMasterAdvice .= '水性人，聪明灵活，但有时过于善变。水日主的人聪明灵活，适应力强，但有时会过于善变，不够专一。建议在感情中保持专一，不要见异思迁。';
        }
        if ($dayMasterAdvice !== '') {
            $advice .= ' ' . $dayMasterAdvice;
        }
        
        // 基于日主强弱的建议
        $strength = $bazi['strength'] ?? [];
        $strengthStatus = $strength['status'] ?? '';
        if ($strengthStatus === '身旺' || $strengthStatus === '中和偏旺') {
            $advice .= ' 命局身旺，在感情中可能比较强势，建议学会包容和体谅。';
        } elseif ($strengthStatus === '身弱' || $strengthStatus === '中和偏弱') {
            $advice .= ' 命局身弱，在感情中可能比较被动，建议主动表达自己的感情和需求。';
        }
        
        return trim($advice);
    }
    
    /**
     * 生成健康建议
     */
    protected function generateHealthAdvice(array $bazi, array $yongshen, array $shenSha): string
    {
        $advice = '';
        
        // 基于格局的建议（增强版）
        $mainPattern = $this->getMainPattern($bazi['pattern']['eight_patterns'] ?? [], $bazi['pattern']['special_patterns'] ?? []);
        if ($mainPattern !== null && isset($this->patternAdvice[$mainPattern]['health'])) {
            $healthAdvice = $this->patternAdvice[$mainPattern]['health'];
            if (is_array($healthAdvice)) {
                // 新增结构化建议
                $advice .= $healthAdvice['suitable'] ?? '';
                $advice .= $healthAdvice['advice'] ?? '';
                
                // 添加详细信息
                if (isset($healthAdvice['details']) && is_array($healthAdvice['details'])) {
                    $details = $healthAdvice['details'];
                    if (isset($details['organ'])) {
                        $advice .= " 注意部位：{$details['organ']}。";
                    }
                    if (isset($details['attention'])) {
                        $advice .= " " . $details['attention'];
                    }
                    if (isset($details['prevention'])) {
                        $advice .= " " . $details['prevention'];
                    }
                }
            } else {
                // 兼容旧版本
                $advice .= $healthAdvice . ' ';
            }
        }
        
        // 基于五行的建议（增强版）
        $wuxingStats = $bazi['wuxing_stats'] ?? [];
        $dominantWuxing = '';
        $maxValue = 0;
        foreach ($wuxingStats as $wx => $value) {
            if ($value > $maxValue) {
                $maxValue = $value;
                $dominantWuxing = $wx;
            }
        }
        
        if ($dominantWuxing !== '') {
            $healthByWuxing = [
                '金' => '金旺，注意肺部健康，避免吸入有害物质。金代表肺和呼吸系统，金旺可能导致肺气过盛或肺气不足。建议多呼吸新鲜空气，保持室内通风，避免吸烟和二手烟。可以多做一些有氧运动，如跑步、游泳等，增强肺功能。',
                '木' => '木旺，注意肝脏健康，保持良好的作息。木代表肝脏和胆，木旺可能导致肝气过盛或肝气郁结。建议保持良好的作息习惯，避免熬夜和过度劳累。可以多吃一些养肝的食物，如绿豆、芹菜、菠菜等。保持心情舒畅，避免情绪波动过大。',
                '水' => '水旺，注意肾脏健康，避免过度劳累。水代表肾脏和膀胱，水旺可能导致肾气过旺或肾气不足。建议避免过度劳累，保证充足的睡眠。可以多吃一些补肾的食物，如黑豆、黑芝麻、核桃等。避免久坐，适当运动，促进血液循环。',
                '火' => '火旺，注意心脏健康，避免过度激动。火代表心脏和小肠，火旺可能导致心火过旺或心血不足。建议保持平和的心态，避免过度激动和情绪波动。可以多吃一些养心的食物，如红枣、桂圆、莲子等。适当运动，但避免过度劳累。',
                '土' => '土旺，注意肠胃健康，饮食要规律。土代表脾胃，土旺可能导致脾胃虚弱或脾胃湿热。建议保持规律的饮食习惯，避免暴饮暴食和过度饮酒。可以多吃一些健脾养胃的食物，如山药、小米、南瓜等。避免过度思虑，保持心情愉快。',
            ];
            if (isset($healthByWuxing[$dominantWuxing])) {
                $advice .= ' ' . $healthByWuxing[$dominantWuxing];
            }
        }
        
        // 基于喜用神的建议
        $yongshenShen = $yongshen['shen'] ?? '';
        if ($yongshenShen !== '') {
            $healthByYongshen = [
                '金' => '喜金，适合做一些呼吸系统锻炼，如慢跑、游泳等。',
                '木' => '喜木，适合做一些伸展运动，如瑜伽、太极等。',
                '水' => '喜水，适合做一些有氧运动，如游泳、跑步等。',
                '火' => '喜火，适合做一些力量训练，如举重、俯卧撑等。',
                '土' => '喜土，适合做一些稳定性训练，如太极、气功等。',
            ];
            if (isset($healthByYongshen[$yongshenShen])) {
                $advice .= ' ' . $healthByYongshen[$yongshenShen];
            }
        }
        
        // 基于神煞的建议（增强版）
        $shenShaAdvice = '';
        foreach ($shenSha as $sha) {
            if ($sha['name'] === '亡神') {
                $shenShaAdvice .= '有亡神，需要特别注意安全，做事要谨慎。亡神星代表意外和危险，建议出行要小心，避免危险的活动和工作。';
                break;
            }
            if ($sha['name'] === '流霞') {
                $shenShaAdvice .= '有流霞，需要注意交通安全，出行要谨慎。流霞星代表血光之灾，建议出行要注意交通安全，避免酒后驾车。';
                break;
            }
            if ($sha['name'] === '羊刃') {
                $shenShaAdvice .= '有羊刃，需要注意情绪管理，避免过度紧张。羊刃星代表冲动和暴力，建议学会控制情绪，避免因情绪波动过大而影响健康。';
                break;
            }
            if ($sha['name'] === '十恶大败') {
                $shenShaAdvice .= '有十恶大败，身体可能较为虚弱，需要加强锻炼，增强体质。建议保持良好的生活习惯，避免熬夜和过度劳累。';
                break;
            }
        }
        if ($shenShaAdvice !== '') {
            $advice .= ' ' . $shenShaAdvice;
        }
        
        return trim($advice);
    }
    
    /**
     * 生成开运建议
     */
    protected function generateLuckyAdvice(array $bazi, ?string $mainPattern, array $shenSha): string
    {
        $advice = '';
        
        // 基于格局的建议（增强版）
        if ($mainPattern !== null && isset($this->patternAdvice[$mainPattern]['lucky'])) {
            $luckyAdvice = $this->patternAdvice[$mainPattern]['lucky'];
            if (is_array($luckyAdvice)) {
                // 新增结构化建议
                if (isset($luckyAdvice['direction'])) {
                    $advice .= "贵人方位：{$luckyAdvice['direction']}。";
                }
                if (isset($luckyAdvice['colors'])) {
                    $advice .= " 幸运颜色：{$luckyAdvice['colors']}。";
                }
                if (isset($luckyAdvice['accessories'])) {
                    $advice .= " 幸运饰品：{$luckyAdvice['accessories']}。";
                }
                if (isset($luckyAdvice['numbers'])) {
                    $advice .= " 幸运数字：{$luckyAdvice['numbers']}。";
                }
                if (isset($luckyAdvice['timing'])) {
                    $advice .= " 幸运时节：{$luckyAdvice['timing']}。";
                }
            } else {
                // 兼容旧版本
                $advice .= $luckyAdvice . ' ';
            }
        }
        
        // 基于喜用神的建议（增强版）
        $yongshenShen = $bazi['yongshen']['shen'] ?? '';
        if ($yongshenShen !== '') {
            $luckyByYongshen = [
                '金' => '喜金，贵人多在西方，适合穿白色、金色衣服，佩戴金属饰品（如金、银首饰）。办公环境可以摆放金属装饰品，如铜制摆件、金箔装饰等。出行时向西方发展更有利于运势。',
                '木' => '喜木，贵人多在东方，适合穿青色、绿色衣服，佩戴木制饰品（如紫檀、黄花梨手串）。办公环境可以摆放植物，如绿萝、发财树等。出行时向东方发展更有利于运势。',
                '水' => '喜水，贵人多在北方，适合穿黑色、蓝色衣服，佩戴水晶饰品（如黑曜石、白水晶）。办公环境可以摆放水景，如鱼缸、水族箱等。出行时向北方发展更有利于运势。',
                '火' => '喜火，贵人多在南方，适合穿红色、紫色衣服，佩戴红宝石饰品（如红宝石、紫水晶）。办公环境可以摆放红色装饰品，如红色靠垫、红色画作等。出行时向南方发展更有利于运势。',
                '土' => '喜土，贵人多在中央，适合穿黄色、棕色衣服，佩戴玉石饰品（如翡翠、和田玉）。办公环境可以摆放陶瓷、玉石装饰品。出行时向中央或东南方发展更有利于运势。',
            ];
            if (isset($luckyByYongshen[$yongshenShen])) {
                $advice .= ' ' . $luckyByYongshen[$yongshenShen];
            }
        }
        
        // 基于神煞的建议（增强版）
        $shenShaAdvice = '';
        foreach ($shenSha as $sha) {
            if ($sha['name'] === '天乙贵人') {
                $shenShaAdvice .= '有天乙贵人，多与贵人接触，把握贵人相助的机会。天乙贵人是最大的吉神，遇到贵人时要把握机会，虚心求教，贵人会在关键时刻帮助你。';
                break;
            }
            if ($sha['name'] === '文昌') {
                $shenShaAdvice .= '有文昌贵人，多读书学习，提升自己的能力。文昌贵人代表学业和考试，适合考试、认证、学习新技能。可以多读书、参加培训、考取证书。';
                break;
            }
            if ($sha['name'] === '驿马') {
                $shenShaAdvice .= '有驿马星，适合外出发展，把握流动的机会。驿马星代表变动和外出，适合出差、旅游、搬迁。抓住外出的机会，能够带来好运。';
                break;
            }
            if ($sha['name'] === '桃花') {
                $shenShaAdvice .= '有桃花星，人缘好，可以利用人脉资源。桃花星代表人缘和魅力，可以扩大社交圈，多参加社交活动，结识新朋友。';
                break;
            }
        }
        if ($shenShaAdvice !== '') {
            $advice .= ' ' . $shenShaAdvice;
        }
        
        // 基于日主的建议
        $dayMaster = $bazi['day']['gan'] ?? '';
        $dayMasterAdvice = '';
        if ($dayMaster === '甲' || $dayMaster === '乙') {
            $dayMasterAdvice .= '木日主，适合在春季、东方发展，多接触绿色植物。';
        } elseif ($dayMaster === '丙' || $dayMaster === '丁') {
            $dayMasterAdvice .= '火日主，适合在夏季、南方发展，多接触阳光和红色物品。';
        } elseif ($dayMaster === '戊' || $dayMaster === '己') {
            $dayMasterAdvice .= '土日主，适合在四季末、中央发展，多接触黄色和稳定的环境。';
        } elseif ($dayMaster === '庚' || $dayMaster === '辛') {
            $dayMasterAdvice .= '金日主，适合在秋季、西方发展，多接触白色和金属物品。';
        } elseif ($dayMaster === '壬' || $dayMaster === '癸') {
            $dayMasterAdvice .= '水日主，适合在冬季、北方发展，多接触黑色和水。';
        }
        if ($dayMasterAdvice !== '') {
            $advice .= ' ' . $dayMasterAdvice;
        }
        
        return trim($advice);
    }
    
    /**
     * 生成综合建议
     */
    protected function generateOverallAdvice(array $patternLevel, ?string $mainPattern, array $shenSha): string
    {
        $level = $patternLevel['level'] ?? '';
        $description = $patternLevel['description'] ?? '';
        
        $advice = "你的格局层次为【{$level}】，{$description} ";
        
        // 综合建议（增强版）
        if ($level === '上上') {
            $advice .= '这是上上等的格局，命运极佳，有成就大业的潜力。建议你积极进取，把握机会，成就一番事业。但要保持谦虚，避免骄傲自满。要善于把握时机，在运势旺盛时大展宏图，在运势低谷时韬光养晦。';
        } elseif ($level === '上') {
            $advice .= '这是上等的格局，命运优秀，有成就一番事业的潜力。建议你积极进取，把握机会，努力奋斗。要注意保持谦虚，避免骄傲自满。要善于利用自己的优势，在合适的时机出手。';
        } elseif ($level === '中上') {
            $advice .= '这是中上等的格局，命运良好，有有所成就的潜力。建议你努力奋斗，稳步发展，能够有所成就。要注意把握机会，不要错失良机。要脚踏实地，一步一个脚印，逐步实现自己的目标。';
        } elseif ($level === '中') {
            $advice .= '这是中等的格局，命运一般，需要努力才能有所成就。建议你脚踏实地，勤恳努力，逐步实现目标。要善于学习，提升自己的能力，等待时机。不要急于求成，要循序渐进，稳扎稳打。';
        } else {
            $advice .= '这是下等的格局，命运欠佳，需要更加努力。建议你修身养性，积累实力，循序渐进。保持乐观心态，相信自己，不要气馁。要善于发现自己的优势和机会，把握每一个可能改变命运的机会。';
        }
        
        // 基于神煞的建议（增强版）
        $goodShenShaCount = 0;
        $badShenShaCount = 0;
        $goodShenShaNames = [];
        $badShenShaNames = [];
        foreach ($shenSha as $sha) {
            if (($sha['quality'] ?? '') === '吉') {
                $goodShenShaCount++;
                $goodShenShaNames[] = $sha['name'];
            } elseif (($sha['quality'] ?? '') === '凶') {
                $badShenShaCount++;
                $badShenShaNames[] = $sha['name'];
            }
        }
        
        if ($goodShenShaCount > 0) {
            $advice .= " 命中有{$goodShenShaCount}个吉神（" . implode('、', $goodShenShaNames) . "），能够得到贵人相助。吉神能够带来好运和机遇，要善于利用吉神的能量，在吉神当值的年份积极进取。";
        }
        
        if ($badShenShaCount > 0) {
            $advice .= " 命中有{$badShenShaCount}个凶煞（" . implode('、', $badShenShaNames) . "），需要特别注意防范。凶煞可能带来麻烦和困难，要注意规避风险，在凶煞当值的年份谨慎行事，避免做出重大决策。";
        }
        
        // 基于格局的建议
        if ($mainPattern !== null) {
            $patternAdvice = [
                '正官格' => '正官格代表官职和地位，仕途有望。建议在事业上追求稳定发展，建立良好的人际关系网络。',
                '七杀格' => '七杀格代表威严和权势，有领导才能。建议发挥自己的魄力和决断力，在竞争激烈的领域中脱颖而出。',
                '食神制杀格' => '食神制杀格代表智慧与力量并存，有领导才能。建议发挥自己的智慧和谋略，在事业上取得成就。',
                '杀印相生格' => '杀印相生格代表威猛转为文治，有谋略和智慧。建议发挥自己的智慧，化压力为动力，在事业上稳步前进。',
                '正财格' => '正财格代表财富稳定，善于理财。建议发挥自己的商业头脑，稳健理财，积累财富。',
                '正印格' => '正印格代表学识和善良，适合从事帮助他人的工作。建议发挥自己的学识和善良，在事业上取得成就。',
                '食神格' => '食神格代表才华和创意，善于表达。建议发挥自己的才华和创意，在艺术、设计等领域取得成就。',
            ];
            if (isset($patternAdvice[$mainPattern])) {
                $advice .= ' ' . $patternAdvice[$mainPattern];
            }
        }
        
        // 时机建议
        $advice .= ' 要善于把握时机，在运势旺盛时积极进取，在运势低谷时韬光养晦。建议每年年初关注当年的运势走向，提前做好准备。在重要的决策时刻，要综合考虑各种因素，不要冲动行事。';
        
        return trim($advice);
    }
    
    /**
     * 生成基于大运流年的时机建议
     */
    protected function generateTimingAdvice(array $bazi): string
    {
        $advice = '';
        
        // 获取大运和流年信息
        $dayunPatterns = $bazi['dayun_patterns'] ?? [];
        $liunianPatterns = $bazi['liunian_patterns'] ?? [];
        
        if (empty($dayunPatterns) && empty($liunianPatterns)) {
            return '暂无大运流年信息，无法提供时机建议。';
        }
        
        $advice .= '根据大运流年分析：';
        
        // 分析当前大运
        if (!empty($dayunPatterns)) {
            $currentDayun = $dayunPatterns[0] ?? [];
            if (!empty($currentDayun)) {
                $dayunStatus = $currentDayun['status'] ?? '';
                $dayunScore = $currentDayun['score'] ?? 0;
                
                if ($dayunStatus === '大吉' || $dayunScore >= 80) {
                    $advice .= ' 当前大运运势极佳，是把握时机、大展宏图的好时机。建议积极进取，把握机会，成就一番事业。';
                } elseif ($dayunStatus === '吉' || $dayunScore >= 60) {
                    $advice .= ' 当前大运运势良好，是稳步发展、有所成就的好时机。建议努力奋斗，把握机会，实现自己的目标。';
                } elseif ($dayunStatus === '平' || $dayunScore >= 40) {
                    $advice .= ' 当前大运运势平稳，适合韬光养晦、积累实力。建议脚踏实地，提升自己的能力，等待时机。';
                } else {
                    $advice .= ' 当前大运运势不佳，需要谨慎行事、规避风险。建议保持低调，避免冒险，等待运势好转。';
                }
            }
        }
        
        // 分析当前流年
        if (!empty($liunianPatterns)) {
            $currentLiunian = $liunianPatterns[0] ?? [];
            if (!empty($currentLiunian)) {
                $liunianStatus = $currentLiunian['status'] ?? '';
                $liunianScore = $currentLiunian['score'] ?? 0;
                
                if ($liunianStatus === '大吉' || $liunianScore >= 80) {
                    $advice .= ' 当前流年运势极佳，是把握时机、大展宏图的好时机。建议积极进取，把握机会，成就一番事业。';
                } elseif ($liunianStatus === '吉' || $liunianScore >= 60) {
                    $advice .= ' 当前流年运势良好，是稳步发展、有所成就的好时机。建议努力奋斗，把握机会，实现自己的目标。';
                } elseif ($liunianStatus === '平' || $liunianScore >= 40) {
                    $advice .= ' 当前流年运势平稳，适合韬光养晦、积累实力。建议脚踏实地，提升自己的能力，等待时机。';
                } else {
                    $advice .= ' 当前流年运势不佳，需要谨慎行事、规避风险。建议保持低调，避免冒险，等待运势好转。';
                }
            }
        }
        
        return trim($advice);
    }
}
