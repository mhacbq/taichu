-- 修复 tc_recharge_order 表缺少 expire_time 字段
ALTER TABLE `tc_recharge_order` ADD COLUMN `expire_time` DATETIME NULL COMMENT '订单过期时间' AFTER `paid_at`;

-- 创建 tc_user_vip 表（VIP会员表）
CREATE TABLE IF NOT EXISTS `tc_user_vip` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `vip_type` VARCHAR(20) NOT NULL DEFAULT 'month' COMMENT 'VIP类型：month/quarter/year',
    `start_time` DATETIME NOT NULL COMMENT '开始时间',
    `end_time` DATETIME NOT NULL COMMENT '到期时间',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1有效 2已过期',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_user` (`user_id`),
    INDEX `idx_status_end` (`status`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户VIP会员表';

-- 创建 tc_task_log 表（任务完成日志表）
CREATE TABLE IF NOT EXISTS `tc_task_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `task_id` VARCHAR(50) NOT NULL COMMENT '任务ID',
    `task_type` VARCHAR(20) NOT NULL DEFAULT 'daily' COMMENT '任务类型：daily/once/unlimited',
    `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_task` (`user_id`, `task_id`),
    INDEX `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务完成日志表';

-- 创建 tc_checkin_record 表（每日签到记录表）
CREATE TABLE IF NOT EXISTS `tc_checkin_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '签到日期',
    `consecutive_days` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '连续签到天数',
    `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_user_date` (`user_id`, `date`),
    INDEX `idx_user_date` (`user_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日签到记录表';

-- 创建 tc_checkin_log 表（Task.php 使用的签到日志表）
CREATE TABLE IF NOT EXISTS `tc_checkin_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `checkin_date` DATE NOT NULL COMMENT '签到日期',
    `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_user_date` (`user_id`, `checkin_date`),
    INDEX `idx_user_date` (`user_id`, `checkin_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务签到日志表';
