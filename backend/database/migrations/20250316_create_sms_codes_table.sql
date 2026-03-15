-- 短信验证码表
CREATE TABLE IF NOT EXISTS `sms_codes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
    `code` VARCHAR(10) NOT NULL COMMENT '验证码',
    `type` VARCHAR(20) NOT NULL COMMENT '类型: register/login/reset/bind/unbind',
    `expire_time` DATETIME NOT NULL COMMENT '过期时间',
    `is_used` TINYINT DEFAULT 0 COMMENT '是否已使用: 0否 1是',
    `ip` VARCHAR(45) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User-Agent',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_phone` (`phone`),
    INDEX `idx_code` (`code`),
    INDEX `idx_type` (`type`),
    INDEX `idx_is_used` (`is_used`),
    INDEX `idx_expire_time` (`expire_time`),
    INDEX `idx_phone_type` (`phone`, `type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信验证码表';
