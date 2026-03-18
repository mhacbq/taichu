-- 系统定时任务表
CREATE TABLE IF NOT EXISTS `tc_system_task` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '任务名称',
    `script_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '脚本ID',
    `command` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '执行命令',
    `cron` VARCHAR(100) NOT NULL COMMENT 'Cron表达式',
    `params` JSON NULL COMMENT '执行参数',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `last_run_time` DATETIME NULL COMMENT '上次运行时间',
    `next_run_time` DATETIME NULL COMMENT '下次运行时间',
    `remark` VARCHAR(255) DEFAULT '' COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统定时任务表';

-- 系统脚本表
CREATE TABLE IF NOT EXISTS `tc_system_script` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '脚本名称',
    `type` VARCHAR(20) DEFAULT 'php' COMMENT '脚本类型: php/shell/python',
    `path` VARCHAR(255) NOT NULL COMMENT '脚本路径',
    `description` VARCHAR(255) DEFAULT '' COMMENT '描述',
    `content` MEDIUMTEXT NULL COMMENT '脚本内容',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统脚本表';

-- 系统任务运行日志
CREATE TABLE IF NOT EXISTS `tc_system_task_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL COMMENT '任务ID',
    `task_name` VARCHAR(100) NOT NULL COMMENT '任务名称',
    `status` TINYINT DEFAULT 0 COMMENT '执行状态: 0运行中 1成功 2失败',
    `output` MEDIUMTEXT NULL COMMENT '执行输出',
    `error` TEXT NULL COMMENT '错误信息',
    `start_time` DATETIME NOT NULL COMMENT '开始时间',
    `end_time` DATETIME NULL COMMENT '结束时间',
    `duration` INT DEFAULT 0 COMMENT '执行时长(秒)',
    INDEX `idx_task_id` (`task_id`),
    INDEX `idx_start_time` (`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统任务运行日志';

-- 反作弊风险事件表
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='反作弊风险事件表';

-- 反作弊规则表
CREATE TABLE IF NOT EXISTS `tc_anti_cheat_rule` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '规则名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '规则代码',
    `type` VARCHAR(50) NOT NULL COMMENT '规则类型',
    `config` JSON NULL COMMENT '规则配置',
    `action` VARCHAR(50) DEFAULT 'log' COMMENT '触发动作: log/block/warn',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='反作弊规则表';

-- 设备指纹表
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备指纹表';
