<?php
declare(strict_types=1);

namespace app\service;

/**
 * 农历转换工具类
 * 提供公历转农历、干支计算及黄历辅助信息。
 */
class LunarService
{
    /**
     * 天干
     */
    public const TIAN_GAN = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];

    /**
     * 地支
     */
    public const DI_ZHI = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    /**
     * 十二建除
     */
    private const ZHIRI_ORDER = ['建', '除', '满', '平', '定', '执', '破', '危', '成', '收', '开', '闭'];

    /**
     * 时辰序列
     */
    private const SHICHEN_BRANCHES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    /**
     * 黄黑道十二神
     */
    private const SHICHEN_GODS = ['青龙', '明堂', '天刑', '朱雀', '金匮', '天德', '白虎', '玉堂', '天牢', '玄武', '司命', '勾陈'];

    /**
     * 农历数据 (2020-2035)
     * 格式：[农历年新年的公历日期, 闰月(0表示无), 各月天数(16进制，从正月到腊月，1=30天, 0=29天), 闰月天数]
     */
    private static $lunarData = [
        2020 => ['2020-01-25', 4, 0x094b, 0],
        2021 => ['2021-02-12', 0, 0x0a95, 0],
        2022 => ['2022-02-01', 0, 0x052a, 0],
        2023 => ['2023-01-22', 2, 0x0aad, 1],
        2024 => ['2024-02-10', 0, 0x0552, 0],
        2025 => ['2025-01-29', 6, 0x0aba, 1],
        2026 => ['2026-02-17', 0, 0x052b, 0],
        2027 => ['2027-02-06', 5, 0x0a93, 1],
        2028 => ['2028-01-26', 0, 0x092b, 0],
        2029 => ['2029-02-13', 0, 0x0d95, 0],
        2030 => ['2030-02-03', 0, 0x0d4a, 0],
        2031 => ['2031-01-23', 3, 0x0da5, 1],
        2032 => ['2032-02-11', 0, 0x056a, 0],
        2033 => ['2033-01-31', 0, 0x0abb, 0],
        2034 => ['2034-02-19', 0, 0x025b, 0],
        2035 => ['2035-02-08', 6, 0x052b, 1],
    ];

    /**
     * 公历转农历
     *
     * @param string $date 公历日期 Y-m-d
     * @return array 农历与黄历信息
     */
    public static function solarToLunar(string $date): array
    {
        $timestamp = strtotime($date);
        $lunarYear = (int)date('Y', $timestamp);

        // 农历年份仍以春节为界，用于农历月日与梅花易数的年支数。
        $newYearDate = self::$lunarData[$lunarYear][0] ?? null;
        if ($newYearDate && $timestamp < strtotime($newYearDate)) {
            $lunarYear--;
        }

        $data = self::$lunarData[$lunarYear] ?? null;
        if (!$data) {
            return self::fallbackSolarToLunar($date);
        }

        $baseTimestamp = strtotime($data[0]);
        $offset = (int)(($timestamp - $baseTimestamp) / 86400);
        $leapMonth = (int)$data[1];
        $monthData = (int)$data[2];
        $leapMonthDays = $data[3] ? 30 : 29;

        $lunarMonth = 1;
        $isLeap = false;

        for ($i = 1; $i <= 12; $i++) {
            $lunarMonth = $i;
            $bit = 12 - $i;
            $days = (($monthData >> $bit) & 1) ? 30 : 29;

            if ($offset < $days) {
                break;
            }
            $offset -= $days;

            if ($i === $leapMonth) {
                if ($offset < $leapMonthDays) {
                    $isLeap = true;
                    break;
                }
                $offset -= $leapMonthDays;
            }
        }


        $lunarDay = $offset + 1;
        $lunarYearGanZhi = self::getYearGanZhiByYear($lunarYear);
        $pillars = self::resolveGanZhiPillars($date, $lunarYearGanZhi);

        return self::buildLunarResponse(
            $lunarYear,
            $lunarMonth,
            $lunarDay,
            $isLeap,
            $lunarYearGanZhi,
            $pillars['year'],
            $pillars['month'],
            $pillars['day']
        );
    }

    /**
     * 构建统一返回结构。
     */
    private static function buildLunarResponse(
        int $lunarYear,
        int $lunarMonth,
        int $lunarDay,
        bool $isLeap,
        string $lunarYearGanZhi,
        string $yearGanZhi,
        string $monthGanZhi,
        string $dayGanZhi
    ): array {
        $yearZhiIndex = self::getZhiIndex(mb_substr($lunarYearGanZhi, 1, 1));
        $almanac = self::buildAlmanacDetail($yearGanZhi, $monthGanZhi, $dayGanZhi);
        $lunarText = trim($lunarYearGanZhi . '年 ' . self::formatLunarMonth($lunarMonth, $isLeap) . self::formatLunarDay($lunarDay));
        $ganzhiText = trim(implode(' ', array_filter([
            $yearGanZhi !== '' ? $yearGanZhi . '年' : '',
            $monthGanZhi !== '' ? $monthGanZhi . '月' : '',
            $dayGanZhi !== '' ? $dayGanZhi . '日' : '',
        ])));

        return [
            'year_zhi_index' => $yearZhiIndex + 1,
            'lunar_month' => $lunarMonth,
            'lunar_day' => $lunarDay,
            'is_leap' => $isLeap,
            'lunar_year_gan_zhi' => $lunarYearGanZhi,
            'year_gan_zhi' => $yearGanZhi,
            'month_gan_zhi' => $monthGanZhi,
            'day_gan_zhi' => $dayGanZhi,
            'yearGanzhi' => $yearGanZhi,
            'monthGanzhi' => $monthGanZhi,
            'dayGanzhi' => $dayGanZhi,
            'lunar_text' => $lunarText,
            'ganzhi' => $ganzhiText,
            'yi' => $almanac['yi'],
            'ji' => $almanac['ji'],
            'jishen' => $almanac['jishen'],
            'xiongsha' => $almanac['xiongsha'],
            'sha' => $almanac['sha'],
            'zhiri' => $almanac['zhiri'],
            'shichen' => $almanac['shichen'],
        ];
    }

    /**
     * 解析黄历所需四柱。
     *
     * 农历显示继续沿用春节口径，但黄历主数据（岁次/月建/日辰）
     * 统一复用八字核心算法，按节气与交节时刻取值，避免立春前后口径漂移。
     */
    private static function resolveGanZhiPillars(string $date, string $fallbackYearGanZhi): array
    {
        $yearGanZhi = $fallbackYearGanZhi;
        $monthGanZhi = '';
        $dayGanZhi = '';
        $baziService = new BaziCalculationService();
        $referenceDateTime = self::resolveGanZhiReferenceDateTime($date, $baziService);

        $pillars = self::extractGanZhiPillarsByMoment($baziService, $referenceDateTime);
        if ($pillars !== null) {
            $yearGanZhi = $pillars['year'] !== '' ? $pillars['year'] : $fallbackYearGanZhi;
            $monthGanZhi = $pillars['month'];
            $dayGanZhi = $pillars['day'];
        }

        if ($dayGanZhi === '') {
            $timestamp = strtotime($referenceDateTime);
            $dayPillar = $baziService->calculateDayPillar(
                (int)date('Y', $timestamp),
                (int)date('n', $timestamp),
                (int)date('j', $timestamp)
            );
            $dayGanZhi = self::TIAN_GAN[$dayPillar['gan_index']] . self::DI_ZHI[$dayPillar['zhi_index']];
        }

        return [
            'year' => $yearGanZhi,
            'month' => $monthGanZhi,
            'day' => $dayGanZhi,
        ];
    }

    /**
     * 黄历接口只有日期没有时辰时，常态取中午；若当日年柱或月柱发生交节切换，则改取入节后的日末口径。
     * 这样既保留日历展示的稳定性，也避免节气当天被固定中午误判在旧节令。
     */
    private static function resolveGanZhiReferenceDateTime(string $date, BaziCalculationService $baziService): string
    {
        $startSnapshot = self::extractGanZhiPillarsByMoment($baziService, $date . ' 00:00:00');
        $endSnapshot = self::extractGanZhiPillarsByMoment($baziService, $date . ' 23:59:59');

        if ($startSnapshot !== null && $endSnapshot !== null) {
            $yearChanged = ($startSnapshot['year'] ?? '') !== ($endSnapshot['year'] ?? '');
            $monthChanged = ($startSnapshot['month'] ?? '') !== ($endSnapshot['month'] ?? '');
            if ($yearChanged || $monthChanged) {
                return $date . ' 23:59:59';
            }
        }

        return $date . ' 12:00:00';
    }

    /**
     * 读取某个参考时刻对应的干支快照。
     */
    private static function extractGanZhiPillarsByMoment(BaziCalculationService $baziService, string $dateTime): ?array
    {
        try {
            $bazi = $baziService->calculateBazi($dateTime, '男');

            return [
                'year' => self::normalizePillar($bazi['year'] ?? []),
                'month' => self::normalizePillar($bazi['month'] ?? []),
                'day' => self::normalizePillar($bazi['day'] ?? []),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }


    /**
     * 规范四柱结构为干支文本。
     */
    private static function normalizePillar(array $pillar): string
    {
        $gan = trim((string)($pillar['gan'] ?? ''));
        $zhi = trim((string)($pillar['zhi'] ?? ''));

        return $gan . $zhi;
    }

    /**
     * 通过年份计算干支。
     */
    private static function getYearGanZhiByYear(int $year): string
    {
        return self::TIAN_GAN[($year - 4) % 10] . self::DI_ZHI[($year - 4) % 12];
    }

    /**
     * 构建黄历辅助信息。
     */
    private static function buildAlmanacDetail(string $yearGanZhi, string $monthGanZhi, string $dayGanZhi): array
    {
        $monthZhi = mb_substr($monthGanZhi, 1, 1);
        $dayGan = mb_substr($dayGanZhi, 0, 1);
        $dayZhi = mb_substr($dayGanZhi, 1, 1);

        $profileMap = self::getZhiriProfileMap();

        if ($monthZhi === '' || $dayGan === '' || $dayZhi === '') {
            $profile = $profileMap['平'];
            return [
                'zhiri' => '平',
                'yi' => $profile['yi'],
                'ji' => $profile['ji'],
                'jishen' => $profile['jishen'],
                'xiongsha' => $profile['xiongsha'],
                'sha' => self::getShaDirection($dayZhi),
                'shichen' => self::buildShiChenData($dayGan, $profile['yi'], $profile['ji']),
            ];
        }

        $monthZhiIndex = self::getZhiIndex($monthZhi);
        $dayZhiIndex = self::getZhiIndex($dayZhi);
        $zhiriIndex = ($dayZhiIndex - $monthZhiIndex + 12) % 12;
        $zhiri = self::ZHIRI_ORDER[$zhiriIndex] ?? '平';
        $profile = $profileMap[$zhiri] ?? $profileMap['平'];

        return [
            'zhiri' => $zhiri,
            'yi' => $profile['yi'],
            'ji' => $profile['ji'],
            'jishen' => $profile['jishen'],
            'xiongsha' => $profile['xiongsha'],
            'sha' => self::getShaDirection($dayZhi),
            'shichen' => self::buildShiChenData($dayGan, $profile['yi'], $profile['ji']),
        ];
    }

    /**
     * 建除十二神的宜忌与吉神凶煞映射。
     */
    private static function getZhiriProfileMap(): array
    {
        return [
            '建' => ['yi' => ['出行', '赴任', '祈福', '求嗣'], 'ji' => ['动土', '开仓', '安葬'], 'jishen' => ['天恩', '岁德'], 'xiongsha' => ['土府', '小时']],
            '除' => ['yi' => ['解除', '扫舍', '沐浴', '求医'], 'ji' => ['嫁娶', '开市', '远行'], 'jishen' => ['天德', '月空'], 'xiongsha' => ['五离', '月害']],
            '满' => ['yi' => ['祈福', '祭祀', '交易', '纳财'], 'ji' => ['诉讼', '安葬', '远迁'], 'jishen' => ['民日', '三合'], 'xiongsha' => ['天刑', '死气']],
            '平' => ['yi' => ['修饰', '会友', '安床', '纳畜'], 'ji' => ['嫁娶', '开渠', '破土'], 'jishen' => ['时德', '鸣吠'], 'xiongsha' => ['月厌', '地火']],
            '定' => ['yi' => ['嫁娶', '订盟', '入学', '立券'], 'ji' => ['诉讼', '出师', '远行'], 'jishen' => ['天喜', '月德合'], 'xiongsha' => ['小耗', '五墓']],
            '执' => ['yi' => ['捕捉', '纳采', '祭祀', '求财'], 'ji' => ['开市', '移徙', '安床'], 'jishen' => ['解神', '金堂'], 'xiongsha' => ['厌对', '招摇']],
            '破' => ['yi' => ['求医', '破屋', '治病', '解除'], 'ji' => ['嫁娶', '开市', '签约'], 'jishen' => ['天仓'], 'xiongsha' => ['月破', '大耗']],
            '危' => ['yi' => ['祭祀', '祈福', '求嗣', '安床'], 'ji' => ['登高', '乘船', '动土'], 'jishen' => ['母仓', '玉宇'], 'xiongsha' => ['天贼', '血支']],
            '成' => ['yi' => ['嫁娶', '开市', '入宅', '签约'], 'ji' => ['诉讼', '安葬'], 'jishen' => ['三合', '天愿'], 'xiongsha' => ['勾绞', '四击']],
            '收' => ['yi' => ['纳财', '收账', '祭祀', '捕猎'], 'ji' => ['开业', '置产', '安床'], 'jishen' => ['圣心', '福德'], 'xiongsha' => ['劫煞', '往亡']],
            '开' => ['yi' => ['开市', '求财', '开张', '出行'], 'ji' => ['安葬', '动土', '针灸'], 'jishen' => ['月恩', '四相'], 'xiongsha' => ['游祸', '五虚']],
            '闭' => ['yi' => ['祭祀', '修筑', '纳藏', '安门'], 'ji' => ['嫁娶', '开市', '远行'], 'jishen' => ['不将', '续世'], 'xiongsha' => ['月煞', '血忌']],
        ];
    }

    /**
     * 根据日干推演十二时辰黄黑道。
     */
    private static function buildShiChenData(string $dayGan, array $yi, array $ji): array
    {
        $startOffsetMap = [
            '甲' => 0, '己' => 0,
            '乙' => 2, '庚' => 2,
            '丙' => 4, '辛' => 4,
            '丁' => 6, '壬' => 6,
            '戊' => 8, '癸' => 8,
        ];
        $auspiciousGods = ['青龙', '明堂', '金匮', '天德', '玉堂', '司命'];
        $timeRanges = ['23:00-00:59', '01:00-02:59', '03:00-04:59', '05:00-06:59', '07:00-08:59', '09:00-10:59', '11:00-12:59', '13:00-14:59', '15:00-16:59', '17:00-18:59', '19:00-20:59', '21:00-22:59'];

        $normalizedDayGan = $dayGan !== '' ? $dayGan : '甲';
        $startOffset = $startOffsetMap[$normalizedDayGan] ?? 0;
        $result = [];
        foreach (self::SHICHEN_BRANCHES as $index => $branch) {
            $god = self::SHICHEN_GODS[($startOffset + $index) % 12];
            $isAuspicious = in_array($god, $auspiciousGods, true);
            $result[] = [
                'name' => $branch,
                'time' => $timeRanges[$index],
                'type' => $isAuspicious ? 'ji' : 'xiong',
                'god' => $god,
                'yiji' => self::buildShiChenAdvice($isAuspicious, $yi, $ji),
            ];
        }

        return $result;
    }

    private static function buildShiChenAdvice(bool $isAuspicious, array $yi, array $ji): string
    {
        $yiText = implode('、', array_slice($yi, 0, 2));
        $jiText = implode('、', array_slice($ji, 0, 2));

        if ($isAuspicious) {
            return '宜' . $yiText . '；忌' . $jiText;
        }

        return '宜静守、复盘；忌' . ($jiText !== '' ? $jiText : '冒进与争执');
    }

    /**
     * 获取煞方。
     */
    private static function getShaDirection(string $dayZhi): string
    {
        if (in_array($dayZhi, ['寅', '午', '戌'], true)) {
            return '北';
        }
        if (in_array($dayZhi, ['亥', '卯', '未'], true)) {
            return '西';
        }
        if (in_array($dayZhi, ['申', '子', '辰'], true)) {
            return '南';
        }
        return '东';
    }

    /**
     * 获取地支索引。
     */
    private static function getZhiIndex(string $zhi): int
    {
        $index = array_search($zhi, self::DI_ZHI, true);
        return $index === false ? 0 : (int)$index;
    }

    /**
     * 降级处理
     */
    private static function fallbackSolarToLunar(string $date): array
    {
        $timestamp = strtotime($date);
        $year = (int)date('Y', $timestamp);
        $month = (int)date('n', $timestamp);
        $day = (int)date('j', $timestamp);
        $lunarYearGanZhi = self::getYearGanZhiByYear($year);
        $pillars = self::resolveGanZhiPillars($date, $lunarYearGanZhi);

        return self::buildLunarResponse(
            $year,
            $month,
            $day,
            false,
            $lunarYearGanZhi,
            $pillars['year'],
            $pillars['month'],
            $pillars['day']
        );
    }

    private static function formatLunarMonth(int $month, bool $isLeap = false): string
    {
        $months = [1 => '正月', 2 => '二月', 3 => '三月', 4 => '四月', 5 => '五月', 6 => '六月', 7 => '七月', 8 => '八月', 9 => '九月', 10 => '十月', 11 => '冬月', 12 => '腊月'];
        $monthText = $months[$month] ?? ($month . '月');
        return $isLeap ? '闰' . $monthText : $monthText;
    }

    private static function formatLunarDay(int $day): string
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
}
