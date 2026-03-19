<?php
declare(strict_types=1);

namespace app\model;

use app\service\LunarService;
use app\service\SchemaInspector;
use think\facade\Db;
use think\Model;

class DailyFortune extends Model
{
    private const TABLE_NAME = 'tc_daily_fortune';
    private const STORAGE_MARKER_USER_ID = 0;
    private const STORAGE_MARKER_TYPE = 'daily';
    private const SNAPSHOT_FIELDS = [
        'date',
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
    ];

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

    private const GAN_WUXING = [
        '甲' => '木', '乙' => '木',
        '丙' => '火', '丁' => '火',
        '戊' => '土', '己' => '土',
        '庚' => '金', '辛' => '金',
        '壬' => '水', '癸' => '水',
    ];

    private const ASPECT_TONE_MAP = [
        'career' => [
            'strong' => '事业气场走强，适合把关键节点往前推',
            'good' => '事业节奏顺手，既定安排更容易见到反馈',
            'steady' => '事业盘面平稳，先稳住优先级再逐项处理',
            'cautious' => '事业阻力偏多，重要决定先复核细节与节奏',
        ],
        'wealth' => [
            'strong' => '财气偏旺，适合抓住回款、成交和资源兑现窗口',
            'good' => '财务节奏尚稳，正财比偏财更值得发力',
            'steady' => '财运以守成为主，先把预算和节奏看紧',
            'cautious' => '财务波动偏明显，先守现金流再谈额外收益',
        ],
        'love' => [
            'strong' => '感情气场有升温迹象，表达与回应都更容易接得住',
            'good' => '情感互动趋于柔和，真诚沟通比试探更有效',
            'steady' => '关系节奏平稳，宜慢一点，把话说透比说满更重要',
            'cautious' => '情绪面较敏感，先稳住语气，别让小事滚成误会',
        ],
        'health' => [
            'strong' => '身体状态在线，恢复力和执行力都比较跟手',
            'good' => '健康节律总体平稳，维持规律作息就能守住状态',
            'steady' => '身体层面宜做减法，先把休息、饮食和补水照顾好',
            'cautious' => '精力消耗偏快，今天更需要主动留白与及时收力',
        ],
    ];

    private const ASPECT_ELEMENT_GUIDANCE = [
        'career' => [
            '木' => '日干属木，利策划沟通、开思路与发起讨论',
            '火' => '日干属火，利展示成果、争取曝光与主动表态',
            '土' => '日干属土，利落地执行、排清单与稳住协同',
            '金' => '日干属金，利审合同、抓标准与做关键取舍',
            '水' => '日干属水，利调研复盘、整合信息与柔性推进',
        ],
        'wealth' => [
            '木' => '木气生发，适合拓展渠道、盘活客源与寻找新增量',
            '火' => '火气外放，利谈曝光、促成交，但别被气氛带着超配',
            '土' => '土气偏实，更适合对账、催收和把预算落到实处',
            '金' => '金气收敛，适合控成本、做审价和优化投入产出比',
            '水' => '水气流动，利信息差与资源调度，但收益兑现要看节奏',
        ],
        'love' => [
            '木' => '木气向上，关系里适合先释出善意、把心意说清',
            '火' => '火气偏盛，热度容易上来，表达可以主动但别过火',
            '土' => '土气安定，适合谈承诺、聊现实安排与建立安全感',
            '金' => '金气偏直，沟通宜讲分寸，少一点锋芒会更顺',
            '水' => '水气柔和，适合倾听、共情与给彼此留出回旋余地',
        ],
        'health' => [
            '木' => '木气主舒展，适合拉伸筋骨、疏肝解郁与户外透气',
            '火' => '火气偏旺，注意心火上浮，饮食作息别再叠加刺激',
            '土' => '土气偏重，脾胃与消化负担要少一点，多做清淡调整',
            '金' => '金气偏肃，呼吸道与皮肤干燥感值得提前留意',
            '水' => '水气偏寒，注意保暖、补水和睡眠恢复，别硬扛疲劳',
        ],
    ];

    private const ZHIRI_GUIDANCE = [
        '建' => '建日宜起步布局，但别一次把摊子铺得太大',
        '除' => '除日利清障减负，先处理积压再开新题更顺',
        '满' => '满日重在充实积累，适合补足条件、把准备做满',
        '平' => '平日讲究稳中求进，节奏均衡比猛冲更重要',
        '定' => '定日利定方案、定边界、定承诺，适合把关键事项坐实',
        '执' => '执日适合持续跟进，但别因为执念忽略现实反馈',
        '破' => '破日宜拆旧局、改错漏，不宜硬推高风险决定',
        '危' => '危日先控风险，重要动作要把预案和边界放在前面',
        '成' => '成日利收成果、推动落地，适合把已有机会转成结果',
        '收' => '收日利回收资源、盘点得失，先收口再扩张更稳',
        '开' => '开日利开局、开张与对外互动，但承诺仍要留余地',
        '闭' => '闭日宜收敛养精，减少无效折腾与额外消耗',
    ];


