<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;
use app\Models\User;
use app\Models\PointsRecord;
use app\Models\BaziRecord;
use app\Models\VipRecord;

class StatisticsController extends Controller
{
    /**
     * 获取平台统计数据
     */
    public function index()
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'active' => User::where('status', 'active')->count(),
                'vip' => User::whereHas('vipRecord', function ($q) {
                    $q->where('end_time', '>', now())->where('is_active', 1);
                })->count(),
            ],
            'points' => [
                'total_issued' => PointsRecord::where('type', 'reward')->sum('amount'),
                'total_consumed' => PointsRecord::where('type', 'consume')->sum('amount'),
                'current_balance' => User::sum('points'),
            ],
            'bazi' => [
                'total' => BaziRecord::count(),
                'today' => BaziRecord::whereDate('created_at', today())->count(),
            ],
            'vip' => [
                'total_purchases' => VipRecord::count(),
                'revenue' => VipRecord::sum('price'),
            ],
        ];

        // 获取最近7天的用户增长趋势
        $userTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = User::whereDate('created_at', $date)->count();
            $userTrend[] = [
                'date' => $date,
                'count' => $count,
            ];
        }

        // 获取最近7天的排盘趋势
        $baziTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = BaziRecord::whereDate('created_at', $date)->count();
            $baziTrend[] = [
                'date' => $date,
                'count' => $count,
            ];
        }

        $stats['trends'] = [
            'users' => $userTrend,
            'bazi' => $baziTrend,
        ];

        return $this->success('获取成功', $stats);
    }
}
