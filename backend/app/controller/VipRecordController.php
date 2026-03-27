<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\VipOrder;
use think\Request;
use think\facade\Db;

/**
 * VIP记录管理控制器
 */
class VipRecordController extends BaseController
{
    /**
     * VIP记录列表
     */
    public function index(Request $request)
    {
        try {
            $page = (int) $request->get('page', 1);
            $pageSize = (int) $request->get('pageSize', 20);
            $userId = $request->get('user_id', '');
            $packageId = $request->get('package_id', '');
            $startDate = $request->get('start_date', '');
            $endDate = $request->get('end_date', '');

            $query = VipOrder::with(['user' => function($query) {
                $query->field('id,nickname,mobile');
            }]);

            if (!empty($userId)) {
                $query->where('user_id', $userId);
            }

            if (!empty($packageId)) {
                $query->where('vip_type', $packageId);
            }

            if (!empty($startDate)) {
                $query->where('created_at', '>=', $startDate);
            }

            if (!empty($endDate)) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            $total = $query->count();
            $records = $query->order('created_at', 'desc')
                ->limit($pageSize)
                ->page($page)
                ->select();

            return $this->success([
                'list' => $records,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize
            ]);
        } catch (\Throwable $e) {
            return $this->error('获取VIP记录失败', 500);
        }
    }

    /**
     * VIP统计
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_records' => VipOrder::count(),
                'today_records' => VipOrder::where('created_at', '>=', date('Y-m-d'))->count(),
                'active_vip_users' => Db::table('tc_user_vip')
                    ->where('status', 1)
                    ->where('end_time', '>', date('Y-m-d H:i:s'))
                    ->distinct()
                    ->count('user_id') ?: Db::table('tc_user')
                    ->where('vip_level', '>', 0)
                    ->where('vip_expire_at', '>', date('Y-m-d H:i:s'))
                    ->count(),
                'expired_users' => Db::table('tc_user_vip')
                    ->where('status', 2)
                    ->distinct()
                    ->count('user_id') ?: Db::table('tc_user')
                    ->where('vip_level', '>', 0)
                    ->where('vip_expire_at', '<', date('Y-m-d H:i:s'))
                    ->count(),
                'total_amount' => VipOrder::where('status', 'paid')->sum('amount'),
                'today_amount' => VipOrder::where('created_at', '>=', date('Y-m-d'))
                    ->where('status', 'paid')
                    ->sum('amount'),
                'type_stats' => VipOrder::where('status', 'paid')
                    ->field('vip_type, COUNT(*) as count, SUM(amount) as total_amount')
                    ->group('vip_type')
                    ->select()
            ];

            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->error('获取VIP统计失败', 500);
        }
    }
}
