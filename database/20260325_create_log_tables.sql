-- 管理员登录日志表
CREATE TABLE IF NOT EXISTS `tc_admin_login_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID',
    `username` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '登录用户名',
    `ip` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '登录IP',
    `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'User-Agent',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '登录状态 1成功 0失败',
    `fail_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '失败原因',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '登录时间',
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员登录日志表';

-- API请求日志表
CREATE TABLE IF NOT EXISTS `tc_api_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `path` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '请求路径',
    `method` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '请求方法',
    `status` INT NOT NULL DEFAULT 200 COMMENT 'HTTP状态码',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID（0为未登录）',
    `ip` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '请求IP',
    `request_body` TEXT NULL COMMENT '请求体（脱敏）',
    `response_code` INT NOT NULL DEFAULT 200 COMMENT '业务响应码',
    `duration_ms` INT NOT NULL DEFAULT 0 COMMENT '耗时（毫秒）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '请求时间',
    KEY `idx_path` (`path`(191)),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API请求日志表';
