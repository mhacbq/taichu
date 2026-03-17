<?php
declare(strict_types=1);

namespace app\service;

/**
 * 八字计算服务
 * 
 * 封装八字排盘计算逻辑，供控制器和其他服务调用
 * 避免控制器之间直接实例化调用，符合MVC规范
 */
class BaziCalculationService
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
     * 地支藏干
     */
    protected $zhiCangGan = [
        '子' => ['癸'],
        '丑' => ['己', '癸', '辛'],
        '寅' => ['甲', '丙', '戊'],
        '卯' => ['乙'],
        '辰' => ['戊', '乙', '癸'],
        '巳' => ['丙', '庚', '戊'],
        '午' => ['丁', '己'],
        '未' => ['己', '丁', '乙'],
        '申' => ['庚', '壬', '戊'],
        '酉' => ['辛'],
        '戌' => ['戊', '辛', '丁'],
        '亥' => ['壬', '甲']
    ];
    
    /**
     * 年干起月干表（五虎遁月法）
     */
    protected $yearGanToMonthGanStart = [
        '甲' => '丙', '乙' => '戊',
        '丙' => '庚', '丁' => '壬',
        '戊' => '甲', '己' => '丙',
        '庚' => '戊', '辛' => '庚',
        '壬' => '壬', '癸' => '甲'
    ];
    
    /**
     * 十神关系表（以日干为基准）
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
     * 纳音五行表
     */
    protected $naYin = [
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
        '壬戌' => '大海水', '癸亥' => '大海水'
    ];
    
    /**
     * 节气数据（1900-2050年主要节气）
     */
    protected $jieQiData = [
        // 1990年节气数据
        1990 => [
            '立春' => '1990-02-04', '雨水' => '1990-02-19',
            '惊蛰' => '1990-03-06', '春分' => '1990-03-21',
            '清明' => '1990-04-05', '谷雨' => '1990-04-20',
            '立夏' => '1990-05-06', '小满' => '1990-05-21',
            '芒种' => '1990-06-06', '夏至' => '1990-06-21',
            '小暑' => '1990-07-07', '大暑' => '1990-07-23',
            '立秋' => '1990-08-08', '处暑' => '1990-08-23',
            '白露' => '1990-09-08', '秋分' => '1990-09-23',
            '寒露' => '1990-10-08', '霜降' => '1990-10-24',
            '立冬' => '1990-11-08', '小雪' => '1990-11-22',
            '大雪' => '1990-12-07', '冬至' => '1990-12-22',
            '小寒' => '1991-01-06', '大寒' => '1991-01-20'
        ],
        // 1995年节气数据
        1995 => [
            '立春' => '1995-02-04', '雨水' => '1995-02-19',
            '惊蛰' => '1995-03-06', '春分' => '1995-03-21',
            '清明' => '1995-04-05', '谷雨' => '1995-04-20',
            '立夏' => '1995-05-06', '小满' => '1995-05-21',
            '芒种' => '1995-06-06', '夏至' => '1995-06-21',
            '小暑' => '1995-07-07', '大暑' => '1995-07-23',
            '立秋' => '1995-08-08', '处暑' => '1995-08-23',
            '白露' => '1995-09-08', '秋分' => '1995-09-23',
            '寒露' => '1995-10-09', '霜降' => '1995-10-24',
            '立冬' => '1995-11-08', '小雪' => '1995-11-22',
            '大雪' => '1995-12-07', '冬至' => '1995-12-22',
            '小寒' => '1996-01-06', '大寒' => '1996-01-20'
        ],
        // 2000年节气数据
        2000 => [
            '立春' => '2000-02-04', '雨水' => '2000-02-19',
            '惊蛰' => '2000-03-05', '春分' => '2000-03-20',
            '清明' => '2000-04-04', '谷雨' => '2000-04-20',
            '立夏' => '2000-05-05', '小满' => '2000-05-21',
            '芒种' => '2000-06-05', '夏至' => '2000-06-21',
            '小暑' => '2000-07-07', '大暑' => '2000-07-22',
            '立秋' => '2000-08-07', '处暑' => '2000-08-23',
            '白露' => '2000-09-07', '秋分' => '2000-09-23',
            '寒露' => '2000-10-08', '霜降' => '2000-10-23',
            '立冬' => '2000-11-07', '小雪' => '2000-11-22',
            '大雪' => '2000-12-07', '冬至' => '2000-12-21',
            '小寒' => '2001-01-05', '大寒' => '2001-01-20'
        ]
    ];

    /**
     * 计算八字
     * 
     * @param string $birthDate 出生日期时间 (格式: Y-m-d H:i:s)
     * @return array 八字数据
     */
    public function calculateBazi(string $birthDate): array
    {
        $timestamp = strtotime($birthDate);
        $year = (int)date('Y', $timestamp);
        $month = (int)date('n', $timestamp);
        $day = (int)date('j', $timestamp);
        $hour = (int)date('G', $timestamp);
        
        // 天干
        $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        // 地支
        $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        
        // ===== 年柱计算 =====
        $lunarYear = $this->getLunarYear($year, $month, $day);
        $yearGanIndex = ($lunarYear - 4) % 10;
        $yearZhiIndex = ($lunarYear - 4) % 12;
        
        // ===== 月柱计算 =====
        $lunarMonthInfo = $this->getLunarMonth($year, $month, $day);
        $monthZhiIndex = $lunarMonthInfo['zhi_index'];
        
        // 月干：五虎遁月法
        $yearGan = $tianGan[$yearGanIndex];
        $monthGanStart = $this->yearGanToMonthGanStart[$yearGan];
        $monthGanIndex = array_search($monthGanStart, $tianGan);
        $monthGanIndex = ($monthGanIndex + $lunarMonthInfo['month'] - 1) % 10;
        
        // ===== 日柱计算 =====
        // 处理晚子时 (23:00-00:00)：日柱需切换至次日
        $calcYear = $year;
        $calcMonth = $month;
        $calcDay = $day;
        if ($hour >= 23) {
            $nextDay = strtotime("+1 day", strtotime("$year-$month-$day"));
            $calcYear = (int)date('Y', $nextDay);
            $calcMonth = (int)date('n', $nextDay);
            $calcDay = (int)date('j', $nextDay);
        }
        
        $dayPillar = $this->calculateDayPillar($calcYear, $calcMonth, $calcDay);
        $dayGanIndex = $dayPillar['gan_index'];
        $dayZhiIndex = $dayPillar['zhi_index'];
        $dayGan = $tianGan[$dayGanIndex];
        
        // ===== 时柱计算 =====
        // 时辰划分：23-1子, 1-3丑, 3-5寅, 5-7卯, 7-9辰, 9-11巳
        // 11-13午, 13-15未, 15-17申, 17-19酉, 19-21戌, 21-23亥
        // 注意：23:00-00:00 属于早子时还是晚子时在命理界有争议，此处统一按 23 点后进一日计算
        if ($hour >= 23) {
            $shiZhiIndex = 0; // 子
        } else {
            $shiZhiIndex = (int)(($hour + 1) / 2) % 12;
        }
        
        // 时干：根据日干推算（日上起时法）
        // 甲己日起甲子，乙庚日起丙子，丙辛日起戊子，丁壬日起庚子，戊癸日起壬子
        $dayGanHourStart = [
            '甲' => '甲', '己' => '甲',
            '乙' => '丙', '庚' => '丙',
            '丙' => '戊', '辛' => '戊',
            '丁' => '庚', '壬' => '庚',
            '戊' => '壬', '癸' => '壬'
        ];
        $hourGanStart = $dayGanHourStart[$dayGan];
        $shiGanIndex = (array_search($hourGanStart, $tianGan) + $shiZhiIndex) % 10;
        
        // 构建四柱数据
        $pillars = [
            'year' => [
                'gan' => $tianGan[$yearGanIndex],
                'zhi' => $diZhi[$yearZhiIndex],
                'gan_index' => $yearGanIndex,
                'zhi_index' => $yearZhiIndex
            ],
            'month' => [
                'gan' => $tianGan[$monthGanIndex],
                'zhi' => $diZhi[$monthZhiIndex],
                'gan_index' => $monthGanIndex,
                'zhi_index' => $monthZhiIndex
            ],
            'day' => [
                'gan' => $dayGan,
                'zhi' => $diZhi[$dayZhiIndex],
                'gan_index' => $dayGanIndex,
                'zhi_index' => $dayZhiIndex
            ],
            'hour' => [
                'gan' => $tianGan[$shiGanIndex],
                'zhi' => $diZhi[$shiZhiIndex],
                'gan_index' => $shiGanIndex,
                'zhi_index' => $shiZhiIndex
            ]
        ];
        
        // 为每个柱添加专业信息
        foreach ($pillars as $key => &$pillar) {
            $gan = $pillar['gan'];
            $zhi = $pillar['zhi'];
            
            // 五行属性
            $pillar['gan_wuxing'] = $this->ganWuXing[$gan];
            $pillar['zhi_wuxing'] = $this->zhiWuXing[$zhi];
            
            // 十神（以日干为基准）
            $pillar['shishen'] = $this->calculateShiShen($dayGan, $gan);
            
            // 地支藏干
            $pillar['canggan'] = $this->zhiCangGan[$zhi];
            
            // 藏干十神
            $pillar['canggan_shishen'] = array_map(function($cg) use ($dayGan) {
                return $this->calculateShiShen($dayGan, $cg);
            }, $pillar['canggan']);
            
            // 纳音
            $pillar['nayin'] = $this->naYin[$gan . $zhi] ?? '';
        }
        
        // 计算五行统计
        $wuxingCount = ['金' => 0, '木' => 0, '水' => 0, '火' => 0, '土' => 0];
        foreach ($pillars as $pillar) {
            $wuxingCount[$pillar['gan_wuxing']]++;
            $wuxingCount[$pillar['zhi_wuxing']]++;
        }
        
        return [
            'year' => $pillars['year'],
            'month' => $pillars['month'],
            'day' => $pillars['day'],
            'hour' => $pillars['hour'],
            'wuxing_stats' => $wuxingCount,
            'day_master' => $dayGan,
            'day_master_wuxing' => $this->ganWuXing[$dayGan],
            'strength' => $this->analyzeStrength($pillars),
            'calculation_note' => '已优化：支持日主强弱分析（得令/得地/得助）'
        ];
    }
    
    /**
     * 分析日主强弱
     * 
     * @param array $pillars 四柱数据
     * @return array 强弱分析结果
     */
    public function analyzeStrength(array $pillars): array
    {
        $dayMaster = $pillars['day']['gan'];
        $dayMasterWuXing = $this->ganWuXing[$dayMaster];
        
        $score = 0;
        $details = [];
        
        // 1. 得令 (30分) - 月令五行对日主的影响
        $monthZhi = $pillars['month']['zhi'];
        $monthWuXing = $this->zhiWuXing[$monthZhi];
        $isDeLing = $this->isSupporting($monthWuXing, $dayMasterWuXing);
        if ($isDeLing) {
            $score += 30;
            $details[] = "得令：生于{$monthZhi}月({$monthWuXing})，月令对日主有生助作用。";
        } else {
            $details[] = "失令：生于{$monthZhi}月({$monthWuXing})，月令对日主处于克泄状态。";
        }
        
        // 2. 得地 (30分) - 地支藏干通根情况
        $branches = [$pillars['year']['zhi'], $pillars['month']['zhi'], $pillars['day']['zhi'], $pillars['hour']['zhi']];
        $rootCount = 0;
        foreach ($branches as $zhi) {
            if (in_array($dayMaster, $this->zhiCangGan[$zhi])) {
                $rootCount++;
            }
        }
        $rootScore = min(30, $rootCount * 10);
        $score += $rootScore;
        if ($rootCount > 0) {
            $details[] = "得地：地支中有{$rootCount}处通根，日主根基较稳。";
        } else {
            $details[] = "失地：地支中无通根，日主浮现无根。";
        }
        
        // 3. 得助 (40分) - 天干帮扶情况
        $stems = [$pillars['year']['gan'], $pillars['month']['gan'], $pillars['hour']['gan']];
        $assistCount = 0;
        foreach ($stems as $gan) {
            if ($this->isSupporting($this->ganWuXing[$gan], $dayMasterWuXing)) {
                $assistCount++;
            }
        }
        $assistScore = min(40, $assistCount * 15);
        $score += $assistScore;
        if ($assistCount > 0) {
            $details[] = "得助：天干中有{$assistCount}处同类或相生五行帮扶。";
        }
        
        // 综合判定
        $status = '中和';
        if ($score >= 70) $status = '极强';
        elseif ($score >= 55) $status = '偏强';
        elseif ($score <= 30) $status = '极弱';
        elseif ($score <= 45) $status = '偏弱';
        
        return [
            'score' => $score,
            'status' => $status,
            'details' => $details,
            'is_strong' => $score >= 50
        ];
    }

    /**
     * 判断五行是否生助（同类或相生）
     */
    protected function isSupporting(string $source, string $target): bool
    {
        if ($source === $target) return true; // 同类
        
        $generating = [
            '木' => '火',
            '火' => '土',
            '土' => '金',
            '金' => '水',
            '水' => '木'
        ];
        
        return ($generating[$source] === $target); // 相生
    }

    /**
     * 获取农历年份（考虑立春）
     */
    protected function getLunarYear(int $year, int $month, int $day): int
    {
        // 优先使用精确节气数据
        if (isset($this->jieQiData[$year]['立春'])) {
            $liChunDate = $this->jieQiData[$year]['立春'];
            $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
            if (strtotime($currentDate) >= strtotime($liChunDate)) {
                return $year;
            }
            return $year - 1;
        }

        // 兜底方案：2月4日作为立春分界
        if ($month > 2 || ($month == 2 && $day >= 4)) {
            return $year;
        }
        return $year - 1;
    }
    
    /**
     * 获取农历月份信息（考虑节气）
     * 
     * 寅月为正月(索引2), 卯月为二月(索引3)...以此类推
     * 月份切换点为：立春、惊蛰、清明、立夏、芒种、小暑、立秋、白露、寒露、立冬、大雪、小寒
     */
    protected function getLunarMonth(int $year, int $month, int $day): array
    {
        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $currentTime = strtotime($currentDate);
        
        // 节气与地支索引映射 (正月寅为2, 二月卯为3...)
        $monthJieQi = [
            ['name' => '立春', 'index' => 2],
            ['name' => '惊蛰', 'index' => 3],
            ['name' => '清明', 'index' => 4],
            ['name' => '立夏', 'index' => 5],
            ['name' => '芒种', 'index' => 6],
            ['name' => '小暑', 'index' => 7],
            ['name' => '立秋', 'index' => 8],
            ['name' => '白露', 'index' => 9],
            ['name' => '寒露', 'index' => 10],
            ['name' => '立冬', 'index' => 11],
            ['name' => '大雪', 'index' => 0],
            ['name' => '小寒', 'index' => 1]
        ];

        // 优先使用节气数据
        if (isset($this->jieQiData[$year])) {
            $data = $this->jieQiData[$year];
            
            // 从后往前找，找到第一个早于当前日期的节气
            for ($i = count($monthJieQi) - 1; $i >= 0; $i--) {
                $jq = $monthJieQi[$i];
                if (isset($data[$jq['name']]) && $currentTime >= strtotime($data[$jq['name']])) {
                    return [
                        'month' => ($i + 1), // 这里的month仅作参考
                        'zhi_index' => $jq['index'],
                        'jieqi' => $jq['name']
                    ];
                }
            }
            
            // 如果早于立春，则是上一年末的小寒后（丑月）
            return [
                'month' => 12,
                'zhi_index' => 1,
                'jieqi' => '小寒(上年)'
            ];
        }

        // 兜底方案：简化计算
        $zhiIndex = ($month + 1) % 12;
        
        return [
            'month' => $month,
            'zhi_index' => $zhiIndex
        ];
    }

    
    /**
     * 计算日柱（使用蔡勒公式简化版）
     */
    protected function calculateDayPillar(int $year, int $month, int $day): array
    {
        // 天干
        $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        // 地支
        $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        
        // 使用基准日计算（1900-01-31是甲子日）
        $baseDate = strtotime('1900-01-31');
        $targetDate = strtotime("$year-$month-$day");
        $daysDiff = (int)(($targetDate - $baseDate) / 86400);
        
        // 计算天干地支索引
        $ganIndex = ($daysDiff % 10 + 10) % 10;
        $zhiIndex = ($daysDiff % 12 + 12) % 12;
        
        return [
            'gan_index' => $ganIndex,
            'zhi_index' => $zhiIndex
        ];
    }
    
    /**
     * 计算十神
     */
    protected function calculateShiShen(string $dayGan, string $targetGan): string
    {
        return $this->shiShenTable[$dayGan][$targetGan] ?? '未知';
    }
}
