-- 积分记录表（模型使用tc_points_record）
CREATE TABLE IF NOT EXISTS `tc_points_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `action` VARCHAR(100) NOT NULL COMMENT '动作说明',
    `points` INT NOT NULL COMMENT '变动积分（正数为增加，负数为减少）',
    `type` VARCHAR(50) DEFAULT '' COMMENT '类型: signin/share/exchange/consume/etc',
    `related_id` INT UNSIGNED DEFAULT 0 COMMENT '关联ID',
    `remark` VARCHAR(500) DEFAULT '' COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_related_id` (`related_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分记录表';
