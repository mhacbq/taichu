<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 充值订单模型
 */
class RechargeOrder extends Model
{
    protected $name = 'tc_recharge_order';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    // 订单状态常量
    const STATUS_PENDING = 'pending';      // 待支付
    const STATUS_PAID = 'paid';            // 已支付
    const STATUS_CANCELLED = 'cancelled';  // 已取消
    const STATUS_REFUNDED = 'refunded';    // 已退款
    const STATUS_PROCESSING = 'processing'; // 处理中（防止并发）
    
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
        'notify_id' => 'string',          // 微信支付通知ID，用于幂等性验证
        'notify_time' => 'datetime',       // 通知处理时间
        'process_log' => 'json',           // 处理日志
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    protected $json = ['callback_data', 'process_log'];
    
    /**
     * 生成唯一订单号
     * 格式：年月日时分秒(14) + 微秒(6) + 随机数(4) + 进程ID(4)
     */
    public static function generateOrderNo(): string
    {
        // 使用更高精度的随机数和进程ID来避免并发冲突
        $microTime = microtime(true);
        $microSeconds = sprintf('%06d', ($microTime - floor($microTime)) * 1000000);
        $randomPart = sprintf('%04d', random_int(0, 9999));
        $processId = sprintf('%04d', getmypid() % 10000);
        
        return date('YmdHis') . $microSeconds . $randomPart . $processId;
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
     * 处理支付成功（带事务和幂等性保护）
     * 
     * @param string $payOrderNo 支付平台订单号
     * @param array $callbackData 回调数据
     * @param string $notifyId 通知ID（用于幂等性验证）
     * @return array [success, message, is_duplicate]
     */
    public function markAsPaid(string $payOrderNo, array $callbackData = [], string $notifyId = ''): array
    {
        // 1. 幂等性检查：如果已经处理过相同的notify_id，直接返回成功
        if (!empty($notifyId) && !empty($this->notify_id) && $this->notify_id === $notifyId) {
            return ['success' => true, 'message' => '已处理过的通知', 'is_duplicate' => true];
        }
        
        // 2. 状态检查：只有待支付订单可以处理
        if ($this->status === self::STATUS_PAID) {
            return ['success' => true, 'message' => '订单已支付', 'is_duplicate' => false];
        }
        
        if ($this->status === self::STATUS_PROCESSING) {
            return ['success' => false, 'message' => '订单正在处理中', 'is_duplicate' => false];
        }
        
        if ($this->status !== self::STATUS_PENDING) {
            return ['success' => false, 'message' => '订单状态不正确：' . $this->status, 'is_duplicate' => false];
        }
        
        // 3. 使用数据库事务确保原子性
        Db::startTrans();
        try {
            // 3.1 先将订单标记为处理中（防止并发）
            $lockResult = Db::name('tc_recharge_order')
                ->where('id', $this->id)
                ->where('status', self::STATUS_PENDING)
                ->update(['status' => self::STATUS_PROCESSING, 'updated_at' => date('Y-m-d H:i:s')]);
            
            if ($lockResult === 0) {
                Db::rollback();
                return ['success' => false, 'message' => '订单状态已变更，可能正在处理', 'is_duplicate' => false];
            }
            
            // 3.2 获取用户数据并加行锁
            $userData = Db::name('tc_user')
                ->where('id', $this->user_id)
                ->where('status', 1)
                ->lock(true)
                ->find();
            
            if (!$userData) {
                Db::rollback();
                // 记录错误日志
                $this->logProcess('error', '用户不存在或已禁用', ['user_id' => $this->user_id]);
                return ['success' => false, 'message' => '用户不存在', 'is_duplicate' => false];
            }
            
            // 3.3 给用户增加积分
            $updateResult = Db::name('tc_user')
                ->where('id', $this->user_id)
                ->inc('points', $this->points)
                ->update();
            
            if ($updateResult === 0) {
                Db::rollback();
                $this->logProcess('error', '积分增加失败', ['user_id' => $this->user_id, 'points' => $this->points]);
                return ['success' => false, 'message' => '积分增加失败', 'is_duplicate' => false];
            }
            
            // 3.4 记录积分变动
            $recordResult = Db::name('tc_points_record')->insert([
                'user_id' => $this->user_id,
                'action' => '充值获取',
                'points' => $this->points,
                'type' => 'recharge',
                'related_id' => $this->id,
                'remark' => "充值金额：{$this->amount}元",
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            if (!$recordResult) {
                Db::rollback();
                $this->logProcess('error', '积分记录失败');
                return ['success' => false, 'message' => '积分记录失败', 'is_duplicate' => false];
            }
            
            // 3.5 更新订单状态为已支付
            $orderUpdate = [
                'status' => self::STATUS_PAID,
                'pay_order_no' => $payOrderNo,
                'pay_time' => date('Y-m-d H:i:s'),
                'callback_data' => json_encode($callbackData),
                'notify_id' => $notifyId,
                'notify_time' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            Db::name('tc_recharge_order')
                ->where('id', $this->id)
                ->update($orderUpdate);
            
            // 3.6 提交事务
            Db::commit();
            
            // 刷新模型数据
            $this->refresh();
            
            // 记录成功日志
            $this->logProcess('success', '支付处理成功', [
                'pay_order_no' => $payOrderNo,
                'points_added' => $this->points,
                'new_balance' => $userData['points'] + $this->points,
            ]);
            
            return ['success' => true, 'message' => '支付处理成功', 'is_duplicate' => false];
            
        } catch (\Exception $e) {
            Db::rollback();
            
            // 记录错误日志
            $this->logProcess('error', '处理异常：' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * 记录处理日志
     */
    protected function logProcess(string $type, string $message, array $data = []): void
    {
        $log = $this->process_log ?? [];
        $log[] = [
            'time' => date('Y-m-d H:i:s'),
            'type' => $type,
            'message' => $message,
            'data' => $data,
        ];
        
        // 只保留最近20条日志
        if (count($log) > 20) {
            $log = array_slice($log, -20);
        }
        
        Db::name('tc_recharge_order')
            ->where('id', $this->id)
            ->update(['process_log' => json_encode($log)]);
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
