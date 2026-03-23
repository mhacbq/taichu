
<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

/**
 * 流年运势记录管理控制器
 */
class YearlyFortuneManage extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    private string $tableName = 'yearly_fortune';

    /**
     * 获取流年运势记录列表
     */
    public function index()
    {
        if (!$this->hasAdminPermission('yearly_fortune_view')) {
            return $this->error('无权限查看流年运势记录', 403);
        }

        try {
            $params = $this->request->get();
            $page = (int) ($params['page'] ?? 1);
            $pageSize = min((int) ($params['page_size'] ?? 20), 100);

            $query = Db::name($this->tableName)->order('created_at', 'desc');

            // 用户筛选
            if (!empty($params['user_id'])) {
                $query->where('user_id', $params['user_id']);
            }

            // 年份筛选
            if (!empty($params['year'])) {
                $query->where('year', $params['year']);
            }

            // 日期范围筛选
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
            return $this->respondSystemException('admin_yearly_fortune_index', $e, '获取流年运势记录列表失败');
        }
    }

    /**
     * 获取流年运势记录详情
     */
    public function detail(int $id)
    {
        if (!$this->hasAdminPermission('yearly_fortune_view')) {
            return $this->error('无权限查看流年运势记录详情', 403);
        }

        try {
            $record = Db::name($this->tableName)->where('id', $id)->find();
            if (!$record) {
                return $this->error('记录不存在', 404);
            }

            return $this->success($record);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_yearly_fortune_detail', $e, '获取流年运势记录详情失败');
        }
    }

    /**
     * 删除流年运势记录
     */
    public function delete(int $id)
    {
        if (!$this->hasAdminPermission('yearly_fortune_delete')) {
            return $this->error('无权限删除流年运势记录', 403);
        }

        try {
            $record = Db::name($this->tableName)->where('id', $id)->find();
            if (!$record) {
                return $this->error('记录不存在', 404);
            }

            Db::name($this->tableName)->where('id', $id)->delete();
            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_yearly_fortune_delete', $e, '删除流年运势记录失败');
        }
    }

    /**
     * 批量删除流年运势记录
     */
    public function batchDelete()
    {
        if (!$this->hasAdminPermission('yearly_fortune_delete')) {
            return $this->error('无权限删除流年运势记录', 403);
        }

        try {
            $ids = $this->request->post('ids', []);
            if (empty($ids) || !is_array($ids)) {
                return $this->error('请选择要删除的记录');
            }

            Db::name($this->tableName)->whereIn('id', $ids)->delete();
            return $this->success(null, '批量删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_yearly_fortune_batch_delete', $e, '批量删除失败');
        }
    }

    /**
     * 获取统计数据
     */
    public function stats()
    {
        if (!$this->hasAdminPermission('yearly_fortune_view')) {
            return $this->error('无权限查看统计数据', 403);
        }

        try {
            $stats = [
                'total' => Db::name($this->tableName)->count(),
                'today' => Db::name($this->tableName)->where('created_at', '>=', date('Y-m-d'))->count(),
                'this_month' => Db::name($this->tableName)->where('created_at', '>=', date('Y-m-01'))->count(),
                'this_year' => Db::name($this->tableName)->where('created_at', '>=', date('Y-01-01'))->count(),
            ];

            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_yearly_fortune_stats', $e, '获取统计数据失败');
        }
    }
}
