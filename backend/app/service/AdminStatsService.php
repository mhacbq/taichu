<?php

namespace app\service;

use think\facade\Db;
use think\facade\Log;


/**
 * 后台统计服务类
 * 提供Dashboard数据、用户统计、订单统计等功能
 */
class AdminStatsService
{
    /**
     * 获取Dashboard统计数据
     * 
     * @return array
     */
    public static function getDashboardStats(): array
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $thisMonth = date('Y-m');
        
        return [
            'overview' => self::getOverviewStats($today, $yesterday),
            'user_stats' => self::getUserStats($today, $thisMonth),
            'order_stats' => self::getOrderStats($today, $thisMonth),
            'divination_stats' => self::getDivinationStats($today),
            'points_stats' => self::getPointsStats($today),
            'trend' => self::getTrendStats(),
        ];
    }

    /**
     * 获取概览统计
     */
    private static function getOverviewStats(string $today, string $yesterday): array
    {
        // 今日数据
        $todayStats = Db::table('site_daily_stats')
            ->where('stat_date', $today)
            ->find();
        
        // 昨日数据
        $yesterdayStats = Db::table('site_daily_stats')
            ->where('stat_date', $yesterday)
            ->find();
        
        // 累计数据
        $totalUsers = Db::table('tc_user')->where('status', 1)->count();
        $totalPoints = Db::table('tc_user')->sum('points');
        
        return [
            'today_revenue' => [
                'value' => $todayStats['paid_amount'] ?? 0,
                'yesterday' => $yesterdayStats['paid_amount'] ?? 0,
                'change' => self::calculateChange(
                    $todayStats['paid_amount'] ?? 0,
                    $yesterdayStats['paid_amount'] ?? 0
                ),
            ],
            'today_orders' => [
                'value' => $todayStats['paid_count'] ?? 0,
                'yesterday' => $yesterdayStats['paid_count'] ?? 0,
                'change' => self::calculateChange(
                    $todayStats['paid_count'] ?? 0,
                    $yesterdayStats['paid_count'] ?? 0
                ),
            ],
            'new_users' => [
                'value' => $todayStats['new_users'] ?? 0,
                'yesterday' => $yesterdayStats['new_users'] ?? 0,
                'change' => self::calculateChange(
                    $todayStats['new_users'] ?? 0,
                    $yesterdayStats['new_users'] ?? 0
                ),
            ],
            'active_users' => [
                'value' => $todayStats['active_users'] ?? 0,
                'yesterday' => $yesterdayStats['active_users'] ?? 0,
                'change' => self::calculateChange(
                    $todayStats['active_users'] ?? 0,
                    $yesterdayStats['active_users'] ?? 0
                ),
            ],
            'total_users' => $totalUsers,
            'total_points_balance' => $totalPoints,
        ];
    }

    /**
     * 获取用户统计
     */
    private static function getUserStats(string $today, string $thisMonth): array
    {
        // 本月新增
        $monthNewUsers = Db::table('tc_user')
            ->whereLike('created_at', $thisMonth . '%')
            ->count();
        
        // 用户来源统计（如果有来源字段）
        $userSources = Db::table('tc_user')
            ->field('source, COUNT(*) as count')
            ->group('source')
            ->select();
        
        // VIP用户统计
        $vipUsers = Db::table('tc_user_vip')
            ->where('status', 1)
            ->where('end_time', '>', date('Y-m-d H:i:s'))
            ->count();
        
        return [
            'total' => Db::table('tc_user')->count(),
            'today_new' => Db::table('site_daily_stats')
                ->where('stat_date', $today)
                ->value('new_users') ?? 0,
            'month_new' => $monthNewUsers,
            'vip_users' => $vipUsers,
            'sources' => $userSources,
        ];
    }

    /**
     * 获取订单统计
     */
    private static function getOrderStats(string $today, string $thisMonth): array
    {
        // 今日订单
        $todayStats = Db::table('site_daily_stats')
            ->where('stat_date', $today)
            ->find();
        
        // 本月订单
        $monthStats = Db::table('site_daily_stats')
            ->whereLike('stat_date', $thisMonth . '%')
            ->field('SUM(order_count) as total_orders, SUM(paid_amount) as total_amount')
            ->find();
        
        // 订单状态分布
        $statusDistribution = Db::table('tc_vip_order')
            ->field('status, COUNT(*) as count')
            ->group('status')
            ->select();
        
        return [
            'today' => [
                'total' => $todayStats['order_count'] ?? 0,
                'paid' => $todayStats['paid_count'] ?? 0,
                'amount' => $todayStats['paid_amount'] ?? 0,
            ],
            'month' => [
                'total' => $monthStats['total_orders'] ?? 0,
                'amount' => $monthStats['total_amount'] ?? 0,
            ],
            'status_distribution' => $statusDistribution,
        ];
    }

    /**
     * 获取占卜统计
     */
    private static function getDivinationStats(string $today): array
    {
        $stats = Db::table('site_daily_stats')
            ->where('stat_date', $today)
            ->find();
        
        // 各类型占卜总数
        $totalStats = Db::table('site_daily_stats')
            ->field([
                'SUM(bazi_count) as total_bazi',
                'SUM(tarot_count) as total_tarot',
                'SUM(liuyao_count) as total_liuyao',
                'SUM(hehun_count) as total_hehun',
            ])
            ->find();
        
        return [
            'today' => [
                'bazi' => $stats['bazi_count'] ?? 0,
                'tarot' => $stats['tarot_count'] ?? 0,
                'liuyao' => $stats['liuyao_count'] ?? 0,
                'hehun' => $stats['hehun_count'] ?? 0,
                'daily_fortune' => $stats['daily_fortune_count'] ?? 0,
            ],
            'total' => [
                'bazi' => $totalStats['total_bazi'] ?? 0,
                'tarot' => $totalStats['total_tarot'] ?? 0,
                'liuyao' => $totalStats['total_liuyao'] ?? 0,
                'hehun' => $totalStats['total_hehun'] ?? 0,
            ],
        ];
    }

    /**
     * 获取积分统计
     */
    private static function getPointsStats(string $today): array
    {
        $stats = Db::table('site_daily_stats')
            ->where('stat_date', $today)
            ->find();
        
        // 积分消耗排行（按用户）
        $topConsumers = Db::table('tc_points_record')
            ->where('type', 'consume')
            ->whereLike('created_at', $today . '%')
            ->field('user_id, SUM(points) as total_points')
            ->group('user_id')
            ->order('total_points', 'DESC')
            ->limit(10)
            ->select();
        
        return [
            'today_given' => $stats['points_given'] ?? 0,
            'today_consumed' => $stats['points_consumed'] ?? 0,
            'balance' => $stats['points_balance'] ?? 0,
            'top_consumers' => $topConsumers,
        ];
    }

    /**
     * 获取趋势统计（最近30天）
     */
    private static function getTrendStats(): array
    {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-29 days'));
        
        $trend = Db::table('site_daily_stats')
            ->whereBetween('stat_date', [$startDate, $endDate])
            ->order('stat_date', 'ASC')
            ->column('*', 'stat_date');
        
        $dates = [];
        $revenue = [];
        $orders = [];
        $newUsers = [];
        $divination = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dates[] = date('m-d', strtotime($date));
            
            $dayStats = $trend[$date] ?? [];
            
            $revenue[] = $dayStats['paid_amount'] ?? 0;
            $orders[] = $dayStats['paid_count'] ?? 0;
            $newUsers[] = $dayStats['new_users'] ?? 0;
            $divination[] = ($dayStats['bazi_count'] ?? 0) 
                + ($dayStats['tarot_count'] ?? 0) 
                + ($dayStats['liuyao_count'] ?? 0);
        }
        
        return [
            'dates' => $dates,
            'revenue' => $revenue,
            'orders' => $orders,
            'new_users' => $newUsers,
            'divination' => $divination,
        ];
    }

    /**
     * 计算增长率
     */
    private static function calculateChange(float $today, float $yesterday): array
    {
        if ($yesterday == 0) {
            return [
                'value' => $today > 0 ? 100 : 0,
                'type' => $today > 0 ? 'increase' : 'flat',
            ];
        }
        
        $change = (($today - $yesterday) / $yesterday) * 100;
        
        return [
            'value' => round(abs($change), 2),
            'type' => $change > 0 ? 'increase' : ($change < 0 ? 'decrease' : 'flat'),
        ];
    }

    /**
     * 更新每日统计（定时任务调用）
     */
    public static function updateDailyStats(string $date = null): bool
    {
        $date = $date ?? date('Y-m-d');
        
        // 用户统计
        $newUsers = Db::table('tc_user')
            ->whereLike('created_at', $date . '%')
            ->count();
        
        $activeUsers = Db::table('tc_user')
            ->whereLike('last_login_at', $date . '%')
            ->count();
        
        // 积分统计
        $pointsGiven = Db::table('tc_points_record')
            ->where('type', 'income')
            ->whereLike('created_at', $date . '%')
            ->sum('points') ?? 0;
        
        $pointsConsumed = Db::table('tc_points_record')
            ->where('type', 'consume')
            ->whereLike('created_at', $date . '%')
            ->sum('points') ?? 0;
        
        // 占卜统计
        $baziCount = Db::table('tc_bazi_record')
            ->whereLike('created_at', $date . '%')
            ->count();
        
        $tarotCount = Db::table('tc_tarot_record')
            ->whereLike('created_at', $date . '%')
            ->count();
        
        $liuyaoCount = Db::table('tc_liuyao_record')
            ->whereLike('created_at', $date . '%')
            ->count();
        
        $hehunCount = Db::table('hehun_records')
            ->whereLike('created_at', $date . '%')
            ->count();
        
        // 订单统计
        $orderStats = Db::table('tc_vip_order')
            ->whereLike('created_at', $date . '%')
            ->field([
                'COUNT(*) as order_count',
                'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as paid_count',
                'SUM(CASE WHEN status = 1 THEN pay_amount ELSE 0 END) as paid_amount',
            ])
            ->find();
        
        // 保存或更新统计
        $data = [
            'stat_date' => $date,
            'new_users' => $newUsers,
            'active_users' => $activeUsers,
            'points_given' => $pointsGiven,
            'points_consumed' => abs($pointsConsumed),
            'bazi_count' => $baziCount,
            'tarot_count' => $tarotCount,
            'liuyao_count' => $liuyaoCount,
            'hehun_count' => $hehunCount,
            'order_count' => $orderStats['order_count'] ?? 0,
            'paid_count' => $orderStats['paid_count'] ?? 0,
            'paid_amount' => $orderStats['paid_amount'] ?? 0,
        ];
        
        $exists = Db::table('site_daily_stats')
            ->where('stat_date', $date)
            ->find();
        
        if ($exists) {
            Db::table('site_daily_stats')
                ->where('stat_date', $date)
                ->update($data);
        } else {
            Db::table('site_daily_stats')->insert($data);
        }
        
        return true;
    }

    /**
     * 获取用户列表（带筛选）
     */
    public static function getUserList(array $params = []): array
    {
        $query = Db::table('tc_user')->alias('u')
            ->leftJoin('tc_user_vip uv', 'u.id = uv.user_id AND uv.status = 1 AND uv.end_time > NOW()')
            ->field([
                'u.id',
                'u.nickname',
                'u.phone',
                'u.points',
                'u.created_at',
                'u.last_login_at',
                'u.status',
                'CASE WHEN uv.id IS NOT NULL THEN 1 ELSE 0 END as is_vip',
                'uv.end_time as vip_end_time',
            ]);
        
        // 筛选条件
        if (!empty($params['keyword'])) {
            $query->where(function ($q) use ($params) {
                $q->whereLike('u.nickname', '%' . $params['keyword'] . '%')
                  ->whereOrLike('u.phone', '%' . $params['keyword'] . '%');
            });
        }
        
        if (isset($params['status'])) {
            $query->where('u.status', $params['status']);
        }
        
        if (!empty($params['is_vip'])) {
            $query->whereNotNull('uv.id');
        }
        
        // 排序
        $orderBy = $params['order_by'] ?? 'id';
        $orderSort = $params['order_sort'] ?? 'DESC';
        $query->order($orderBy, $orderSort);
        
        // 分页
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        
        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();
        
        return [
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'list' => $list,
        ];
    }

    /**
     * 调整用户积分
     */
    public static function adjustUserPoints(int $userId, int $points, string $reason, int $adminId): bool
    {
        $user = Db::table('tc_user')->where('id', $userId)->find();
        if (!$user) {
            throw new \Exception('用户不存在');
        }
        
        $newPoints = $user['points'] + $points;
        if ($newPoints < 0) {
            throw new \Exception('积分不足');
        }
        
        Db::startTrans();
        try {
            // 更新用户积分
            Db::table('tc_user')
                ->where('id', $userId)
                ->update(['points' => $newPoints]);
            
            // 记录积分变动
            Db::table('tc_points_record')->insert([
                'user_id' => $userId,
                'action' => $points > 0 ? '管理员增加' : '管理员扣除',
                'points' => abs($points),
                'type' => $points > 0 ? 'income' : 'consume',
                'description' => $reason,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            // 记录管理员操作日志
            Db::table('tc_admin_log')->insert([
                'admin_id' => $adminId,
                'action' => 'adjust_points',
                'target_type' => 'user',
                'target_id' => $userId,
                'content' => "调整用户积分: {$points}, 原因: {$reason}",
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 获取订单列表
     */
    public static function getOrderList(array $params = []): array
    {
        $query = Db::table('tc_vip_order')->alias('o')
            ->leftJoin('tc_user u', 'o.user_id = u.id')
            ->field([
                'o.*',
                'u.nickname',
                'u.phone',
            ]);
        
        // 筛选条件
        if (!empty($params['order_no'])) {
            $query->whereLike('o.order_no', '%' . $params['order_no'] . '%');
        }
        
        if (!empty($params['user_id'])) {
            $query->where('o.user_id', $params['user_id']);
        }
        
        if (isset($params['status'])) {
            $query->where('o.status', $params['status']);
        }
        
        if (!empty($params['date_start'])) {
            $query->where('o.created_at', '>=', $params['date_start']);
        }
        
        if (!empty($params['date_end'])) {
            $query->where('o.created_at', '<=', $params['date_end']);
        }
        
        // 排序
        $query->order('o.created_at', 'DESC');
        
        // 分页
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        
        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();
        
        return [
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'list' => $list,
        ];
    }

    /**
     * 处理退款
     */
    public static function refundOrder(int $orderId, float $amount, string $reason, int $adminId): bool
    {
        $order = Db::table('tc_vip_order')->where('id', $orderId)->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        
        if ($order['status'] != 1) {
            throw new \Exception('订单未支付，无法退款');
        }
        
        if ($amount > $order['pay_amount']) {
            throw new \Exception('退款金额不能超过支付金额');
        }

        Log::info('后台订单退款开始', [
            'admin_id' => $adminId,
            'order_id' => $orderId,
            'order_no' => self::maskOrderNo((string) ($order['order_no'] ?? '')),
            'amount' => round($amount, 2),
            'pay_amount' => round((float) ($order['pay_amount'] ?? 0), 2),
        ]);
        
        Db::startTrans();
        try {
            // 生成退款单号
            $refundNo = 'REF' . date('YmdHis') . mt_rand(1000, 9999);
            
            // 1. 调用微信支付退款接口
            $refundResult = WechatPayService::refund(
                $order['order_no'],
                $refundNo,
                (float)$order['pay_amount'],
                $amount,
                $reason
            );

            if (!$refundResult['success']) {
                throw new \Exception('微信退款失败: ' . $refundResult['message']);
            }

            // 2. 更新订单状态
            Db::table('tc_vip_order')
                ->where('id', $orderId)
                ->update([
                    'status' => 3,  // 已退款
                    'refund_no' => $refundNo,
                    'refund_amount' => $amount,
                    'refund_time' => date('Y-m-d H:i:s'),
                    'refund_reason' => $reason,
                ]);
            
            // 3. 记录管理员操作日志
            Db::table('tc_admin_log')->insert([
                'admin_id' => $adminId,
                'action' => 'refund_order',
                'target_type' => 'order',
                'target_id' => $orderId,
                'content' => "订单退款成功: 金额{$amount}, 退款单号: {$refundNo}, 原因: {$reason}",
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            Db::commit();

            Log::info('后台订单退款成功', [
                'admin_id' => $adminId,
                'order_id' => $orderId,
                'order_no' => self::maskOrderNo((string) ($order['order_no'] ?? '')),
                'refund_no' => self::maskOrderNo($refundNo),
                'amount' => round($amount, 2),
            ]);

            return true;
        } catch (\Exception $e) {
            Db::rollback();

            Log::error('后台订单退款失败', [
                'admin_id' => $adminId,
                'order_id' => $orderId,
                'order_no' => self::maskOrderNo((string) ($order['order_no'] ?? '')),
                'amount' => round($amount, 2),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 脱敏订单号/退款单号
     */
    private static function maskOrderNo(string $value): string
    {
        $length = strlen($value);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($value, -4);
    }
}
