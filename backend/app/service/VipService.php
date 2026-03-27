<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use app\model\UserVip;
use app\model\VipOrder;
use app\service\ConfigService;
use app\service\PointsService;

/**
 * VIP服务类
 */
class VipService
{
    /**
     * 获取用户VIP信息
     */
    public static function getUserVipInfo(int $userId): array
    {
        $vip = UserVip::getUserVip($userId);
        
        if (!$vip) {
            return [
                'is_vip' => false,
                'vip_type' => null,
                'vip_type_name' => null,
                'start_time' => null,
                'end_time' => null,
                'remaining_days' => 0,
            ];
        }
        
        return [
            'is_vip' => true,
            'vip_type' => $vip->vip_type,
            'vip_type_name' => $vip->getVipTypeName(),
            'start_time' => $vip->start_time,
            'end_time' => $vip->end_time,
            'remaining_days' => $vip->getRemainingDays(),
        ];
    }
    
    /**
     * 检查用户是否为VIP
     */
    public static function isVip(int $userId): bool
    {
        return UserVip::isVip($userId);
    }
    
    /**
     * 创建VIP订单
     */
    public static function createOrder(int $userId, string $vipType, string $payMethod = 'wechat'): array
    {
        // 验证VIP类型
        if (!in_array($vipType, ['month', 'quarter', 'year'])) {
            throw new \Exception('无效的VIP类型');
        }
        
        // 获取客户端信息
        $clientIp = request()->ip() ?? '';
        $userAgent = request()->header('User-Agent') ?? '';
        
        // 创建订单
        $order = VipOrder::createOrder($userId, $vipType, $clientIp, $userAgent);
        
        if (empty($order)) {
            throw new \Exception('订单创建失败');
        }
        
        // 生成支付参数（这里简化处理，实际应该调用微信支付接口）
        $payParams = self::generatePayParams($order, $payMethod);
        
        return [
            'order_no' => $order['order_no'],
            'amount' => $order['amount'],
            'vip_type' => $order['vip_type'],
            'pay_params' => $payParams,
        ];
    }
    
    /**
     * 生成支付参数
     */
    protected static function generatePayParams(array $order, string $payMethod): array
    {
        // 这里应该调用实际的支付接口生成支付参数
        // 简化处理，返回模拟数据
        return [
            'pay_method' => $payMethod,
            'order_no' => $order['order_no'],
            'amount' => $order['amount'],
            // 实际应该返回微信支付的调起参数
            'timestamp' => time(),
            'nonce_str' => md5(uniqid()),
        ];
    }
    
    /**
     * 处理支付回调
     */
    public static function handlePayCallback(string $orderNo, array $callbackData): bool
    {
        $order = VipOrder::findByOrderNo($orderNo);
        
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        
        if ($order->status !== VipOrder::STATUS_PENDING) {
            return true; // 已经处理过了
        }
        
        $payOrderNo = $callbackData['transaction_id'] ?? $callbackData['pay_order_no'] ?? '';
        
        return $order->markAsPaid($payOrderNo, $callbackData);
    }
    
    /**
     * 获取VIP价格配置（人民币，单位：元）
     */
    public static function getVipPrices(): array
    {
        return [
            'month' => ConfigService::get('vip_month_price', VipOrder::PRICE_MONTH),
            'quarter' => ConfigService::get('vip_quarter_price', VipOrder::PRICE_QUARTER),
            'year' => ConfigService::get('vip_year_price', VipOrder::PRICE_YEAR),
        ];
    }

    /**
     * 获取VIP积分兑换价格配置（积分）
     */
    public static function getVipPointsPrices(): array
    {
        return [
            'month'   => (int) ConfigService::get('vip_month_points',   500),
            'quarter' => (int) ConfigService::get('vip_quarter_points', 1200),
            'year'    => (int) ConfigService::get('vip_year_points',    3600),
        ];
    }

