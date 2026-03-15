-- 塔罗占卜记录表（模型使用tc_tarot_record）
CREATE TABLE IF NOT EXISTS `tc_tarot_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `spread_type` VARCHAR(50) NOT NULL COMMENT '牌阵类型: single/three/celtic/relationship',
    `question` VARCHAR(500) DEFAULT '' COMMENT '占卜问题',
    `cards` JSON NULL COMMENT '抽到的牌（JSON数组）',
    `interpretation` TEXT COMMENT '解读内容',
    `ai_analysis` TEXT COMMENT 'AI深度分析',
    `is_public` TINYINT DEFAULT 0 COMMENT '是否公开: 0私密 1公开',
    `share_code` VARCHAR(50) DEFAULT '' COMMENT '分享码',
    `view_count` INT DEFAULT 0 COMMENT '查看次数',
    `client_ip` VARCHAR(45) DEFAULT '' COMMENT '客户端IP',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_spread_type` (`spread_type`),
    INDEX `idx_is_public` (`is_public`),
    INDEX `idx_share_code` (`share_code`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗占卜记录表';
