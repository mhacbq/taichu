-- ============================================================
-- 太初命理网站 - 数据库表结构修复脚本
-- 用于修复缺失的字段
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. 积分记录表 tc_points_record - 添加 action 字段
-- ============================================================
ALTER TABLE `tc_points_record` 
ADD COLUMN IF NOT EXISTS `action` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '动作描述' AFTER `user_id`;

-- ============================================================
-- 2. 用户表 tc_user - 检查并添加可能缺失的字段
-- ============================================================
-- 检查是否有 invite_code 字段（邀请功能）
ALTER TABLE `tc_user` 
ADD COLUMN IF NOT EXISTS `invite_code` VARCHAR(20) DEFAULT '' COMMENT '邀请码' AFTER `status`,
ADD COLUMN IF NOT EXISTS `invited_by` INT(11) UNSIGNED DEFAULT 0 COMMENT '被谁邀请' AFTER `invite_code`,
ADD COLUMN IF NOT EXISTS `is_vip` TINYINT(1) DEFAULT 0 COMMENT '是否VIP: 0否 1是' AFTER `invited_by`,
ADD COLUMN IF NOT EXISTS `vip_expire_at` DATETIME DEFAULT NULL COMMENT 'VIP过期时间' AFTER `is_vip`;

-- ============================================================
-- 3. 每日运势表 tc_daily_fortune - 如果不存在则创建
-- ============================================================
CREATE TABLE IF NOT EXISTS `tc_daily_fortune` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `fortune_date` DATE NOT NULL COMMENT '运势日期',
  `overall_score` INT(11) DEFAULT 0 COMMENT '综合评分',
  `career_score` INT(11) DEFAULT 0 COMMENT '事业评分',
  `wealth_score` INT(11) DEFAULT 0 COMMENT '财运评分',
  `love_score` INT(11) DEFAULT 0 COMMENT '感情评分',
  `health_score` INT(11) DEFAULT 0 COMMENT '健康评分',
  `lucky_direction` VARCHAR(20) DEFAULT '' COMMENT '幸运方位',
  `lucky_color` VARCHAR(20) DEFAULT '' COMMENT '幸运颜色',
  `lucky_number` VARCHAR(10) DEFAULT '' COMMENT '幸运数字',
  `advice` TEXT COMMENT '建议',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_date` (`user_id`, `fortune_date`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='每日运势记录表';

-- ============================================================
-- 4. 签到记录表 tc_checkin_record - 如果不存在则创建
-- ============================================================
CREATE TABLE IF NOT EXISTS `tc_checkin_record` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `checkin_date` DATE NOT NULL COMMENT '签到日期',
  `points` INT(11) DEFAULT 0 COMMENT '获得积分',
  `continuous_days` INT(11) DEFAULT 1 COMMENT '连续签到天数',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_date` (`user_id`, `checkin_date`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到记录表';

-- ============================================================
-- 5. 管理员表 tc_admin - 如果不存在则创建
-- ============================================================
CREATE TABLE IF NOT EXISTS `tc_admin` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码',
  `nickname` VARCHAR(50) DEFAULT '' COMMENT '昵称',
  `email` VARCHAR(100) DEFAULT '' COMMENT '邮箱',
  `phone` VARCHAR(20) DEFAULT '' COMMENT '手机号',
  `avatar` VARCHAR(500) DEFAULT '' COMMENT '头像',
  `role_id` INT(11) UNSIGNED DEFAULT 0 COMMENT '角色ID',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 0禁用 1启用',
  `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` VARCHAR(45) DEFAULT '' COMMENT '最后登录IP',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- ============================================================
-- 6. 管理员日志表 tc_admin_log - 如果不存在则创建
-- ============================================================
CREATE TABLE IF NOT EXISTS `tc_admin_log` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) UNSIGNED DEFAULT 0 COMMENT '管理员ID',
  `admin_name` VARCHAR(50) DEFAULT '' COMMENT '管理员名称',
  `action` VARCHAR(100) DEFAULT '' COMMENT '操作',
  `module` VARCHAR(50) DEFAULT '' COMMENT '模块',
  `target_id` INT(11) UNSIGNED DEFAULT 0 COMMENT '目标ID',
  `target_type` VARCHAR(50) DEFAULT '' COMMENT '目标类型',
  `method` VARCHAR(10) DEFAULT '' COMMENT '请求方法',
  `url` VARCHAR(500) DEFAULT '' COMMENT 'URL',
  `params` JSON DEFAULT NULL COMMENT '参数',
  `ip` VARCHAR(45) DEFAULT '' COMMENT 'IP地址',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员日志表';

-- ============================================================
-- 7. 系统配置表 tc_config - 如果不存在则创建
-- ============================================================
CREATE TABLE IF NOT EXISTS `tc_config` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL COMMENT '配置键',
  `value` TEXT COMMENT '配置值',
  `type` VARCHAR(20) DEFAULT 'string' COMMENT '类型: string/int/json/array',
  `description` VARCHAR(255) DEFAULT '' COMMENT '描述',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- ============================================================
-- 8. VIP订单表 tc_vip_order - 如果不存在则创建
-- ============================================================
CREATE TABLE IF NOT EXISTS `tc_vip_order` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` VARCHAR(50) NOT NULL COMMENT '订单号',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `vip_type` VARCHAR(30) DEFAULT 'month' COMMENT 'VIP类型: month/quarter/year',
  `amount` DECIMAL(10,2) NOT NULL COMMENT '金额',
  `status` VARCHAR(20) DEFAULT 'pending' COMMENT '状态: pending/paid/cancelled',
  `pay_time` DATETIME DEFAULT NULL COMMENT '支付时间',
  `expire_at` DATETIME DEFAULT NULL COMMENT 'VIP过期时间',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='VIP订单表';

-- ============================================================
-- 9. 反馈表 tc_feedback - 如果不存在则创建
-- ============================================================
CREATE TABLE IF NOT EXISTS `tc_feedback` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `type` VARCHAR(50) DEFAULT '' COMMENT '反馈类型',
  `title` VARCHAR(200) DEFAULT '' COMMENT '标题',
  `content` TEXT COMMENT '内容',
  `contact` VARCHAR(100) DEFAULT '' COMMENT '联系方式',
  `images` JSON DEFAULT NULL COMMENT '图片',
  `status` TINYINT(1) DEFAULT 0 COMMENT '状态: 0待处理 1处理中 2已解决',
  `reply` TEXT COMMENT '回复',
  `replied_at` DATETIME DEFAULT NULL COMMENT '回复时间',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户反馈表';

-- ============================================================
-- 10. AI分析记录表 tc_ai_analysis - 如果不存在则创建
-- ============================================================
CREATE TABLE IF NOT EXISTS `tc_ai_analysis` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `type` VARCHAR(50) DEFAULT '' COMMENT '分析类型: bazi/tarot/liuyao/hehun',
  `input_data` JSON DEFAULT NULL COMMENT '输入数据',
  `result` TEXT COMMENT '分析结果',
  `model` VARCHAR(50) DEFAULT '' COMMENT '使用的AI模型',
  `tokens` INT(11) DEFAULT 0 COMMENT '消耗的token数',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI分析记录表';

SET FOREIGN_KEY_CHECKS = 1;

-- 完成
SELECT '数据库修复完成' AS message;
