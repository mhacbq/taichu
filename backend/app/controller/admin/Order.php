<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\AdminStatsService;
use think\facade\Log;

/**
 * 后台订单管理控制器
 */
class Order extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取订单列表
     */
    public function index()
    {
        if (!$this->hasAnyAdminPermission(['stats_view', 'config_manage'])) {
            return $this->error('无权限查看订单列表', 403);
        }

        try {
            $params = $this->request->get();
            $data = AdminStatsService::getOrderList($params);
            return $this->success($data);
        } catch (\Throwable $e) {
            Log::error('后台获取订单列表失败', [
                'admin_id' => $this->getAdminId(),
                'error' => $e->getMessage(),
            ]);
            return $this->error('获取订单列表失败，请稍后重试', 500);
        }
    }

    /**
     * 获取订单详情
     */
    public function detail(int $id)
    {
        if (!$this->hasAnyAdminPermission(['stats_view', 'config_manage'])) {
            return $this->error('无权限查看订单详情', 403);
        }

        try {
            $order = \think\facade\Db::table('tc_vip_order')
                ->alias('o')
                ->leftJoin('tc_user u', 'o.user_id = u.id')
                ->where('o.id', $id)
                ->field([
                    'o.*',
                    'u.nickname',
                    'u.phone',
                ])
                ->find();

            if (!$order) {
                return $this->error('订单不存在', 404);
            }

            return $this->success($order);
        } catch (\Throwable $e) {
            Log::error('后台获取订单详情失败', [
                'admin_id' => $this->getAdminId(),
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('获取订单详情失败，请稍后重试', 500);
        }
    }

    /**
     * 处理退款
     */
    public function refund()
    {
        if (!$this->hasAdminPermission('config_manage')) {
            return $this->error('无权限处理退款', 403);
        }

        try {
            $data = $this->request->post();

            if (empty($data['order_id']) || empty($data['amount'])) {
                return $this->error('参数错误');
            }

            $reason = trim((string) ($data['reason'] ?? '管理员退款')) ?: '管理员退款';

            AdminStatsService::refundOrder(
                (int) $data['order_id'],
                (float) $data['amount'],
                $reason,
                $this->getAdminId()
            );

            return $this->success([], '退款处理成功');
        } catch (\Throwable $e) {
            Log::error('后台处理退款失败', [
                'admin_id' => $this->getAdminId(),
                'order_id' => $data['order_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return $this->error('退款处理失败，请稍后重试', 500);
        }
    }

    /**
     * 获取VIP套餐列表
     */
    public function packages()
    {
        if (!$this->hasAnyAdminPermission(['stats_view', 'config_manage'])) {
            return $this->error('无权限查看套餐列表', 403);
        }

        try {
            $packages = \think\facade\Db::table('tc_vip_package')
                ->where('status', 1)
                ->order('sort_order', 'ASC')
                ->select();

            return $this->success($packages);
        } catch (\Throwable $e) {
            Log::error('后台获取VIP套餐失败', [
                'admin_id' => $this->getAdminId(),
                'error' => $e->getMessage(),
            ]);
            return $this->error('获取套餐列表失败，请稍后重试', 500);
        }
    }

    /**
     * 保存VIP套餐
     */
    public function savePackage()
    {
        if (!$this->hasAdminPermission('config_manage')) {
            return $this->error('无权限修改VIP套餐', 403);
        }

        try {
            $data = $this->request->post();

            if (empty($data['package_name']) || empty($data['price']) || empty($data['duration'])) {
                return $this->error('请填写完整信息');
            }

            $packageData = [
                'package_name' => $data['package_name'],
                'duration' => (int) $data['duration'],
                'price' => (float) $data['price'],
                'original_price' => (float) ($data['original_price'] ?? $data['price']),
                'description' => $data['description'] ?? '',
                'features' => json_encode($data['features'] ?? []),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_recommend' => (int) ($data['is_recommend'] ?? 0),
                'status' => (int) ($data['status'] ?? 1),
            ];

            if (!empty($data['id'])) {
                $packageData['updated_at'] = date('Y-m-d H:i:s');
                \think\facade\Db::table('tc_vip_package')
                    ->where('id', $data['id'])
                    ->update($packageData);
            } else {
                $packageData['created_at'] = date('Y-m-d H:i:s');
                \think\facade\Db::table('tc_vip_package')->insert($packageData);
            }

            return $this->success([], '保存成功');
        } catch (\Throwable $e) {
            Log::error('后台保存VIP套餐失败', [
                'admin_id' => $this->getAdminId(),
                'package_id' => $data['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return $this->error('保存失败，请稍后重试', 500);
        }
    }
}
