<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 积分商品模型
 */
class PointsProduct extends Model
{
    // 表名
    protected $name = 'tc_points_product';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 创建时间字段
    protected $createTime = 'created_at';
    
    // 更新时间字段
    protected $updateTime = 'updated_at';
    
    // 状态常量
    const STATUS_OFFLINE = 0;  // 已下架
    const STATUS_ONLINE = 1;   // 上架中
    const STATUS_SOLDOUT = 2;  // 已售罄
    
    // 商品类型常量
    const TYPE_POINTS = 'points';      // 积分充值
    const TYPE_VIP = 'vip';            // VIP会员
    const TYPE_SERVICE = 'service';    // 服务兑换
    const TYPE_PHYSICAL = 'physical';  // 实物商品
    const TYPE_COUPON = 'coupon';      // 优惠券
    
    /**
     * 获取上架中的商品列表
     */
    public static function getOnlineProducts(string $type = '', int $limit = 20): array
    {
        $query = self::where('status', self::STATUS_ONLINE)
            ->where('stock', '>', 0)
            ->order('sort_order', 'asc')
            ->order('created_at', 'desc')
            ->limit($limit);
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $products = $query->select();
        
        $result = [];
        foreach ($products as $product) {
            $result[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'description' => $product['description'],
                'type' => $product['type'],
                'points_price' => $product['points_price'],
                'original_price' => $product['original_price'],
                'stock' => $product['stock'],
                'icon' => $product['icon'],
                'tags' => json_decode($product['tags'] ?? '[]', true),
                'purchase_limit' => $product['purchase_limit'],
            ];
        }
        
        return $result;
    }
    
    /**
     * 获取商品详情
     */
    public static function getProductDetail(int $productId): ?array
    {
        $product = self::find($productId);
        
        if (!$product || $product['status'] !== self::STATUS_ONLINE) {
            return null;
        }
        
        return [
            'id' => $product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'detail' => $product['detail'],
            'type' => $product['type'],
            'points_price' => $product['points_price'],
            'original_price' => $product['original_price'],
            'stock' => $product['stock'],
            'sold_count' => $product['sold_count'],
            'icon' => $product['icon'],
            'images' => json_decode($product['images'] ?? '[]', true),
            'tags' => json_decode($product['tags'] ?? '[]', true),
            'purchase_limit' => $product['purchase_limit'],
            'validity_days' => $product['validity_days'],
            'usage_guide' => $product['usage_guide'],
        ];
    }
    
    /**
     * 减少库存（原子操作）
     */
    public static function decreaseStock(int $productId, int $quantity = 1): bool
    {
        // 使用数据库原生操作确保原子性
        $result = Db::name('tc_points_product')
            ->where('id', $productId)
            ->where('stock', '>=', $quantity)
            ->where('status', self::STATUS_ONLINE)
            ->dec('stock', $quantity)
            ->inc('sold_count', $quantity)
            ->update();
        
        // 如果影响行数为0，说明库存不足或商品已下架
        if ($result === 0) {
            return false;
        }
        
        // 检查是否售罄，如果是则更新状态
        $currentStock = Db::name('tc_points_product')
            ->where('id', $productId)
            ->value('stock');
        
        if ($currentStock !== null && $currentStock <= 0) {
            Db::name('tc_points_product')
                ->where('id', $productId)
                ->update(['status' => self::STATUS_SOLDOUT]);
        }
        
        return true;
    }
    
    /**
     * 检查购买限制
     */
    public static function checkPurchaseLimit(int $productId, int $userId): array
    {
        $product = self::find($productId);
        
        if (!$product) {
            return ['allowed' => false, 'message' => '商品不存在'];
        }
        
        if ($product['status'] !== self::STATUS_ONLINE) {
            return ['allowed' => false, 'message' => '商品已下架'];
        }
        
        if ($product['stock'] <= 0) {
            return ['allowed' => false, 'message' => '商品已售罄'];
        }
        
        // 检查购买次数限制
        if ($product['purchase_limit'] > 0) {
            $purchasedCount = PointsExchange::where('user_id', $userId)
                ->where('product_id', $productId)
                ->where('status', '!=', PointsExchange::STATUS_CANCELLED)
                ->count();
            
            if ($purchasedCount >= $product['purchase_limit']) {
                return ['allowed' => false, 'message' => '已达到购买次数限制'];
            }
        }
        
        return ['allowed' => true, 'message' => '可以购买'];
    }
    
    /**
     * 获取商品类型列表
     */
    public static function getProductTypes(): array
    {
        return [
            ['key' => self::TYPE_POINTS, 'name' => '积分充值', 'icon' => '💎'],
            ['key' => self::TYPE_VIP, 'name' => '会员特权', 'icon' => '👑'],
            ['key' => self::TYPE_SERVICE, 'name' => '服务兑换', 'icon' => '✨'],
            ['key' => self::TYPE_PHYSICAL, 'name' => '实物周边', 'icon' => '🎁'],
            ['key' => self::TYPE_COUPON, 'name' => '优惠券', 'icon' => '🎫'],
        ];
    }
}