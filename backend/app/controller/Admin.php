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
    protected const SEO_CHANGE_FREQUENCIES = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
    protected const SEO_ENGINES = ['baidu', 'bing', '360', 'sogou'];
    protected const SEO_SUBMIT_TYPES = ['url', 'sitemap'];
    
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
     * 记录操作日志
     */
    protected function logOperation(
        string $action, 
        string $module = '', 
        array $data = []
    ): void {
        try {
            AdminLog::record([
                'admin_id' => $this->adminId,
                'admin_name' => $this->adminName,
                'action' => $action,
                'module' => $module,
                'target_id' => $data['target_id'] ?? 0,
                'target_type' => $data['target_type'] ?? '',
                'detail' => $data['detail'] ?? '',
                'before_data' => $data['before_data'] ?? null,
                'after_data' => $data['after_data'] ?? null,
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->header('User-Agent') ?? '',
                'request_url' => $this->request->url(true),
                'request_method' => $this->request->method(),
                'status' => $data['status'] ?? 1,
                'error_msg' => $data['error_msg'] ?? '',
            ]);
        } catch (\Exception $e) {
            Log::error('记录操作日志失败: ' . $e->getMessage());
        }
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
        } catch (\Exception $e) {
            return $this->error('创建失败: ' . $e->getMessage(), 500);
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
            // 从数据库或缓存读取设置
            $settings = [
                'site_name' => '太初命理',
                'logo' => '',
                'site_description' => '专业的命理分析平台',
                'register_points' => 100,
                'checkin_points' => 10,
                'bazi_cost' => 20,
                'tarot_cost' => 10,
                'enable_register' => true,
                'enable_daily' => true,
                'enable_feedback' => true
            ];

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
        
        try {
            $settings = $request->post();
            
            if (empty($settings) || !is_array($settings)) {
                return $this->error('设置数据不能为空', 400);
            }
            
            // 获取操作前的设置用于日志记录
            $configKeys = array_keys($settings);
            $oldSettings = Db::name('system_config')
                ->whereIn('config_key', $configKeys)
                ->column('config_value', 'config_key');
            
            // 批量更新配置
            $updateCount = 0;
            foreach ($settings as $key => $value) {
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
                    // 新增配置项，默认为string类型
                    Db::name('system_config')->insert([
                        'config_key' => $key,
                        'config_value' => is_array($value) ? json_encode($value) : (string)$value,
                        'config_type' => is_array($value) ? 'json' : 'string',
                        'category' => 'custom',
                        'is_editable' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $updateCount++;
                }
            }
            
            // 记录操作日志
            $this->logOperation('update', 'config', [
                'detail' => '更新系统设置',
                'before_data' => $oldSettings,
                'after_data' => $settings,
            ]);

            return $this->success(['updated_count' => $updateCount], '保存成功');
        } catch (\Exception $e) {
            Log::error('保存系统设置失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'settings' => $settings ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('保存失败，请稍后重试', 500);
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
     * 获取系统公告列表
     */
    public function getNotices(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限查看公告', 403);
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
            $status = $request->get('status', '');

            $rows = Db::name('system_config')
                ->where('category', self::CATEGORY_SYSTEM_NOTICE)
                ->order('created_at', 'desc')
                ->select()
                ->toArray();
            $list = [];
            foreach ($rows as $row) {
                $item = $this->decodeConfigJson($row['config_value']);
                $itemStatus = (int) ($item['status'] ?? 0);
                if ($status !== '' && $itemStatus !== (int) $status) {
                    continue;
                }
                $list[] = [
                    'id' => $row['id'],
                    'title' => $item['title'] ?? $row['description'],
                    'type' => $item['type'] ?? 'normal',
                    'content' => $item['content'] ?? '',
                    'status' => $itemStatus,
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ];
            }

            $total = count($list);
            $list = array_slice($list, ($page - 1) * $pageSize, $pageSize);

            return $this->success([
                'list' => array_values($list),
                'total' => $total,
            ], '获取成功');

        } catch (\Exception $e) {
            Log::error('获取系统公告失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
            ]);
            return $this->error('获取系统公告失败，请稍后重试', 500);
        }
    }

    /**
     * 保存系统公告
     */
    public function saveNotice(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限保存公告', 403);
        }

        $data = $request->post();
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        $type = trim((string) ($data['type'] ?? 'normal'));
        $content = trim((string) ($data['content'] ?? ''));
        $status = isset($data['status']) ? (int) $data['status'] : 1;

        if ($title === '' || $content === '') {
            return $this->error('公告标题和内容不能为空', 400);
        }
        if (!in_array($type, ['normal', 'important'], true)) {
            return $this->error('公告类型无效', 400);
        }
        if (!in_array($status, [0, 1], true)) {
            return $this->error('公告状态无效', 400);
        }

        try {
            $existing = null;
            if ($id > 0) {
                $existing = Db::name('system_config')
                    ->where('id', $id)
                    ->where('category', self::CATEGORY_SYSTEM_NOTICE)
                    ->find();
                if (!$existing) {
                    return $this->error('公告不存在', 404);
                }
            }

            $payload = [
                'title' => $title,
                'type' => $type,
                'content' => $content,
                'status' => $status,
            ];

            $saveData = [
                'config_value' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'config_type' => 'json',
                'description' => $title,
                'category' => self::CATEGORY_SYSTEM_NOTICE,
                'is_editable' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($id > 0) {
                Db::name('system_config')->where('id', $id)->update($saveData);
            } else {
                $saveData['config_key'] = 'system_notice_' . uniqid('', true);
                $saveData['sort_order'] = 0;
                $saveData['created_at'] = date('Y-m-d H:i:s');
                $id = (int) Db::name('system_config')->insertGetId($saveData);
            }

            $this->logOperation('save_notice', 'config', [
                'target_id' => $id,
                'target_type' => 'notice',
                'detail' => ($existing ? '更新' : '新增') . '系统公告: ' . $title,
                'before_data' => $existing ? $this->decodeConfigJson($existing['config_value']) : [],
                'after_data' => $payload,
            ]);

            return $this->success(['id' => $id] + $payload, '保存成功');
        } catch (\Exception $e) {
            Log::error('保存系统公告失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'id' => $id,
            ]);
            return $this->error('保存系统公告失败，请稍后重试', 500);
        }
    }

    /**
     * 删除系统公告
     */
    public function deleteNotice(int $id)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限删除公告', 403);
        }

        try {
            $existing = Db::name('system_config')
                ->where('id', $id)
                ->where('category', self::CATEGORY_SYSTEM_NOTICE)
                ->find();
            if (!$existing) {
                return $this->error('公告不存在', 404);
            }

            Db::name('system_config')->where('id', $id)->delete();

            $this->logOperation('delete_notice', 'config', [
                'target_id' => $id,
                'target_type' => 'notice',
                'detail' => '删除系统公告: ' . $existing['description'],
                'before_data' => $this->decodeConfigJson($existing['config_value']),
            ]);

            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            Log::error('删除系统公告失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'id' => $id,
            ]);
            return $this->error('删除系统公告失败，请稍后重试', 500);
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

            $query = Db::name('admin')
                ->alias('a')
                ->leftJoin('tc_admin_user_role aur', 'aur.admin_id = a.id')
                ->leftJoin('tc_admin_role r', 'r.id = aur.role_id')
                ->field('a.id,a.username,a.nickname,a.status,a.last_login_at,r.name as role_name,r.code as role_code')
                ->group('a.id')
                ->order('a.id', 'desc');

            if ($keyword !== '') {
                $query->whereLike('a.username|a.nickname', '%' . addcslashes($keyword, '%_\\') . '%');
            }
            if ($status !== '') {
                $query->where('a.status', (int) $status);
            }

            $rows = $query->select()->toArray();
            $total = count($rows);
            $list = array_slice($rows, ($page - 1) * $pageSize, $pageSize);

            foreach ($list as &$item) {
                $item['role_name'] = $item['role_name'] ?: '未分配角色';
                $item['role_code'] = $item['role_code'] ?: '';
                $item['nickname'] = $item['nickname'] ?: $item['username'];
                $item['status'] = (int) ($item['status'] ?? 0);
            }
            unset($item);

            return $this->success([
                'list' => array_values($list),
                'total' => $total,
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取管理员列表失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
            ]);
            return $this->error('获取管理员列表失败，请稍后重试', 500);
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
        
        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE)
            );
            
            $params = $request->get();
            $params['module'] = 'api';
            
            $result = AdminLog::getLogList($params, $pagination['page'], $pagination['pageSize']);
            return $this->success($result, '获取成功');
        } catch (\Exception $e) {
            Log::error('获取API日志失败: ' . $e->getMessage());
            return $this->error('获取失败', 500);
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

        try {
            $list = Db::name('tc_anti_cheat_rule')->select();
            return $this->success($list, '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取规则失败', 500);
        }
    }

    /**
     * 保存/更新反作弊规则
     */
    public function saveRiskRule(Request $request)
    {
        if (!$this->checkPermission('anticheat_manage')) {
            return $this->error('无权限保存规则', 403);
        }

        try {
            $data = $request->post();
            $id = $data['id'] ?? 0;
            unset($data['id']);

            if ($id > 0) {
                Db::name('tc_anti_cheat_rule')->where('id', $id)->update($data);
            } else {
                $id = Db::name('tc_anti_cheat_rule')->insertGetId($data);
            }

            $this->logOperation('save_risk_rule', 'anticheat', [
                'target_id' => $id,
                'detail' => "保存/更新反作弊规则: " . ($data['name'] ?? '')
            ]);

            return $this->success(['id' => $id], '保存成功');
        } catch (\Exception $e) {
            return $this->error('保存失败', 500);
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

        try {
            Db::name('tc_anti_cheat_rule')->where('id', $id)->delete();
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败', 500);
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
        // 检查权限
        if (!$this->checkPermission('feedback_view')) {
            return $this->error('无权限查看反馈', 403);
        }

        try {
            $count = Feedback::where('status', 0)->count();
            $list = Feedback::where('status', 0)
                ->order('created_at', 'desc')
                ->limit(5)
                ->select()
                ->toArray();

            return $this->success([
                'count' => $count,
                'list' => $list
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取待处理反馈失败: ' . $e->getMessage());
            return $this->error('获取待处理反馈失败', 500);
        }
    }

    /**
     * 获取实时数据
     */
    public function realtime()
    {
        // 检查权限
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限访问统计数据', 403);
        }

        try {
            // 获取今日数据
            $todayUsers = User::where('created_at', '>=', date('Y-m-d'))->count();
            $todayBazi = BaziRecord::where('created_at', '>=', date('Y-m-d'))->count();
            $todayTarot = TarotRecord::where('created_at', '>=', date('Y-m-d'))->count();
            $todayFeedback = Feedback::where('created_at', '>=', date('Y-m-d'))->count();

            // 获取在线用户数（最近15分钟内有活动的用户）
            $onlineUsers = User::where('last_active', '>=', date('Y-m-d H:i:s', strtotime('-15 minutes')))->count();

            // 获取待处理反馈数
            $pendingFeedback = Feedback::where('status', 0)->count();

            return $this->success([
                'today_users' => $todayUsers,
                'today_bazi' => $todayBazi,
                'today_tarot' => $todayTarot,
                'today_feedback' => $todayFeedback,
                'online_users' => $onlineUsers,
                'pending_feedback' => $pendingFeedback,
                'timestamp' => date('Y-m-d H:i:s')
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取实时数据失败: ' . $e->getMessage());
            return $this->error('获取实时数据失败', 500);
        }
    }

    /**
     * 导出用户数据
     */
    public function exportUsers(Request $request)
    {
        // 检查权限
        if (!$this->checkPermission('user_manage')) {
            return $this->error('无权限导出用户数据', 403);
        }

        try {
            $params = $request->get();
            $query = User::order('id', 'desc');

            // 搜索条件
            if (!empty($params['keyword'])) {
                // 使用参数绑定防止SQL注入
                $keyword = preg_replace('/[%_\\\\]/', '', $params['keyword']);
                $query->whereLike('nickname|phone', '%' . $keyword . '%');
            }

            // 状态筛选
            if (isset($params['status']) && $params['status'] !== '') {
                $query->where('status', (int)$params['status']);
            }

            // 时间范围
            if (!empty($params['startDate'])) {
                $query->where('created_at', '>=', $params['startDate']);
            }
            if (!empty($params['endDate'])) {
                $query->where('created_at', '<=', $params['endDate'] . ' 23:59:59');
            }

            $users = $query->select()->toArray();

            // 生成CSV内容
            $headers = ['ID', '昵称', '手机号', '积分', 'VIP等级', '状态', '注册时间', '最后登录'];
            $csv = implode(',', $headers) . "\n";

            foreach ($users as $user) {
                $row = [
                    $user['id'],
                    $this->escapeCsv($user['nickname'] ?? ''),
                    $user['phone'] ?? '',
                    $user['points'] ?? 0,
                    $user['vip_level'] ?? 0,
                    $user['status'] == 1 ? '正常' : ($user['status'] == 0 ? '禁用' : '未知'),
                    $user['created_at'] ?? '',
                    $user['last_active'] ?? ''
                ];
                $csv .= implode(',', $row) . "\n";
            }

            // 添加BOM头以支持中文
            $csv = "\xEF\xBB\xBF" . $csv;

            // 记录操作日志
            $this->logOperation('导出用户数据', 'user', [
                'target_id' => 0,
                'detail' => '导出用户数据，共' . count($users) . '条记录'
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
     * 黄历列表
     */
    public function almanacList(Request $request)
    {
        if (!$this->checkPermission('almanac_view')) {
            return $this->error('无权限查看黄历', 403);
        }

        $year = filter_var($request->get('year', date('Y')), FILTER_VALIDATE_INT) ?: (int)date('Y');
        $month = filter_var($request->get('month', date('m')), FILTER_VALIDATE_INT) ?: (int)date('m');

        try {
            $list = Db::name('almanac')
                ->where('solar_date', '>=', "$year-$month-01")
                ->where('solar_date', '<=', "$year-$month-" . cal_days_in_month(CAL_GREGORIAN, $month, $year))
                ->order('solar_date', 'asc')
                ->select()
                ->toArray();

            return $this->success([
                'list' => $list,
                'year' => $year,
                'month' => $month,
            ]);
        } catch (\Exception $e) {
            Log::error('获取黄历列表失败: ' . $e->getMessage());
            return $this->error('获取黄历列表失败', 500);
        }
    }

    /**
     * 保存黄历
     */
    public function saveAlmanac(Request $request)
    {
        if (!$this->checkPermission('almanac_edit')) {
            return $this->error('无权限编辑黄历', 403);
        }

        $data = $request->post();

        // 验证必填字段
        if (empty($data['solar_date'])) {
            return $this->error('阳历日期不能为空', 400);
        }

        try {
            // 检查记录是否存在
            $exists = Db::name('almanac')
                ->where('solar_date', $data['solar_date'])
                ->find();

            $saveData = [
                'solar_date' => $data['solar_date'],
                'lunar_date' => $data['lunar_date'] ?? '',
                'ganzhi' => $data['ganzhi'] ?? '',
                'yi' => is_array($data['yi']) ? json_encode($data['yi'], JSON_UNESCAPED_UNICODE) : ($data['yi'] ?? ''),
                'ji' => is_array($data['ji']) ? json_encode($data['ji'], JSON_UNESCAPED_UNICODE) : ($data['ji'] ?? ''),
                'jishen' => is_array($data['jishen']) ? json_encode($data['jishen'], JSON_UNESCAPED_UNICODE) : ($data['jishen'] ?? ''),
                'xiongsha' => is_array($data['xiongsha']) ? json_encode($data['xiongsha'], JSON_UNESCAPED_UNICODE) : ($data['xiongsha'] ?? ''),
                'sha' => $data['sha'] ?? '',
                'zhiri' => $data['zhiri'] ?? '',
                'shichen' => is_array($data['shichen']) ? json_encode($data['shichen'], JSON_UNESCAPED_UNICODE) : ($data['shichen'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($exists) {
                Db::name('almanac')
                    ->where('solar_date', $data['solar_date'])
                    ->update($saveData);
            } else {
                $saveData['created_at'] = date('Y-m-d H:i:s');
                Db::name('almanac')->insert($saveData);
            }

            // 记录操作日志
            $this->logOperation('保存黄历', 'almanac', [
                'target_id' => $data['solar_date'],
                'detail' => '保存黄历: ' . $data['solar_date']
            ]);

            return $this->success([], '保存成功');
        } catch (\Exception $e) {
            Log::error('保存黄历失败: ' . $e->getMessage());
            return $this->error('保存失败', 500);
        }
    }

    /**
     * 生成月历
     */
    public function generateAlmanacMonth(Request $request)
    {
        if (!$this->checkPermission('almanac_edit')) {
            return $this->error('无权限生成黄历', 403);
        }

        $year = $request->post('year', date('Y'));
        $month = $request->post('month', date('m'));

        if (!is_numeric($year) || !is_numeric($month)) {
            return $this->error('年份和月份必须是数字', 400);
        }

        try {
            // 获取该月所有日期
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);
            $insertData = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                
                // 检查是否已存在
                $exists = Db::name('almanac')
                    ->where('solar_date', $date)
                    ->find();

                if (!$exists) {
                    // 使用 Lunar 类计算农历信息
                    $lunarInfo = $this->solarToLunar($year, $month, $day);
                    
                    $insertData[] = [
                        'solar_date' => $date,
                        'lunar_date' => $lunarInfo['lunar_date'],
                        'ganzhi' => $lunarInfo['ganzhi'],
                        'yi' => json_encode($lunarInfo['yi'], JSON_UNESCAPED_UNICODE),
                        'ji' => json_encode($lunarInfo['ji'], JSON_UNESCAPED_UNICODE),
                        'jishen' => json_encode($lunarInfo['jishen'], JSON_UNESCAPED_UNICODE),
                        'xiongsha' => json_encode($lunarInfo['xiongsha'], JSON_UNESCAPED_UNICODE),
                        'sha' => $lunarInfo['sha'],
                        'zhiri' => $lunarInfo['zhiri'],
                        'shichen' => json_encode($lunarInfo['shichen'], JSON_UNESCAPED_UNICODE),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }

            if (!empty($insertData)) {
                // 批量插入
                Db::name('almanac')->insertAll($insertData);
            }

            // 记录操作日志
            $this->logOperation('生成月历', 'almanac', [
                'target_id' => 0,
                'detail' => "生成 {$year}年{$month}月 黄历，共" . count($insertData) . "条"
            ]);

            return $this->success([
                'generated' => count($insertData),
            ], '生成成功');
        } catch (\Exception $e) {
            Log::error('生成月历失败: ' . $e->getMessage());
            return $this->error('生成失败', 500);
        }
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
     * SEO 配置列表
     */
    public function seoConfigList(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限查看SEO配置', 403);
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
            $isActive = $request->get('is_active', '');

            $query = Db::name('seo_config')
                ->order('priority', 'desc')
                ->order('updated_at', 'desc');

            if ($keyword !== '') {
                $escapedKeyword = addcslashes($keyword, '%_\\');
                $query->whereLike('route|title|description', '%' . $escapedKeyword . '%');
            }

            if ($isActive !== '') {
                $activeValue = (int) $isActive;
                if (!in_array($activeValue, [0, 1], true)) {
                    return $this->error('SEO状态无效', 400);
                }
                $query->where('is_active', $activeValue);
            }

            $total = $query->count();
            $rows = $query->page($page, $pageSize)->select()->toArray();
            $activeRows = Db::name('seo_config')
                ->where('is_active', 1)
                ->field('route,updated_at')
                ->select()
                ->toArray();
            $lastModified = '';
            foreach ($activeRows as $row) {
                if (($row['updated_at'] ?? '') > $lastModified) {
                    $lastModified = (string) $row['updated_at'];
                }
            }

            $xmlSize = 128;
            foreach ($activeRows as $row) {
                $xmlSize += strlen((string) ($row['route'] ?? '')) + 128;
            }
            $fileSize = number_format($xmlSize / 1024, 1) . ' KB';

            return $this->success([
                'list' => array_map([$this, 'formatSeoConfigRow'], $rows),
                'total' => $total,
                'sitemap' => [
                    'lastModified' => $lastModified ?: date('Y-m-d H:i:s'),
                    'urlCount' => count($activeRows),
                    'fileSize' => $fileSize,
                    'baiduIndexed' => Db::name('seo_submissions')
                        ->where('engine', 'baidu')
                        ->where('type', 'sitemap')
                        ->where('status', 'success')
                        ->count() > 0,
                ],
                'submitStatus' => $this->formatSeoSubmitStatus(),
            ], '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取SEO配置失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'params' => $request->get(),
            ]);
            return $this->error('获取SEO配置失败，请稍后重试', 500);
        }
    }

    /**
     * 保存 SEO 配置
     */
    public function saveSeoConfig(Request $request, int $id = 0)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限维护SEO配置', 403);
        }

        $data = $request->isPut() ? $request->put() : $request->post();
        $id = max($id, (int) ($data['id'] ?? 0));
        $route = trim((string) ($data['route'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $keywords = $this->normalizeSeoKeywords($data['keywords'] ?? []);
        $image = trim((string) ($data['image'] ?? ''));
        $robots = trim((string) ($data['robots'] ?? 'index,follow'));
        $ogType = trim((string) ($data['og_type'] ?? ($data['ogType'] ?? 'website')));
        $canonical = trim((string) ($data['canonical'] ?? ''));
        $priority = filter_var($data['priority'] ?? 0.5, FILTER_VALIDATE_FLOAT);
        $priority = $priority === false ? 0.5 : max(0, min(1, (float) $priority));
        $changefreq = trim((string) ($data['changefreq'] ?? 'weekly'));
        $isActive = isset($data['is_active']) ? (int) $data['is_active'] : (isset($data['isActive']) ? (int) $data['isActive'] : 1);

        if ($route === '' || !str_starts_with($route, '/')) {
            return $this->error('页面路由必须以 / 开头', 400);
        }
        if ($title === '' || $description === '') {
            return $this->error('SEO标题和描述不能为空', 400);
        }
        if (empty($keywords)) {
            return $this->error('至少需要填写一个关键词', 400);
        }
        if (!in_array($robots, ['index,follow', 'noindex,follow', 'noindex,nofollow'], true)) {
            return $this->error('Robots配置无效', 400);
        }
        if (!in_array($changefreq, self::SEO_CHANGE_FREQUENCIES, true)) {
            return $this->error('更新频率无效', 400);
        }
        if (!in_array($isActive, [0, 1], true)) {
            return $this->error('SEO状态无效', 400);
        }

        try {
            $duplicateQuery = Db::name('seo_config')->where('route', $route);
            if ($id > 0) {
                $duplicateQuery->where('id', '<>', $id);
            }
            if ($duplicateQuery->find()) {
                return $this->error('该页面路由的SEO配置已存在', 400);
            }

            $existing = $id > 0 ? Db::name('seo_config')->where('id', $id)->find() : null;
            if ($id > 0 && !$existing) {
                return $this->error('SEO配置不存在', 404);
            }

            $payload = [
                'route' => $route,
                'title' => $title,
                'description' => $description,
                'keywords' => json_encode($keywords, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'image' => $image,
                'robots' => $robots,
                'og_type' => $ogType === '' ? 'website' : $ogType,
                'canonical' => $canonical,
                'priority' => $priority,
                'changefreq' => $changefreq,
                'is_active' => $isActive,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                Db::name('seo_config')->where('id', $id)->update($payload);
            } else {
                $payload['created_at'] = date('Y-m-d H:i:s');
                $id = (int) Db::name('seo_config')->insertGetId($payload);
            }

            $saved = Db::name('seo_config')->where('id', $id)->find();
            $afterData = $saved ? $this->formatSeoConfigRow($saved) : ['id' => $id, 'route' => $route];

            $this->logOperation('save_seo_config', 'config', [
                'target_id' => $id,
                'target_type' => 'seo_config',
                'detail' => ($existing ? '更新' : '新增') . 'SEO配置: ' . $route,
                'before_data' => $existing ?: [],
                'after_data' => $afterData,
            ]);

            return $this->success($afterData, '保存成功');
        } catch (\Throwable $e) {
            Log::error('保存SEO配置失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'id' => $id,
                'route' => $route,
            ]);
            return $this->error('保存SEO配置失败，请稍后重试', 500);
        }
    }

    /**
     * 删除 SEO 配置
     */
    public function deleteSeoConfig(int $id)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限删除SEO配置', 403);
        }

        try {
            $existing = Db::name('seo_config')->where('id', $id)->find();
            if (!$existing) {
                return $this->error('SEO配置不存在', 404);
            }

            Db::name('seo_config')->where('id', $id)->delete();

            $this->logOperation('delete_seo_config', 'config', [
                'target_id' => $id,
                'target_type' => 'seo_config',
                'detail' => '删除SEO配置: ' . $existing['route'],
                'before_data' => $existing,
            ]);

            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            Log::error('删除SEO配置失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'id' => $id,
            ]);
            return $this->error('删除SEO配置失败，请稍后重试', 500);
        }
    }

    /**
     * SEO 统计数据
     */
    public function seoStats(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限查看SEO统计', 403);
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
            $pageKeyword = trim((string) $request->get('keyword', ''));

            $baiduIndexed = (int) Db::name('seo_indexed_pages')->where('baidu_status', 'indexed')->count();
            $bingIndexed = (int) Db::name('seo_indexed_pages')->where('bing_status', 'indexed')->count();
            $baiduRecent = (int) Db::name('seo_indexed_pages')
                ->where('baidu_status', 'indexed')
                ->where('indexed_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->count();
            $baiduPrevious = (int) Db::name('seo_indexed_pages')
                ->where('baidu_status', 'indexed')
                ->where('indexed_at', '>=', date('Y-m-d H:i:s', strtotime('-14 days')))
                ->where('indexed_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->count();
            $bingRecent = (int) Db::name('seo_indexed_pages')
                ->where('bing_status', 'indexed')
                ->where('indexed_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->count();
            $bingPrevious = (int) Db::name('seo_indexed_pages')
                ->where('bing_status', 'indexed')
                ->where('indexed_at', '>=', date('Y-m-d H:i:s', strtotime('-14 days')))
                ->where('indexed_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->count();

            $allKeywordRows = Db::name('seo_keywords')
                ->where('is_target', 1)
                ->order('baidu_rank', 'asc')
                ->order('bing_rank', 'asc')
                ->select()
                ->toArray();
            $keywordTotal = count($allKeywordRows);
            $keywordTop10 = 0;
            foreach ($allKeywordRows as $row) {
                $baiduRank = (int) ($row['baidu_rank'] ?? 0);
                $bingRank = (int) ($row['bing_rank'] ?? 0);
                if (($baiduRank >= 1 && $baiduRank <= 10) || ($bingRank >= 1 && $bingRank <= 10)) {
                    $keywordTop10++;
                }
            }
            $keywordRows = array_slice($allKeywordRows, 0, 50);

            $trafficCurrent = (int) Db::name('seo_traffic_daily')
                ->where('stat_date', '>=', date('Y-m-d', strtotime('-30 days')))
                ->sum('organic_sessions');
            $trafficPrevious = (int) Db::name('seo_traffic_daily')
                ->where('stat_date', '>=', date('Y-m-d', strtotime('-60 days')))
                ->where('stat_date', '<', date('Y-m-d', strtotime('-30 days')))
                ->sum('organic_sessions');

            $pageQuery = Db::name('seo_indexed_pages')->order('updated_at', 'desc');
            if ($pageKeyword !== '') {
                $escapedKeyword = addcslashes($pageKeyword, '%_\\');
                $pageQuery->whereLike('url|title|page_route', '%' . $escapedKeyword . '%');
            }
            $pageTotal = $pageQuery->count();
            $pageRows = $pageQuery->page($page, $pageSize)->select()->toArray();

            $configRows = Db::name('seo_config')->select()->toArray();
            $pendingPages = 0;
            $allIndexedRows = Db::name('seo_indexed_pages')->field('baidu_status,bing_status')->select()->toArray();
            foreach ($allIndexedRows as $indexedRow) {
                if (($indexedRow['baidu_status'] ?? '') !== 'indexed' || ($indexedRow['bing_status'] ?? '') !== 'indexed') {
                    $pendingPages++;
                }
            }

            return $this->success([
                'stats' => [
                    'baidu' => [
                        'indexed' => $baiduIndexed,
                        'trend' => $this->calculatePercentageTrend($baiduRecent, $baiduPrevious),
                    ],
                    'bing' => [
                        'indexed' => $bingIndexed,
                        'trend' => $this->calculatePercentageTrend($bingRecent, $bingPrevious),
                    ],
                    'keywords' => [
                        'total' => $keywordTotal,
                        'top10' => $keywordTop10,
                    ],
                    'traffic' => [
                        'organic' => $trafficCurrent,
                        'trend' => $this->calculatePercentageTrend($trafficCurrent, $trafficPrevious),
                    ],
                ],
                'keywords' => array_map([$this, 'formatSeoKeywordRow'], $keywordRows),
                'pages' => [
                    'list' => array_map([$this, 'formatSeoIndexedPageRow'], $pageRows),
                    'total' => $pageTotal,
                    'page' => $page,
                    'pageSize' => $pageSize,
                ],
                'suggestions' => $this->buildSeoSuggestions($configRows, $keywordTotal, $keywordTop10, $pendingPages),
                'submissions' => Db::name('seo_submissions')
                    ->order('submitted_at', 'desc')
                    ->limit(10)
                    ->select()
                    ->toArray(),
            ], '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取SEO统计失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'params' => $request->get(),
            ]);
            return $this->error('获取SEO统计失败，请稍后重试', 500);
        }
    }

    /**
     * 获取 robots.txt 配置
     */
    public function seoRobots()
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限查看robots配置', 403);
        }

        try {
            $rows = Db::name('seo_robots')
                ->where('is_active', 1)
                ->order('sort_order', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            return $this->success([
                'content' => $this->buildRobotsContent($rows),
                'list' => array_map(function (array $row): array {
                    return [
                        'id' => (int) $row['id'],
                        'user_agent' => $row['user_agent'],
                        'rules' => $this->normalizeRobotsRulePayload($row['rules'] ?? ''),
                        'crawl_delay' => (int) ($row['crawl_delay'] ?? 0),
                        'sitemap_url' => $row['sitemap_url'] ?? '',
                        'sort_order' => (int) ($row['sort_order'] ?? 0),
                        'updated_at' => $row['updated_at'] ?? '',
                    ];
                }, $rows),
                'updated_at' => empty($rows) ? '' : max(array_column($rows, 'updated_at')),
            ], '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取robots配置失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
            ]);
            return $this->error('获取robots配置失败，请稍后重试', 500);
        }
    }

    /**
     * 保存 robots.txt 配置
     */
    public function saveSeoRobots(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限保存robots配置', 403);
        }

        $content = trim((string) ($request->put('content', '') ?: $request->post('content', '')));
        if ($content === '') {
            return $this->error('robots.txt 内容不能为空', 400);
        }

        $parsedEntries = $this->parseRobotsContent($content);
        if (empty($parsedEntries)) {
            return $this->error('robots.txt 内容格式无效', 400);
        }

        try {
            $beforeRows = Db::name('seo_robots')->order('sort_order', 'asc')->select()->toArray();
            $insertData = [];
            $now = date('Y-m-d H:i:s');
            foreach ($parsedEntries as $index => $entry) {
                $insertData[] = [
                    'user_agent' => $entry['user_agent'],
                    'rules' => json_encode($entry['rules'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'crawl_delay' => (int) $entry['crawl_delay'],
                    'sitemap_url' => $entry['sitemap_url'],
                    'is_active' => 1,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Db::startTrans();
            Db::name('seo_robots')->delete();
            Db::name('seo_robots')->insertAll($insertData);
            Db::commit();

            $this->logOperation('save_seo_robots', 'config', [
                'target_type' => 'seo_robots',
                'detail' => '更新robots.txt配置',
                'before_data' => $beforeRows,
                'after_data' => $insertData,
            ]);

            return $this->success([
                'count' => count($insertData),
                'content' => $content,
            ], '保存成功');
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('保存robots配置失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
            ]);
            return $this->error('保存robots配置失败，请稍后重试', 500);
        }
    }

    /**
     * 提交 SEO 收录请求
     */
    public function seoSubmit(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限执行收录提交', 403);
        }

        $engine = trim((string) $request->post('engine', ''));
        $type = trim((string) $request->post('type', 'sitemap'));
        $url = trim((string) $request->post('url', ''));

        if (!in_array($engine, self::SEO_ENGINES, true)) {
            return $this->error('搜索引擎类型无效', 400);
        }
        if (!in_array($type, self::SEO_SUBMIT_TYPES, true)) {
            return $this->error('提交类型无效', 400);
        }
        if ($url === '') {
            $url = rtrim((string) env('SITE_URL', 'https://taichu.chat'), '/') . ($type === 'sitemap' ? '/sitemap.xml' : '/');
        }

        try {
            $payload = [
                'engine' => $engine,
                'type' => $type,
                'url' => $url,
                'status' => 'success',
                'response' => '已记录提交请求，待搜索平台同步结果',
                'submitted_at' => date('Y-m-d H:i:s'),
                'completed_at' => date('Y-m-d H:i:s'),
            ];
            $id = (int) Db::name('seo_submissions')->insertGetId($payload);

            $this->logOperation('submit_seo', 'config', [
                'target_id' => $id,
                'target_type' => 'seo_submission',
                'detail' => sprintf('提交%s到%s', $type, $engine),
                'after_data' => $payload,
            ]);

            return $this->success(['id' => $id] + $payload, '提交成功');
        } catch (\Throwable $e) {
            Log::error('提交SEO收录请求失败: ' . $e->getMessage(), [
                'admin_id' => $this->adminId,
                'engine' => $engine,
                'type' => $type,
                'url' => $url,
            ]);
            return $this->error('提交收录请求失败，请稍后重试', 500);
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
     * 格式化 SEO 配置行数据
     */
    protected function formatSeoConfigRow(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'route' => $row['route'] ?? '',
            'title' => $row['title'] ?? '',
            'description' => $row['description'] ?? '',
            'keywords' => $this->normalizeSeoKeywords($row['keywords'] ?? ''),
            'image' => $row['image'] ?? '',
            'robots' => $row['robots'] ?? 'index,follow',
            'ogType' => $row['og_type'] ?? 'website',
            'canonical' => $row['canonical'] ?? '',
            'priority' => isset($row['priority']) ? (float) $row['priority'] : 0.5,
            'changefreq' => $row['changefreq'] ?? 'weekly',
            'isActive' => (int) ($row['is_active'] ?? 1),
            'updated_at' => $row['updated_at'] ?? '',
        ];
    }

    /**
     * 格式化 SEO 关键词数据
     */
    protected function formatSeoKeywordRow(array $row): array
    {
        $categoryMap = [
            'core' => '核心词',
            'long' => '长尾词',
            'related' => '相关词',
            'brand' => '品牌词',
        ];

        $baiduRank = (int) ($row['baidu_rank'] ?? 0);
        $bingRank = (int) ($row['bing_rank'] ?? 0);
        $ranks = array_filter([$baiduRank, $bingRank]);
        $bestRank = empty($ranks) ? 0 : min($ranks);
        $trend = $bestRank === 0 ? 0 : ($bestRank <= 20 ? 1 : -1);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'keyword' => $row['keyword'] ?? '',
            'category' => $categoryMap[$row['category'] ?? 'general'] ?? ($row['category'] ?? '通用词'),
            'baiduRank' => $baiduRank,
            'bingRank' => $bingRank,
            'searchVolume' => (int) ($row['search_volume'] ?? 0),
            'trend' => $trend,
        ];
    }

    /**
     * 格式化 SEO 页面收录数据
     */
    protected function formatSeoIndexedPageRow(array $row): array
    {
        $statusMap = [
            'indexed' => '已收录',
            'pending' => '待收录',
            'not_indexed' => '未收录',
        ];

        $lastCrawl = $row['baidu_last_crawl'] ?? '';
        if (($row['bing_last_crawl'] ?? '') > $lastCrawl) {
            $lastCrawl = $row['bing_last_crawl'];
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'url' => $row['url'] ?? '',
            'pageRoute' => $row['page_route'] ?? '',
            'title' => $row['title'] ?? '',
            'baiduStatus' => $statusMap[$row['baidu_status'] ?? 'pending'] ?? '待收录',
            'bingStatus' => $statusMap[$row['bing_status'] ?? 'pending'] ?? '待收录',
            'lastCrawl' => $lastCrawl ?: '-',
            'traffic' => (int) ($row['organic_traffic'] ?? 0),
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
     * 规范化 SEO 关键词输入
     */
    protected function normalizeSeoKeywords($keywords): array
    {
        if (is_string($keywords)) {
            $decoded = json_decode($keywords, true);
            if (is_array($decoded)) {
                $keywords = $decoded;
            } else {
                $keywords = preg_split('/[,，]/', $keywords) ?: [];
            }
        }

        if (!is_array($keywords)) {
            return [];
        }

        $keywords = array_map(static function ($item): string {
            return trim((string) $item);
        }, $keywords);
        $keywords = array_values(array_filter(array_unique($keywords), static function (string $item): bool {
            return $item !== '';
        }));

        return $keywords;
    }

    /**
     * 解析 robots 规则
     */
    protected function normalizeRobotsRulePayload($value): array
    {
        $rows = $this->normalizeJsonArrayInput($value);
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = strtolower(trim((string) ($row['type'] ?? '')));
            $path = trim((string) ($row['path'] ?? ''));
            if ($type === '' || $path === '') {
                continue;
            }

            $result[] = [
                'type' => $type,
                'path' => $path,
            ];
        }

        return $result;
    }

    /**
     * 解析 robots 文本
     */
    protected function parseRobotsContent(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $entries = [];
        $current = null;

        $flush = static function (?array $entry, array &$target): void {
            if (!$entry || empty($entry['user_agent'])) {
                return;
            }
            $target[] = $entry;
        };

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^User-agent:\s*(.+)$/i', $line, $matches)) {
                $flush($current, $entries);
                $current = [
                    'user_agent' => trim($matches[1]),
                    'rules' => [],
                    'crawl_delay' => 0,
                    'sitemap_url' => '',
                ];
                continue;
            }

            if ($current === null) {
                continue;
            }

            if (preg_match('/^(Allow|Disallow):\s*(.*)$/i', $line, $matches)) {
                $current['rules'][] = [
                    'type' => strtolower($matches[1]),
                    'path' => trim($matches[2]) === '' ? '/' : trim($matches[2]),
                ];
                continue;
            }

            if (preg_match('/^Crawl-delay:\s*(\d+)$/i', $line, $matches)) {
                $current['crawl_delay'] = (int) $matches[1];
                continue;
            }

            if (preg_match('/^Sitemap:\s*(.+)$/i', $line, $matches)) {
                $current['sitemap_url'] = trim($matches[1]);
            }
        }

        $flush($current, $entries);

        return array_values(array_filter($entries, static function (array $entry): bool {
            return $entry['user_agent'] !== '';
        }));
    }

    /**
     * 生成 robots 文本
     */
    protected function buildRobotsContent(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }

        $blocks = [];
        foreach ($rows as $row) {
            $lines = ['User-agent: ' . ($row['user_agent'] ?? '*')];
            foreach ($this->normalizeRobotsRulePayload($row['rules'] ?? '') as $rule) {
                $prefix = $rule['type'] === 'allow' ? 'Allow' : 'Disallow';
                $lines[] = $prefix . ': ' . $rule['path'];
            }
            $crawlDelay = (int) ($row['crawl_delay'] ?? 0);
            if ($crawlDelay > 0) {
                $lines[] = 'Crawl-delay: ' . $crawlDelay;
            }
            $sitemapUrl = trim((string) ($row['sitemap_url'] ?? ''));
            if ($sitemapUrl !== '') {
                $lines[] = 'Sitemap: ' . $sitemapUrl;
            }
            $blocks[] = implode("\r\n", $lines);
        }

        return implode("\r\n\r\n", $blocks) . "\r\n";
    }

    /**
     * SEO 提交状态
     */
    protected function formatSeoSubmitStatus(): array
    {
        $statusMap = [];
        foreach (self::SEO_ENGINES as $engine) {
            $row = Db::name('seo_submissions')
                ->where('engine', $engine)
                ->order('submitted_at', 'desc')
                ->find();

            if (!$row) {
                $statusMap[$engine] = ['type' => 'info', 'text' => '未提交'];
                continue;
            }

            $status = (string) ($row['status'] ?? 'pending');
            if ($status === 'success') {
                $statusMap[$engine] = ['type' => 'success', 'text' => '已提交'];
            } elseif ($status === 'failed') {
                $statusMap[$engine] = ['type' => 'danger', 'text' => '提交失败'];
            } else {
                $statusMap[$engine] = ['type' => 'warning', 'text' => '处理中'];
            }
        }

        return $statusMap;
    }

    /**
     * 计算趋势百分比
     */
    protected function calculatePercentageTrend(int $current, int $previous): int
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    /**
     * 构建 SEO 建议
     */
    protected function buildSeoSuggestions(array $configRows, int $keywordTotal, int $keywordTop10, int $pendingPages): array
    {
        $suggestions = [];

        $missingDesc = 0;
        $shortTitle = 0;
        foreach ($configRows as $row) {
            $titleLength = mb_strlen((string) ($row['title'] ?? ''));
            $descLength = mb_strlen((string) ($row['description'] ?? ''));
            if ($titleLength < 20 || $titleLength > 60) {
                $shortTitle++;
            }
            if ($descLength < 50 || $descLength > 200) {
                $missingDesc++;
            }
        }

        if ($pendingPages > 0) {
            $suggestions[] = [
                'priority' => 'high',
                'title' => '存在待收录页面',
                'description' => '当前仍有' . $pendingPages . '个页面未完成搜索引擎收录，建议优先补提 sitemap 或 URL。',
                'action' => '检查收录',
            ];
        }

        if ($shortTitle > 0 || $missingDesc > 0) {
            $suggestions[] = [
                'priority' => 'medium',
                'title' => '部分页面 TDK 质量不足',
                'description' => '共有' . ($shortTitle + $missingDesc) . '项标题或描述长度不在建议区间内，建议继续优化。',
                'action' => '优化配置',
            ];
        }

        if ($keywordTotal > 0 && $keywordTop10 < max(1, (int) ceil($keywordTotal * 0.2))) {
            $suggestions[] = [
                'priority' => 'medium',
                'title' => '核心关键词 Top10 占比偏低',
                'description' => '当前目标关键词' . $keywordTotal . '个，其中仅' . $keywordTop10 . '个进入前10，建议继续优化内容与内链。',
                'action' => '查看关键词',
            ];
        }

        if (empty($suggestions)) {
            $suggestions[] = [
                'priority' => 'low',
                'title' => '当前 SEO 基础状态稳定',
                'description' => '页面配置、关键词和收录状态暂无明显阻塞项，可以持续观察搜索表现。',
                'action' => '查看报告',
            ];
        }

        return $suggestions;
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
            $article = Db::name('tc_article')->where('id', $id)->find();
            if (!$article) {
                return $this->error('文章不存在', 404);
            }
            return $this->success($article);
        } catch (\Exception $e) {
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
        if (empty($data['title']) || empty($data['content'])) {
            return $this->error('标题和内容不能为空', 400);
        }

        try {
            $saveData = [
                'category_id' => $data['category_id'] ?? 0,
                'title' => $data['title'],
                'slug' => $data['slug'] ?? time(),
                'summary' => $data['summary'] ?? '',
                'content' => $data['content'],
                'thumbnail' => $data['thumbnail'] ?? '',
                'status' => $data['status'] ?? 1,
                'is_hot' => $data['is_hot'] ?? 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $id = Db::name('tc_article')->insertGetId($saveData);
            return $this->success(['id' => $id], '保存成功');
        } catch (\Exception $e) {
            return $this->error('保存失败: ' . $e->getMessage(), 500);
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

        $data = $request->post();
        try {
            $updateData = [];
            $allowedFields = ['title', 'summary', 'content', 'thumbnail', 'status', 'is_hot', 'category_id', 'slug'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            $updateData['updated_at'] = date('Y-m-d H:i:s');

            Db::name('tc_article')->where('id', $id)->update($updateData);
            return $this->success(null, '更新成功');
        } catch (\Exception $e) {
            return $this->error('更新失败', 500);
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
            Db::name('tc_article')->where('id', $id)->delete();
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败', 500);
        }
    }

    /**
     * 文章分类列表
     */
    public function articleCategories()
    {
        try {
            $list = Db::name('tc_article_category')->order('sort_order', 'asc')->select()->toArray();
            return $this->success($list);
        } catch (\Exception $e) {
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
        if (empty($data['name'])) {
            return $this->error('分类名称不能为空', 400);
        }

        try {
            $saveData = [
                'name' => $data['name'],
                'slug' => $data['slug'] ?? time(),
                'description' => $data['description'] ?? '',
                'sort_order' => $data['sort_order'] ?? 0,
                'status' => $data['status'] ?? 1,
            ];

            if (isset($data['id']) && $data['id'] > 0) {
                Db::name('tc_article_category')->where('id', $data['id'])->update($saveData);
                return $this->success(null, '更新成功');
            } else {
                $id = Db::name('tc_article_category')->insertGetId($saveData);
                return $this->success(['id' => $id], '创建成功');
            }
        } catch (\Exception $e) {
            return $this->error('操作失败', 500);
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

        try {
            // 检查是否有文章使用此分类
            $count = Db::name('tc_article')->where('category_id', $id)->count();
            if ($count > 0) {
                return $this->error('该分类下尚有文章，无法删除', 400);
            }

            Db::name('tc_article_category')->where('id', $id)->delete();
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败', 500);
        }
    }
}
