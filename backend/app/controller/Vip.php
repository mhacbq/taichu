<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\VipOrder;
use app\service\ConfigService;
use app\service\VipService;
use think\response\Json;


/**
 * VIP会员控制器
 */
class Vip extends BaseController
{
    protected function ensureVipFeatureEnabled(): ?Json
    {
        if (ConfigService::isFeatureEnabled('vip')) {
            return null;
        }

        return $this->error('VIP功能暂未开放', 403);
    }

    protected function buildVipLogContext(array $user = [], string $vipType = '', string $payMethod = '', array $extra = []): array
    {
        $context = array_filter([
            'user_id' => (int) ($user['sub'] ?? 0),
            'vip_type' => $vipType,
            'pay_method' => $payMethod,
        ], static fn ($value) => !($value === '' || $value === 0 || $value === null));

        return array_merge($context, $extra);
    }

    /**
     * 中间件配置
     * packages/info/benefits 为公开接口，无需登录
     */
    protected $middleware = [
        \app\middleware\Auth::class => ['except' => ['packages', 'info', 'benefits']],
    ];
    
    /**
     * 获取VIP套餐列表（前端 getVipPackages 调用）
     */
    public function packages(): Json
    {
        if ($disabledResponse = $this->ensureVipFeatureEnabled()) {
            return $disabledResponse;
        }

        $pointsPrices = VipService::getVipPointsPrices();

        $packages = [
            [
                'id'             => 'month',
                'name'           => '连续包月',
                'price'          => (string) ConfigService::get('vip_month_price', VipOrder::PRICE_MONTH),
                'points_price'   => $pointsPrices['month'],
                'duration'       => 1,
                'points'         => 500,
                'is_recommended' => false,
                'description'    => json_encode(['每月赠送 500 积分', '解锁深度解读', '塔罗专属牌阵', '优先客服响应']),
            ],
            [
                'id'             => 'quarter',
                'name'           => '连续包季',
                'price'          => (string) ConfigService::get('vip_quarter_price', VipOrder::PRICE_QUARTER),
                'points_price'   => $pointsPrices['quarter'],
                'duration'       => 3,
                'points'         => 1800,
                'is_recommended' => true,
                'description'    => json_encode(['每季赠送 1800 积分', '解锁深度解读', '塔罗专属牌阵', '优先客服响应', '专属身份标识']),
            ],
            [
                'id'             => 'year',
                'name'           => '连续包年',
                'price'          => (string) ConfigService::get('vip_year_price', VipOrder::PRICE_YEAR),
                'points_price'   => $pointsPrices['year'],
                'duration'       => 12,
                'points'         => 8000,
                'is_recommended' => false,
                'description'    => json_encode(['每年赠送 8000 积分', '解锁深度解读', '塔罗专属牌阵', '优先客服响应', '专属身份标识', '新功能优先体验']),
            ],
        ];

        return $this->success($packages);
    }

    /**
     * 获取用户VIP状态（前端 getUserVipStatus 调用）
     */
    public function status(): Json
    {
        $user   = $this->request->user;
        $userId = $user['sub'];

        $vipInfo = VipService::getUserVipInfo($userId);

        return $this->success([
            'is_vip'      => $vipInfo['is_vip'],
            'expire_time' => $vipInfo['end_time'] ?? '',
        ]);
    }

    /**
     * 积分兑换VIP套餐（前端 purchaseVip 调用）
     */
    public function purchase(): Json
    {
        $user      = $this->request->user;
        $userId    = (int) $user['sub'];
        $packageId = $this->request->post('package_id', '');

        if ($disabledResponse = $this->ensureVipFeatureEnabled()) {
            return $disabledResponse;
        }

        if (!in_array($packageId, ['month', 'quarter', 'year'], true)) {
            return $this->error('无效的套餐类型', 400);
        }

        try {
            $result = VipService::purchaseWithPoints($userId, $packageId);

            if (!$result['success']) {
                return $this->error($result['message'] ?? '兑换失败', 400);
            }

            return $this->success([
                'expire_time' => $result['expire_time'],
                'balance'     => $result['balance'],
            ], $result['message']);
        } catch (\Exception $e) {
            return $this->respondSystemException(
                'vip_purchase_points',
                $e,
                '积分兑换VIP失败，请稍后重试',
                $this->buildVipLogContext($user, $packageId, 'points')
            );
        }
    }

