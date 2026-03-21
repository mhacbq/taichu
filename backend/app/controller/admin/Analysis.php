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
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        
        // 从JWT token中获取管理员信息
        $adminUser = $this->request->adminUser ?? [];
        $this->adminId = $adminUser['id'] ?? 0;
    }
    
    /**
     * 检查权限
     */
    protected function hasAdminPermission(string $permissionCode): bool
    {
        if ($this->adminId === 0) {
            return false;
        }
        
        // 如果是超级管理员，直接返回true
        $adminUser = $this->request->adminUser ?? [];
        $roles = $adminUser['roles'] ?? [];
        if (in_array('admin', $roles)) {
            return true;
        }
        
        // 操作员只能查看数据
        if (in_array('operator', $roles)) {
            return in_array($permissionCode, ['payment_view', 'user_view', 'result_view']);
        }
        
        return false;
    }
    /**
     * 充值数据分析
     */
    public function payment(Request $request)
    {
        if (!$this->hasAdminPermission('payment_view')) {
            return $this->error('无权限查看充值数据', 403);
        }

        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));

        try {
            // 充值趋势（按日期）
            $trendData = Db::table('tc_payment')
                ->field('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total_amount')
                ->where('status', 'success')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->group('DATE(created_at)')
                ->order('date', 'asc')
                ->select()
                ->toArray();

            // 统计数据
            $stats = Db::table('tc_payment')
                ->field([
                    'COUNT(*) as order_count',
                    'SUM(CASE WHEN type = "vip" THEN 1 ELSE 0 END) as vip_count',
                    'SUM(CASE WHEN type = "recharge" THEN 1 ELSE 0 END) as recharge_count',
                    'SUM(amount) as total_amount'
                ])
                ->where('status', 'success')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->find();

            // 图表数据
            $chartData = [
                'dates' => array_column($trendData, 'date'),
                'amounts' => array_column($trendData, 'total_amount'),
                'counts' => array_column($trendData, 'count')
            ];

            return $this->success([
                'stats' => [
                    'total_amount' => $stats['total_amount'] ?? 0,
                    'order_count' => $stats['order_count'] ?? 0,
                    'vip_count' => $stats['vip_count'] ?? 0,
                    'recharge_count' => $stats['recharge_count'] ?? 0
                ],
                'chart_data' => $chartData
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
        if (!$this->hasAdminPermission('user_view')) {
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
                ->where('last_login_at', '>=', $sevenDayAgo . ' 00:00:00')
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
        if (!$this->hasAdminPermission('result_view')) {
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
