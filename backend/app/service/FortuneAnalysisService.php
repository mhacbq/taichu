<?php
declare(strict_types=1);

namespace app\service;

/**
 * 命理分析服务
 * 
 * 提供大运与流年的综合分析功能
 */
class FortuneAnalysisService
{
    /**
     * 天干五行
     */
    protected $ganWuXing = [
        '甲' => '木', '乙' => '木',
        '丙' => '火', '丁' => '火',
        '戊' => '土', '己' => '土',
        '庚' => '金', '辛' => '金',
        '壬' => '水', '癸' => '水'
    ];
    
    /**
     * 地支五行
     */
    protected $zhiWuXing = [
        '子' => '水', '丑' => '土', '寅' => '木', '卯' => '木',
        '辰' => '土', '巳' => '火', '午' => '火', '未' => '土',
        '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水'
    ];
    
    /**
     * 天干十神关系
     */
    protected function calculateShiShen(string $dayMaster, string $targetGan): string
    {
        $ganYinYang = [
            '甲' => '阳', '乙' => '阴', '丙' => '阳', '丁' => '阴', '戊' => '阳',
            '己' => '阴', '庚' => '阳', '辛' => '阴', '壬' => '阳', '癸' => '阴'
        ];
        
        $shengRelation = ['木' => '火', '火' => '土', '土' => '金', '金' => '水', '水' => '木'];
        $keRelation = ['木' => '土', '土' => '水', '水' => '火', '火' => '金', '金' => '木'];
        
        $dayWuxing = $this->ganWuXing[$dayMaster];
        $targetWuxing = $this->ganWuXing[$targetGan];
        $dayYinyang = $ganYinYang[$dayMaster];
        $targetYinyang = $ganYinYang[$targetGan];
        
        $isSameYinyang = ($dayYinyang === $targetYinyang);
        
        if ($shengRelation[$targetWuxing] === $dayWuxing) {
            return $isSameYinyang ? '偏印' : '正印';
        }
        if ($shengRelation[$dayWuxing] === $targetWuxing) {
            return $isSameYinyang ? '食神' : '伤官';
        }
        if ($keRelation[$targetWuxing] === $dayWuxing) {
            return $isSameYinyang ? '七杀' : '正官';
        }
        if ($keRelation[$dayWuxing] === $targetWuxing) {
            return $isSameYinyang ? '偏财' : '正财';
        }
        return $isSameYinyang ? '比肩' : '劫财';
    }
    
    /**
     * 综合分析大运与流年
     * 
     * @param array $bazi 八字信息
     * @param array $daYun 大运信息
     * @param array $liuNian 流年信息
     * @return array 综合分析结果
     */
    public function analyzeDaYunLiuNian(array $bazi, array $daYun, array $liuNian): array
    {
        $dayMaster = $bazi['day']['gan'];
        $dayMasterWuxing = $bazi['day_master_wuxing'];
        
        $analysis = [];
        
        // 找出当前所在的大运
        $currentAge = $this->calculateCurrentAge($bazi['birth_date'] ?? date('Y-m-d'));
        $currentDaYun = $this->findCurrentDaYun($daYun, $currentAge);
        
        foreach ($liuNian as $yearData) {
            $year = $yearData['year'];
            $yearGan = $yearData['gan'];
            $yearZhi = $yearData['zhi'];
            
            // 计算流年与日主的关系
            $yearShiShen = $this->calculateShiShen($dayMaster, $yearGan);
            
            // 分析流年与大运的关系
            $liuNianDaYunRelation = $this->analyzeLiuNianDaYunRelation(
                $yearData, 
                $currentDaYun, 
                $dayMasterWuxing
            );
            
            // 综合分析该年运势
            $yearAnalysis = $this->generateYearAnalysis(
                $year,
                $yearShiShen,
                $liuNianDaYunRelation,
                $dayMasterWuxing,
                $currentDaYun
            );
            
            $analysis[] = array_merge($yearData, [
                'shishen' => $yearShiShen,
                'dayun_relation' => $liuNianDaYunRelation,
                'analysis' => $yearAnalysis
            ]);
        }
        
        return [
            'current_dayun' => $currentDaYun,
            'current_age' => $currentAge,
            'yearly_analysis' => $analysis
        ];
    }
    
    /**
     * 计算当前年龄
     */
    protected function calculateCurrentAge(string $birthDate): int
    {
        $birth = new \DateTime($birthDate);
        $now = new \DateTime();
        return $birth->diff($now)->y;
    }
    
    /**
     * 找出当前所在的大运
     */
    protected function findCurrentDaYun(array $daYun, int $currentAge): ?array
    {
        foreach ($daYun as $yun) {
            if ($currentAge >= $yun['age_start'] && $currentAge <= $yun['age_end']) {
                return $yun;
            }
        }
        return $daYun[0] ?? null;
    }
    
