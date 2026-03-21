<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Log;

/**
 * 平台统计数据控制器
 */
class Statistics extends BaseController
{
    /**
     * 平台统计数据
     */
    public function index()
    {
        try {
            $stats = [
                'user_stats' => [
                    'total_users' => Db::table('user')->count(),
                    'today_users' => Db::table('user')
                        ->where('created_at', '>=', date('Y-m-d'))
                        ->count(),
                    'active_users' => Db::table('user')
                        ->where('last_login_time', '>=', date('Y-m-d'))
                        ->count(),
                ],
                'content_stats' => [
                    'total_bazi' => Db::table('bazi_record')->count(),
                    'today_bazi' => Db::table('bazi_record')
                        ->where('created_at', '>=', date('Y-m-d'))
                        ->count(),
                    'total_tarot' => Db::table('tarot_record')->count(),
                    'today_tarot' => Db::table('tarot_record')
                        ->where('created_at', '>=', date('Y-m-d'))
                        ->count(),
                ],
                'vip_stats' => [
                    'total_vip_users' => Db::table('vip_record')
                        ->where('expire_time', '>', date('Y-m-d H:i:s'))
                        ->distinct()
                        ->count('user_id'),
                    'today_vip_orders' => Db::table('vip_record')
                        ->where('created_at', '>=', date('Y-m-d'))
                        ->count(),
                ],
                'points_stats' => [
                    'total_points' => Db::table('user')->sum('points'),
                    'today_points_records' => Db::table('points_record')
                        ->where('created_at', '>=', date('Y-m-d'))
                        ->count(),
                ],
                'order_stats' => [
                    'total_orders' => Db::table('vip_record')->count(),
                    'total_amount' => Db::table('vip_record')->sum('amount'),
                    'today_orders' => Db::table('vip_record')
                        ->where('created_at', '>=', date('Y-m-d'))
                        ->count(),
                    'today_amount' => Db::table('vip_record')
                        ->where('created_at', '>=', date('Y-m-d'))
                        ->sum('amount'),
                ],
                'feedback_stats' => [
                    'total_feedback' => Db::table('feedback')->count(),
                    'pending_feedback' => Db::table('feedback')
                        ->where('status', 'pending')
                        ->count(),
                ],
            ];

            return $this->success($stats);
        } catch (\Throwable $e) {
            Log::error('获取平台统计失败', [
                'error' => $e->getMessage()
            ]);
            return $this->error('获取统计数据失败', 500);
        }
    }
}
