-- 任务记录表
CREATE TABLE IF NOT EXISTS `tc_task_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `task_type` VARCHAR(50) NOT NULL COMMENT '任务类型: daily/weekly/once/limit',
    `task_code` VARCHAR(50) NOT NULL COMMENT '任务编码: first_share/complete_profile/etc',
    `task_name` VARCHAR(100) NOT NULL COMMENT '任务名称',
    `points` INT DEFAULT 0 COMMENT '奖励积分',
    `limit_type` VARCHAR(20) DEFAULT '' COMMENT '限制类型: daily/total/none',
    `limit_count` INT DEFAULT 1 COMMENT '限制次数',
    `completed_count` INT DEFAULT 1 COMMENT '已完成次数',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_task_code` (`task_code`),
    INDEX `idx_task_type` (`task_type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务记录表';

-- 签到日志表（Task控制器使用）
CREATE TABLE IF NOT EXISTS `tc_checkin_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '签到日期',
    `consecutive_days` INT DEFAULT 1 COMMENT '连续签到天数',
    `base_points` INT DEFAULT 0 COMMENT '基础积分',
    `bonus_points` INT DEFAULT 0 COMMENT '奖励积分',
    `total_points` INT DEFAULT 0 COMMENT '总积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_user_date` (`user_id`, `date`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到日志表';
