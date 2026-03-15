<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 积分兑换记录模型
 */
class PointsExchange extends Model
{
    // 表名
    protected $name = 'tc_points_exchange';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 创建时间字段
    protected $createTime = 'created_at';
    
    // 更新时间字段
    protected $updateTime = 'updated_at';
    
    // 状态常量
    const STATUS_PENDING = 0;    // 待处理
    const STATUS_COMPLETED = 1;  // 已完成
    const STATUS_CANCELLED = 2;  // 已取消
    const STATUS_FAILED = 3;     // 兑换失败
    
    /**
     * 创建兑换记录
     */
    public static function createExchange(
        int $userId,
        int $productId,
        array $productInfo,
        int $pointsCost
    ): self {
        return self::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'product_name' => $productInfo['name'],
            'product_type' => $productInfo['type'],
            'points_cost' => $pointsCost,
            'quantity' => 1,
            'status' => self::STATUS_PENDING,
            'exchange_no' => self::generateExchangeNo(),
            'valid_until' => $productInfo['validity_days'] > 0 
                ? date('Y-m-d H:i:s', strtotime("+{$productInfo['validity_days']} days"))
                : null,
        ]);
    }
    
    /**
     * 生成兑换单号
     */
    protected static function generateExchangeNo(): string
    {
        $prefix = 'EX';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        return $prefix . $date . $random;
    }
    
    /**
     * 完成兑换
     */
    public function complete(string $remark = ''): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = date('Y-m-d H:i:s');
        if ($remark) {
            $this->remark = $remark;
        }
        return $this->save();
    }
    
    /**
     * 取消兑换
     */
    public function cancel(string $reason = ''): bool
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancel_reason = $reason;
        $this->cancelled_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * 获取用户的兑换记录
     */
    public static function getUserExchanges(int $userId, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $exchanges = self::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->limit($offset, $limit)
            ->select();
        
        $result = [];
        foreach ($exchanges as $exchange) {
            $result[] = [
                'id' => $exchange['id'],
                'exchange_no' => $exchange['exchange_no'],
                'product_name' => $exchange['product_name'],
                'product_type' => $exchange['product_type'],
                'points_cost' => $exchange['points_cost'],
                'quantity' => $exchange['quantity'],
                'status' => $exchange['status'],
                'status_text' => self::getStatusText($exchange['status']),
                'valid_until' => $exchange['valid_until'],
                'created_at' => $exchange['created_at'],
                'completed_at' => $exchange['completed_at'],
            ];
        }
        
        return $result;
    }
    
    /**
     * 获取状态文本
     */
    public static function getStatusText(int $status): string
    {
        $statusMap = [
            self::STATUS_PENDING => '待处理',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_FAILED => '兑换失败',
        ];
        
        return $statusMap[$status] ?? '未知状态';
    }
    
    /**
     * 处理商品兑换
     * 根据不同商品类型执行不同的处理逻辑
     */
    public function processExchange(): array
    {
        $productType = $this->product_type;
        $result = ['success' => false, 'message' => ''];
        
        try {
            switch ($productType) {
                case PointsProduct::TYPE_POINTS:
                    // 积分充值类商品 - 直接增加用户积分
                    $user = User::find($this->user_id);
                    if ($user) {
                        // 解析商品名称获取赠送积分数
                        preg_match('/(\d+)/', $this->product_name, $matches);
                        $bonusPoints = isset($matches[1]) ? (int)$matches[1] : 0;
                        
                        if ($bonusPoints > 0) {
                            $user->addPoints($bonusPoints);
                            PointsRecord::record(
                                $this->user_id,
                                '积分商城兑换',
                                $bonusPoints,
                                'exchange',
                                $this->id,
                                "兑换商品：{$this->product_name}"
                            );
                        }
                        $result = ['success' => true, 'message' => '积分已到账'];
                    }
                    break;
                    
                case PointsProduct::TYPE_VIP:
                    // VIP会员类商品 - 延长VIP有效期
                    $user = User::find($this->user_id);
                    if ($user) {
                        // 解析VIP时长
                        preg_match('/(\d+)/', $this->product_name, $matches);
                        $days = isset($matches[1]) ? (int)$matches[1] : 30;
                        
                        $currentExpiry = $user->vip_expire_at ?? date('Y-m-d');
                        $newExpiry = date('Y-m-d', strtotime($currentExpiry . " +{$days} days"));
                        
                        $user->is_vip = 1;
                        $user->vip_expire_at = $newExpiry;
                        $user->save();
                        
                        $result = ['success' => true, 'message' => "VIP已延长{$days}天"];
                    }
                    break;
                    
                case PointsProduct::TYPE_SERVICE:
                    // 服务类商品 - 生成服务兑换码
                    $redeemCode = $this->generateRedeemCode();
                    $this->redeem_code = $redeemCode;
                    $this->save();
                    $result = ['success' => true, 'message' => '兑换码已生成', 'code' => $redeemCode];
                    break;
                    
                case PointsProduct::TYPE_COUPON:
                    // 优惠券类商品 - 发放优惠券
                    $redeemCode = $this->generateRedeemCode();
                    $this->redeem_code = $redeemCode;
                    $this->save();
                    $result = ['success' => true, 'message' => '优惠券已发放', 'code' => $redeemCode];
                    break;
                    
                case PointsProduct::TYPE_PHYSICAL:
                    // 实物商品 - 需要填写地址，标记为待发货
                    $result = ['success' => true, 'message' => '请完善收货地址', 'need_address' => true];
                    break;
                    
                default:
                    $result = ['success' => true, 'message' => '兑换成功'];
            }
        } catch (\Exception $e) {
            $result = ['success' => false, 'message' => '处理失败：' . $e->getMessage()];
        }
        
        return $result;
    }
    
    /**
     * 生成兑换码
     */
    protected function generateRedeemCode(): string
    {
        $prefix = strtoupper(substr($this->product_type, 0, 2));
        $random = strtoupper(substr(md5(uniqid()), 0, 10));
        return $prefix . $random;
    }
}