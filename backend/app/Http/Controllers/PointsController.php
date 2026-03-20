<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;
use app\Models\PointsRecord;
use app\Models\User;

class PointsController extends Controller
{
    /**
     * 获取积分记录列表（管理员）
     */
    public function records(Request $request)
    {
        $query = PointsRecord::with('user');

        // 搜索条件
        if ($request->has('keyword')) {
            $keyword = $request->input('keyword');
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        // 类型筛选
        if ($request->has('type')) {
            $type = $request->input('type');
            $query->where('type', $type);
        }

        // 日期范围
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $records = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('page_size', 15));

        return $this->success('获取成功', $records);
    }

    /**
     * 获取积分统计（管理员）
     */
    public function statistics()
    {
        $stats = [
            'total_issued' => PointsRecord::where('type', 'reward')->sum('amount'),
            'total_consumed' => PointsRecord::where('type', 'consume')->sum('amount'),
            'current_balance' => User::sum('points'),
            'recent_records' => PointsRecord::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return $this->success('获取成功', $stats);
    }
}