    /**
     * 分析流年与大运的关系
     */
    protected function analyzeLiuNianDaYunRelation(
        array $liuNian, 
        ?array $daYun, 
        string $dayMasterWuxing
    ): array {
        if (!$daYun) {
            return ['level' => 'neutral', 'desc' => '运势平稳'];
        }
        
        $yearGan = $liuNian['gan'];
        $yearZhi = $liuNian['zhi'];
        $yunGan = $daYun['gan'];
        $yunZhi = $daYun['zhi'];
        
        // 检查天干关系
        $ganRelation = $this->getGanRelation($yearGan, $yunGan);
        
        // 检查地支关系
        $zhiRelation = $this->getZhiRelation($yearZhi, $yunZhi);
        
        // 综合分析
        $score = 5; // 基础分
        
        if ($ganRelation === '相合') $score += 2;
        if ($ganRelation === '相冲') $score -= 2;
        if ($zhiRelation === '六合' || $zhiRelation === '三合') $score += 2;
        if ($zhiRelation === '六冲') $score -= 2;
        
        // 五行关系分析
        $yearWuxing = $this->ganWuXing[$yearGan];
        $yunWuxing = $this->ganWuXing[$yunGan];
        
        $wuxingAnalysis = $this->analyzeWuxingRelation($yearWuxing, $yunWuxing, $dayMasterWuxing);
        
        $level = $score >= 6 ? 'positive' : ($score <= 4 ? 'cautious' : 'neutral');
        
        return [
            'level' => $level,
            'score' => $score,
            'gan_relation' => $ganRelation,
            'zhi_relation' => $zhiRelation,
            'wuxing_analysis' => $wuxingAnalysis,
            'desc' => $this->getRelationDescription($ganRelation, $zhiRelation, $wuxingAnalysis)
        ];
    }
    
    /**
     * 获取天干关系
     */
    protected function getGanRelation(string $gan1, string $gan2): string
    {
        $ganHe = [
            '甲' => '己', '己' => '甲',
            '乙' => '庚', '庚' => '乙',
            '丙' => '辛', '辛' => '丙',
            '丁' => '壬', '壬' => '丁',
            '戊' => '癸', '癸' => '戊'
        ];
        
        $ganChong = [
            '甲' => '庚', '庚' => '甲',
            '乙' => '辛', '辛' => '乙',
            '丙' => '壬', '壬' => '丙',
            '丁' => '癸', '癸' => '丁',
            '戊' => '己', '己' => '戊'
        ];
        
        if (isset($ganHe[$gan1]) && $ganHe[$gan1] === $gan2) {
            return '相合';
        }
        if (isset($ganChong[$gan1]) && $ganChong[$gan1] === $gan2) {
            return '相冲';
        }
        return '中性';
    }
    
    /**
     * 获取地支关系
     */
    protected function getZhiRelation(string $zhi1, string $zhi2): string
    {
        $liuHe = [
            '子' => '丑', '丑' => '子',
            '寅' => '亥', '亥' => '寅',
            '卯' => '戌', '戌' => '卯',
            '辰' => '酉', '酉' => '辰',
            '巳' => '申', '申' => '巳',
            '午' => '未', '未' => '午'
        ];
        
        $liuChong = [
            '子' => '午', '午' => '子',
            '丑' => '未', '未' => '丑',
            '寅' => '申', '申' => '寅',
            '卯' => '酉', '酉' => '卯',
            '辰' => '戌', '戌' => '辰',
            '巳' => '亥', '亥' => '巳'
        ];
        
        if (isset($liuHe[$zhi1]) && $liuHe[$zhi1] === $zhi2) {
            return '六合';
        }
        if (isset($liuChong[$zhi1]) && $liuChong[$zhi1] === $zhi2) {
            return '六冲';
        }
        
        // 检查三合
        $sanHe = [
            ['申', '子', '辰'],
            ['亥', '卯', '未'],
            ['寅', '午', '戌'],
            ['巳', '酉', '丑']
        ];
        
        foreach ($sanHe as $group) {
            if (in_array($zhi1, $group) && in_array($zhi2, $group)) {
                return '三合';
            }
        }
        
        return '中性';
    }
    
