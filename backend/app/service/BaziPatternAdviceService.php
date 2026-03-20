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
     * 格局建议模板
     */
    protected $patternAdvice = [
        // 八格建议
        '正官格' => [
            'career' => '适合从事管理、行政、教育、法律等需要权威和责任感的职业。建议追求稳定发展，建立良好的职业声誉。',
            'wealth' => '收入稳定，适合稳健理财。建议避免高风险投资，选择低风险、长期稳定的理财产品。',
            'relationship' => '重视承诺和责任，感情稳定。建议选择成熟稳重的伴侣，建立长期稳定的关系。',
            'health' => '注意肝脏健康，避免过度劳累。建议保持规律作息，适当运动。',
            'lucky' => '贵人多在东方、南方，适合佩戴玉石饰品，颜色以青色、红色为主。',
        ],
        '七杀格' => [
            'career' => '适合从事竞争性强、需要魄力和决断力的职业，如军人、警察、企业家、运动员等。',
            'wealth' => '有偏财运，但波动较大。建议谨慎投资，分散风险，避免孤注一掷。',
            'relationship' => '性格直率，容易冲动。建议学会控制情绪，选择能够包容你性格的伴侣。',
            'health' => '注意心脏健康，避免过度紧张和压力。建议学会放松，保持心态平和。',
            'lucky' => '贵人多在北方、西方，适合佩戴金属饰品，颜色以黑色、白色为主。',
        ],
        '食神制杀格' => [
            'career' => '有大将之才，适合担任重要领导职位或创业。建议发挥领导才能，勇于担当。',
            'wealth' => '财运亨通，既有正财也有偏财。建议多元化投资，把握商业机会。',
            'relationship' => '刚柔并济，既有魄力又有智慧。建议寻找能够欣赏你才华的伴侣。',
            'health' => '精力充沛，但要注意劳逸结合。建议适度运动，保持良好体魄。',
            'lucky' => '贵人多在南方、西方，适合佩戴金玉饰品，颜色以红色、白色为主。',
        ],
        '杀印相生格' => [
            'career' => '威猛转为文治，适合担任要职。建议发挥谋略和智慧，能够化压力为动力。',
            'wealth' => '财运稳定，有贵人相助。建议稳健理财，借助人脉资源。',
            'relationship' => '有魄力且智慧，善于谋略。建议选择能够理解你野心的伴侣。',
            'health' => '需要注意精神压力，保持心态平和。建议培养兴趣爱好，缓解压力。',
            'lucky' => '贵人多在东方、南方，适合佩戴翡翠饰品，颜色以青色、红色为主。',
        ],
        '正财格' => [
            'career' => '适合从事商业、金融、销售、管理等工作。建议发挥商业头脑，脚踏实地。',
            'wealth' => '财运稳定，勤俭持家。建议稳健理财，避免过度消费，积累财富。',
            'relationship' => '重视承诺，务实稳重。建议选择踏实可靠的伴侣，共同经营家庭。',
            'health' => '注意肠胃健康，饮食要规律。建议保持健康饮食习惯。',
            'lucky' => '贵人多在南方、西方，适合佩戴水晶饰品，颜色以黄色、白色为主。',
        ],
        '正印格' => [
            'career' => '适合从事教育、文化、医疗、慈善等需要包容心的职业。建议发挥学识和善良。',
            'wealth' => '财运平稳，有贵人相助。建议稳健理财，避免追求暴利。',
            'relationship' => '心地善良，有包容心。建议选择善良温柔的伴侣，互相扶持。',
            'health' => '注意情绪健康，保持心态平和。建议多接触大自然，放松心情。',
            'lucky' => '贵人多在北方、东方，适合佩戴玛瑙饰品，颜色以黑色、青色为主。',
        ],
        '食神格' => [
            'career' => '适合从事艺术、设计、教育、餐饮等需要创造力的职业。建议发挥才华和创意。',
            'wealth' => '有才华能够转化为财富。建议发挥艺术天赋，创造价值。',
            'relationship' => '聪明有才华，乐观开朗。建议选择能够欣赏你才华的伴侣。',
            'health' => '注意饮食习惯，享受美食但要适度。建议保持良好生活方式。',
            'lucky' => '贵人多在南方、东方，适合佩戴琥珀饰品，颜色以红色、青色为主。',
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
        
        // 基于格局的建议
        if ($mainPattern !== null && isset($this->patternAdvice[$mainPattern]['career'])) {
            $advice .= $this->patternAdvice[$mainPattern]['career'] . ' ';
        }
        
        // 基于喜用神的建议
        $yongshenShen = $yongshen['shen'] ?? '';
        if ($yongshenShen !== '') {
            $careerByYongshen = [
                '金' => '命局喜金，适合从事金融、法律、机械、汽车、珠宝等行业。',
                '木' => '命局喜木，适合从事教育、文化、出版、设计、环保等行业。',
                '水' => '命局喜水，适合从事贸易、物流、旅游、咨询、传媒等行业。',
                '火' => '命局喜火，适合从事能源、餐饮、美容、演艺、互联网等行业。',
                '土' => '命局喜土，适合从事房地产、建筑、农业、保险、管理等行业。',
            ];
            if (isset($careerByYongshen[$yongshenShen])) {
                $advice .= $careerByYongshen[$yongshenShen] . ' ';
            }
        }
        
        // 基于神煞的建议
        $hasWenchang = false;
        $hasTianyi = false;
        foreach ($shenSha as $sha) {
            if ($sha['name'] === '文昌') {
                $hasWenchang = true;
                $advice .= '有文昌贵人，有考试运，适合学习深造。';
                break;
            }
            if ($sha['name'] === '天乙贵人') {
                $hasTianyi = true;
                $advice .= '有天乙贵人，事业上有贵人相助，易得提拔。';
                break;
            }
        }
        
        return $advice;
    }
    
    /**
     * 生成财运建议
     */
    protected function generateWealthAdvice(array $bazi, ?string $mainPattern, array $yongshen, array $shenSha): string
    {
        $advice = '';
        
        // 基于格局的建议
        if ($mainPattern !== null && isset($this->patternAdvice[$mainPattern]['wealth'])) {
            $advice .= $this->patternAdvice[$mainPattern]['wealth'] . ' ';
        }
        
        // 基于十神的建议
        $shishenAnalysis = $bazi['shishen_analysis'] ?? [];
        $dominantShishen = $shishenAnalysis['dominant'] ?? '';
        if ($dominantShishen === '偏财') {
            $advice .= '命局偏财旺，有偏财运，适合投资理财，但要谨慎。';
        } elseif ($dominantShishen === '正财') {
            $advice .= '命局正财旺，财运稳定，适合稳健理财。';
        } elseif ($dominantShishen === '食神' || $dominantShishen === '伤官') {
            $advice .= '命局食伤旺，有才华能够转化为财富，适合技术、艺术或创业。';
        }
        
        // 基于神煞的建议
        $hasYangren = false;
        foreach ($shenSha as $sha) {
            if ($sha['name'] === '羊刃') {
                $hasYangren = true;
                $advice .= '有羊刃，财运波动较大，需要稳健理财。';
                break;
            }
            if ($sha['name'] === '天乙贵人') {
                $advice .= '有天乙贵人，财运有贵人相助。';
                break;
            }
        }
        
        return $advice;
    }
    
    /**
     * 生成感情建议
     */
    protected function generateRelationshipAdvice(array $bazi, ?string $mainPattern, array $shenSha): string
    {
        $advice = '';
        
        // 基于格局的建议
        if ($mainPattern !== null && isset($this->patternAdvice[$mainPattern]['relationship'])) {
            $advice .= $this->patternAdvice[$mainPattern]['relationship'] . ' ';
        }
        
        // 基于神煞的建议
        $hasTaohua = false;
        $hasGuchen = false;
        foreach ($shenSha as $sha) {
            if ($sha['name'] === '桃花' || $sha['name'] === '红艳') {
                $hasTaohua = true;
                $advice .= '有桃花/红艳，异性缘好，感情运势佳，但也要理智对待感情。';
                break;
            }
            if ($sha['name'] === '孤辰' || $sha['name'] === '寡宿') {
                $hasGuchen = true;
                $advice .= '有孤辰/寡宿，感情生活可能较为平淡，需要主动经营感情。';
                break;
            }
        }
        
        // 基于日主的建议
        $dayMaster = $bazi['day']['gan'] ?? '';
        if ($dayMaster === '甲' || $dayMaster === '乙') {
            $advice .= '木性人，感情专一，但有时过于固执。';
        } elseif ($dayMaster === '丙' || $dayMaster === '丁') {
            $advice .= '火性人，热情浪漫，但有时过于冲动。';
        } elseif ($dayMaster === '戊' || $dayMaster === '己') {
            $advice .= '土性人，稳重踏实，但有时过于保守。';
        } elseif ($dayMaster === '庚' || $dayMaster === '辛') {
            $advice .= '金性人，刚毅果断，但有时过于冷酷。';
        } elseif ($dayMaster === '壬' || $dayMaster === '癸') {
            $advice .= '水性人，聪明灵活，但有时过于善变。';
        }
        
        return $advice;
    }
    
    /**
     * 生成健康建议
     */
    protected function generateHealthAdvice(array $bazi, array $yongshen, array $shenSha): string
    {
        $advice = '';
        
        // 基于格局的建议
        $mainPattern = $this->getMainPattern($bazi['pattern']['eight_patterns'] ?? [], $bazi['pattern']['special_patterns'] ?? []);
        if ($mainPattern !== null && isset($this->patternAdvice[$mainPattern]['health'])) {
            $advice .= $this->patternAdvice[$mainPattern]['health'] . ' ';
        }
        
        // 基于五行的建议
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
                '金' => '金旺，注意肺部健康，避免吸入有害物质。',
                '木' => '木旺，注意肝脏健康，保持良好的作息。',
                '水' => '水旺，注意肾脏健康，避免过度劳累。',
                '火' => '火旺，注意心脏健康，避免过度激动。',
                '土' => '土旺，注意肠胃健康，饮食要规律。',
            ];
            if (isset($healthByWuxing[$dominantWuxing])) {
                $advice .= $healthByWuxing[$dominantWuxing] . ' ';
            }
        }
        
        // 基于神煞的建议
        foreach ($shenSha as $sha) {
            if ($sha['name'] === '亡神') {
                $advice .= '有亡神，需要特别注意安全，做事要谨慎。';
                break;
            }
            if ($sha['name'] === '流霞') {
                $advice .= '有流霞，需要注意交通安全，出行要谨慎。';
                break;
            }
        }
        
        return $advice;
    }
    
    /**
     * 生成开运建议
     */
    protected function generateLuckyAdvice(array $bazi, ?string $mainPattern, array $shenSha): string
    {
        $advice = '';
        
        // 基于格局的建议
        if ($mainPattern !== null && isset($this->patternAdvice[$mainPattern]['lucky'])) {
            $advice .= $this->patternAdvice[$mainPattern]['lucky'] . ' ';
        }
        
        // 基于喜用神的建议
        $yongshenShen = $bazi['yongshen']['shen'] ?? '';
        if ($yongshenShen !== '') {
            $luckyByYongshen = [
                '金' => '喜金，贵人多在西方，适合穿白色、金色衣服，佩戴金属饰品。',
                '木' => '喜木，贵人多在东方，适合穿青色、绿色衣服，佩戴木制饰品。',
                '水' => '喜水，贵人多在北方，适合穿黑色、蓝色衣服，佩戴水晶饰品。',
                '火' => '喜火，贵人多在南方，适合穿红色、紫色衣服，佩戴红宝石饰品。',
                '土' => '喜土，贵人多在中央，适合穿黄色、棕色衣服，佩戴玉石饰品。',
            ];
            if (isset($luckyByYongshen[$yongshenShen])) {
                $advice .= $luckyByYongshen[$yongshenShen] . ' ';
            }
        }
        
        // 基于神煞的建议
        foreach ($shenSha as $sha) {
            if ($sha['name'] === '天乙贵人') {
                $advice .= '有天乙贵人，多与贵人接触，把握贵人相助的机会。';
                break;
            }
            if ($sha['name'] === '文昌') {
                $advice .= '有文昌贵人，多读书学习，提升自己的能力。';
                break;
            }
        }
        
        return $advice;
    }
    
    /**
     * 生成综合建议
     */
    protected function generateOverallAdvice(array $patternLevel, ?string $mainPattern, array $shenSha): string
    {
        $level = $patternLevel['level'] ?? '';
        $description = $patternLevel['description'] ?? '';
        
        $advice = "你的格局层次为【{$level}】，{$description} ";
        
        // 综合建议
        if ($level === '上上' || $level === '上') {
            $advice .= '建议你积极进取，把握机会，成就一番事业。但要保持谦虚，避免骄傲自满。';
        } elseif ($level === '中上') {
            $advice .= '建议你努力奋斗，稳步发展，能够有所成就。';
        } elseif ($level === '中') {
            $advice .= '建议你脚踏实地，勤恳努力，逐步实现目标。';
        } else {
            $advice .= '建议你修身养性，积累实力，循序渐进。保持乐观心态，相信自己。';
        }
        
        // 基于神煞的建议
        $goodShenShaCount = 0;
        $badShenShaCount = 0;
        foreach ($shenSha as $sha) {
            if (($sha['quality'] ?? '') === '吉') {
                $goodShenShaCount++;
            } elseif (($sha['quality'] ?? '') === '凶') {
                $badShenShaCount++;
            }
        }
        
        if ($goodShenShaCount > 0) {
            $advice .= " 命中有{$goodShenShaCount}个吉神，能够得到贵人相助。";
        }
        
        if ($badShenShaCount > 0) {
            $advice .= " 命中有{$badShenShaCount}个凶煞，需要特别注意防范。";
        }
        
        return $advice;
    }
}
