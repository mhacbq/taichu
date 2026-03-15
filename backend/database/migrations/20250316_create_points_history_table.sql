-- 积分历史记录表
CREATE TABLE IF NOT EXISTS `points_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL COMMENT '类型: add/reduce/freeze/unfreeze',
    `points` INT NOT NULL COMMENT '变动积分',
    `balance` INT NOT NULL COMMENT '变动后余额',
    `action` VARCHAR(100) NOT NULL COMMENT '动作说明',
    `action_type` VARCHAR(50) DEFAULT '' COMMENT '动作类型: signin/share/recharge/consume/etc',
    `source_id` INT UNSIGNED DEFAULT 0 COMMENT '来源ID',
    `source_type` VARCHAR(50) DEFAULT '' COMMENT '来源类型',
    `remark` VARCHAR(500) DEFAULT '' COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_action_type` (`action_type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分历史记录表';

-- 积分兑换记录表
CREATE TABLE IF NOT EXISTS `points_exchange` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `product_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
    `product_name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `product_type` VARCHAR(50) NOT NULL COMMENT '商品类型: vip/feature/content',
    `points` INT NOT NULL COMMENT '消耗积分',
    `status` TINYINT DEFAULT 0 COMMENT '状态: 0待处理 1已完成 2已取消 3已退款',
    `expire_at` DATETIME NULL COMMENT '过期时间',
    `completed_at` DATETIME NULL COMMENT '完成时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分兑换记录表';
