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
        $fortune = DailyFortune::getToday();
        
        $yi = explode(',', $fortune->yi);
        $ji = explode(',', $fortune->ji);
        
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
        ]);
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
        return [$colors[$keys[0]], $colors[$keys[1]]];
    }
    
    /**
     * 生成幸运方位
     */
    protected function generateLuckyDirections(): array
    {
        $directions = ['东', '南', '西', '北', '东南', '东北', '西南', '西北'];
        $keys = array_rand($directions, 2);
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
