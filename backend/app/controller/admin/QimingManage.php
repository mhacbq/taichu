<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\QimingRecord;
use think\Request;
use think\facade\Db;

/**
 * 取名记录管理控制器
 */
class QimingManage extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取取名记录列表
     */
    public function index()
    {
        if (!$this->hasAdminPermission('qiming_view')) {
            return $this->error('无权限查看取名记录', 403);
        }

        try {
            $params = $this->request->get();
            $page = (int) ($params['page'] ?? 1);
            $pageSize = min((int) ($params['page_size'] ?? 20), 100);

            $query = QimingRecord::with(['user'])->order('created_at', 'desc');

            // 搜索参数处理
            if (!empty($params['user_id'])) {
                $query->where('user_id', $params['user_id']);
            }

            if (!empty($params['surname'])) {
                $query->whereLike('surname', '%' . $params['surname'] . '%');
            }

            if (!empty($params['start_date'])) {
                $query->where('created_at', '>=', $params['start_date']);
            }
            if (!empty($params['end_date'])) {
                $query->where('created_at', '<=', $params['end_date'] . ' 23:59:59');
            }

            $total = $query->count();
            $list = $query->page($page, $pageSize)->select()->toArray();

            return $this->success([
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'list' => $list,
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_qiming_index', $e, '获取取名记录列表失败');
        }
    }

    /**
     * 获取取名记录详情
     */
    public function detail(int $id)
    {
        if (!$this->hasAdminPermission('qiming_view')) {
            return $this->error('无权限查看取名记录详情', 403);
        }

        try {
            $record = QimingRecord::with(['user'])->find($id);
            if (!$record) {
                return $this->error('记录不存在', 404);
            }

            return $this->success($record->toArray());
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_qiming_detail', $e, '获取取名记录详情失败');
        }
    }

    /**
     * 删除取名记录
     */
    public function delete(int $id)
    {
        if (!$this->hasAdminPermission('qiming_delete')) {
            return $this->error('无权限删除取名记录', 403);
        }

        try {
            $record = QimingRecord::find($id);
            if (!$record) {
                return $this->error('记录不存在', 404);
            }

            $record->delete();
            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_qiming_delete', $e, '删除取名记录失败');
        }
    }

    /**
     * 批量删除取名记录
     */
    public function batchDelete()
    {
        if (!$this->hasAdminPermission('qiming_delete')) {
            return $this->error('无权限删除取名记录', 403);
        }

        try {
            $ids = $this->request->post('ids', []);
            if (empty($ids) || !is_array($ids)) {
                return $this->error('请选择要删除的记录');
            }

            QimingRecord::whereIn('id', $ids)->delete();
            return $this->success(null, '批量删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_qiming_batch_delete', $e, '批量删除失败');
        }
    }

    /**
     * 获取统计数据
     */
    public function stats()
    {
        if (!$this->hasAdminPermission('qiming_view')) {
            return $this->error('无权限查看统计数据', 403);
        }

        try {
            $stats = [
                'total' => QimingRecord::count(),
                'today' => QimingRecord::where('created_at', '>=', date('Y-m-d'))->count(),
                'this_month' => QimingRecord::where('created_at', '>=', date('Y-m-01'))->count(),
                'this_year' => QimingRecord::where('created_at', '>=', date('Y-01-01'))->count(),
            ];

            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_qiming_stats', $e, '获取统计数据失败');
        }
    }
}

