-- ============================================================
-- 太初命理网站 - 数据库全表字段修复脚本
-- 逐个添加可能缺失的字段
-- 如果报错"Duplicate column name"，说明字段已存在，忽略即可
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. 修复 tc_points_record 表（积分记录表）
-- ============================================================
ALTER TABLE `tc_points_record` ADD COLUMN `action` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '动作描述' AFTER `user_id`;
ALTER TABLE `tc_points_record` ADD COLUMN `points` INT(11) NOT NULL DEFAULT '0' COMMENT '变动积分（正数增加，负数减少）' AFTER `action`;
ALTER TABLE `tc_points_record` ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '类型' AFTER `points`;
ALTER TABLE `tc_points_record` ADD COLUMN `related_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关联ID' AFTER `type`;
ALTER TABLE `tc_points_record` ADD COLUMN `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注' AFTER `related_id`;

-- ============================================================
-- 2. 修复 tc_user 表（用户表）
-- ============================================================
ALTER TABLE `tc_user` ADD COLUMN `points` INT(11) NOT NULL DEFAULT '0' COMMENT '积分余额' AFTER `phone`;
ALTER TABLE `tc_user` ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '0禁用 1正常' AFTER `points`;
ALTER TABLE `tc_user` ADD COLUMN `invite_code` VARCHAR(20) DEFAULT '' COMMENT '邀请码' AFTER `status`;
ALTER TABLE `tc_user` ADD COLUMN `invited_by` INT(11) UNSIGNED DEFAULT 0 COMMENT '被谁邀请' AFTER `invite_code`;
ALTER TABLE `tc_user` ADD COLUMN `is_vip` TINYINT(1) DEFAULT 0 COMMENT '是否VIP: 0否 1是' AFTER `invited_by`;
ALTER TABLE `tc_user` ADD COLUMN `vip_expire_at` DATETIME DEFAULT NULL COMMENT 'VIP过期时间' AFTER `is_vip`;
ALTER TABLE `tc_user` ADD COLUMN `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间' AFTER `vip_expire_at`;
ALTER TABLE `tc_user` ADD COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间' AFTER `last_login_at`;
ALTER TABLE `tc_user` ADD COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间' AFTER `created_at`;

-- ============================================================
-- 3. 修复 tc_recharge_order 表（充值订单表）
-- ============================================================
ALTER TABLE `tc_recharge_order` ADD COLUMN `order_no` VARCHAR(50) NOT NULL COMMENT '订单号' AFTER `id`;
ALTER TABLE `tc_recharge_order` ADD COLUMN `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID' AFTER `order_no`;
ALTER TABLE `tc_recharge_order` ADD COLUMN `amount` DECIMAL(10,2) NOT NULL COMMENT '充值金额' AFTER `user_id`;
ALTER TABLE `tc_recharge_order` ADD COLUMN `points` INT(11) NOT NULL COMMENT '获得积分' AFTER `amount`;
ALTER TABLE `tc_recharge_order` ADD COLUMN `status` VARCHAR(20) DEFAULT 'pending' COMMENT '状态' AFTER `points`;
ALTER TABLE `tc_recharge_order` ADD COLUMN `payment_type` VARCHAR(30) DEFAULT 'wechat_jsapi' COMMENT '支付方式' AFTER `status`;
ALTER TABLE `tc_recharge_order` ADD COLUMN `pay_order_no` VARCHAR(100) DEFAULT '' COMMENT '支付平台订单号' AFTER `payment_type`;
ALTER TABLE `tc_recharge_order` ADD COLUMN `pay_time` DATETIME DEFAULT NULL COMMENT '支付时间' AFTER `pay_order_no`;
ALTER TABLE `tc_recharge_order` ADD COLUMN `expire_time` DATETIME DEFAULT NULL COMMENT '过期时间' AFTER `pay_time`;
ALTER TABLE `tc_recharge_order` ADD COLUMN `client_ip` VARCHAR(45) DEFAULT '' COMMENT '客户端IP' AFTER `expire_time`;
ALTER TABLE `tc_recharge_order` ADD COLUMN `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User-Agent' AFTER `client_ip`;
ALTER TABLE `tc_recharge_order` ADD COLUMN `callback_data` JSON DEFAULT NULL COMMENT '回调数据' AFTER `user_agent`;
ALTER TABLE `tc_recharge_order` ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间' AFTER `callback_data`;

-- ============================================================
-- 4. 修复 tc_tarot_record 表（塔罗记录表）
-- ============================================================
ALTER TABLE `tc_tarot_record` ADD COLUMN `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID' AFTER `id`;
ALTER TABLE `tc_tarot_record` ADD COLUMN `spread_type` VARCHAR(50) NOT NULL COMMENT '牌阵类型' AFTER `user_id`;
ALTER TABLE `tc_tarot_record` ADD COLUMN `question` VARCHAR(500) DEFAULT '' COMMENT '占卜问题' AFTER `spread_type`;
ALTER TABLE `tc_tarot_record` ADD COLUMN `cards` JSON DEFAULT NULL COMMENT '抽到的牌' AFTER `question`;
ALTER TABLE `tc_tarot_record` ADD COLUMN `interpretation` TEXT COMMENT '解读内容' AFTER `cards`;
ALTER TABLE `tc_tarot_record` ADD COLUMN `ai_analysis` TEXT COMMENT 'AI深度分析' AFTER `interpretation`;
ALTER TABLE `tc_tarot_record` ADD COLUMN `is_public` TINYINT(1) DEFAULT 0 COMMENT '是否公开' AFTER `ai_analysis`;
ALTER TABLE `tc_tarot_record` ADD COLUMN `share_code` VARCHAR(50) DEFAULT '' COMMENT '分享码' AFTER `is_public`;
ALTER TABLE `tc_tarot_record` ADD COLUMN `view_count` INT(11) DEFAULT 0 COMMENT '查看次数' AFTER `share_code`;
ALTER TABLE `tc_tarot_record` ADD COLUMN `client_ip` VARCHAR(45) DEFAULT '' COMMENT '客户端IP' AFTER `view_count`;
ALTER TABLE `tc_tarot_record` ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间' AFTER `client_ip`;

-- ============================================================
-- 5. 修复 tc_bazi_record 表（八字记录表）
-- ============================================================
ALTER TABLE `tc_bazi_record` ADD COLUMN `user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `birth_date` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '出生日期' AFTER `user_id`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `gender` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '性别' AFTER `birth_date`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `location` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '出生地' AFTER `gender`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `year_gan` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '年干' AFTER `location`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `year_zhi` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '年支' AFTER `year_gan`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `month_gan` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '月干' AFTER `year_zhi`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `month_zhi` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '月支' AFTER `month_gan`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `day_gan` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '日干' AFTER `month_zhi`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `day_zhi` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '日支' AFTER `day_gan`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `hour_gan` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '时干' AFTER `day_zhi`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `hour_zhi` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '时支' AFTER `hour_gan`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `analysis` TEXT COMMENT '分析结果' AFTER `hour_zhi`;
ALTER TABLE `tc_bazi_record` ADD COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间' AFTER `analysis`;

SET FOREIGN_KEY_CHECKS = 1;

-- 完成
SELECT '数据库全表字段修复完成' AS message;
