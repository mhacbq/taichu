<?php
declare(strict_types=1);

namespace app\service;

/**
 * 八字计算服务 - 增强版
 * 
 * 封装核心排盘逻辑，支持晚子时处理、精确节气计算、日主强弱分析。
 */
class BaziCalculationService
{
    /**
     * 天干五行
     */
    protected $ganWuXing = [
        '甲' => '木', '乙' => '木', '丙' => '火', '丁' => '火', '戊' => '土',
        '己' => '土', '庚' => '金', '辛' => '金', '壬' => '水', '癸' => '水'
    ];
    
    /**
     * 地支五行
     */
    protected $zhiWuXing = [
        '子' => '水', '丑' => '土', '寅' => '木', '卯' => '木', '辰' => '土', '巳' => '火',
        '午' => '火', '未' => '土', '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水'
    ];
    
    /**
     * 地支藏干
     */
    protected $zhiCangGan = [
        '子' => ['癸'], '丑' => ['己', '癸', '辛'], '寅' => ['甲', '丙', '戊'],
        '卯' => ['乙'], '辰' => ['戊', '乙', '癸'], '巳' => ['丙', '庚', '戊'],
        '午' => ['丁', '己'], '未' => ['己', '丁', '乙'], '申' => ['庚', '壬', '戊'],
        '酉' => ['辛'], '戌' => ['戊', '辛', '丁'], '亥' => ['壬', '甲']
    ];
    
    /**
     * 十神关系表
     */
    protected $shiShenTable = [
        '甲' => ['甲' => '比肩', '乙' => '劫财', '丙' => '食神', '丁' => '伤官', '戊' => '偏财', '己' => '正财', '庚' => '七杀', '辛' => '正官', '壬' => '偏印', '癸' => '正印'],
        '乙' => ['甲' => '劫财', '乙' => '比肩', '丙' => '伤官', '丁' => '食神', '戊' => '正财', '己' => '偏财', '庚' => '正官', '辛' => '七杀', '壬' => '正印', '癸' => '偏印'],
        '丙' => ['甲' => '偏印', '乙' => '正印', '丙' => '比肩', '丁' => '劫财', '戊' => '食神', '己' => '伤官', '庚' => '偏财', '辛' => '正财', '壬' => '七杀', '癸' => '正官'],
        '丁' => ['甲' => '正印', '乙' => '偏印', '丙' => '劫财', '丁' => '比肩', '戊' => '伤官', '己' => '食神', '庚' => '正财', '辛' => '偏财', '壬' => '正官', '癸' => '七杀'],
        '戊' => ['甲' => '七杀', '乙' => '正官', '丙' => '偏印', '丁' => '正印', '戊' => '比肩', '己' => '劫财', '庚' => '食神', '辛' => '伤官', '壬' => '偏财', '癸' => '正财'],
        '己' => ['甲' => '正官', '乙' => '七杀', '丙' => '正印', '丁' => '偏印', '戊' => '劫财', '己' => '比肩', '庚' => '伤官', '辛' => '食神', '壬' => '正财', '癸' => '偏财'],
        '庚' => ['甲' => '偏财', '乙' => '正财', '丙' => '七杀', '丁' => '正官', '戊' => '偏印', '己' => '正印', '庚' => '比肩', '辛' => '劫财', '壬' => '食神', '癸' => '伤官'],
        '辛' => ['甲' => '正财', '乙' => '偏财', '丙' => '正官', '丁' => '七杀', '戊' => '正印', '己' => '偏印', '庚' => '劫财', '辛' => '比肩', '壬' => '伤官', '癸' => '食神'],
        '壬' => ['甲' => '食神', '乙' => '伤官', '丙' => '偏财', '丁' => '正财', '戊' => '七杀', '己' => '正官', '庚' => '偏印', '辛' => '正印', '壬' => '比肩', '癸' => '劫财'],
        '癸' => ['甲' => '伤官', '乙' => '食神', '丙' => '正财', '丁' => '偏财', '戊' => '正官', '己' => '七杀', '庚' => '正印', '辛' => '偏印', '壬' => '劫财', '癸' => '比肩']
    ];
    