    /**
     * 获取VIP购买记录（前端 getVipRecords 调用）
     */
    public function records(): Json
    {
        $user   = $this->request->user;
        $userId = $user['sub'];
        $page   = (int) $this->request->get('page', 1);
        $limit  = (int) $this->request->get('limit', 10);

        $page  = max(1, $page);
        $limit = min(100, max(1, $limit));

        $result = VipService::getUserOrders($userId, $page, $limit);

        return $this->success($result);
    }

    /**
     * 获取VIP信息
     */
    public function info(): Json
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        
        // 检查VIP功能是否开启
        if (!ConfigService::isFeatureEnabled('vip')) {
            return $this->error('VIP功能暂未开放', 403);
        }
        
        $vipInfo = VipService::getUserVipInfo($userId);
        
        return $this->success($vipInfo);
    }
    
    /**
     * 获取VIP权益
     */
    public function benefits(): Json
    {
        if ($disabledResponse = $this->ensureVipFeatureEnabled()) {
            return $disabledResponse;
        }
        
        $benefits = [

            'prices' => [
                'month' => ConfigService::get('vip_month_price', 19.9),
                'quarter' => ConfigService::get('vip_quarter_price', 49),
                'year' => ConfigService::get('vip_year_price', 168),
            ],
            'features' => [
                [
                    'icon' => 'star',
                    'title' => '无限次排盘',
                    'desc' => '不受次数限制，随时想看就看',
                ],
                [
                    'icon' => 'document',
                    'title' => '解锁专业报告',
                    'desc' => '详细命盘解读、性格分析',
                ],
                [
                    'icon' => 'diamond',
                    'title' => '积分加倍',
                    'desc' => '签到积分x' . ConfigService::get('vip_daily_points_multiplier', 2) . '倍',
                ],
                [
                    'icon' => 'heart',
                    'title' => '八字合婚',
                    'desc' => '免费使用合婚功能',
                ],
                [
                    'icon' => 'service',
                    'title' => '优先客服',
                    'desc' => '专属客服通道',
                ],
                [
                    'icon' => 'gift',
                    'title' => '新功能抢先',
                    'desc' => '优先体验新功能',
                ],
            ],
        ];
        
        return $this->success($benefits);
    }
    
    /**
     * 订阅VIP
     */
    public function subscribe(): Json
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $vipType = $this->request->post('type', '');
        $payMethod = $this->request->post('pay_method', 'wechat');
        
        if ($disabledResponse = $this->ensureVipFeatureEnabled()) {
            return $disabledResponse;
        }
        
        if (!in_array($vipType, ['month', 'quarter', 'year'], true)) {
            return $this->error('无效的VIP类型', 400);
        }
        
        try {
            $order = VipService::createOrder($userId, $vipType, $payMethod);
            
            return $this->success([
                'order_no' => $order['order_no'],
                'amount' => $order['amount'],
                'pay_params' => $order['pay_params'] ?? null,
            ], '订单创建成功');
        } catch (\Exception $e) {
            return $this->respondSystemException(
                'vip_subscribe_create_order',
                $e,
                '创建VIP订单失败，请稍后重试',
                $this->buildVipLogContext($user, $vipType, $payMethod)
            );
        }

    }
    
    /**
     * 获取VIP订单列表
     */
    public function orders(): Json
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 10);
        
        // 限制分页参数范围
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        
        $orders = VipOrder::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->page($page, $limit)
            ->select();
        
        $total = VipOrder::where('user_id', $userId)->count();
        
        return $this->success([
            'list' => $orders,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }
}