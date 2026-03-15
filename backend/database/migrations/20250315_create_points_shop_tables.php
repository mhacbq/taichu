<?php

declare(strict_types=1);

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePointsShopTables extends Migrator
{
    /**
     * 创建积分商城相关表
     */
    public function change()
    {
        // 积分商品表
        $this->table('tc_points_product', ['engine' => 'InnoDB', 'charset' => 'utf8mb4'])
            ->addColumn('name', 'string', ['limit' => 100, 'comment' => '商品名称'])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true, 'comment' => '商品简介'])
            ->addColumn('detail', 'text', ['null' => true, 'comment' => '商品详情'])
            ->addColumn('type', 'string', ['limit' => 20, 'comment' => '商品类型:points/vip/service/physical/coupon'])
            ->addColumn('points_price', 'integer', ['signed' => false, 'default' => 0, 'comment' => '积分价格'])
            ->addColumn('original_price', 'integer', ['signed' => false, 'default' => 0, 'comment' => '原价（用于展示）'])
            ->addColumn('stock', 'integer', ['signed' => false, 'default' => 0, 'comment' => '库存数量'])
            ->addColumn('sold_count', 'integer', ['signed' => false, 'default' => 0, 'comment' => '已售数量'])
            ->addColumn('icon', 'string', ['limit' => 255, 'null' => true, 'comment' => '商品图标'])
            ->addColumn('images', 'text', ['null' => true, 'comment' => '商品图片JSON'])
            ->addColumn('tags', 'text', ['null' => true, 'comment' => '商品标签JSON'])
            ->addColumn('purchase_limit', 'integer', ['signed' => false, 'default' => 0, 'comment' => '每人限购数量，0表示不限'])
            ->addColumn('validity_days', 'integer', ['signed' => false, 'default' => 0, 'comment' => '有效期天数，0表示永久'])
            ->addColumn('usage_guide', 'text', ['null' => true, 'comment' => '使用说明'])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'default' => 0, 'comment' => '排序'])
            ->addColumn('status', 'tinyint', ['limit' => 1, 'default' => 1, 'comment' => '状态:0下架,1上架,2售罄'])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addIndex(['type', 'status'])
            ->addIndex(['status', 'sort_order'])
            ->create();
        
        // 积分兑换记录表
        $this->table('tc_points_exchange', ['engine' => 'InnoDB', 'charset' => 'utf8mb4'])
            ->addColumn('user_id', 'integer', ['signed' => false, 'comment' => '用户ID'])
            ->addColumn('product_id', 'integer', ['signed' => false, 'comment' => '商品ID'])
            ->addColumn('exchange_no', 'string', ['limit' => 32, 'comment' => '兑换单号'])
            ->addColumn('product_name', 'string', ['limit' => 100, 'comment' => '商品名称'])
            ->addColumn('product_type', 'string', ['limit' => 20, 'comment' => '商品类型'])
            ->addColumn('points_cost', 'integer', ['signed' => false, 'comment' => '消耗积分'])
            ->addColumn('quantity', 'integer', ['signed' => false, 'default' => 1, 'comment' => '兑换数量'])
            ->addColumn('status', 'tinyint', ['limit' => 1, 'default' => 0, 'comment' => '状态:0待处理,1已完成,2已取消,3失败'])
            ->addColumn('redeem_code', 'string', ['limit' => 50, 'null' => true, 'comment' => '兑换码'])
            ->addColumn('valid_until', 'datetime', ['null' => true, 'comment' => '有效期至'])
            ->addColumn('extra_info', 'text', ['null' => true, 'comment' => '额外信息JSON'])
            ->addColumn('remark', 'string', ['limit' => 255, 'null' => true, 'comment' => '备注'])
            ->addColumn('cancel_reason', 'string', ['limit' => 255, 'null' => true, 'comment' => '取消原因'])
            ->addColumn('completed_at', 'datetime', ['null' => true, 'comment' => '完成时间'])
            ->addColumn('cancelled_at', 'datetime', ['null' => true, 'comment' => '取消时间'])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addIndex(['user_id', 'created_at'])
            ->addIndex(['exchange_no'])
            ->addIndex(['status'])
            ->addIndex(['product_id'])
            ->create();
        
        // 插入默认商品数据
        $this->insertDefaultProducts();
    }
    
    /**
     * 插入默认商品数据
     */
    protected function insertDefaultProducts()
    {
        $products = [
            [
                'name' => '100积分充值包',
                'description' => '直接到账100积分，可用于各种测算服务',
                'type' => 'points',
                'points_price' => 0, // 免费兑换，用于测试
                'original_price' => 10,
                'stock' => 9999,
                'icon' => '💎',
                'tags' => json_encode(['热销', '限时']),
                'purchase_limit' => 1,
                'validity_days' => 0,
                'sort_order' => 1,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'VIP会员7天',
                'description' => '享受VIP特权7天，免费使用付费功能',
                'type' => 'vip',
                'points_price' => 500,
                'original_price' => 50,
                'stock' => 9999,
                'icon' => '👑',
                'tags' => json_encode(['超值', '推荐']),
                'purchase_limit' => 0,
                'validity_days' => 7,
                'sort_order' => 2,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'VIP会员30天',
                'description' => '享受VIP特权30天，免费使用付费功能',
                'type' => 'vip',
                'points_price' => 1500,
                'original_price' => 150,
                'stock' => 9999,
                'icon' => '👑',
                'tags' => json_encode(['超值', '热销']),
                'purchase_limit' => 0,
                'validity_days' => 30,
                'sort_order' => 3,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => '八字详批服务券',
                'description' => '解锁一次完整的八字详批服务',
                'type' => 'service',
                'points_price' => 300,
                'original_price' => 30,
                'stock' => 9999,
                'icon' => '📜',
                'tags' => json_encode(['实用']),
                'purchase_limit' => 10,
                'validity_days' => 90,
                'sort_order' => 4,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => '合婚分析服务券',
                'description' => '解锁一次完整的八字合婚分析服务',
                'type' => 'service',
                'points_price' => 500,
                'original_price' => 50,
                'stock' => 9999,
                'icon' => '💕',
                'tags' => json_encode(['人气']),
                'purchase_limit' => 5,
                'validity_days' => 90,
                'sort_order' => 5,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => '8折优惠券',
                'description' => '全场服务8折优惠，可与积分抵扣同时使用',
                'type' => 'coupon',
                'points_price' => 200,
                'original_price' => 20,
                'stock' => 9999,
                'icon' => '🎫',
                'tags' => json_encode(['实用']),
                'purchase_limit' => 5,
                'validity_days' => 30,
                'sort_order' => 6,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
        
        $this->table('tc_points_product')->insert($products)->save();
    }
    
    /**
     * 回滚操作
     */
    public function down()
    {
        $this->table('tc_points_product')->drop();
        $this->table('tc_points_exchange')->drop();
    }
}