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
    /**
     * 中间件配置
     */
    protected array $middleware = [
        \app\middleware\Auth::class,
    ];
    
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
        // 检查VIP功能是否开启
        if (!ConfigService::isFeatureEnabled('vip')) {
            return $this->error('VIP功能暂未开放', 403);
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
        
        // 检查VIP功能是否开启
        if (!ConfigService::isFeatureEnabled('vip')) {
            return $this->error('VIP功能暂未开放', 403);
        }
        
        if (!in_array($vipType, ['month', 'quarter', 'year'])) {
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
            return $this->error($e->getMessage(), 500);
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