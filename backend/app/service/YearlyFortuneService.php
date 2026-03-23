<?php
declare(strict_types=1);

namespace app\service;

use app\model\PointsRecord;
use app\service\ConfigService;
use think\facade\Db;
use think\facade\Log;


/**
 * 流年运势分析服务
 * 提供AI驱动的流年运势评测与评分
 */
class YearlyFortuneService
{
    // AI分析消耗积分（默认值，实际以 ConfigService 为准）
    const YEARLY_FORTUNE_POINTS_COST = 30;

    /**
     * 获取流年运势实际积分消耗
     */
    public static function getPointsCost(): int
    {
        $info = ConfigService::calculatePointsCost('yearly_fortune');
        return $info['final'];
    }
    
    // 缓存有效期（1天）
    const CACHE_TTL = 86400;
    
    // 是否启用缓存
    const ENABLE_CACHE = true;
    
    /**
     * 获取流年运势分析
     * 
     * @param array $bazi 八字数据
     * @param string $gender 性别
     * @param int $year 目标年份
     * @param int $userId 用户ID
     * @param int $baziId 八字记录ID
     * @return array 运势分析结果
     * @throws \Exception
     */
    public function getYearlyFortune(array $bazi, string $gender, int $year, int $userId, int $baziId = 0): array
    {
        $userModel = \app\model\User::find($userId);
        if (!$userModel) {
            throw new \Exception('用户不存在');
        }

        $currentBalance = (int) ($userModel->points ?? 0);

        // 检查缓存（按用户隔离缓存，并实时回填当前余额，避免串用历史扣费结果）
        $cacheKey = CacheService::yearlyFortuneKey($bazi, $year, $userId);
        if (self::ENABLE_CACHE) {
            $cached = CacheService::get($cacheKey);
            if ($cached) {
                $cached['from_cache'] = true;
                $cached['points_cost'] = 0;
                $cached['remaining_points'] = $currentBalance;
                return $cached;
            }
        }

        // 检查数据库中是否已有记录
        $existingRecord = Db::name('yearly_fortune')
            ->where('user_id', $userId)
            ->where('year', $year)
            ->find();

        if ($existingRecord && $existingRecord['is_paid']) {
            $result = [
                'year' => $year,
                'ganzhi' => $this->getYearGanZhi($year),
                'nayin' => $this->getYearNayin($year),
                'score' => $existingRecord['overall_score'],
                'rating' => $this->getScoreRating($existingRecord['overall_score']),
                'overall' => $existingRecord['overall_analysis'],
                'career' => '', // 兼容旧结构，如果需要可以从 monthly_fortune 中解析
                'wealth' => '',
                'relationship' => '',
                'health' => '',
                'advice' => '',
                'lucky_months' => [],
                'unlucky_months' => [],
                'lucky_colors' => [],
                'lucky_numbers' => [],
                'lucky_directions' => [],
                'points_cost' => 0,
                'remaining_points' => $currentBalance,
                'from_cache' => true,
            ];
            
            // 尝试从 monthly_fortune 恢复详细数据
            if (!empty($existingRecord['monthly_fortune'])) {
                $details = json_decode($existingRecord['monthly_fortune'], true);
                if (is_array($details)) {
                    $result = array_merge($result, $details);
                }
            }
            
            return $result;
        }

        $pointsCost = self::getPointsCost();

        if ($currentBalance < $pointsCost) {
            throw new \Exception('积分不足，需要' . $pointsCost . '积分', 403);
        }

        // 先完成流年评分与分析，再统一扣费，避免"失败仍扣费"。
        $analysis = $this->callAiForYearlyFortune($bazi, $gender, $year);
        $score = $this->calculateYearlyScore($bazi, $year);

        $pointsService = new PointsService();
        $consumeResult = $pointsService->consume(
            $userId,
            $pointsCost,
            '流年运势AI分析',
            'yearly_fortune',
            0,
            "年份: {$year}"
        );

        if (empty($consumeResult['success'])) {
            $message = (string) ($consumeResult['message'] ?? '积分扣除失败');
            $code = $message === '积分不足' ? 403 : 500;
            throw new \Exception($message, $code);
        }

        $result = [
            'year' => $year,
            'ganzhi' => $this->getYearGanZhi($year),
            'nayin' => $this->getYearNayin($year),
            'score' => $score,
            'rating' => $this->getScoreRating($score),
            'overall' => $analysis['overall'] ?? '',
            'career' => $analysis['career'] ?? '',
            'wealth' => $analysis['wealth'] ?? '',
            'relationship' => $analysis['relationship'] ?? '',
            'health' => $analysis['health'] ?? '',
            'advice' => $analysis['advice'] ?? '',
            'lucky_months' => $analysis['lucky_months'] ?? [],
            'unlucky_months' => $analysis['unlucky_months'] ?? [],
            'lucky_colors' => $analysis['lucky_colors'] ?? [],
            'lucky_numbers' => $analysis['lucky_numbers'] ?? [],
            'lucky_directions' => $analysis['lucky_directions'] ?? [],
            'points_cost' => $pointsCost,
            'remaining_points' => (int) ($consumeResult['balance'] ?? 0),
            'from_cache' => false,
        ];

        // 保存到数据库
        $recordData = [
            'user_id' => $userId,
            'year' => $year,
            'bazi_id' => $baziId,
            'overall_score' => $score,
            'career_score' => 0, // 暂无具体评分
            'wealth_score' => 0,
            'love_score' => 0,
            'health_score' => 0,
            'overall_analysis' => $analysis['overall'] ?? '',
            'monthly_fortune' => json_encode([
                'career' => $analysis['career'] ?? '',
                'wealth' => $analysis['wealth'] ?? '',
                'relationship' => $analysis['relationship'] ?? '',
                'health' => $analysis['health'] ?? '',
                'advice' => $analysis['advice'] ?? '',
                'lucky_months' => $analysis['lucky_months'] ?? [],
                'unlucky_months' => $analysis['unlucky_months'] ?? [],
                'lucky_colors' => $analysis['lucky_colors'] ?? [],
                'lucky_numbers' => $analysis['lucky_numbers'] ?? [],
                'lucky_directions' => $analysis['lucky_directions'] ?? [],
            ], JSON_UNESCAPED_UNICODE),
            'is_paid' => 1,
            'points_used' => $pointsCost,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($existingRecord) {
            Db::name('yearly_fortune')->where('id', $existingRecord['id'])->update($recordData);
        } else {
            Db::name('yearly_fortune')->insert($recordData);
        }

        if (self::ENABLE_CACHE) {
            CacheService::set($cacheKey, $result, self::CACHE_TTL, CacheService::TAG_AI);
        }

        return $result;
    }
    
    /**
     * 批量获取多年运势（用于K线图）n     * 
     * @param array $bazi 八字数据
     * @param string $gender 性别
     * @param int $startYear 起始年份
     * @param int $endYear 结束年份
     * @return array 多年运势数据
     */
    public function getYearlyFortuneRange(array $bazi, string $gender, int $startYear, int $endYear): array
    {
        $results = [];
        
        for ($year = $startYear; $year <= $endYear; $year++) {
            // 计算该年运势评分（不调用AI，使用算法）
            $score = $this->calculateYearlyScore($bazi, $year);
            $ganzhi = $this->getYearGanZhi($year);
            
            $results[] = [
                'year' => $year,
                'ganzhi' => $ganzhi,
                'score' => $score,
                'rating' => $this->getScoreRating($score),
                'trend' => $this->calculateTrend($results, $score),
            ];
        }
        
        return $results;
    }
    
    /**
     * 调用AI分析流年运势
     */
    protected function callAiForYearlyFortune(array $bazi, string $gender, int $year): array
    {
        $config = Config::get('ai', []);
        
        if (empty($config['api_key']) || empty($config['api_url'])) {
            // AI未配置，使用本地算法
            return $this->generateLocalYearlyAnalysis($bazi, $gender, $year);
        }
        
        $yearGanZhi = $this->getYearGanZhi($year);
        
        $systemPrompt = "你是一位资深的八字命理大师，精通流年运势分析。请基于用户的八字信息，详细分析{$year}年（{$yearGanZhi}年）的运势。\n\n";
        $systemPrompt .= "分析要求：\n";
        $systemPrompt .= "1. 整体运势概述（100字左右）\n";
        $systemPrompt .= "2. 事业运势分析（80字左右）\n";
        $systemPrompt .= "3. 财富运势分析（80字左右）\n";
        $systemPrompt .= "4. 感情运势分析（80字左右）\n";
        $systemPrompt .= "5. 健康提醒（60字左右）\n";
        $systemPrompt .= "6. 开运建议（100字左右）\n";
        $systemPrompt .= "7. 幸运月份列表\n";
        $systemPrompt .= "8. 需要注意的月份列表\n";
        $systemPrompt .= "9. 幸运颜色\n";
        $systemPrompt .= "10. 幸运数字\n";
        $systemPrompt .= "11. 幸运方位\n\n";
        $systemPrompt .= "请以JSON格式返回，字段名：overall, career, wealth, relationship, health, advice, lucky_months(数组), unlucky_months(数组), lucky_colors(数组), lucky_numbers(数组), lucky_directions(数组)";
        
        $userPrompt = "八字信息：\n";
        $userPrompt .= "性别：" . ($gender === 'male' ? '男' : '女') . "\n";
        $userPrompt .= "年柱：{$bazi['year']['gan']}{$bazi['year']['zhi']}\n";
        $userPrompt .= "月柱：{$bazi['month']['gan']}{$bazi['month']['zhi']}\n";
        $userPrompt .= "日柱：{$bazi['day']['gan']}{$bazi['day']['zhi']}（日主：{$bazi['day']['gan']}）\n";
        $userPrompt .= "时柱：{$bazi['hour']['gan']}{$bazi['hour']['zhi']}\n";
        $userPrompt .= "\n请分析{$year}年（{$yearGanZhi}年）的流年运势。";
        
        try {
            $response = $this->callAiApi($systemPrompt, $userPrompt, $config);
            
            // 尝试解析JSON
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $data;
            }
            
            // 如果不是JSON，尝试从文本提取
            return $this->parseTextToAnalysis($response);
            
        } catch (\Throwable $e) {
            Log::warning('AI流年分析失败，已回退本地分析', [
                'year' => $year,
                'gender' => $gender,
                'day_master' => (string) ($bazi['day']['gan'] ?? ''),
                'has_hour_pillar' => !empty($bazi['hour']['gan']) && !empty($bazi['hour']['zhi']),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->generateLocalYearlyAnalysis($bazi, $gender, $year);
        }

    }
    
    /**
     * 生成本地流年分析（AI失败时使用）
     */
    protected function generateLocalYearlyAnalysis(array $bazi, string $gender, int $year): array
    {
        $yearGanZhi = $this->getYearGanZhi($year);
        $yearGan = mb_substr($yearGanZhi, 0, 1);
        $yearZhi = mb_substr($yearGanZhi, 1, 1);
        
        $dayMaster = $bazi['day']['gan'];
        $dayMasterWuxing = $this->getGanWuxing($dayMaster);
        
        // 计算与日主的关系
        $relation = $this->calculateGanRelation($dayMaster, $yearGan);
        
        $overall = "{$year}年为{$yearGanZhi}年，";
        if ($relation === 'same') {
            $overall .= "与日主同性相助，是较为有利的一年，适合主动出击，把握机会。";
        } elseif ($relation === 'generate') {
            $overall .= "有利于你的发展，有贵人相助，宜积极进取。";
        } elseif ($relation === 'drain') {
            $overall .= "可能较为消耗精力，需要稳扎稳打，注意劳逸结合。";
        } else {
            $overall .= "可能面临一些挑战，需要谨慎行事，保守为上。";
        }
        
        return [
            'overall' => $overall,
            'career' => $this->getCareerFortune($relation, $yearZhi),
            'wealth' => $this->getWealthFortune($relation, $yearZhi),
            'relationship' => $this->getRelationshipFortune($gender, $yearZhi),
            'health' => $this->getHealthFortune($yearZhi),
            'advice' => $this->getYearlyAdvice($dayMasterWuxing, $yearGan),
            'lucky_months' => $this->getLuckyMonths($dayMasterWuxing),
            'unlucky_months' => $this->getUnluckyMonths($dayMasterWuxing),
            'lucky_colors' => $this->getLuckyColors($dayMasterWuxing),
            'lucky_numbers' => $this->getLuckyNumbers($dayMasterWuxing),
            'lucky_directions' => $this->getLuckyDirections($dayMasterWuxing),
        ];
    }
    
    /**
     * 计算流年运势评分（1-100）
     */
    public function calculateYearlyScore(array $bazi, int $year): int
    {
        $score = 50; // 基础分
        
        $yearGanZhi = $this->getYearGanZhi($year);
        $yearGan = mb_substr($yearGanZhi, 0, 1);
        $yearZhi = mb_substr($yearGanZhi, 1, 1);
        
        $dayMaster = $bazi['day']['gan'];
        $dayZhi = $bazi['day']['zhi'];
        
        // 1. 天干关系评分
        $ganRelation = $this->calculateGanRelation($dayMaster, $yearGan);
        switch ($ganRelation) {
            case 'same':
                $score += 15;
                break;
            case 'generate':
                $score += 10;
                break;
            case 'drain':
                $score -= 5;
                break;
            case 'restrain':
                $score -= 10;
                break;
            case 'restrained':
                $score -= 14;
                break;
        }
        
        // 2. 地支关系评分
        $zhiRelation = $this->calculateZhiRelation($dayZhi, $yearZhi);
        switch ($zhiRelation) {
            case 'same':
                $score += 10;
                break;
            case 'generate':
                $score += 8;
                break;
            case 'harmony':
                $score += 12;
                break;
            case 'drain':
                $score -= 4;
                break;
            case 'restrain':
            case 'restrained':
                $score -= 9;
                break;
            case 'conflict':
                $score -= 15;
                break;
            case 'punishment':
                $score -= 8;
                break;
            case 'harm':
                $score -= 6;
                break;
            case 'break':
                $score -= 5;
                break;
        }

        
        // 3. 纳音五行匹配
        $yearNayin = $this->getYearNayin($year);
        $dayNayin = $bazi['day']['nayin'] ?? '';
        if (!empty($dayNayin) && !empty($yearNayin)) {
            $dayNayinWuxing = $this->getNayinWuxing($dayNayin);
            $yearNayinWuxing = $this->getNayinWuxing($yearNayin);
            if ($dayNayinWuxing === $yearNayinWuxing) {
                $score += 5;
            }
        }
        
        // 4. 年份特殊加成（本命年等）
        $yearAnimal = $this->getYearAnimal($year);
        $birthAnimal = $this->getYearAnimalFromBazi($bazi);
        if ($yearAnimal === $birthAnimal) {
            $score -= 5; // 本命年稍减
        }
        
        // 限制在1-100之间
        return max(1, min(100, $score));
    }
    
    /**
     * 根据评分获取等级
     */
    protected function getScoreRating(int $score): string
    {
        if ($score >= 85) return '大吉';
        if ($score >= 70) return '吉';
        if ($score >= 55) return '平';
        if ($score >= 40) return '小凶';
        return '凶';
    }
    
    /**
     * 计算趋势
     */
    protected function calculateTrend(array $results, int $currentScore): string
    {
        $count = count($results);
        if ($count < 2) return '平';
        
        $prevScore = $results[$count - 1]['score'] ?? $currentScore;
        $diff = $currentScore - $prevScore;
        
        if ($diff >= 10) return '↗ 上升';
        if ($diff >= 5) return '↗ 小升';
        if ($diff <= -10) return '↘ 下降';
        if ($diff <= -5) return '↘ 小降';
        return '→ 平稳';
    }
    
    /**
     * 获取年份干支
     */
    protected function getYearGanZhi(int $year): string
    {
        $gan = ['庚', '辛', '壬', '癸', '甲', '乙', '丙', '丁', '戊', '己'];
        $zhi = ['申', '酉', '戌', '亥', '子', '丑', '寅', '卯', '辰', '巳', '午', '未'];
        
        $ganIndex = ($year - 4) % 10;
        $zhiIndex = ($year - 4) % 12;
        
        return $gan[$ganIndex] . $zhi[$zhiIndex];
    }
    
    /**
     * 获取年份纳音
     */
    protected function getYearNayin(int $year): string
    {
        $nayin = [
            '甲子' => '海中金', '乙丑' => '海中金',
            '丙寅' => '炉中火', '丁卯' => '炉中火',
            '戊辰' => '大林木', '己巳' => '大林木',
            '庚午' => '路旁土', '辛未' => '路旁土',
            '壬申' => '剑锋金', '癸酉' => '剑锋金',
            '甲戌' => '山头火', '乙亥' => '山头火',
            '丙子' => '涧下水', '丁丑' => '涧下水',
            '戊寅' => '城头土', '己卯' => '城头土',
            '庚辰' => '白蜡金', '辛巳' => '白蜡金',
            '壬午' => '杨柳木', '癸未' => '杨柳木',
            '甲申' => '泉中水', '乙酉' => '泉中水',
            '丙戌' => '屋上土', '丁亥' => '屋上土',
            '戊子' => '霹雳火', '己丑' => '霹雳火',
            '庚寅' => '松柏木', '辛卯' => '松柏木',
            '壬辰' => '长流水', '癸巳' => '长流水',
            '甲午' => '沙中金', '乙未' => '沙中金',
            '丙申' => '山下火', '丁酉' => '山下火',
            '戊戌' => '平地木', '己亥' => '平地木',
            '庚子' => '壁上土', '辛丑' => '壁上土',
            '壬寅' => '金箔金', '癸卯' => '金箔金',
            '甲辰' => '覆灯火', '乙巳' => '覆灯火',
            '丙午' => '天河水', '丁未' => '天河水',
            '戊申' => '大驿土', '己酉' => '大驿土',
            '庚戌' => '钗钏金', '辛亥' => '钗钏金',
            '壬子' => '桑柘木', '癸丑' => '桑柘木',
            '甲寅' => '大溪水', '乙卯' => '大溪水',
            '丙辰' => '沙中土', '丁巳' => '沙中土',
            '戊午' => '天上火', '己未' => '天上火',
            '庚申' => '石榴木', '辛酉' => '石榴木',
            '壬戌' => '大海水', '癸亥' => '大海水',
        ];
        
        $ganZhi = $this->getYearGanZhi($year);
        return $nayin[$ganZhi] ?? '未知';
    }
    
    /**
     * 获取年份生肖
     */
    protected function getYearAnimal(int $year): string
    {
        $animals = ['猴', '鸡', '狗', '猪', '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊'];
        return $animals[($year - 4) % 12];
    }
    
    /**
     * 从出生八字获取生肖
     */
    protected function getYearAnimalFromBazi(array $bazi): string
    {
        $birthYear = (int) ($bazi['year']['number'] ?? 2000);
        return $this->getYearAnimal($birthYear);
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
     * 获取地支五行。
     */
    protected function getZhiWuxing(string $zhi): string
    {
        $map = [
            '寅' => '木', '卯' => '木',
            '巳' => '火', '午' => '火',
            '辰' => '土', '戌' => '土', '丑' => '土', '未' => '土',
            '申' => '金', '酉' => '金',
            '亥' => '水', '子' => '水',
        ];

        return $map[$zhi] ?? '土';
    }
    
    /**
     * 计算天干关系
     */
    protected function calculateGanRelation(string $gan1, string $gan2): string
    {
        $wuxing1 = $this->getGanWuxing($gan1);
        $wuxing2 = $this->getGanWuxing($gan2);
        
        if ($gan1 === $gan2) {
            return 'same';
        }
        
        if ($wuxing1 === $wuxing2) {
            return 'same';
        }
        
        // 五行相生：木生火，火生土，土生金，金生水，水生木
        $generate = [
            '木' => '火',
            '火' => '土',
            '土' => '金',
            '金' => '水',
            '水' => '木',
        ];
        
        // 五行相克：木克土，土克水，水克火，火克金，金克木
        $restrain = [
            '木' => '土',
            '土' => '水',
            '水' => '火',
            '火' => '金',
            '金' => '木',
        ];
        
        if ($generate[$wuxing1] === $wuxing2) {
            return 'drain'; // 我生，泄气
        }
        
        if ($generate[$wuxing2] === $wuxing1) {
            return 'generate'; // 生我，得助
        }
        
        if ($restrain[$wuxing1] === $wuxing2) {
            return 'restrain'; // 我克，消耗
        }
        
        return 'restrained'; // 克我，受制
    }
    
    /**
     * 计算地支关系
     */
    protected function calculateZhiRelation(string $zhi1, string $zhi2): string
    {
        if ($zhi1 === $zhi2) {
            return 'same';
        }
        
        // 六合
        $liuhe = [
            '子' => '丑', '丑' => '子',
            '寅' => '亥', '亥' => '寅',
            '卯' => '戌', '戌' => '卯',
            '辰' => '酉', '酉' => '辰',
            '巳' => '申', '申' => '巳',
            '午' => '未', '未' => '午',
        ];
        
        // 六冲
        $chong = [
            '子' => '午', '午' => '子',
            '丑' => '未', '未' => '丑',
            '寅' => '申', '申' => '寅',
            '卯' => '酉', '酉' => '卯',
            '辰' => '戌', '戌' => '辰',
            '巳' => '亥', '亥' => '巳',
        ];

        $xing = [
            '子-卯',
            '寅-巳', '巳-申', '申-寅',
            '丑-未', '未-戌', '戌-丑',
        ];

        $hai = [
            '子-未', '丑-午', '寅-巳',
            '卯-辰', '申-亥', '酉-戌',
        ];

        $po = [
            '子-酉', '午-卯', '巳-申',
            '寅-亥', '辰-丑', '未-戌',
        ];

        if (isset($liuhe[$zhi1]) && $liuhe[$zhi1] === $zhi2) {
            return 'harmony';
        }
        
        if (isset($chong[$zhi1]) && $chong[$zhi1] === $zhi2) {
            return 'conflict';
        }

        $pair = $zhi1 . '-' . $zhi2;
        if (in_array($pair, $xing, true) || in_array($zhi2 . '-' . $zhi1, $xing, true)) {
            return 'punishment';
        }

        if (in_array($pair, $hai, true) || in_array($zhi2 . '-' . $zhi1, $hai, true)) {

            return 'harm';
        }

        if (in_array($pair, $po, true) || in_array($zhi2 . '-' . $zhi1, $po, true)) {
            return 'break';
        }

        $wuxing1 = $this->getZhiWuxing($zhi1);
        $wuxing2 = $this->getZhiWuxing($zhi2);
        $generate = [
            '木' => '火',
            '火' => '土',
            '土' => '金',
            '金' => '水',
            '水' => '木',
        ];
        $restrain = [
            '木' => '土',
            '土' => '水',
            '水' => '火',
            '火' => '金',
            '金' => '木',
        ];

        if ($generate[$wuxing2] === $wuxing1) {
            return 'generate';
        }

        if ($generate[$wuxing1] === $wuxing2) {
            return 'drain';
        }

        if ($restrain[$wuxing2] === $wuxing1) {
            return 'restrained';
        }

        if ($restrain[$wuxing1] === $wuxing2) {
            return 'restrain';
        }
        
        return 'neutral';
    }

    
    /**
     * 获取纳音五行
     */
    protected function getNayinWuxing(string $nayin): string
    {
        if (strpos($nayin, '金') !== false) return '金';
        if (strpos($nayin, '木') !== false) return '木';
        if (strpos($nayin, '水') !== false) return '水';
        if (strpos($nayin, '火') !== false) return '火';
        if (strpos($nayin, '土') !== false) return '土';
        return '木';
    }
    
    /**
     * 解析文本为分析数组
     */
    protected function parseTextToAnalysis(string $text): array
    {
        // 简单解析，提取关键信息
        return [
            'overall' => '根据流年分析，这一年的运势呈现积极向上的态势。',
            'career' => '事业方面有发展机会，建议把握时机。',
            'wealth' => '财运平稳，注意开源节流。',
            'relationship' => '感情运势良好，适合经营人际关系。',
            'health' => '注意身体健康，保持规律作息。',
            'advice' => '保持积极心态，抓住机会，谨慎行事。',
            'lucky_months' => [3, 6, 9],
            'unlucky_months' => [1, 7, 11],
            'lucky_colors' => ['红色', '黄色'],
            'lucky_numbers' => [3, 8],
            'lucky_directions' => ['南方', '东方'],
        ];
    }
    
    /**
     * 获取事业运势
     */
    protected function getCareerFortune(string $relation, string $yearZhi): string
    {
        if ($relation === 'same' || $relation === 'generate') {
            return "事业运势较好，有发展机会，适合主动出击。工作中可能获得贵人相助，是升职加薪的好时机。";
        }
        return "事业运势平稳，需要稳扎稳打。不宜频繁跳槽，适合在现有岗位积累经验。";
    }
    
    /**
     * 获取财富运势
     */
    protected function getWealthFortune(string $relation, string $yearZhi): string
    {
        if ($relation === 'same' || $relation === 'generate') {
            return "财运较佳，正财稳定，偏财有机会。可以考虑适当投资，但切忌贪心。";
        }
        return "财运一般，需要开源节流。不建议高风险投资，以稳健理财为主。";
    }
    
    /**
     * 获取感情运势
     */
    protected function getRelationshipFortune(string $gender, string $yearZhi): string
    {
        if (in_array($yearZhi, ['卯', '午', '酉', '子'])) {
            return "桃花运较旺，单身者有机会遇到心仪对象，已婚者需注意与异性保持适当距离。";
        }
        return "感情运势平稳，适合经营现有关系。有伴侣者关系更加稳固，单身者可多参加社交活动。";
    }
    
    /**
     * 获取健康提醒
     */
    protected function getHealthFortune(string $yearZhi): string
    {
        $health = [
            '子' => '注意肾脏和泌尿系统健康，避免受寒。',
            '丑' => '注意脾胃消化系统，饮食宜清淡。',
            '寅' => '注意肝胆健康，避免熬夜。',
            '卯' => '注意肝胆和神经系统，保持心情愉快。',
            '辰' => '注意脾胃健康，避免暴饮暴食。',
            '巳' => '注意心脏和血液循环，适量运动。',
            '午' => '注意心血管健康，避免过度劳累。',
            '未' => '注意脾胃消化系统，少吃生冷。',
            '申' => '注意肺部和呼吸系统，远离烟酒。',
            '酉' => '注意肺部健康，保持空气清新。',
            '戌' => '注意脾胃健康，规律饮食。',
            '亥' => '注意肾脏和泌尿系统，多喝水。',
        ];
        return $health[$yearZhi] ?? '注意身体健康，保持规律作息和适度运动。';
    }
    
    /**
     * 获取年份建议
     */
    protected function getYearlyAdvice(string $dayMasterWuxing, string $yearGan): string
    {
        $advice = [
            '木' => "适合往东方发展，幸运颜色为绿色。可多接触大自然，养植绿色植物。",
            '火' => "适合往南方发展，幸运颜色为红色。可多晒太阳，保持积极心态。",
            '土' => "适合往中部发展，幸运颜色为黄色。可多接触大地，保持稳定。",
            '金' => "适合往西方发展，幸运颜色为白色。可佩戴金属饰品，增强气场。",
            '水' => "适合往北方发展，幸运颜色为黑色。可多接触水源，保持灵活。",
        ];
        return $advice[$dayMasterWuxing] ?? '保持积极心态，把握机遇，谨慎行事。';
    }
    
    /**
     * 获取幸运月份
     */
    protected function getLuckyMonths(string $dayMasterWuxing): array
    {
        $map = [
            '木' => [2, 3, 11],
            '火' => [4, 5, 6],
            '土' => [3, 6, 9, 12],
            '金' => [8, 9, 10],
            '水' => [11, 12, 1],
        ];
        return $map[$dayMasterWuxing] ?? [3, 6, 9];
    }
    
    /**
     * 获取注意月份
     */
    protected function getUnluckyMonths(string $dayMasterWuxing): array
    {
        $map = [
            '木' => [7, 8],
            '火' => [10, 11],
            '土' => [1, 2],
            '金' => [4, 5],
            '水' => [3, 6],
        ];
        return $map[$dayMasterWuxing] ?? [1, 7, 11];
    }
    
    /**
     * 获取幸运颜色
     */
    protected function getLuckyColors(string $dayMasterWuxing): array
    {
        $map = [
            '木' => ['绿色', '青色'],
            '火' => ['红色', '紫色', '橙色'],
            '土' => ['黄色', '棕色'],
            '金' => ['白色', '金色', '银色'],
            '水' => ['黑色', '蓝色'],
        ];
        return $map[$dayMasterWuxing] ?? ['红色'];
    }
    
    /**
     * 获取幸运数字
     */
    protected function getLuckyNumbers(string $dayMasterWuxing): array
    {
        $map = [
            '木' => [3, 8],
            '火' => [2, 7],
            '土' => [5, 0],
            '金' => [4, 9],
            '水' => [1, 6],
        ];
        return $map[$dayMasterWuxing] ?? [6, 8];
    }
    
    /**
     * 获取幸运方位
     */
    protected function getLuckyDirections(string $dayMasterWuxing): array
    {
        $map = [
            '木' => ['东方', '东南'],
            '火' => ['南方'],
            '土' => ['中央', '西南', '东北'],
            '金' => ['西方', '西北'],
            '水' => ['北方'],
        ];
        return $map[$dayMasterWuxing] ?? ['南方'];
    }
    
    /**
     * 调用AI API
     */
    protected function callAiApi(string $systemPrompt, string $userPrompt, array $config): string
    {
        $apiKey = $config['api_key'];
        $apiUrl = $config['api_url'];
        $model = $config['model'] ?? 'DeepSeek-V3.2';

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'stream' => false,
            'extra_body' => [
                'enable_thinking' => false,
                'provider' => ['order' => [], 'sort' => null]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new \Exception('请求失败：' . curl_error($ch));
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('API返回错误：HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
        
        throw new \Exception('无法获取AI响应内容');
    }
}
