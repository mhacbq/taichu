-- 充值订单退款审计字段补充
-- 用途：为后台真实微信退款链路补齐退款流水号、退款金额、微信退款单号和响应审计信息
-- 编写方式：使用 information_schema 判断字段是否已存在，避免重复执行时报错

SET NAMES utf8mb4;
USE taichu;

-- refund_no
SET @has_refund_no := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_recharge_order' AND COLUMN_NAME = 'refund_no');
SET @sql1 := IF(@has_refund_no = 0, "ALTER TABLE `tc_recharge_order` ADD COLUMN `refund_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '商户退款单号' AFTER `pay_order_no`", 'SELECT 1');
PREPARE s1 FROM @sql1; EXECUTE s1; DEALLOCATE PREPARE s1;

-- refund_amount
SET @has_refund_amount := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_recharge_order' AND COLUMN_NAME = 'refund_amount');
SET @sql2 := IF(@has_refund_amount = 0, "ALTER TABLE `tc_recharge_order` ADD COLUMN `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额' AFTER `status`", 'SELECT 1');
PREPARE s2 FROM @sql2; EXECUTE s2; DEALLOCATE PREPARE s2;

-- refund_time
SET @has_refund_time := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_recharge_order' AND COLUMN_NAME = 'refund_time');
SET @sql3 := IF(@has_refund_time = 0, "ALTER TABLE `tc_recharge_order` ADD COLUMN `refund_time` DATETIME NULL COMMENT '退款时间' AFTER `pay_time`", 'SELECT 1');
PREPARE s3 FROM @sql3; EXECUTE s3; DEALLOCATE PREPARE s3;

-- refund_reason
SET @has_refund_reason := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_recharge_order' AND COLUMN_NAME = 'refund_reason');
SET @sql4 := IF(@has_refund_reason = 0, "ALTER TABLE `tc_recharge_order` ADD COLUMN `refund_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '退款原因' AFTER `refund_time`", 'SELECT 1');
PREPARE s4 FROM @sql4; EXECUTE s4; DEALLOCATE PREPARE s4;

-- wechat_refund_id
SET @has_wechat_refund_id := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_recharge_order' AND COLUMN_NAME = 'wechat_refund_id');
SET @sql5 := IF(@has_wechat_refund_id = 0, "ALTER TABLE `tc_recharge_order` ADD COLUMN `wechat_refund_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '微信退款单号' AFTER `refund_reason`", 'SELECT 1');
PREPARE s5 FROM @sql5; EXECUTE s5; DEALLOCATE PREPARE s5;

-- refund_response
SET @has_refund_response := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_recharge_order' AND COLUMN_NAME = 'refund_response');
SET @sql6 := IF(@has_refund_response = 0, "ALTER TABLE `tc_recharge_order` ADD COLUMN `refund_response` JSON NULL COMMENT '退款响应原文' AFTER `callback_data`", 'SELECT 1');
PREPARE s6 FROM @sql6; EXECUTE s6; DEALLOCATE PREPARE s6;

-- 索引（重复创建会被 MySQL 报 Duplicate key name，用 information_schema 判断后执行）
SET @has_idx_refund_time := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_recharge_order' AND INDEX_NAME = 'idx_refund_time');
SET @sql7 := IF(@has_idx_refund_time = 0, 'ALTER TABLE `tc_recharge_order` ADD INDEX `idx_refund_time` (`refund_time`)', 'SELECT 1');
PREPARE s7 FROM @sql7; EXECUTE s7; DEALLOCATE PREPARE s7;

SET @has_idx_refund_no := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_recharge_order' AND INDEX_NAME = 'idx_refund_no');
SET @sql8 := IF(@has_idx_refund_no = 0, 'ALTER TABLE `tc_recharge_order` ADD INDEX `idx_refund_no` (`refund_no`)', 'SELECT 1');
PREPARE s8 FROM @sql8; EXECUTE s8; DEALLOCATE PREPARE s8;

SET @has_idx_wechat := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_recharge_order' AND INDEX_NAME = 'idx_wechat_refund_id');
SET @sql9 := IF(@has_idx_wechat = 0, 'ALTER TABLE `tc_recharge_order` ADD INDEX `idx_wechat_refund_id` (`wechat_refund_id`)', 'SELECT 1');
PREPARE s9 FROM @sql9; EXECUTE s9; DEALLOCATE PREPARE s9;
