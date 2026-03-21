<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\VipPackage;
use think\Request;
use think\facade\Db;

class VipPackages extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取VIP套餐列表
     */
    public function getList()
    {
        if (!$this->hasAdminPermission('vip_view')) {
            return $this->error('无权限查看VIP套餐', 403);
        }

        $params = $this->request->get();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);
        $status = $params['status'] ?? '';

        try {
            $query = VipPackage::where('is_deleted', 0);

            if ($status !== '') {
                $query->where('status', $status);
            }

            $total = $query->count();
            $list = $query->order('sort', 'asc')
                ->order('id', 'desc')
                ->limit($pageSize)
                ->page($page)
                ->select()
                ->toArray();

            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_vip_packages_list', $e, '获取VIP套餐列表失败');
        }
    }

    /**
     * 获取VIP套餐详情
     */
    public function getDetail($id)
    {
        if (!$this->hasAdminPermission('vip_view')) {
            return $this->error('无权限查看VIP套餐', 403);
        }

        try {
            $package = VipPackage::find($id);
            if (!$package) {
                return $this->error('VIP套餐不存在', 404);
            }

            return $this->success($package);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_vip_packages_detail', $e, '获取VIP套餐详情失败');
        }
    }

    /**
     * 保存VIP套餐
     */
    public function save()
    {
        if (!$this->hasAdminPermission('vip_edit')) {
            return $this->error('无权限编辑VIP套餐', 403);
        }

        $data = $this->request->post();
        $id = $data['id'] ?? 0;

        try {
            if ($id) {
                $package = VipPackage::find($id);
                if (!$package) {
                    return $this->error('VIP套餐不存在', 404);
                }
                $package->save($data);
            } else {
                $package = VipPackage::create($data);
            }

            $this->logOperation('save_vip_package', 'vip', [
                'package_id' => $package->id,
                'name' => $package->name
            ]);

            return $this->success($package, '保存成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_vip_packages_save', $e, '保存VIP套餐失败');
        }
    }

    /**
     * 删除VIP套餐
     */
    public function delete($id)
    {
        if (!$this->hasAdminPermission('vip_edit')) {
            return $this->error('无权限删除VIP套餐', 403);
        }

        try {
            $package = VipPackage::find($id);
            if (!$package) {
                return $this->error('VIP套餐不存在', 404);
            }

            $package->is_deleted = 1;
            $package->save();

            $this->logOperation('delete_vip_package', 'vip', [
                'package_id' => $id,
                'name' => $package->name
            ]);

            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_vip_packages_delete', $e, '删除VIP套餐失败');
        }
    }

    /**
     * 批量更新状态
     */
    public function batchUpdateStatus()
    {
        if (!$this->hasAdminPermission('vip_edit')) {
            return $this->error('无权限编辑VIP套餐', 403);
        }

        $data = $this->request->post();
        $ids = $data['ids'] ?? [];
        $status = $data['status'] ?? 0;

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择套餐');
        }

        try {
            $count = VipPackage::whereIn('id', $ids)->update(['status' => $status]);

            $this->logOperation('batch_update_vip_status', 'vip', [
                'ids' => $ids,
                'status' => $status,
                'count' => $count
            ]);

            return $this->success(['count' => $count], "更新{$count}个套餐状态");
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_vip_packages_batch_status', $e, '批量更新状态失败');
        }
    }

    /**
     * 更新排序
     */
    public function updateSort()
    {
        if (!$this->hasAdminPermission('vip_edit')) {
            return $this->error('无权限编辑VIP套餐', 403);
        }

        $data = $this->request->post();
        $sortData = $data['sort_data'] ?? [];

        if (empty($sortData)) {
            return $this->error('排序数据不能为空');
        }

        try {
            foreach ($sortData as $item) {
                VipPackage::where('id', $item['id'])->update(['sort' => $item['sort']]);
            }

            return $this->success([], '排序更新成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_vip_packages_update_sort', $e, '更新排序失败');
        }
    }

    /**
     * 获取套餐统计
     */
    public function getStats()
    {
        if (!$this->hasAdminPermission('vip_view')) {
            return $this->error('无权限查看VIP统计', 403);
        }

        try {
            $stats = VipPackage::where('is_deleted', 0)
                ->field([
                    'COUNT(*) as total_packages',
                    'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_packages',
                    'SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as inactive_packages',
                    'AVG(price) as avg_price',
                    'MIN(price) as min_price',
                    'MAX(price) as max_price'
                ])
                ->find();

            $packageSales = Db::table('tc_vip_order')
                ->field('package_id, COUNT(*) as sales_count, SUM(price) as total_amount')
                ->where('status', 1)
                ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime('-30 days')))
                ->group('package_id')
                ->select()
                ->toArray();

            return $this->success([
                'overview' => $stats,
                'sales' => $packageSales
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_vip_packages_stats', $e, '获取套餐统计失败');
        }
    }
}
