SET NAMES utf8mb4;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_event` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '事件类型: login_anomaly/ip_frequency/device_risk',
    `level` VARCHAR(20) DEFAULT 'medium' COMMENT '风险等级: low/medium/high/critical',
    `detail` JSON NULL COMMENT '详细信息',
    `ip` VARCHAR(45) DEFAULT '' COMMENT 'IP地址',
    `device_id` VARCHAR(100) DEFAULT '' COMMENT '设备ID',
    `status` TINYINT DEFAULT 0 COMMENT '处理状态: 0待处理 1已确认 2已忽略',
    `handler_id` INT UNSIGNED DEFAULT 0 COMMENT '处理人ID',
    `handle_remark` VARCHAR(255) DEFAULT '' COMMENT '处理备注',
    `handle_at` DATETIME NULL COMMENT '处理时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反作弊风险事件表';

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_rule` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '规则名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '规则代码',
    `type` VARCHAR(50) NOT NULL COMMENT '规则类型',
    `config` JSON NULL COMMENT '规则配置',
    `action` VARCHAR(50) DEFAULT 'log' COMMENT '触发动作: log/block/captcha',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反作弊规则表';

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_device` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `device_id` VARCHAR(100) NOT NULL UNIQUE COMMENT '设备唯一标识',
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '关联最后用户ID',
    `platform` VARCHAR(20) DEFAULT '' COMMENT '平台: ios/android/web',
    `info` JSON NULL COMMENT '设备硬件信息',
    `is_blocked` TINYINT DEFAULT 0 COMMENT '是否黑名单: 0否 1是',
    `block_reason` VARCHAR(255) DEFAULT '' COMMENT '拉黑原因',
    `last_active_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_blocked` (`is_blocked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备指纹表';

INSERT INTO `tc_anti_cheat_rule` (`name`, `code`, `type`, `config`, `action`, `status`)
VALUES
    ('IP访问限频', 'ip_rate_default', 'ip_rate', JSON_OBJECT('threshold', 60, 'window_minutes', 1), 'block', 1),
    ('单设备注册限制', 'device_rate_default', 'device_rate', JSON_OBJECT('threshold', 3, 'window_minutes', 1), 'block', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `type` = VALUES(`type`),
    `config` = VALUES(`config`),
    `action` = VALUES(`action`),
    `status` = VALUES(`status`),
    `updated_at` = CURRENT_TIMESTAMP;

COMMIT;
