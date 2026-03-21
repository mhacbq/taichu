<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\PointsRecord;
use think\Request;
use think\facade\Db;

/**
 * 积分管理控制器
 */
class PointsController extends BaseController
{
    /**
     * 积分记录列表
     */
    public function records(Request $request)
    {
        try {
            $page = (int) $request->get('page', 1);
            $pageSize = (int) $request->get('pageSize', 20);
            $userId = $request->get('user_id', '');
            $type = $request->get('type', '');
            $startDate = $request->get('start_date', '');
            $endDate = $request->get('end_date', '');

            $query = Db::table('tc_points_record as pr')
                ->leftJoin('tc_user as u', 'pr.user_id', '=', 'u.id')
                ->field('pr.*, u.nickname, u.mobile');

            if (!empty($userId)) {
                $query->where('pr.user_id', $userId);
            }

            if (!empty($type)) {
                $query->where('pr.type', $type);
            }

            if (!empty($startDate)) {
                $query->where('pr.created_at', '>=', $startDate);
            }

            if (!empty($endDate)) {
                $query->where('pr.created_at', '<=', $endDate . ' 23:59:59');
            }

            $total = $query->count();
            $records = $query->order('pr.created_at', 'desc')
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
            return $this->error('获取积分记录失败', 500);
        }
    }

    /**
     * 积分统计
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_points' => Db::table('user')->sum('points'),
                'total_records' => PointsRecord::count(),
                'today_records' => PointsRecord::where('created_at', '>=', date('Y-m-d'))->count(),
                'today_add' => PointsRecord::where('created_at', '>=', date('Y-m-d'))
                    ->where('amount', '>', 0)
                    ->sum('amount'),
                'today_sub' => abs(PointsRecord::where('created_at', '>=', date('Y-m-d'))
                    ->where('amount', '<', 0)
                    ->sum('amount')),
                'type_stats' => PointsRecord::field('type, COUNT(*) as count, SUM(amount) as total_amount')
                    ->group('type')
                    ->select()
            ];

            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->error('获取积分统计失败', 500);
        }
    }
}
