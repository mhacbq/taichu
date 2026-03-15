-- 积分兑换记录表（模型使用tc_points_exchange）
CREATE TABLE IF NOT EXISTS `tc_points_exchange` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `exchange_no` VARCHAR(50) NOT NULL UNIQUE COMMENT '兑换单号',
    `product_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
    `product_name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `product_type` VARCHAR(50) NOT NULL COMMENT '商品类型: points/vip/service/physical/coupon',
    `points_cost` INT NOT NULL COMMENT '消耗积分',
    `quantity` INT DEFAULT 1 COMMENT '兑换数量',
    `redeem_code` VARCHAR(50) DEFAULT '' COMMENT '兑换码',
    `valid_until` DATETIME NULL COMMENT '有效期至',
    `completed_at` DATETIME NULL COMMENT '完成时间',
    `cancelled_at` DATETIME NULL COMMENT '取消时间',
    `cancel_reason` VARCHAR(255) DEFAULT '' COMMENT '取消原因',
    `remark` VARCHAR(500) DEFAULT '' COMMENT '备注',
    `status` TINYINT DEFAULT 0 COMMENT '状态: 0待处理 1已完成 2已取消 3失败',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_exchange_no` (`exchange_no`),
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分兑换记录表';
