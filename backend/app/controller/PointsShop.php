<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\PointsProduct;
use app\model\PointsExchange;
use app\model\User;
use think\facade\Db;

/**
 * 积分商城控制器
 */
class PointsShop extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * 获取商品列表
     */
    public function products()
    {
        $type = $this->request->get('type', '');
        $limit = (int)$this->request->get('limit', 20);
        
        // 限制最大数量
        $limit = min($limit, 50);
        
        $products = PointsProduct::getOnlineProducts($type, $limit);
        
        return $this->success([
            'products' => $products,
            'types' => PointsProduct::getProductTypes(),
        ]);
    }
    
    /**
     * 获取商品详情
     */
    public function productDetail()
    {
        $productId = (int)$this->request->get('id', 0);
        
        if (!$productId) {
            return $this->error('请提供商品ID');
        }
        
        $product = PointsProduct::getProductDetail($productId);
        
        if (!$product) {
            return $this->error('商品不存在或已下架', 404);
        }
        
        // 获取当前用户的购买状态
        $user = $this->request->user;
        $purchaseStatus = PointsProduct::checkPurchaseLimit($productId, $user['sub']);
        
        return $this->success([
            'product' => $product,
            'can_purchase' => $purchaseStatus['allowed'],
            'purchase_message' => $purchaseStatus['message'],
        ]);
    }
    
    /**
     * 兑换商品
     */
    public function exchange()
    {
        $data = $this->request->post();
        
        // 验证参数
        if (empty($data['product_id'])) {
            return $this->error('请选择要兑换的商品');
        }
        
        $productId = (int)$data['product_id'];
        $user = $this->request->user;
        $userId = $user['sub'];
        
        // 获取商品信息
        $product = PointsProduct::find($productId);
        
        if (!$product) {
            return $this->error('商品不存在', 404);
        }
        
        // 检查商品状态
        if ($product['status'] !== PointsProduct::STATUS_ONLINE) {
            return $this->error('商品已下架');
        }
        
        if ($product['stock'] <= 0) {
            return $this->error('商品已售罄');
        }
        
        // 检查购买限制
        $purchaseLimit = PointsProduct::checkPurchaseLimit($productId, $userId);
        if (!$purchaseLimit['allowed']) {
            return $this->error($purchaseLimit['message']);
        }
        
        // 获取用户信息并检查积分
        $userModel = User::find($userId);
        if (!$userModel) {
            return $this->error('用户不存在', 404);
        }
        
        $pointsCost = $product['points_price'];
        
        if ($userModel->points < $pointsCost) {
            return $this->error('积分不足，无法兑换', 403, [
                'need_points' => $pointsCost,
                'current_points' => $userModel->points,
                'shortage' => $pointsCost - $userModel->points,
            ]);
        }
        
        // 开始事务
        Db::startTrans();
        try {
            // 扣除积分
            $userModel->deductPoints($pointsCost);
            
            // 记录积分变动
            \app\model\PointsRecord::record(
                $userId,
                '积分商城兑换',
                -$pointsCost,
                'exchange',
                $productId,
                "兑换商品：{$product['name']}"
            );
            
            // 减少库存
            PointsProduct::decreaseStock($productId);
            
            // 创建兑换记录
            $exchange = PointsExchange::createExchange(
                $userId,
                $productId,
                $product->toArray(),
                $pointsCost
            );
            
            // 处理兑换（根据不同商品类型）
            $processResult = $exchange->processExchange();
            
            if ($processResult['success']) {
                $exchange->complete($processResult['message']);
                
                // 如果需要填写地址，保存相关信息
                if (!empty($processResult['need_address'])) {
                    $exchange->extra_info = json_encode([
                        'need_address' => true,
                        'address_filled' => false,
                    ]);
                    $exchange->save();
                }
                
                Db::commit();
                
                return $this->success([
                    'exchange_id' => $exchange->id,
                    'exchange_no' => $exchange->exchange_no,
                    'product_name' => $product['name'],
                    'points_cost' => $pointsCost,
                    'remaining_points' => $userModel->points,
                    'message' => $processResult['message'],
                    'redeem_code' => $processResult['code'] ?? null,
                    'need_address' => $processResult['need_address'] ?? false,
                ], '兑换成功');
            } else {
                // 处理失败，回滚
                Db::rollback();
                return $this->error($processResult['message']);
            }
            
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('兑换失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取我的兑换记录
     */
    public function myExchanges()
    {
        $user = $this->request->user;
        $page = (int)$this->request->get('page', 1);
        $limit = (int)$this->request->get('limit', 10);
        
        $exchanges = PointsExchange::getUserExchanges($user['sub'], $page, $limit);
        
        // 获取总数
        $total = PointsExchange::where('user_id', $user['sub'])->count();
        
        return $this->success([
            'list' => $exchanges,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
        ]);
    }
    
    /**
     * 获取兑换详情
     */
    public function exchangeDetail()
    {
        $exchangeId = (int)$this->request->get('id', 0);
        $user = $this->request->user;
        
        if (!$exchangeId) {
            return $this->error('请提供兑换记录ID');
        }
        
        $exchange = PointsExchange::where('id', $exchangeId)
            ->where('user_id', $user['sub'])
            ->find();
        
        if (!$exchange) {
            return $this->error('兑换记录不存在', 404);
        }
        
        $detail = [
            'id' => $exchange['id'],
            'exchange_no' => $exchange['exchange_no'],
            'product_name' => $exchange['product_name'],
            'product_type' => $exchange['product_type'],
            'points_cost' => $exchange['points_cost'],
            'quantity' => $exchange['quantity'],
            'status' => $exchange['status'],
            'status_text' => PointsExchange::getStatusText($exchange['status']),
            'redeem_code' => $exchange['redeem_code'],
            'valid_until' => $exchange['valid_until'],
            'extra_info' => json_decode($exchange['extra_info'] ?? '{}', true),
            'remark' => $exchange['remark'],
            'created_at' => $exchange['created_at'],
            'completed_at' => $exchange['completed_at'],
        ];
        
        return $this->success($detail);
    }
    
    /**
     * 填写收货地址（实物商品）
     */
    public function fillAddress()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
        // 验证参数
        if (empty($data['exchange_id'])) {
            return $this->error('请提供兑换记录ID');
        }
        
        $requiredFields = ['name', 'phone', 'province', 'city', 'district', 'address'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->error("请填写{$field}");
            }
        }
        
        // 验证手机号
        if (!preg_match('/^1[3-9]\d{9}$/', $data['phone'])) {
            return $this->error('手机号格式不正确');
        }
        
        $exchangeId = (int)$data['exchange_id'];
        
        // 查询兑换记录
        $exchange = PointsExchange::where('id', $exchangeId)
            ->where('user_id', $user['sub'])
            ->where('status', PointsExchange::STATUS_COMPLETED)
            ->find();
        
        if (!$exchange) {
            return $this->error('兑换记录不存在或状态不正确', 404);
        }
        
        // 保存地址信息
        $addressInfo = [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'province' => $data['province'],
            'city' => $data['city'],
            'district' => $data['district'],
            'address' => $data['address'],
            'postal_code' => $data['postal_code'] ?? '',
        ];
        
        $extraInfo = json_decode($exchange['extra_info'] ?? '{}', true);
        $extraInfo['address'] = $addressInfo;
        $extraInfo['address_filled'] = true;
        $extraInfo['address_filled_at'] = date('Y-m-d H:i:s');
        
        $exchange->extra_info = json_encode($extraInfo);
        $exchange->save();
        
        return $this->success(null, '地址填写成功，等待发货');
    }
    
    /**
     * 获取商城首页数据
     */
    public function home()
    {
        $user = $this->request->user;
        $userModel = User::find($user['sub']);
        
        // 获取推荐商品
        $featuredProducts = PointsProduct::getOnlineProducts('', 6);
        
        // 获取各类型商品
        $vipProducts = PointsProduct::getOnlineProducts(PointsProduct::TYPE_VIP, 3);
        $serviceProducts = PointsProduct::getOnlineProducts(PointsProduct::TYPE_SERVICE, 4);
        
        return $this->success([
            'user_points' => $userModel->points ?? 0,
            'featured' => $featuredProducts,
            'categories' => [
                ['key' => 'vip', 'name' => '会员特权', 'products' => $vipProducts],
                ['key' => 'service', 'name' => '服务兑换', 'products' => $serviceProducts],
            ],
            'banners' => [
                [
                    'title' => '积分当钱花',
                    'subtitle' => '好礼兑不停',
                    'image' => '/static/shop/banner1.png',
                ],
                [
                    'title' => 'VIP特权',
                    'subtitle' => '尊享更多服务',
                    'image' => '/static/shop/banner2.png',
                ],
            ],
        ]);
    }
}