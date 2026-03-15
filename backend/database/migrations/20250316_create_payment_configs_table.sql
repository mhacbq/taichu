-- 支付配置表
CREATE TABLE IF NOT EXISTS `payment_configs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL DEFAULT 'wechat_jsapi' COMMENT '支付类型: wechat_jsapi/wechat_native/alipay',
    `name` VARCHAR(100) DEFAULT '' COMMENT '配置名称',
    `mch_id` VARCHAR(50) DEFAULT '' COMMENT '商户号',
    `app_id` VARCHAR(50) DEFAULT '' COMMENT '应用ID',
    `api_key` VARCHAR(255) DEFAULT '' COMMENT 'API密钥',
    `api_cert` TEXT COMMENT 'API证书内容',
    `api_key_pem` TEXT COMMENT 'API密钥证书内容',
    `notify_url` VARCHAR(500) DEFAULT '' COMMENT '支付回调URL',
    `return_url` VARCHAR(500) DEFAULT '' COMMENT '支付完成返回URL',
    `is_enabled` TINYINT DEFAULT 0 COMMENT '是否启用: 0禁用 1启用',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_type` (`type`),
    INDEX `idx_is_enabled` (`is_enabled`),
    UNIQUE KEY `uk_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付配置表';

-- 插入默认微信支付配置占位
INSERT INTO `payment_configs` (`type`, `name`, `notify_url`, `is_enabled`) VALUES
('wechat_jsapi', '微信支付JSAPI', '/api/payment/notify/wechat', 0);
