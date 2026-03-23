<?php
declare(strict_types=1);

namespace app\service;

use app\service\ConfigService;

/**
 * 大运运势评分服务
 * 提供大运的详细评分和分析
 */
class DayunFortuneService
{
    // 大运评分消耗积分（默认值，实际以 ConfigService 为准）
    const DAYUN_ANALYSIS_POINTS_COST = 50;
    
    // 大运K线图消耗积分（默认值，实际以 ConfigService 为准）
    const DAYUN_CHART_POINTS_COST = 30;

    /**
     * 获取大运分析实际积分消耗
     */
    public static function getAnalysisPointsCost(): int
    {
        $info = ConfigService::calculatePointsCost('dayun_analysis');
        return $info['final'];
    }

    /**
     * 获取大运K线图实际积分消耗
     */
    public static function getChartPointsCost(): int
    {
        $info = ConfigService::calculatePointsCost('dayun_chart');
        return $info['final'];
    }
    
    // 缓存有效期（3天）
    const CACHE_TTL = 259200;
    
    /**
     * 分析单个大运的运势
     * 
     * @param array $dayun 大运数据
     * @param array $bazi 八字数据
     * @param int $userId 用户ID
     * @return array 大运分析结果
     * @throws \Exception
     */
    public function analyzeDayun(array $dayun, array $bazi, int $userId): array
    {
        // 检查缓存
        $cacheKey = CacheService::dayunKey($dayun, $bazi['day']['gan']);
        $cached = CacheService::get($cacheKey);
        if ($cached) {
            $cached['from_cache'] = true;
            $cached['points_cost'] = 0;
            return $cached;
        }

        $userModel = \app\model\User::find($userId);
        if (!$userModel) {
            throw new \Exception('用户不存在');
        }

        $analysisCost = self::getAnalysisPointsCost();

        if ((int) ($userModel->points ?? 0) < $analysisCost) {
            throw new \Exception('积分不足，需要' . $analysisCost . '积分', 403);
        }

        // 先完成所有业务计算，计算成功后再扣积分，避免计算异常时误扣费
        $scores = $this->calculateDayunScores($dayun, $bazi);
        $analysis = $this->generateDayunAnalysis($dayun, $bazi, $scores);
        $overallScore = $this->normalizeScore($scores['overall'] ?? 0);
        $fortuneLevel = $this->getFortuneLevel($overallScore);

        // 计算完成后再扣积分（确保计算异常时不扣费）
        $pointsService = new PointsService();
        $consumeResult = $pointsService->consume(
            $userId,
            $analysisCost,
            '大运运势分析',
            'dayun_analysis',
            0,
            "大运: {$dayun['gan']}{$dayun['zhi']} ({$dayun['start_age']}-{$dayun['end_age']}岁)"
        );

        if (empty($consumeResult['success'])) {
            $message = (string) ($consumeResult['message'] ?? '积分扣除失败');
            $code = $message === '积分不足' ? 403 : 500;
            throw new \Exception($message, $code);
        }

        $result = [
            'dayun' => $dayun,
            'scores' => $scores,
            'fortune_level' => $fortuneLevel,
            'overall_score' => $overallScore,
            'analysis' => $analysis,
            'lucky_years' => $this->getLuckyYears($dayun),
            'unlucky_years' => $this->getUnluckyYears($dayun),
            'key_suggestions' => $this->getKeySuggestions($scores, $dayun, $bazi),
            'points_cost' => $analysisCost,
            'remaining_points' => (int) ($consumeResult['balance'] ?? 0),
            'from_cache' => false,
        ];

        CacheService::set($cacheKey, $result, self::CACHE_TTL, CacheService::TAG_AI);

        return $result;
    }
    
