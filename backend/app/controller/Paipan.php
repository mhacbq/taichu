<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\BaziRecord;
use app\model\PointsRecord;
use think\facade\Db;

class Paipan extends BaseController
{
    // 排盘所需积分
    const BAZI_POINTS_COST = 10;
    
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * 八字排盘
     */
    public function bazi()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
        // 验证参数
        if (empty($data['birthDate'])) {
            return $this->error('请提供出生日期');
        }
        
        if (empty($data['gender'])) {
            return $this->error('请提供性别');
        }
        
        // 检查用户积分
        $userModel = \app\model\User::find($user['sub']);
        if (!$userModel) {
            return $this->error('用户不存在', 404);
        }
        
        if ($userModel->points < self::BAZI_POINTS_COST) {
            return $this->error('积分不足，请先充值', 403);
        }
        
        // 解析出生日期
        $birthDate = $data['birthDate'];
        $gender = $data['gender'];
        $location = $data['location'] ?? '';
        
        // 计算八字
        $bazi = $this->calculateBazi($birthDate);
        
        // 生成分析
        $analysis = $this->generateAnalysis($bazi, $gender);
        
        // 计算大运
        $daYun = $this->calculateDaYun($bazi, $gender, $birthDate);
        $daYun = $this->analyzeDaYunLuck($daYun, $bazi);
        
        // 计算流年（最近5年）
        $currentYear = (int)date('Y');
        $liuNian = $this->calculateLiuNian($currentYear, 5);
        
