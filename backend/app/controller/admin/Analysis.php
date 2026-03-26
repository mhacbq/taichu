<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\Payment;
use app\model\User;
use think\Request;
use think\facade\Db;

class Analysis extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

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
            // 优先查 tc_recharge_order，兼容 tc_payment
            $payTable = null;
            foreach (['tc_recharge_order', 'tc_payment'] as $t) {
                try {
                    Db::table($t)->limit(1)->select();
                    $payTable = $t;
                    break;
                } catch (\Throwable $ignored) {}
            }

            if ($payTable === null) {
                return $this->success([
                    'stats' => ['total_amount' => 0, 'order_count' => 0, 'vip_count' => 0, 'recharge_count' => 0],
                    'chart_data' => ['dates' => [], 'amounts' => [], 'counts' => []],
                ], '暂无充值数据');
            }

            // 充值趋势（按日期）
            $trendData = Db::table($payTable)
                ->field('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total_amount')
                ->where('status', 'success')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->group('DATE(created_at)')
                ->order('date', 'asc')
                ->select()
                ->toArray();

            // 统计数据
            $stats = Db::table($payTable)
                ->field('COUNT(*) as order_count, SUM(amount) as total_amount')
                ->where('status', 'success')
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->find();

            $chartData = [
                'dates'   => array_column($trendData, 'date'),
                'amounts' => array_column($trendData, 'total_amount'),
                'counts'  => array_column($trendData, 'count'),
            ];

            return $this->success([
                'stats' => [
                    'total_amount'   => $stats['total_amount'] ?? 0,
                    'order_count'    => $stats['order_count'] ?? 0,
                    'vip_count'      => 0,
                    'recharge_count' => $stats['order_count'] ?? 0,
                ],
                'chart_data' => $chartData,
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

            // 用户来源分析（通过邀请人字段区分：邀请注册 vs 直接注册）
            $invitedCount = Db::table('tc_user')
                ->where('invited_by', '>', 0)
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->count();
            $directCount = Db::table('tc_user')
                ->where('invited_by', 0)
                ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->count();
            $sourceData = [
                ['source' => '邀请注册', 'count' => $invitedCount],
                ['source' => '直接注册', 'count' => $directCount],
            ];

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
            $tables = [
                'bazi'   => 'tc_bazi_record',
                'tarot'  => 'tc_tarot_record',
                'liuyao' => 'tc_liuyao_record',
            ];

            $typeStats = [];
            $trendMap = [];

            foreach ($tables as $type => $table) {
                try {
                    $count = Db::table($table)
                        ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                        ->count();
                    $typeStats[] = ['type' => $type, 'count' => $count];

                    // 趋势数据
                    $rows = Db::table($table)
                        ->field('DATE(created_at) as date, COUNT(*) as count')
                        ->whereBetweenTime('created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59')
                        ->group('DATE(created_at)')
                        ->select()
                        ->toArray();
                    foreach ($rows as $row) {
                        $trendMap[$row['date']] = ($trendMap[$row['date']] ?? 0) + (int) $row['count'];
                    }
                } catch (\Throwable $ignored) {
                    // 表不存在时跳过
                }
            }

            ksort($trendMap);
            $trendData = array_map(
                fn($date, $count) => ['date' => $date, 'count' => $count],
                array_keys($trendMap),
                array_values($trendMap)
            );

            return $this->success([
                'trend' => $trendData,
                'type_ranking' => $typeStats,
                'type_distribution' => $typeStats,
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('analysis_result', $e, '获取测算数据失败');
        }
    }
}
