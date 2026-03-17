-- 推送通知相关表结构
-- 创建时间：2026-03-17

USE taichu;

CREATE TABLE IF NOT EXISTS `tc_notification` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '通知类型',
    `title` VARCHAR(200) NOT NULL COMMENT '通知标题',
    `content` TEXT NULL COMMENT '通知内容',
    `data` JSON NULL COMMENT '附加数据',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已读',
    `read_at` DATETIME NULL DEFAULT NULL COMMENT '已读时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_created_at` (`user_id`, `created_at`),
    KEY `idx_user_is_read` (`user_id`, `is_read`),
    KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='站内通知表';

CREATE TABLE IF NOT EXISTS `tc_notification_setting` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `daily_fortune` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '每日运势通知开关',
    `system_notice` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '系统公告通知开关',
    `activity` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '活动通知开关',
    `recharge` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '充值通知开关',
    `points_change` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '积分变动通知开关',
    `push_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用设备推送',
    `sound_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用声音提醒',
    `vibration_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用震动提醒',
    `quiet_hours_start` CHAR(5) NOT NULL DEFAULT '22:00' COMMENT '免打扰开始时间',
    `quiet_hours_end` CHAR(5) NOT NULL DEFAULT '08:00' COMMENT '免打扰结束时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_notification_setting_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户通知设置表';

CREATE TABLE IF NOT EXISTS `tc_push_device` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `platform` VARCHAR(20) NOT NULL COMMENT '平台 ios/android/web',
    `device_token` VARCHAR(500) NOT NULL COMMENT '推送令牌',
    `device_id` VARCHAR(255) NOT NULL COMMENT '设备唯一标识',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否激活',
    `last_active_at` DATETIME NULL DEFAULT NULL COMMENT '最近活跃时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_push_device_device_id` (`device_id`),
    KEY `idx_user_platform` (`user_id`, `platform`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户推送设备表';

-- 为已有用户补齐默认通知设置，避免首次打开接口时为空
INSERT INTO `tc_notification_setting` (
    `user_id`,
    `daily_fortune`,
    `system_notice`,
    `activity`,
    `recharge`,
    `points_change`,
    `push_enabled`,
    `sound_enabled`,
    `vibration_enabled`,
    `quiet_hours_start`,
    `quiet_hours_end`
)
SELECT
    u.id,
    1,
    1,
    1,
    1,
    1,
    1,
    1,
    1,
    '22:00',
    '08:00'
FROM `tc_user` u
LEFT JOIN `tc_notification_setting` ns ON ns.user_id = u.id
WHERE ns.user_id IS NULL;