    /**
     * 积分兑换VIP（原子事务：扣积分 + 激活VIP）
     *
     * @param int    $userId  用户ID
     * @param string $vipType 套餐类型：month / quarter / year
     * @return array [success, message, expire_time, balance]
     * @throws \Exception
     */
    public static function purchaseWithPoints(int $userId, string $vipType): array
    {
        if (!in_array($vipType, ['month', 'quarter', 'year'], true)) {
            return ['success' => false, 'message' => '无效的套餐类型'];
        }

        $prices = self::getVipPointsPrices();
        $cost   = $prices[$vipType];

        Db::startTrans();
        try {
            // 1. 行锁检查积分
            $userData = Db::name('tc_user')
                ->where('id', $userId)
                ->where('status', 1)
                ->lock(true)
                ->find();

            if (!$userData) {
                Db::rollback();
                return ['success' => false, 'message' => '用户不存在'];
            }

            if ((int) $userData['points'] < $cost) {
                Db::rollback();
                return [
                    'success' => false,
                    'message' => "积分不足，需要 {$cost} 积分，当前余额 {$userData['points']} 积分",
                    'balance' => (int) $userData['points'],
                ];
            }

            // 2. 扣除积分
            Db::name('tc_user')->where('id', $userId)->dec('points', $cost)->update();
            $newBalance = (int) $userData['points'] - $cost;

            // 3. 写积分流水
            $pointsService = new PointsService();
            $vipTypeNames  = ['month' => '月度VIP', 'quarter' => '季度VIP', 'year' => '年度VIP'];
            $recordPayload = \app\model\PointsRecord::buildRecordPayload(
                $userId,
                '兑换' . ($vipTypeNames[$vipType] ?? 'VIP'),
                -$cost,
                'vip_exchange',
                0,
                "积分兑换{$vipTypeNames[$vipType]}",
                ['balance' => $newBalance]
            );
            Db::name('tc_points_record')->insert($recordPayload);

            // 4. 激活 VIP（续费或新开）
            $duration = VipOrder::getVipDuration($vipType);
            $vip      = UserVip::activateVip($userId, $vipType, $duration);

            Db::commit();

            return [
                'success'     => true,
                'message'     => 'VIP 开通成功',
                'expire_time' => $vip->end_time,
                'balance'     => $newBalance,
            ];

        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
    
    /**
     * 获取VIP权益列表
     */
    public static function getVipBenefits(): array
    {
        $dailyPointsMultiplier = ConfigService::get('vip_daily_points_multiplier', 2);
        
        return [
            [
                'icon' => 'Magic',
                'title' => '无限次排盘',
                'desc' => '不受次数限制，随时想看就看',
            ],
            [
                'icon' => 'DataLine',
                'title' => '解锁专业报告',
                'desc' => '详细命盘解读、性格分析',
            ],
            [
                'icon' => 'Coin',
                'title' => '积分加倍',
                'desc' => "签到积分x{$dailyPointsMultiplier}倍",
            ],
            [
                'icon' => 'Heart',
                'title' => '八字合婚',
                'desc' => '免费使用合婚功能',
            ],
            [
                'icon' => 'Service',
                'title' => '优先客服',
                'desc' => '专属客服通道',
            ],
            [
                'icon' => 'Gift',
                'title' => '新功能抢先',
                'desc' => '优先体验新功能',
            ],
        ];
    }
    
    /**
     * 检查并更新过期VIP
     */
    public static function checkExpiredVips(): int
    {
        return UserVip::checkAndUpdateExpired();
    }
    
    /**
     * 获取用户订单列表
     */
    public static function getUserOrders(int $userId, int $page = 1, int $limit = 10): array
    {
        $orders = VipOrder::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->page($page, $limit)
            ->select();
        
        $total = VipOrder::where('user_id', $userId)->count();
        
        $list = [];
        foreach ($orders as $order) {
            $list[] = [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'vip_type' => $order->vip_type,
                'vip_type_name' => $order->getVipTypeName(),
                'amount' => $order->amount,
                'status' => $order->status,
                'pay_time' => $order->pay_time,
                'created_at' => $order->created_at,
            ];
        }
        
        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }
}