    /**
     * 获取大运K线图数据
     * 
     * @param array $dayuns 大运列表
     * @param array $bazi 八字数据
     * @param int $userId 用户ID
     * @return array K线图数据
     * @throws \Exception
     */
    public function getDayunChartData(array $dayuns, array $bazi, int $userId): array
    {
        $userModel = \app\model\User::find($userId);
        if (!$userModel) {
            throw new \Exception('用户不存在');
        }

        $currentBalance = (int) ($userModel->points ?? 0);

        // 检查缓存（按用户隔离，并在命中时回填当前余额，避免旧缓存造成扣费口径漂移）
        $cacheKey = 'dayun_chart:' . md5(json_encode($dayuns, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ($bazi['day']['gan'] ?? '') . ':' . $userId);
        $cached = CacheService::get($cacheKey);
        if ($cached) {
            $cached['from_cache'] = true;
            $cached['points_cost'] = 0;
            $cached['remaining_points'] = $currentBalance;
            return $cached;
        }

        $chartCost = self::getChartPointsCost();

        if ($currentBalance < $chartCost) {
            throw new \Exception('积分不足，需要' . $chartCost . '积分', 403);
        }


        $chartData = [];

        foreach ($dayuns as $index => $dayun) {
            $scores = $this->calculateDayunScores($dayun, $bazi);

            // 细化到每一年的数据点
            $startYear = $dayun['start_year'] ?? (date('Y') - $dayun['start_age'] + 20);
            $years = [];
            $overallScore = $this->normalizeScore($scores['overall'] ?? 0);

            for ($i = 0; $i < 10; $i++) {

                $year = $startYear + $i;
                $age = $dayun['start_age'] + $i;

                // 计算该年的波动
                $yearScore = $this->calculateYearScoreInDayun($overallScore, $i);

                $years[] = [
                    'year' => $year,
                    'age' => $age,
                    'score' => $yearScore,
                    'ganzhi' => $this->getYearGanZhi($year),
                    'is_current' => ($year == date('Y')),
                ];
            }

            $chartData[] = [
                'dayun_index' => $index,
                'dayun_name' => $dayun['gan'] . $dayun['zhi'],
                'dayun_nayin' => $dayun['nayin'] ?? '',
                'start_age' => $dayun['start_age'],
                'end_age' => $dayun['end_age'],
                'overall_score' => $overallScore,
                'fortune_level' => $this->getFortuneLevel($overallScore),
                'years' => $years,
                'trend' => $this->calculateTrend($years),
            ];
        }

        $summary = $this->generateChartSummary($chartData);
        $bestPeriod = $this->findBestPeriod($chartData);

        $pointsService = new PointsService();
        $consumeResult = $pointsService->consume(
            $userId,
            $chartCost,
            '大运运势K线图',
            'dayun_chart',
            0,
            '查看大运运势走势图'
        );

        if (empty($consumeResult['success'])) {
            $message = (string) ($consumeResult['message'] ?? '积分扣除失败');
            $code = $message === '积分不足' ? 403 : 500;
            throw new \Exception($message, $code);
        }

        $result = [
            'chart_data' => $chartData,
            'summary' => $summary,
            'best_period' => $bestPeriod,
            'points_cost' => $chartCost,
            'remaining_points' => (int) ($consumeResult['balance'] ?? 0),
            'from_cache' => false,
        ];


        CacheService::set($cacheKey, $result, self::CACHE_TTL, CacheService::TAG_AI);

        return $result;
    }
    
    /**
     * 计算大运各项评分
     */
    protected function calculateDayunScores(array $dayun, array $bazi): array
    {
        $dayMaster = $bazi['day']['gan'];
        $dayZhi = $bazi['day']['zhi'];
        $dayMasterWuxing = $this->getGanWuxing($dayMaster);
        
        $dayunGan = $dayun['gan'];
        $dayunZhi = $dayun['zhi'];
        $dayunGanWuxing = $this->getGanWuxing($dayunGan);
        
        // 基础分
        $baseScore = 50;
        
        // 1. 天干评分（30%）
        $ganScore = $this->calculateGanScore($dayMaster, $dayunGan);
        
        // 2. 地支评分（30%）
        $zhiScore = $this->calculateZhiScore($dayZhi, $dayunZhi);
        
        // 3. 五行匹配评分（20%）
        $wuxingScore = $this->calculateWuxingScore($dayMasterWuxing, $dayunGanWuxing);
        
        // 4. 十神评分（20%）
        $shishenScore = $this->calculateShishenScore($dayun['shishen'] ?? '比肩');
        
        // 计算总分
        $overall = (int) round(
            $ganScore * 0.3 +
            $zhiScore * 0.3 +
            $wuxingScore * 0.2 +
            $shishenScore * 0.2
        );

        // 各维度评分改为确定性加权，避免同一命局重复请求却出现随机波动。
        $career = (int) round($ganScore * 0.35 + $shishenScore * 0.30 + $zhiScore * 0.20 + $wuxingScore * 0.15);
        $wealth = (int) round($wuxingScore * 0.35 + $shishenScore * 0.25 + $ganScore * 0.20 + $zhiScore * 0.20);
        $relationship = (int) round($zhiScore * 0.35 + $wuxingScore * 0.25 + $ganScore * 0.20 + $shishenScore * 0.20);
        $health = (int) round($zhiScore * 0.30 + $wuxingScore * 0.30 + $ganScore * 0.20 + $shishenScore * 0.20);

        return [
            'overall' => $overall,
            'career' => min(100, max(1, $career)),
            'wealth' => min(100, max(1, $wealth)),
            'relationship' => min(100, max(1, $relationship)),
            'health' => min(100, max(1, $health)),
            'gan_score' => $ganScore,
            'zhi_score' => $zhiScore,
            'wuxing_score' => $wuxingScore,
            'shishen_score' => $shishenScore,
        ];
    }
    
    /**
     * 计算天干评分
     */
    protected function calculateGanScore(string $dayMaster, string $dayunGan): int
    {
        $relation = $this->calculateGanRelation($dayMaster, $dayunGan);
        
        switch ($relation) {
            case 'same':
                return 85; // 比肩劫财，助力
            case 'generate':
                return 90; // 得生，最佳
            case 'drain':
                return 60; // 泄气
            case 'restrain':
                return 50; // 消耗
            case 'restrained':
                return 40; // 受制
            default:
                return 50;
        }
    }
    
    /**
     * 计算地支评分
     */
    protected function calculateZhiScore(string $dayZhi, string $dayunZhi): int
    {
        $relation = $this->calculateZhiRelation($dayZhi, $dayunZhi);
        
        switch ($relation) {
            case 'same':
                return 80;
            case 'harmony':
                return 90; // 六合最吉
            case 'generate':
                return 75;
            case 'conflict':
                return 35; // 相冲最凶
            case 'punishment':
                return 45;
            default:
                return 60;
        }
    }
    
    /**
     * 计算五行匹配评分
     */
    protected function calculateWuxingScore(string $dayMasterWuxing, string $dayunWuxing): int
    {
        if ($dayMasterWuxing === $dayunWuxing) {
            return 80; // 同五行
        }
        
        // 相生关系
        $generate = [
            '木' => '火',
            '火' => '土',
            '土' => '金',
            '金' => '水',
            '水' => '木',
        ];
        
        if ($generate[$dayMasterWuxing] === $dayunWuxing) {
            return 90; // 我生，顺畅
        }
        
        if ($generate[$dayunWuxing] === $dayMasterWuxing) {
            return 85; // 生我，得助
        }
        
        return 50; // 相克
    }
    
    /**
     * 计算十神评分
     */
    protected function calculateShishenScore(string $shishen): int
    {
        $scores = [
            '正官' => 85,
            '七杀' => 60,
            '正印' => 90,
            '偏印' => 70,
            '比肩' => 80,
            '劫财' => 65,
            '食神' => 85,
            '伤官' => 70,
            '正财' => 90,
            '偏财' => 80,
        ];
        
        return $scores[$shishen] ?? 70;
    }
    
    /**
     * 计算大运中某一年的评分
     */
    protected function calculateYearScoreInDayun(int $dayunScore, int $yearInDayun): int
    {
        // 十年大运通常呈“初入适应、中段发力、末段收束”的节奏，这里用确定性曲线模拟。
        $progress = $yearInDayun / 9; // 0-1
        $curveVariation = sin($progress * M_PI) * 8;

        $phaseVariation = match (true) {
            $yearInDayun <= 1 => -3,
            $yearInDayun <= 4 => 2,
            $yearInDayun <= 7 => 4,
            default => -2,
        };

        $yearScore = $dayunScore + $curveVariation + $phaseVariation;

        return max(1, min(100, (int) round($yearScore)));
    }

    /**
     * 统一归一化评分，避免严格类型链路里把 float 直接传给只接收 int 的方法。
     */
    protected function normalizeScore($score): int
    {
        return max(0, min(100, (int) round((float) $score)));
    }

    
    /**
     * 生成大运分析
     */
    protected function generateDayunAnalysis(array $dayun, array $bazi, array $scores): array
    {
        $overall = $scores['overall'];
        $level = $this->getFortuneLevel($overall);
        
        $shishen = $dayun['shishen'] ?? '比肩';
        $dayunName = $dayun['gan'] . $dayun['zhi'];
        
        // 整体运势分析
        $overallText = "此大运为{$dayunName}（{$shishen}）运，运势评级【{$level}】（{$overall}分）。";
        
        if ($overall >= 80) {
            $overallText .= "这是一个人生的黄金时期，各方面运势都比较顺遂，适合积极进取，把握机会实现人生目标。";
        } elseif ($overall >= 60) {
            $overallText .= "此运整体平稳，有起有落，需要稳扎稳打。虽然没有大起大落的惊喜，但也是积累的好时机。";
        } else {
            $overallText .= "此运相对平淡，可能会遇到一些挑战和阻碍。建议以守为主，避免冒进，多做准备等待时机。";
        }
        
        // 事业分析
        $careerScore = $scores['career'];
        $careerText = "事业运势{$careerScore}分。";
        if ($careerScore >= 75) {
            $careerText .= "事业发展机会较多，适合主动争取，有望获得晋升或新的发展平台。";
        } elseif ($careerScore >= 55) {
            $careerText .= "事业平稳发展，建议在当前岗位上深耕细作，积累经验。";
        } else {
            $careerText .= "事业可能面临一些阻力，不宜频繁变动，稳守现有岗位为上策。";
        }
        
        // 财富分析
        $wealthScore = $scores['wealth'];
        $wealthText = "财富运势{$wealthScore}分。";
        if ($wealthScore >= 75) {
            $wealthText .= "财运较佳，正财偏财都有机会，可以适当投资理财，但要注意风险控制。";
        } elseif ($wealthScore >= 55) {
            $wealthText .= "财运平稳，以正财为主，建议稳健理财，不要冒险投资。";
        } else {
            $wealthText .= "财运较弱，需要节俭开支，避免大额投资，以防财务压力。";
        }
        
        // 感情分析
        $relationshipScore = $scores['relationship'];
        $relationshipText = "感情运势{$relationshipScore}分。";
        if ($relationshipScore >= 75) {
            $relationshipText .= "感情运势良好，单身者有机会遇到合适的人，有伴侣者关系更加稳固。";
        } elseif ($relationshipScore >= 55) {
            $relationshipText .= "感情发展平稳，需要双方共同经营和维护。";
        } else {
            $relationshipText .= "感情可能会有波折，需要多沟通理解，避免误会和冲突。";
        }
        
        // 健康分析
        $healthScore = $scores['health'];
        $healthText = "健康运势{$healthScore}分。";
        if ($healthScore >= 75) {
            $healthText .= "身体状况良好，但仍需保持规律作息和适度运动。";
        } elseif ($healthScore >= 55) {
            $healthText .= "健康状况一般，要注意劳逸结合，避免过度劳累。";
        } else {
            $healthText .= "健康需要特别关注，建议定期体检，养成良好的生活习惯。";
        }
        
        return [
            'overall' => $overallText,
            'career' => $careerText,
            'wealth' => $wealthText,
            'relationship' => $relationshipText,
            'health' => $healthText,
        ];
    }
    
    /**
     * 获取运势等级
     */
    protected function getFortuneLevel(int $score): string
    {
        if ($score >= 90) return '大吉';
        if ($score >= 80) return '吉';
        if ($score >= 70) return '小吉';
        if ($score >= 60) return '平';
        if ($score >= 50) return '小凶';
        if ($score >= 40) return '凶';
        return '大凶';
    }
    
    /**
     * 获取幸运年份
     */
    protected function getLuckyYears(array $dayun): array
    {
        $startYear = $dayun['start_year'] ?? (date('Y') - $dayun['start_age'] + 20);
        $lucky = [];
        
        // 根据天干地支推算吉利年份
        $gan = $dayun['gan'];
        $zhi = $dayun['zhi'];
        
        for ($i = 0; $i < 10; $i++) {
            $year = $startYear + $i;
            $yearGanZhi = $this->getYearGanZhi($year);
            
            // 简单规则：天干或地支相合为吉年
            if (strpos($yearGanZhi, $gan) !== false || strpos($yearGanZhi, $zhi) !== false) {
                $lucky[] = $year;
            }
        }
        
        return array_slice($lucky, 0, 3);
    }
    
    /**
     * 获取需要注意的年份
     */
    protected function getUnluckyYears(array $dayun): array
    {
        $startYear = $dayun['start_year'] ?? (date('Y') - $dayun['start_age'] + 20);
        $unlucky = [];
        
        // 冲太岁等年份
        for ($i = 0; $i < 10; $i++) {
            if ($i % 3 === 0) { // 模拟一些年份
                $unlucky[] = $startYear + $i;
            }
        }
        
        return array_slice($unlucky, 0, 3);
    }
    
    /**
     * 获取关键建议
     */
    protected function getKeySuggestions(array $scores, array $dayun, array $bazi): array
    {
        $suggestions = [];
        
        // 根据最低分给出建议
        $minAspect = array_search(min($scores), $scores);
        
        switch ($minAspect) {
            case 'career':
                $suggestions[] = '事业上需要稳扎稳打，不要急于求成';
                $suggestions[] = '多学习新技能，为未来做准备';
                break;
            case 'wealth':
                $suggestions[] = '理财以稳健为主，避免高风险投资';
                $suggestions[] = '开源节流，建立应急储备金';
                break;
            case 'relationship':
                $suggestions[] = '多关心家人朋友，维护好人际关系';
                $suggestions[] = '学会沟通，避免不必要的误会';
                break;
            case 'health':
                $suggestions[] = '身体是革命的本钱，注意休息和运动';
                $suggestions[] = '定期体检，预防胜于治疗';
                break;
            default:
                $suggestions[] = '保持积极心态，好运自然会来';
                $suggestions[] = '多行善积德，广结善缘';
        }
        
        // 根据十神给建议
        $shishen = $dayun['shishen'] ?? '比肩';
        $shishenAdvice = [
            '正官' => '把握机会展现能力，有望获得认可',
            '七杀' => '注意控制情绪，避免与人发生冲突',
            '正印' => '适合学习进修，提升自身能力',
            '偏印' => '保持独立思考，但也要听取他人意见',
            '比肩' => '合作有利，但要注意竞争关系',
            '劫财' => '注意财务安全，避免借贷纠纷',
            '食神' => '享受生活，但要适度节制',
            '伤官' => '发挥创意，但要注意表达方式',
            '正财' => '财运稳定，适合稳健理财',
            '偏财' => '有机会获得意外之财，但要谨慎',
        ];
        
        if (isset($shishenAdvice[$shishen])) {
            $suggestions[] = $shishenAdvice[$shishen];
        }
        
        return $suggestions;
    }
    
    /**
     * 计算大运趋势
     */
    protected function calculateTrend(array $years): string
    {
        $firstScore = $years[0]['score'] ?? 50;
        $lastScore = $years[count($years) - 1]['score'] ?? 50;
        $diff = $lastScore - $firstScore;
        
        if ($diff > 15) return '上升';
        if ($diff > 5) return '小升';
        if ($diff < -15) return '下降';
        if ($diff < -5) return '小降';
        return '平稳';
    }
    
    /**
     * 生成图表摘要
     */
    protected function generateChartSummary(array $chartData): string
    {
        $scores = array_column($chartData, 'overall_score');
        $avgScore = round(array_sum($scores) / count($scores));
        $maxScore = max($scores);
        $minScore = min($scores);
        
        return "大运整体运势平均{$avgScore}分，最高{$maxScore}分，最低{$minScore}分。";
    }
    
    /**
     * 找出最佳时期
     */
    protected function findBestPeriod(array $chartData): array
    {
        $bestDayun = null;
        $bestScore = 0;
        
        foreach ($chartData as $dayun) {
            if ($dayun['overall_score'] > $bestScore) {
                $bestScore = $dayun['overall_score'];
                $bestDayun = $dayun;
            }
        }
        
        if (!$bestDayun) {
            return [];
        }
        
        // 找出该大运中的最佳年份
        $bestYear = null;
        $bestYearScore = 0;
        
        foreach ($bestDayun['years'] as $year) {
            if ($year['score'] > $bestYearScore) {
                $bestYearScore = $year['score'];
                $bestYear = $year;
            }
        }
        
        return [
            'dayun_name' => $bestDayun['dayun_name'],
            'dayun_score' => $bestDayun['overall_score'],
            'age_range' => $bestDayun['start_age'] . '-' . $bestDayun['end_age'] . '岁',
            'best_year' => $bestYear['year'] ?? null,
            'best_year_score' => $bestYearScore,
            'best_age' => $bestYear['age'] ?? null,
        ];
    }
    
    /**
     * 获取天干五行
     */
    protected function getGanWuxing(string $gan): string
    {
        $map = [
            '甲' => '木', '乙' => '木',
            '丙' => '火', '丁' => '火',
            '戊' => '土', '己' => '土',
            '庚' => '金', '辛' => '金',
            '壬' => '水', '癸' => '水',
        ];
        return $map[$gan] ?? '木';
    }
    
    /**
     * 计算天干关系
     */
    protected function calculateGanRelation(string $gan1, string $gan2): string
    {
        if ($gan1 === $gan2) return 'same';
        
        $wuxing1 = $this->getGanWuxing($gan1);
        $wuxing2 = $this->getGanWuxing($gan2);
        
        if ($wuxing1 === $wuxing2) return 'same';
        
        $generate = ['木' => '火', '火' => '土', '土' => '金', '金' => '水', '水' => '木'];
        $restrain = ['木' => '土', '土' => '水', '水' => '火', '火' => '金', '金' => '木'];
        
        if ($generate[$wuxing1] === $wuxing2) return 'drain';
        if ($generate[$wuxing2] === $wuxing1) return 'generate';
        if ($restrain[$wuxing1] === $wuxing2) return 'restrain';
        
        return 'restrained';
    }
    
    /**
     * 计算地支关系
     */
    protected function calculateZhiRelation(string $zhi1, string $zhi2): string
    {
        if ($zhi1 === $zhi2) return 'same';
        
        $liuhe = ['子' => '丑', '丑' => '子', '寅' => '亥', '亥' => '寅', '卯' => '戌', '戌' => '卯', '辰' => '酉', '酉' => '辰', '巳' => '申', '申' => '巳', '午' => '未', '未' => '午'];
        $chong = ['子' => '午', '午' => '子', '丑' => '未', '未' => '丑', '寅' => '申', '申' => '寅', '卯' => '酉', '酉' => '卯', '辰' => '戌', '戌' => '辰', '巳' => '亥', '亥' => '巳'];
        
        if (isset($liuhe[$zhi1]) && $liuhe[$zhi1] === $zhi2) return 'harmony';
        if (isset($chong[$zhi1]) && $chong[$zhi1] === $zhi2) return 'conflict';
        
        return 'neutral';
    }
    
    /**
     * 获取年份干支
     */
    protected function getYearGanZhi(int $year): string
    {
        $gan = ['庚', '辛', '壬', '癸', '甲', '乙', '丙', '丁', '戊', '己'];
        $zhi = ['申', '酉', '戌', '亥', '子', '丑', '寅', '卯', '辰', '巳', '午', '未'];
        
        return $gan[($year - 4) % 10] . $zhi[($year - 4) % 12];
    }
}
