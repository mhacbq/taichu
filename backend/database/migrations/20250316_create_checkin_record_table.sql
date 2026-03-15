-- 每日签到记录表
CREATE TABLE IF NOT EXISTS `checkin_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '签到日期',
    `consecutive_days` INT DEFAULT 1 COMMENT '连续签到天数',
    `points` INT DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_user_date` (`user_id`, `date`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日签到记录表';
