<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\User;
use app\model\BaziRecord;
use app\model\PointsRecord;
use app\model\Feedback;
use think\Request;

/**
 * 后台管理控制器
 */
class Admin extends BaseController
{
    /**
     * 获取仪表盘统计数据
     */
    public function dashboard()
    {
        $statistics = [
            'total_users' => User::count(),
            'today_users' => User::where('created_at', '>=', date('Y-m-d'))->count(),
            'total_bazi' => BaziRecord::count(),
            'today_bazi' => BaziRecord::where('created_at', '>=', date('Y-m-d'))->count(),
            'total_tarot' => \app\model\DailyFortune::count(),
            'today_tarot' => \app\model\DailyFortune::where('created_at', '>=', date('Y-m-d'))->count(),
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
            ['name' => '塔罗占卜', 'value' => \app\model\DailyFortune::count()],
            ['name' => '每日运势', 'value' => \app\model\DailyFortune::count()],
            ['name' => '积分兑换', 'value' => PointsRecord::where('type', 'reduce')->count()]
        ];

        return json([
            'code' => 200,
            'data' => [
                'statistics' => $statistics,
                'user_trend' => $userTrend,
                'feature_stats' => $featureStats
            ]
        ]);
    }

    /**
     * 获取用户列表
     */
    public function users(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);
        $username = $request->get('username', '');
        $phone = $request->get('phone', '');
        $status = $request->get('status', '');

        $query = User::order('id', 'desc');

        if ($username) {
            // 净化输入，防止SQL注入
            $username = preg_replace('/[%_\\]/', '', $username);
            $query->whereLike('username|nickname', "%{$username}%");
        }
        if ($phone) {
            // 净化输入，防止SQL注入
            $phone = preg_replace('/[%_\\]/', '', $phone);
            $query->whereLike('phone', "%{$phone}%");
        }
        if ($status !== '') {
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
    }

    /**
     * 获取用户详情
     */
    public function userDetail($id)
    {
        $user = User::find($id);
        if (!$user) {
            return json(['code' => 404, 'message' => '用户不存在']);
        }

        // 添加统计数据
        $user['bazi_count'] = BaziRecord::where('user_id', $id)->count();
        $user['tarot_count'] = \app\model\DailyFortune::where('user_id', $id)->count();

        return json([
            'code' => 200,
            'data' => $user
        ]);
    }

    /**
     * 更新用户状态
     */
    public function updateUserStatus(Request $request, $id)
    {
        $status = $request->put('status');
        $user = User::find($id);
        
        if (!$user) {
            return json(['code' => 404, 'message' => '用户不存在']);
        }

        $user->status = $status;
        $user->save();

        return json(['code' => 200, 'message' => '操作成功']);
    }

    /**
     * 获取八字记录列表
     */
    public function baziRecords(Request $request)
    {
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
    }

    /**
     * 删除八字记录
     */
    public function deleteBaziRecord($id)
    {
        BaziRecord::destroy($id);
        return json(['code' => 200, 'message' => '删除成功']);
    }

    /**
     * 获取积分记录
     */
    public function pointsRecords(Request $request)
    {
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
    }

    /**
     * 调整用户积分
     */
    public function adjustPoints(Request $request)
    {
        $userId = $request->post('user_id');
        $type = $request->post('type');
        $amount = $request->post('amount');
        $reason = $request->post('reason', '管理员调整');

        $user = User::find($userId);
        if (!$user) {
            return json(['code' => 404, 'message' => '用户不存在']);
        }

        // 调整积分
        if ($type === 'add') {
            $user->points += $amount;
        } else {
            $user->points -= $amount;
            if ($user->points < 0) {
                $user->points = 0;
            }
        }
        $user->save();

        // 记录积分变动
        PointsRecord::create([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'balance' => $user->points,
            'reason' => $reason,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return json(['code' => 200, 'message' => '调整成功']);
    }

    /**
     * 获取反馈列表
     */
    public function feedbackList(Request $request)
    {
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
    }

    /**
     * 回复反馈
     */
    public function replyFeedback(Request $request, $id)
    {
        $reply = $request->post('reply');
        $status = $request->post('status', 'resolved');

        $feedback = Feedback::find($id);
        if (!$feedback) {
            return json(['code' => 404, 'message' => '反馈不存在']);
        }

        $feedback->reply = $reply;
        $feedback->status = $status;
        $feedback->replied_at = date('Y-m-d H:i:s');
        $feedback->save();

        return json(['code' => 200, 'message' => '回复成功']);
    }

    /**
     * 获取系统设置
     */
    public function getSettings()
    {
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

        return json([
            'code' => 200,
            'data' => $settings
        ]);
    }

    /**
     * 保存系统设置
     */
    public function saveSettings(Request $request)
    {
        $settings = $request->post();
        
        // 保存到数据库或缓存
        // TODO: 实现设置保存逻辑

        return json(['code' => 200, 'message' => '保存成功']);
    }
}
