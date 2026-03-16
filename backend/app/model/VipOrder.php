<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * VIP订单模型
 */
class VipOrder extends Model
{
    protected $name = 'tc_vip_order';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    // 订单状态常量
    const STATUS_PENDING = 'pending';      // 待支付
    const STATUS_PAID = 'paid';            // 已支付
    const STATUS_CANCELLED = 'cancelled';  // 已取消
    const STATUS_REFUNDED = 'refunded';    // 已退款
    const STATUS_PROCESSING = 'processing'; // 处理中（防止并发）
    
    // VIP类型常量
    const TYPE_MONTH = 'month';       // 月卡
    const TYPE_QUARTER = 'quarter';   // 季卡
    const TYPE_YEAR = 'year';         // 年卡
    
    // VIP价格配置（默认价格）
    const PRICE_MONTH = 19.9;
    const PRICE_QUARTER = 49;
    const PRICE_YEAR = 168;
    
    // VIP时长（天）
    const DURATION_MONTH = 30;
    const DURATION_QUARTER = 90;
    const DURATION_YEAR = 365;
    
    protected $schema = [
        'id' => 'int',
        'order_no' => 'string',
        'user_id' => 'int',
        'vip_type' => 'string',
        'amount' => 'float',
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
        $microTime = microtime(true);
        $microSeconds = sprintf('%06d', ($microTime - floor($microTime)) * 1000000);
        $randomPart = sprintf('%04d', random_int(0, 9999));
        $processId = sprintf('%04d', getmypid() % 10000);
        
        return 'VIP' . date('YmdHis') . $microSeconds . $randomPart . $processId;
    }
    
    /**
     * 获取VIP类型价格
     */
    public static function getVipPrice(string $vipType): float
    {
        $prices = [
            self::TYPE_MONTH => self::PRICE_MONTH,
            self::TYPE_QUARTER => self::PRICE_QUARTER,
            self::TYPE_YEAR => self::PRICE_YEAR,
        ];
        
        return $prices[$vipType] ?? self::PRICE_MONTH;
    }
    
    /**
     * 获取VIP类型时长（天）
     */
    public static function getVipDuration(string $vipType): int
    {
        $durations = [
            self::TYPE_MONTH => self::DURATION_MONTH,
            self::TYPE_QUARTER => self::DURATION_QUARTER,
            self::TYPE_YEAR => self::DURATION_YEAR,
        ];
        
        return $durations[$vipType] ?? self::DURATION_MONTH;
    }
    
    /**
     * 创建订单
     */
    public static function createOrder(int $userId, string $vipType, string $clientIp = '', string $userAgent = ''): array
    {
        $amount = self::getVipPrice($vipType);
        
        $order = new self();
        $order->order_no = self::generateOrderNo();
        $order->user_id = $userId;
        $order->vip_type = $vipType;
        $order->amount = $amount;
        $order->status = self::STATUS_PENDING;
        $order->payment_type = 'wechat_jsapi';
        $order->expire_time = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $order->client_ip = $clientIp;
        $order->user_agent = $userAgent;
        
        if ($order->save()) {
            return [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'vip_type' => $order->vip_type,
                'amount' => $order->amount,
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
        
        Db::startTrans();
        try {
            // 更新订单状态
            $this->status = self::STATUS_PAID;
            $this->pay_order_no = $payOrderNo;
            $this->pay_time = date('Y-m-d H:i:s');
            $this->callback_data = $callbackData;
            $this->save();
            
            // 激活用户VIP
            $duration = self::getVipDuration($this->vip_type);
            UserVip::activateVip($this->user_id, $this->vip_type, $duration);
            
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
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
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    /**
     * 获取VIP类型名称
     */
    public function getVipTypeName(): string
    {
        $names = [
            self::TYPE_MONTH => '月度会员',
            self::TYPE_QUARTER => '季度会员',
            self::TYPE_YEAR => '年度会员',
        ];
        
        return $names[$this->vip_type] ?? '未知类型';
    }
}
