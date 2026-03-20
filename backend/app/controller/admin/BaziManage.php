<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\BaziRecord;
use app\service\SchemaInspector;
use think\Request;
use think\facade\Db;

/**
 * 八字测算结果管理控制器
 */
class BaziManage extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取八字测算结果列表
     */
    public function index()
    {
        if (!$this->hasAdminPermission('bazi_view')) {
            return $this->error('无权限查看八字测算结果', 403);
        }

        try {
            $params = $this->request->get();
            $page = (int) ($params['page'] ?? 1);
            $pageSize = min((int) ($params['page_size'] ?? 20), 100);

            $query = BaziRecord::order('created_at', 'desc');

            // 用户筛选
            if (!empty($params['user_id'])) {
                $query->where('user_id', $params['user_id']);
            }

            // 日期范围筛选
            if (!empty($params['start_date'])) {
                $query->where('created_at', '>=', $params['start_date']);
            }
            if (!empty($params['end_date'])) {
                $query->where('created_at', '<=', $params['end_date'] . ' 23:59:59');
            }

            // 关键词搜索
            if (!empty($params['keyword'])) {
                $keyword = '%' . $params['keyword'] . '%';
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('name', $keyword)
                      ->whereOr('gender', $keyword)
                      ->whereOr('birthday', $keyword);
                });
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
            return $this->respondSystemException('admin_bazi_index', $e, '获取八字测算结果列表失败');
        }
    }

    /**
     * 获取八字测算结果详情
     */
    public function detail(int $id)
    {
        if (!$this->hasAdminPermission('bazi_view')) {
            return $this->error('无权限查看八字测算结果详情', 403);
        }

        try {
            $record = BaziRecord::find($id);
            if (!$record) {
                return $this->error('测算结果不存在', 404);
            }

            return $this->success($record->toArray());
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_bazi_detail', $e, '获取八字测算结果详情失败');
        }
    }

    /**
     * 删除八字测算结果
     */
    public function delete(int $id)
    {
        if (!$this->hasAdminPermission('bazi_delete')) {
            return $this->error('无权限删除八字测算结果', 403);
        }

        try {
            $record = BaziRecord::find($id);
            if (!$record) {
                return $this->error('测算结果不存在', 404);
            }

            $record->delete();
            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_bazi_delete', $e, '删除八字测算结果失败');
        }
    }

    /**
     * 批量删除八字测算结果
     */
    public function batchDelete()
    {
        if (!$this->hasAdminPermission('bazi_delete')) {
            return $this->error('无权限删除八字测算结果', 403);
        }

        try {
            $ids = $this->request->post('ids', []);
            if (empty($ids) || !is_array($ids)) {
                return $this->error('请选择要删除的记录');
            }

            BaziRecord::whereIn('id', $ids)->delete();
            return $this->success(null, '批量删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_bazi_batch_delete', $e, '批量删除失败');
        }
    }

    /**
     * 获取统计数据
     */
    public function stats()
    {
        if (!$this->hasAdminPermission('bazi_view')) {
            return $this->error('无权限查看统计数据', 403);
        }

        try {
            $stats = [
                'total' => BaziRecord::count(),
                'today' => BaziRecord::where('created_at', '>=', date('Y-m-d'))->count(),
                'this_month' => BaziRecord::where('created_at', '>=', date('Y-m-01'))->count(),
                'this_year' => BaziRecord::where('created_at', '>=', date('Y-01-01'))->count(),
            ];

            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_bazi_stats', $e, '获取统计数据失败');
        }
    }
}
