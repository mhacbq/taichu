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
                'total_tarot' => DailyFortune::count(),
                'today_tarot' => DailyFortune::where('created_at', '>=', date('Y-m-d'))->count(),
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

            return json([
                'code' => 200,
                'data' => [
                    'statistics' => $statistics,
                    'user_trend' => $userTrend,
                    'feature_stats' => $featureStats
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('仪表盘数据获取失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取统计数据失败，请稍后重试']);
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

            $query = User::order('id', 'desc');

            if ($username) {
                // 净化输入，防止SQL注入
                $username = preg_replace('/[%_\\\\]/', '', $username);
                $query->whereLike('username|nickname', "%{$username}%");
            }
            if ($phone) {
                // 净化输入，防止SQL注入
                $phone = preg_replace('/[%_\\\\]/', '', $phone);
                $query->whereLike('phone', "%{$phone}%");
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

            return json([
                'code' => 200,
                'data' => [
                    'list' => $list,
                    'total' => $total
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('获取用户列表失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取用户列表失败，请稍后重试']);
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
                return json(['code' => 404, 'message' => '用户不存在']);
            }

            // 添加统计数据
            $user['bazi_count'] = BaziRecord::where('user_id', $id)->count();
            $user['tarot_count'] = \app\model\DailyFortune::where('user_id', $id)->count();

            // 记录查看日志
            $this->logOperation('view', 'user', [
                'target_id' => $id,
                'target_type' => 'user',
                'detail' => '查看用户详情',
            ]);

            return json([
                'code' => 200,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('获取用户详情失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取用户详情失败，请稍后重试']);
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
            $user = User::find($id);
            
            if (!$user) {
                return json(['code' => 404, 'message' => '用户不存在']);
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

            return json(['code' => 200, 'message' => '操作成功']);
        } catch (\Exception $e) {
            Log::error('更新用户状态失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '操作失败，请稍后重试']);
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

            return json([
                'code' => 200,
                'data' => [
                    'list' => $list,
                    'total' => $total
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('获取八字记录失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取记录失败，请稍后重试']);
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
                return json(['code' => 404, 'message' => '记录不存在']);
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

            return json(['code' => 200, 'message' => '删除成功']);
        } catch (\Exception $e) {
            Log::error('删除八字记录失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '删除失败，请稍后重试']);
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

            return json([
                'code' => 200,
                'data' => [
                    'list' => $list,
                    'total' => $total
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('获取积分记录失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取积分记录失败，请稍后重试']);
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
                return json(['code' => 404, 'message' => '用户不存在']);
            }

            // 记录调整前的积分
            $beforePoints = $user->points;

            // 调整积分
            if ($type === 'add') {
                $user->addPointsAtomic($amount);
            } else {
                $result = $user->deductPoints($amount);
                if (!$result) {
                    return json(['code' => 400, 'message' => '用户积分不足']);
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

            return json(['code' => 200, 'message' => '调整成功']);
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
            
            return json(['code' => 500, 'message' => '调整失败，请稍后重试']);
        }
    }

    /**
     * 获取反馈列表
     */
    public function feedbackList(Request $request)
    {
        // 检查权限
        if (!$this->checkPermission('feedback_view')) {
            return json(['code' => 403, 'message' => '无权限查看反馈列表']);
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

            return json([
                'code' => 200,
                'data' => [
                    'list' => $list,
                    'total' => $total
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('获取反馈列表失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取反馈列表失败，请稍后重试']);
        }
    }

    /**
     * 回复反馈
     */
    public function replyFeedback(Request $request, $id)
    {
        // 检查权限
        if (!$this->checkPermission('content_manage')) {
            return json(['code' => 403, 'message' => '无权限回复反馈']);
        }
        
        try {
            $reply = $request->post('reply');
            $status = $request->post('status', 'resolved');

            $feedback = Feedback::find($id);
            if (!$feedback) {
                return json(['code' => 404, 'message' => '反馈不存在']);
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

            return json(['code' => 200, 'message' => '回复成功']);
        } catch (\Exception $e) {
            Log::error('回复反馈失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '回复失败，请稍后重试']);
        }
    }

    /**
     * 获取系统设置
     */
    public function getSettings()
    {
        // 检查权限
        if (!$this->checkPermission('config_manage')) {
            return json(['code' => 403, 'message' => '无权限查看系统设置']);
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

            return json([
                'code' => 200,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('获取系统设置失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取设置失败，请稍后重试']);
        }
    }

    /**
     * 保存系统设置
     */
    public function saveSettings(Request $request)
    {
        // 检查权限
        if (!$this->checkPermission('config_manage')) {
            return json(['code' => 403, 'message' => '无权限修改系统设置']);
        }
        
        try {
            $settings = $request->post();
            
            // 记录操作前的设置（这里简化处理）
            $this->logOperation('update', 'config', [
                'detail' => '更新系统设置',
                'after_data' => $settings,
            ]);

            // TODO: 实现设置保存逻辑

            return json(['code' => 200, 'message' => '保存成功']);
        } catch (\Exception $e) {
            Log::error('保存系统设置失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '保存失败，请稍后重试']);
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
            
            return json([
                'code' => 200,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('获取操作日志失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取日志失败，请稍后重试']);
        }
    }
}