    protected $table = self::TABLE_NAME;
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $schema = [
        'id' => 'int',
        'user_id' => 'int',
        'date' => 'string',
        'fortune_type' => 'string',
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
        'fortune_score' => 'int',
        'fortune_desc' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 获取指定日期的运势
     */
    public static function getByDate(string $date): ?self
    {
        if (!self::canQueryStorage()) {
            return null;
        }

        return self::buildStorageQuery($date)->find();
    }

    /**
     * 获取今日运势（如果没有则生成）
     */
    public static function getToday(): self
    {
        $today = date('Y-m-d');
        $generated = self::buildFortunePayload($today);

        if (!self::supportsPersistentSnapshot()) {
            return self::fromPayload($generated);
        }

        $fortune = self::getByDate($today);
        if (!$fortune) {
            if (!self::persistSnapshot($generated)) {
                return self::fromPayload($generated);
            }

            return self::getByDate($today) ?? self::fromPayload($generated);
        }

        $pending = self::collectPendingSnapshotUpdates($fortune, $generated);
        if (!empty($pending)) {
            self::persistSnapshot($pending, (int) ($fortune->id ?? 0));
            $fortune = self::getByDate($today) ?? self::fromPayload(array_merge($generated, $pending));
        }

        return $fortune;
    }

    /**
     * 生成运势
     */
    protected static function generateFortune(string $date): self
    {
        $payload = self::buildFortunePayload($date);

        if (self::supportsPersistentSnapshot() && self::persistSnapshot($payload)) {
            return self::getByDate($date) ?? self::fromPayload($payload);
        }

        return self::fromPayload($payload);
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
            'career_desc' => self::getAspectDescription('career', $careerScore, $almanac, $yi, $ji),
            'wealth_score' => $wealthScore,
            'wealth_desc' => self::getAspectDescription('wealth', $wealthScore, $almanac, $yi, $ji),
            'love_score' => $loveScore,
            'love_desc' => self::getAspectDescription('love', $loveScore, $almanac, $yi, $ji),
            'health_score' => $healthScore,
            'health_desc' => self::getAspectDescription('health', $healthScore, $almanac, $yi, $ji),

            'yi' => implode(',', $yi),
            'ji' => implode(',', $ji),
        ];
    }

    protected static function supportsPersistentSnapshot(): bool
    {
        $columns = self::getTableColumns();
        if (empty($columns)) {
            return false;
        }

        foreach (self::SNAPSHOT_FIELDS as $field) {
            if (!isset($columns[$field])) {
                return false;
            }
        }

        return true;
    }

    protected static function canQueryStorage(): bool
    {
        return isset(self::getTableColumns()['date']);
    }

    protected static function getTableColumns(): array
    {
        if (!SchemaInspector::tableExists(self::TABLE_NAME)) {
            return [];
        }

        return SchemaInspector::getTableColumns(self::TABLE_NAME);
    }

    protected static function buildStorageQuery(?string $date = null)
    {
        $query = self::where([]);
        $columns = self::getTableColumns();

        if ($date !== null) {
            $query->where('date', $date);
        }
        if (isset($columns['fortune_type'])) {
            $query->where('fortune_type', self::STORAGE_MARKER_TYPE);
        }
        if (isset($columns['user_id'])) {
            $query->where('user_id', self::STORAGE_MARKER_USER_ID);
        }

        return $query;
    }

