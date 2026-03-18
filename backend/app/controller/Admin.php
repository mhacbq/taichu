<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\User;
use app\model\BaziRecord;
use app\model\PointsRecord;
use app\model\Feedback;
use app\model\AdminLog;
use app\model\DailyFortune;
use app\model\TarotRecord;
use app\service\AdminAuthService;
use app\service\AdminStatsService;
use app\service\ConfigService;
use app\service\SchemaInspector;


use think\Request;
use think\facade\Log;
use think\facade\Db;


/**
 * 后台管理控制器
 */
class Admin extends BaseController
{
    /**
     * 当前管理员ID
     */
    protected int $adminId = 0;
    
    /**
     * 当前管理员名称
     */
    protected string $adminName = '';
    
    /**
     * 默认分页大小
     */
    protected const DEFAULT_PAGE_SIZE = 20;
    
    /**
     * 最大分页大小
     */
    protected const MAX_PAGE_SIZE = 100;
    protected const CATEGORY_POINTS = 'points';
    protected const CATEGORY_POINTS_COST = 'points_cost';
    protected const CATEGORY_SENSITIVE_WORDS = 'sensitive_words';
    protected const CATEGORY_SYSTEM_NOTICE = 'system_notice';
    protected const SHENSHA_TYPES = ['daji', 'ji', 'ping', 'xiong', 'daxiong'];
    protected const SHENSHA_CATEGORIES = ['guiren', 'xueye', 'ganqing', 'jiankang', 'caiyun', 'qita'];
    
    /**
     * 初始化
     */

    public function initialize()
    {
        parent::initialize();
        
        // 从JWT token中获取管理员信息（后台管理使用adminUser）
        $adminUser = $this->request->adminUser ?? [];
        $this->adminId = $adminUser['id'] ?? 0;
        $this->adminName = $adminUser['username'] ?? 'Unknown';
    }
    
    /**
     * 检查权限
     */
    protected function checkPermission(string $permissionCode): bool
    {
        return AdminAuthService::checkPermission($this->adminId, $permissionCode);
    }
    


    /**
     * 获取仪表盘统计数据
     */
    public function dashboard()
    {
        // 检查权限
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限访问统计数据', 403);
        }
        
        try {
            $statistics = [
                'total_users' => User::count(),
                'today_users' => User::where('created_at', '>=', date('Y-m-d'))->count(),
                'total_bazi' => BaziRecord::count(),
                'today_bazi' => BaziRecord::where('created_at', '>=', date('Y-m-d'))->count(),
                'total_tarot' => TarotRecord::count(),
                'today_tarot' => TarotRecord::where('created_at', '>=', date('Y-m-d'))->count(),
            ];

            // 用户增长趋势（最近7天）
            $userTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $userTrend[] = [
                    'date' => $date,
                    'new_users' => User::whereLike('created_at', "$date%")->count(),
                    'active_users' => User::whereLike('last_login_at', "$date%")->count()
                ];
            }

            // 功能使用分布
            $featureStats = [
                ['name' => '八字排盘', 'value' => BaziRecord::count()],
                ['name' => '塔罗占卜', 'value' => TarotRecord::count()],
                ['name' => '每日运势', 'value' => DailyFortune::count()],
                ['name' => '积分兑换', 'value' => PointsRecord::where('type', 'reduce')->count()]
            ];

            // 记录查看日志
            $this->logOperation('view', 'stats', [
                'detail' => '查看仪表盘统计数据',
            ]);

            return $this->success([
                'statistics' => $statistics,
                'user_trend' => $userTrend,
                'feature_stats' => $featureStats
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('仪表盘数据获取失败: ' . $e->getMessage());
            return $this->error('获取统计数据失败，请稍后重试', 500);
        }
    }

    /**
     * 获取Dashboard趋势数据
     */
    public function dashboardTrend()
    {
        // 检查权限
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限访问统计数据', 403);
        }

        try {
            // 获取最近7天的用户注册趋势
            $userTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $count = User::where('created_at', '>=', $date . ' 00:00:00')
                    ->where('created_at', '<=', $date . ' 23:59:59')
                    ->count();
                $userTrend[] = [
                    'date' => $date,
                    'count' => $count
                ];
            }

