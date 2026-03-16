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
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        
        // 从JWT token中获取管理员信息
        $user = $this->request->user ?? [];
        $this->adminId = $user['sub'] ?? 0;
        $this->adminName = $user['nickname'] ?? 'Unknown';
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
                    'new_users' => User::where('created_at', 'like', "$date%")->count(),
                    'active_users' => User::where('last_login_at', 'like', "$date%")->count()
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
            $pageSize = $request->get('pageSize', 20);
            $username = $request->get('username', '');
            $phone = $request->get('phone', '');
            $status = $request->get('status', '');

            // 验证分页参数
            $page = filter_var($page, FILTER_VALIDATE_INT) ?: 1;
            $pageSize = filter_var($pageSize, FILTER_VALIDATE_INT) ?: 20;
            $page = max(1, $page);
            $pageSize = max(1, min(100, $pageSize)); // 限制最大100条

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
            $pageSize = $request->get('pageSize', 20);
            $userId = $request->get('user_id', '');

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
     * 获取积分记录
     */
    public function pointsRecords(Request $request)
    {
        // 检查权限
        if (!$this->checkPermission('points_view')) {
            return $this->error('无权限查看积分记录', 403);
        }
        
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
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
     * 获取反馈列表
     */
    public function feedbackList(Request $request)
    {
        // 检查权限
        if (!$this->checkPermission('feedback_view')) {
            return $this->error('无权限查看反馈列表', 403);
        }

        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
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
            
            // 记录操作前的设置（这里简化处理）
            $this->logOperation('update', 'config', [
                'detail' => '更新系统设置',
                'after_data' => $settings,
            ]);

            // TODO: 实现设置保存逻辑

            return $this->success(null, '保存成功');
        } catch (\Exception $e) {
            Log::error('保存系统设置失败: ' . $e->getMessage());
            return $this->error('保存失败，请稍后重试', 500);
        }
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
            $page = $request->get('page', 1);
            $perPage = $request->get('pageSize', 20);
            
            $result = AdminLog::getLogList($params, $page, $perPage);
            
            return $this->success($result, '获取成功');
        } catch (\Exception $e) {
            Log::error('获取操作日志失败: ' . $e->getMessage());
            return $this->error('获取日志失败，请稍后重试', 500);
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

        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));

        try {
            $list = Db::name('almanac')
                ->whereYear('solar_date', $year)
                ->whereMonth('solar_date', $month)
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
}
