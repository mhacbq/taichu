-- 分享记录表
CREATE TABLE IF NOT EXISTS `tc_share_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '分享类型: poster/record/app/page',
    `platform` VARCHAR(50) NOT NULL COMMENT '分享平台: wechat/moments/qq/weibo/copy',
    `content_id` INT UNSIGNED DEFAULT 0 COMMENT '内容ID',
    `content_type` VARCHAR(50) DEFAULT '' COMMENT '内容类型: bazi/hehun/tarot/daily/etc',
    `share_code` VARCHAR(20) DEFAULT '' COMMENT '分享码',
    `points_reward` INT DEFAULT 0 COMMENT '奖励积分',
    `ip` VARCHAR(45) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User-Agent',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_platform` (`platform`),
    INDEX `idx_share_code` (`share_code`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分享记录表';
