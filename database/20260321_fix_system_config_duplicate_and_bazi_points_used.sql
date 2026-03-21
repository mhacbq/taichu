-- 2026-03-21
-- 一次性修复：
-- 1) tc_system_config 重复导入时报 Duplicate entry 'site.name' for key 'uk_key'
-- 2) tc_bazi_record 缺少 points_used 导致后续修复脚本或运行时报错

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET @db_name = DATABASE();

INSERT INTO `tc_system_config` (`key`, `value`, `type`, `group`, `description`, `is_public`, `sort`) VALUES
('site.name', '太初命理', 'string', 'site', '站点名称', 1, 1),
('site.description', '专业的八字排盘、塔罗占卜、运势分析平台', 'string', 'site', '站点描述', 1, 2),
('site.logo', '', 'string', 'site', '站点Logo', 1, 3),
('site.icp', '', 'string', 'site', 'ICP备案号', 1, 4),
('site.contact_email', 'support@taichu.com', 'string', 'site', '联系邮箱', 0, 5),
('site.contact_phone', '', 'string', 'site', '联系电话', 0, 6),
('points.register', '100', 'int', 'points', '新用户注册赠送积分', 1, 1),
('points.checkin', '10', 'int', 'points', '每日签到赠送积分', 1, 2),
('points.checkin_continuous', '5', 'int', 'points', '连续签到额外奖励', 1, 3),
('points.invite', '20', 'int', 'points', '邀请好友奖励积分', 1, 4),
('points.share', '5', 'int', 'points', '分享奖励积分', 1, 5),
('points.profile_complete', '20', 'int', 'points', '完善资料奖励积分', 1, 6),
('points.bazi.basic', '0', 'int', 'points_cost', '八字基础排盘消耗', 1, 1),
('points.bazi.report', '50', 'int', 'points_cost', '八字详细报告消耗', 1, 2),
('points.tarot', '10', 'int', 'points_cost', '塔罗占卜消耗', 1, 3),
('points.daily_fortune', '0', 'int', 'points_cost', '每日运势消耗', 1, 4),
('points.yearly_fortune', '50', 'int', 'points_cost', '流年运势消耗', 1, 5),
('points.hehun', '80', 'int', 'points_cost', '八字合婚消耗', 1, 6),
('points.qiming', '100', 'int', 'points_cost', '取名建议消耗', 1, 7),
('points.jiri', '20', 'int', 'points_cost', '吉日查询消耗', 1, 8),
('vip.month.price', '19.90', 'string', 'vip', '月度VIP价格', 1, 1),
('vip.month.points', '100', 'int', 'vip', '月度VIP赠送积分', 1, 2),
('vip.quarter.price', '49.00', 'string', 'vip', '季度VIP价格', 1, 3),
('vip.quarter.points', '300', 'int', 'vip', '季度VIP赠送积分', 1, 4),
('vip.year.price', '168.00', 'string', 'vip', '年度VIP价格', 1, 5),
('vip.year.points', '1200', 'int', 'vip', '年度VIP赠送积分', 1, 6),
('vip.discount', '10', 'int', 'vip', 'VIP积分折扣(%)', 1, 7),
('recharge.option.1', '{"amount":10,"points":100,"gift":0}', 'json', 'recharge', '充值选项1', 1, 1),
('recharge.option.2', '{"amount":30,"points":330,"gift":30}', 'json', 'recharge', '充值选项2', 1, 2),
('recharge.option.3', '{"amount":50,"points":600,"gift":100}', 'json', 'recharge', '充值选项3', 1, 3),
('recharge.option.4', '{"amount":100,"points":1300,"gift":300}', 'json', 'recharge', '充值选项4', 1, 4),
('recharge.option.5', '{"amount":200,"points":2800,"gift":800}', 'json', 'recharge', '充值选项5', 1, 5),
('marketing.new_user_discount', 'true', 'bool', 'marketing', '新用户优惠', 1, 1),
('marketing.new_user_discount_rate', '50', 'int', 'marketing', '新用户折扣率(%)', 1, 2),
('marketing.limited_time_offer', 'false', 'bool', 'marketing', '限时优惠', 1, 3),
('marketing.limited_time_discount', '20', 'int', 'marketing', '限时优惠折扣(%)', 1, 4)
ON DUPLICATE KEY UPDATE
`value` = VALUES(`value`),
`type` = VALUES(`type`),
`group` = VALUES(`group`),
`description` = VALUES(`description`),
`is_public` = VALUES(`is_public`),
`sort` = VALUES(`sort`);

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'points_used'),
  'SELECT ''skip points_used''',
  IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'is_paid'),
    'ALTER TABLE `tc_bazi_record` ADD COLUMN `points_used` INT NOT NULL DEFAULT 0 COMMENT ''消耗积分'' AFTER `is_paid`',
    'ALTER TABLE `tc_bazi_record` ADD COLUMN `points_used` INT NOT NULL DEFAULT 0 COMMENT ''消耗积分'' AFTER `is_first`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

