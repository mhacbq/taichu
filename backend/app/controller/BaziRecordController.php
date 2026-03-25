<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\BaziRecord;
use think\Request;
use think\facade\Db;

/**
 * 八字记录管理控制器
 */
class BaziRecordController extends BaseController
{
    /**
     * 八字记录列表
     */
    public function index(Request $request)
    {
        try {
            $page = (int) $request->get('page', 1);
            $pageSize = (int) $request->get('pageSize', 20);
            $userId = $request->get('user_id', '');
            $startDate = $request->get('start_date', '');
            $endDate = $request->get('end_date', '');

            $query = Db::table('tc_bazi_record as br')
                ->leftJoin('tc_user as u', 'br.user_id', '=', 'u.id')
                ->field('br.*, u.nickname, u.mobile');

            if (!empty($userId)) {
                $query->where('br.user_id', $userId);
            }

            if (!empty($startDate)) {
                $query->where('br.created_at', '>=', $startDate);
            }

            if (!empty($endDate)) {
                $query->where('br.created_at', '<=', $endDate . ' 23:59:59');
            }

            $total = $query->count();
            $records = $query->order('br.created_at', 'desc')
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
            return $this->error('获取八字记录失败', 500);
        }
    }

    /**
     * 八字统计
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_records' => BaziRecord::count(),
                'today_records' => BaziRecord::where('created_at', '>=', date('Y-m-d'))->count(),
                'total_users' => Db::name('bazi_record')->distinct()->count('user_id'),
                'gender_stats' => BaziRecord::field('gender, COUNT(*) as count')
                    ->group('gender')
                    ->select(),
                'calendar_type_stats' => BaziRecord::field('calendar_type, COUNT(*) as count')
                    ->group('calendar_type')
                    ->select()
            ];

            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->error('获取八字统计失败', 500);
        }
    }
}