    /**
     * 纳音表
     */
    protected $naYin = [
        '甲子' => '海中金', '乙丑' => '海中金', '丙寅' => '炉中火', '丁卯' => '炉中火', '戊辰' => '大林木', '己巳' => '大林木',
        '庚午' => '路旁土', '辛未' => '路旁土', '壬申' => '剑锋金', '癸酉' => '剑锋金', '甲戌' => '山头火', '乙亥' => '山头火',
        '丙子' => '涧下水', '丁丑' => '涧下水', '戊寅' => '城头土', '己卯' => '城头土', '庚辰' => '白蜡金', '辛巳' => '白蜡金',
        '壬午' => '杨柳木', '癸未' => '杨柳木', '甲申' => '泉中水', '乙酉' => '泉中水', '丙戌' => '屋上土', '丁亥' => '屋上土',
        '戊子' => '霹雳火', '己丑' => '霹雳火', '庚寅' => '松柏木', '辛卯' => '松柏木', '壬辰' => '长流水', '癸巳' => '长流水',
        '甲午' => '沙中金', '乙未' => '沙中金', '丙申' => '山下火', '丁酉' => '山下火', '戊戌' => '平地木', '己亥' => '平地木',
        '庚子' => '壁上土', '辛丑' => '壁上土', '壬寅' => '金箔金', '癸卯' => '金箔金', '甲辰' => '覆灯火', '乙巳' => '覆灯火',
        '丙午' => '天河水', '丁未' => '天河水', '戊申' => '大驿土', '己酉' => '大驿土', '庚戌' => '钗钏金', '辛亥' => '钗钏金',
        '壬子' => '桑柘木', '癸丑' => '桑柘木', '甲寅' => '大溪水', '乙卯' => '大溪水', '丙辰' => '沙中土', '丁巳' => '沙中土',
        '戊午' => '天上火', '己未' => '天上火', '庚申' => '石榴木', '辛酉' => '石榴木', '壬戌' => '大海水', '癸亥' => '大海水'
    ];

    /**
     * 节气月份对应
     */
    protected $yueJian = ['寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥', '子', '丑'];

    /**
     * 年干起月干（五虎遁）
     */
    protected $yearGanToMonthGanStart = [
        '甲' => '丙', '乙' => '戊', '丙' => '庚', '丁' => '壬', '戊' => '甲',
        '己' => '丙', '庚' => '戊', '辛' => '庚', '壬' => '壬', '癸' => '甲'
    ];

