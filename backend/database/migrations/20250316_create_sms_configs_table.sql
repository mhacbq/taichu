-- 短信配置表
CREATE TABLE IF NOT EXISTS `sms_configs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `provider` VARCHAR(50) NOT NULL DEFAULT 'tencent' COMMENT '服务商: tencent/aliyun',
    `name` VARCHAR(100) DEFAULT '' COMMENT '配置名称',
    `secret_id` VARCHAR(255) DEFAULT '' COMMENT 'Secret ID',
    `secret_key` VARCHAR(255) DEFAULT '' COMMENT 'Secret Key',
    `sdk_app_id` VARCHAR(50) DEFAULT '' COMMENT 'SDK App ID',
    `sign_name` VARCHAR(100) DEFAULT '' COMMENT '短信签名',
    `template_code` VARCHAR(50) DEFAULT '' COMMENT '通用验证码模板ID',
    `template_register` VARCHAR(50) DEFAULT '' COMMENT '注册模板ID',
    `template_login` VARCHAR(50) DEFAULT '' COMMENT '登录模板ID',
    `template_reset` VARCHAR(50) DEFAULT '' COMMENT '重置密码模板ID',
    `template_bind` VARCHAR(50) DEFAULT '' COMMENT '绑定手机模板ID',
    `is_enabled` TINYINT DEFAULT 0 COMMENT '是否启用: 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_provider` (`provider`),
    INDEX `idx_is_enabled` (`is_enabled`),
    UNIQUE KEY `uk_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信配置表';

-- 插入默认腾讯云短信配置占位
INSERT INTO `sms_configs` (`provider`, `name`, `is_enabled`) VALUES
('tencent', '腾讯云短信', 0);
