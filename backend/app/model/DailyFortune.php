<?php
declare(strict_types=1);

namespace app\model;

use app\service\LunarService;
use think\Model;

class DailyFortune extends Model
{
    private const ZHIRI_SCORE_MAP = [
        '建' => 74,
        '除' => 80,
        '满' => 78,
        '平' => 68,
        '定' => 84,
        '执' => 72,
        '破' => 52,
        '危' => 58,
        '成' => 88,
        '收' => 70,
        '开' => 90,
        '闭' => 56,
    ];

    private const LEGACY_DESCRIPTIONS = [
        'career_desc' => ['事业运势较好，工作顺利', '事业运势平稳，保持现状'],
        'wealth_desc' => ['财运不错，有意外收获', '财运一般，谨慎理财'],
        'love_desc' => ['感情运势甜蜜，桃花旺', '感情运势平淡，需要主动'],
        'health_desc' => ['健康状况良好', '健康状况一般，注意休息'],
    ];

    private const ASPECT_KEYWORDS = [
        'career' => [
            'positive' => ['赴任', '开市', '交易', '签约', '上任', '出行'],
            'negative' => ['安葬', '动土', '诉讼'],
        ],
        'wealth' => [
            'positive' => ['交易', '纳财', '开市', '立券', '求财'],
            'negative' => ['开仓', '破土', '安葬'],
        ],
        'love' => [
            'positive' => ['嫁娶', '求嗣', '祈福', '会友'],
            'negative' => ['诉讼', '安葬', '破屋'],
        ],
        'health' => [
            'positive' => ['祈福', '祭祀', '求医', '沐浴'],
            'negative' => ['动土', '安葬', '远行'],
        ],
    ];