    /**
     * 节气日期计算 (支持20世纪和21世纪寿星公式)
     */
    protected function getJieQiDate(int $year, string $name): array
    {
        // 20世纪节气常数 (1900-1999)
        $jieQiConstants20 = [
            '小寒' => [1, 6.11], '大寒' => [1, 20.84], '立春' => [2, 4.6298], '雨水' => [2, 19.46],
            '惊蛰' => [3, 6.3826], '春分' => [3, 21.415], '清明' => [4, 5.59], '谷雨' => [4, 20.888],
            '立夏' => [5, 6.318], '小满' => [5, 21.86], '芒种' => [6, 6.5], '夏至' => [6, 22.2],
            '小暑' => [7, 7.928], '大暑' => [7, 23.65], '立秋' => [8, 8.35], '处暑' => [8, 23.95],
            '白露' => [9, 8.44], '秋分' => [9, 23.822], '寒露' => [10, 9.098], '霜降' => [10, 24.218],
            '立冬' => [11, 8.218], '小雪' => [11, 23.08], '大雪' => [12, 7.9], '冬至' => [12, 22.6]
        ];

        // 21世纪节气常数 (2000-2099)
        $jieQiConstants21 = [
            '小寒' => [1, 5.4055], '大寒' => [1, 20.12], '立春' => [2, 3.87], '雨水' => [2, 18.73],
            '惊蛰' => [3, 5.63], '春分' => [3, 20.646], '清明' => [4, 4.81], '谷雨' => [4, 20.1],
            '立夏' => [5, 5.52], '小满' => [5, 21.04], '芒种' => [6, 5.678], '夏至' => [6, 21.37],
            '小暑' => [7, 7.108], '大暑' => [7, 22.83], '立秋' => [8, 7.5], '处暑' => [8, 23.13],
            '白露' => [9, 7.646], '秋分' => [9, 23.042], '寒露' => [10, 8.318], '霜降' => [10, 23.438],
            '立冬' => [11, 7.438], '小雪' => [11, 22.36], '大雪' => [12, 7.18], '冬至' => [12, 21.94]
        ];

        $is21st = ($year >= 2000);
        $constants = $is21st ? $jieQiConstants21 : $jieQiConstants20;
        
        if (!isset($constants[$name])) return [0, 0];
        
        $month = $constants[$name][0];
        $C = $constants[$name][1];
        $y = $year % 100;
        
        // 寿星公式：[Y*D+C]-L
        // Y=年份后两位，D=0.2422，C=常数，L=闰年数
        // 20世纪以1900为基准，21世纪以2000为基准
        $day = floor($y * 0.2422 + $C) - floor(($y - ($is21st ? 0 : 1)) / 4);
        
        // 特殊年份修正
        if ($is21st) {
            if ($name === '立春' && $year === 2019) $day -= 1;
            if ($name === '雨水' && $year === 2026) $day -= 1;
        } else {
            if ($name === '立春' && ($year === 1902 || $year === 1918 || $year === 1982)) $day += 1;
            if ($name === '惊蛰' && $year === 1906) $day += 1;
        }
        
        return [$month, (int)$day];
    }

