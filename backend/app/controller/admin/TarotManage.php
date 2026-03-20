<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\TarotRecord;
use think\Request;

/**
 * 塔罗测算结果管理控制器
 */
class TarotManage extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取塔罗测算结果列表
     */
    public function index()
    {
        if (!$this->hasAdminPermission('tarot_view')) {
            return $this->error('无权限查看塔罗测算结果', 403);
        }

        try {
            $params = $this->request->get();
            $page = (int) ($params['page'] ?? 1);
            $pageSize = min((int) ($params['page_size'] ?? 20), 100);

            $query = TarotRecord::order('created_at', 'desc');

            // 用户筛选
            if (!empty($params['user_id'])) {
                $query->where('user_id', $params['user_id']);
            }

            // 牌阵类型筛选
            if (!empty($params['spread_type'])) {
                $query->where('spread_type', $params['spread_type']);
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
                    $q->whereLike('question', $keyword)
                      ->whereOr('interpretation', $keyword);
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
            return $this->respondSystemException('admin_tarot_index', $e, '获取塔罗测算结果列表失败');
        }
    }

    /**
     * 获取塔罗测算结果详情
     */
    public function detail(int $id)
    {
        if (!$this->hasAdminPermission('tarot_view')) {
            return $this->error('无权限查看塔罗测算结果详情', 403);
        }

        try {
            $record = TarotRecord::find($id);
            if (!$record) {
                return $this->error('测算结果不存在', 404);
            }

            return $this->success($record->toArray());
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_tarot_detail', $e, '获取塔罗测算结果详情失败');
        }
    }

    /**
     * 删除塔罗测算结果
     */
    public function delete(int $id)
    {
        if (!$this->hasAdminPermission('tarot_delete')) {
            return $this->error('无权限删除塔罗测算结果', 403);
        }

        try {
            $record = TarotRecord::find($id);
            if (!$record) {
                return $this->error('测算结果不存在', 404);
            }

            $record->delete();
            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_tarot_delete', $e, '删除塔罗测算结果失败');
        }
    }

    /**
     * 批量删除塔罗测算结果
     */
    public function batchDelete()
    {
        if (!$this->hasAdminPermission('tarot_delete')) {
            return $this->error('无权限删除塔罗测算结果', 403);
        }

        try {
            $ids = $this->request->post('ids', []);
            if (empty($ids) || !is_array($ids)) {
                return $this->error('请选择要删除的记录');
            }

            TarotRecord::whereIn('id', $ids)->delete();
            return $this->success(null, '批量删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_tarot_batch_delete', $e, '批量删除失败');
        }
    }

    /**
     * 获取统计数据
     */
    public function stats()
    {
        if (!$this->hasAdminPermission('tarot_view')) {
            return $this->error('无权限查看统计数据', 403);
        }

        try {
            $stats = [
                'total' => TarotRecord::count(),
                'today' => TarotRecord::where('created_at', '>=', date('Y-m-d'))->count(),
                'this_month' => TarotRecord::where('created_at', '>=', date('Y-m-01'))->count(),
                'this_year' => TarotRecord::where('created_at', '>=', date('Y-01-01'))->count(),
            ];

            // 按牌阵类型统计
            $spreadTypeStats = TarotRecord::field('spread_type, COUNT(*) as count')
                ->group('spread_type')
                ->select()
                ->toArray();
            $stats['spread_type'] = $spreadTypeStats;

            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_tarot_stats', $e, '获取统计数据失败');
        }
    }
}
