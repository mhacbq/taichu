<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Cache;
use think\facade\Db;

/**
 * 任务系统控制器
 */
class Task extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * 获取任务列表
     */
    public function getTasks()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        
        $today = date('Y-m-d');
        
        // 任务配置
        $tasks = [
            [
                'id' => 'daily_login',
                'type' => 'daily',
                'title' => '每日登录',
                'desc' => '每天登录APP即可获得积分',
                'icon' => 'login',
                'points' => 5,
                'max_times' => 1,
            ],
            [
                'id' => 'daily_paipan',
                'type' => 'daily',
                'title' => '每日排盘',
                'desc' => '完成一次八字排盘',
                'icon' => 'calendar',
                'points' => 10,
                'max_times' => 1,
            ],
            [
                'id' => 'daily_share',
                'type' => 'daily',
                'title' => '每日分享',
                'desc' => '分享APP给好友',
                'icon' => 'share',
                'points' => 2,
                'max_times' => 5,
            ],
            [
                'id' => 'complete_profile',
                'type' => 'once',
                'title' => '完善资料',
                'desc' => '完善个人资料信息',
                'icon' => 'user',
                'points' => 20,
                'max_times' => 1,
            ],
            [
                'id' => 'first_paipan',
                'type' => 'once',
                'title' => '首次排盘',
                'desc' => '完成第一次八字排盘',
                'icon' => 'star',
                'points' => 30,
                'max_times' => 1,
            ],
            [
                'id' => 'first_recharge',
                'type' => 'once',
                'title' => '首次充值',
                'desc' => '完成第一次积分充值',
                'icon' => 'wallet',
                'points' => 50,
                'max_times' => 1,
            ],
            [
                'id' => 'invite_friend',
                'type' => 'unlimited',
                'title' => '邀请好友',
                'desc' => '邀请好友注册并使用',
                'icon' => 'team',
                'points' => 50,
                'max_times' => -1,
            ],
            [
                'id' => 'daily_checkin',
                'type' => 'daily',
                'title' => '每日签到',
                'desc' => '连续签到可获得更多奖励',
                'icon' => 'check',
                'points' => 5,
                'max_times' => 1,
            ],
        ];
        
        // 获取用户任务进度
        foreach ($tasks as &$task) {
            $progress = $this->getTaskProgress($userId, $task['id'], $task['type']);
            $task['completed_times'] = $progress['completed'];
            $task['is_completed'] = $progress['completed'] >= $task['max_times'] && $task['max_times'] > 0;
            $task['can_claim'] = $this->canClaimTask($userId, $task);
            $task['last_claimed'] = $progress['last_claimed'];
        }
        
        // 获取用户连续签到天数
        $consecutiveDays = $this->getConsecutiveCheckinDays($userId);
        
        return $this->success([
            'tasks' => $tasks,
            'consecutive_days' => $consecutiveDays,
            'today' => $today,
        ]);
    }
    
    /**
     * 领取任务奖励
     */
    public function claimReward()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $taskId = $this->request->post('task_id');
        
        if (empty($taskId)) {
            return $this->error('任务ID不能为空');
        }
        
        // 任务配置
        $taskConfig = $this->getTaskConfig($taskId);
        if (!$taskConfig) {
            return $this->error('任务不存在');
        }
        
        // 检查任务是否可以领取
        if (!$this->canClaimTask($userId, $taskConfig)) {
            return $this->error('任务未完成或已领取');
        }
        
        // 检查任务进度
        $progress = $this->getTaskProgress($userId, $taskId, $taskConfig['type']);
        if ($progress['completed'] >= $taskConfig['max_times'] && $taskConfig['max_times'] > 0) {
            return $this->error('任务奖励已领取完毕');
        }
        
        // 开始事务
        Db::startTrans();
        try {
            // 增加用户积分
            $userModel = \app\model\User::find($userId);
            if (!$userModel) {
                Db::rollback();
                return $this->error('用户不存在');
            }
            
            $userModel->addPoints($taskConfig['points']);
            
            // 记录积分变动
            \app\model\PointsRecord::record(
                $userId,
                $taskConfig['title'],
                $taskConfig['points'],
                'task',
                0,
                "完成任务：{$taskConfig['title']}"
            );
            
            // 记录任务完成
            Db::name('tc_task_log')->insert([
                'user_id' => $userId,
                'task_id' => $taskId,
                'task_type' => $taskConfig['type'],
                'points' => $taskConfig['points'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            Db::commit();
            
            return $this->success([
                'points_earned' => $taskConfig['points'],
                'total_points' => $userModel->points,
            ], '领取成功');
            
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('领取失败：' . $e->getMessage());
        }
    }
    
    /**
     * 完成任务（由其他控制器调用）
     */
    public static function completeTask(int $userId, string $taskId): bool
    {
        $taskConfig = self::getTaskConfigStatic($taskId);
        if (!$taskConfig) {
            return false;
        }
        
        $today = date('Y-m-d');
        
        // 检查今日是否已完成
        if ($taskConfig['type'] === 'daily') {
            $todayCount = Db::name('tc_task_log')
                ->where('user_id', $userId)
                ->where('task_id', $taskId)
                ->whereDate('created_at', $today)
                ->count();
            
            if ($todayCount >= $taskConfig['max_times']) {
                return false;
            }
        }
        
        // 检查是否已完成
        if ($taskConfig['type'] === 'once') {
            $exists = Db::name('tc_task_log')
                ->where('user_id', $userId)
                ->where('task_id', $taskId)
                ->find();
            
            if ($exists) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 签到
     */
    public function checkin()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        
        $today = date('Y-m-d');
        
        // 检查今日是否已签到
        $todayCheckin = Db::name('tc_checkin_log')
            ->where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->find();
        
        if ($todayCheckin) {
            return $this->error('今日已签到，请明天再来');
        }
        
        // 获取连续签到天数
        $consecutiveDays = $this->getConsecutiveCheckinDays($userId);
        $newConsecutiveDays = $consecutiveDays + 1;
        
        // 计算签到奖励（连续签到奖励递增）
        $basePoints = 5;
        $bonusPoints = min($newConsecutiveDays * 2, 20); // 最多20额外积分
        $totalPoints = $basePoints + $bonusPoints;
        
        // 开始事务
        Db::startTrans();
        try {
            // 记录签到
            Db::name('tc_checkin_log')->insert([
                'user_id' => $userId,
                'consecutive_days' => $newConsecutiveDays,
                'points' => $totalPoints,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            // 增加用户积分
            $userModel = \app\model\User::find($userId);
            $userModel->addPoints($totalPoints);
            
            // 记录积分变动
            \app\model\PointsRecord::record(
                $userId,
                '每日签到',
                $totalPoints,
                'checkin',
                0,
                "连续签到{$newConsecutiveDays}天"
            );
            
            Db::commit();
            
            return $this->success([
                'points_earned' => $totalPoints,
                'base_points' => $basePoints,
                'bonus_points' => $bonusPoints,
                'consecutive_days' => $newConsecutiveDays,
                'total_points' => $userModel->points,
            ], '签到成功');
            
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('签到失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取签到记录
     */
    public function getCheckinHistory()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 30);
        
        $list = Db::name('tc_checkin_log')
            ->where('user_id', $userId)
            ->order('created_at', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
        
        $consecutiveDays = $this->getConsecutiveCheckinDays($userId);
        $todayCheckin = Db::name('tc_checkin_log')
            ->where('user_id', $userId)
            ->whereDate('created_at', date('Y-m-d'))
            ->find();
        
        return $this->success([
            'list' => $list,
            'consecutive_days' => $consecutiveDays,
            'today_checkin' => !empty($todayCheckin),
        ]);
    }
    
    /**
     * 获取任务配置
     */
    protected function getTaskConfig(string $taskId): ?array
    {
        $configs = [
            'daily_login' => [
                'id' => 'daily_login',
                'type' => 'daily',
                'title' => '每日登录',
                'points' => 5,
                'max_times' => 1,
            ],
            'daily_paipan' => [
                'id' => 'daily_paipan',
                'type' => 'daily',
                'title' => '每日排盘',
                'points' => 10,
                'max_times' => 1,
            ],
            'daily_share' => [
                'id' => 'daily_share',
                'type' => 'daily',
                'title' => '每日分享',
                'points' => 2,
                'max_times' => 5,
            ],
            'complete_profile' => [
                'id' => 'complete_profile',
                'type' => 'once',
                'title' => '完善资料',
                'points' => 20,
                'max_times' => 1,
            ],
            'first_paipan' => [
                'id' => 'first_paipan',
                'type' => 'once',
                'title' => '首次排盘',
                'points' => 30,
                'max_times' => 1,
            ],
            'first_recharge' => [
                'id' => 'first_recharge',
                'type' => 'once',
                'title' => '首次充值',
                'points' => 50,
                'max_times' => 1,
            ],
            'invite_friend' => [
                'id' => 'invite_friend',
                'type' => 'unlimited',
                'title' => '邀请好友',
                'points' => 50,
                'max_times' => -1,
            ],
            'daily_checkin' => [
                'id' => 'daily_checkin',
                'type' => 'daily',
                'title' => '每日签到',
                'points' => 5,
                'max_times' => 1,
            ],
        ];
        
        return $configs[$taskId] ?? null;
    }
    
    /**
     * 静态获取任务配置
     */
    protected static function getTaskConfigStatic(string $taskId): ?array
    {
        $configs = [
            'daily_login' => [
                'id' => 'daily_login',
                'type' => 'daily',
                'title' => '每日登录',
                'points' => 5,
                'max_times' => 1,
            ],
            'daily_paipan' => [
                'id' => 'daily_paipan',
                'type' => 'daily',
                'title' => '每日排盘',
                'points' => 10,
                'max_times' => 1,
            ],
            'daily_share' => [
                'id' => 'daily_share',
                'type' => 'daily',
                'title' => '每日分享',
                'points' => 2,
                'max_times' => 5,
            ],
            'complete_profile' => [
                'id' => 'complete_profile',
                'type' => 'once',
                'title' => '完善资料',
                'points' => 20,
                'max_times' => 1,
            ],
            'first_paipan' => [
                'id' => 'first_paipan',
                'type' => 'once',
                'title' => '首次排盘',
                'points' => 30,
                'max_times' => 1,
            ],
            'first_recharge' => [
                'id' => 'first_recharge',
                'type' => 'once',
                'title' => '首次充值',
                'points' => 50,
                'max_times' => 1,
            ],
            'invite_friend' => [
                'id' => 'invite_friend',
                'type' => 'unlimited',
                'title' => '邀请好友',
                'points' => 50,
                'max_times' => -1,
            ],
            'daily_checkin' => [
                'id' => 'daily_checkin',
                'type' => 'daily',
                'title' => '每日签到',
                'points' => 5,
                'max_times' => 1,
            ],
        ];
        
        return $configs[$taskId] ?? null;
    }
    
    /**
     * 获取任务进度
     */
    protected function getTaskProgress(int $userId, string $taskId, string $type): array
    {
        if ($type === 'daily') {
            $today = date('Y-m-d');
            $count = Db::name('tc_task_log')
                ->where('user_id', $userId)
                ->where('task_id', $taskId)
                ->whereDate('created_at', $today)
                ->count();
            
            $lastClaimed = Db::name('tc_task_log')
                ->where('user_id', $userId)
                ->where('task_id', $taskId)
                ->order('created_at', 'desc')
                ->value('created_at');
            
            return [
                'completed' => $count,
                'last_claimed' => $lastClaimed,
            ];
        } elseif ($type === 'once') {
            $exists = Db::name('tc_task_log')
                ->where('user_id', $userId)
                ->where('task_id', $taskId)
                ->find();
            
            return [
                'completed' => $exists ? 1 : 0,
                'last_claimed' => $exists ? $exists['created_at'] : null,
            ];
        } else {
            $count = Db::name('tc_task_log')
                ->where('user_id', $userId)
                ->where('task_id', $taskId)
                ->count();
            
            $lastClaimed = Db::name('tc_task_log')
                ->where('user_id', $userId)
                ->where('task_id', $taskId)
                ->order('created_at', 'desc')
                ->value('created_at');
            
            return [
                'completed' => $count,
                'last_claimed' => $lastClaimed,
            ];
        }
    }
    
    /**
     * 检查任务是否可以领取
     */
    protected function canClaimTask(int $userId, array $task): bool
    {
        // 这里简化处理，实际应该根据业务逻辑判断
        // 例如检查用户是否完成了任务要求的行为
        return false; // 默认不能领取，需要前端触发完成
    }
    
    /**
     * 获取连续签到天数
     */
    protected function getConsecutiveCheckinDays(int $userId): int
    {
        $lastCheckin = Db::name('tc_checkin_log')
            ->where('user_id', $userId)
            ->order('created_at', 'desc')
            ->find();
        
        if (!$lastCheckin) {
            return 0;
        }
        
        $lastDate = date('Y-m-d', strtotime($lastCheckin['created_at']));
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        if ($lastDate === $today) {
            return $lastCheckin['consecutive_days'];
        } elseif ($lastDate === $yesterday) {
            return $lastCheckin['consecutive_days'];
        } else {
            return 0;
        }
    }
}