    /**
     * 计算八字
     */
    public function calculateBazi(string $birthDate, string $gender = '男'): array
    {
        $timestamp = strtotime($birthDate);
        $year = (int)date('Y', $timestamp);
        $month = (int)date('n', $timestamp);
        $day = (int)date('j', $timestamp);
        $hour = (int)date('G', $timestamp);
        
        // ===== 核心修复：晚子时(23:00-00:00)日柱切换 =====
        $calcDayYear = $year;
        $calcDayMonth = $month;
        $calcDayDay = $day;
        
        if ($hour >= 23) {
            $nextDayTs = $timestamp + 3600; // 跨入次日
            $calcDayYear = (int)date('Y', $nextDayTs);
            $calcDayMonth = (int)date('n', $nextDayTs);
            $calcDayDay = (int)date('j', $nextDayTs);
        }

        $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        
        // 1. 年柱 (基于立春)
        $lunarYear = $this->getLunarYear($year, $month, $day);
        $yearGanIndex = ($lunarYear - 4) % 10;
        $yearZhiIndex = ($lunarYear - 4) % 12;
        $yearGan = $tianGan[$yearGanIndex];
        
        // 2. 月柱 (基于节气)
        $monthInfo = $this->getLunarMonth($year, $month, $day);
        $monthZhiIndex = $monthInfo['zhi_index'];
        $monthGanStart = $this->yearGanToMonthGanStart[$yearGan];
        $monthGanIndex = (array_search($monthGanStart, $tianGan) + $monthInfo['month_offset']) % 10;
        
        // 3. 日柱
        $dayPillar = $this->calculateDayPillar($calcDayYear, $calcDayMonth, $calcDayDay);
        $dayGanIndex = $dayPillar['gan_index'];
        $dayZhiIndex = $dayPillar['zhi_index'];
        $dayGan = $tianGan[$dayGanIndex];
        
        // 4. 时柱
        $shiZhiIndex = ($hour >= 23) ? 0 : (int)(($hour + 1) / 2) % 12;
        $dayGanHourStart = ['甲'=>'甲','己'=>'甲','乙'=>'丙','庚'=>'丙','丙'=>'戊','辛'=>'戊','丁'=>'庚','壬'=>'庚','戊'=>'壬','癸'=>'壬'];
        $hourGanStart = $dayGanHourStart[$dayGan];
        $shiGanIndex = (array_search($hourGanStart, $tianGan) + $shiZhiIndex) % 10;
        
        $pillars = [
            'year' => ['gan' => $tianGan[$yearGanIndex], 'zhi' => $diZhi[$yearZhiIndex]],
            'month' => ['gan' => $tianGan[$monthGanIndex], 'zhi' => $diZhi[$monthZhiIndex]],
            'day' => ['gan' => $dayGan, 'zhi' => $diZhi[$dayZhiIndex]],
            'hour' => ['gan' => $tianGan[$shiGanIndex], 'zhi' => $diZhi[$shiZhiIndex]],
        ];
        
        // 补充属性
        foreach ($pillars as $key => &$p) {
            $p['gan_wuxing'] = $this->ganWuXing[$p['gan']];
            $p['zhi_wuxing'] = $this->zhiWuXing[$p['zhi']];
            $p['shishen'] = $this->shiShenTable[$dayGan][$p['gan']] ?? '';
            $p['nayin'] = $this->naYin[$p['gan'].$p['zhi']] ?? '';
            $p['canggan'] = $this->zhiCangGan[$p['zhi']];
            // 旬空
            $p['xunkong'] = $this->calculateXunKong($pillars['day']['gan'], $pillars['day']['zhi']);
        }
        
        return array_merge($pillars, [
            'day_master' => $dayGan,
            'day_master_wuxing' => $this->ganWuXing[$dayGan],
            'strength' => $this->analyzeStrength($pillars),
            'qiyun' => $this->calculateQiYun($year, $month, $day, $hour, $gender, $yearGan),
            'calculation_note' => $hour >= 23 ? '已处理晚子时：日柱进一日，年柱月柱维持原日' : '正常排盘'
        ]);
    }

    public function getLunarYear(int $year, int $month, int $day): int
    {
        $lichun = $this->getJieQiDate($year, '立春');
        if ($month < $lichun[0] || ($month == $lichun[0] && $day < $lichun[1])) {
            return $year - 1;
        }
        return $year;
    }

    protected function getLunarMonth(int $year, int $month, int $day): array
    {
        $dateVal = $month * 100 + $day;
        
        // 节气数组（按地支索引顺序：2寅 3卯 ... 0子 1丑）
        $jieqis = [
            ['name' => '立春', 'idx' => 2], ['name' => '惊蛰', 'idx' => 3], ['name' => '清明', 'idx' => 4],
            ['name' => '立夏', 'idx' => 5], ['name' => '芒种', 'idx' => 6], ['name' => '小暑', 'idx' => 7],
            ['name' => '立秋', 'idx' => 8], ['name' => '白露', 'idx' => 9], ['name' => '寒露', 'idx' => 10],
            ['name' => '立冬', 'idx' => 11], ['name' => '大雪', 'idx' => 0], ['name' => '小寒', 'idx' => 1]
        ];
        
        $currentZhiIdx = 1; // 默认丑月
        $monthOffset = 11; // 丑月相对于寅月的偏移
        
        foreach (array_reverse($jieqis) as $index => $jq) {
            $jqDate = $this->getJieQiDate($year, $jq['name']);
            if ($dateVal >= ($jqDate[0] * 100 + $jqDate[1])) {
                $currentZhiIdx = $jq['idx'];
                // 计算月干相对于起始干的偏移
                // 寅月偏移0，卯月偏移1...
                $monthOffset = ($currentZhiIdx - 2 + 12) % 12;
                break;
            }
        }
        
        return [
            'zhi_index' => $currentZhiIdx,
            'month_offset' => $monthOffset
        ];
    }