    /**
     * 分析五行关系
     */
    protected function analyzeWuxingRelation(string $yearWuxing, string $yunWuxing, string $dayMasterWuxing): array
    {
        $shengRelation = ['木' => '火', '火' => '土', '土' => '金', '金' => '水', '水' => '木'];
        $keRelation = ['木' => '土', '土' => '水', '水' => '火', '火' => '金', '金' => '木'];
        
        // 流年与大运的五行关系
        $yearToYun = '中性';
        if ($shengRelation[$yearWuxing] === $yunWuxing) {
            $yearToYun = '生';
        } elseif ($keRelation[$yearWuxing] === $yunWuxing) {
            $yearToYun = '克';
        }
        
        // 流年对日主的影响
        $yearToDay = '中性';
        if ($yearWuxing === $dayMasterWuxing) {
            $yearToDay = '比劫';
        } elseif ($shengRelation[$dayMasterWuxing] === $yearWuxing) {
            $yearToDay = '食伤';
        } elseif ($shengRelation[$yearWuxing] === $dayMasterWuxing) {
            $yearToDay = '印绶';
        } elseif ($keRelation[$dayMasterWuxing] === $yearWuxing) {
            $yearToDay = '官杀';
        } elseif ($keRelation[$yearWuxing] === $dayMasterWuxing) {
            $yearToDay = '财星';
        }
        
        return [
            'year_to_yun' => $yearToYun,
            'year_to_day' => $yearToDay,
            'year_wuxing' => $yearWuxing,
            'yun_wuxing' => $yunWuxing
        ];
    }
    
    /**
     * 获取关系描述
     */
    protected function getRelationDescription(string $ganRelation, string $zhiRelation, array $wuxingAnalysis): string
    {
        $desc = '';
        
        if ($ganRelation === '相合' || $zhiRelation === '六合') {
            $desc = '流年与大运相合，运势顺畅，易得助力。';
        } elseif ($ganRelation === '相冲' || $zhiRelation === '六冲') {
            $desc = '流年与大运相冲，变动较多，需谨慎行事。';
        } elseif ($zhiRelation === '三合') {
            $desc = '流年与大运形成三合，贵人运佳，合作顺利。';
        } else {
            $desc = '流年与大运关系平和，稳中有进。';
        }
        
        // 根据五行影响调整
        switch ($wuxingAnalysis['year_to_day']) {
            case '印绶':
                $desc .= '利于学习进修，易得长辈提携。';
                break;
            case '财星':
                $desc .= '财运活跃，宜把握机会。';
                break;
            case '官杀':
                $desc .= '事业有压力，但利于晋升。';
                break;
            case '食伤':
                $desc .= '创意丰富，宜表达展现。';
                break;
        }
        
        return $desc;
    }
    
    /**
     * 生成年度分析
     */
    protected function generateYearAnalysis(
        int $year,
        string $yearShiShen,
        array $liuNianDaYunRelation,
        string $dayMasterWuxing,
        ?array $currentDaYun
    ): array {
        
        // 事业分析
        $career = $this->analyzeCareer($yearShiShen, $liuNianDaYunRelation['wuxing_analysis']);
        
        // 财运分析
        $wealth = $this->analyzeWealth($yearShiShen, $liuNianDaYunRelation['wuxing_analysis']);
        
        // 感情分析
        $relationship = $this->analyzeRelationship($yearShiShen, $liuNianDaYunRelation['wuxing_analysis']);
        
        // 健康提示
        $health = $this->analyzeHealth($yearShiShen, $dayMasterWuxing);
        
        return [
            'year' => $year,
            'overall_level' => $liuNianDaYunRelation['level'],
            'overall_desc' => $liuNianDaYunRelation['desc'],
            'career' => $career,
            'wealth' => $wealth,
            'relationship' => $relationship,
            'health' => $health,
            'shishen' => $yearShiShen,
            'key_months' => $this->suggestKeyMonths($year, $liuNianDaYunRelation['level'])
        ];
    }
    
    /**
     * 分析事业运势
     */
    protected function analyzeCareer(string $shishen, array $wuxingAnalysis): array
    {
        $careerMap = [
            '正官' => ['level' => 'positive', 'desc' => '事业发展稳定，有晋升机会，宜表现责任心'],
            '七杀' => ['level' => 'cautious', 'desc' => '事业压力较大，竞争激烈，需谨慎决策'],
            '正印' => ['level' => 'positive', 'desc' => '学习进修的好时机，易得贵人提携'],
            '偏印' => ['level' => 'neutral', 'desc' => '适合专业技能提升，可能有意外收获'],
            '正财' => ['level' => 'positive', 'desc' => '工作收入稳定，踏实努力有回报'],
            '偏财' => ['level' => 'neutral', 'desc' => '可能有副业机会，但不宜冒进'],
            '食神' => ['level' => 'positive', 'desc' => '创意表达能力强，适合展现才华'],
            '伤官' => ['level' => 'cautious', 'desc' => '想法较多，注意言行分寸，避免冲突'],
            '比肩' => ['level' => 'neutral', 'desc' => '适合团队合作，但需注意竞争关系'],
            '劫财' => ['level' => 'cautious', 'desc' => '竞争压力大，注意人际关系处理']
        ];
        
        return $careerMap[$shishen] ?? ['level' => 'neutral', 'desc' => '事业平稳发展，按部就班即可'];
    }
    
