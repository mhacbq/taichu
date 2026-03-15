-- 用户反馈表
CREATE TABLE IF NOT EXISTS `feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL DEFAULT 'suggestion' COMMENT '类型: bug/feature/suggestion/other',
    `title` VARCHAR(200) NOT NULL COMMENT '标题',
    `content` TEXT NOT NULL COMMENT '内容',
    `images` JSON NULL COMMENT '图片列表',
    `contact` VARCHAR(100) DEFAULT '' COMMENT '联系方式',
    `status` TINYINT DEFAULT 0 COMMENT '状态: 0待处理 1处理中 2已回复 3已关闭',
    `reply` TEXT COMMENT '回复内容',
    `replied_by` INT UNSIGNED DEFAULT 0 COMMENT '回复人ID',
    `replied_at` DATETIME NULL COMMENT '回复时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户反馈表';
