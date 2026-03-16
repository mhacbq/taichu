<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\AdminStatsService;
use think\Request;

/**
 * 后台订单管理控制器
 */
class Order extends BaseController
{
    /**
     * 获取订单列表
     */
    public function index()
    {
        try {
            $params = $this->request->get();
            $data = AdminStatsService::getOrderList($params);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error('获取订单列表失败：' . $e->getMessage());
        }
    }

    /**
     * 获取订单详情
     */
    public function detail(int $id)
    {
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
                return $this->error('订单不存在');
            }

            return $this->success($order);
        } catch (\Exception $e) {
            return $this->error('获取订单详情失败：' . $e->getMessage());
        }
    }

    /**
     * 处理退款
     */
    public function refund()
    {
        try {
            $data = $this->request->post();

            // 验证参数
            if (empty($data['order_id']) || empty($data['amount'])) {
                return $this->error('参数错误');
            }

            $adminId = $this->request->adminId ?? 0;
            $reason = $data['reason'] ?? '管理员退款';

            AdminStatsService::refundOrder(
                (int)$data['order_id'],
                (float)$data['amount'],
                $reason,
                $adminId
            );

            return $this->success([], '退款处理成功');
        } catch (\Exception $e) {
            return $this->error('退款处理失败：' . $e->getMessage());
        }
    }

    /**
     * 获取VIP套餐列表
     */
    public function packages()
    {
        try {
            $packages = \think\facade\Db::table('tc_vip_package')
                ->where('status', 1)
                ->order('sort_order', 'ASC')
                ->select();

            return $this->success($packages);
        } catch (\Exception $e) {
            return $this->error('获取套餐列表失败：' . $e->getMessage());
        }
    }

    /**
     * 保存VIP套餐
     */
    public function savePackage()
    {
        try {
            $data = $this->request->post();

            // 验证必填字段
            if (empty($data['package_name']) || empty($data['price']) || empty($data['duration'])) {
                return $this->error('请填写完整信息');
            }

            $packageData = [
                'package_name' => $data['package_name'],
                'duration' => (int)$data['duration'],
                'price' => (float)$data['price'],
                'original_price' => (float)($data['original_price'] ?? $data['price']),
                'description' => $data['description'] ?? '',
                'features' => json_encode($data['features'] ?? []),
                'sort_order' => (int)($data['sort_order'] ?? 0),
                'is_recommend' => (int)($data['is_recommend'] ?? 0),
                'status' => (int)($data['status'] ?? 1),
            ];

            if (!empty($data['id'])) {
                // 更新
                $packageData['updated_at'] = date('Y-m-d H:i:s');
                \think\facade\Db::table('tc_vip_package')
                    ->where('id', $data['id'])
                    ->update($packageData);
            } else {
                // 新增
                $packageData['created_at'] = date('Y-m-d H:i:s');
                \think\facade\Db::table('tc_vip_package')->insert($packageData);
            }

            return $this->success([], '保存成功');
        } catch (\Exception $e) {
            return $this->error('保存失败：' . $e->getMessage());
        }
    }
}
