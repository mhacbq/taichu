<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;
use app\Models\BaziRecord;

class BaziRecordController extends Controller
{
    /**
     * 获取八字排盘记录列表（管理员）
     */
    public function index(Request $request)
    {
        $query = BaziRecord::with('user');

        // 搜索条件
        if ($request->has('keyword')) {
            $keyword = $request->input('keyword');
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        // 模式筛选
        if ($request->has('mode')) {
            $query->where('mode', $request->input('mode'));
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
     * 获取排盘统计（管理员）
     */
    public function statistics()
    {
        $stats = [
            'total' => BaziRecord::count(),
            'simple' => BaziRecord::where('mode', 'simple')->count(),
            'pro' => BaziRecord::where('mode', 'pro')->count(),
            'today' => BaziRecord::whereDate('created_at', today())->count(),
            'recent_records' => BaziRecord::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return $this->success('获取成功', $stats);
    }
}
