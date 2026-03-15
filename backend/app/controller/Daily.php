<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\DailyFortune;
use app\model\PointsRecord;
use think\facade\Db;

class Daily extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
    // 签到奖励积分
    const CHECKIN_POINTS = 5;
    // 连续签到额外奖励
    const CONSECUTIVE_BONUS = [3 => 3, 7 => 7, 15 => 15, 30 => 30];
    
    /**
     * 获取今日运势
     */
    public function fortune()
    {
        $user = $this->request->user;
        $fortune = DailyFortune::getToday();
        
        $yi = explode(',', $fortune->yi);
        $ji = explode(',', $fortune->ji);
        
        // 获取用户八字信息，生成个性化运势
        $personalized = null;
        if ($user) {
            $personalized = $this->generatePersonalizedFortune($user['sub'], $fortune);
        }
        
        return $this->success([
            'date' => $fortune->date,
            'lunarDate' => $fortune->lunar_date,
            'overallScore' => $fortune->overall_score,
            'summary' => $fortune->summary,
            'aspects' => [
                ['name' => '事业运', 'icon' => '💼', 'score' => $fortune->career_score, 'description' => $fortune->career_desc],
                ['name' => '财运', 'icon' => '💰', 'score' => $fortune->wealth_score, 'description' => $fortune->wealth_desc],
                ['name' => '感情运', 'icon' => '💕', 'score' => $fortune->love_score, 'description' => $fortune->love_desc],
                ['name' => '健康运', 'icon' => '🏃', 'score' => $fortune->health_score, 'description' => $fortune->health_desc],
            ],
            'yi' => $yi,
            'ji' => $ji,
            'details' => [
                'career' => $fortune->career_desc,
                'wealth' => $fortune->wealth_desc,
                'love' => $fortune->love_desc,
                'health' => $fortune->health_desc,
            ],
            'personalized' => $personalized,
        ]);
    }
    
    /**
     * 生成个性化运势
     */
    protected function generatePersonalizedFortune(int $userId, $fortune): ?array
    {
        // 获取用户最近的八字排盘记录
        $baziRecord = Db::name('tc_bazi_record')
            ->where('user_id', $userId)
            ->order('created_at', 'desc')
            ->find();
        
        if (!$baziRecord) {
            return null;
        }
        
        // 计算今日干支（使用公历转干支算法）
        $today = date('Y-m-d');
        $todayGanZhi = $this->getDayGanZhi($today);
        $todayGan = mb_substr($todayGanZhi, 0, 1);
        $todayZhi = mb_substr($todayGanZhi, 1, 1);
        
        // 用户日主
        $dayMaster = $baziRecord['day_gan'];
        
        // 五行属性
        $ganWuXing = [
            '甲' => '木', '乙' => '木',
            '丙' => '火', '丁' => '火',
            '戊' => '土', '己' => '土',
            '庚' => '金', '辛' => '金',
            '壬' => '水', '癸' => '水'
        ];
        $zhiWuXing = [
            '子' => '水', '丑' => '土', '寅' => '木', '卯' => '木',
            '辰' => '土', '巳' => '火', '午' => '火', '未' => '土',
            '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水'
        ];
        
        $dayMasterWuxing = $ganWuXing[$dayMaster];
        $todayGanWuxing = $ganWuXing[$todayGan];
        $todayZhiWuxing = $zhiWuXing[$todayZhi];
        
        // 五行关系
        $wuxingShengKe = [
            '金' => ['生' => '水', '克' => '木', '被生' => '土', '被克' => '火'],
            '木' => ['生' => '火', '克' => '土', '被生' => '水', '被克' => '金'],
            '水' => ['生' => '木', '克' => '火', '被生' => '金', '被克' => '土'],
            '火' => ['生' => '土', '克' => '金', '被生' => '木', '被克' => '水'],
            '土' => ['生' => '金', '克' => '水', '被生' => '火', '被克' => '木']
        ];
        
        // 计算今日与日主的关系
        $relation = '';
        $luckLevel = '平';
        $advice = '';
        
        if ($todayGanWuxing === $dayMasterWuxing) {
            $relation = '比劫';
            $luckLevel = '平';
            $advice = '今日比肩劫财，适合与朋友合作，但需注意财物安全，避免借贷纠纷。';
        } elseif ($wuxingShengKe[$dayMasterWuxing]['被生'] === $todayGanWuxing) {
            $relation = '印绶';
            $luckLevel = '吉';
            $advice = '今日印绶当令，贵人运佳，适合学习进修、寻求长辈指导，学业事业有进。';
        } elseif ($wuxingShengKe[$dayMasterWuxing]['生'] === $todayGanWuxing) {
            $relation = '食伤';
            $luckLevel = '平';
            $advice = '今日食伤生财，创意灵感丰富，适合艺术创作、表达自我，但要注意言行分寸。';
        } elseif ($wuxingShengKe[$dayMasterWuxing]['被克'] === $todayGanWuxing) {
            $relation = '官杀';
            $luckLevel = '凶';
            $advice = '今日官杀攻身，压力较大，需谨慎行事，避免冲动决策，注意身体健康。';
        } else {
            $relation = '财星';
            $luckLevel = '吉';
            $advice = '今日财星高照，财运亨通，适合理财投资、商务谈判，有意外收获之机。';
        }
        
        // 根据今日运势基础分和个人关系调整
        $baseScore = $fortune->overall_score;
        $adjustedScore = $baseScore;
        if ($luckLevel === '吉') {
            $adjustedScore = min(100, $baseScore + 5);
        } elseif ($luckLevel === '凶') {
            $adjustedScore = max(40, $baseScore - 5);
        }
        
        return [
            'hasBazi' => true,
            'dayMaster' => $dayMaster,
            'dayMasterWuxing' => $dayMasterWuxing,
            'todayGanZhi' => $todayGan . $todayZhi,
            'todayWuxing' => $todayGanWuxing . $todayZhiWuxing,
            'relation' => $relation,
            'luckLevel' => $luckLevel,
            'advice' => $advice,
            'personalScore' => $adjustedScore,
            'luckyColors' => $this->getLuckyColorsByWuxing($dayMasterWuxing),
            'luckyDirections' => $this->getLuckyDirectionsByWuxing($dayMasterWuxing),
        ];
    }
    
    /**
     * 根据五行获取幸运色
     */
    protected function getLuckyColorsByWuxing(string $wuxing): array
    {
        $colors = [
            '金' => ['白色', '金色', '银色'],
            '木' => ['绿色', '青色', '翠色'],
            '水' => ['黑色', '蓝色', '灰色'],
            '火' => ['红色', '紫色', '橙色'],
            '土' => ['黄色', '棕色', '咖啡色']
        ];
        return $colors[$wuxing] ?? ['黄色'];
    }
    
    /**
     * 根据五行获取幸运方位
     */
    protected function getLuckyDirectionsByWuxing(string $wuxing): array
    {
        $directions = [
            '金' => ['西方', '西北方'],
            '木' => ['东方', '东南方'],
            '水' => ['北方'],
            '火' => ['南方'],
            '土' => ['中央', '东北方', '西南方']
        ];
        return $directions[$wuxing] ?? ['中央'];
    }
    
    /**
     * 获取指定日期的日干支
     * 使用公历转干支算法
     */
    protected function getDayGanZhi(string $date): string
    {
        $timestamp = strtotime($date);
        $year = (int)date('Y', $timestamp);
        $month = (int)date('m', $timestamp);
        $day = (int)date('d', $timestamp);
        
        // 天干地支数组
        $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        
        // 计算儒略日数（简化算法）
        $a = floor((14 - $month) / 12);
        $y = $year + 4800 - $a;
        $m = $month + 12 * $a - 3;
        
        $julianDay = $day + floor((153 * $m + 2) / 5) + 365 * $y + floor($y / 4) - floor($y / 100) + floor($y / 400) - 32045;
        
        // 日干支计算（以1900-01-31为基准，当天是甲子日）
        $baseJulianDay = 2415021; // 1900-01-31的儒略日
        $offset = $julianDay - $baseJulianDay;
        
        // 计算日干支索引
        $ganIndex = ($offset % 10 + 10) % 10;
        $zhiIndex = ($offset % 12 + 12) % 12;
        
        return $tianGan[$ganIndex] . $diZhi[$zhiIndex];
    }
    
    /**
     * 获取今日宜忌
     */
    public function luck()
    {
        $fortune = DailyFortune::getToday();
        
        return $this->success([
            'date' => $fortune->date,
            'lunarDate' => $fortune->lunar_date,
            'yi' => explode(',', $fortune->yi),
            'ji' => explode(',', $fortune->ji),
            'luckyNumbers' => $this->generateLuckyNumbers(),
            'luckyColors' => $this->generateLuckyColors(),
            'luckyDirections' => $this->generateLuckyDirections(),
        ]);
    }
    
    /**
     * 生成幸运数字
     */
    protected function generateLuckyNumbers(): array
    {
        $numbers = [];
        while (count($numbers) < 3) {
            $num = mt_rand(1, 99);
            if (!in_array($num, $numbers)) {
                $numbers[] = $num;
            }
        }
        return $numbers;
    }
    
    /**
     * 生成幸运颜色
     */
    protected function generateLuckyColors(): array
    {
        $colors = ['红色', '黄色', '蓝色', '绿色', '紫色', '橙色', '白色', '黑色'];
        $keys = array_rand($colors, 2);
        // 确保$keys是数组（当选择2个元素时array_rand返回数组，选择1个时返回单个键）
        if (!is_array($keys)) {
            $keys = [$keys, ($keys + 1) % count($colors)];
        }
        return [$colors[$keys[0]], $colors[$keys[1]]];
    }
    
    /**
     * 生成幸运方位
     */
    protected function generateLuckyDirections(): array
    {
        $directions = ['东', '南', '西', '北', '东南', '东北', '西南', '西北'];
        $keys = array_rand($directions, 2);
        // 确保$keys是数组
        if (!is_array($keys)) {
            $keys = [$keys, ($keys + 1) % count($directions)];
        }
        return [$directions[$keys[0]], $directions[$keys[1]]];
    }
    
    /**
     * 每日签到
     */
    public function checkin()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $today = date('Y-m-d');
        
        // 检查今天是否已签到
        $todayCheckin = Db::name('checkin_record')
            ->where('user_id', $userId)
            ->where('date', $today)
            ->find();
        
        if ($todayCheckin) {
            return $this->error('您今天已经签到过了，明天再来吧！', 400);
        }
        
        // 获取昨天是否签到（用于计算连续天数）
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yesterdayCheckin = Db::name('checkin_record')
            ->where('user_id', $userId)
            ->where('date', $yesterday)
            ->find();
        
        $consecutiveDays = $yesterdayCheckin ? ($yesterdayCheckin['consecutive_days'] + 1) : 1;
        
        // 计算奖励
        $basePoints = self::CHECKIN_POINTS;
        $bonusPoints = 0;
        
        foreach (self::CONSECUTIVE_BONUS as $days => $bonus) {
            if ($consecutiveDays >= $days) {
                $bonusPoints = $bonus;
            }
        }
        
        $totalPoints = $basePoints + $bonusPoints;
        
        Db::startTrans();
        try {
            // 记录签到
            Db::name('checkin_record')->insert([
                'user_id' => $userId,
                'date' => $today,
                'consecutive_days' => $consecutiveDays,
                'points' => $totalPoints,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            // 增加积分
            $userModel = \app\model\User::find($userId);
            $userModel->addPoints($totalPoints);
            
            // 记录积分变动
            $remark = "连续签到{$consecutiveDays}天";
            if ($bonusPoints > 0) {
                $remark .= "，额外奖励{$bonusPoints}积分";
            }
            
            PointsRecord::record(
                $userId,
                '每日签到',
                $totalPoints,
                'checkin',
                0,
                $remark
            );
            
            Db::commit();
            
            return $this->success([
                'points' => $totalPoints,
                'consecutiveDays' => $consecutiveDays,
                'bonusPoints' => $bonusPoints,
                'totalPoints' => $userModel->points,
                'message' => $bonusPoints > 0 
                    ? "签到成功！获得{$basePoints}积分，连续{$consecutiveDays}天额外奖励{$bonusPoints}积分！"
                    : "签到成功！获得{$basePoints}积分！",
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('签到失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取签到状态
     */
    public function checkinStatus()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $today = date('Y-m-d');
        
        // 检查今天是否已签到
        $todayCheckin = Db::name('checkin_record')
            ->where('user_id', $userId)
            ->where('date', $today)
            ->find();
        
        // 获取连续签到天数
        $consecutiveDays = $todayCheckin ? $todayCheckin['consecutive_days'] : 0;
        
        // 如果今天没签到，检查昨天是否签到
        if (!$todayCheckin) {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $yesterdayCheckin = Db::name('checkin_record')
                ->where('user_id', $userId)
                ->where('date', $yesterday)
                ->find();
            $consecutiveDays = $yesterdayCheckin ? $yesterdayCheckin['consecutive_days'] : 0;
        }
        
        // 获取本月签到记录
        $monthStart = date('Y-m-01');
        $monthCheckins = Db::name('checkin_record')
            ->where('user_id', $userId)
            ->where('date', '>=', $monthStart)
            ->column('date');
        
        return $this->success([
            'checkedIn' => !!$todayCheckin,
            'consecutiveDays' => $consecutiveDays,
            'todayPoints' => self::CHECKIN_POINTS,
            'monthCheckins' => $monthCheckins,
            'consecutiveBonus' => self::CONSECUTIVE_BONUS,
        ]);
    }
}
