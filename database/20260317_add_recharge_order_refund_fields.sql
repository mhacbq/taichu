-- 充值订单退款审计字段补充
-- 用途：为后台真实微信退款链路补齐退款流水号、退款金额、微信退款单号和响应审计信息

ALTER TABLE `tc_recharge_order`
    ADD COLUMN `refund_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '商户退款单号' AFTER `pay_order_no`,
    ADD COLUMN `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额' AFTER `status`,
    ADD COLUMN `refund_time` DATETIME NULL COMMENT '退款时间' AFTER `pay_time`,
    ADD COLUMN `refund_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '退款原因' AFTER `refund_time`,
    ADD COLUMN `wechat_refund_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '微信退款单号' AFTER `refund_reason`,
    ADD COLUMN `refund_response` JSON NULL COMMENT '退款响应原文' AFTER `callback_data`,
    ADD INDEX `idx_refund_time` (`refund_time`),
    ADD INDEX `idx_refund_no` (`refund_no`),
    ADD INDEX `idx_wechat_refund_id` (`wechat_refund_id`);
