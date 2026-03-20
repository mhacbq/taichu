<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;
use app\Models\UserVip;
use app\Models\User;

class VipRecordController extends Controller
{
    /**
     * 获取VIP购买记录列表（管理员）
     */
    public function index(Request $request)
    {
        $query = UserVip::with(['user', 'package']);

        // 搜索条件
        if ($request->has('keyword')) {
            $keyword = $request->input('keyword');
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('end_time', '>', now());
            } elseif ($status === 'expired') {
                $query->where('end_time', '<=', now());
            }
        }

        $records = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('page_size', 15));

        return $this->success('获取成功', $records);
    }

    /**
     * 获取VIP统计（管理员）
     */
    public function statistics()
    {
        $stats = [
            'total_purchases' => UserVip::count(),
            'active_vip' => UserVip::where('end_time', '>', now())
                ->where('is_active', 1)
                ->count(),
            'expired_vip' => UserVip::where('end_time', '<=', now())->count(),
            'total_revenue' => UserVip::whereHas('package')->get()->sum(function ($vip) {
                return $vip->package->price ?? 0;
            }),
            'recent_records' => UserVip::with(['user', 'package'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return $this->success('获取成功', $stats);
    }
}
