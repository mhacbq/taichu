<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\AdminStatsService;
use think\Request;

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
        try {
            $stats = AdminStatsService::getDashboardStats();
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error('获取统计数据失败：' . $e->getMessage());
        }
    }

    /**
     * 获取趋势数据
     */
    public function trend()
    {
        try {
            $days = $this->request->get('days', 30);
            $stats = AdminStatsService::getTrendStats((int)$days);
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error('获取趋势数据失败：' . $e->getMessage());
        }
    }

    /**
     * 手动更新统计数据（测试用）
     */
    public function updateStats()
    {
        try {
            $date = $this->request->post('date');
            AdminStatsService::updateDailyStats($date);
            return $this->success([], '统计数据更新成功');
        } catch (\Exception $e) {
            return $this->error('更新统计数据失败：' . $e->getMessage());
        }
    }
}
