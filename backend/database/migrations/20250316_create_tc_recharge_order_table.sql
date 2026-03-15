-- 充值订单表（模型使用tc_recharge_order）
CREATE TABLE IF NOT EXISTS `tc_recharge_order` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_no` VARCHAR(50) NOT NULL UNIQUE COMMENT '订单号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `amount` DECIMAL(10,2) NOT NULL COMMENT '充值金额',
    `points` INT NOT NULL COMMENT '获得积分',
    `status` VARCHAR(20) DEFAULT 'pending' COMMENT '状态: pending/paid/cancelled/refunded/processing',
    `payment_type` VARCHAR(30) DEFAULT 'wechat_jsapi' COMMENT '支付方式',
    `pay_order_no` VARCHAR(100) DEFAULT '' COMMENT '支付平台订单号',
    `pay_time` DATETIME NULL COMMENT '支付时间',
    `expire_time` DATETIME NULL COMMENT '过期时间',
    `client_ip` VARCHAR(45) DEFAULT '' COMMENT '客户端IP',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User-Agent',
    `callback_data` JSON NULL COMMENT '回调数据',
    `notify_id` VARCHAR(100) DEFAULT '' COMMENT '通知ID（幂等性验证）',
    `notify_time` DATETIME NULL COMMENT '通知处理时间',
    `process_log` JSON NULL COMMENT '处理日志',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_order_no` (`order_no`),
    INDEX `idx_status` (`status`),
    INDEX `idx_pay_order_no` (`pay_order_no`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值订单表';