    protected $table = 'tc_daily_fortune';
    protected $pk = 'id';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $schema = [
        'id' => 'int',
        'date' => 'string',
        'lunar_date' => 'string',
        'overall_score' => 'int',
        'summary' => 'string',
        'career_score' => 'int',
        'career_desc' => 'string',
        'wealth_score' => 'int',
        'wealth_desc' => 'string',
        'love_score' => 'int',
        'love_desc' => 'string',
        'health_score' => 'int',
        'health_desc' => 'string',
        'yi' => 'string',
        'ji' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * 获取指定日期的运势
     */
    public static function getByDate(string $date): ?self
    {
        return self::where('date', $date)->find();
    }
    
    /**
     * 获取今日运势（如果没有则生成）
     */
    public static function getToday(): self
    {
        $today = date('Y-m-d');
        $fortune = self::where('date', $today)->find();
        
        if (!$fortune) {
            return self::generateFortune($today);
        }

        $pending = [];
        $expectedLunarDate = self::solarToLunar($today);
        if ($fortune->lunar_date !== $expectedLunarDate) {
            $pending['lunar_date'] = $expectedLunarDate;
        }

        if (self::shouldRefreshGeneratedRecord($fortune)) {
            $generated = self::buildFortunePayload($today);
            foreach ([
                'lunar_date',
                'overall_score',
                'summary',
                'career_score',
                'career_desc',
                'wealth_score',
                'wealth_desc',
                'love_score',
                'love_desc',
                'health_score',
                'health_desc',
                'yi',
                'ji',
            ] as $field) {
                $currentValue = (string) ($fortune->{$field} ?? '');
                $targetValue = (string) ($generated[$field] ?? '');
                if ($currentValue !== $targetValue) {
                    $pending[$field] = $generated[$field];
                }
            }
        }

        if (!empty($pending)) {
            $fortune->save($pending);
            $fortune = self::where('id', $fortune->id)->find() ?? $fortune;
        }
        
        return $fortune;
    }
    
    /**
     * 生成运势
     */
    protected static function generateFortune(string $date): self
    {
        return self::create(self::buildFortunePayload($date));
    }

    protected static function buildFortunePayload(string $date): array
    {
        $almanac = LunarService::solarToLunar($date);
        $yi = self::normalizeActivities($almanac['yi'] ?? ['出行', '祈福']);
        $ji = self::normalizeActivities($almanac['ji'] ?? ['动土']);

        $overallBase = self::calculateOverallBase($date, $almanac, $yi, $ji);
        $overallScore = self::stableScore($date, 'overall', $overallBase, 4, 60, 95);
        $careerScore = self::calculateAspectScore($date, 'career', $overallScore, $almanac, $yi, $ji);
        $wealthScore = self::calculateAspectScore($date, 'wealth', $overallScore, $almanac, $yi, $ji);
        $loveScore = self::calculateAspectScore($date, 'love', $overallScore, $almanac, $yi, $ji);
        $healthScore = self::calculateAspectScore($date, 'health', $overallScore, $almanac, $yi, $ji);

        return [
            'date' => $date,
            'lunar_date' => self::solarToLunar($date),
            'overall_score' => $overallScore,
            'summary' => self::getSummaryByScore($overallScore),
            'career_score' => $careerScore,
            'career_desc' => self::getAspectDescription('career', $careerScore),
            'wealth_score' => $wealthScore,
            'wealth_desc' => self::getAspectDescription('wealth', $wealthScore),
            'love_score' => $loveScore,
            'love_desc' => self::getAspectDescription('love', $loveScore),
            'health_score' => $healthScore,
            'health_desc' => self::getAspectDescription('health', $healthScore),
            'yi' => implode(',', $yi),
            'ji' => implode(',', $ji),
        ];
    }

    protected static function shouldRefreshGeneratedRecord(self $fortune): bool
    {
        foreach (self::LEGACY_DESCRIPTIONS as $field => $legacyValues) {
            $value = trim((string) ($fortune->{$field} ?? ''));
            if ($value === '' || !in_array($value, $legacyValues, true)) {
                return false;
            }
        }

        return true;
    }

    protected static function calculateOverallBase(string $date, array $almanac, array $yi, array $ji): int
    {
        $zhiri = (string) ($almanac['zhiri'] ?? '平');
        $base = self::ZHIRI_SCORE_MAP[$zhiri] ?? self::ZHIRI_SCORE_MAP['平'];
        $jishenCount = count(self::normalizeActivities($almanac['jishen'] ?? []));
        $xiongshaCount = count(self::normalizeActivities($almanac['xiongsha'] ?? []));

        $base += min(6, count($yi));
        $base -= min(6, count($ji));
        $base += min(3, $jishenCount) * 2;
        $base -= min(3, $xiongshaCount) * 2;

        return self::stableScore($date, 'base', $base, 2, 58, 92);
    }

    protected static function calculateAspectScore(string $date, string $aspect, int $overallScore, array $almanac, array $yi, array $ji): int
    {
        $biasMap = [
            'career' => 2,
            'wealth' => 0,
            'love' => -1,
            'health' => 1,
        ];

        $context = implode('|', [
            $date,
            $aspect,
            (string) ($almanac['day_gan_zhi'] ?? ''),
            (string) ($almanac['zhiri'] ?? ''),
            implode(',', $yi),
            implode(',', $ji),
        ]);

        $keywordImpact = self::calculateKeywordImpact($aspect, $yi, $ji);
        $seedOffset = self::stableOffset($context, 9) - 4;
        $score = $overallScore + ($biasMap[$aspect] ?? 0) + $keywordImpact + $seedOffset;

        return self::clampScore($score, 48, 98);
    }

    protected static function calculateKeywordImpact(string $aspect, array $yi, array $ji): int
    {
        $keywords = self::ASPECT_KEYWORDS[$aspect] ?? ['positive' => [], 'negative' => []];
        $positiveHits = self::countKeywordHits($yi, $keywords['positive']);
        $negativeHits = self::countKeywordHits($ji, $keywords['negative']);

        return min(8, $positiveHits * 3) - min(8, $negativeHits * 4);
    }

    protected static function countKeywordHits(array $source, array $keywords): int
    {
        $hits = 0;
        foreach ($source as $value) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($value, $keyword) !== false) {
                    $hits++;
                    break;
                }
            }
        }

        return $hits;
    }

    protected static function normalizeActivities($value): array
    {
        if (is_string($value)) {
            $value = array_filter(explode(',', $value));
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn($item): string => trim((string) $item),
            $value
        ), static fn(string $item): bool => $item !== ''));
    }

    protected static function stableScore(string $date, string $salt, int $base, int $variance, int $min, int $max): int
    {
        $offset = self::stableOffset($date . '|' . $salt, $variance * 2 + 1) - $variance;
        return self::clampScore($base + $offset, $min, $max);
    }

    protected static function stableOffset(string $seed, int $mod): int
    {
        if ($mod <= 0) {
            return 0;
        }

        return self::stableHash($seed) % $mod;
    }

    protected static function stableHash(string $seed): int
    {
        return (int) sprintf('%u', crc32($seed));
    }

    protected static function clampScore(int|float $score, int $min, int $max): int
    {
        return max($min, min($max, (int) round($score)));
    }

    protected static function getAspectDescription(string $aspect, int $score): string
    {
        return match ($aspect) {
            'career' => self::describeCareer($score),
            'wealth' => self::describeWealth($score),
            'love' => self::describeLove($score),
            'health' => self::describeHealth($score),
            default => self::getSummaryByScore($score),
        };
    }

    protected static function describeCareer(int $score): string
    {
        if ($score >= 85) {
            return '事业运势走强，适合推进关键事务，求职汇报更容易见到回音。';
        }
        if ($score >= 70) {
            return '事业运势稳中有进，按计划推进更容易见效。';
        }
        if ($score >= 60) {
            return '事业运势平稳，先把节奏放稳，避免贪多求快。';
        }

        return '事业运势偏谨慎，重要决定宜复核，避免硬碰硬。';
    }

    protected static function describeWealth(int $score): string
    {
        if ($score >= 85) {
            return '财运活跃，适合谈单回款与稳健开源，但仍要见好就收。';
        }
        if ($score >= 70) {
            return '财运稳中有升，正财表现更可靠，支出宜有计划。';
        }
        if ($score >= 60) {
            return '财运平平，以守为主，别被一时冲动带节奏。';
        }

        return '财运偏弱，投资借贷宜谨慎，先守住现金流。';
    }

    protected static function describeLove(int $score): string
    {
        if ($score >= 85) {
            return '感情气场顺畅，单身者容易有回应，有伴者适合主动升温。';
        }
        if ($score >= 70) {
            return '感情运势和缓，真诚表达比试探更有效。';
        }
        if ($score >= 60) {
            return '感情运势平稳，宜多沟通少猜测，别把情绪闷着。';
        }

        return '感情运势偏弱，容易因小事起波动，先稳住语气与节奏。';
    }

    protected static function describeHealth(int $score): string
    {
        if ($score >= 85) {
            return '健康状态在线，作息规律时恢复力和精力都不错。';
        }
        if ($score >= 70) {
            return '健康运势平稳，保持节律即可，别透支体力。';
        }
        if ($score >= 60) {
            return '健康方面要多留意，尤其别熬夜，饮食宜清淡。';
        }

        return '健康运势偏弱，宜减少劳累与冒进，必要时及时休息检查。';
    }

    
    /**
     * 公历转农历（使用统一农历服务）
     */
    protected static function solarToLunar(string $date): string
    {
        $lunar = LunarService::solarToLunar($date);
        if (!empty($lunar['lunar_text'])) {
            return (string) $lunar['lunar_text'];
        }

        $month = self::formatLunarMonth((int)($lunar['lunar_month'] ?? 1), (bool)($lunar['is_leap'] ?? false));
        $day = self::formatLunarDay((int)($lunar['lunar_day'] ?? 1));
        $yearGanZhi = $lunar['year_gan_zhi'] ?? '';

        return trim("{$yearGanZhi}年 {$month}{$day}");
    }

    protected static function formatLunarMonth(int $month, bool $isLeap = false): string
    {
        $months = [1 => '正月', 2 => '二月', 3 => '三月', 4 => '四月', 5 => '五月', 6 => '六月', 7 => '七月', 8 => '八月', 9 => '九月', 10 => '十月', 11 => '冬月', 12 => '腊月'];
        $monthText = $months[$month] ?? ($month . '月');

        return $isLeap ? '闰' . $monthText : $monthText;
    }

    protected static function formatLunarDay(int $day): string
    {
        $days = [
            1 => '初一', 2 => '初二', 3 => '初三', 4 => '初四', 5 => '初五',
            6 => '初六', 7 => '初七', 8 => '初八', 9 => '初九', 10 => '初十',
            11 => '十一', 12 => '十二', 13 => '十三', 14 => '十四', 15 => '十五',
            16 => '十六', 17 => '十七', 18 => '十八', 19 => '十九', 20 => '二十',
            21 => '廿一', 22 => '廿二', 23 => '廿三', 24 => '廿四', 25 => '廿五',
            26 => '廿六', 27 => '廿七', 28 => '廿八', 29 => '廿九', 30 => '三十',
        ];

        return $days[$day] ?? ((string)$day);
    }
    
    /**
     * 根据分数获取运势摘要
     */
    protected static function getSummaryByScore(int $score): string
    {
        if ($score >= 90) return '今日运势极佳，万事顺遂，把握机会！';
        if ($score >= 80) return '今日运势良好，积极进取，收获颇丰。';
        if ($score >= 70) return '今日运势平稳，稳扎稳打，循序渐进。';
        if ($score >= 60) return '今日运势一般，保持谨慎，静待时机。';
        return '今日运势欠佳，低调行事，以守为攻。';
    }
}
