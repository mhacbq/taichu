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
    protected static array $tableColumnCache = [];
    protected static array $resolvedTableCache = [];

    /**
     * 获取Dashboard统计数据
     */
    public static function getDashboardStats(): array
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $thisMonth = date('Y-m');

        $overview = self::getOverviewStats($today, $yesterday);
        $userStats = self::getUserStats($today, $thisMonth);
        $orderStats = self::getOrderStats($today, $thisMonth);
        $divinationStats = self::getDivinationStats($today);
        $pointsStats = self::getPointsStats($today);
        $trend = self::getTrendStats(30);

        return [
            'overview' => $overview,
            'user_stats' => $userStats,
            'order_stats' => $orderStats,
            'divination_stats' => $divinationStats,
            'points_stats' => $pointsStats,
            'trend' => $trend,
            'statistics' => self::buildLegacyDashboardStatistics($userStats, $divinationStats),
            'user_trend' => $trend['user_trend'] ?? [],
            'bazi_trend' => $trend['bazi_trend'] ?? [],
            'tarot_trend' => $trend['tarot_trend'] ?? [],
        ];
    }

    /**
     * 获取概览统计
     */
    private static function getOverviewStats(string $today, string $yesterday): array
    {
        $todayStats = self::getDailyStatsSnapshot($today);
        $yesterdayStats = self::getDailyStatsSnapshot($yesterday);

        $totalUsers = 0;
        $totalPoints = 0;
        if (self::tableExists('tc_user')) {
            $totalUserQuery = Db::table('tc_user');
            if (self::tableHasColumn('tc_user', 'status')) {
                $totalUserQuery->where('status', 1);
            }
            $totalUsers = (int) $totalUserQuery->count();

            if (self::tableHasColumn('tc_user', 'points')) {
                $totalPoints = (int) (Db::table('tc_user')->sum('points') ?? 0);
            }
        }

        return [
            'today_revenue' => [
                'value' => (float) ($todayStats['paid_amount'] ?? 0),
                'yesterday' => (float) ($yesterdayStats['paid_amount'] ?? 0),
                'change' => self::calculateChange(
                    (float) ($todayStats['paid_amount'] ?? 0),
                    (float) ($yesterdayStats['paid_amount'] ?? 0)
                ),
            ],
            'today_orders' => [
                'value' => (int) ($todayStats['paid_count'] ?? 0),
                'yesterday' => (int) ($yesterdayStats['paid_count'] ?? 0),
                'change' => self::calculateChange(
                    (float) ($todayStats['paid_count'] ?? 0),
                    (float) ($yesterdayStats['paid_count'] ?? 0)
                ),
            ],
            'new_users' => [
                'value' => (int) ($todayStats['new_users'] ?? 0),
                'yesterday' => (int) ($yesterdayStats['new_users'] ?? 0),
                'change' => self::calculateChange(
                    (float) ($todayStats['new_users'] ?? 0),
                    (float) ($yesterdayStats['new_users'] ?? 0)
                ),
            ],
            'active_users' => [
                'value' => (int) ($todayStats['active_users'] ?? 0),
                'yesterday' => (int) ($yesterdayStats['active_users'] ?? 0),
                'change' => self::calculateChange(
                    (float) ($todayStats['active_users'] ?? 0),
                    (float) ($yesterdayStats['active_users'] ?? 0)
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
        $monthNewUsers = self::tableExists('tc_user')
            ? (int) Db::table('tc_user')->whereLike('created_at', $thisMonth . '%')->count()
            : 0;

        $userSources = [];
        if (self::tableHasColumn('tc_user', 'source')) {
            $userSources = Db::table('tc_user')
                ->field('source, COUNT(*) as count')
                ->group('source')
                ->select()
                ->toArray();
        }

        $vipUsers = 0;
        $vipTable = self::resolveVipUserTable();
        if ($vipTable !== null) {
            $vipQuery = Db::table($vipTable);
            if (self::tableHasColumn($vipTable, 'status')) {
                $vipQuery->where('status', 1);
            } elseif (self::tableHasColumn($vipTable, 'is_active')) {
                $vipQuery->where('is_active', 1);
            }
            if (self::tableHasColumn($vipTable, 'end_time')) {
                $vipQuery->where('end_time', '>', date('Y-m-d H:i:s'));
            }
            $vipUsers = (int) $vipQuery->count();
        }

        $todayStats = self::getDailyStatsSnapshot($today);

        return [
            'total' => self::tableExists('tc_user') ? (int) Db::table('tc_user')->count() : 0,
            'today_new' => (int) ($todayStats['new_users'] ?? 0),
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
        $todayStats = self::getDailyStatsSnapshot($today);
        $monthStats = self::getMonthlyOrderStats($thisMonth);

        $statusDistribution = [];
        $vipOrderTable = self::resolveVipOrderTable();
        if ($vipOrderTable !== null && self::tableHasColumn($vipOrderTable, 'status')) {
            $statusDistribution = Db::table($vipOrderTable)
                ->field('status, COUNT(*) as count')
                ->group('status')
                ->select()
                ->toArray();
        }

        return [
            'today' => [
                'total' => (int) ($todayStats['order_count'] ?? 0),
                'paid' => (int) ($todayStats['paid_count'] ?? 0),
                'amount' => (float) ($todayStats['paid_amount'] ?? 0),
            ],
            'month' => [
                'total' => (int) ($monthStats['total_orders'] ?? 0),
                'paid_orders' => (int) ($monthStats['paid_orders'] ?? 0),
                'amount' => (float) ($monthStats['total_amount'] ?? 0),
            ],
            'status_distribution' => $statusDistribution,
        ];
    }

    /**
     * 获取占卜统计
     */
    private static function getDivinationStats(string $today): array
    {
        $todayStats = self::getDailyStatsSnapshot($today);

        return [
            'today' => [
                'bazi' => (int) ($todayStats['bazi_count'] ?? 0),
                'tarot' => (int) ($todayStats['tarot_count'] ?? 0),
                'liuyao' => (int) ($todayStats['liuyao_count'] ?? 0),
                'hehun' => (int) ($todayStats['hehun_count'] ?? 0),
                'daily_fortune' => (int) ($todayStats['daily_fortune_count'] ?? 0),
            ],
            'total' => [
                'bazi' => self::countAllRows('tc_bazi_record'),
                'tarot' => self::countAllRows('tc_tarot_record'),
                'liuyao' => self::countAllRows(self::resolveLiuyaoRecordTable()),
                'hehun' => self::countAllRows('hehun_records'),
            ],
        ];
    }

    /**
     * 获取积分统计快照（供积分管理页单独调用）
     */
    public static function getPointsStatsSnapshot(?string $date = null): array
    {
        $targetDate = self::normalizeStatsDate($date);

        return array_merge([
            'date' => $targetDate,
        ], self::buildPointsStatsPayload($targetDate));
    }

    /**
     * 获取积分统计
     */
    private static function getPointsStats(string $today): array
    {
        return self::buildPointsStatsPayload($today);
    }

    /**
     * 构建积分统计输出
     */
    private static function buildPointsStatsPayload(string $date): array
    {
        [$todayGiven, $todayConsumed, $topConsumers, $recordCount] = self::collectPointsStatsByDate($date);

        return [
            'today_given' => $todayGiven,
            'today_consumed' => $todayConsumed,
            'balance' => self::getCurrentPointsBalance(),
            'top_consumers' => $topConsumers,
            'total_records' => $recordCount,
        ];
    }

    /**
     * 汇总某日积分数据与高消耗用户
     */
    private static function collectPointsStatsByDate(string $date): array
    {
        if (!self::tableExists('tc_points_record')
            || !self::tableHasColumn('tc_points_record', 'created_at')) {
            return [0, 0, [], 0];
        }

        $baseQuery = Db::table('tc_points_record')
            ->where('created_at', '>=', $date . ' 00:00:00')
            ->where('created_at', '<=', $date . ' 23:59:59');

        $recordCount = (int) (clone $baseQuery)->count();
        if ($recordCount === 0) {
            return [0, 0, [], 0];
        }

        $todayGiven = 0;
        $todayConsumed = 0;
        $topConsumers = [];

        if (self::tableHasColumn('tc_points_record', 'points')) {
            $todayGiven = (int) ((clone $baseQuery)->where('points', '>', 0)->sum('points') ?? 0);
            $todayConsumed = abs((int) ((clone $baseQuery)->where('points', '<', 0)->sum('points') ?? 0));

            if (self::tableHasColumn('tc_points_record', 'user_id')) {
                $topConsumers = (clone $baseQuery)
                    ->where('points', '<', 0)
                    ->field('user_id, ABS(SUM(points)) as total_points')
                    ->group('user_id')
                    ->order('total_points', 'DESC')
                    ->limit(10)
                    ->select()
                    ->toArray();
            }
        } elseif (self::tableHasColumn('tc_points_record', 'amount')
            && self::tableHasColumn('tc_points_record', 'type')) {
            $spentTypes = ['reduce', 'consume', 'deduct', 'expense', 'cost', 'exchange', 'redeem', 'refund', 'tarot', 'bazi', 'hehun', 'liuyao'];

            $todayConsumed = (int) ((clone $baseQuery)->whereIn('type', $spentTypes)->sum('amount') ?? 0);
            $todayGiven = (int) ((clone $baseQuery)->whereNotIn('type', $spentTypes)->sum('amount') ?? 0);

            if (self::tableHasColumn('tc_points_record', 'user_id')) {
                $topConsumers = (clone $baseQuery)
                    ->whereIn('type', $spentTypes)
                    ->field('user_id, SUM(amount) as total_points')
                    ->group('user_id')
                    ->order('total_points', 'DESC')
                    ->limit(10)
                    ->select()
                    ->toArray();
            }
        }

        return [
            $todayGiven,
            $todayConsumed,
            self::enrichTopPointsConsumers($topConsumers),
            $recordCount,
        ];
    }

    /**
     * 为高消耗用户补充展示字段
     */
    private static function enrichTopPointsConsumers(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $userIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['user_id'] ?? 0),
            $rows
        ), static fn (int $userId): bool => $userId > 0)));

        $userMap = [];
        $userTable = self::resolveUserTable();
        if (!empty($userIds)
            && $userTable !== null
            && self::tableExists($userTable)
            && self::tableHasColumn($userTable, 'id')) {
            $fields = ['id'];
            $fields[] = self::tableHasColumn($userTable, 'username') ? 'username' : "'' as username";
            $fields[] = self::tableHasColumn($userTable, 'nickname') ? 'nickname' : "'' as nickname";
            $fields[] = self::tableHasColumn($userTable, 'phone') ? 'phone' : "'' as phone";

            $users = Db::table($userTable)
                ->whereIn('id', $userIds)
                ->field($fields)
                ->select()
                ->toArray();

            foreach ($users as $user) {
                $userMap[(int) ($user['id'] ?? 0)] = $user;
            }
        }

        foreach ($rows as &$row) {
            $userId = (int) ($row['user_id'] ?? 0);
            $user = $userMap[$userId] ?? [];
            $rawUsername = trim((string) ($user['username'] ?? ''));
            $rawNickname = trim((string) ($user['nickname'] ?? ''));
            $rawPhone = trim((string) ($user['phone'] ?? ''));
            $displayName = self::resolveDisplayUserName($rawUsername, $rawNickname, $rawPhone, $userId);

            $row['user_id'] = $userId;
            $row['total_points'] = abs((int) ($row['total_points'] ?? 0));
            $row['username'] = $displayName;
            $row['nickname'] = $rawNickname !== '' && !self::isPhoneLikeUserName($rawNickname, $rawPhone)
                ? $rawNickname
                : $displayName;
            $row['phone'] = $rawPhone;
        }
        unset($row);

        return $rows;
    }

    /**
     * 获取当前积分总余额
     */
    private static function getCurrentPointsBalance(): int
    {
        if (!self::tableExists('tc_user') || !self::tableHasColumn('tc_user', 'points')) {
            return 0;
        }

        return (int) (Db::table('tc_user')->sum('points') ?? 0);
    }

    /**
     * 归一化统计日期
     */
    private static function normalizeStatsDate(?string $date): string
    {
        $value = trim((string) ($date ?? ''));
        if ($value !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return date('Y-m-d');
    }


    /**
     * 获取趋势统计（兼容旧后台结构）
     */
    public static function getTrendStats(int $days = 30): array
    {
        $days = max(1, min(90, $days));
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $storedTrend = self::buildStoredTrendMap($startDate, $endDate);

        $dates = [];
        $revenue = [];
        $orders = [];
        $newUsers = [];
        $divination = [];
        $userTrend = [];
        $baziTrend = [];
        $tarotTrend = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $dates[] = date('m-d', strtotime($date));

            $dayStats = $storedTrend[$date] ?? self::buildLiveDailyStats($date);
            $paidAmount = (float) ($dayStats['paid_amount'] ?? 0);
            $paidCount = (int) ($dayStats['paid_count'] ?? 0);
            $dayNewUsers = (int) ($dayStats['new_users'] ?? 0);
            $dayBazi = (int) ($dayStats['bazi_count'] ?? 0);
            $dayTarot = (int) ($dayStats['tarot_count'] ?? 0);
            $dayLiuyao = (int) ($dayStats['liuyao_count'] ?? 0);

            $revenue[] = $paidAmount;
            $orders[] = $paidCount;
            $newUsers[] = $dayNewUsers;
            $divination[] = $dayBazi + $dayTarot + $dayLiuyao;

            $userTrend[] = ['date' => $date, 'count' => $dayNewUsers];
            $baziTrend[] = ['date' => $date, 'count' => $dayBazi];
            $tarotTrend[] = ['date' => $date, 'count' => $dayTarot];
        }

        return [
            'dates' => $dates,
            'revenue' => $revenue,
            'orders' => $orders,
            'new_users' => $newUsers,
            'divination' => $divination,
            'user_trend' => $userTrend,
            'bazi_trend' => $baziTrend,
            'tarot_trend' => $tarotTrend,
        ];
    }

    /**
     * 构建独立后台兼容统计卡片数据
     */
    private static function buildLegacyDashboardStatistics(array $userStats, array $divinationStats): array
    {
        return [
            'total_users' => (int) ($userStats['total'] ?? 0),
            'today_users' => (int) ($userStats['today_new'] ?? 0),
            'total_bazi' => (int) ($divinationStats['total']['bazi'] ?? 0),
            'today_bazi' => (int) ($divinationStats['today']['bazi'] ?? 0),
            'total_tarot' => (int) ($divinationStats['total']['tarot'] ?? 0),
            'today_tarot' => (int) ($divinationStats['today']['tarot'] ?? 0),
        ];
    }

    /**
     * 读取已落库的日统计数据
     */
    private static function buildStoredTrendMap(string $startDate, string $endDate): array
    {
        if (!self::tableExists('site_daily_stats')) {
            return [];
        }

        $rows = Db::table('site_daily_stats')
            ->whereBetween('stat_date', [$startDate, $endDate])
            ->order('stat_date', 'ASC')
            ->select()
            ->toArray();

        $result = [];
        foreach ($rows as $row) {
            $statDate = (string) ($row['stat_date'] ?? '');
            if ($statDate === '') {
                continue;
            }

            $result[$statDate] = $row;
        }

        return $result;
    }

    /**
     * 获取月度订单统计
     */
    private static function getMonthlyOrderStats(string $thisMonth): array
    {
        $orderTable = self::resolveRechargeOrderTable();
        if ($orderTable !== null) {
            $paidAmount = 0;
            $paidCount = 0;
            if (self::tableHasColumn($orderTable, 'pay_time')) {
                $paidBaseQuery = Db::table($orderTable)
                    ->whereLike('pay_time', $thisMonth . '%');

                foreach (self::getRechargePaidConditions($orderTable) as $condition) {
                    [$field, $operator, $value] = $condition;
                    $paidBaseQuery->where($field, $operator, $value);
                }

                $paidCount = (int) (clone $paidBaseQuery)->count();
                $paidAmount = (float) ((clone $paidBaseQuery)->sum('amount') ?? 0);
            }

            return [
                'total_orders' => self::tableHasColumn($orderTable, 'created_at')
                    ? (int) Db::table($orderTable)->whereLike('created_at', $thisMonth . '%')->count()
                    : 0,
                'paid_orders' => $paidCount,
                'total_amount' => $paidAmount,
            ];
        }

        if (self::tableExists('site_daily_stats')) {
            $monthStats = Db::table('site_daily_stats')
                ->whereLike('stat_date', $thisMonth . '%')
                ->field('SUM(order_count) as total_orders, SUM(paid_count) as paid_orders, SUM(paid_amount) as total_amount')
                ->find() ?: [];

            return [
                'total_orders' => (int) ($monthStats['total_orders'] ?? 0),
                'paid_orders' => (int) ($monthStats['paid_orders'] ?? 0),
                'total_amount' => (float) ($monthStats['total_amount'] ?? 0),
            ];
        }

        return ['total_orders' => 0, 'paid_orders' => 0, 'total_amount' => 0];
    }

    /**
     * 获取指定日期的统计快照
     */
    private static function getDailyStatsSnapshot(string $date): array
    {
        if (self::tableExists('site_daily_stats')) {
            $stats = Db::table('site_daily_stats')
                ->where('stat_date', $date)
                ->find();
            if (is_array($stats) && !empty($stats)) {
                return $stats;
            }
        }

        return self::buildLiveDailyStats($date);
    }

    /**
     * 根据当前业务表实时构建某天的统计数据
     */
    private static function buildLiveDailyStats(string $date): array
    {
        $rechargeOrderTable = self::resolveRechargeOrderTable();
        $refundTimeField = $rechargeOrderTable !== null && self::tableHasColumn($rechargeOrderTable, 'refund_time')
            ? 'refund_time'
            : null;
        $paidConditions = self::getRechargePaidConditions($rechargeOrderTable);
        $refundConditions = self::getRechargeRefundConditions($rechargeOrderTable);

        $orderCount = self::countRowsByDate($rechargeOrderTable, 'created_at', $date);
        $orderAmount = self::sumFieldByDate($rechargeOrderTable, 'created_at', $date, 'amount');
        $paidCount = self::countRowsByDate($rechargeOrderTable, 'pay_time', $date, $paidConditions);
        $paidAmount = self::sumFieldByDate($rechargeOrderTable, 'pay_time', $date, 'amount', $paidConditions);
        $refundCount = $refundTimeField !== null
            ? self::countRowsByDate($rechargeOrderTable, $refundTimeField, $date, $refundConditions)
            : 0;
        $refundAmount = $refundTimeField !== null
            ? self::sumFieldByDate($rechargeOrderTable, $refundTimeField, $date, 'refund_amount', $refundConditions)
            : 0;
        $pointsStats = self::collectPointsStatsByDate($date);

        return [
            'stat_date' => $date,
            'new_users' => self::countRowsByDate('tc_user', 'created_at', $date),
            'active_users' => self::countRowsByDate('tc_user', 'last_login_at', $date),
            'total_users' => self::countAllRows('tc_user'),
            'points_given' => (int) ($pointsStats[0] ?? 0),
            'points_consumed' => (int) ($pointsStats[1] ?? 0),
            'points_balance' => self::getCurrentPointsBalance(),
            'bazi_count' => self::countRowsByDate('tc_bazi_record', 'created_at', $date),
            'tarot_count' => self::countRowsByDate('tc_tarot_record', 'created_at', $date),
            'liuyao_count' => self::countRowsByDate(self::resolveLiuyaoRecordTable(), 'created_at', $date),
            'hehun_count' => self::countRowsByDate('hehun_records', 'created_at', $date),
            'daily_fortune_count' => 0,
            'order_count' => $orderCount,
            'order_amount' => round($orderAmount, 2),
            'paid_count' => $paidCount,
            'paid_amount' => round($paidAmount, 2),
            'refund_count' => $refundCount,
            'refund_amount' => round($refundAmount, 2),
        ];
    }

    /**
     * 统计某天的记录数
     */
    private static function countRowsByDate(?string $table, string $timeField, string $date, array $conditions = []): int
    {
        if ($table === null || !self::tableExists($table) || !self::tableHasColumn($table, $timeField)) {
            return 0;
        }

        $query = Db::table($table)
            ->where($timeField, '>=', $date . ' 00:00:00')
            ->where($timeField, '<=', $date . ' 23:59:59');

        foreach ($conditions as $condition) {
            if (!is_array($condition) || count($condition) < 3) {
                continue;
            }

            [$field, $operator, $value] = $condition;
            if (!is_string($field) || $field === '' || !self::tableHasColumn($table, $field)) {
                return 0;
            }

            $query->where($field, (string) $operator, $value);
        }

        return (int) $query->count();
    }

    /**
     * 统计某天某个数值字段的汇总
     */
    private static function sumFieldByDate(?string $table, string $timeField, string $date, string $field, array $conditions = []): float
    {
        if ($table === null
            || !self::tableExists($table)
            || !self::tableHasColumn($table, $timeField)
            || !self::tableHasColumn($table, $field)) {
            return 0;
        }

        $query = Db::table($table)
            ->where($timeField, '>=', $date . ' 00:00:00')
            ->where($timeField, '<=', $date . ' 23:59:59');

        foreach ($conditions as $condition) {
            if (!is_array($condition) || count($condition) < 3) {
                continue;
            }

            [$conditionField, $operator, $value] = $condition;
            if (!is_string($conditionField) || $conditionField === '' || !self::tableHasColumn($table, $conditionField)) {
                return 0;
            }

            $query->where($conditionField, (string) $operator, $value);
        }

        return (float) ($query->sum($field) ?? 0);
    }

    /**
     * 汇总某天的积分发放/消耗
     */
    private static function sumPointsByTypeAndDate(string $date, array $types): int
    {
        if (empty($types)
            || !self::tableExists('tc_points_record')
            || !self::tableHasColumn('tc_points_record', 'type')
            || !self::tableHasColumn('tc_points_record', 'points')
            || !self::tableHasColumn('tc_points_record', 'created_at')) {
            return 0;
        }

        $query = Db::table('tc_points_record')
            ->where('created_at', '>=', $date . ' 00:00:00')
            ->where('created_at', '<=', $date . ' 23:59:59');

        if (count($types) === 1) {
            $query->where('type', $types[0]);
        } else {
            $query->whereIn('type', $types);
        }

        return (int) ($query->sum('points') ?? 0);
    }

    /**
     * 生成充值订单“已支付”筛选条件
     */
    private static function getRechargePaidConditions(?string $table): array
    {
        if ($table === null) {
            return [];
        }

        if (self::tableHasColumn($table, 'status')) {
            return [['status', '=', 'paid']];
        }

        if (self::tableHasColumn($table, 'pay_status')) {
            return [['pay_status', '=', 1]];
        }

        return [];
    }

    /**
     * 生成充值订单“已退款”筛选条件
     */
    private static function getRechargeRefundConditions(?string $table): array
    {
        if ($table !== null && self::tableHasColumn($table, 'status')) {
            return [['status', '=', 'refunded']];
        }

        return [];
    }

    /**
     * 统计整表记录数
     */
    private static function countAllRows(?string $table): int
    {
        if ($table === null || !self::tableExists($table)) {
            return 0;
        }

        return (int) Db::table($table)->count();
    }

    /**
     * 解析充值订单表
     */
    private static function resolveRechargeOrderTable(): ?string
    {
        return self::resolveFirstExistingTable('recharge_order', ['tc_recharge_order']);
    }

    /**
     * 解析 VIP 订单表
     */
    private static function resolveVipOrderTable(): ?string
    {
        return self::resolveFirstExistingTable('vip_order', ['tc_vip_order', 'vip_orders']);
    }

    /**
     * 解析用户 VIP 表
     */
    private static function resolveVipUserTable(): ?string
    {
        return self::resolveFirstExistingTable('vip_user', ['tc_user_vip', 'user_vip']);
    }

    /**
     * 解析用户主表
     */
    private static function resolveUserTable(): ?string
    {
        return self::resolveFirstExistingTable('user', ['tc_user', 'user']);
    }

    /**
     * 归一化 VIP 订单状态
     */
    private static function normalizeVipOrderStatus(mixed $status): int
    {
        if (is_numeric($status)) {
            return (int) $status;
        }

        $normalized = strtolower(trim((string) $status));
        return match ($normalized) {
            'paid', 'success', 'completed' => 1,
            'cancelled', 'canceled', 'closed' => 2,
            'refunded' => 3,
            default => 0,
        };
    }

    /**
     * 归一化 VIP 订单输出字段
     */
    private static function normalizeVipOrderRow(array $row): array
    {
        $amount = isset($row['pay_amount']) ? $row['pay_amount'] : ($row['amount'] ?? 0);
        $packagePrice = isset($row['package_price']) ? $row['package_price'] : ($row['amount'] ?? 0);
        $packageName = trim((string) ($row['package_name'] ?? ''));

        if ($packageName === '' && isset($row['duration']) && (int) $row['duration'] > 0) {
            $packageName = (int) $row['duration'] . '天VIP';
        }

        return array_merge($row, [
            'order_no' => (string) ($row['order_no'] ?? ''),
            'user_id' => (int) ($row['user_id'] ?? 0),
            'nickname' => (string) ($row['nickname'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'package_name' => $packageName,
            'package_price' => round((float) $packagePrice, 2),
            'pay_amount' => round((float) $amount, 2),
            'status' => self::normalizeVipOrderStatus($row['status'] ?? 0),
            'pay_time' => (string) ($row['pay_time'] ?? ''),
            'refund_time' => (string) ($row['refund_time'] ?? ''),
            'refund_no' => (string) ($row['refund_no'] ?? ''),
            'refund_amount' => round((float) ($row['refund_amount'] ?? 0), 2),
            'refund_reason' => (string) ($row['refund_reason'] ?? ''),
            'transaction_id' => (string) (($row['transaction_id'] ?? '') ?: ($row['pay_trade_no'] ?? '')),
            'platform' => (string) (($row['platform'] ?? '') ?: ($row['pay_type'] ?? 'wechat')),
        ]);
    }

    /**
     * 解析六爻记录表
     */
    private static function resolveLiuyaoRecordTable(): ?string
    {
        return self::resolveFirstExistingTable('liuyao_record', ['tc_liuyao_record', 'liuyao_records']);
    }


    /**
     * 返回首个存在的数据表
     */
    private static function resolveFirstExistingTable(string $cacheKey, array $candidates): ?string
    {
        if (array_key_exists($cacheKey, self::$resolvedTableCache)) {
            return self::$resolvedTableCache[$cacheKey];
        }

        foreach ($candidates as $table) {
            if (self::tableExists((string) $table)) {
                self::$resolvedTableCache[$cacheKey] = (string) $table;
                return (string) $table;
            }
        }

        self::$resolvedTableCache[$cacheKey] = null;
        return null;
    }

    /**
     * 判断数据表是否存在指定字段
     */
    private static function tableHasColumn(string $table, string $column): bool
    {
        if ($table === '' || $column === '') {
            return false;
        }

        $columns = self::getTableColumns($table);
        return isset($columns[$column]);
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
    public static function updateDailyStats(?string $date = null): bool
    {
        $date = trim((string) ($date ?? '')) ?: date('Y-m-d');
        $snapshot = self::buildLiveDailyStats($date);

        if (!self::tableExists('site_daily_stats')) {
            Log::warning('site_daily_stats 表不存在，跳过统计落库', [
                'date' => $date,
            ]);
            return true;
        }

        $columns = self::getTableColumns('site_daily_stats');
        $data = [];
        foreach ($snapshot as $field => $value) {
            if (isset($columns[$field])) {
                $data[$field] = $value;
            }
        }
        if (isset($columns['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

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
     * 构建按用户统计次数的子查询字段
     */
    private static function buildUserRelationCountField(?string $table, string $alias): string
    {
        if ($table === null
            || !self::tableExists($table)
            || !self::tableHasColumn($table, 'user_id')) {
            return '0 as ' . $alias;
        }

        return sprintf('(SELECT COUNT(*) FROM %s stats WHERE stats.user_id = u.id) as %s', $table, $alias);
    }

    /**
     * 构建 VIP 有效记录筛选 SQL
     */
    private static function buildVipActiveConditionSql(string $table, string $alias = 'uv'): string
    {
        $conditions = [sprintf('%s.user_id = u.id', $alias)];

        if (self::tableHasColumn($table, 'status')) {
            $conditions[] = sprintf('%s.status = 1', $alias);
        } elseif (self::tableHasColumn($table, 'is_active')) {
            $conditions[] = sprintf('%s.is_active = 1', $alias);
        }

        if (self::tableHasColumn($table, 'end_time')) {
            $conditions[] = sprintf("%s.end_time > '%s'", $alias, date('Y-m-d H:i:s'));
        }

        return implode(' AND ', $conditions);
    }

    /**
     * 构建用户列表所需的 VIP 字段
     */
    private static function buildUserVipFields(): array
    {
        $vipTable = self::resolveVipUserTable();
        if ($vipTable === null || !self::tableHasColumn($vipTable, 'user_id')) {
            return [
                '0 as is_vip',
                'NULL as vip_end_time',
            ];
        }

        $conditionSql = self::buildVipActiveConditionSql($vipTable);
        $vipEndTimeField = self::tableHasColumn($vipTable, 'end_time')
            ? sprintf('(SELECT MAX(uv.end_time) FROM %s uv WHERE %s) as vip_end_time', $vipTable, $conditionSql)
            : 'NULL as vip_end_time';

        return [
            sprintf('CASE WHEN EXISTS(SELECT 1 FROM %s uv WHERE %s) THEN 1 ELSE 0 END as is_vip', $vipTable, $conditionSql),
            $vipEndTimeField,
        ];
    }

    /**
     * 按是否 VIP 过滤用户列表
     */
    private static function applyVipFilterToUserQuery($query, $isVip): void
    {
        if ($isVip === null || $isVip === '' || !in_array((int) $isVip, [0, 1], true)) {
            return;
        }

        $vipTable = self::resolveVipUserTable();
        if ($vipTable === null || !self::tableHasColumn($vipTable, 'user_id')) {
            if ((int) $isVip === 1) {
                $query->whereRaw('1 = 0');
            }
            return;
        }

        $conditionSql = self::buildVipActiveConditionSql($vipTable);
        if ((int) $isVip === 1) {
            $query->whereRaw(sprintf('EXISTS(SELECT 1 FROM %s uv WHERE %s)', $vipTable, $conditionSql));
            return;
        }

        $query->whereRaw(sprintf('NOT EXISTS(SELECT 1 FROM %s uv WHERE %s)', $vipTable, $conditionSql));
    }

    /**
     * 按 user_id 批量统计关联记录数
     */
    private static function buildUserRelationCountMap(?string $table, array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn (int $id): bool => $id > 0)));
        if (empty($userIds)
            || $table === null
            || !self::tableExists($table)
            || !self::tableHasColumn($table, 'user_id')) {
            return [];
        }

        $rows = Db::table($table)
            ->whereIn('user_id', $userIds)
            ->field('user_id, COUNT(*) as total')
            ->group('user_id')
            ->select()
            ->toArray();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) ($row['user_id'] ?? 0)] = (int) ($row['total'] ?? 0);
        }

        return $result;
    }

    /**
     * 批量解析用户 VIP 状态与到期时间
     */
    private static function buildUserVipStatusMap(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn (int $id): bool => $id > 0)));
        if (empty($userIds)) {
            return [];
        }

        $vipTable = self::resolveVipUserTable();
        if ($vipTable === null || !self::tableHasColumn($vipTable, 'user_id')) {
            return [];
        }

        $query = Db::table($vipTable)->whereIn('user_id', $userIds);
        if (self::tableHasColumn($vipTable, 'status')) {
            $query->where('status', 1);
        } elseif (self::tableHasColumn($vipTable, 'is_active')) {
            $query->where('is_active', 1);
        }

        $hasVipEndTime = self::tableHasColumn($vipTable, 'end_time');
        if ($hasVipEndTime) {
            $query->where('end_time', '>', date('Y-m-d H:i:s'));
        }

        $rows = $query
            ->field($hasVipEndTime ? 'user_id, MAX(end_time) as vip_end_time' : 'user_id')
            ->group('user_id')
            ->select()
            ->toArray();

        $result = [];
        foreach ($rows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $result[$userId] = [
                'is_vip' => 1,
                'vip_end_time' => $hasVipEndTime ? ($row['vip_end_time'] ?? null) : null,
            ];
        }

        return $result;
    }

    /**
     * 向用户列表查询追加关键词检索条件
     */
    private static function applyUserKeywordFilter($query, string $keyword): void
    {
        $escapedKeyword = '%' . addcslashes($keyword, '%_\\') . '%';
        $query->where(function ($q) use ($escapedKeyword, $keyword) {
            $hasCondition = false;
            if (self::tableHasColumn('tc_user', 'username')) {
                $q->whereLike('u.username', $escapedKeyword);
                $hasCondition = true;
            }
            if (self::tableHasColumn('tc_user', 'nickname')) {
                if ($hasCondition) {
                    $q->whereOrLike('u.nickname', $escapedKeyword);
                } else {
                    $q->whereLike('u.nickname', $escapedKeyword);
                    $hasCondition = true;
                }
            }
            if (self::tableHasColumn('tc_user', 'phone')) {
                if ($hasCondition) {
                    $q->whereOrLike('u.phone', $escapedKeyword);
                } else {
                    $q->whereLike('u.phone', $escapedKeyword);
                    $hasCondition = true;
                }
            }

            if (preg_match('/^\d+$/', $keyword)) {
                if ($hasCondition) {
                    $q->whereOr('u.id', (int) $keyword);
                } else {
                    $q->where('u.id', (int) $keyword);
                    $hasCondition = true;
                }
            }
            if (!$hasCondition) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    private static function resolveDisplayUserName(string $username, string $nickname, string $phone, int $userId): string
    {
        if ($username !== '' && !self::isPhoneLikeUserName($username, $phone)) {
            return $username;
        }

        if ($nickname !== '' && !self::isPhoneLikeUserName($nickname, $phone)) {
            return $nickname;
        }

        return '用户#' . $userId;
    }

    private static function isPhoneLikeUserName(string $value, string $phone = ''): bool
    {
        $normalizedValue = trim($value);
        if ($normalizedValue === '') {
            return false;
        }

        $normalizedPhone = trim($phone);
        if ($normalizedPhone !== '' && $normalizedValue === $normalizedPhone) {
            return true;
        }

        return (bool) preg_match('/^1[3-9]\d{9}$/', $normalizedValue);
    }

    /**
     * 获取用户列表（带筛选）
     */


    public static function getUserList(array $params = []): array
    {

        if (!self::tableExists('tc_user')) {
            return [
                'total' => 0,
                'page' => 1,
                'page_size' => 20,
                'pageSize' => 20,
                'list' => [],
            ];
        }

        $page = max(1, (int) ($params['page'] ?? 1));
        $pageSize = (int) ($params['page_size'] ?? ($params['pageSize'] ?? 20));
        $pageSize = max(1, min(100, $pageSize));

        $username = trim((string) ($params['username'] ?? ''));
        $phone = trim((string) ($params['phone'] ?? ''));
        $keyword = trim((string) ($params['keyword'] ?? ''));
        $status = $params['status'] ?? null;
        $isVip = $params['is_vip'] ?? null;

        $dateRange = $params['dateRange'] ?? [];
        if (!is_array($dateRange)) {
            $dateRange = [];
        }
        $startDate = trim((string) ($params['startDate'] ?? ($params['start_date'] ?? ($dateRange[0] ?? ''))));
        $endDate = trim((string) ($params['endDate'] ?? ($params['end_date'] ?? ($dateRange[1] ?? ''))));

        $fields = [
            'u.id',
            self::tableHasColumn('tc_user', 'username') ? 'u.username' : "'' as username",
            self::tableHasColumn('tc_user', 'nickname') ? 'u.nickname' : "'' as nickname",
            self::tableHasColumn('tc_user', 'avatar') ? 'u.avatar' : "'' as avatar",
            self::tableHasColumn('tc_user', 'phone') ? 'u.phone' : "'' as phone",
            self::tableHasColumn('tc_user', 'points') ? 'u.points' : '0 as points',
            self::tableHasColumn('tc_user', 'created_at') ? 'u.created_at' : 'NULL as created_at',
            self::tableHasColumn('tc_user', 'last_login_at') ? 'u.last_login_at' : 'NULL as last_login_at',
            self::tableHasColumn('tc_user', 'status') ? 'u.status' : '1 as status',
        ];

        $query = Db::table('tc_user')->alias('u')->field($fields);

        if ($username !== '') {
            self::applyUserKeywordFilter($query, $username);
        } elseif ($keyword !== '') {
            self::applyUserKeywordFilter($query, $keyword);
        }



        if ($phone !== '' && self::tableHasColumn('tc_user', 'phone')) {
            $escapedPhone = addcslashes($phone, '%_\\');
            $query->whereLike('u.phone', '%' . $escapedPhone . '%');
        }

        if ($status !== null && $status !== '' && self::tableHasColumn('tc_user', 'status')) {
            $query->where('u.status', (int) $status);
        }

        self::applyVipFilterToUserQuery($query, $isVip);

        if ($startDate !== '' && self::tableHasColumn('tc_user', 'created_at')) {
            $query->where('u.created_at', '>=', $startDate . ' 00:00:00');
        }

        if ($endDate !== '' && self::tableHasColumn('tc_user', 'created_at')) {
            $query->where('u.created_at', '<=', $endDate . ' 23:59:59');
        }

        $allowedOrderFields = ['id', 'points', 'created_at', 'last_login_at'];
        $orderBy = (string) ($params['order_by'] ?? 'id');
        if (!in_array($orderBy, $allowedOrderFields, true) || !self::tableHasColumn('tc_user', $orderBy)) {
            $orderBy = 'id';
        }
        $orderSort = strtoupper((string) ($params['order_sort'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $query->order('u.' . $orderBy, $orderSort);

        $total = (int) (clone $query)->count();
        $list = $query->page($page, $pageSize)->select()->toArray();
        $userIds = array_values(array_unique(array_filter(array_map('intval', array_column($list, 'id')), static fn (int $id): bool => $id > 0)));
        $baziCountMap = self::buildUserRelationCountMap('tc_bazi_record', $userIds);
        $tarotCountMap = self::buildUserRelationCountMap('tc_tarot_record', $userIds);
        $vipStatusMap = self::buildUserVipStatusMap($userIds);

        foreach ($list as &$item) {
            $userId = (int) ($item['id'] ?? 0);
            $vipStatus = $vipStatusMap[$userId] ?? ['is_vip' => 0, 'vip_end_time' => null];
            $rawUsername = trim((string) ($item['username'] ?? ''));
            $rawNickname = trim((string) ($item['nickname'] ?? ''));
            $rawPhone = trim((string) ($item['phone'] ?? ''));
            $displayName = self::resolveDisplayUserName($rawUsername, $rawNickname, $rawPhone, $userId);

            $item['status'] = (int) ($item['status'] ?? 0);
            $item['points'] = (int) ($item['points'] ?? 0);
            $item['bazi_count'] = (int) ($baziCountMap[$userId] ?? 0);
            $item['tarot_count'] = (int) ($tarotCountMap[$userId] ?? 0);
            $item['is_vip'] = (int) ($vipStatus['is_vip'] ?? 0);
            $item['vip_end_time'] = $vipStatus['vip_end_time'] ?? null;
            $item['username'] = $displayName;
            $item['avatar'] = (string) ($item['avatar'] ?? '');
            $item['nickname'] = $rawNickname !== '' && !self::isPhoneLikeUserName($rawNickname, $rawPhone)
                ? $rawNickname
                : $displayName;
            $item['phone'] = $rawPhone;
        }


        unset($item);

        return [
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'pageSize' => $pageSize,
            'list' => $list,
        ];
    }


    /**
     * 调整用户积分
     */
    public static function adjustUserPoints(int $userId, int $points, string $reason, int $adminId): array
    {
        $reason = trim($reason) !== '' ? trim($reason) : '管理员调整';

        Db::startTrans();
        try {
            $user = Db::table('tc_user')
                ->where('id', $userId)
                ->lock(true)
                ->find();

            if (!$user) {
                throw new \Exception('用户不存在');
            }

            $beforePoints = (int) ($user['points'] ?? 0);
            $afterPoints = $beforePoints + $points;
            if ($afterPoints < 0) {
                throw new \Exception('积分不足');
            }

            Db::table('tc_user')
                ->where('id', $userId)
                ->update(['points' => $afterPoints]);

            $recordId = self::insertPointsRecordCompat(
                $userId,
                $points,
                $beforePoints,
                $afterPoints,
                $reason,
                $adminId
            );

            try {
                self::insertAdminLogCompat([
                    'admin_id' => $adminId,
                    'action' => 'adjust_points',
                    'module' => 'points',
                    'target_type' => 'user',
                    'target_id' => $userId,
                    'detail' => "调整用户积分：{$points}，原因：{$reason}",
                    'before_data' => ['points' => $beforePoints],
                    'after_data' => ['points' => $afterPoints],
                    'status' => 1,
                ]);
            } catch (\Throwable $logException) {
                Log::warning('积分调整管理员日志写入失败，已跳过不阻塞主流程', [
                    'user_id' => $userId,
                    'admin_id' => $adminId,
                    'error' => $logException->getMessage(),
                ]);
            }

            Db::commit();


            $notificationSent = false;
            try {
                $notificationSent = \app\controller\Notification::sendPointsChangeNotification($userId, $points, $reason);
            } catch (\Throwable $notifyException) {
                Log::warning('积分调整通知发送失败', [
                    'user_id' => $userId,
                    'admin_id' => $adminId,
                    'error' => $notifyException->getMessage(),
                ]);
            }

            return [
                'user_id' => $userId,
                'before_points' => $beforePoints,
                'after_points' => $afterPoints,
                'change_points' => $points,
                'reason' => $reason,
                'record_id' => $recordId,
                'notification_sent' => $notificationSent,
            ];
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 兼容不同积分记录表结构写入积分变动
     */
    protected static function insertPointsRecordCompat(
        int $userId,
        int $points,
        int $beforePoints,
        int $afterPoints,
        string $reason,
        int $adminId
    ): int {
        $columns = self::getTableColumns('tc_points_record');
        $isIncrease = $points >= 0;
        $payload = [
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($columns['action'])) {
            $payload['action'] = $isIncrease ? '管理员增加' : '管理员扣除';
        }
        if (isset($columns['points'])) {
            $payload['points'] = $points;
        }
        if (isset($columns['type'])) {
            $payload['type'] = $isIncrease ? 'income' : 'consume';
        }
        if (isset($columns['related_id'])) {
            $payload['related_id'] = $adminId;
        }
        if (isset($columns['remark'])) {
            $payload['remark'] = $reason;
        }
        if (isset($columns['description'])) {
            $payload['description'] = $reason;
        }
        if (isset($columns['amount'])) {
            $payload['amount'] = abs($points);
        }
        if (isset($columns['balance'])) {
            $payload['balance'] = $afterPoints;
        }
        if (isset($columns['reason'])) {
            $payload['reason'] = $reason;
        }
        if (isset($columns['source_id'])) {
            $payload['source_id'] = $adminId;
        }
        if (isset($columns['source_type'])) {
            $payload['source_type'] = 'admin_adjust';
        }

        return (int) Db::table('tc_points_record')->insertGetId($payload);
    }

    /**
     * 获取表字段缓存
     */
    protected static function getTableColumns(string $table): array
    {
        if (isset(self::$tableColumnCache[$table])) {
            return self::$tableColumnCache[$table];
        }

        $columns = SchemaInspector::getTableColumns($table);
        self::$tableColumnCache[$table] = $columns;

        return $columns;
    }

    /**
     * 兼容不同后台日志表结构写入管理员日志
     */
    protected static function insertAdminLogCompat(array $payload): void
    {
        $table = self::resolveAdminLogTable();
        if ($table === null) {
            return;
        }

        $columns = self::getTableColumns($table);
        $detail = (string) ($payload['detail'] ?? '');
        $requestUrl = (string) ($payload['request_url'] ?? '');
        $requestMethod = (string) ($payload['request_method'] ?? '');

        $data = [
            'admin_id' => (int) ($payload['admin_id'] ?? 0),
            'admin_name' => (string) ($payload['admin_name'] ?? ''),
            'action' => (string) ($payload['action'] ?? ''),
            'module' => (string) ($payload['module'] ?? ''),
            'target_id' => (int) ($payload['target_id'] ?? 0),
            'target_type' => (string) ($payload['target_type'] ?? ''),
            'ip' => (string) ($payload['ip'] ?? ''),
        ];

        if (isset($columns['detail'])) {
            $data['detail'] = $detail;
        } elseif (isset($columns['content'])) {
            $data['content'] = $detail;
        }

        if (isset($columns['params'])) {
            $data['params'] = self::normalizeParamsLogValue($payload, $detail);
        }


        if (isset($columns['before_data']) && array_key_exists('before_data', $payload)) {
            $data['before_data'] = self::normalizeStructuredLogValue($payload['before_data']);
        }
        if (isset($columns['after_data']) && array_key_exists('after_data', $payload)) {
            $data['after_data'] = self::normalizeStructuredLogValue($payload['after_data']);
        }
        if (isset($columns['request_url'])) {
            $data['request_url'] = $requestUrl;
        } elseif (isset($columns['url'])) {
            $data['url'] = $requestUrl;
        }
        if (isset($columns['request_method'])) {
            $data['request_method'] = $requestMethod;
        } elseif (isset($columns['method'])) {
            $data['method'] = $requestMethod;
        }

        if (isset($columns['status'])) {
            $data['status'] = (int) ($payload['status'] ?? 1);
        }
        if (isset($columns['error_msg'])) {
            $data['error_msg'] = (string) ($payload['error_msg'] ?? '');
        }
        if (isset($columns['user_agent'])) {
            $data['user_agent'] = (string) ($payload['user_agent'] ?? '');
        }
        if (isset($columns['created_at'])) {
            $data['created_at'] = (string) ($payload['created_at'] ?? date('Y-m-d H:i:s'));
        }

        Db::table($table)->insert($data);
    }

    /**
     * 将数组/对象日志字段安全序列化为可落库的字符串
     */
    protected static function normalizeStructuredLogValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $encoded === false ? '{}' : $encoded;
    }

    protected static function normalizeParamsLogValue(array $payload, string $detail): string
    {
        $params = [
            'detail' => $detail,
            'action' => (string) ($payload['action'] ?? ''),
            'module' => (string) ($payload['module'] ?? ''),
            'target_id' => (int) ($payload['target_id'] ?? 0),
            'target_type' => (string) ($payload['target_type'] ?? ''),
        ];

        if (array_key_exists('before_data', $payload)) {
            $params['before_data'] = $payload['before_data'];
        }
        if (array_key_exists('after_data', $payload)) {
            $params['after_data'] = $payload['after_data'];
        }
        if (!empty($payload['request_url'])) {
            $params['request_url'] = (string) $payload['request_url'];
        }
        if (!empty($payload['request_method'])) {
            $params['request_method'] = (string) $payload['request_method'];
        }
        if (array_key_exists('status', $payload)) {
            $params['status'] = (int) $payload['status'];
        }

        $encoded = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $encoded === false ? '{}' : $encoded;
    }

    /**
     * 解析后台日志表名
     */

    protected static function resolveAdminLogTable(): ?string

    {
        foreach (['tc_admin_log', 'admin_log'] as $table) {
            if (self::tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    /**
     * 判断数据表是否存在
     */
    protected static function tableExists(string $table): bool
    {
        return SchemaInspector::tableExists($table);
    }

    /**
     * 获取订单列表
     */
    public static function getOrderList(array $params = []): array
    {
        $orderTable = self::resolveVipOrderTable();
        if ($orderTable === null) {
            return [
                'total' => 0,
                'page' => 1,
                'page_size' => 20,
                'list' => [],
            ];
        }

        $userTable = self::resolveUserTable();
        $query = Db::table($orderTable)->alias('o');
        if ($userTable !== null && self::tableHasColumn($userTable, 'id')) {
            $query->leftJoin($userTable . ' u', 'o.user_id = u.id');
        }

        $fields = ['o.*'];
        $fields[] = $userTable !== null && self::tableHasColumn($userTable, 'nickname') ? 'u.nickname' : "'' as nickname";
        $fields[] = $userTable !== null && self::tableHasColumn($userTable, 'phone') ? 'u.phone' : "'' as phone";
        $query->field($fields);

        if (!empty($params['order_no']) && self::tableHasColumn($orderTable, 'order_no')) {
            $escapedOrderNo = addcslashes((string) $params['order_no'], '%_\\');
            $query->whereLike('o.order_no', '%' . $escapedOrderNo . '%');
        }

        if (!empty($params['user_id']) && self::tableHasColumn($orderTable, 'user_id')) {
            $query->where('o.user_id', (int) $params['user_id']);
        }

        if (isset($params['status']) && $params['status'] !== '' && self::tableHasColumn($orderTable, 'status')) {
            $query->where('o.status', $params['status']);
        }

        if (!empty($params['date_start']) && self::tableHasColumn($orderTable, 'created_at')) {
            $query->where('o.created_at', '>=', $params['date_start']);
        }

        if (!empty($params['date_end']) && self::tableHasColumn($orderTable, 'created_at')) {
            $query->where('o.created_at', '<=', $params['date_end']);
        }

        if (self::tableHasColumn($orderTable, 'created_at')) {
            $query->order('o.created_at', 'DESC');
        } else {
            $query->order('o.id', 'DESC');
        }

        $page = max(1, (int) ($params['page'] ?? 1));
        $pageSize = max(1, min(100, (int) ($params['page_size'] ?? 20)));

        $total = (int) (clone $query)->count();
        $rows = $query->page($page, $pageSize)->select()->toArray();
        $list = array_map([self::class, 'normalizeVipOrderRow'], $rows);

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
        $orderTable = self::resolveVipOrderTable();
        if ($orderTable === null) {
            throw new \Exception('订单表不存在');
        }

        $order = Db::table($orderTable)->where('id', $orderId)->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }

        if (self::normalizeVipOrderStatus($order['status'] ?? 0) !== 1) {
            throw new \Exception('订单未支付，无法退款');
        }

        $payAmount = (float) ($order['pay_amount'] ?? ($order['amount'] ?? 0));
        if ($amount > $payAmount) {
            throw new \Exception('退款金额不能超过支付金额');
        }

        Log::info('后台订单退款开始', [
            'admin_id' => $adminId,
            'order_id' => $orderId,
            'order_no' => self::maskOrderNo((string) ($order['order_no'] ?? '')),
            'amount' => round($amount, 2),
            'pay_amount' => round($payAmount, 2),
        ]);

        Db::startTrans();
        try {
            $refundNo = 'REF' . date('YmdHis') . mt_rand(1000, 9999);
            $refundResult = WechatPayService::refund(
                (string) ($order['order_no'] ?? ''),
                $refundNo,
                $payAmount,
                $amount,
                $reason
            );

            if (!$refundResult['success']) {
                throw new \Exception('微信退款失败: ' . $refundResult['message']);
            }

            $updateData = ['status' => 3];
            $columns = self::getTableColumns($orderTable);
            if (isset($columns['refund_no'])) {
                $updateData['refund_no'] = $refundNo;
            }
            if (isset($columns['refund_amount'])) {
                $updateData['refund_amount'] = $amount;
            }
            if (isset($columns['refund_time'])) {
                $updateData['refund_time'] = date('Y-m-d H:i:s');
            }
            if (isset($columns['refund_reason'])) {
                $updateData['refund_reason'] = $reason;
            }

            Db::table($orderTable)
                ->where('id', $orderId)
                ->update($updateData);

            self::insertAdminLogCompat([
                'admin_id' => $adminId,
                'action' => 'refund_order',
                'module' => 'order',
                'target_type' => 'order',
                'target_id' => $orderId,
                'detail' => "订单退款成功: 金额{$amount}, 退款单号: {$refundNo}, 原因: {$reason}",
                'after_data' => [
                    'refund_no' => $refundNo,
                    'refund_amount' => $amount,
                    'refund_reason' => $reason,
                ],
                'status' => 1,
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
