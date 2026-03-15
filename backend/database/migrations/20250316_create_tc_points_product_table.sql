-- 积分商品表（模型使用tc_points_product）
CREATE TABLE IF NOT EXISTS `tc_points_product` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `description` VARCHAR(500) DEFAULT '' COMMENT '商品简介',
    `detail` TEXT COMMENT '商品详情',
    `type` VARCHAR(50) NOT NULL COMMENT '类型: points/vip/service/physical/coupon',
    `points_price` INT NOT NULL COMMENT '积分价格',
    `original_price` DECIMAL(10,2) DEFAULT 0 COMMENT '原价（元）',
    `stock` INT DEFAULT 0 COMMENT '库存数量，-1为无限',
    `sold_count` INT DEFAULT 0 COMMENT '已售数量',
    `icon` VARCHAR(255) DEFAULT '' COMMENT '商品图标',
    `images` JSON NULL COMMENT '商品图片列表',
    `tags` JSON NULL COMMENT '标签列表',
    `purchase_limit` INT DEFAULT 0 COMMENT '购买限制，0为无限制',
    `validity_days` INT DEFAULT 0 COMMENT '有效期天数，0为永久',
    `usage_guide` TEXT COMMENT '使用说明',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `status` TINYINT DEFAULT 0 COMMENT '状态: 0已下架 1上架中 2已售罄',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分商品表';

-- 插入默认积分商品
INSERT INTO `tc_points_product` (`name`, `description`, `type`, `points_price`, `original_price`, `stock`, `icon`, `tags`, `validity_days`, `status`) VALUES
('100积分充值', '充值100积分到账户', 'points', 100, 10.00, -1, '💎', '["积分"]', 0, 1),
('月度VIP会员', '享受30天VIP特权', 'vip', 500, 19.90, -1, '👑', '["会员","热门"]', 30, 1),
('季度VIP会员', '享受90天VIP特权', 'vip', 1200, 49.00, -1, '👑', '["会员","优惠"]', 90, 1),
('年度VIP会员', '享受365天VIP特权', 'vip', 4000, 168.00, -1, '👑', '["会员","超值"]', 365, 1);
