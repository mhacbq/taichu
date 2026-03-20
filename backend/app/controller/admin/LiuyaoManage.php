<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\SchemaInspector;
use think\Request;
use think\facade\Db;

/**
 * 六爻测算结果管理控制器
 */
class LiuyaoManage extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    private string $tableName = 'tc_liuyao_record';

    /**
     * 获取六爻测算结果列表
     */
    public function index()
    {
        if (!$this->hasAdminPermission('liuyao_view')) {
            return $this->error('无权限查看六爻测算结果', 403);
        }

        try {
            $tableName = SchemaInspector::resolveFirstExistingTable([
                'tc_liuyao_record',
                'liuyao_records'
            ]);

            if (!$tableName) {
                return $this->error('六爻测算表不存在', 500);
            }

            $params = $this->request->get();
            $page = (int) ($params['page'] ?? 1);
            $pageSize = min((int) ($params['page_size'] ?? 20), 100);

            $query = Db::table($tableName)->order('created_at', 'desc');

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
                    $q->whereLike('question', $keyword)
                      ->whereOr('result', $keyword);
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
            return $this->respondSystemException('admin_liuyao_index', $e, '获取六爻测算结果列表失败');
        }
    }

    /**
     * 获取六爻测算结果详情
     */
    public function detail(int $id)
    {
        if (!$this->hasAdminPermission('liuyao_view')) {
            return $this->error('无权限查看六爻测算结果详情', 403);
        }

        try {
            $tableName = SchemaInspector::resolveFirstExistingTable([
                'tc_liuyao_record',
                'liuyao_records'
            ]);

            if (!$tableName) {
                return $this->error('六爻测算表不存在', 500);
            }

            $record = Db::table($tableName)->where('id', $id)->find();
            if (!$record) {
                return $this->error('测算结果不存在', 404);
            }

            return $this->success($record);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_liuyao_detail', $e, '获取六爻测算结果详情失败');
        }
    }

    /**
     * 删除六爻测算结果
     */
    public function delete(int $id)
    {
        if (!$this->hasAdminPermission('liuyao_delete')) {
            return $this->error('无权限删除六爻测算结果', 403);
        }

        try {
            $tableName = SchemaInspector::resolveFirstExistingTable([
                'tc_liuyao_record',
                'liuyao_records'
            ]);

            if (!$tableName) {
                return $this->error('六爻测算表不存在', 500);
            }

            $record = Db::table($tableName)->where('id', $id)->find();
            if (!$record) {
                return $this->error('测算结果不存在', 404);
            }

            Db::table($tableName)->where('id', $id)->delete();
            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_liuyao_delete', $e, '删除六爻测算结果失败');
        }
    }

    /**
     * 批量删除六爻测算结果
     */
    public function batchDelete()
    {
        if (!$this->hasAdminPermission('liuyao_delete')) {
            return $this->error('无权限删除六爻测算结果', 403);
        }

        try {
            $tableName = SchemaInspector::resolveFirstExistingTable([
                'tc_liuyao_record',
                'liuyao_records'
            ]);

            if (!$tableName) {
                return $this->error('六爻测算表不存在', 500);
            }

            $ids = $this->request->post('ids', []);
            if (empty($ids) || !is_array($ids)) {
                return $this->error('请选择要删除的记录');
            }

            Db::table($tableName)->whereIn('id', $ids)->delete();
            return $this->success(null, '批量删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_liuyao_batch_delete', $e, '批量删除失败');
        }
    }

    /**
     * 获取统计数据
     */
    public function stats()
    {
        if (!$this->hasAdminPermission('liuyao_view')) {
            return $this->error('无权限查看统计数据', 403);
        }

        try {
            $tableName = SchemaInspector::resolveFirstExistingTable([
                'tc_liuyao_record',
                'liuyao_records'
            ]);

            if (!$tableName) {
                return $this->error('六爻测算表不存在', 500);
            }

            $stats = [
                'total' => Db::table($tableName)->count(),
                'today' => Db::table($tableName)->where('created_at', '>=', date('Y-m-d'))->count(),
                'this_month' => Db::table($tableName)->where('created_at', '>=', date('Y-m-01'))->count(),
                'this_year' => Db::table($tableName)->where('created_at', '>=', date('Y-01-01'))->count(),
            ];

            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_liuyao_stats', $e, '获取统计数据失败');
        }
    }
}
