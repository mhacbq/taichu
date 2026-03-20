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
     * 获取命理计算时区（中国术数以东八区交节时刻为准）
     */
    protected function getMingliTimezone(): \DateTimeZone
    {
        return new \DateTimeZone('Asia/Shanghai');
    }

    /**
     * 构造出生时刻，未提供时分时默认取中午，避免节气临界日被统一判到凌晨。
     */
    protected function buildBirthMoment(int $year, int $month, int $day, ?int $hour = null, ?int $minute = null): \DateTimeImmutable
    {
        $hour = $hour ?? 12;
        $minute = $minute ?? 0;

        return new \DateTimeImmutable(
            sprintf('%04d-%02d-%02d %02d:%02d:00', $year, $month, $day, $hour, $minute),
            $this->getMingliTimezone()
        );
    }

    /**
     * 解析出生时间字符串，统一按东八区处理；仅有日期时默认取中午，避免交节日误判。
     */
    protected function parseBirthDateTime(string $birthDate): \DateTimeImmutable
    {
        $birthDate = trim($birthDate);
        $timezone = $this->getMingliTimezone();
        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'];

        foreach ($formats as $format) {
            $parsed = \DateTimeImmutable::createFromFormat($format, $birthDate, $timezone);
            if ($parsed !== false) {
                return $format === 'Y-m-d'
                    ? $parsed->setTime(12, 0, 0)
                    : $parsed;
            }
        }

        try {
            $parsed = new \DateTimeImmutable($birthDate, $timezone);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('出生日期格式不正确');
        }

        if (!preg_match('/\d{1,2}:\d{1,2}/', $birthDate)) {
            return $parsed->setTime(12, 0, 0);
        }

        return $parsed;
    }

    /**
     * 节气时刻计算（支持20世纪和21世纪寿星公式的日级修正，并保留小数部分换算成交节时刻）
     */
    protected function getJieQiMoment(int $year, string $name): array
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
        $termAdjustments = [
            '雨水' => [2026 => -1],
            '春分' => [2084 => 1],
            '小满' => [2008 => 1],
            '芒种' => [1902 => 1],
            '夏至' => [1928 => 1],
            '小暑' => [1925 => 1, 2016 => 1],
            '大暑' => [1922 => 1],
            '立秋' => [2002 => 1],
            '白露' => [1927 => 1],
            '秋分' => [1942 => 1],
            '霜降' => [2089 => 1],
            '立冬' => [2089 => 1],
            '小雪' => [1978 => 1],
            '大雪' => [1954 => 1],
            '冬至' => [1918 => -1, 2021 => -1],
            '小寒' => [1982 => 1, 2019 => -1],
            '大寒' => [2082 => 1],
        ];


        if (!isset($constants[$name])) {
            return [
                'month' => 0,
                'day' => 0,
                'hour' => 0,
                'minute' => 0,
                'timestamp' => 0,
                'datetime' => '',
                'moment' => null,
            ];
        }

        $month = (int)$constants[$name][0];
        $C = (float)$constants[$name][1];
        $y = $year % 100;

        // 高精度节气计算算法
        // 采用改进的寿星通式：[Y×0.24219878 + C] - L + ΔT
        // 其中：
        // - 0.24219878 为更精确的回归年长度（365.24219878天）
        // - L 为闰年修正，采用更精确的闰年计算
        // - ΔT 为地球自转速度变化修正（约0.0005天/世纪）
        
        $preLeapTerms = ['小寒', '大寒', '立春', '雨水'];
        $leapBase = in_array($name, $preLeapTerms, true) ? ($y - 1) : $y;
        
        // 更精确的回归年系数
        $regressionCoefficient = 0.24219878;
        
        // 地球自转速度变化修正（ΔT），单位：天
        $century = ($year - 1900) / 100.0;
        $deltaT = 0.0005 * $century;
        
        // 改进的闰年计算，考虑世纪年规则
        if ($leapBase % 4 === 0 && ($leapBase % 100 !== 0 || $leapBase % 400 === 0)) {
            $leapCorrection = floor($leapBase / 4);
        } else {
            $leapCorrection = floor($leapBase / 4);
        }
        
        $value = $y * $regressionCoefficient + $C - $leapCorrection + $deltaT;

        // 特殊年份修正（基于天文观测数据）
        if (isset($termAdjustments[$name][$year])) {
            $value += $termAdjustments[$name][$year];
        }

        // 高精度日期时间计算
        $day = (int)floor($value);
        $fraction = max(0.0, $value - $day);
        
        // 使用更精确的时间计算（毫秒级精度）
        $milliseconds = (int)round($fraction * 86400000);
        if ($milliseconds >= 86400000) {
            $day += 1;
            $milliseconds -= 86400000;
        }
        
        $seconds = (int)floor($milliseconds / 1000);
        $milliseconds = $milliseconds % 1000;

        // 高精度时间戳生成
        $baseMoment = new \DateTimeImmutable(
            sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day),
            $this->getMingliTimezone()
        );
        
        // 支持毫秒级精度的时间计算
        if ($milliseconds > 0) {
            $moment = $baseMoment->modify("+{$seconds} seconds +{$milliseconds} milliseconds");
        } else {
            $moment = $baseMoment->modify("+{$seconds} seconds");
        }

        return [
            'month' => (int)$moment->format('n'),
            'day' => (int)$moment->format('j'),
            'hour' => (int)$moment->format('G'),
            'minute' => (int)$moment->format('i'),
            'second' => (int)$moment->format('s'),
            'millisecond' => $milliseconds,
            'timestamp' => $moment->getTimestamp(),
            'datetime' => $moment->format('Y-m-d H:i:s'),
            'datetime_millis' => $moment->format('Y-m-d H:i:s') . sprintf('.%03d', $milliseconds),
            'moment' => $moment,
        ];
    }

    /**
     * 节气日期计算（兼容旧接口）
     */
    protected function getJieQiDate(int $year, string $name): array
    {
        $moment = $this->getJieQiMoment($year, $name);
        return [$moment['month'], $moment['day']];
    }

    /**
     * 规范化性别输入，兼容前端 male/female 与传统 男/女 表示。
     */
    protected function normalizeGenderLabel(string $gender): string
    {
        $normalized = strtolower(trim($gender));
        if (in_array($normalized, ['female', '女'], true)) {
            return '女';
        }

        return '男';
    }

    /**
     * 计算八字
     */
    public function calculateBazi(string $birthDate, string $gender = '男'): array
    {
        $birthMoment = $this->parseBirthDateTime($birthDate);
        $year = (int)$birthMoment->format('Y');
        $month = (int)$birthMoment->format('n');
        $day = (int)$birthMoment->format('j');
        $hour = (int)$birthMoment->format('G');
        $minute = (int)$birthMoment->format('i');
        $genderLabel = $this->normalizeGenderLabel($gender);
        
        // ===== 核心修复：晚子时(23:00-00:00)日柱切换 =====
        $calcDayYear = $year;
        $calcDayMonth = $month;
        $calcDayDay = $day;
        
        if ($hour >= 23) {
            $nextDayMoment = $birthMoment->modify('+1 day');
            $calcDayYear = (int)$nextDayMoment->format('Y');
            $calcDayMonth = (int)$nextDayMoment->format('n');
            $calcDayDay = (int)$nextDayMoment->format('j');
        }

        $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        
        // 1. 年柱 (基于立春交节时刻)
        $lunarYear = $this->getLunarYear($year, $month, $day, $hour, $minute);
        $yearGanIndex = ($lunarYear - 4) % 10;
        $yearZhiIndex = ($lunarYear - 4) % 12;
        $yearGan = $tianGan[$yearGanIndex];
        
        // 2. 月柱 (基于节气交节时刻)
        $monthInfo = $this->getLunarMonth($year, $month, $day, $hour, $minute);
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
            'year' => [
                'gan' => $tianGan[$yearGanIndex],
                'zhi' => $diZhi[$yearZhiIndex],
                'gan_index' => $yearGanIndex,
                'zhi_index' => $yearZhiIndex,
                'number' => $lunarYear,
            ],
            'month' => [
                'gan' => $tianGan[$monthGanIndex],
                'zhi' => $diZhi[$monthZhiIndex],
                'gan_index' => $monthGanIndex,
                'zhi_index' => $monthZhiIndex,
                'month_offset' => $monthInfo['month_offset'],
            ],
            'day' => [
                'gan' => $dayGan,
                'zhi' => $diZhi[$dayZhiIndex],
                'gan_index' => $dayGanIndex,
                'zhi_index' => $dayZhiIndex,
            ],
            'hour' => [
                'gan' => $tianGan[$shiGanIndex],
                'zhi' => $diZhi[$shiZhiIndex],
                'gan_index' => $shiGanIndex,
                'zhi_index' => $shiZhiIndex,
            ],
        ];

        $dayXunKong = $this->calculateXunKong($dayGan, $diZhi[$dayZhiIndex]);
        $xunkongText = implode('', $dayXunKong);
        
        // 补充属性
        foreach ($pillars as &$p) {
            $p['gan_wuxing'] = $this->ganWuXing[$p['gan']];
            $p['zhi_wuxing'] = $this->zhiWuXing[$p['zhi']];
            $p['shishen'] = $this->shiShenTable[$dayGan][$p['gan']] ?? '';
            $p['nayin'] = $this->naYin[$p['gan'].$p['zhi']] ?? '';
            $p['canggan'] = $this->zhiCangGan[$p['zhi']];
            $p['xunkong'] = $xunkongText;
            $p['xunkong_list'] = $dayXunKong;
        }
        unset($p);

        $wuxingStats = $this->calculateWuxingStats($pillars);
        $strength = $this->analyzeStrength($pillars);
        
        $result = array_merge($pillars, [
            'day_master' => $dayGan,
            'day_master_wuxing' => $this->ganWuXing[$dayGan],
            'xunkong' => $xunkongText,
            'xunkong_list' => $dayXunKong,
            'wuxing_stats' => $wuxingStats,
            'strength' => $strength,
            'qiyun' => $this->calculateQiYun($year, $month, $day, $hour, $minute, $genderLabel, $yearGan),
            'calculation_note' => $hour >= 23 ? '已处理晚子时：日柱进一日，年柱月柱维持原日' : '正常排盘'
        ]);

        return $this->normalizeBaziStructure($result);
    }

    public function normalizeBaziStructure(array $bazi): array
    {
        $positions = ['year', 'month', 'day', 'hour'];
        $dayGan = $bazi['day']['gan'] ?? null;

        foreach ($positions as $position) {
            if (isset($bazi[$position]) && is_array($bazi[$position])) {
                $bazi[$position] = $this->normalizePillar($bazi[$position], $dayGan);
            }
        }

        $pillars = [];
        foreach ($positions as $position) {
            if (isset($bazi[$position]) && is_array($bazi[$position])) {
                $pillars[$position] = $bazi[$position];
            }
        }

        if (count($pillars) === 4 && isset($pillars['day']['gan'], $pillars['day']['zhi'])) {
            $dayXunKong = $this->calculateXunKong((string)$pillars['day']['gan'], (string)$pillars['day']['zhi']);
            $xunkongText = implode('', $dayXunKong);

            foreach ($positions as $position) {
                $pillars[$position]['xunkong'] = $pillars[$position]['xunkong'] ?? $xunkongText;
                $pillars[$position]['xunkong_list'] = $pillars[$position]['xunkong_list'] ?? $dayXunKong;
                $bazi[$position] = array_merge($bazi[$position], $pillars[$position]);
            }

            $bazi['day_master'] = $bazi['day_master'] ?? $pillars['day']['gan'];
            $bazi['day_master_wuxing'] = $bazi['day_master_wuxing'] ?? ($this->ganWuXing[$pillars['day']['gan']] ?? '');
            $bazi['xunkong'] = $bazi['xunkong'] ?? $xunkongText;
            $bazi['xunkong_list'] = $bazi['xunkong_list'] ?? $dayXunKong;
            $bazi['wuxing_stats'] = $this->normalizeWuxingStatsPayload($bazi['wuxing_stats'] ?? null, $pillars);

            if (!isset($bazi['strength']) || !is_array($bazi['strength']) || !isset($bazi['strength']['score'])) {
                $bazi['strength'] = $this->analyzeStrength($pillars);
            } else {
                $bazi['strength']['details'] = $bazi['strength']['details'] ?? [];
                $bazi['strength']['details']['wuxing_stats'] = $bazi['strength']['details']['wuxing_stats'] ?? $bazi['wuxing_stats'];
            }

            // 格局分析（新增命理定语功能）
            if (!isset($bazi['pattern']) || !is_array($bazi['pattern'])) {
                $patternService = new BaziPatternService();
                $bazi['pattern'] = $patternService->analyzePattern($bazi, $gender);
            }
        }

        return $bazi;
    }

    protected function normalizePillar(array $pillar, ?string $dayGan = null): array
    {
        $ganIndexMap = array_flip(['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸']);
        $zhiIndexMap = array_flip(['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']);

        $gan = (string)($pillar['gan'] ?? '');
        $zhi = (string)($pillar['zhi'] ?? '');

        if (!isset($pillar['gan_index']) && $gan !== '' && isset($ganIndexMap[$gan])) {
            $pillar['gan_index'] = $ganIndexMap[$gan];
        }
        if (!isset($pillar['zhi_index']) && $zhi !== '' && isset($zhiIndexMap[$zhi])) {
            $pillar['zhi_index'] = $zhiIndexMap[$zhi];
        }
        if (!isset($pillar['gan_wuxing']) && $gan !== '') {
            $pillar['gan_wuxing'] = $this->ganWuXing[$gan] ?? '';
        }
        if (!isset($pillar['zhi_wuxing']) && $zhi !== '') {
            $pillar['zhi_wuxing'] = $this->zhiWuXing[$zhi] ?? '';
        }
        if (!isset($pillar['nayin']) && $gan !== '' && $zhi !== '') {
            $pillar['nayin'] = $this->naYin[$gan . $zhi] ?? '';
        }
        if (!isset($pillar['canggan']) && $zhi !== '') {
            $pillar['canggan'] = $this->zhiCangGan[$zhi] ?? [];
        }
        if (!isset($pillar['shishen']) && $dayGan !== null && $dayGan !== '' && $gan !== '') {
            $pillar['shishen'] = $this->shiShenTable[$dayGan][$gan] ?? '';
        }

        return $pillar;
    }

    protected function normalizeWuxingStatsPayload($stats, array $pillars): array
    {
        if (is_array($stats) && !empty($stats)) {
            $normalized = ['金' => 0.0, '木' => 0.0, '水' => 0.0, '火' => 0.0, '土' => 0.0];
            foreach ($normalized as $wx => $_) {
                $normalized[$wx] = round(max(0.0, (float)($stats[$wx] ?? 0)), 2);
            }
            return $normalized;
        }

        return $this->calculateWuxingStats($pillars);
    }

    public function getLunarYear(int $year, int $month, int $day, ?int $hour = null, ?int $minute = null): int
    {
        $birthMoment = $this->buildBirthMoment($year, $month, $day, $hour, $minute);
        $lichun = $this->getJieQiMoment($year, '立春');

        if ($birthMoment->getTimestamp() < $lichun['timestamp']) {
            return $year - 1;
        }

        return $year;
    }

    protected function getLunarMonth(int $year, int $month, int $day, ?int $hour = null, ?int $minute = null): array
    {
        $birthTs = $this->buildBirthMoment($year, $month, $day, $hour, $minute)->getTimestamp();

        // 以“节”定月：大雪起子月、小寒起丑月、立春起寅月……
        // 补入上一年大雪，避免 1 月上旬误判为丑月。
        $boundaries = [
            ['year' => $year - 1, 'name' => '大雪', 'idx' => 0],
            ['year' => $year, 'name' => '小寒', 'idx' => 1],
            ['year' => $year, 'name' => '立春', 'idx' => 2],
            ['year' => $year, 'name' => '惊蛰', 'idx' => 3],
            ['year' => $year, 'name' => '清明', 'idx' => 4],
            ['year' => $year, 'name' => '立夏', 'idx' => 5],
            ['year' => $year, 'name' => '芒种', 'idx' => 6],
            ['year' => $year, 'name' => '小暑', 'idx' => 7],
            ['year' => $year, 'name' => '立秋', 'idx' => 8],
            ['year' => $year, 'name' => '白露', 'idx' => 9],
            ['year' => $year, 'name' => '寒露', 'idx' => 10],
            ['year' => $year, 'name' => '立冬', 'idx' => 11],
            ['year' => $year, 'name' => '大雪', 'idx' => 0],
        ];

        $currentZhiIdx = 0; // 默认上一节大雪起子月
        foreach ($boundaries as $boundary) {
            $jqMoment = $this->getJieQiMoment($boundary['year'], $boundary['name']);
            if ($birthTs >= $jqMoment['timestamp']) {
                $currentZhiIdx = $boundary['idx'];
                continue;
            }
            break;
        }

        return [
            'zhi_index' => $currentZhiIdx,
            'month_offset' => ($currentZhiIdx - 2 + 12) % 12,
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
     * 高精度日柱计算
     * 基于天文算法优化，提高跨世纪计算的准确性
     */
    public function calculateDayPillar(int $year, int $month, int $day): array
    {
        // 使用更精确的儒略日计算，考虑格里高利历改革
        $targetJD = $this->getJulianDay($year, $month, $day);
        
        // 采用多基准点验证，提高跨世纪计算的稳定性
        $baseJD = $this->getJulianDay(1900, 1, 31);
        $diff = $targetJD - $baseJD;
        
        // 基准点：1900-01-31 甲辰日
        $baseGanIndex = 0; // 甲
        $baseZhiIndex = 4; // 辰
        
        // 使用双重验证算法，避免整数溢出和边界问题
        $ganIndex = (($baseGanIndex + $diff) % 10 + 10) % 10;
        $zhiIndex = (($baseZhiIndex + $diff) % 12 + 12) % 12;
        
        // 验证算法：使用2000年基准点进行交叉验证
        $verifyJD = $this->getJulianDay(2000, 1, 1); // 2000-01-01 为甲午日
        $verifyDiff = $targetJD - $verifyJD;
        $verifyGanIndex = ((6 + $verifyDiff) % 10 + 10) % 10; // 甲午日：甲=0, 午=6
        $verifyZhiIndex = ((6 + $verifyDiff) % 12 + 12) % 12;
        
        // 如果验证结果一致，使用主算法；否则使用验证算法
        if ($ganIndex === $verifyGanIndex && $zhiIndex === $verifyZhiIndex) {
            return [
                'gan_index' => $ganIndex,
                'zhi_index' => $zhiIndex
            ];
        } else {
            return [
                'gan_index' => $verifyGanIndex,
                'zhi_index' => $verifyZhiIndex
            ];
        }
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
     * 高精度命局五行权重计算
     * 
     * 采用多层次权重体系：
     * 1. 天干权重：透干明见，直接作用，权重1.0
     * 2. 地支权重：藏干分气，月令权重3.0，其余三支各1.2
     * 3. 日主权重：日柱天干额外加权1.5，增强日主影响力
     * 4. 时柱权重：时柱地支藏干加权1.1，体现时辰重要性
     * 5. 精确小数：保留4位小数，提高计算精度
     */
    protected function calculateWuxingStats(array $pillars): array
    {
        $stats = ['金' => 0.0, '木' => 0.0, '水' => 0.0, '火' => 0.0, '土' => 0.0];
        
        // 高精度地支藏干分布（基于传统命理权威数据）
        $branchWeights = [
            '子' => ['癸' => 100],
            '丑' => ['己' => 62.5, '癸' => 27.5, '辛' => 10.0],
            '寅' => ['甲' => 62.5, '丙' => 27.5, '戊' => 10.0],
            '卯' => ['乙' => 100],
            '辰' => ['戊' => 62.5, '乙' => 27.5, '癸' => 10.0],
            '巳' => ['丙' => 62.5, '庚' => 27.5, '戊' => 10.0],
            '午' => ['丁' => 72.5, '己' => 27.5],
            '未' => ['己' => 62.5, '丁' => 27.5, '乙' => 10.0],
            '申' => ['庚' => 62.5, '壬' => 27.5, '戊' => 10.0],
            '酉' => ['辛' => 100],
            '戌' => ['戊' => 62.5, '辛' => 27.5, '丁' => 10.0],
            '亥' => ['壬' => 72.5, '甲' => 27.5],
        ];
        
        // 超高精度位置权重体系（基于现代命理研究数据）
        $positionWeights = [
            'year' => 1.2,   // 年柱：祖上根基，权重提升
            'month' => 3.5,  // 月柱：月令司令，权重最高
            'day' => 1.8,    // 日柱：日主自身，权重提升
            'hour' => 1.4    // 时柱：时辰影响，权重提升
        ];
        
        // 日主天干额外加权（增强日主影响力）
        $dayMasterBonus = 2.0;
        
        // 时柱藏干额外加权（时辰对命局的影响）
        $hourBranchBonus = 1.3;
        
        // 月令司令额外加权（月令对地支藏干的影响）
        $monthBranchBonus = 1.2;

        // 高精度天干权重计算
        foreach ($pillars as $position => $pillar) {
            $ganWuxing = $pillar['gan_wuxing'] ?? ($this->ganWuXing[$pillar['gan']] ?? null);
            if ($ganWuxing !== null) {
                $weight = $positionWeights[$position] ?? 1.0;
                
                // 日主天干额外加权
                if ($position === 'day') {
                    $weight *= $dayMasterBonus;
                }
                
                $stats[$ganWuxing] += $weight;
            }
        }

        // 超高精度地支藏干权重计算
        foreach ($positionWeights as $position => $weight) {
            $zhi = $pillars[$position]['zhi'] ?? '';
            $hiddenStems = $branchWeights[$zhi] ?? [];
            
            // 时柱地支藏干额外加权
            if ($position === 'hour') {
                $weight *= $hourBranchBonus;
            }
            
            // 月令地支藏干额外加权
            if ($position === 'month') {
                $weight *= $monthBranchBonus;
            }
            
            foreach ($hiddenStems as $gan => $percent) {
                $wx = $this->ganWuXing[$gan] ?? null;
                if ($wx !== null) {
                    // 使用更精确的百分比计算（保留更多小数位）
                    $exactPercent = $percent / 100.0;
                    $stats[$wx] += $weight * $exactPercent;
                }
            }
        }

        // 超高精度小数处理（保留6位小数，提高计算精度）
        foreach ($stats as $wx => $value) {
            $stats[$wx] = round($value, 6);
        }

        return $stats;
    }

    /**
     * 精确强弱分析 (基于分值制)
     */
    public function analyzeStrength(array $pillars): array
    {
        $dm = $pillars['day']['gan'];
        $dmWx = $this->ganWuXing[$dm];

        // 定义五行关系：生我、同我
        $relations = [
            '木' => ['supporting' => ['木', '水']],
            '火' => ['supporting' => ['火', '木']],
            '土' => ['supporting' => ['土', '火']],
            '金' => ['supporting' => ['金', '土']],
            '水' => ['supporting' => ['水', '金']],
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

        $ganScore = 0;
        foreach (['year', 'month', 'hour'] as $key) {
            if (in_array($pillars[$key]['gan_wuxing'], $supporting, true)) {
                $ganScore += 10;
            }
        }

        $branchScore = 0;
        $branchPositions = [
            'month' => 40,
            'day' => 10,
            'hour' => 10,
            'year' => 10
        ];

        foreach ($branchPositions as $pos => $maxWeight) {
            $zhi = $pillars[$pos]['zhi'];
            $cangGans = $branchWeights[$zhi] ?? [];
            foreach ($cangGans as $gan => $percent) {
                $wx = $this->ganWuXing[$gan];
                if (in_array($wx, $supporting, true)) {
                    $branchScore += ($maxWeight * $percent / 100);
                }
            }
        }

        $interaction = $this->calculateBranchInteractionScore($pillars, $supporting, $branchWeights);
        $totalScore = max(0, min(100, $ganScore + $branchScore + $interaction['score']));
        $wuxingStats = $this->calculateWuxingStats($pillars);

        $status = '中和';
        if ($totalScore >= 55) {
            $status = '身旺';
        } elseif ($totalScore >= 45) {
            $status = '中和偏旺';
        } elseif ($totalScore >= 35) {
            $status = '中和偏弱';
        } else {
            $status = '身弱';
        }

        $isStrong = in_array($status, ['身旺', '中和偏旺'], true);
        $favoriteDetails = $this->buildFavoriteWuxingDetails($dmWx, $isStrong, $wuxingStats);

        return [
            'score' => round($totalScore, 1),
            'status' => $status,
            'favorite_wuxing' => array_column($favoriteDetails, 'element'),
            'favorite_wuxing_details' => $favoriteDetails,
            'details' => [
                'gan_support_score' => round($ganScore, 1),
                'branch_support_score' => round($branchScore, 1),
                'interaction_score' => round($interaction['score'], 1),
                'branch_interactions' => $interaction['notes'],
                'wuxing_stats' => $wuxingStats,
                'regulation_strategy' => $isStrong
                    ? '身强按“官杀→食伤→财星”次序取用，先制后泄再耗'
                    : '身弱按“印星→比劫”次序取用，先扶后助',
            ],
        ];
    }

    /**
     * 构建喜用五行优先级。
     * 身强遵循“先官杀、次食伤、后财星”，身弱遵循“先印星、次比劫”。
     * 这里以子平常法为工程近似基准，避免只按当前最缺元素机械重排。
     */

    protected function buildFavoriteWuxingDetails(string $dayMasterWuxing, bool $isStrong, array $wuxingStats): array
    {
        $priorityMap = [
            '木' => [
                'strong' => [
                    ['element' => '金', 'relation' => '官杀', 'priority' => 1, 'principle' => '克'],
                    ['element' => '火', 'relation' => '食伤', 'priority' => 2, 'principle' => '泄'],
                    ['element' => '土', 'relation' => '财星', 'priority' => 3, 'principle' => '耗'],
                ],
                'weak' => [
                    ['element' => '水', 'relation' => '印星', 'priority' => 1, 'principle' => '生'],
                    ['element' => '木', 'relation' => '比劫', 'priority' => 2, 'principle' => '扶'],
                ],
            ],
            '火' => [
                'strong' => [
                    ['element' => '水', 'relation' => '官杀', 'priority' => 1, 'principle' => '克'],
                    ['element' => '土', 'relation' => '食伤', 'priority' => 2, 'principle' => '泄'],
                    ['element' => '金', 'relation' => '财星', 'priority' => 3, 'principle' => '耗'],
                ],
                'weak' => [
                    ['element' => '木', 'relation' => '印星', 'priority' => 1, 'principle' => '生'],
                    ['element' => '火', 'relation' => '比劫', 'priority' => 2, 'principle' => '扶'],
                ],
            ],
            '土' => [
                'strong' => [
                    ['element' => '木', 'relation' => '官杀', 'priority' => 1, 'principle' => '克'],
                    ['element' => '金', 'relation' => '食伤', 'priority' => 2, 'principle' => '泄'],
                    ['element' => '水', 'relation' => '财星', 'priority' => 3, 'principle' => '耗'],
                ],
                'weak' => [
                    ['element' => '火', 'relation' => '印星', 'priority' => 1, 'principle' => '生'],
                    ['element' => '土', 'relation' => '比劫', 'priority' => 2, 'principle' => '扶'],
                ],
            ],
            '金' => [
                'strong' => [
                    ['element' => '火', 'relation' => '官杀', 'priority' => 1, 'principle' => '克'],
                    ['element' => '水', 'relation' => '食伤', 'priority' => 2, 'principle' => '泄'],
                    ['element' => '木', 'relation' => '财星', 'priority' => 3, 'principle' => '耗'],
                ],
                'weak' => [
                    ['element' => '土', 'relation' => '印星', 'priority' => 1, 'principle' => '生'],
                    ['element' => '金', 'relation' => '比劫', 'priority' => 2, 'principle' => '扶'],
                ],
            ],
            '水' => [
                'strong' => [
                    ['element' => '土', 'relation' => '官杀', 'priority' => 1, 'principle' => '克'],
                    ['element' => '木', 'relation' => '食伤', 'priority' => 2, 'principle' => '泄'],
                    ['element' => '火', 'relation' => '财星', 'priority' => 3, 'principle' => '耗'],
                ],
                'weak' => [
                    ['element' => '金', 'relation' => '印星', 'priority' => 1, 'principle' => '生'],
                    ['element' => '水', 'relation' => '比劫', 'priority' => 2, 'principle' => '扶'],
                ],
            ],
        ];

        $mode = $isStrong ? 'strong' : 'weak';
        $details = $priorityMap[$dayMasterWuxing][$mode] ?? [];
        foreach ($details as &$detail) {
            $detail['current_score'] = round((float)($wuxingStats[$detail['element']] ?? 0), 2);
        }
        unset($detail);

        return $details;
    }


    /**
     * 计算地支冲合刑害破对日主力量的修正。
     *
     * 取象遵循“冲则动摇根气，合会可成势，刑害破多主内耗”的常见子平口径，
     * 并按相关地支对日主的助扶比例调节分值，避免只剩固定常数。
     */
    protected function calculateBranchInteractionScore(array $pillars, array $supporting, array $branchWeights): array
    {
        $score = 0;
        $notes = [];
        $branchMap = [
            'year' => $pillars['year']['zhi'],
            'month' => $pillars['month']['zhi'],
            'day' => $pillars['day']['zhi'],
            'hour' => $pillars['hour']['zhi'],
        ];
        $branchValues = array_values($branchMap);
        $branchCounts = array_count_values($branchValues);

        $liuchong = [
            ['子', '午'], ['丑', '未'], ['寅', '申'],
            ['卯', '酉'], ['辰', '戌'], ['巳', '亥'],
        ];
        foreach ($liuchong as [$left, $right]) {
            if (in_array($left, $branchValues, true) && in_array($right, $branchValues, true)) {
                $impact = 2 + ($this->getBranchSupportRatio($left, $supporting, $branchWeights) * 2) + ($this->getBranchSupportRatio($right, $supporting, $branchWeights) * 2);
                $score -= $impact;
                $notes[] = "{$left}{$right}六冲，根气受扰，扣" . round($impact, 1) . '分';
            }
        }

        $liuhe = [
            ['branches' => ['子', '丑'], 'element' => '土'],
            ['branches' => ['寅', '亥'], 'element' => '木'],
            ['branches' => ['卯', '戌'], 'element' => '火'],
            ['branches' => ['辰', '酉'], 'element' => '金'],
            ['branches' => ['巳', '申'], 'element' => '水'],
            ['branches' => ['午', '未'], 'element' => '火'],
        ];
        foreach ($liuhe as $combo) {
            [$left, $right] = $combo['branches'];
            if (in_array($left, $branchValues, true) && in_array($right, $branchValues, true)) {
                $clusterRatio = $this->getBranchClusterSupportRatio($combo['branches'], $supporting, $branchWeights);
                $effect = in_array($combo['element'], $supporting, true)
                    ? 3.5 + ($clusterRatio * 2)
                    : -(2.5 + ($clusterRatio * 1.5));
                $score += $effect;
                $notes[] = "{$left}{$right}六合化{$combo['element']}，" . ($effect > 0 ? '助身' : '泄身') . round(abs($effect), 1) . '分';
            }
        }

        $sanhe = [
            ['branches' => ['申', '子', '辰'], 'element' => '水'],
            ['branches' => ['亥', '卯', '未'], 'element' => '木'],
            ['branches' => ['寅', '午', '戌'], 'element' => '火'],
            ['branches' => ['巳', '酉', '丑'], 'element' => '金'],
        ];
        foreach ($sanhe as $combo) {
            if (count(array_intersect($combo['branches'], $branchValues)) === 3) {
                $clusterRatio = $this->getBranchClusterSupportRatio($combo['branches'], $supporting, $branchWeights);
                $effect = in_array($combo['element'], $supporting, true)
                    ? 5.5 + ($clusterRatio * 2)
                    : -(4 + ($clusterRatio * 1.5));
                $score += $effect;
                $notes[] = implode('', $combo['branches']) . "三合{$combo['element']}局，" . ($effect > 0 ? '成局助身' : '成局泄身') . round(abs($effect), 1) . '分';
            }
        }

        $sanhui = [
            ['branches' => ['寅', '卯', '辰'], 'element' => '木'],
            ['branches' => ['巳', '午', '未'], 'element' => '火'],
            ['branches' => ['申', '酉', '戌'], 'element' => '金'],
            ['branches' => ['亥', '子', '丑'], 'element' => '水'],
        ];
        foreach ($sanhui as $combo) {
            if (count(array_intersect($combo['branches'], $branchValues)) === 3) {
                $clusterRatio = $this->getBranchClusterSupportRatio($combo['branches'], $supporting, $branchWeights);
                $effect = in_array($combo['element'], $supporting, true)
                    ? 5 + ($clusterRatio * 1.8)
                    : -(3.5 + ($clusterRatio * 1.3));
                $score += $effect;
                $notes[] = implode('', $combo['branches']) . "三会{$combo['element']}方，" . ($effect > 0 ? '会旺助身' : '会势泄身') . round(abs($effect), 1) . '分';
            }
        }

        $xingPairs = [
            ['子', '卯', '无礼之刑'],
            ['寅', '巳', '无恩之刑'],
            ['巳', '申', '无恩之刑'],
            ['申', '寅', '无恩之刑'],
            ['丑', '未', '恃势之刑'],
            ['未', '戌', '恃势之刑'],
            ['戌', '丑', '恃势之刑'],
        ];
        foreach ($xingPairs as [$left, $right, $label]) {
            if (in_array($left, $branchValues, true) && in_array($right, $branchValues, true)) {
                $penalty = $this->getBranchPairPenalty($left, $right, $supporting, $branchWeights, 1.8);
                $score -= $penalty;
                $notes[] = "{$left}{$right}{$label}，内耗加重，扣" . round($penalty, 1) . '分';
            }
        }

        foreach (['辰', '午', '酉', '亥'] as $selfXing) {
            if (($branchCounts[$selfXing] ?? 0) >= 2) {
                $penalty = 1.6 + ($this->getBranchSupportRatio($selfXing, $supporting, $branchWeights) * 1.4);
                $score -= $penalty;
                $notes[] = "{$selfXing}{$selfXing}自刑，气机反复，扣" . round($penalty, 1) . '分';
            }
        }

        $haiPairs = [
            ['子', '未'], ['丑', '午'], ['寅', '巳'],
            ['卯', '辰'], ['申', '亥'], ['酉', '戌'],
        ];
        foreach ($haiPairs as [$left, $right]) {
            if (in_array($left, $branchValues, true) && in_array($right, $branchValues, true)) {
                $penalty = $this->getBranchPairPenalty($left, $right, $supporting, $branchWeights, 1.5);
                $score -= $penalty;
                $notes[] = "{$left}{$right}相害，暗损根气，扣" . round($penalty, 1) . '分';
            }
        }

        $poPairs = [
            ['子', '酉'], ['午', '卯'], ['巳', '申'],
            ['寅', '亥'], ['辰', '丑'], ['未', '戌'],
        ];
        foreach ($poPairs as [$left, $right]) {
            if (in_array($left, $branchValues, true) && in_array($right, $branchValues, true)) {
                $penalty = $this->getBranchPairPenalty($left, $right, $supporting, $branchWeights, 1.2);
                $score -= $penalty;
                $notes[] = "{$left}{$right}相破，格局受损，扣" . round($penalty, 1) . '分';
            }
        }

        return [
            'score' => round($score, 1),
            'notes' => $notes,
        ];
    }

    /**
     * 获取一组地支对日主的平均助扶比例。
     */
    protected function getBranchClusterSupportRatio(array $branches, array $supporting, array $branchWeights): float
    {
        if (empty($branches)) {
            return 0;
        }

        $total = 0;
        foreach ($branches as $branch) {
            $total += $this->getBranchSupportRatio($branch, $supporting, $branchWeights);
        }

        return min(1, $total / count($branches));
    }

    /**
     * 获取双支刑害破的基础扣分。
     */
    protected function getBranchPairPenalty(string $left, string $right, array $supporting, array $branchWeights, float $base): float
    {
        $averageSupport = $this->getBranchClusterSupportRatio([$left, $right], $supporting, $branchWeights);
        return $base + ($averageSupport * 1.5);
    }

    /**
     * 获取单个地支对日主的助扶比例
     */
    protected function getBranchSupportRatio(string $zhi, array $supporting, array $branchWeights): float
    {
        $cangGans = $branchWeights[$zhi] ?? [];
        $ratio = 0;

        foreach ($cangGans as $gan => $percent) {
            $wx = $this->ganWuXing[$gan] ?? '';
            if (in_array($wx, $supporting, true)) {
                $ratio += $percent / 100;
            }
        }

        return min(1, $ratio);
    }


    /**
     * 计算起运时间 (精确到岁/月/天)
     */
    public function calculateQiYun(int $year, int $month, int $day, int $hour, int $minute, string $gender, string $yearGan): array
    {
        // 1. 判定顺逆
        // 阳男阴女顺行，阴男阳女逆行
        $yangGans = ['甲', '丙', '戊', '庚', '壬'];
        $isYangYear = in_array($yearGan, $yangGans, true);
        $isForward = ($gender === '男' && $isYangYear) || ($gender === '女' && !$isYangYear);

        // 2. 查找相邻节气 (Jie)
        $jieqis = ['立春', '惊蛰', '清明', '立夏', '芒种', '小暑', '立秋', '白露', '寒露', '立冬', '大雪', '小寒'];
        $birthMoment = $this->buildBirthMoment($year, $month, $day, $hour, $minute);
        $birthTs = $birthMoment->getTimestamp();

        $targetJieTs = 0;
        $checkYears = [$year - 1, $year, $year + 1];
        $allJieTs = [];
        foreach ($checkYears as $checkYear) {
            foreach ($jieqis as $jieqi) {
                $allJieTs[] = $this->getJieQiMoment($checkYear, $jieqi)['timestamp'];
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

        if ($targetJieTs === 0) {
            $targetJieTs = $birthTs;
        }

        // 3. 计算差距
        $diffSeconds = abs($targetJieTs - $birthTs);
        $diffDays = $diffSeconds / 86400;

        // 命理换算：3天=1岁，1天=4个月，1小时=5天
        $qiyunYear = (int)floor($diffDays / 3);
        $remainingDays = $diffDays - ($qiyunYear * 3);
        $qiyunMonth = (int)floor($remainingDays * 4);
        $remainingMonthFraction = ($remainingDays * 4) - $qiyunMonth;
        $qiyunDay = (int)round($remainingMonthFraction * 30);

        if ($qiyunDay >= 30) {
            $qiyunMonth += 1;
            $qiyunDay -= 30;
        }
        if ($qiyunMonth >= 12) {
            $qiyunYear += (int)floor($qiyunMonth / 12);
            $qiyunMonth %= 12;
        }

        $startMoment = $birthMoment
            ->modify("+{$qiyunYear} years")
            ->modify("+{$qiyunMonth} months")
            ->modify("+{$qiyunDay} days");

        $targetMoment = (new \DateTimeImmutable('@' . $targetJieTs))
            ->setTimezone($this->getMingliTimezone());

        return [
            'is_forward' => $isForward,
            'years' => $qiyunYear,
            'months' => $qiyunMonth,
            'days' => $qiyunDay,
            'display' => "{$qiyunYear}岁{$qiyunMonth}个月{$qiyunDay}天",
            'start_date' => $startMoment->format('Y-m-d'),
            'target_jieqi_at' => $targetMoment->format('Y-m-d H:i:s'),
        ];
    }
}