            // 获取最近7天的功能使用趋势
            $baziTrend = [];
            $tarotTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $baziCount = BaziRecord::where('created_at', '>=', $date . ' 00:00:00')
                    ->where('created_at', '<=', $date . ' 23:59:59')
                    ->count();
                $tarotCount = TarotRecord::where('created_at', '>=', $date . ' 00:00:00')
                    ->where('created_at', '<=', $date . ' 23:59:59')
                    ->count();
                $baziTrend[] = ['date' => $date, 'count' => $baziCount];
                $tarotTrend[] = ['date' => $date, 'count' => $tarotCount];
            }

            return $this->success([
                'user_trend' => $userTrend,
                'bazi_trend' => $baziTrend,
                'tarot_trend' => $tarotTrend
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取趋势数据失败: ' . $e->getMessage());
            return $this->error('获取趋势数据失败', 500);
        }
    }

    /**
     * 获取图表数据
     */
    public function chartData($type)
    {
        // 检查权限
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限访问统计数据', 403);
        }

        try {
            switch ($type) {
                case 'user_source':
                    // 用户来源分布
                    $data = [
                        ['name' => '直接访问', 'value' => User::where('source', 'direct')->count()],
                        ['name' => '搜索引擎', 'value' => User::where('source', 'search')->count()],
                        ['name' => '社交媒体', 'value' => User::where('source', 'social')->count()],
                        ['name' => '邀请注册', 'value' => User::where('source', 'invite')->count()],
                        ['name' => '其他', 'value' => User::where('source', 'other')->count()],
                    ];
                    break;

                case 'feature_usage':
                    // 功能使用分布
                    $data = [
                        ['name' => '八字排盘', 'value' => BaziRecord::count()],
                        ['name' => '塔罗占卜', 'value' => TarotRecord::count()],
                        ['name' => '每日运势', 'value' => DailyFortune::count()],
                    ];
                    break;

                case 'user_status':
                    // 用户状态分布
                    $data = [
                        ['name' => '正常', 'value' => User::where('status', 1)->count()],
                        ['name' => '禁用', 'value' => User::where('status', 0)->count()],
                    ];
                    break;

                default:
                    return $this->error('未知的图表类型', 400);
            }

            return $this->success($data, '获取成功');
        } catch (\Exception $e) {
            Log::error('获取图表数据失败: ' . $e->getMessage());
            return $this->error('获取图表数据失败', 500);
        }
    }

    /**
     * 获取用户列表
     */
    public function users(Request $request)
    {
        // 检查权限
        if (!$this->checkPermission('user_view')) {
            return $this->error('无权限查看用户列表', 403);
        }
        
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', self::DEFAULT_PAGE_SIZE);
            $username = $request->get('username', '');
            $phone = $request->get('phone', '');
            $status = $request->get('status', '');

            // 验证分页参数
            $page = filter_var($page, FILTER_VALIDATE_INT) ?: 1;
            $pageSize = filter_var($pageSize, FILTER_VALIDATE_INT) ?: self::DEFAULT_PAGE_SIZE;
            $page = max(1, $page);
            $pageSize = max(1, min(self::MAX_PAGE_SIZE, $pageSize)); // 限制最大分页数

            $query = User::order('id', 'desc');

            if ($username) {
                // 使用参数绑定防止SQL注入
                $username = preg_replace('/[%_\\\\]/', '', $username);
                $query->whereLike('username|nickname', '%' . $username . '%');
            }
            if ($phone) {
                // 使用参数绑定防止SQL注入
                $phone = preg_replace('/[%_\\\\]/', '', $phone);
                $query->whereLike('phone', '%' . $phone . '%');
            }
            if ($status !== '') {
                $query->where('status', $status);
            }

            $total = $query->count();
            $list = $query->page($page, $pageSize)->select();

            // 记录查看日志
            $this->logOperation('view', 'user', [
                'detail' => '查看用户列表',
            ]);

            return $this->success([
                'list' => $list,
                'total' => $total
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取用户列表失败: ' . $e->getMessage());
            return $this->error('获取用户列表失败，请稍后重试', 500);
        }
    }

    /**
     * 获取用户详情
     */
    public function userDetail($id)
    {
        // 检查权限
        if (!$this->checkPermission('user_view')) {
            return $this->error('无权限查看用户信息', 403);
        }
        
        try {
            $user = User::find($id);
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            // 添加统计数据
            $user['bazi_count'] = BaziRecord::where('user_id', $id)->count();
            $user['tarot_count'] = TarotRecord::where('user_id', $id)->count();

            // 记录查看日志
            $this->logOperation('view', 'user', [
                'target_id' => $id,
                'target_type' => 'user',
                'detail' => '查看用户详情',
            ]);

            return $this->success($user, '获取成功');
        } catch (\Exception $e) {
            Log::error('获取用户详情失败: ' . $e->getMessage());
            return $this->error('获取用户详情失败，请稍后重试', 500);
        }
    }

    /**
     * 更新用户状态
     */
    public function updateUserStatus(Request $request, $id)
    {
        // 检查权限
        if (!$this->checkPermission('user_edit')) {
            return $this->error('无权限编辑用户', 403);
        }
        
        try {
            $status = $request->put('status');
            
            // 验证状态参数有效性
            if (!in_array($status, [0, 1, 2], true)) {
                return $this->error('状态值无效，必须是0(禁用)、1(正常)或2(待验证)', 400);
            }
            
            $user = User::find($id);
            
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            $oldStatus = $user->status;
            $user->status = $status;
            $user->save();

            // 记录操作日志
            $this->logOperation('update', 'user', [
                'target_id' => $id,
                'target_type' => 'user',
                'detail' => '更新用户状态为: ' . $status,
                'before_data' => ['status' => $oldStatus],
                'after_data' => ['status' => $status],
            ]);

            return $this->success(null, '操作成功');
        } catch (\Exception $e) {
            Log::error('更新用户状态失败: ' . $e->getMessage());
            return $this->error('操作失败，请稍后重试', 500);
        }
    }

    /**
     * 批量更新用户状态
     */
    public function batchUpdateUserStatus(Request $request)
    {
        if (!$this->checkPermission('user_edit')) {
            return $this->error('无权限批量编辑用户', 403);
        }

        try {
            $ids = $request->put('ids', []);
            $status = $request->put('status');

            if (!is_array($ids) || empty($ids)) {
                return $this->error('用户ID列表不能为空', 400);
            }
            if (!in_array($status, [0, 1, 2], true)) {
                return $this->error('状态值无效，必须是0(禁用)、1(正常)或2(待验证)', 400);
            }

            $userIds = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $value): bool => $value > 0)));
            if (empty($userIds)) {
                return $this->error('用户ID列表无效', 400);
            }

            $existingCount = User::whereIn('id', $userIds)->count();
            if ($existingCount === 0) {
                return $this->error('未找到可更新的用户', 404);
            }

            User::whereIn('id', $userIds)->update(['status' => $status]);

            $this->logOperation('batch_update_status', 'user', [
                'detail' => '批量更新用户状态为: ' . $status,
                'after_data' => [
                    'user_ids' => $userIds,
                    'status' => $status,
                    'matched_count' => $existingCount,
                ],
            ]);

            return $this->success([
                'updated_count' => $existingCount,
                'status' => $status,
                'user_ids' => $userIds,
            ], '批量操作成功');
        } catch (\Throwable $e) {
            Log::error('批量更新用户状态失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
            ]);
            return $this->error('批量操作失败，请稍后重试', 500);
        }
    }


    /**
     * 获取八字记录列表
     */
    public function baziRecords(Request $request)
    {
        // 检查权限
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限查看内容', 403);
        }
        
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', self::DEFAULT_PAGE_SIZE);
            $userId = $request->get('user_id', '');

            // 验证分页参数
            $page = max(1, intval($page));
            $pageSize = max(1, min(self::MAX_PAGE_SIZE, intval($pageSize)));

            $query = BaziRecord::order('id', 'desc');
            
            if ($userId) {
                $query->where('user_id', $userId);
            }

            $total = $query->count();
            $list = $query->page($page, $pageSize)->select();

            return $this->success([
                'list' => $list,
                'total' => $total
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取八字记录失败: ' . $e->getMessage());
            return $this->error('获取记录失败，请稍后重试', 500);
        }
    }

    /**
     * 删除八字记录
     */
    public function deleteBaziRecord($id)
    {
        // 检查权限
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限删除内容', 403);
        }
        
        try {
            $record = BaziRecord::find($id);
            if (!$record) {
                return $this->error('记录不存在', 404);
            }

            // 记录操作前的数据
            $beforeData = $record->toArray();
            
            BaziRecord::destroy($id);
            
            // 记录删除日志
            $this->logOperation('delete', 'content', [
                'target_id' => $id,
                'target_type' => 'bazi_record',
                'detail' => '删除八字记录',
                'before_data' => $beforeData,
            ]);

            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            Log::error('删除八字记录失败: ' . $e->getMessage());
            return $this->error('删除失败，请稍后重试', 500);
        }
    }

    /**
     * 获取塔罗记录列表
     */
    public function tarotRecords(Request $request)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限查看内容', 403);
        }
        
        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $userId = $request->get('user_id', '');

            $query = TarotRecord::order('id', 'desc');

            if ($userId) {
                $query->where('user_id', $userId);
            }

            $total = $query->count();
            $list = $query->page($page, $pageSize)->select();

            return $this->success([

                'list' => $list,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            Log::error('获取塔罗记录失败: ' . $e->getMessage());
            return $this->error('获取记录失败', 500);
        }
    }

    /**
     * 获取塔罗详情
     */
    public function tarotDetail($id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限查看内容', 403);
        }
        
        try {
            $record = TarotRecord::find($id);
            if (!$record) {
                return $this->error('记录不存在', 404);
            }
            return $this->success($record);
        } catch (\Exception $e) {
            return $this->error('获取详情失败', 500);
        }
    }

    /**
     * 删除塔罗记录
     */
    public function deleteTarotRecord($id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限删除内容', 403);
        }
        
        try {
            $record = TarotRecord::find($id);
            if (!$record) {
                return $this->error('记录不存在', 404);
            }
            
            $beforeData = $record->toArray();
            TarotRecord::destroy($id);
            
            $this->logOperation('delete', 'content', [
                'target_id' => $id,
                'target_type' => 'tarot_record',
                'detail' => '删除塔罗记录',
                'before_data' => $beforeData,
            ]);

            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败', 500);
        }
    }

    /**
     * 每日运势列表
     */
    public function dailyFortuneList(Request $request)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限查看内容', 403);
        }
        
        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $date = $request->get('date', '');

            $query = DailyFortune::order('date', 'desc');

            if ($date) {
                $query->where('date', $date);
            }

            $total = $query->count();
            $list = $query->page($page, $pageSize)->select();

            return $this->success([

                'list' => $list,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            return $this->error('获取运势列表失败', 500);
        }
    }

    /**
     * 创建每日运势
     */
    public function createDailyFortune(Request $request)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限编辑内容', 403);
        }

        $data = [];
        try {
            $data = $request->post();
            $fortune = DailyFortune::create($data);

            $this->logOperation('create', 'content', [
                'target_id' => $fortune->id,
                'target_type' => 'daily_fortune',
                'detail' => '新增每日运势: ' . ($data['date'] ?? ''),
                'after_data' => $data
            ]);

            return $this->success($fortune, '创建成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_create_daily_fortune', $e, '创建失败，请稍后重试', [
                'date' => $data['date'] ?? '',
            ]);
        }
    }

    /**
     * 更新每日运势
     */
    public function updateDailyFortune(Request $request, $id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限编辑内容', 403);
        }
        
        try {
            $data = $request->put();
            $fortune = DailyFortune::find($id);
            if (!$fortune) {
                return $this->error('记录不存在', 404);
            }
            
            $beforeData = $fortune->toArray();
            $fortune->save($data);
            
            $this->logOperation('update', 'content', [
                'target_id' => $id,
                'target_type' => 'daily_fortune',
                'detail' => '更新每日运势: ' . $fortune->date,
                'before_data' => $beforeData,
                'after_data' => $data
            ]);

            return $this->success(null, '更新成功');
        } catch (\Exception $e) {
            return $this->error('更新失败', 500);
        }
    }

    /**
     * 删除每日运势
     */
    public function deleteDailyFortune($id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限删除内容', 403);
        }
        
        try {
            $fortune = DailyFortune::find($id);
            if (!$fortune) {
                return $this->error('记录不存在', 404);
            }
            
            $beforeData = $fortune->toArray();
            DailyFortune::destroy($id);
            
            $this->logOperation('delete', 'content', [
                'target_id' => $id,
                'target_type' => 'daily_fortune',
                'detail' => '删除每日运势: ' . $fortune->date,
                'before_data' => $beforeData,
            ]);

            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败', 500);
        }
    }

    /**
     * 获取积分记录
     */
    public function pointsRecords(Request $request)
    {
        // 检查权限
        if (!$this->checkPermission('points_view')) {
            return $this->error('无权限查看积分记录', 403);
        }
        
        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $userId = $request->get('user_id', '');
            $type = $request->get('type', '');


            $query = PointsRecord::order('id', 'desc');
            
            if ($userId) {
                $query->where('user_id', $userId);
            }
            if ($type) {
                $query->where('type', $type);
            }

            $total = $query->count();
            $list = $query->page($page, $pageSize)->select();

            return $this->success([
                'list' => $list,
                'total' => $total
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取积分记录失败: ' . $e->getMessage());
            return $this->error('获取积分记录失败，请稍后重试', 500);
        }
    }

    protected function applyPointsRecordDirectionFilter($query, string $type, array $columns): void
    {
        $normalized = strtolower(trim($type));
        $isReduce = in_array($normalized, ['reduce', 'sub', 'subtract', 'minus', 'consume'], true);

        if (isset($columns['points'])) {
            $query->where('points', $isReduce ? '<' : '>', 0);
            return;
        }

        if (isset($columns['type'])) {
            $query->whereIn('type', $isReduce
                ? ['reduce', 'sub', 'subtract', 'minus', 'consume']
                : ['add', 'income', 'increase', 'recharge', 'register', 'reward']);
        }
    }

    protected function normalizePointsRecordRow(array $row, array $columns): array
    {
        $rawAmount = isset($row['amount']) ? (int) $row['amount'] : null;
        $delta = isset($row['points']) ? (int) $row['points'] : ($rawAmount ?? 0);
        $type = $this->normalizePointsRecordType($row, $delta);
        if ($rawAmount === null) {
            $rawAmount = abs($delta);
        }

        $reason = trim((string) (
            $row['reason']
            ?? $row['remark']
            ?? $row['description']
            ?? $row['action']
            ?? $row['type']
            ?? '积分变动'
        ));

        $balance = array_key_exists('balance', $row) && $row['balance'] !== null && $row['balance'] !== ''
            ? (int) $row['balance']
            : $this->estimatePointsRecordBalance($row, $columns);

        return array_merge($row, [
            'type' => $type,
            'amount' => abs((int) $rawAmount),
            'balance' => $balance,
            'reason' => $reason !== '' ? $reason : '积分变动',
            'created_at' => (string) ($row['created_at'] ?? ''),
        ]);
    }

    protected function normalizePointsRecordType(array $row, int $delta): string
    {
        if ($delta < 0) {
            return 'reduce';
        }
        if ($delta > 0) {
            return 'add';
        }

        $normalized = strtolower(trim((string) ($row['type'] ?? '')));
        return in_array($normalized, ['reduce', 'sub', 'subtract', 'minus', 'consume'], true) ? 'reduce' : 'add';
    }

    protected function estimatePointsRecordBalance(array $row, array $columns): ?int
    {
        if (!isset($row['user_id']) || !isset($columns['user_id']) || !isset($columns['points']) || !SchemaInspector::tableExists('tc_user')) {
            return null;
        }

        $userId = (int) ($row['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $currentPoints = Db::table('tc_user')->where('id', $userId)->value('points');
        if ($currentPoints === null) {
            return null;
        }

        $laterQuery = Db::table('tc_points_record')->where('user_id', $userId);
        $createdAt = trim((string) ($row['created_at'] ?? ''));
        $recordId = (int) ($row['id'] ?? 0);

        if ($createdAt !== '') {
            $laterQuery->where(function ($query) use ($createdAt, $recordId) {
                $query->where('created_at', '>', $createdAt);
                if ($recordId > 0) {
                    $query->whereOr(function ($orQuery) use ($createdAt, $recordId) {
                        $orQuery->where('created_at', '=', $createdAt)->where('id', '>', $recordId);
                    });
                }
            });
        } elseif ($recordId > 0) {
            $laterQuery->where('id', '>', $recordId);
        }

        $laterDelta = (int) ($laterQuery->sum('points') ?? 0);
        return (int) $currentPoints - $laterDelta;
    }

    /**
     * 调整用户积分
     */
    public function adjustPoints(Request $request)
    {

        // 检查权限
        if (!$this->checkPermission('points_adjust')) {
            return $this->error('无权限调整积分', 403);
        }
        
        // 参数验证
        $data = $request->post();
        
        // 手动验证参数
        if (empty($data['user_id']) || !is_numeric($data['user_id']) || $data['user_id'] <= 0) {
            return $this->error('用户ID必须是正整数', 400);
        }
        
        if (empty($data['type']) || !in_array($data['type'], ['add', 'sub'])) {
            return $this->error('调整类型必须是add或sub', 400);
        }
        
        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0 || $data['amount'] >= 100000) {
            return $this->error('积分数量必须是1-99999之间的整数', 400);
        }
        
        if (empty($data['reason']) || !is_string($data['reason']) || mb_strlen($data['reason']) > 200) {
            return $this->error('调整原因不能为空且不能超过200字符', 400);
        }
        
        try {
            $userId = $request->post('user_id');
            $type = $request->post('type');
            $amount = (int) $request->post('amount');
            $reason = $request->post('reason', '管理员调整');

            $user = User::find($userId);
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            // 记录调整前的积分
            $beforePoints = $user->points;

            // 调整积分
            if ($type === 'add') {
                $user->addPointsAtomic($amount);
            } else {
                $result = $user->deductPoints($amount);
                if (!$result) {
                    return $this->error('用户积分不足', 400);
                }
            }

            // 刷新用户数据获取最新积分
            $user->refresh();

            // 记录积分变动
            PointsRecord::create([
                'user_id' => $userId,
                'type' => $type === 'add' ? 'add' : 'reduce',
                'amount' => $amount,
                'balance' => $user->points,
                'reason' => $reason,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // 记录操作日志
            $this->logOperation('adjust_points', 'points', [
                'target_id' => $userId,
                'target_type' => 'user',
                'detail' => "{$type}积分 {$amount}, 原因: {$reason}",
                'before_data' => ['points' => $beforePoints],
                'after_data' => ['points' => $user->points],
            ]);

            return $this->success(null, '调整成功');
        } catch (\Exception $e) {
            Log::error('调整积分失败: ' . $e->getMessage());
            
            // 记录失败日志
            $this->logOperation('adjust_points', 'points', [
                'target_id' => $request->post('user_id', 0),
                'target_type' => 'user',
                'detail' => '调整积分失败',
                'status' => 0,
                'error_msg' => $e->getMessage(),
            ]);
            
            return $this->error('调整失败，请稍后重试', 500);
        }
    }

    /**
     * 获取积分规则
     */
    public function getPointsRules()
    {
        if (!$this->checkPermission('points_view')) {
            return $this->error('无权限查看积分规则', 403);
        }

        try {
            $rules = Db::name('system_config')
                ->whereIn('category', [self::CATEGORY_POINTS, self::CATEGORY_POINTS_COST])
                ->order('category', 'asc')
                ->order('sort_order', 'asc')
                ->select()
                ->toArray();

            $list = array_map(function (array $rule): array {
                $points = (int) $rule['config_value'];
                if ($rule['category'] === self::CATEGORY_POINTS_COST) {
                    $points = -abs($points);
                }

                return [
                    'id' => $rule['id'],
                    'name' => $rule['description'] ?: $rule['config_key'],
                    'code' => $rule['config_key'],
                    'points' => $points,
                    'description' => $rule['description'] ?: '',
                    'status' => (int) $rule['is_editable'],
                    'category' => $rule['category'],
                    'sort_order' => (int) $rule['sort_order'],
                    'updated_at' => $rule['updated_at'],
                ];
            }, $rules);

            return $this->success([
                'list' => $list,
                'total' => count($list),
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取积分规则失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
            ]);
            return $this->error('获取积分规则失败，请稍后重试', 500);
        }
    }

    /**
     * 保存积分规则
     */
    public function savePointsRules(Request $request)
    {
        if (!$this->checkPermission('points_adjust')) {
            return $this->error('无权限修改积分规则', 403);
        }

        $data = $request->put();
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $points = filter_var($data['points'] ?? null, FILTER_VALIDATE_INT);
        $status = isset($data['status']) ? (int) $data['status'] : 1;
        $sortOrder = isset($data['sort_order']) ? max(0, (int) $data['sort_order']) : 0;

        if ($name === '' || $code === '' || $points === false) {
            return $this->error('规则名称、标识和积分值不能为空', 400);
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $code)) {
            return $this->error('规则标识只能包含字母、数字和下划线', 400);
        }

        if (!in_array($status, [0, 1], true)) {
            return $this->error('状态值无效', 400);
        }

        $category = $points < 0 ? self::CATEGORY_POINTS_COST : self::CATEGORY_POINTS;
        $storedPoints = abs((int) $points);

        try {
            $existing = null;
            if ($id > 0) {
                $existing = Db::name('system_config')->where('id', $id)->find();
                if (!$existing) {
                    return $this->error('积分规则不存在', 404);
                }
            }

            $duplicateQuery = Db::name('system_config')->where('config_key', $code);
            if ($id > 0) {
                $duplicateQuery->where('id', '<>', $id);
            }
            if ($duplicateQuery->find()) {
                return $this->error('规则标识已存在', 400);
            }

            $payload = [
                'config_key' => $code,
                'config_value' => (string) $storedPoints,
                'config_type' => 'int',
                'description' => $description !== '' ? $description : $name,
                'category' => $category,
                'is_editable' => $status,
                'sort_order' => $sortOrder,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($id > 0) {
                Db::name('system_config')->where('id', $id)->update($payload);
            } else {
                $payload['created_at'] = date('Y-m-d H:i:s');
                $id = (int) Db::name('system_config')->insertGetId($payload);
            }

            $after = [
                'id' => $id,
                'name' => $name,
                'code' => $code,
                'points' => $points,
                'description' => $description !== '' ? $description : $name,
                'status' => $status,
                'category' => $category,
                'sort_order' => $sortOrder,
            ];

            $this->logOperation('save_points_rule', 'points', [
                'target_id' => $id,
                'target_type' => 'points_rule',
                'detail' => ($existing ? '更新' : '新增') . '积分规则: ' . $code,
                'before_data' => $existing ?: [],
                'after_data' => $after,
            ]);

            return $this->success($after, '保存成功');
        } catch (\Exception $e) {
            Log::error('保存积分规则失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'code' => $code,
            ]);
            return $this->error('保存积分规则失败，请稍后重试', 500);
        }
    }

    /**
     * 获取反馈列表
     */
    public function feedbackList(Request $request)

    {
        // 检查权限
        if (!$this->checkPermission('feedback_view')) {
            return $this->error('无权限查看反馈列表', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $type = $request->get('type', '');
            $status = $request->get('status', '');


            $query = Feedback::order('id', 'desc');
            
            if ($type) {
                $query->where('type', $type);
            }
            if ($status) {
                $query->where('status', $status);
            }

            $total = $query->count();
            $list = $query->page($page, $pageSize)->select();

            return $this->success([
                'list' => $list,
                'total' => $total
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取反馈列表失败: ' . $e->getMessage());
            return $this->error('获取反馈列表失败，请稍后重试', 500);
        }
    }

    /**
     * 回复反馈
     */
    public function replyFeedback(Request $request, $id)
    {
        // 检查权限
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限回复反馈', 403);
        }
        
        try {
            $reply = $request->post('reply');
            $status = $request->post('status', 'resolved');

            $feedback = Feedback::find($id);
            if (!$feedback) {
                return $this->error('反馈不存在', 404);
            }

            $oldStatus = $feedback->status;
            $feedback->reply = $reply;
            $feedback->status = $status;
            $feedback->replied_at = date('Y-m-d H:i:s');
            $feedback->save();

            // 记录操作日志
            $this->logOperation('update', 'feedback', [
                'target_id' => $id,
                'target_type' => 'feedback',
                'detail' => '回复用户反馈',
                'before_data' => ['status' => $oldStatus, 'reply' => ''],
                'after_data' => ['status' => $status, 'reply' => $reply],
            ]);

            return $this->success(null, '回复成功');
        } catch (\Exception $e) {
            Log::error('回复反馈失败: ' . $e->getMessage());
            return $this->error('回复失败，请稍后重试', 500);
        }
    }

    /**
     * 获取系统设置
     */
    public function getSettings()
    {
        // 检查权限
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限查看系统设置', 403);
        }
        
        try {
            $settings = $this->buildSystemSettingsResponse();

            // 记录查看日志
            $this->logOperation('view', 'config', [
                'detail' => '查看系统设置',
            ]);

            return $this->success($settings, '获取成功');
        } catch (\Exception $e) {
            Log::error('获取系统设置失败: ' . $e->getMessage());
            return $this->error('获取设置失败，请稍后重试', 500);
        }
    }


    /**
     * 保存系统设置
     */
    public function saveSettings(Request $request)
    {
        // 检查权限
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限修改系统设置', 403);
        }
        
        $configKeys = [];
        try {
            $settings = $request->isPut() ? $request->put() : $request->post();

            if (empty($settings) || !is_array($settings)) {
                return $this->error('设置数据不能为空', 400);
            }

            $normalizedSettings = $this->normalizeSystemSettingsInput($settings);
            if (empty($normalizedSettings)) {
                return $this->error('没有可保存的系统设置项', 400);
            }

            // 获取操作前的设置用于日志记录
            $configKeys = array_keys($normalizedSettings);
            $oldSettings = Db::name('system_config')
                ->whereIn('config_key', $configKeys)
                ->column('config_value', 'config_key');

            // 批量更新配置
            $updateCount = 0;
            foreach ($normalizedSettings as $key => $value) {
                // 验证配置键名格式
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                    continue;
                }

                // 获取配置类型
                $config = Db::name('system_config')
                    ->where('config_key', $key)
                    ->find();

                if ($config) {
                    // 根据类型处理值
                    $processedValue = $this->processConfigValue($value, $config['config_type']);

                    Db::name('system_config')
                        ->where('config_key', $key)
                        ->update([
                            'config_value' => $processedValue,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    $updateCount++;
                } else {
                    $configType = is_bool($value) ? 'bool' : (is_int($value) ? 'int' : (is_float($value) ? 'float' : (is_array($value) ? 'json' : 'string')));
                    // 新增配置项时按归一化后的真实类型落库
                    Db::name('system_config')->insert([
                        'config_key' => $key,
                        'config_value' => $this->processConfigValue($value, $configType),
                        'config_type' => $configType,
                        'category' => 'custom',
                        'is_editable' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $updateCount++;
                }
            }

            ConfigService::clearCache();
            $latestSettings = $this->buildSystemSettingsResponse();

            // 记录操作日志
            $this->logOperation('update', 'config', [
                'detail' => '更新系统设置',
                'before_data' => $oldSettings,
                'after_data' => $latestSettings,
            ]);

            return $this->success([
                'updated_count' => $updateCount,
                'settings' => $latestSettings,
            ], '保存成功');

        } catch (\Exception $e) {
            return $this->respondSystemException('admin_save_settings', $e, '保存失败，请稍后重试', [
                'config_keys' => $configKeys,
                'settings_count' => count($configKeys),
            ]);
        }
    }

    
    /**
     * 获取敏感词列表
     */
    public function getSensitiveWords(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限查看敏感词', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $keyword = trim((string) $request->get('keyword', ''));

            $query = Db::name('system_config')
                ->where('category', self::CATEGORY_SENSITIVE_WORDS)
                ->order('created_at', 'desc');

            if ($keyword !== '') {
                $query->whereLike('description', '%' . addcslashes($keyword, '%_\\') . '%');
            }

            $total = $query->count();
            $rows = $query->page($page, $pageSize)->select()->toArray();
            $allRows = Db::name('system_config')
                ->where('category', self::CATEGORY_SENSITIVE_WORDS)
                ->select()
                ->toArray();

            $stats = ['illegal' => 0, 'sensitive' => 0];
            foreach ($allRows as $row) {
                $item = $this->decodeConfigJson($row['config_value']);
                $type = $item['type'] ?? 'sensitive';
                if ($type === 'illegal') {
                    $stats['illegal']++;
                } else {
                    $stats['sensitive']++;
                }
            }

            $list = array_map(function (array $row): array {
                $item = $this->decodeConfigJson($row['config_value']);

                return [
                    'id' => $row['id'],
                    'word' => $item['word'] ?? $row['description'],
                    'type' => $item['type'] ?? 'sensitive',
                    'replacement' => $item['replacement'] ?? '',
                    'remark' => $item['remark'] ?? '',
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ];
            }, $rows);

            return $this->success([
                'list' => $list,
                'total' => $total,
                'stats' => [
                    'illegal' => $stats['illegal'],
                    'sensitive' => $stats['sensitive'],
                    'total' => $stats['illegal'] + $stats['sensitive'],
                ],
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取敏感词列表失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
            ]);
            return $this->error('获取敏感词列表失败，请稍后重试', 500);
        }
    }

    /**
     * 新增敏感词
     */
    public function addSensitiveWord(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限添加敏感词', 403);
        }

        $data = $request->post();
        $word = trim((string) ($data['word'] ?? ''));
        $type = trim((string) ($data['type'] ?? 'sensitive'));
        $replacement = trim((string) ($data['replacement'] ?? ''));
        $remark = trim((string) ($data['remark'] ?? ''));

        if ($word === '') {
            return $this->error('敏感词不能为空', 400);
        }
        if (!in_array($type, ['illegal', 'sensitive'], true)) {
            return $this->error('敏感词类型无效', 400);
        }

        try {
            if ($this->hasDuplicateSensitiveWord($word)) {
                return $this->error('敏感词已存在', 400);
            }

            $payload = [
                'word' => $word,
                'type' => $type,
                'replacement' => $replacement,
                'remark' => $remark,
            ];

            $id = (int) Db::name('system_config')->insertGetId([
                'config_key' => 'sensitive_word_' . uniqid('', true),
                'config_value' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'config_type' => 'json',
                'description' => $word,
                'category' => self::CATEGORY_SENSITIVE_WORDS,
                'is_editable' => 1,
                'sort_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->logOperation('create_sensitive_word', 'config', [
                'target_id' => $id,
                'target_type' => 'sensitive_word',
                'detail' => '新增敏感词: ' . $word,
                'after_data' => $payload,
            ]);

            return $this->success(['id' => $id] + $payload, '添加成功');
        } catch (\Exception $e) {
            Log::error('新增敏感词失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'word' => $word,
            ]);
            return $this->error('添加敏感词失败，请稍后重试', 500);
        }
    }

    /**
     * 更新敏感词
     */
    public function updateSensitiveWord(Request $request, int $id)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限修改敏感词', 403);
        }

        $data = $request->put();
        $word = trim((string) ($data['word'] ?? ''));
        $type = trim((string) ($data['type'] ?? 'sensitive'));
        $replacement = trim((string) ($data['replacement'] ?? ''));
        $remark = trim((string) ($data['remark'] ?? ''));

        if ($word === '') {
            return $this->error('敏感词不能为空', 400);
        }
        if (!in_array($type, ['illegal', 'sensitive'], true)) {
            return $this->error('敏感词类型无效', 400);
        }

        try {
            $existing = Db::name('system_config')
                ->where('id', $id)
                ->where('category', self::CATEGORY_SENSITIVE_WORDS)
                ->find();
            if (!$existing) {
                return $this->error('敏感词不存在', 404);
            }
            if ($this->hasDuplicateSensitiveWord($word, $id)) {
                return $this->error('敏感词已存在', 400);
            }

            $payload = [
                'word' => $word,
                'type' => $type,
                'replacement' => $replacement,
                'remark' => $remark,
            ];

            Db::name('system_config')
                ->where('id', $id)
                ->update([
                    'config_value' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'description' => $word,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->logOperation('update_sensitive_word', 'config', [
                'target_id' => $id,
                'target_type' => 'sensitive_word',
                'detail' => '更新敏感词: ' . $word,
                'before_data' => $this->decodeConfigJson($existing['config_value']),
                'after_data' => $payload,
            ]);

            return $this->success(['id' => $id] + $payload, '更新成功');
        } catch (\Exception $e) {
            Log::error('更新敏感词失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'id' => $id,
            ]);
            return $this->error('更新敏感词失败，请稍后重试', 500);
        }
    }

    /**
     * 删除敏感词
     */
    public function deleteSensitiveWord(int $id)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限删除敏感词', 403);
        }

        try {
            $existing = Db::name('system_config')
                ->where('id', $id)
                ->where('category', self::CATEGORY_SENSITIVE_WORDS)
                ->find();
            if (!$existing) {
                return $this->error('敏感词不存在', 404);
            }

            Db::name('system_config')->where('id', $id)->delete();

            $this->logOperation('delete_sensitive_word', 'config', [
                'target_id' => $id,
                'target_type' => 'sensitive_word',
                'detail' => '删除敏感词: ' . $existing['description'],
                'before_data' => $this->decodeConfigJson($existing['config_value']),
            ]);

            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            Log::error('删除敏感词失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'id' => $id,
            ]);
            return $this->error('删除敏感词失败，请稍后重试', 500);
        }
    }

    /**
     * 批量导入敏感词
     */
    public function importSensitiveWords(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限导入敏感词', 403);
        }

        $rawWords = trim((string) $request->post('words', ''));
        if ($rawWords === '') {
            return $this->error('导入内容不能为空', 400);
        }

        $lines = preg_split('/\r\n|\r|\n/', $rawWords) ?: [];
        $insertData = [];
        $addedWords = [];
        $skipped = 0;

        try {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                [$word, $type, $replacement] = array_pad(explode('|', $line, 3), 3, '');
                $word = trim($word);
                $type = trim($type) !== '' ? trim($type) : 'sensitive';
                $replacement = trim($replacement);

                if ($word === '' || !in_array($type, ['illegal', 'sensitive'], true) || $this->hasDuplicateSensitiveWord($word) || in_array($word, $addedWords, true)) {
                    $skipped++;
                    continue;
                }

                $insertData[] = [
                    'config_key' => 'sensitive_word_' . uniqid('', true),
                    'config_value' => json_encode([
                        'word' => $word,
                        'type' => $type,
                        'replacement' => $replacement,
                        'remark' => '批量导入',
                    ], JSON_UNESCAPED_UNICODE),
                    'config_type' => 'json',
                    'description' => $word,
                    'category' => self::CATEGORY_SENSITIVE_WORDS,
                    'is_editable' => 1,
                    'sort_order' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $addedWords[] = $word;
            }

            if (!empty($insertData)) {
                Db::name('system_config')->insertAll($insertData);
            }

            $this->logOperation('import_sensitive_words', 'config', [
                'detail' => '批量导入敏感词',
                'after_data' => [
                    'imported' => count($insertData),
                    'skipped' => $skipped,
                ],
            ]);

            return $this->success([
                'imported' => count($insertData),
                'skipped' => $skipped,
            ], '导入成功');
        } catch (\Exception $e) {
            Log::error('批量导入敏感词失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
            ]);
            return $this->error('导入敏感词失败，请稍后重试', 500);
        }
    }

    /**
     * 获取管理员列表
     */
    public function getAdminUsers(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限查看管理员列表', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $keyword = trim((string) $request->get('keyword', ''));
            $status = $request->get('status', '');

            $adminTable = $this->resolveCompatibleTable(['tc_admin', 'admin'], 'tc_admin');
            if (!$this->tableExists($adminTable)) {
                return $this->error('管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql', 500);
            }

            if (!$this->tableExists('tc_admin_role') || !$this->tableExists('tc_admin_user_role')) {
                return $this->error('管理员角色表不存在，请先执行 database/20260317_create_admin_users_table.sql', 500);
            }

            $columns = $this->getCompatibleTableColumns($adminTable);
            $nicknameField = isset($columns['nickname']) ? 'a.nickname' : "''";
            $statusField = isset($columns['status']) ? 'a.status' : '1';
            $lastLoginField = isset($columns['last_login_at']) ? 'a.last_login_at' : 'NULL';

            $query = Db::table($adminTable)
                ->alias('a')
                ->leftJoin('tc_admin_user_role aur', 'aur.admin_id = a.id')
                ->leftJoin('tc_admin_role r', 'r.id = aur.role_id')
                ->field(implode(',', [
                    'a.id',
                    'a.username',
                    $nicknameField . ' as nickname',
                    $statusField . ' as status',
                    $lastLoginField . ' as last_login_at',
                    'r.name as role_name',
                    'r.code as role_code',
                ]))
                ->group('a.id')
                ->order('a.id', 'desc');

            if ($keyword !== '') {
                $escapedKeyword = '%' . addcslashes($keyword, '%_\\') . '%';
                $query->where(function ($q) use ($escapedKeyword, $columns) {
                    $q->whereLike('a.username', $escapedKeyword);
                    if (isset($columns['nickname'])) {
                        $q->whereOrLike('a.nickname', $escapedKeyword);
                    }
                });
            }
            if ($status !== '' && isset($columns['status'])) {
                $query->where('a.status', (int) $status);
            }

            $rows = $query->select()->toArray();
            $total = count($rows);
            $list = array_slice($rows, ($page - 1) * $pageSize, $pageSize);

            foreach ($list as &$item) {
                $role = $this->normalizeAdminRoleCode((string) ($item['role_code'] ?? ''));
                $item['role'] = $role !== '' ? $role : 'operator';
                $item['role_name'] = $item['role_name'] ?: $this->resolveAdminRoleName($item['role']);
                $item['role_code'] = $item['role_code'] ?: '';
                $item['nickname'] = $item['nickname'] ?: $item['username'];
                $item['status'] = (int) ($item['status'] ?? 0);
            }
            unset($item);

            return $this->success([
                'list' => array_values($list),
                'total' => $total,
            ], '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取管理员列表失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
            ]);
            return $this->error('获取管理员列表失败，请稍后重试', 500);
        }
    }

    /**
     * 保存管理员
     */
    public function saveAdminUser(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限管理管理员账号', 403);
        }

        $data = $request->post();
        $id = (int) ($data['id'] ?? 0);
        $username = trim((string) ($data['username'] ?? ''));
        $nickname = trim((string) ($data['nickname'] ?? ''));
        $password = trim((string) ($data['password'] ?? ''));
        $status = isset($data['status']) ? (int) $data['status'] : 1;
        $roleCode = trim((string) ($data['role'] ?? 'operator'));

        if ($id <= 0 && $username === '') {
            return $this->error('用户名不能为空', 422);
        }
        if ($id <= 0 && $password === '') {
            return $this->error('密码不能为空', 422);
        }

        $adminTable = $this->resolveCompatibleTable(['tc_admin', 'admin'], 'tc_admin');
        if (!$this->tableExists($adminTable)) {
            return $this->error('管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql', 500);
        }

        $role = $this->resolveAdminRoleRecord($roleCode);
        if ($role === null) {
            return $this->error('角色不存在，请先初始化管理员角色数据', 422);
        }

        $columns = $this->getCompatibleTableColumns($adminTable);

        Db::startTrans();
        try {
            $existing = null;
            if ($id > 0) {
                $existing = Db::table($adminTable)->where('id', $id)->lock(true)->find();
                if (!$existing) {
                    Db::rollback();
                    return $this->error('管理员不存在', 404);
                }
            }

            $targetUsername = $id > 0
                ? trim((string) ($username !== '' ? $username : ($existing['username'] ?? '')))
                : $username;
            $targetNickname = $nickname !== '' ? $nickname : $targetUsername;

            $duplicateQuery = Db::table($adminTable)->where('username', $targetUsername);
            if ($id > 0) {
                $duplicateQuery->where('id', '<>', $id);
            }
            if ($duplicateQuery->find()) {
                Db::rollback();
                return $this->error('用户名已存在', 422);
            }

            $payload = [];
            if (isset($columns['username'])) {
                $payload['username'] = $targetUsername;
            }
            if (isset($columns['nickname'])) {
                $payload['nickname'] = $targetNickname;
            }
            if (isset($columns['status'])) {
                $payload['status'] = $status === 1 ? 1 : 0;
            }
            if ($password !== '' && isset($columns['password'])) {
                $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            if ($id > 0) {
                if (!empty($payload)) {
                    Db::table($adminTable)->where('id', $id)->update($payload);
                }
            } else {
                $id = (int) Db::table($adminTable)->insertGetId($payload);
            }

            $this->syncAdminRoleBinding($adminTable, $id, (int) $role['id']);

            $this->logOperation($existing ? 'update' : 'create', 'system', [
                'target_id' => $id,
                'target_type' => 'admin',
                'detail' => ($existing ? '更新' : '创建') . '管理员账号：' . $targetUsername,
                'before_data' => $existing ? [
                    'username' => $existing['username'] ?? '',
                    'nickname' => $existing['nickname'] ?? '',
                    'status' => (int) ($existing['status'] ?? 0),
                ] : null,
                'after_data' => [
                    'username' => $targetUsername,
                    'nickname' => $targetNickname,
                    'status' => $payload['status'] ?? (int) ($existing['status'] ?? 1),
                    'role' => $this->normalizeAdminRoleCode((string) ($role['code'] ?? $roleCode)),
                ],
            ]);

            Db::commit();
            return $this->success(['id' => $id], '保存成功');
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('保存管理员失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'target_admin_id' => $id,
            ]);
            return $this->error('保存管理员失败，请稍后重试', 500);
        }
    }

    /**
     * 删除管理员
     */
    public function deleteAdminUser($id)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限删除管理员账号', 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->error('管理员ID无效', 422);
        }
        if ($id === $this->adminId) {
            return $this->error('不能删除当前登录管理员', 422);
        }

        $adminTable = $this->resolveCompatibleTable(['tc_admin', 'admin'], 'tc_admin');
        if (!$this->tableExists($adminTable)) {
            return $this->error('管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql', 500);
        }

        Db::startTrans();
        try {
            $admin = Db::table($adminTable)->where('id', $id)->lock(true)->find();
            if (!$admin) {
                Db::rollback();
                return $this->error('管理员不存在', 404);
            }

            Db::table($adminTable)->where('id', $id)->delete();
            if ($this->tableExists('tc_admin_user_role')) {
                Db::table('tc_admin_user_role')->where('admin_id', $id)->delete();
            }
            AdminAuthService::clearPermissionCache($id);

            $this->logOperation('delete', 'system', [
                'target_id' => $id,
                'target_type' => 'admin',
                'detail' => '删除管理员账号：' . (string) ($admin['username'] ?? $id),
                'before_data' => [
                    'username' => $admin['username'] ?? '',
                    'nickname' => $admin['nickname'] ?? '',
                    'status' => (int) ($admin['status'] ?? 0),
                ],
            ]);

            Db::commit();
            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('删除管理员失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'target_admin_id' => $id,
            ]);
            return $this->error('删除管理员失败，请稍后重试', 500);
        }
    }

    /**
     * 获取积分统计
     */
    public function pointsStats(Request $request)
    {
        if (!$this->checkPermission('points_view')) {
            return $this->error('无权限查看积分统计', 403);
        }

        try {
            $date = trim((string) $request->get('date', ''));
            $stats = AdminStatsService::getPointsStatsSnapshot($date !== '' ? $date : null);
            return $this->success($stats, '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取积分统计失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'date' => (string) $request->get('date', ''),
            ]);
            return $this->error('获取积分统计失败，请稍后重试', 500);
        }
    }


    /**
     * 处理配置值根据类型
     */
    protected function processConfigValue($value, string $type): string


    {
        switch ($type) {
            case 'json':
                return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
            case 'bool':
                return $value ? '1' : '0';
            case 'int':
                return (string)(int)$value;
            case 'float':
                return (string)(float)$value;
            case 'string':
            default:
                return (string)$value;
        }
    }

    /**
     * 归一化系统设置中的布尔值
     */
    protected function normalizeSettingBool(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value !== 0;
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
            return false;
        }

        return $default;
    }

    /**
     * 构建后台系统设置页的真实配置响应
     */
    protected function buildSystemSettingsResponse(): array
    {
        return [
            'site_name' => (string) ConfigService::get('site_name', '太初命理'),
            'logo' => (string) ConfigService::get('logo', ''),
            'site_description' => (string) ConfigService::get('site_description', '专业的命理分析平台'),
            'register_points' => (int) ConfigService::get('register_points', 100),
            'checkin_points' => (int) ConfigService::get('checkin_points', ConfigService::get('points_sign_daily', 5)),
            'bazi_cost' => (int) ConfigService::get('points_cost_bazi', ConfigService::get('bazi_cost', 20)),
            'tarot_cost' => (int) ConfigService::get('points_cost_tarot', ConfigService::get('tarot_cost', 10)),
            'enable_register' => $this->normalizeSettingBool(ConfigService::get('feature_register_enabled', ConfigService::get('enable_register', true)), true),
            'enable_daily' => $this->normalizeSettingBool(ConfigService::get('feature_daily_enabled', ConfigService::get('enable_daily', true)), true),
            'enable_feedback' => $this->normalizeSettingBool(ConfigService::get('feature_feedback_enabled', ConfigService::get('enable_feedback', true)), true),
            'enable_ai_analysis' => $this->normalizeSettingBool(ConfigService::get('feature_ai_analysis_enabled', ConfigService::get('enable_ai_analysis', true)), true),
        ];
    }

    /**
     * 把后台设置页的旧字段映射为真实业务配置键
     */
    protected function normalizeSystemSettingsInput(array $settings): array
    {
        $normalized = [];

        if (array_key_exists('site_name', $settings)) {
            $normalized['site_name'] = trim((string) $settings['site_name']);
        }
        if (array_key_exists('logo', $settings)) {
            $normalized['logo'] = trim((string) $settings['logo']);
        }
        if (array_key_exists('site_description', $settings)) {
            $normalized['site_description'] = trim((string) $settings['site_description']);
        }
        if (array_key_exists('register_points', $settings)) {
            $normalized['register_points'] = max(0, (int) $settings['register_points']);
        }
        if (array_key_exists('checkin_points', $settings)) {
            $checkinPoints = max(0, (int) $settings['checkin_points']);
            $normalized['checkin_points'] = $checkinPoints;
            $normalized['points_sign_daily'] = $checkinPoints;
        }
        if (array_key_exists('bazi_cost', $settings)) {
            $baziCost = max(0, (int) $settings['bazi_cost']);
            $normalized['bazi_cost'] = $baziCost;
            $normalized['points_cost_bazi'] = $baziCost;
        }
        if (array_key_exists('tarot_cost', $settings)) {
            $tarotCost = max(0, (int) $settings['tarot_cost']);
            $normalized['tarot_cost'] = $tarotCost;
            $normalized['points_cost_tarot'] = $tarotCost;
        }
        if (array_key_exists('enable_register', $settings)) {
            $enabled = $this->normalizeSettingBool($settings['enable_register'], true);
            $normalized['enable_register'] = $enabled;
            $normalized['feature_register_enabled'] = $enabled;
        }
        if (array_key_exists('enable_daily', $settings)) {
            $enabled = $this->normalizeSettingBool($settings['enable_daily'], true);
            $normalized['enable_daily'] = $enabled;
            $normalized['feature_daily_enabled'] = $enabled;
        }
        if (array_key_exists('enable_feedback', $settings)) {
            $enabled = $this->normalizeSettingBool($settings['enable_feedback'], true);
            $normalized['enable_feedback'] = $enabled;
            $normalized['feature_feedback_enabled'] = $enabled;
        }
        if (array_key_exists('enable_ai_analysis', $settings)) {
            $enabled = $this->normalizeSettingBool($settings['enable_ai_analysis'], true);
            $normalized['enable_ai_analysis'] = $enabled;
            $normalized['feature_ai_analysis_enabled'] = $enabled;
        }

        return $normalized;
    }

    /**
     * 解析配置中的JSON值
     */

    protected function decodeConfigJson(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 检查敏感词是否重复
     */
    protected function hasDuplicateSensitiveWord(string $word, int $excludeId = 0): bool
    {
        $rows = Db::name('system_config')
            ->where('category', self::CATEGORY_SENSITIVE_WORDS)
            ->select()
            ->toArray();

        foreach ($rows as $row) {
            if ($excludeId > 0 && (int) $row['id'] === $excludeId) {
                continue;
            }

            $item = $this->decodeConfigJson($row['config_value']);
            if (($item['word'] ?? '') === $word) {
                return true;
            }
        }

        return false;
    }

    /**
     * 统一前端使用的管理员角色编码
     */
    protected function normalizeAdminRoleCode(string $roleCode): string
    {
        $roleCode = strtolower(trim($roleCode));

        return match ($roleCode) {
            'super_admin', 'admin' => 'admin',
            'operator', 'normal_admin', 'customer_service' => 'operator',
            default => $roleCode,
        };
    }

    /**
     * 管理员角色显示名
     */
    protected function resolveAdminRoleName(string $roleCode): string
    {
        return match ($this->normalizeAdminRoleCode($roleCode)) {
            'admin' => '超级管理员',
            'operator' => '运营人员',
            default => '未分配角色',
        };
    }

    /**
     * 解析管理员角色记录
     */
    protected function resolveAdminRoleRecord(string $roleCode): ?array
    {
        if (!$this->tableExists('tc_admin_role')) {
            return null;
        }

        $normalizedRole = $this->normalizeAdminRoleCode($roleCode);
        $candidates = match ($normalizedRole) {
            'admin' => ['super_admin', 'admin'],
            'operator' => ['operator', 'normal_admin', 'customer_service'],
            default => [$normalizedRole],
        };

        $rows = Db::table('tc_admin_role')
            ->whereIn('code', $candidates)
            ->select()
            ->toArray();

        foreach ($candidates as $candidate) {
            foreach ($rows as $row) {
                if (($row['code'] ?? '') === $candidate) {
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * 同步管理员角色绑定
     */
    protected function syncAdminRoleBinding(string $adminTable, int $adminId, int $roleId): void
    {
        if ($adminId <= 0) {
            return;
        }

        if ($this->tableExists('tc_admin_user_role')) {
            Db::table('tc_admin_user_role')->where('admin_id', $adminId)->delete();

            if ($roleId > 0) {
                $payload = [
                    'admin_id' => $adminId,
                    'role_id' => $roleId,
                ];
                $rolePivotColumns = $this->getCompatibleTableColumns('tc_admin_user_role');
                if (isset($rolePivotColumns['created_at'])) {
                    $payload['created_at'] = date('Y-m-d H:i:s');
                }
                Db::table('tc_admin_user_role')->insert($payload);
            }
        }

        $adminColumns = $this->getCompatibleTableColumns($adminTable);
        if (isset($adminColumns['role_id'])) {
            Db::table($adminTable)
                ->where('id', $adminId)
                ->update(['role_id' => $roleId]);
        }

        AdminAuthService::clearPermissionCache($adminId);
    }

    /**
     * 统一查询后台日志列表
     */
    protected function listAdminLogs(Request $request, string $type, string $errorMessage): \think\response\Json
    {
        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );

            $filters = $this->normalizeAdminLogFilters($request->get());
            $query = $this->buildAdminLogQuery($type, $filters);
            $countQuery = clone $query;
            $total = $countQuery->count();
            $rows = $query
                ->page($pagination['page'], $pagination['pageSize'])
                ->select()
                ->toArray();

            $list = array_map(fn(array $item) => $this->formatAdminLogItem($item), $rows);

            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $pagination['page'],
                'pageSize' => $pagination['pageSize'],
            ], '获取成功');
        } catch (\Throwable $e) {
            Log::error('查询后台日志失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'type' => $type,
            ]);
            return $this->error($errorMessage, 500);
        }
    }

    /**
     * 归一化日志筛选条件
     */
    protected function normalizeAdminLogFilters(array $params): array
    {
        $filters = [];
        $operator = trim((string) ($params['operator'] ?? ''));
        if ($operator !== '') {
            if (ctype_digit($operator)) {
                $filters['admin_id'] = (int) $operator;
            } else {
                $filters['keyword'] = $operator;
            }
        }

        if (!empty($params['keyword']) && empty($filters['keyword'])) {
            $filters['keyword'] = trim((string) $params['keyword']);
        }
        if (!empty($params['module'])) {
            $filters['module'] = trim((string) $params['module']);
        }
        if (!empty($params['action'])) {
            $filters['action'] = trim((string) $params['action']);
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $filters['status'] = (int) $params['status'];
        }

        $startTime = trim((string) ($params['start_time'] ?? ($params['dateRange'][0] ?? '')));
        $endTime = trim((string) ($params['end_time'] ?? ($params['dateRange'][1] ?? '')));
        if ($startTime !== '') {
            $filters['start_time'] = $startTime;
        }
        if ($endTime !== '') {
            $filters['end_time'] = $endTime;
        }

        return $filters;
    }

    /**
     * 构建后台日志查询
     */
    protected function buildAdminLogQuery(string $type, array $filters)
    {
        $normalizedType = $this->normalizeAdminLogType($type);
        if ($normalizedType === '') {
            throw new \InvalidArgumentException('日志类型无效');
        }

        $query = AdminLog::order('id', 'desc');
        switch ($normalizedType) {
            case 'login':
                $query->where('action', 'login');
                break;
            case 'api':
                $query->where('module', 'api');
                break;
            case 'operation':
                $query->where('action', '<>', 'login')
                    ->where('module', '<>', 'api');
                break;
        }

        $this->applyAdminLogFilters($query, $filters);
        return $query;
    }

    /**
     * 应用后台日志筛选条件
     */
    protected function applyAdminLogFilters($query, array $filters): void
    {
        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', (int) $filters['admin_id']);
        }
        if (!empty($filters['action'])) {
            $query->where('action', (string) $filters['action']);
        }
        if (!empty($filters['module'])) {
            $query->where('module', (string) $filters['module']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }
        if (!empty($filters['start_time'])) {
            $query->where('created_at', '>=', (string) $filters['start_time']);
        }
        if (!empty($filters['end_time'])) {
            $query->where('created_at', '<=', (string) $filters['end_time']);
        }
        if (!empty($filters['keyword'])) {
            $keyword = addcslashes((string) $filters['keyword'], '%_\\');
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->whereLike('admin_name', '%' . $keyword . '%')
                    ->whereOrLike('detail', '%' . $keyword . '%');
            });
        }
    }

    /**
     * 格式化后台日志项，兼容前端展示字段
     */
    protected function formatAdminLogItem(array $item): array
    {
        $beforeData = $item['before_data'] ?? null;
        if (is_string($beforeData) && $beforeData !== '') {
            $decodedBefore = json_decode($beforeData, true);
            $beforeData = is_array($decodedBefore) ? $decodedBefore : $beforeData;
        }

        $afterData = $item['after_data'] ?? null;
        if (is_string($afterData) && $afterData !== '') {
            $decodedAfter = json_decode($afterData, true);
            $afterData = is_array($decodedAfter) ? $decodedAfter : $afterData;
        }

        $duration = 0;
        if (is_array($afterData) && isset($afterData['duration'])) {
            $duration = (int) $afterData['duration'];
        }

        return array_merge($item, [
            'operator' => (string) ($item['admin_name'] ?? ''),
            'description' => (string) ($item['detail'] ?? ''),
            'request' => is_array($beforeData)
                ? json_encode($beforeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (string) ($beforeData ?? ''),
            'response' => is_array($afterData)
                ? json_encode($afterData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (string) ($afterData ?? ''),
            'duration' => $duration,
            'status' => (int) ($item['status'] ?? 0),
        ]);
    }

    /**
     * 归一化日志类型
     */
    protected function normalizeAdminLogType(string $type): string
    {
        $type = strtolower(trim($type));
        return in_array($type, ['operation', 'login', 'api'], true) ? $type : '';
    }

    /**
     * 日志类型显示名
     */
    protected function resolveAdminLogTypeLabel(string $type): string
    {
        return match ($this->normalizeAdminLogType($type)) {
            'login' => '登录日志',
            'api' => 'API日志',
            default => '操作日志',
        };
    }

    /**
     * 构建后台日志导出 CSV
     */
    protected function buildAdminLogExportCsv(array $rows): string
    {
        $csv = chr(0xEF) . chr(0xBB) . chr(0xBF);
        $headers = ['日志ID', '操作人', '操作模块', '操作类型', '操作描述', 'IP地址', '状态', '操作时间'];
        $csv .= implode(',', array_map(fn(string $field) => $this->escapeCsv($field), $headers)) . "\n";

        foreach ($rows as $row) {
            $item = $this->formatAdminLogItem($row);
            $csv .= implode(',', [
                $this->escapeCsv((string) ($item['id'] ?? '')),
                $this->escapeCsv((string) ($item['operator'] ?? '')),
                $this->escapeCsv((string) ($item['module'] ?? '')),
                $this->escapeCsv((string) ($item['action'] ?? '')),
                $this->escapeCsv((string) ($item['description'] ?? '')),
                $this->escapeCsv((string) ($item['ip'] ?? '')),
                $this->escapeCsv((int) ($item['status'] ?? 0) === 1 ? '成功' : '失败'),
                $this->escapeCsv((string) ($item['created_at'] ?? '')),
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * 格式化反作弊规则输出
     */
    protected function transformRiskRuleRow(array $row): array
    {
        $config = $row['config'] ?? [];
        if (is_string($config) && $config !== '') {
            $decoded = json_decode($config, true);
            $config = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($config)) {
            $config = [];
        }

        $threshold = (int) ($config['threshold'] ?? $config['limit'] ?? 0);

        return array_merge($row, [
            'config' => $config,
            'threshold' => $threshold,
            'status' => (int) ($row['status'] ?? 0),
            'action' => (string) ($row['action'] ?? 'log'),
            'code' => (string) ($row['code'] ?? ''),
        ]);
    }

    /**
     * 组装反作弊规则写入载荷
     */
    protected function buildRiskRulePayload(array $data, array $existing = []): array
    {
        $name = trim((string) ($data['name'] ?? ($existing['name'] ?? '')));
        if ($name === '') {
            throw new \InvalidArgumentException('规则名称不能为空');
        }

        $type = trim((string) ($data['type'] ?? ($existing['type'] ?? '')));
        if ($type === '') {
            throw new \InvalidArgumentException('检测类型不能为空');
        }

        $action = trim((string) ($data['action'] ?? ($existing['action'] ?? 'log')));
        if (!in_array($action, ['log', 'captcha', 'block'], true)) {
            throw new \InvalidArgumentException('拦截动作无效');
        }

        $threshold = (int) ($data['threshold'] ?? 0);
        if ($threshold <= 0) {
            $existingConfig = $existing['config'] ?? [];
            if (is_string($existingConfig) && $existingConfig !== '') {
                $decodedConfig = json_decode($existingConfig, true);
                $existingConfig = is_array($decodedConfig) ? $decodedConfig : [];
            }
            $threshold = (int) ($existingConfig['threshold'] ?? 0);
        }
        if ($threshold <= 0) {
            throw new \InvalidArgumentException('阈值必须大于 0');
        }

        $config = $data['config'] ?? ($existing['config'] ?? []);
        if (is_string($config) && $config !== '') {
            $decodedConfig = json_decode($config, true);
            $config = is_array($decodedConfig) ? $decodedConfig : [];
        } elseif (!is_array($config)) {
            $config = [];
        }
        $config['threshold'] = $threshold;
        $config['window_minutes'] = (int) ($config['window_minutes'] ?? 1);

        $code = trim((string) ($data['code'] ?? ($existing['code'] ?? '')));
        if ($code === '') {
            $code = strtolower($type . '_' . substr(md5($name . microtime(true) . mt_rand()), 0, 8));
        }
        $code = trim((string) preg_replace('/[^a-z0-9_]+/i', '_', strtolower($code)), '_');
        if ($code === '') {
            throw new \InvalidArgumentException('规则编码无效');
        }

        return [
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'action' => $action,
            'status' => (int) (($data['status'] ?? ($existing['status'] ?? 1)) == 1),
        ];
    }
    
    /**
     * 获取操作日志列表
     */


    public function operationLogs(Request $request)
    {
        // 检查权限
        if (!$this->checkPermission('log_view')) {
            return $this->error('无权限查看操作日志', 403);
        }
        
        try {
            $params = $request->get();
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );
            $page = $pagination['page'];
            $perPage = $pagination['pageSize'];
            
            $result = AdminLog::getLogList($params, $page, $perPage);

            
            return $this->success($result, '获取成功');
        } catch (\Exception $e) {
            Log::error('获取操作日志失败: ' . $e->getMessage());
            return $this->error('获取日志失败，请稍后重试', 500);
        }
    }

    /**
     * 获取八字记录详情
     */
    public function baziDetail($id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限查看内容详情', 403);
        }
        
        try {
            $record = BaziRecord::find($id);
            if (!$record) {
                return $this->error('记录不存在', 404);
            }
            return $this->success($record, '获取成功');
        } catch (\Exception $e) {
            Log::error('获取八字详情失败: ' . $e->getMessage());
            return $this->error('获取详情失败', 500);
        }
    }

    /**
     * 获取反馈详情
     */
    public function feedbackDetail($id)
    {
        if (!$this->checkPermission('feedback_view')) {
            return $this->error('无权限查看反馈详情', 403);
        }
        
        try {
            $feedback = Feedback::find($id);
            if (!$feedback) {
                return $this->error('反馈不存在', 404);
            }
            return $this->success($feedback, '获取成功');
        } catch (\Exception $e) {
            Log::error('获取反馈详情失败: ' . $e->getMessage());
            return $this->error('获取详情失败', 500);
        }
    }

    /**
     * 更新反馈状态
     */
    public function updateFeedbackStatus(Request $request, $id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限修改反馈状态', 403);
        }
        
        try {
            $status = $request->put('status');
            $feedback = Feedback::find($id);
            if (!$feedback) {
                return $this->error('反馈不存在', 404);
            }
            
            $oldStatus = $feedback->status;
            $feedback->status = $status;
            $feedback->save();
            
            $this->logOperation('update_status', 'feedback', [
                'target_id' => $id,
                'target_type' => 'feedback',
                'detail' => "修改反馈状态为: {$status}",
                'before_data' => ['status' => $oldStatus],
                'after_data' => ['status' => $status]
            ]);
            
            return $this->success(null, '更新成功');
        } catch (\Exception $e) {
            Log::error('更新反馈状态失败: ' . $e->getMessage());
            return $this->error('操作失败', 500);
        }
    }

    /**
     * 删除反馈
     */
    public function deleteFeedback($id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限删除反馈', 403);
        }
        
        try {
            $feedback = Feedback::find($id);
            if (!$feedback) {
                return $this->error('反馈不存在', 404);
            }
            
            $beforeData = $feedback->toArray();
            Feedback::destroy($id);
            
            $this->logOperation('delete', 'feedback', [
                'target_id' => $id,
                'target_type' => 'feedback',
                'detail' => '删除反馈记录',
                'before_data' => $beforeData
            ]);
            
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            Log::error('删除反馈失败: ' . $e->getMessage());
            return $this->error('删除失败', 500);
        }
    }

    /**
     * 获取反馈分类
     */
    public function feedbackCategories()
    {
        if (!$this->checkPermission('feedback_view')) {
            return $this->error('无权限查看反馈分类', 403);
        }

        try {
            $list = Db::name('system_config')
                ->where('category', 'feedback_category')
                ->select();
            return $this->success($list, '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取分类失败', 500);
        }
    }

    /**
     * 保存反馈分类
     */
    public function saveFeedbackCategory(Request $request)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限管理反馈分类', 403);
        }

        try {
            $data = $request->post();
            $id = $data['id'] ?? 0;
            unset($data['id']);

            if ($id > 0) {
                Db::name('system_config')->where('id', $id)->update($data);
            } else {
                $data['category'] = 'feedback_category';
                $data['config_key'] = 'fb_cat_' . uniqid();
                $id = Db::name('system_config')->insertGetId($data);
            }

            return $this->success(['id' => $id], '保存成功');
        } catch (\Exception $e) {
            return $this->error('保存失败', 500);
        }
    }

    /**
     * 删除反馈分类
     */
    public function deleteFeedbackCategory($id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限删除反馈分类', 403);
        }

        try {
            Db::name('system_config')->where('id', $id)->where('category', 'feedback_category')->delete();
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败', 500);
        }
    }


    /**
     * 获取用户行为记录
     */
    public function userBehavior(Request $request)
    {
        if (!$this->checkPermission('user_view')) {
            return $this->error('无权限查看用户行为', 403);
        }
        
        try {
            $userId = (int) $request->get('user_id');
            if (!$userId) {
                return $this->error('缺少用户ID', 400);
            }
            
            // 从操作日志中获取该用户的相关记录
            $list = AdminLog::where('target_id', $userId)
                ->where('target_type', 'user')
                ->order('id', 'desc')
                ->limit(50)
                ->select();
                
            return $this->success([
                'list' => $list,
                'total' => count($list)
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取用户行为记录失败: ' . $e->getMessage());
            return $this->error('获取失败', 500);
        }
    }

    /**
     * 登录日志
     */
    public function loginLogs(Request $request)
    {
        if (!$this->checkPermission('log_view')) {
            return $this->error('无权限查看登录日志', 403);
        }
        
        try {
            $params = $request->get();
            $params['action'] = 'login';
            
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE)
            );
            
            $result = AdminLog::getLogList($params, $pagination['page'], $pagination['pageSize']);
            return $this->success($result, '获取成功');
        } catch (\Exception $e) {
            Log::error('获取登录日志失败: ' . $e->getMessage());
            return $this->error('获取失败', 500);
        }
    }

    /**
     * API日志
     */
    public function apiLogs(Request $request)
    {
        if (!$this->checkPermission('log_view')) {
            return $this->error('无权限查看API日志', 403);
        }

        return $this->listAdminLogs($request, 'api', '获取API日志失败，请稍后重试');
    }

    /**
     * 清空日志
     */
    public function clearLogs($type)
    {
        if (!$this->checkPermission('log_view')) {
            return $this->error('无权限清空日志', 403);
        }

        try {
            $normalizedType = $this->normalizeAdminLogType((string) $type);
            if ($normalizedType === '') {
                return $this->error('日志类型无效', 422);
            }

            $affected = $this->buildAdminLogQuery($normalizedType, [])->delete();
            $this->logOperation('delete', 'log', [
                'detail' => '清空' . $this->resolveAdminLogTypeLabel($normalizedType) . '，共' . $affected . '条',
                'after_data' => ['type' => $normalizedType, 'count' => $affected],
            ]);

            return $this->success(['count' => $affected], '清空成功');
        } catch (\Throwable $e) {
            Log::error('清空日志失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'type' => (string) $type,
            ]);
            return $this->error('清空日志失败，请稍后重试', 500);
        }
    }

    /**
     * 导出日志
     */
    public function exportLogs(Request $request, $type)
    {
        if (!$this->checkPermission('log_view')) {
            return $this->error('无权限导出日志', 403);
        }

        try {
            $normalizedType = $this->normalizeAdminLogType((string) $type);
            if ($normalizedType === '') {
                return $this->error('日志类型无效', 422);
            }

            $filters = $this->normalizeAdminLogFilters($request->get());
            $rows = $this->buildAdminLogQuery($normalizedType, $filters)->select()->toArray();
            $csv = $this->buildAdminLogExportCsv($rows);
            $filename = $normalizedType . '_logs_' . date('YmdHis') . '.csv';

            $this->logOperation('export', 'log', [
                'detail' => '导出' . $this->resolveAdminLogTypeLabel($normalizedType) . '，共' . count($rows) . '条',
                'after_data' => ['type' => $normalizedType, 'count' => count($rows)],
            ]);

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Throwable $e) {
            Log::error('导出日志失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'type' => (string) $type,
            ]);
            return $this->error('导出日志失败，请稍后重试', 500);
        }
    }


    /**
     * 获取风险事件列表
     */
    public function riskEvents(Request $request)
    {
        if (!$this->checkPermission('anticheat_view')) {
            return $this->error('无权限查看风险事件', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE)
            );
            $status = $request->get('status', '');
            $type = $request->get('type', '');

            $query = Db::name('tc_anti_cheat_event')->order('id', 'desc');
            if ($status !== '') {
                $query->where('status', (int)$status);
            }
            if ($type) {
                $query->where('type', $type);
            }

            $total = $query->count();
            $list = $query->page($pagination['page'], $pagination['pageSize'])->select();

            return $this->success([
                'list' => $list,
                'total' => $total
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取风险事件失败: ' . $e->getMessage());
            return $this->error('获取失败', 500);
        }
    }

    /**
     * 获取风险事件详情
     */
    public function riskEventDetail($id)
    {
        if (!$this->checkPermission('anticheat_view')) {
            return $this->error('无权限查看详情', 403);
        }

        try {
            $event = Db::name('tc_anti_cheat_event')->where('id', $id)->find();
            if (!$event) {
                return $this->error('事件不存在', 404);
            }
            return $this->success($event, '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取详情失败', 500);
        }
    }

    /**
     * 处理风险事件
     */
    public function handleRiskEvent(Request $request, $id)
    {
        if (!$this->checkPermission('anticheat_manage')) {
            return $this->error('无权限处理风险事件', 403);
        }

        try {
            $data = $request->put();
            $status = $data['status'] ?? 1;
            $remark = $data['remark'] ?? '';

            Db::name('tc_anti_cheat_event')->where('id', $id)->update([
                'status' => $status,
                'handle_remark' => $remark,
                'handler_id' => $this->adminId,
                'handle_at' => date('Y-m-d H:i:s')
            ]);

            $this->logOperation('handle_risk_event', 'anticheat', [
                'target_id' => $id,
                'detail' => "处理风险事件, 状态: {$status}, 备注: {$remark}"
            ]);

            return $this->success(null, '操作成功');
        } catch (\Exception $e) {
            return $this->error('操作失败', 500);
        }
    }

    /**
     * 获取反作弊规则列表
     */
    public function riskRules()
    {
        if (!$this->checkPermission('anticheat_view')) {
            return $this->error('无权限查看规则', 403);
        }

        if (!$this->tableExists('tc_anti_cheat_rule')) {
            return $this->error('反作弊规则表不存在，请先执行 database/20260317_create_anticheat_tables.sql', 500);
        }

        try {
            $list = Db::name('tc_anti_cheat_rule')
                ->order('id', 'desc')
                ->select()
                ->toArray();

            $list = array_map(fn(array $item) => $this->transformRiskRuleRow($item), $list);
            return $this->success($list, '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取反作弊规则失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
            ]);
            return $this->error('获取规则失败，请稍后重试', 500);
        }
    }

    /**
     * 新增反作弊规则
     */
    public function saveRiskRule(Request $request)
    {
        if (!$this->checkPermission('anticheat_manage')) {
            return $this->error('无权限保存规则', 403);
        }

        if (!$this->tableExists('tc_anti_cheat_rule')) {
            return $this->error('反作弊规则表不存在，请先执行 database/20260317_create_anticheat_tables.sql', 500);
        }

        try {
            $payload = $this->buildRiskRulePayload($request->post());
            $id = (int) Db::name('tc_anti_cheat_rule')->insertGetId($payload);
            $saved = Db::name('tc_anti_cheat_rule')->where('id', $id)->find();

            $this->logOperation('create', 'anticheat', [
                'target_id' => $id,
                'target_type' => 'risk_rule',
                'detail' => '新增反作弊规则：' . ($payload['name'] ?? ''),
                'after_data' => $this->transformRiskRuleRow($saved ?: []),
            ]);

            return $this->success(['id' => $id], '保存成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('保存反作弊规则失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
            ]);
            return $this->error('保存失败，请稍后重试', 500);
        }
    }

    /**
     * 更新反作弊规则
     */
    public function updateRiskRule(Request $request, $id)
    {
        if (!$this->checkPermission('anticheat_manage')) {
            return $this->error('无权限更新规则', 403);
        }

        if (!$this->tableExists('tc_anti_cheat_rule')) {
            return $this->error('反作弊规则表不存在，请先执行 database/20260317_create_anticheat_tables.sql', 500);
        }

        $id = (int) $id;
        try {
            $existing = Db::name('tc_anti_cheat_rule')->where('id', $id)->find();
            if (!$existing) {
                return $this->error('规则不存在', 404);
            }

            $payload = $this->buildRiskRulePayload($request->put(), $existing);
            Db::name('tc_anti_cheat_rule')->where('id', $id)->update($payload);
            $saved = Db::name('tc_anti_cheat_rule')->where('id', $id)->find();

            $this->logOperation('update', 'anticheat', [
                'target_id' => $id,
                'target_type' => 'risk_rule',
                'detail' => '更新反作弊规则：' . ($payload['name'] ?? ''),
                'before_data' => $this->transformRiskRuleRow($existing),
                'after_data' => $this->transformRiskRuleRow($saved ?: []),
            ]);

            return $this->success(['id' => $id], '更新成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('更新反作弊规则失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'rule_id' => $id,
            ]);
            return $this->error('更新失败，请稍后重试', 500);
        }
    }

    /**
     * 删除反作弊规则
     */
    public function deleteRiskRule($id)
    {
        if (!$this->checkPermission('anticheat_manage')) {
            return $this->error('无权限删除规则', 403);
        }

        if (!$this->tableExists('tc_anti_cheat_rule')) {
            return $this->error('反作弊规则表不存在，请先执行 database/20260317_create_anticheat_tables.sql', 500);
        }

        $id = (int) $id;
        try {
            $existing = Db::name('tc_anti_cheat_rule')->where('id', $id)->find();
            if (!$existing) {
                return $this->error('规则不存在', 404);
            }

            Db::name('tc_anti_cheat_rule')->where('id', $id)->delete();
            $this->logOperation('delete', 'anticheat', [
                'target_id' => $id,
                'target_type' => 'risk_rule',
                'detail' => '删除反作弊规则：' . (string) ($existing['name'] ?? ''),
                'before_data' => $this->transformRiskRuleRow($existing),
            ]);

            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            Log::error('删除反作弊规则失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'rule_id' => $id,
            ]);
            return $this->error('删除失败，请稍后重试', 500);
        }
    }


    /**
     * 获取设备指纹列表
     */
    public function deviceFingerprints(Request $request)
    {
        if (!$this->checkPermission('anticheat_view')) {
            return $this->error('无权限查看设备信息', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE)
            );
            $keyword = $request->get('keyword', '');

            $query = Db::name('tc_anti_cheat_device')->order('last_active_at', 'desc');
            if ($keyword) {
                $query->whereLike('device_id|block_reason', '%' . $keyword . '%');
            }

            $total = $query->count();
            $list = $query->page($pagination['page'], $pagination['pageSize'])->select();

            return $this->success([
                'list' => $list,
                'total' => $total
            ], '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取设备列表失败', 500);
        }
    }

    /**
     * 拉黑/移出黑名单设备
     */
    public function blockDevice(Request $request, $id)
    {
        if (!$this->checkPermission('anticheat_manage')) {
            return $this->error('无权限操作设备', 403);
        }

        try {
            $data = $request->put();
            $isBlocked = $data['is_blocked'] ?? 1;
            $reason = $data['reason'] ?? '';

            Db::name('tc_anti_cheat_device')->where('id', $id)->update([
                'is_blocked' => $isBlocked,
                'block_reason' => $reason
            ]);

            return $this->success(null, '操作成功');
        } catch (\Exception $e) {
            return $this->error('操作失败', 500);
        }
    }

    /**
     * 获取系统任务列表
     */
    public function taskList(Request $request)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限管理系统任务', 403);
        }

        try {
            $list = Db::name('tc_system_task')->select();
            return $this->success($list, '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取任务列表失败', 500);
        }
    }

    /**
     * 获取任务详情
     */
    public function taskDetail($id)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限查看详情', 403);
        }

        try {
            $task = Db::name('tc_system_task')->where('id', $id)->find();
            if (!$task) {
                return $this->error('任务不存在', 404);
            }
            return $this->success($task, '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取详情失败', 500);
        }
    }

    /**
     * 创建系统任务
     */
    public function createTask(Request $request)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限创建任务', 403);
        }

        try {
            $data = $request->post();
            $id = Db::name('tc_system_task')->insertGetId($data);
            return $this->success(['id' => $id], '创建成功');
        } catch (\Exception $e) {
            return $this->error('创建失败', 500);
        }
    }

    /**
     * 更新系统任务
     */
    public function updateTask(Request $request, $id)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限修改任务', 403);
        }

        try {
            $data = $request->put();
            Db::name('tc_system_task')->where('id', $id)->update($data);
            return $this->success(null, '更新成功');
        } catch (\Exception $e) {
            return $this->error('更新失败', 500);
        }
    }

    /**
     * 删除系统任务
     */
    public function deleteTask($id)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限删除任务', 403);
        }

        try {
            Db::name('tc_system_task')->where('id', $id)->delete();
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败', 500);
        }
    }

    /**
     * 立即执行任务
     */
    public function runTask($id)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限执行任务', 403);
        }

        try {
            $task = Db::name('tc_system_task')->where('id', $id)->find();
            if (!$task) {
                return $this->error('任务不存在', 404);
            }

            $this->logOperation('run_task', 'task', [
                'target_id' => $id,
                'detail' => "手动触发系统任务: {$task['name']}"
            ]);

            return $this->success(null, '任务已触发执行');
        } catch (\Exception $e) {
            return $this->error('触发失败', 500);
        }
    }

    /**
     * 切换任务状态
     */
    public function toggleTaskStatus(Request $request, $id)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限修改任务状态', 403);
        }

        try {
            $status = $request->put('status');
            Db::name('tc_system_task')->where('id', $id)->update(['status' => $status]);
            return $this->success(null, '状态更新成功');
        } catch (\Exception $e) {
            return $this->error('更新失败', 500);
        }
    }

    /**
     * 获取任务运行日志
     */
    public function taskLogs(Request $request)
    {
        if (!$this->checkPermission('task_view')) {
            return $this->error('无权限查看任务日志', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE)
            );
            $taskId = $request->get('task_id');

            $query = Db::name('tc_system_task_log')->order('id', 'desc');
            if ($taskId) {
                $query->where('task_id', $taskId);
            }

            $total = $query->count();
            $list = $query->page($pagination['page'], $pagination['pageSize'])->select();

            return $this->success([
                'list' => $list,
                'total' => $total
            ], '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取日志失败', 500);
        }
    }

    /**
     * 获取系统脚本列表
     */
    public function getTaskScripts()
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限管理脚本', 403);
        }

        try {
            $list = Db::name('tc_system_script')->select();
            return $this->success($list, '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取脚本列表失败', 500);
        }
    }

    /**
     * 保存系统脚本
     */
    public function saveTaskScript(Request $request)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限保存脚本', 403);
        }

        try {
            $data = $request->post();
            $id = $data['id'] ?? 0;
            unset($data['id']);

            if ($id > 0) {
                Db::name('tc_system_script')->where('id', $id)->update($data);
            } else {
                $id = Db::name('tc_system_script')->insertGetId($data);
            }

            return $this->success(['id' => $id], '保存成功');
        } catch (\Exception $e) {
            return $this->error('保存失败', 500);
        }
    }

    /**
     * 删除脚本
     */
    public function deleteTaskScript($id)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限删除脚本', 403);
        }

        try {
            Db::name('tc_system_script')->where('id', $id)->delete();
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败', 500);
        }
    }




    /**
     * 获取待处理反馈数量
     */
    public function pendingFeedback()
    {
        if (!$this->checkPermission('feedback_view')) {
            return $this->error('无权限查看反馈', 403);
        }

        try {
            $query = $this->buildPendingFeedbackQuery();
            $count = $query->count();
            $list = $this->buildPendingFeedbackQuery()
                ->order('created_at', 'desc')
                ->limit(5)
                ->select()
                ->toArray();

            return $this->success([
                'count' => $count,
                'list' => $list,
            ], '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取待处理反馈失败: ' . $e->getMessage());
            return $this->error('获取待处理反馈失败', 500);
        }
    }

    /**
     * 获取实时数据
     */
    public function realtime(Request $request)
    {
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限访问统计数据', 403);
        }

        try {
            $limit = max(1, min(50, (int) $request->get('limit', 10)));
            return $this->success($this->buildRealtimePayload($limit), '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取实时数据失败: ' . $e->getMessage());
            return $this->error('获取实时数据失败', 500);
        }
    }

    /**
     * 导出实时看板快照
     */
    public function exportRealtime(Request $request)
    {
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限导出实时数据', 403);
        }

        try {
            $limit = max(1, min(100, (int) $request->get('limit', 50)));
            $payload = $this->buildRealtimePayload($limit);

            $rows = [
                ['指标', '数值'],
                ['今日新增用户', (string) ($payload['today_users'] ?? 0)],
                ['今日八字排盘', (string) ($payload['today_bazi'] ?? 0)],
                ['今日塔罗占卜', (string) ($payload['today_tarot'] ?? 0)],
                ['今日反馈', (string) ($payload['today_feedback'] ?? 0)],
                ['近15分钟活跃用户', (string) ($payload['online_users'] ?? 0)],
                ['待处理反馈', (string) ($payload['pending_feedback'] ?? 0)],
                ['生成时间', (string) ($payload['timestamp'] ?? '')],
                [],
                ['时间', '事件', '用户'],
            ];

            foreach ($payload['realtime_list'] ?? [] as $item) {
                $rows[] = [
                    (string) ($item['time'] ?? ''),
                    (string) ($item['action'] ?? ''),
                    (string) ($item['user'] ?? ''),
                ];
            }

            $csvLines = array_map(function (array $row): string {
                return implode(',', array_map(fn ($value) => $this->escapeCsv((string) $value), $row));
            }, $rows);
            $csv = "\xEF\xBB\xBF" . implode("\n", $csvLines) . "\n";

            $this->logOperation('export_realtime', 'stats', [
                'detail' => '导出实时看板快照，条数：' . count($payload['realtime_list'] ?? []),
            ]);

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="dashboard_realtime_' . date('YmdHis') . '.csv"',
            ]);
        } catch (\Throwable $e) {
            Log::error('导出实时看板快照失败: ' . $e->getMessage());
            return $this->error('导出实时数据失败，请稍后重试', 500);
        }
    }

    /**
     * 构建实时看板数据
     */
    protected function buildRealtimePayload(int $limit = 10): array
    {
        $todayStart = date('Y-m-d 00:00:00');
        $recentActiveAt = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $realtimeList = $this->buildRealtimeList($limit);

        return [
            'today_users' => User::where('created_at', '>=', $todayStart)->count(),
            'today_bazi' => BaziRecord::where('created_at', '>=', $todayStart)->count(),
            'today_tarot' => TarotRecord::where('created_at', '>=', $todayStart)->count(),
            'today_feedback' => Feedback::where('created_at', '>=', $todayStart)->count(),
            'online_users' => User::where('last_login_at', '>=', $recentActiveAt)->count(),
            'pending_feedback' => $this->buildPendingFeedbackQuery()->count(),
            'realtime_list' => $realtimeList,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 构建实时动态列表
     */
    protected function buildRealtimeList(int $limit = 10): array
    {
        $userRows = User::order('created_at', 'desc')
            ->limit($limit)
            ->field($this->buildRealtimeUserFieldList(true))
            ->select()
            ->toArray();

        $baziRows = BaziRecord::order('created_at', 'desc')
            ->limit($limit)
            ->field('user_id,created_at')
            ->select()
            ->toArray();

        $tarotRows = TarotRecord::order('created_at', 'desc')
            ->limit($limit)
            ->field('user_id,created_at,spread_type')
            ->select()
            ->toArray();

        $feedbackRows = $this->buildPendingFeedbackQuery()
            ->order('created_at', 'desc')
            ->limit($limit)
            ->field('user_id,type,created_at')
            ->select()
            ->toArray();

        $userNameMap = $this->resolveRealtimeUserNames(array_merge(
            array_column($baziRows, 'user_id'),
            array_column($tarotRows, 'user_id'),
            array_column($feedbackRows, 'user_id')
        ));

        $events = [];
        foreach ($userRows as $row) {
            $sortTime = (string) ($row['created_at'] ?? $row['updated_at'] ?? '');
            $events[] = [
                'time' => date('H:i:s', strtotime($sortTime ?: 'now')),
                'action' => '新用户注册',
                'user' => $this->formatRealtimeUserName($row),
                'sort_time' => $sortTime,
            ];
        }
        foreach ($baziRows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            $events[] = [
                'time' => date('H:i:s', strtotime((string) ($row['created_at'] ?? 'now'))),
                'action' => '提交八字排盘',
                'user' => $userNameMap[$userId] ?? ('用户#' . $userId),
                'sort_time' => (string) ($row['created_at'] ?? ''),
            ];
        }
        foreach ($tarotRows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            $spreadType = trim((string) ($row['spread_type'] ?? ''));
            $events[] = [
                'time' => date('H:i:s', strtotime((string) ($row['created_at'] ?? 'now'))),
                'action' => $spreadType !== '' ? '进行塔罗占卜（' . $spreadType . '）' : '进行塔罗占卜',
                'user' => $userNameMap[$userId] ?? ('用户#' . $userId),
                'sort_time' => (string) ($row['created_at'] ?? ''),
            ];
        }
        foreach ($feedbackRows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            $feedbackType = trim((string) ($row['type'] ?? '反馈'));
            $events[] = [
                'time' => date('H:i:s', strtotime((string) ($row['created_at'] ?? 'now'))),
                'action' => '提交待处理反馈（' . $feedbackType . '）',
                'user' => $userNameMap[$userId] ?? ($userId > 0 ? '用户#' . $userId : '匿名用户'),
                'sort_time' => (string) ($row['created_at'] ?? ''),
            ];
        }

        usort($events, static function (array $left, array $right): int {
            return strcmp((string) ($right['sort_time'] ?? ''), (string) ($left['sort_time'] ?? ''));
        });

        $events = array_slice($events, 0, $limit);

        return array_map(static function (array $event): array {
            unset($event['sort_time']);
            return $event;
        }, $events);
    }

    /**
     * 获取待处理反馈查询
     */
    protected function buildPendingFeedbackQuery()
    {
        return Feedback::where(function ($query) {
            $query->where('status', 0)
                ->whereOr('status', '=', 'pending');
        });
    }

    /**
     * 构建实时动态所需的用户字段列表
     */
    protected function buildRealtimeUserFieldList(bool $includeTimeField = false): string
    {
        $columns = SchemaInspector::getTableColumns('tc_user');
        $fields = ['id'];

        foreach (['nickname', 'username', 'phone'] as $field) {
            if (isset($columns[$field])) {
                $fields[] = $field;
            }
        }

        if ($includeTimeField) {
            foreach (['created_at', 'updated_at'] as $field) {
                if (isset($columns[$field])) {
                    $fields[] = $field;
                    break;
                }
            }
        }

        return implode(',', array_values(array_unique($fields)));
    }

    /**
     * 统一格式化实时动态中的用户显示名
     */
    protected function formatRealtimeUserName(array $row): string
    {
        $id = (int) ($row['id'] ?? 0);
        foreach (['nickname', 'username', 'phone'] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '用户#' . $id;
    }

    /**
     * 解析实时动态中的用户名称
     */
    protected function resolveRealtimeUserNames(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (empty($userIds)) {
            return [];
        }

        $rows = User::whereIn('id', $userIds)
            ->field($this->buildRealtimeUserFieldList())
            ->select()
            ->toArray();

        $result = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $result[$id] = $this->formatRealtimeUserName($row);
        }

        return $result;
    }


    /**
     * 导出用户数据
     */

    public function exportUsers(Request $request)
    {
        if (!$this->checkPermission('user_view')) {
            return $this->error('无权限导出用户数据', 403);
        }

        try {
            $params = $request->get();
            $query = User::order('id', 'desc');
            $username = trim((string) ($params['username'] ?? ($params['keyword'] ?? '')));
            $phone = trim((string) ($params['phone'] ?? ''));
            $status = $params['status'] ?? '';
            $dateRange = $params['dateRange'] ?? [];
            $startDate = trim((string) ($params['startDate'] ?? ((is_array($dateRange) && isset($dateRange[0])) ? $dateRange[0] : '')));
            $endDate = trim((string) ($params['endDate'] ?? ((is_array($dateRange) && isset($dateRange[1])) ? $dateRange[1] : '')));

            if ($username !== '') {
                $safeUsername = preg_replace('/[%_\\]/', '', $username) ?: '';
                $query->whereLike('username|nickname', '%' . $safeUsername . '%');
            }
            if ($phone !== '') {
                $safePhone = preg_replace('/[%_\\]/', '', $phone) ?: '';
                $query->whereLike('phone', '%' . $safePhone . '%');
            }

            if ($status !== '') {
                $status = (int) $status;
                if (!in_array($status, [0, 1, 2], true)) {
                    return $this->error('用户状态参数无效', 400);
                }
                $query->where('status', $status);
            }

            if ($startDate !== '') {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) !== 1) {
                    return $this->error('开始日期格式无效', 400);
                }
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }
            if ($endDate !== '') {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate) !== 1) {
                    return $this->error('结束日期格式无效', 400);
                }
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            $users = $query->select()->toArray();
            $headers = ['ID', '昵称', '手机号', '积分', 'VIP等级', '状态', '注册时间', '最后登录'];
            $csv = implode(',', $headers) . "\n";

            foreach ($users as $user) {
                $row = [
                    $user['id'],
                    $this->escapeCsv((string) ($user['nickname'] ?? '')),
                    $user['phone'] ?? '',
                    $user['points'] ?? 0,
                    $user['vip_level'] ?? 0,
                    (int) ($user['status'] ?? 0) === 1 ? '正常' : ((int) ($user['status'] ?? 0) === 0 ? '禁用' : '待验证'),
                    $user['created_at'] ?? '',
                    $user['last_login_at'] ?? ''
                ];
                $csv .= implode(',', $row) . "\n";
            }

            $csv = "\xEF\xBB\xBF" . $csv;

            $this->logOperation('export', 'user', [
                'target_id' => 0,
                'detail' => '导出用户数据，共' . count($users) . '条记录',
                'after_data' => [
                    'count' => count($users),
                    'filters' => [
                        'username' => $username,
                        'phone' => $phone,
                        'status' => $status,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                    ],
                ],
            ]);

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="users_' . date('YmdHis') . '.csv"'
            ]);
        } catch (\Exception $e) {
            Log::error('导出用户数据失败: ' . $e->getMessage());
            return $this->error('导出失败，请稍后重试', 500);
        }
    }

    /**
     * CSV字段转义
     */
    private function escapeCsv(string $field): string
    {
        $field = str_replace('"', '""', $field);
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            $field = '"' . $field . '"';
        }
        return $field;
    }

    /**
     * 解析兼容表名
     */
    protected function resolveCompatibleTable(array $candidates, string $fallback): string
    {
        foreach ($candidates as $table) {
            if ($this->tableExists($table)) {
                return $table;
            }
        }

        return $fallback;
    }

    /**
     * 获取兼容表字段信息
     */
    protected function getCompatibleTableColumns(string $table): array
    {
        $escapedTable = str_replace('`', '``', $table);
        $columns = [];

        foreach (Db::query("SHOW COLUMNS FROM `{$escapedTable}`") as $column) {
            $field = (string) ($column['Field'] ?? '');
            if ($field !== '') {
                $columns[$field] = strtolower((string) ($column['Type'] ?? ''));
            }
        }

        return $columns;
    }

    /**
     * 判断数据表是否存在
     */
    protected function tableExists(string $table): bool
    {
        $escapedTable = addslashes($table);
        return !empty(Db::query("SHOW TABLES LIKE '{$escapedTable}'"));
    }

    /**
     * 神煞列表
     */
    public function shenshaList(Request $request)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限查看神煞数据', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $keyword = trim((string) $request->get('keyword', ''));
            $type = trim((string) $request->get('type', ''));
            $category = trim((string) $request->get('category', ''));
            $status = $request->get('status', '');

            $query = Db::name('shensha')
                ->order('sort', 'asc')
                ->order('id', 'desc');

            if ($keyword !== '') {
                $escapedKeyword = addcslashes($keyword, '%_\\');
                $query->whereLike('name|description|effect', '%' . $escapedKeyword . '%');
            }

            if ($type !== '') {
                if (!in_array($type, self::SHENSHA_TYPES, true)) {
                    return $this->error('神煞类型无效', 400);
                }
                $query->where('type', $type);
            }

            if ($category !== '') {
                if (!in_array($category, self::SHENSHA_CATEGORIES, true)) {
                    return $this->error('神煞分类无效', 400);
                }
                $query->where('category', $category);
            }

            if ($status !== '') {
                $status = (int) $status;
                if (!in_array($status, [0, 1], true)) {
                    return $this->error('神煞状态无效', 400);
                }
                $query->where('status', $status);
            }

            $total = $query->count();
            $rows = $query->page($page, $pageSize)->select()->toArray();

            return $this->success([
                'list' => array_map([$this, 'formatShenshaRow'], $rows),
                'total' => $total,
            ], '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取神煞列表失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'params' => $request->get(),
            ]);
            return $this->error('获取神煞列表失败，请稍后重试', 500);
        }
    }

    /**
     * 神煞选项
     */
    public function shenshaOptions()
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限查看神煞选项', 403);
        }

        try {
            $rows = Db::name('shensha')
                ->where('status', 1)
                ->order('type', 'asc')
                ->order('sort', 'asc')
                ->field('id,name,type,category')
                ->select()
                ->toArray();

            $options = array_map(static function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'value' => $row['name'],
                    'label' => $row['name'],
                    'type' => $row['type'],
                    'category' => $row['category'],
                ];
            }, $rows);

            return $this->success($options, '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取神煞选项失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
            ]);
            return $this->error('获取神煞选项失败，请稍后重试', 500);
        }
    }

    /**
     * 保存神煞
     */
    public function saveShensha(Request $request, int $id = 0)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限维护神煞数据', 403);
        }

        $data = $request->isPut() ? $request->put() : $request->post();
        $id = max($id, (int) ($data['id'] ?? 0));
        $name = trim((string) ($data['name'] ?? ''));
        $type = trim((string) ($data['type'] ?? 'ji'));
        $category = trim((string) ($data['category'] ?? 'guiren'));
        $description = trim((string) ($data['description'] ?? ''));
        $effect = trim((string) ($data['effect'] ?? ''));
        $checkRule = trim((string) ($data['checkRule'] ?? ($data['check_rule'] ?? '')));
        $checkCode = trim((string) ($data['checkCode'] ?? ($data['check_code'] ?? '')));
        $sort = max(0, (int) ($data['sort'] ?? 0));
        $status = isset($data['status']) ? (int) $data['status'] : 1;
        $ganRules = $this->normalizeJsonArrayInput($data['ganRules'] ?? ($data['gan_rules'] ?? []));
        $zhiRules = $this->normalizeJsonArrayInput($data['zhiRules'] ?? ($data['zhi_rules'] ?? []));

        if ($name === '' || $description === '' || $checkRule === '') {
            return $this->error('神煞名称、含义说明和查法规则不能为空', 400);
        }
        if (!in_array($type, self::SHENSHA_TYPES, true)) {
            return $this->error('神煞类型无效', 400);
        }
        if (!in_array($category, self::SHENSHA_CATEGORIES, true)) {
            return $this->error('神煞分类无效', 400);
        }
        if (!in_array($status, [0, 1], true)) {
            return $this->error('神煞状态无效', 400);
        }

        try {
            $duplicateQuery = Db::name('shensha')->where('name', $name);
            if ($id > 0) {
                $duplicateQuery->where('id', '<>', $id);
            }
            if ($duplicateQuery->find()) {
                return $this->error('神煞名称已存在', 400);
            }

            $existing = $id > 0 ? Db::name('shensha')->where('id', $id)->find() : null;
            if ($id > 0 && !$existing) {
                return $this->error('神煞不存在', 404);
            }

            $payload = [
                'name' => $name,
                'type' => $type,
                'category' => $category,
                'description' => $description,
                'effect' => $effect,
                'check_rule' => $checkRule,
                'check_code' => $checkCode,
                'gan_rules' => empty($ganRules) ? null : json_encode($ganRules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'zhi_rules' => empty($zhiRules) ? null : json_encode($zhiRules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'sort' => $sort,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                Db::name('shensha')->where('id', $id)->update($payload);
            } else {
                $payload['created_at'] = date('Y-m-d H:i:s');
                $id = (int) Db::name('shensha')->insertGetId($payload);
            }

            $saved = Db::name('shensha')->where('id', $id)->find();
            $afterData = $saved ? $this->formatShenshaRow($saved) : ['id' => $id, 'name' => $name];

            $this->logOperation('save_shensha', 'content', [
                'target_id' => $id,
                'target_type' => 'shensha',
                'detail' => ($existing ? '更新' : '新增') . '神煞: ' . $name,
                'before_data' => $existing ?: [],
                'after_data' => $afterData,
            ]);

            return $this->success($afterData, '保存成功');
        } catch (\Throwable $e) {
            Log::error('保存神煞失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'id' => $id,
                'name' => $name,
            ]);
            return $this->error('保存神煞失败，请稍后重试', 500);
        }
    }

    /**
     * 删除神煞
     */
    public function deleteShensha(int $id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限删除神煞数据', 403);
        }

        try {
            $existing = Db::name('shensha')->where('id', $id)->find();
            if (!$existing) {
                return $this->error('神煞不存在', 404);
            }

            Db::name('shensha')->where('id', $id)->delete();

            $this->logOperation('delete_shensha', 'content', [
                'target_id' => $id,
                'target_type' => 'shensha',
                'detail' => '删除神煞: ' . $existing['name'],
                'before_data' => $existing,
            ]);

            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            Log::error('删除神煞失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'id' => $id,
            ]);
            return $this->error('删除神煞失败，请稍后重试', 500);
        }
    }

    /**
     * 格式化神煞行数据
     */
    protected function formatShenshaRow(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => $row['name'] ?? '',
            'type' => $row['type'] ?? '',
            'category' => $row['category'] ?? '',
            'description' => $row['description'] ?? '',
            'effect' => $row['effect'] ?? '',
            'checkRule' => $row['check_rule'] ?? '',
            'checkCode' => $row['check_code'] ?? '',
            'ganRules' => $this->normalizeJsonArrayInput($row['gan_rules'] ?? ''),
            'zhiRules' => $this->normalizeJsonArrayInput($row['zhi_rules'] ?? ''),
            'sort' => (int) ($row['sort'] ?? 0),
            'status' => (int) ($row['status'] ?? 0),
            'created_at' => $row['created_at'] ?? '',
            'updated_at' => $row['updated_at'] ?? '',
        ];
    }

    /**
     * 解析数组或 JSON 输入
     */
    protected function normalizeJsonArrayInput($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return [];
        }

        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 阳历转农历（简化版）
     */
    private function solarToLunar($year, $month, $day)
    {
        // 这里使用简化的算法，实际项目中可以使用专业的农历库
        // 如 overtrue/chinese-calendar
        
        $gan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $zhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        $zhiri = ['建', '除', '满', '平', '定', '执', '破', '危', '成', '收', '开', '闭'];
        
        // 计算日干支（简化算法）
        $baseDate = strtotime('1900-01-31'); // 1900年正月初一
        $targetDate = strtotime("{$year}-{$month}-{$day}");
        $daysDiff = floor(($targetDate - $baseDate) / 86400);
        
        $ganIndex = $daysDiff % 10;
        $zhiIndex = $daysDiff % 12;
        
        // 计算年月干支（简化）
        $yearGanIndex = ($year - 4) % 10;
        $yearZhiIndex = ($year - 4) % 12;
        
        // 宜忌事项
        $commonYi = ['祭祀', '祈福', '出行', '结婚', '开业', '动土', '安床', '纳财'];
        $commonJi = ['安葬', '破土', '开仓', '出货', '词讼', '求医'];
        
        // 根据日干支生成宜忌
        $dayGan = $gan[$ganIndex];
        $yi = [];
        $ji = [];
        
        // 简单规则：根据天干决定宜忌
        if (in_array($dayGan, ['甲', '乙', '丙', '丁'])) {
            $yi = array_slice($commonYi, 0, 4);
            $ji = array_slice($commonJi, 0, 2);
        } else {
            $yi = array_slice($commonYi, 2, 4);
            $ji = array_slice($commonJi, 2, 2);
        }
        
        return [
            'lunar_date' => '农历' . ($month) . '月' . $day,
            'ganzhi' => $gan[$yearGanIndex] . $zhi[$yearZhiIndex] . '年 ' . $gan[$ganIndex] . $zhi[$zhiIndex] . '日',
            'yi' => $yi,
            'ji' => $ji,
            'jishen' => ['天德', '月德'],
            'xiongsha' => ['五虚', '土符'],
            'sha' => ['东', '南', '西', '北'][$day % 4],
            'zhiri' => $zhiri[$day % 12],
            'shichen' => [
                ['name' => '子', 'time' => '23:00-01:00', 'type' => 'ji', 'yiji' => '宜祭祀 忌动土'],
                ['name' => '丑', 'time' => '01:00-03:00', 'type' => 'xiaoJi', 'yiji' => '宜安床 忌出行'],
                ['name' => '寅', 'time' => '03:00-05:00', 'type' => 'ping', 'yiji' => '平'],
                ['name' => '卯', 'time' => '05:00-07:00', 'type' => 'ji', 'yiji' => '宜出行 忌动土'],
                ['name' => '辰', 'time' => '07:00-09:00', 'type' => 'xiong', 'yiji' => '忌出行'],
                ['name' => '巳', 'time' => '09:00-11:00', 'type' => 'ping', 'yiji' => '平'],
                ['name' => '午', 'time' => '11:00-13:00', 'type' => 'ji', 'yiji' => '宜会友 忌词讼'],
                ['name' => '未', 'time' => '13:00-15:00', 'type' => 'xiaoJi', 'yiji' => '宜求财'],
                ['name' => '申', 'time' => '15:00-17:00', 'type' => 'ping', 'yiji' => '平'],
                ['name' => '酉', 'time' => '17:00-19:00', 'type' => 'ji', 'yiji' => '宜嫁娶'],
                ['name' => '戌', 'time' => '19:00-21:00', 'type' => 'xiong', 'yiji' => '忌安床'],
                ['name' => '亥', 'time' => '21:00-23:00', 'type' => 'ping', 'yiji' => '平'],
            ],
        ];
    }

    /**
     * 文章列表
     */
    public function articleList(Request $request)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限查看文章', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE)
            );
            $keyword = $request->get('keyword', '');
            $categoryId = $request->get('category_id', 0);

            $query = Db::name('tc_article')->alias('a')
                ->join('tc_article_category c', 'a.category_id = c.id', 'left')
                ->field('a.*, c.name as category_name')
                ->order('a.created_at', 'desc');

            if ($keyword) {
                $query->whereLike('a.title|a.summary', '%' . $keyword . '%');
            }

            if ($categoryId) {
                $query->where('a.category_id', $categoryId);
            }

            $total = $query->count();
            $list = $query->page($pagination['page'], $pagination['pageSize'])->select()->toArray();

            return $this->success([
                'list' => $list,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            Log::error('获取文章列表失败: ' . $e->getMessage());
            return $this->error('获取文章列表失败', 500);
        }
    }

    /**
     * 文章详情
     */
    public function articleDetail($id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限查看文章', 403);
        }

        try {
            $article = Db::table('tc_article')
                ->alias('a')
                ->leftJoin('tc_article_category c', 'a.category_id = c.id')
                ->field('a.*, c.name as category_name, c.parent_id as category_parent_id')
                ->where('a.id', (int) $id)
                ->find();
            if (!$article) {
                return $this->error('文章不存在', 404);
            }

            $categoryPayload = $this->buildArticleCategoryPayload();
            $article['category_path'] = $this->resolveArticleCategoryPath((int) ($article['category_id'] ?? 0), $categoryPayload['map']);
            return $this->success($article);
        } catch (\Throwable $e) {
            Log::error('获取文章详情失败: ' . $e->getMessage());
            return $this->error('获取文章详情失败', 500);
        }
    }

    /**
     * 保存文章
     */
    public function saveArticle(Request $request)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限编辑内容', 403);
        }

        $data = $request->post();
        $title = trim((string) ($data['title'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));
        if ($title === '' || $content === '') {
            return $this->error('标题和内容不能为空', 400);
        }

        try {
            $category = $this->resolveArticleCategoryForWrite((int) ($data['category_id'] ?? 0));
            if (!$category) {
                return $this->error('请选择有效且已启用的分类', 400);
            }

            $slug = $this->sanitizeArticleSlug((string) ($data['slug'] ?? ''), $title, 'article_');
            if (!$this->isUniqueArticleSlug($slug)) {
                return $this->error('文章标识已存在，请更换 slug', 400);
            }

            $status = $this->normalizeArticleStatusValue($data['status'] ?? 1);
            if ($status === null) {
                return $this->error('文章状态参数无效', 400);
            }

            $saveData = [
                'category_id' => (int) $category['id'],
                'title' => $title,
                'slug' => $slug,
                'summary' => trim((string) ($data['summary'] ?? '')),
                'content' => $content,
                'thumbnail' => trim((string) ($data['thumbnail'] ?? '')),
                'status' => $status,
                'is_hot' => $this->normalizeBoolNumber($data['is_hot'] ?? 0),
                'author_id' => $this->getOperatorId(),
                'author_name' => (string) ($this->adminName ?? ''),
                'published_at' => in_array($status, [1, 2], true)
                    ? trim((string) ($data['published_at'] ?? '')) ?: date('Y-m-d H:i:s')
                    : null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $id = Db::table('tc_article')->insertGetId($saveData);
            return $this->success([
                'id' => $id,
                'category_id' => (int) $category['id'],
                'category_name' => (string) $category['name'],
                'slug' => $slug,
            ], '保存成功');
        } catch (\Throwable $e) {
            Log::error('保存文章失败: ' . $e->getMessage());
            return $this->error('保存失败，请稍后重试', 500);
        }
    }

    /**
     * 更新文章
     */
    public function updateArticle(Request $request, $id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限编辑内容', 403);
        }

        $articleId = (int) $id;
        $article = Db::table('tc_article')->where('id', $articleId)->find();
        if (!$article) {
            return $this->error('文章不存在', 404);
        }

        $data = $request->post();
        try {
            $updateData = [];

            if (array_key_exists('title', $data)) {
                $title = trim((string) $data['title']);
                if ($title === '') {
                    return $this->error('标题不能为空', 400);
                }
                $updateData['title'] = $title;
            }
            if (array_key_exists('summary', $data)) {
                $updateData['summary'] = trim((string) $data['summary']);
            }
            if (array_key_exists('content', $data)) {
                $content = trim((string) $data['content']);
                if ($content === '') {
                    return $this->error('内容不能为空', 400);
                }
                $updateData['content'] = $content;
            }
            if (array_key_exists('thumbnail', $data)) {
                $updateData['thumbnail'] = trim((string) $data['thumbnail']);
            }
            if (array_key_exists('is_hot', $data)) {
                $updateData['is_hot'] = $this->normalizeBoolNumber($data['is_hot']);
            }
            if (array_key_exists('status', $data)) {
                $status = $this->normalizeArticleStatusValue($data['status']);
                if ($status === null) {
                    return $this->error('文章状态参数无效', 400);
                }
                $updateData['status'] = $status;
                $updateData['published_at'] = in_array($status, [1, 2], true)
                    ? trim((string) ($data['published_at'] ?? ($article['published_at'] ?? ''))) ?: date('Y-m-d H:i:s')
                    : null;
            }
            if (array_key_exists('category_id', $data)) {
                $category = $this->resolveArticleCategoryForWrite((int) $data['category_id']);
                if (!$category) {
                    return $this->error('请选择有效且已启用的分类', 400);
                }
                $updateData['category_id'] = (int) $category['id'];
            }
            if (array_key_exists('slug', $data) || array_key_exists('title', $data)) {
                $slug = $this->sanitizeArticleSlug(
                    (string) ($data['slug'] ?? ($article['slug'] ?? '')),
                    (string) ($updateData['title'] ?? $article['title'] ?? ''),
                    'article_'
                );
                if (!$this->isUniqueArticleSlug($slug, $articleId)) {
                    return $this->error('文章标识已存在，请更换 slug', 400);
                }
                $updateData['slug'] = $slug;
            }

            if (empty($updateData)) {
                return $this->error('没有可更新的字段', 400);
            }

            $updateData['updated_at'] = date('Y-m-d H:i:s');
            Db::table('tc_article')->where('id', $articleId)->update($updateData);
            return $this->success(null, '更新成功');
        } catch (\Throwable $e) {
            Log::error('更新文章失败: ' . $e->getMessage(), ['article_id' => $articleId]);
            return $this->error('更新失败，请稍后重试', 500);
        }
    }

    /**
     * 删除文章
     */
    public function deleteArticle($id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限删除内容', 403);
        }

        try {
            $deleted = Db::table('tc_article')->where('id', (int) $id)->delete();
            if ($deleted === 0) {
                return $this->error('文章不存在', 404);
            }

            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            Log::error('删除文章失败: ' . $e->getMessage(), ['article_id' => (int) $id]);
            return $this->error('删除失败，请稍后重试', 500);
        }
    }

    /**
     * 文章分类列表
     */
    public function articleCategories(Request $request)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限查看分类', 403);
        }

        try {
            $payload = $this->buildArticleCategoryPayload($this->normalizeBoolNumber($request->get('include_disabled', 0)) === 1);
            $selectedId = (int) $request->get('selected_id', 0);

            return $this->success([
                'list' => $payload['list'],
                'tree' => $payload['tree'],
                'selected_path' => $selectedId > 0 ? $this->resolveArticleCategoryPath($selectedId, $payload['map']) : [],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            Log::error('获取文章分类失败: ' . $e->getMessage());
            return $this->error('获取分类失败', 500);
        }
    }

    /**
     * 保存分类
     */
    public function saveArticleCategory(Request $request)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限编辑内容', 403);
        }

        $data = $request->post();
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return $this->error('分类名称不能为空', 400);
        }

        $categoryId = (int) ($data['id'] ?? 0);
        $parentId = max(0, (int) ($data['parent_id'] ?? 0));
        if ($categoryId > 0 && $categoryId === $parentId) {
            return $this->error('父分类不能选择自己', 400);
        }

        try {
            if ($parentId > 0) {
                $parent = Db::table('tc_article_category')->where('id', $parentId)->find();
                if (!$parent) {
                    return $this->error('父分类不存在', 404);
                }
            }

            $slug = $this->sanitizeArticleSlug((string) ($data['slug'] ?? ''), $name, 'category_');
            if (!$this->isUniqueArticleCategorySlug($slug, $categoryId)) {
                return $this->error('分类标识已存在，请更换 slug', 400);
            }

            $saveData = [
                'name' => $name,
                'slug' => $slug,
                'description' => trim((string) ($data['description'] ?? '')),
                'parent_id' => $parentId,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'status' => $this->normalizeArticleCategoryStatus($data['status'] ?? 1),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($categoryId > 0) {
                $exists = Db::table('tc_article_category')->where('id', $categoryId)->find();
                if (!$exists) {
                    return $this->error('分类不存在', 404);
                }
                Db::table('tc_article_category')->where('id', $categoryId)->update($saveData);
            } else {
                $saveData['created_at'] = date('Y-m-d H:i:s');
                $categoryId = (int) Db::table('tc_article_category')->insertGetId($saveData);
            }

            $payload = $this->buildArticleCategoryPayload(true);
            return $this->success([
                'id' => $categoryId,
                'slug' => $slug,
                'categories' => $payload,
            ], '保存成功');
        } catch (\Throwable $e) {
            Log::error('保存文章分类失败: ' . $e->getMessage(), ['category_id' => $categoryId]);
            return $this->error('操作失败，请稍后重试', 500);
        }
    }

    /**
     * 删除分类
     */
    public function deleteArticleCategory($id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限删除内容', 403);
        }

        $categoryId = (int) $id;
        try {
            $category = Db::table('tc_article_category')->where('id', $categoryId)->find();
            if (!$category) {
                return $this->error('分类不存在', 404);
            }

            $articleCount = Db::table('tc_article')->where('category_id', $categoryId)->count();
            if ($articleCount > 0) {
                return $this->error('该分类下尚有文章，无法删除', 400);
            }

            $childCount = Db::table('tc_article_category')->where('parent_id', $categoryId)->count();
            if ($childCount > 0) {
                return $this->error('该分类下仍有子分类，无法删除', 400);
            }

            Db::table('tc_article_category')->where('id', $categoryId)->delete();
            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            Log::error('删除文章分类失败: ' . $e->getMessage(), ['category_id' => $categoryId]);
            return $this->error('删除失败，请稍后重试', 500);
        }
    }

    /**
     * 校验并返回可写入的文章分类
     */
    protected function resolveArticleCategoryForWrite(int $categoryId): ?array
    {
        if ($categoryId <= 0) {
            return null;
        }

        $category = Db::table('tc_article_category')->where('id', $categoryId)->find();
        if (!$category) {
            return null;
        }

        return (int) ($category['status'] ?? 0) === 1 ? $category : null;
    }

    /**
     * 构建文章分类联动载荷
     */
    protected function buildArticleCategoryPayload(bool $includeDisabled = false): array
    {
        $query = Db::table('tc_article_category')
            ->alias('c')
            ->leftJoin('tc_article a', 'a.category_id = c.id')
            ->field([
                'c.id',
                'c.name',
                'c.slug',
                'c.description',
                'c.parent_id',
                'c.sort_order',
                'c.status',
                'c.created_at',
                'c.updated_at',
                'COUNT(a.id) AS article_count',
            ])
            ->group('c.id')
            ->order('c.sort_order', 'asc')
            ->order('c.id', 'asc');

        if (!$includeDisabled) {
            $query->where('c.status', 1);
        }

        $rows = $query->select()->toArray();
        $list = array_map(function (array $row): array {
            $row['id'] = (int) ($row['id'] ?? 0);
            $row['parent_id'] = (int) ($row['parent_id'] ?? 0);
            $row['status'] = (int) ($row['status'] ?? 0);
            $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
            $row['article_count'] = (int) ($row['article_count'] ?? 0);
            return $row;
        }, $rows);

        $map = [];
        foreach ($list as $item) {
            $map[(int) $item['id']] = $item;
        }

        return [
            'list' => $list,
            'tree' => $this->buildArticleCategoryTree($list),
            'map' => $map,
        ];
    }

    /**
     * 组装文章分类树
     */
    protected function buildArticleCategoryTree(array $list): array
    {
        $treeMap = [];
        foreach ($list as $item) {
            $item['children'] = [];
            $treeMap[(int) $item['id']] = $item;
        }

        $tree = [];
        foreach ($treeMap as $id => &$item) {
            $parentId = (int) ($item['parent_id'] ?? 0);
            if ($parentId > 0 && isset($treeMap[$parentId])) {
                $treeMap[$parentId]['children'][] = &$item;
                continue;
            }

            $tree[] = &$item;
        }
        unset($item);

        return array_values($tree);
    }

    /**
     * 解析分类路径，供编辑页快速跳转使用
     */
    protected function resolveArticleCategoryPath(int $categoryId, array $categoryMap): array
    {
        $path = [];
        $visited = [];
        while ($categoryId > 0 && isset($categoryMap[$categoryId]) && !isset($visited[$categoryId])) {
            $visited[$categoryId] = true;
            $current = $categoryMap[$categoryId];
            array_unshift($path, [
                'id' => (int) ($current['id'] ?? 0),
                'name' => (string) ($current['name'] ?? ''),
                'slug' => (string) ($current['slug'] ?? ''),
            ]);
            $categoryId = (int) ($current['parent_id'] ?? 0);
        }

        return $path;
    }

    /**
     * 归一化文章状态
     */
    protected function normalizeArticleStatusValue(mixed $status): ?int
    {
        $status = (int) $status;
        return in_array($status, [0, 1, 2, 3], true) ? $status : null;
    }

    /**
     * 归一化分类状态
     */
    protected function normalizeArticleCategoryStatus(mixed $status): int
    {
        return (int) ((int) $status === 1);
    }

    /**
     * 归一化布尔型数值字段
     */
    protected function normalizeBoolNumber(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (int) ((int) $value !== 0);
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }

    /**
     * 生成安全 slug
     */
    protected function sanitizeArticleSlug(string $slug, string $fallback, string $prefix = ''): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-_]+/i', '-', $slug) ?: '';
        $slug = trim($slug, '-_');

        if ($slug === '') {
            $base = strtolower(trim($fallback));
            $base = preg_replace('/[^a-z0-9\-_]+/i', '-', $base) ?: '';
            $base = trim($base, '-_');
            $slug = $base !== '' ? $base : $prefix . time();
        }

        return $slug;
    }

    /**
     * 校验文章 slug 唯一性
     */
    protected function isUniqueArticleSlug(string $slug, int $excludeId = 0): bool
    {
        $query = Db::table('tc_article')->where('slug', $slug);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }

        return $query->count() === 0;
    }

    /**
     * 校验分类 slug 唯一性
     */
    protected function isUniqueArticleCategorySlug(string $slug, int $excludeId = 0): bool
    {
        $query = Db::table('tc_article_category')->where('slug', $slug);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }

        return $query->count() === 0;
    }
}

