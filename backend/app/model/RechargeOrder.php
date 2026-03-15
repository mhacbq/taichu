<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 充值订单模型
 */
class RechargeOrder extends Model
{
    protected $name = 'recharge_orders';
    
    protected $autoWriteTimestamp = true;
    
    // 订单状态常量
    const STATUS_PENDING = 'pending';      // 待支付
    const STATUS_PAID = 'paid';            // 已支付
    const STATUS_CANCELLED = 'cancelled';  // 已取消
    const STATUS_REFUNDED = 'refunded';    // 已退款
    
    protected $schema = [
        'id' => 'int',
        'order_no' => 'string',
        'user_id' => 'int',
        'amount' => 'float',
        'points' => 'int',
        'status' => 'string',
        'payment_type' => 'string',
        'pay_order_no' => 'string',
        'pay_time' => 'datetime',
        'expire_time' => 'datetime',
        'client_ip' => 'string',
        'user_agent' => 'string',
        'callback_data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    protected $json = ['callback_data'];
    
    /**
     * 生成唯一订单号
     */
    public static function generateOrderNo(): string
    {
        return date('YmdHis') . substr(microtime(true) * 10000, -6) . mt_rand(10, 99);
    }
    
    /**
     * 创建订单
     */
    public static function createOrder(int $userId, float $amount, int $points, string $clientIp = '', string $userAgent = ''): array
    {
        $order = new self();
        $order->order_no = self::generateOrderNo();
        $order->user_id = $userId;
        $order->amount = $amount;
        $order->points = $points;
        $order->status = self::STATUS_PENDING;
        $order->payment_type = 'wechat_jsapi';
        $order->expire_time = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $order->client_ip = $clientIp;
        $order->user_agent = $userAgent;
        
        if ($order->save()) {
            return [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'amount' => $order->amount,
                'points' => $order->points,
                'expire_time' => $order->expire_time,
                'status' => $order->status,
            ];
        }
        
        return [];
    }
    
    /**
     * 根据订单号查找订单
     */
    public static function findByOrderNo(string $orderNo): ?self
    {
        return self::where('order_no', $orderNo)->find();
    }
    
    /**
     * 处理支付成功
     */
    public function markAsPaid(string $payOrderNo, array $callbackData = []): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }
        
        $this->status = self::STATUS_PAID;
        $this->pay_order_no = $payOrderNo;
        $this->pay_time = date('Y-m-d H:i:s');
        $this->callback_data = $callbackData;
        
        if ($this->save()) {
            // 给用户增加积分
            $user = User::find($this->user_id);
            if ($user) {
                $user->addPoints($this->points);
                
                // 记录积分变动
                PointsRecord::record(
                    $this->user_id,
                    '充值获取',
                    $this->points,
                    'recharge',
                    $this->id,
                    "充值金额：{$this->amount}元"
                );
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 取消订单
     */
    public function cancel(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }
        
        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }
    
    /**
     * 检查订单是否过期
     */
    public function isExpired(): bool
    {
        return strtotime($this->expire_time) < time();
    }
    
    /**
     * 获取用户订单列表
     */
    public static function getUserOrders(int $userId, int $page = 1, int $limit = 10): array
    {
        return self::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
