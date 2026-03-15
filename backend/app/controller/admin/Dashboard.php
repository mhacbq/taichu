<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Dashboard extends BaseController
{
    /**
     * 获取仪表盘统计数据
     */
    public function statistics()
    {
        // 今日日期
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // 总用户数
        $totalUsers = Db::name('user')->where('status', 1)->count();
        
        // 今日新增用户
        $todayNewUsers = Db::name('user')
            ->where('created_at', '>=', $today)
            ->count();
        
        // 昨日新增用户
        $yesterdayNewUsers = Db::name('user')
            ->where('created_at', '>=', $yesterday)
            ->where('created_at', '<', $today)
            ->count();
        
        // 今日八字排盘次数
        $todayBazi = Db::name('bazi_record')
            ->where('created_at', '>=', $today)
            ->count();
        
        // 昨日八字排盘次数
        $yesterdayBazi = Db::name('bazi_record')
            ->where('created_at', '>=', $yesterday)
            ->where('created_at', '<', $today)
            ->count();
        
        // 塔罗占卜次数
        $todayTarot = Db::name('points_history')
            ->where('action', 'like', '%塔罗%')
            ->where('created_at', '>=', $today)
            ->count();
        
        $yesterdayTarot = Db::name('points_history')
            ->where('action', 'like', '%塔罗%')
            ->where('created_at', '>=', $yesterday)
            ->where('created_at', '<', $today)
            ->count();
        
        // 计算趋势
        $userTrend = $yesterdayNewUsers > 0 
            ? round(($todayNewUsers - $yesterdayNewUsers) / $yesterdayNewUsers * 100, 1)
            : 0;
        
        $baziTrend = $yesterdayBazi > 0
            ? round(($todayBazi - $yesterdayBazi) / $yesterdayBazi * 100, 1)
            : 0;
        
        $tarotTrend = $yesterdayTarot > 0
            ? round(($todayTarot - $yesterdayTarot) / $yesterdayTarot * 100, 1)
            : 0;
        
        return $this->success([
            'totalUsers' => [
                'value' => $totalUsers,
                'trend' => $userTrend,
            ],
            'todayNewUsers' => [
                'value' => $todayNewUsers,
                'trend' => $userTrend,
            ],
            'todayBazi' => [
                'value' => $todayBazi,
                'trend' => $baziTrend,
            ],
            'todayTarot' => [
                'value' => $todayTarot,
                'trend' => $tarotTrend,
            ],
        ]);
    }
    
    /**
     * 获取趋势数据
     */
    public function trend()
    {
        $days = intval($this->request->get('days', 7));
        $days = min($days, 30);
        
        $dates = [];
        $newUsers = [];
        $activeUsers = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dates[] = date('m-d', strtotime($date));
            
            // 新增用户
            $newUsers[] = Db::name('user')
                ->where('created_at', '>=', $date)
                ->where('created_at', '<', date('Y-m-d', strtotime($date . ' +1 day')))
                ->count();
            
            // 活跃用户（有操作记录）
            $activeUsers[] = Db::name('bazi_record')
                ->where('created_at', '>=', $date)
                ->where('created_at', '<', date('Y-m-d', strtotime($date . ' +1 day')))
                ->count();
        }
        
        return $this->success([
            'dates' => $dates,
            'newUsers' => $newUsers,
            'activeUsers' => $activeUsers,
        ]);
    }
    
    /**
     * 获取实时数据
     */
    public function realtime()
    {
        // 获取最近的排盘记录
        $recentBazi = Db::name('bazi_record')
            ->alias('br')
            ->join('user u', 'br.user_id = u.id', 'LEFT')
            ->field('br.created_at, u.phone')
            ->order('br.created_at', 'DESC')
            ->limit(10)
            ->select();
        
        $data = [];
        foreach ($recentBazi as $item) {
            $phone = $item['phone'] ? substr_replace($item['phone'], '****', 3, 4) : '游客';
            $data[] = [
                'time' => date('H:i:s', strtotime($item['created_at'])),
                'action' => '八字排盘',
                'user' => '用户' . $phone,
            ];
        }
        
        return $this->success($data);
    }
    
    /**
     * 获取功能使用分布
     */
    public function chart($type)
    {
        if ($type === 'feature') {
            // 功能使用分布
            $baziCount = Db::name('bazi_record')->count();
            $tarotCount = Db::name('points_history')
                ->where('action', 'like', '%塔罗%')
                ->count();
            $dailyCount = Db::name('daily_fortune')->count();
            $exchangeCount = Db::name('points_exchange')->count();
            
            return $this->success([
                ['name' => '八字排盘', 'value' => $baziCount],
                ['name' => '塔罗占卜', 'value' => $tarotCount],
                ['name' => '每日运势', 'value' => $dailyCount],
                ['name' => '积分兑换', 'value' => $exchangeCount],
            ]);
        }
        
        return $this->error('未知图表类型');
    }
    
    /**
     * 获取待处理反馈
     */
    public function pendingFeedback()
    {
        $feedback = Db::name('feedback')
            ->where('status', 0)
            ->order('created_at', 'DESC')
            ->limit(5)
            ->select();
        
        $data = [];
        foreach ($feedback as $item) {
            $data[] = [
                'id' => $item['id'],
                'content' => $item['content'],
                'type' => $this->getFeedbackType($item['type']),
                'time' => $item['created_at'],
            ];
        }
        
        return $this->success($data);
    }
    
    private function getFeedbackType($type)
    {
        $types = [
            'suggestion' => '建议',
            'bug' => '问题',
            'complaint' => '投诉',
            'other' => '其他',
        ];
        return $types[$type] ?? '其他';
    }
}