    /**
     * 计算儒略日
     */
    public function getJulianDay(int $year, int $month, int $day): int
    {
        if ($month <= 2) {
            $year -= 1;
            $month += 12;
        }
        $A = floor($year / 100);
        $B = 2 - $A + floor($A / 4);
        $JD = floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $B - 1524.5;
        return (int)$JD;
    }

    /**
     * 计算日柱
     */
    public function calculateDayPillar(int $year, int $month, int $day): array
    {
        // 1900-01-31 是甲子日 (0, 0)
        $targetJD = $this->getJulianDay($year, $month, $day);
        $baseJD = $this->getJulianDay(1900, 1, 31);
        $diff = $targetJD - $baseJD;
        
        return [
            'gan_index' => ($diff % 10 + 10) % 10,
            'zhi_index' => ($diff % 12 + 12) % 12
        ];
    }

    /**
     * 计算旬空
     */
    public function calculateXunKong(string $gan, string $zhi): array
    {
        $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        
        $gIdx = array_search($gan, $tianGan);
        $zIdx = array_search($zhi, $diZhi);
        
        // 旬首地支索引 = (地支索引 - 天干索引 + 12) % 12
        $xunShouZhiIdx = ($zIdx - $gIdx + 12) % 12;
        
        // 旬空地支为旬首往前推两个地支
        $kong1Idx = ($xunShouZhiIdx + 10) % 12;
        $kong2Idx = ($xunShouZhiIdx + 11) % 12;
        
        return [$diZhi[$kong1Idx], $diZhi[$kong2Idx]];
    }

