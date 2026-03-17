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
     * 获取趋势统计（兼容旧后台结构）
     */
    public static function getTrendStats(int $days = 30): array
    {
        $days = max(1, min(90, $days));
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        $trend = Db::table('site_daily_stats')
            ->whereBetween('stat_date', [$startDate, $endDate])
            ->order('stat_date', 'ASC')
            ->column('*', 'stat_date');

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

            $dayStats = $trend[$date] ?? [];
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
            'points_balance' => (int) (Db::table('tc_user')->sum('points') ?? 0),
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

        $query = Db::table('tc_user')->alias('u')
            ->leftJoin('tc_user_vip uv', 'u.id = uv.user_id AND uv.status = 1 AND uv.end_time > NOW()')
            ->field([
                'u.id',
                'u.nickname',
                'u.avatar',
                'u.phone',
                'u.points',
                'u.created_at',
                'u.last_login_at',
                'u.status',
                'CASE WHEN uv.id IS NOT NULL THEN 1 ELSE 0 END as is_vip',
                'uv.end_time as vip_end_time',
                '(SELECT COUNT(*) FROM tc_bazi_record br WHERE br.user_id = u.id) as bazi_count',
                '(SELECT COUNT(*) FROM tc_tarot_record tr WHERE tr.user_id = u.id) as tarot_count',
            ]);

        if ($username !== '') {
            $escapedUsername = addcslashes($username, '%_\\');
            $query->whereLike('u.nickname', '%' . $escapedUsername . '%');
        } elseif ($keyword !== '') {
            $escapedKeyword = addcslashes($keyword, '%_\\');
            $query->where(function ($q) use ($escapedKeyword) {
                $q->whereLike('u.nickname', '%' . $escapedKeyword . '%')
                    ->whereOrLike('u.phone', '%' . $escapedKeyword . '%');
            });
        }

        if ($phone !== '') {
            $escapedPhone = addcslashes($phone, '%_\\');
            $query->whereLike('u.phone', '%' . $escapedPhone . '%');
        }

        if ($status !== null && $status !== '') {
            $query->where('u.status', (int) $status);
        }

        if ($isVip !== null && $isVip !== '' && (int) $isVip === 1) {
            $query->whereNotNull('uv.id');
        }

        if ($startDate !== '') {
            $query->where('u.created_at', '>=', $startDate . ' 00:00:00');
        }

        if ($endDate !== '') {
            $query->where('u.created_at', '<=', $endDate . ' 23:59:59');
        }

        $allowedOrderFields = ['id', 'points', 'created_at', 'last_login_at'];
        $orderBy = (string) ($params['order_by'] ?? 'id');
        if (!in_array($orderBy, $allowedOrderFields, true)) {
            $orderBy = 'id';
        }
        $orderSort = strtoupper((string) ($params['order_sort'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $query->order('u.' . $orderBy, $orderSort);

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        foreach ($list as &$item) {
            $item['status'] = (int) ($item['status'] ?? 0);
            $item['points'] = (int) ($item['points'] ?? 0);
            $item['bazi_count'] = (int) ($item['bazi_count'] ?? 0);
            $item['tarot_count'] = (int) ($item['tarot_count'] ?? 0);
            $item['is_vip'] = (int) ($item['is_vip'] ?? 0);
            $item['username'] = (string) (($item['phone'] ?? '') ?: ('用户#' . (int) ($item['id'] ?? 0)));
            $item['avatar'] = (string) ($item['avatar'] ?? '');
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

        $columns = [];
        foreach (Db::query('SHOW COLUMNS FROM `' . $table . '`') as $column) {
            $field = (string) ($column['Field'] ?? '');
            if ($field !== '') {
                $columns[$field] = true;
            }
        }

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
        } elseif (isset($columns['params'])) {
            $data['params'] = $detail;
        }

        if (isset($columns['before_data']) && array_key_exists('before_data', $payload)) {
            $data['before_data'] = $payload['before_data'];
        }
        if (isset($columns['after_data']) && array_key_exists('after_data', $payload)) {
            $data['after_data'] = $payload['after_data'];
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
        $escapedTable = addslashes($table);
        return !empty(Db::query("SHOW TABLES LIKE '{$escapedTable}'"));
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