    /**
     * 分析财运
     */
    protected function analyzeWealth(string $shishen, array $wuxingAnalysis): array
    {
        $wealthMap = [
            '正财' => ['level' => 'positive', 'desc' => '正财收入稳定，理财规划有利'],
            '偏财' => ['level' => 'neutral', 'desc' => '偏财有机会，但风险并存，需谨慎'],
            '食神' => ['level' => 'positive', 'desc' => '财源广进，可通过技能变现'],
            '伤官' => ['level' => 'cautious', 'desc' => '财运起伏，避免冲动消费'],
            '正印' => ['level' => 'neutral', 'desc' => '财运平稳，宜稳健理财'],
            '正官' => ['level' => 'positive', 'desc' => '正财运佳，工作收入有增长'],
        ];
        
        return $wealthMap[$shishen] ?? ['level' => 'neutral', 'desc' => '财运平稳，量入为出即可'];
    }
    
    /**
     * 分析感情
     */
    protected function analyzeRelationship(string $shishen, array $wuxingAnalysis): array
    {
        $relationshipMap = [
            '正财' => ['level' => 'positive', 'desc' => '感情稳定，适合谈婚论嫁'],
            '偏财' => ['level' => 'neutral', 'desc' => '异性缘佳，但需专一对待'],
            '正官' => ['level' => 'positive', 'desc' => '感情和谐，互相尊重'],
            '七杀' => ['level' => 'cautious', 'desc' => '感情有波折，需多沟通'],
            '食神' => ['level' => 'positive', 'desc' => '感情甜蜜，相处融洽'],
            '伤官' => ['level' => 'cautious', 'desc' => '情绪波动大，注意表达方式'],
            '正印' => ['level' => 'neutral', 'desc' => '感情平稳，互相包容'],
        ];
        
        return $relationshipMap[$shishen] ?? ['level' => 'neutral', 'desc' => '感情发展平稳，顺其自然'];
    }
    
    /**
     * 分析健康
     */
    protected function analyzeHealth(string $shishen, string $dayMasterWuxing): array
    {
        $healthMap = [
            '金' => '注意呼吸系统、皮肤健康',
            '木' => '注意肝胆、神经系统',
            '水' => '注意肾脏、泌尿系统',
            '火' => '注意心脏、血压',
            '土' => '注意脾胃、消化系统'
        ];
        
        $shishenHealth = [
            '官杀' => '注意工作压力带来的健康问题',
            '食伤' => '注意饮食规律，避免过劳',
            '印绶' => '整体健康良好，保持现状',
            '财星' => '注意劳逸结合',
            '比劫' => '注意运动安全，避免外伤'
        ];
        
        $baseHealth = $healthMap[$dayMasterWuxing] ?? '保持规律作息';
        $shishenEffect = $shishenHealth[$this->categorizeShiShen($shishen)] ?? '';
        
        return [
            'attention' => $baseHealth . ($shishenEffect ? '；' . $shishenEffect : ''),
            'suggestion' => '建议定期体检，保持适度运动，注意饮食均衡。'
        ];
    }
    
    /**
     * 分类十神
     */
    protected function categorizeShiShen(string $shishen): string
    {
        $categoryMap = [
            '正官' => '官杀', '七杀' => '官杀',
            '正印' => '印绶', '偏印' => '印绶',
            '正财' => '财星', '偏财' => '财星',
            '食神' => '食伤', '伤官' => '食伤',
            '比肩' => '比劫', '劫财' => '比劫'
        ];
        
        return $categoryMap[$shishen] ?? '';
    }
    
    /**
     * 建议关键月份
     */
    protected function suggestKeyMonths(int $year, string $level): array
    {
        $keyMonths = [];
        
        if ($level === 'positive') {
            $keyMonths = [
                ['month' => 2, 'desc' => '开局良好，把握机会'],
                ['month' => 5, 'desc' => '事业有突破'],
                ['month' => 8, 'desc' => '财运亨通'],
                ['month' => 11, 'desc' => '年终收获']
            ];
        } elseif ($level === 'cautious') {
            $keyMonths = [
                ['month' => 3, 'desc' => '注意变动，谨慎行事'],
                ['month' => 6, 'desc' => '压力较大，注意调节'],
                ['month' => 9, 'desc' => '避免冲动决策'],
                ['month' => 12, 'desc' => '总结反思，规划来年']
            ];
        } else {
            $keyMonths = [
                ['month' => 4, 'desc' => '稳步推进'],
                ['month' => 7, 'desc' => '适合学习进修'],
                ['month' => 10, 'desc' => '注意人际关系']
            ];
        }
        
        return $keyMonths;
    }
}