        Db::startTrans();
        try {
            // 扣除积分
            $userModel->deductPoints(self::BAZI_POINTS_COST);
            
            // 记录积分变动
            PointsRecord::record(
                $user['sub'],
                '八字排盘消耗',
                -self::BAZI_POINTS_COST,
                'bazi',
                0,
                "排盘日期: {$birthDate}"
            );
            
            // 保存排盘记录
            $record = BaziRecord::create([
                'user_id' => $user['sub'],
                'birth_date' => $birthDate,
                'gender' => $gender,
                'location' => $location,
                'year_gan' => $bazi['year']['gan'],
                'year_zhi' => $bazi['year']['zhi'],
                'month_gan' => $bazi['month']['gan'],
                'month_zhi' => $bazi['month']['zhi'],
                'day_gan' => $bazi['day']['gan'],
                'day_zhi' => $bazi['day']['zhi'],
                'hour_gan' => $bazi['hour']['gan'],
                'hour_zhi' => $bazi['hour']['zhi'],
                'analysis' => $analysis,
            ]);
            
            Db::commit();
            
            return $this->success([
                'id' => $record->id,
                'bazi' => $bazi,
                'analysis' => $analysis,
                'dayun' => $daYun,
                'liunian' => $liuNian,
                'points_cost' => self::BAZI_POINTS_COST,
                'remaining_points' => $userModel->points,
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('排盘失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取排盘历史
     */
    public function history()
    {
        $user = $this->request->user;
        $limit = $this->request->get('limit', 20);
        
        $history = BaziRecord::getUserHistory($user['sub'], (int)$limit);
        
        return $this->success($history);
    }
    
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
     * 六十甲子纳音
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
     * 计算十神
     * 以日干为基准，计算其他干支对应的十神
     */
    protected function calculateShiShen(string $dayGan, string $targetGan): string
    {
        // 天干阴阳
        $ganYinYang = [
            '甲' => '阳', '乙' => '阴', '丙' => '阳', '丁' => '阴', '戊' => '阳',
            '己' => '阴', '庚' => '阳', '辛' => '阴', '壬' => '阳', '癸' => '阴'
        ];
        
        // 五行相生关系
        $shengRelation = ['木' => '火', '火' => '土', '土' => '金', '金' => '水', '水' => '木'];
        // 五行相克关系
        $keRelation = ['木' => '土', '土' => '水', '水' => '火', '火' => '金', '金' => '木'];
        
        $dayWuXing = $this->ganWuXing[$dayGan];
        $targetWuXing = $this->ganWuXing[$targetGan];
        $dayYinYang = $ganYinYang[$dayGan];
        $targetYinYang = $ganYinYang[$targetGan];
        
        $isSameYinYang = ($dayYinYang === $targetYinYang);
        
        // 生我者
        if ($shengRelation[$targetWuXing] === $dayWuXing) {
            return $isSameYinYang ? '偏印' : '正印';
        }
        // 我生者
        if ($shengRelation[$dayWuXing] === $targetWuXing) {
            return $isSameYinYang ? '食神' : '伤官';
        }
        // 克我者
        if ($keRelation[$targetWuXing] === $dayWuXing) {
            return $isSameYinYang ? '七杀' : '正官';
        }
        // 我克者
        if ($keRelation[$dayWuXing] === $targetWuXing) {
            return $isSameYinYang ? '偏财' : '正财';
        }
        // 同我者
        return $isSameYinYang ? '比肩' : '劫财';
    }
    
    /**
     * 节气数据（近似值，用于月柱计算）
     * 节气月份对应：正月从立春开始
     */
    protected $jieQiMonthMap = [
        // 立春(2月4日左右)开始为正月
        [2, 4] => 1,   // 正月
        [3, 6] => 2,   // 二月
        [4, 5] => 3,   // 三月
        [5, 6] => 4,   // 四月
        [6, 6] => 5,   // 五月
        [7, 7] => 6,   // 六月
        [8, 8] => 7,   // 七月
        [9, 8] => 8,   // 八月
        [10, 8] => 9,  // 九月
        [11, 7] => 10, // 十月
        [12, 7] => 11, // 冬月
        [1, 6] => 12,  // 腊月
    ];
    
    /**
     * 年干对应月干起始（五虎遁月法）
     */
    protected $yearGanToMonthGanStart = [
        '甲' => '丙', '乙' => '戊', '丙' => '庚', '丁' => '壬', '戊' => '甲',
        '己' => '丙', '庚' => '戊', '辛' => '庚', '壬' => '壬', '癸' => '甲'
    ];
    
    /**
     * 日柱基准日（1900年1月31日为甲子的近似值）
     * 使用更精确的基准：1900年1月1日为甲戌日
     */
    protected $dayPillarBaseDate = '1900-01-01';
    protected $dayPillarBaseGanIndex = 0;  // 甲
    protected $dayPillarBaseZhiIndex = 10; // 戌
    
    /**
     * 计算农历年（考虑立春）
     * 立春前属于上一年
     */
    protected function getLunarYear(int $year, int $month, int $day): int
    {
        // 简化处理：2月4日前为上年，2月4日后为本年
        // 实际应根据具体节气日期计算
        if ($month < 2 || ($month == 2 && $day < 4)) {
            return $year - 1;
        }
        return $year;
    }
    
    /**
     * 计算农历月（考虑节气）
     * 返回农历月份和月支索引
     */
    protected function getLunarMonth(int $year, int $month, int $day): array
    {
        // 简化节气判断（实际应使用精确节气表）
        $jieQiDays = [4, 6, 5, 6, 6, 7, 8, 8, 8, 7, 7, 6]; // 各月节气约略日
        $lunarMonth = $month;
        
        // 判断是否已经过节气
        if ($day < $jieQiDays[$month - 1]) {
            $lunarMonth = $month - 1;
            if ($lunarMonth < 1) {
                $lunarMonth = 12;
            }
        }
        
        // 月支：正月为寅(索引2)
        $zhiIndex = ($lunarMonth + 1) % 12;
        
        return [
            'month' => $lunarMonth,
            'zhi_index' => $zhiIndex
        ];
    }
    
    /**
     * 计算日柱（使用蔡勒公式近似计算）
     */
    protected function calculateDayPillar(int $year, int $month, int $day): array
    {
        // 蔡勒公式计算星期几的变体，用于计算干支
        if ($month < 3) {
            $month += 12;
            $year--;
        }
        
        $c = (int)($year / 100);
        $y = $year % 100;
        
        // 计算从1900年1月1日（甲戌日）起的天数差
        $baseDate = new \DateTime('1900-01-01');
        $targetDate = new \DateTime("$year-" . ($month > 12 ? $month - 12 : $month) . "-$day");
        if ($month > 12) $targetDate->modify('+1 year');
        
        $diff = $baseDate->diff($targetDate);
        $days = $diff->days;
        
        // 1900年1月1日是甲戌日（甲=0, 戌=10）
        $ganIndex = ($days + 0) % 10;
        $zhiIndex = ($days + 10) % 12;
        
        return [
            'gan_index' => $ganIndex,
            'zhi_index' => $zhiIndex
        ];
    }
    
    /**
     * 计算八字
     */
    protected function calculateBazi(string $birthDate): array
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
        $dayPillar = $this->calculateDayPillar($year, $month, $day);
        $dayGanIndex = $dayPillar['gan_index'];
        $dayZhiIndex = $dayPillar['zhi_index'];
        $dayGan = $tianGan[$dayGanIndex];
        
        // ===== 时柱计算 =====
        // 时辰划分：23-1子, 1-3丑, 3-5寅, 5-7卯, 7-9辰, 9-11巳
        // 11-13午, 13-15未, 15-17申, 17-19酉, 19-21戌, 21-23亥
        if ($hour == 23) {
            // 23:00-23:59属于次日子时
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
            'calculation_note' => '已优化：考虑节气、日上起时法'
        ];
    }
    
    /**
     * 计算大运
     * 大运根据月柱推算，阳男阴女顺排，阴男阳女逆排
     */
    protected function calculateDaYun(array $bazi, string $gender, string $birthDate): array
    {
        $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        
        // 年干阴阳
        $ganYinYang = ['甲' => '阳', '乙' => '阴', '丙' => '阳', '丁' => '阴', '戊' => '阳',
                       '己' => '阴', '庚' => '阳', '辛' => '阴', '壬' => '阳', '癸' => '阴'];
        
        $yearGan = $bazi['year']['gan'];
        $isYang = ($ganYinYang[$yearGan] === '阳');
        $isMale = ($gender === 'male');
        
        // 确定大运顺逆：阳男阴女顺排，阴男阳女逆排
        $isForward = ($isYang && $isMale) || (!$isYang && !$isMale);
        
        // 月柱作为大运起点
        $monthGanIndex = $bazi['month']['gan_index'];
        $monthZhiIndex = $bazi['month']['zhi_index'];
        
        $daYun = [];
        
        // 计算起运年龄（简化计算，实际应根据节气）
        $birthTimestamp = strtotime($birthDate);
        $birthYear = (int)date('Y', $birthTimestamp);
        $startAge = 3; // 简化：一般3岁起运
        
        for ($i = 0; $i < 8; $i++) {
            if ($isForward) {
                $ganIndex = ($monthGanIndex + $i + 1) % 10;
                $zhiIndex = ($monthZhiIndex + $i + 1) % 12;
            } else {
                $ganIndex = ($monthGanIndex - $i - 1 + 10) % 10;
                $zhiIndex = ($monthZhiIndex - $i - 1 + 12) % 12;
            }
            
            $gan = $tianGan[$ganIndex];
            $zhi = $diZhi[$zhiIndex];
            
            // 计算十神
            $dayGan = $bazi['day']['gan'];
            $shiShen = $this->calculateShiShen($dayGan, $gan);
            
            $daYun[] = [
                'gan' => $gan,
                'zhi' => $zhi,
                'gan_wuxing' => $this->ganWuXing[$gan],
                'zhi_wuxing' => $this->zhiWuXing[$zhi],
                'shishen' => $shiShen,
                'age_start' => $startAge + $i * 10,
                'age_end' => $startAge + ($i + 1) * 10 - 1,
                'years' => ($birthYear + $startAge + $i * 10) . '-' . ($birthYear + $startAge + ($i + 1) * 10 - 1),
                'nayin' => $this->naYin[$gan . $zhi] ?? ''
            ];
        }
        
        return $daYun;
    }
    
    /**
     * 计算流年
     * 流年干支直接根据年份计算
     */
    protected function calculateLiuNian(int $startYear, int $count = 5): array
    {
        $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        
        $liuNian = [];
        $currentYear = (int)date('Y');
        
        for ($i = 0; $i < $count; $i++) {
            $year = $startYear + $i;
            $ganIndex = ($year - 4) % 10;
            $zhiIndex = ($year - 4) % 12;
            
            $gan = $tianGan[$ganIndex];
            $zhi = $diZhi[$zhiIndex];
            
            $liuNian[] = [
                'year' => $year,
                'gan' => $gan,
                'zhi' => $zhi,
                'gan_wuxing' => $this->ganWuXing[$gan],
                'zhi_wuxing' => $this->zhiWuXing[$zhi],
                'nayin' => $this->naYin[$gan . $zhi] ?? '',
                'is_current' => ($year === $currentYear)
            ];
        }
        
        return $liuNian;
    }
    
    /**
     * 分析大运吉凶
     */
    protected function analyzeDaYunLuck(array $daYun, array $bazi): array
    {
        $dayMasterWuxing = $bazi['day_master_wuxing'];
        $wuxingShengKe = [
            '金' => ['生' => '水', '克' => '木', '被生' => '土', '被克' => '火'],
            '木' => ['生' => '火', '克' => '土', '被生' => '水', '被克' => '金'],
            '水' => ['生' => '木', '克' => '火', '被生' => '金', '被克' => '土'],
            '火' => ['生' => '土', '克' => '金', '被生' => '木', '被克' => '水'],
            '土' => ['生' => '金', '克' => '水', '被生' => '火', '被克' => '木']
        ];
        
        foreach ($daYun as &$yun) {
            $yunWuxing = $yun['gan_wuxing'];
            $yunZhiWuxing = $yun['zhi_wuxing'];
            
            // 简单分析：大运五行与日主五行的关系
            if ($yunWuxing === $dayMasterWuxing) {
                $yun['luck'] = '平';
                $yun['luck_desc'] = '比劫运，兄弟朋友助力，但竞争也多';
            } elseif ($wuxingShengKe[$dayMasterWuxing]['被生'] === $yunWuxing) {
                $yun['luck'] = '吉';
                $yun['luck_desc'] = '印绶运，贵人相助，学业事业有进';
            } elseif ($wuxingShengKe[$dayMasterWuxing]['生'] === $yunWuxing) {
                $yun['luck'] = '平';
                $yun['luck_desc'] = '食伤运，创意发挥，但消耗精力';
            } elseif ($wuxingShengKe[$dayMasterWuxing]['被克'] === $yunWuxing) {
                $yun['luck'] = '凶';
                $yun['luck_desc'] = '官杀运，压力较大，需谨慎行事';
            } else {
                $yun['luck'] = '吉';
                $yun['luck_desc'] = '财运，财源广进，利于经商投资';
            }
        }
        
        return $daYun;
    }
    
    /**
     * 生成命理分析
     */
    protected function generateAnalysis(array $bazi, string $gender): string
    {
        $dayGan = $bazi['day']['gan'];
        $genderText = $gender === 'male' ? '男性' : '女性';
        
        $analysis = "您的日主为【{$dayGan}】，属{$genderText}。\n\n";
        $analysis .= "【年柱】{$bazi['year']['gan']}{$bazi['year']['zhi']}：代表祖上根基，早年运势。\n";
        $analysis .= "【月柱】{$bazi['month']['gan']}{$bazi['month']['zhi']}：代表父母兄弟，青年运势。\n";
        $analysis .= "【日柱】{$bazi['day']['gan']}{$bazi['day']['zhi']}：代表自身夫妻，中年运势。\n";
        $analysis .= "【时柱】{$bazi['hour']['gan']}{$bazi['hour']['zhi']}：代表子女晚景，晚年运势。\n\n";
        
        $analysis .= "您的八字五行分布较为均衡，性格稳重踏实。事业上宜循序渐进，财运方面有积累之象。\n";
        $analysis .= "感情方面，{$genderText}以{$dayGan}日主，配偶宫为{$bazi['day']['zhi']}，婚姻较为和谐。\n";
        $analysis .= "健康方面需注意脾胃保养，保持规律作息。";
        
        return $analysis;
    }
}