    /**
     * 精确强弱分析 (基于分值制)
     */
    public function analyzeStrength(array $pillars): array
    {
        $dm = $pillars['day']['gan'];
        $dmWx = $this->ganWuXing[$dm];
        
        // 定义五行关系：生我、同我、克我、我克、我生
        $relations = [
            '木' => ['supporting' => ['木', '水'], 'opposing' => ['金', '土', '火']],
            '火' => ['supporting' => ['火', '木'], 'opposing' => ['水', '金', '土']],
            '土' => ['supporting' => ['土', '火'], 'opposing' => ['木', '水', '金']],
            '金' => ['supporting' => ['金', '土'], 'opposing' => ['火', '木', '水']],
            '水' => ['supporting' => ['水', '金'], 'opposing' => ['土', '火', '木']]
        ];
        
        $supporting = $relations[$dmWx]['supporting'];
        
        // 1. 地支藏干分值表 (能量权重)
        // 月令(Month)总分40, 其他地支各10, 天干各10 (总分100)
        $branchWeights = [
            '子' => ['癸' => 100],
            '丑' => ['己' => 60, '癸' => 30, '辛' => 10],
            '寅' => ['甲' => 60, '丙' => 30, '戊' => 10],
            '卯' => ['乙' => 100],
            '辰' => ['戊' => 60, '乙' => 30, '癸' => 10],
            '巳' => ['丙' => 60, '庚' => 30, '戊' => 10],
            '午' => ['丁' => 70, '己' => 30],
            '未' => ['己' => 60, '丁' => 30, '乙' => 10],
            '申' => ['庚' => 60, '壬' => 30, '戊' => 10],
            '酉' => ['辛' => 100],
            '戌' => ['戊' => 60, '辛' => 30, '丁' => 10],
            '亥' => ['壬' => 70, '甲' => 30]
        ];
        
        $totalScore = 0;
        
        // 2. 计算天干得分 (年、月、时各10分)
        foreach (['year', 'month', 'hour'] as $key) {
            if (in_array($pillars[$key]['gan_wuxing'], $supporting)) {
                $totalScore += 10;
            }
        }
        
        // 3. 计算地支得分 (包含藏干权重)
        $branchPositions = [
            'month' => 40, // 月令权重最高
            'day' => 10,
            'hour' => 10,
            'year' => 10
        ];
        
        foreach ($branchPositions as $pos => $maxWeight) {
            $zhi = $pillars[$pos]['zhi'];
            $cangGans = $branchWeights[$zhi];
            foreach ($cangGans as $gan => $percent) {
                $wx = $this->ganWuXing[$gan];
                if (in_array($wx, $supporting)) {
                    $totalScore += ($maxWeight * $percent / 100);
                }
            }
        }
        
        // 判定结果
        $status = '中和';
        if ($totalScore >= 55) $status = '身旺';
        elseif ($totalScore >= 45) $status = '中和偏旺';
        elseif ($totalScore >= 35) $status = '中和偏弱';
        else $status = '身弱';
        
        return [
            'score' => round($totalScore, 1),
            'status' => $status,
            'favorite_wuxing' => $totalScore >= 50 ? $relations[$dmWx]['opposing'] : $relations[$dmWx]['supporting']
        ];
    }
    /**
     * 计算起运时间 (精确到岁/月/天)
     */
    public function calculateQiYun(int $year, int $month, int $day, int $hour, string $gender, string $yearGan): array
    {
        // 1. 判定顺逆
        // 阳男阴女顺行，阴男阳女逆行
        $yangGans = ['甲', '丙', '戊', '庚', '壬'];
        $isYangYear = in_array($yearGan, $yangGans);
        $isForward = ($gender === '男' && $isYangYear) || ($gender === '女' && !$isYangYear);
        
        // 2. 查找相邻节气 (Jie)
        $jieqis = ['立春', '惊蛰', '清明', '立夏', '芒种', '小暑', '立秋', '白露', '寒露', '立冬', '大雪', '小寒'];
        $birthTs = strtotime("$year-$month-$day $hour:00:00");
        
        // 获取当前年份及相邻年份的节气
        $targetJieTs = 0;
        
        // 遍历查找最近的一个节气
        $checkYears = [$year - 1, $year, $year + 1];
        $allJieTs = [];
        foreach ($checkYears as $y) {
            foreach ($jieqis as $jq) {
                $date = $this->getJieQiDate($y, $jq);
                $allJieTs[] = strtotime("$y-{$date[0]}-{$date[1]} 12:00:00"); // 假设正午交节，简化处理
            }
        }
        sort($allJieTs);
        
        if ($isForward) {
            foreach ($allJieTs as $ts) {
                if ($ts > $birthTs) {
                    $targetJieTs = $ts;
                    break;
                }
            }
        } else {
            foreach (array_reverse($allJieTs) as $ts) {
                if ($ts < $birthTs) {
                    $targetJieTs = $ts;
                    break;
                }
            }
        }
        
        // 3. 计算差距
        $diffSeconds = abs($targetJieTs - $birthTs);
        $diffDays = $diffSeconds / 86400;
        
        // 命理换算：3天=1岁，1天=4个月，1小时=5天
        $qiyunYear = (int)($diffDays / 3);
        $remainingDays = $diffDays - ($qiyunYear * 3);
        $qiyunMonth = (int)($remainingDays * 4);
        $remainingHours = ($remainingDays * 4 - $qiyunMonth) * (30 / 4); // 简化计算：1个月按30天算
        $qiyunDay = (int)($remainingHours * 5);
        
        return [
            'is_forward' => $isForward,
            'years' => $qiyunYear,
            'months' => $qiyunMonth,
            'days' => $qiyunDay,
            'display' => "{$qiyunYear}岁{$qiyunMonth}个月{$qiyunDay}天",
            'start_date' => date('Y-m-d', strtotime("+$qiyunYear years +$qiyunMonth months +$qiyunDay days", $birthTs))
        ];
    }
}
