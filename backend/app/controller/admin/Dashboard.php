<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\AdminStatsService;
use think\facade\Log;

/**
 * 后台Dashboard控制器
 */
class Dashboard extends BaseController
{
    /**
     * 获取Dashboard统计数据
     */
    public function index()
    {
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限访问统计数据', 403);
        }

        try {
            $stats = AdminStatsService::getDashboardStats();
            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->respondWithDashboardError('dashboard_index', '获取统计数据失败，请稍后重试', $e);
        }
    }

    /**
     * 获取趋势数据
     */
    public function trend()
    {
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限访问统计数据', 403);
        }

        $days = (int) $this->request->get('days', 30);

        try {
            $stats = AdminStatsService::getTrendStats($days);
            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->respondWithDashboardError('dashboard_trend', '获取趋势数据失败，请稍后重试', $e, [
                'days' => $days,
            ]);
        }
    }

    /**
     * 手动更新统计数据
     */
    public function updateStats()
    {
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限刷新统计数据', 403);
        }

        $date = trim((string) $this->request->post('date', ''));
        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->error('统计日期格式无效，请使用 YYYY-MM-DD', 400);
        }

        $targetDate = $date !== '' ? $date : date('Y-m-d');

        try {
            AdminStatsService::updateDailyStats($targetDate);
            return $this->success([
                'date' => $targetDate,
            ], '统计数据更新成功');
        } catch (\Throwable $e) {
            return $this->respondWithDashboardError('dashboard_update_stats', '更新统计数据失败，请稍后重试', $e, [
                'date' => $targetDate,
            ]);
        }
    }

    /**
     * 统一处理 Dashboard 异常
     */
    private function respondWithDashboardError(string $action, string $message, \Throwable $e, array $context = [])
    {
        Log::error('后台Dashboard接口异常', [
            'action' => $action,
            'admin_id' => $this->getAdminId(),
            'context' => $context,
            'error' => $e->getMessage(),
        ]);

        return $this->error($message, 500);
    }
}
