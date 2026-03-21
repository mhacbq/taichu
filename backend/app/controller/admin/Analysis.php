<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\Payment;
use app\model\User;
use think\Request;
use think\facade\Db;

class Analysis extends BaseController
{
    /**
     * 充值数据分析
     */
    public function payment(Request $request)
    {
        if (!$this->checkPermission('payment_view')) {
            return $this->error('无权限查看充值数据', 403);
        }

        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));

        try {
            // 渠道对比分析
            $channelData = Db::table('tc_payment')
                ->field('channel, COUNT(*) as count, SUM(amount) as total_amount, AVG(amount) as avg_amount')
                ->where('status', 'success')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->group('channel')
                ->select()
                ->toArray();

            // 充值趋势（按日期）
            $trendData = Db::table('tc_payment')
                ->field('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total_amount')
                ->where('status', 'success')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->group('DATE(created_at)')
                ->order('date', 'asc')
                ->select()
                ->toArray();

            // 复购率分析
            $totalUsers = Db::table('tc_payment')
                ->where('status', 'success')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->group('user_id')
                ->count();

            $repeatUsers = Db::table('tc_payment')
                ->field('user_id, COUNT(*) as payment_count')
                ->where('status', 'success')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->group('user_id')
                ->having('payment_count > 1')
                ->count();

            // 充值热力图（按时间段）
            $hourData = Db::table('tc_payment')
                ->field('HOUR(created_at) as hour, COUNT(*) as count')
                ->where('status', 'success')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->group('HOUR(created_at)')
                ->order('hour', 'asc')
                ->select()
                ->toArray();

            return $this->success([
                'channel' => $channelData,
                'trend' => $trendData,
                'repeat_rate' => $totalUsers > 0 ? round(($repeatUsers / $totalUsers) * 100, 2) : 0,
                'heatmap' => $hourData,
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('analysis_payment', $e, '获取充值数据失败');
        }
    }

    /**
     * 用户增长分析
     */
    public function user(Request $request)
    {
        if (!$this->checkPermission('user_view')) {
            return $this->error('无权限查看用户数据', 403);
        }

        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));

        try {
            // 用户增长趋势
            $growthData = Db::table('tc_user')
                ->field('DATE(created_at) as date, COUNT(*) as count')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->group('DATE(created_at)')
                ->order('date', 'asc')
                ->select()
                ->toArray();

            // 留存率分析（7日留存）
            $sevenDayAgo = date('Y-m-d', strtotime('-7 days'));
            $newUsersSevenDaysAgo = Db::table('tc_user')
                ->whereBetweenTime('created_at', $sevenDayAgo . ' 00:00:00', $sevenDayAgo . ' 23:59:59')
                ->column('id');

            $retainedUsers = Db::table('tc_user')
                ->whereIn('id', $newUsersSevenDaysAgo)
                ->where('last_login_time', '>=', $sevenDayAgo . ' 00:00:00')
                ->count();

            $retentionRate = count($newUsersSevenDaysAgo) > 0 
                ? round(($retainedUsers / count($newUsersSevenDaysAgo)) * 100, 2) 
                : 0;

            // 用户来源分析
            $sourceData = Db::table('tc_user')
                ->field('source, COUNT(*) as count')
                ->whereNotNull('source')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->group('source')
                ->select()
                ->toArray();

            return $this->success([
                'growth' => $growthData,
                'retention_rate' => $retentionRate,
                'source' => $sourceData,
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('analysis_user', $e, '获取用户数据失败');
        }
    }

    /**
     * 测算数据统计
     */
    public function result(Request $request)
    {
        if (!$this->checkPermission('result_view')) {
            return $this->error('无权限查看测算数据', 403);
        }

        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));

        try {
            // 测算量趋势
            $trendData = Db::table('tc_result')
                ->field('DATE(created_at) as date, COUNT(*) as count')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->group('DATE(created_at)')
                ->order('date', 'asc')
                ->select()
                ->toArray();

            // 热门测算类型排行
            $typeRanking = Db::table('tc_result')
                ->field('type, COUNT(*) as count')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->group('type')
                ->order('count', 'desc')
                ->limit(10)
                ->select()
                ->toArray();

            // 类型分布
            $typeDistribution = Db::table('tc_result')
                ->field('type, COUNT(*) as count')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->group('type')
                ->select()
                ->toArray();

            return $this->success([
                'trend' => $trendData,
                'type_ranking' => $typeRanking,
                'type_distribution' => $typeDistribution,
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('analysis_result', $e, '获取测算数据失败');
        }
    }
}