    protected static function persistSnapshot(array $payload, int $id = 0): bool
    {
        if (!self::supportsPersistentSnapshot()) {
            return false;
        }

        $columns = self::getTableColumns();
        $now = date('Y-m-d H:i:s');
        $storagePayload = self::buildStoragePayload($payload, $columns, $now, $id <= 0);

        try {
            if ($id > 0 && isset($columns['id'])) {
                return Db::name(self::TABLE_NAME)->where('id', $id)->update($storagePayload) !== false;
            }

            return Db::name(self::TABLE_NAME)->insert($storagePayload) !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected static function buildStoragePayload(array $payload, array $columns, string $now, bool $isCreate): array
    {
        $storagePayload = [];

        foreach (self::SNAPSHOT_FIELDS as $field) {
            if (isset($columns[$field]) && array_key_exists($field, $payload)) {
                $storagePayload[$field] = $payload[$field];
            }
        }

        if (isset($columns['user_id'])) {
            $storagePayload['user_id'] = self::STORAGE_MARKER_USER_ID;
        }
        if (isset($columns['fortune_type'])) {
            $storagePayload['fortune_type'] = self::STORAGE_MARKER_TYPE;
        }
        if (isset($columns['fortune_score']) && isset($payload['overall_score'])) {
            $storagePayload['fortune_score'] = (int) $payload['overall_score'];
        }
        if (isset($columns['fortune_desc']) && isset($payload['summary'])) {
            $storagePayload['fortune_desc'] = (string) $payload['summary'];
        }
        if ($isCreate && isset($columns['created_at'])) {
            $storagePayload['created_at'] = $now;
        }
        if (isset($columns['updated_at'])) {
            $storagePayload['updated_at'] = $now;
        }

        return $storagePayload;
    }

    protected static function fromPayload(array $payload): self
    {
        return new self($payload);
    }

    protected static function collectPendingSnapshotUpdates(self $fortune, array $generated): array
    {
        $pending = [];

        $expectedLunarDate = self::solarToLunar((string) ($generated['date'] ?? date('Y-m-d')));
        if ((string) ($fortune->lunar_date ?? '') !== $expectedLunarDate) {
            $pending['lunar_date'] = $expectedLunarDate;
        }

        if (self::shouldRefreshGeneratedRecord($fortune, $generated) || self::hasIncompleteSnapshot($fortune)) {
            foreach (self::SNAPSHOT_FIELDS as $field) {
                $currentValue = (string) ($fortune->{$field} ?? '');
                $targetValue = (string) ($generated[$field] ?? '');
                if ($currentValue !== $targetValue) {
                    $pending[$field] = $generated[$field];
                }
            }
        }


        return $pending;
    }

    protected static function hasIncompleteSnapshot(self $fortune): bool
    {
        foreach (['summary', 'career_desc', 'wealth_desc', 'love_desc', 'health_desc', 'yi', 'ji'] as $field) {
            if (trim((string) ($fortune->{$field} ?? '')) === '') {
                return true;
            }
        }

        foreach (['overall_score', 'career_score', 'wealth_score', 'love_score', 'health_score'] as $field) {
            if ((int) ($fortune->{$field} ?? 0) <= 0) {
                return true;
            }
        }

        return false;
    }

    protected static function shouldRefreshGeneratedRecord(self $fortune, array $generated = []): bool
    {
        foreach (self::LEGACY_DESCRIPTIONS as $field => $legacyValues) {
            $value = trim((string) ($fortune->{$field} ?? ''));
            if ($value !== '' && in_array($value, $legacyValues, true)) {
                return true;
            }
        }

        foreach (self::SNAPSHOT_FIELDS as $field) {
            if (!array_key_exists($field, $generated)) {
                continue;
            }

            $currentValue = (string) ($fortune->{$field} ?? '');
            $targetValue = (string) ($generated[$field] ?? '');
            if ($targetValue !== '' && $currentValue !== $targetValue) {
                return true;
            }
        }

        return false;
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

    protected static function getAspectDescription(string $aspect, int $score, array $almanac = [], array $yi = [], array $ji = []): string
    {
        $segments = array_values(array_filter([
            self::buildAspectTone($aspect, $score),
            self::buildDayGuidance($aspect, $almanac),
            self::buildActivityGuidance($aspect, $yi, $ji, $almanac),
        ], static fn(string $segment): bool => trim($segment) !== ''));

        if (empty($segments)) {
            return self::getSummaryByScore($score);
        }

        return implode('；', $segments) . '。';
    }

    protected static function buildAspectTone(string $aspect, int $score): string
    {
        $band = self::resolveScoreBand($score);
        return self::ASPECT_TONE_MAP[$aspect][$band] ?? self::getSummaryByScore($score);
    }

    protected static function resolveScoreBand(int $score): string
    {
        if ($score >= 85) {
            return 'strong';
        }
        if ($score >= 70) {
            return 'good';
        }
        if ($score >= 60) {
            return 'steady';
        }

        return 'cautious';
    }

    protected static function buildDayGuidance(string $aspect, array $almanac): string
    {
        $dayGanZhi = trim((string) ($almanac['day_gan_zhi'] ?? ''));
        $zhiri = trim((string) ($almanac['zhiri'] ?? ''));
        $dayGan = $dayGanZhi !== '' ? mb_substr($dayGanZhi, 0, 1) : '';
        $dayWuxing = self::GAN_WUXING[$dayGan] ?? '';
        $elementGuide = self::ASPECT_ELEMENT_GUIDANCE[$aspect][$dayWuxing] ?? '';
        $zhiriGuide = self::ZHIRI_GUIDANCE[$zhiri] ?? '';

        $segments = [];
        if ($dayGanZhi !== '' && $elementGuide !== '') {
            $segments[] = "{$dayGanZhi}日里，{$elementGuide}";
        } elseif ($elementGuide !== '') {
            $segments[] = $elementGuide;
        }

        if ($zhiriGuide !== '') {
            $segments[] = $zhiriGuide;
        }

        return implode('，', $segments);
    }

    protected static function buildActivityGuidance(string $aspect, array $yi, array $ji, array $almanac): string
    {
        $keywords = self::ASPECT_KEYWORDS[$aspect] ?? ['positive' => [], 'negative' => []];
        $positiveMatches = self::extractMatchedActivities($yi, $keywords['positive']);
        $negativeMatches = self::extractMatchedActivities($ji, $keywords['negative']);

        $segments = [];
        if (!empty($positiveMatches)) {
            $segments[] = self::buildPositiveActivityGuidance($aspect, $positiveMatches);
        }
        if (!empty($negativeMatches)) {
            $segments[] = self::buildNegativeActivityGuidance($aspect, $negativeMatches);
        }
        if (empty($segments)) {
            $segments[] = self::buildFallbackActivityGuidance($aspect, $almanac);
        }

        return implode('，', array_filter($segments));
    }

    protected static function buildPositiveActivityGuidance(string $aspect, array $matched): string
    {
        $items = self::formatActivityList($matched);

        return match ($aspect) {
            'career' => "黄历宜{$items}，适合先清积压、修方案，再推进汇报签批",
            'wealth' => "黄历宜{$items}，正财回款、对账催收和稳健谈单更占上风",
            'love' => "黄历宜{$items}，适合约见、表态和修复关系温度",
            'health' => "黄历宜{$items}，体检调理、作息修复和轻运动更容易见效",
            default => "黄历宜{$items}，按顺势而为更容易省力",
        };
    }

    protected static function buildNegativeActivityGuidance(string $aspect, array $matched): string
    {
        $items = self::formatActivityList($matched);

        return match ($aspect) {
            'career' => "忌{$items}，临时拍板、贸然出差和口头承诺要多留缓冲",
            'wealth' => "忌{$items}，冲动消费、超预算采购与高波动投机先收一收",
            'love' => "忌{$items}，沟通里别翻旧账，也别把情绪顶到台面",
            'health' => "忌{$items}，过度劳累、奔波与作息失衡要尽量减少",
            default => "忌{$items}，今天先避开硬冲硬顶的做法",
        };
    }

    protected static function buildFallbackActivityGuidance(string $aspect, array $almanac): string
    {
        $jishen = array_slice(self::normalizeActivities($almanac['jishen'] ?? []), 0, 2);
        $xiongsha = array_slice(self::normalizeActivities($almanac['xiongsha'] ?? []), 0, 2);
        $spiritLead = '';

        if (!empty($jishen) && count($jishen) >= count($xiongsha)) {
            $spiritLead = '吉神' . implode('、', $jishen) . '可借力';
        } elseif (!empty($xiongsha)) {
            $spiritLead = '需留意' . implode('、', $xiongsha) . '带来的反复';
        }

        $tail = match ($aspect) {
            'career' => '推进事项前先把流程、节点和责任人钉牢，会比蛮冲更有效',
            'wealth' => '钱上先看确定性和回收周期，再谈弹性收益',
            'love' => '关系里宜直说需求、少拐弯，温度反而更稳',
            'health' => '今天比拼的不是硬扛，而是作息、补水与恢复能力',
            default => '先顺着盘面做减法，再逐步加码会更稳',
        };

        return trim($spiritLead !== '' ? $spiritLead . '，' . $tail : $tail);
    }

    protected static function extractMatchedActivities(array $source, array $keywords): array
    {
        $matched = [];
        foreach ($source as $value) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($value, $keyword) !== false) {
                    $matched[] = $value;
                    break;
                }
            }
        }

        return array_values(array_unique($matched));
    }

    protected static function formatActivityList(array $activities, int $limit = 2): string
    {
        $items = array_slice(array_values(array_filter(array_map(
            static fn($item): string => trim((string) $item),
            $activities
        ))), 0, $limit);

        return implode('、', $items);
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

        $month = self::formatLunarMonth((int) ($lunar['lunar_month'] ?? 1), (bool) ($lunar['is_leap'] ?? false));
        $day = self::formatLunarDay((int) ($lunar['lunar_day'] ?? 1));
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

        return $days[$day] ?? ((string) $day);
    }

    /**
     * 根据分数获取运势摘要
     */
    protected static function getSummaryByScore(int $score): string
    {
        if ($score >= 90) {
            return '今日运势极佳，万事顺遂，把握机会！';
        }
        if ($score >= 80) {
            return '今日运势良好，积极进取，收获颇丰。';
        }
        if ($score >= 70) {
            return '今日运势平稳，稳扎稳打，循序渐进。';
        }
        if ($score >= 60) {
            return '今日运势一般，保持谨慎，静待时机。';
        }

        return '今日运势欠佳，低调行事，以守为攻。';
    }
}
