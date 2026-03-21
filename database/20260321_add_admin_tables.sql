-- 管理端补充表结构 - VIP套餐、积分规则、系统日志、定时任务
-- 生成时间：2026-03-21

USE taichu;

-- =====================================================
-- 1. VIP套餐表
-- =====================================================
CREATE TABLE IF NOT EXISTS `tc_vip_package` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '套餐名称',
    `vip_type` TINYINT NOT NULL COMMENT 'VIP类型 1月度 2季度 3年度',
    `duration` INT NOT NULL COMMENT '时长(月)',
    `original_price` DECIMAL(10, 2) NOT NULL COMMENT '原价',
    `price` DECIMAL(10, 2) NOT NULL COMMENT '现价',
    `points` INT DEFAULT 0 COMMENT '赠送积分',
    `discount` DECIMAL(3, 2) DEFAULT 1.00 COMMENT '折扣',
    `features` JSON NULL COMMENT '功能列表',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `is_recommend` TINYINT DEFAULT 0 COMMENT '是否推荐',
    `status` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY `idx_vip_type` (`vip_type`),
    KEY `idx_status` (`status`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VIP套餐表';

-- 插入默认VIP套餐数据
INSERT INTO `tc_vip_package` (`name`, `vip_type`, `duration`, `original_price`, `price`, `points`, `discount`, `features`, `sort`, `is_recommend`, `status`)
VALUES
('月度会员', 1, 1, 19.90, 9.90, 100, 0.50, 
 JSON_ARRAY('无限次排盘', '所有报告免费', '积分充值9折', '专属客服'), 1, 0, 1),
('季度会员', 2, 3, 59.70, 29.90, 350, 0.50,
 JSON_ARRAY('无限次排盘', '所有报告免费', '积分充值8.5折', '专属客服', '新功能优先体验'), 2, 1, 1),
('年度会员', 3, 12, 238.80, 99.90, 1500, 0.42,
 JSON_ARRAY('无限次排盘', '所有报告免费', '积分充值8折', '专属客服', '新功能优先体验', '专属命理顾问'), 3, 0, 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `original_price` = VALUES(`original_price`),
    `price` = VALUES(`price`),
    `points` = VALUES(`points`),
    `discount` = VALUES(`discount`),
    `features` = VALUES(`features`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =====================================================
-- 2. 积分规则表
-- =====================================================
CREATE TABLE IF NOT EXISTS `tc_points_rule` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '规则名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '规则代码',
    `type` VARCHAR(20) NOT NULL COMMENT '类型 earn获得 consume扣除',
    `action` VARCHAR(50) NOT NULL COMMENT '业务动作',
    `points` INT NOT NULL COMMENT '积分数量',
    `max_daily` INT DEFAULT 0 COMMENT '每日上限 0无限制',
    `max_total` INT DEFAULT 0 COMMENT '总上限 0无限制',
    `description` VARCHAR(500) DEFAULT '' COMMENT '规则说明',
    `icon` VARCHAR(200) DEFAULT '' COMMENT '图标',
    `is_enabled` TINYINT DEFAULT 1 COMMENT '是否启用',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY `idx_type` (`type`),
    KEY `idx_is_enabled` (`is_enabled`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分规则表';

-- 插入默认积分规则数据
INSERT INTO `tc_points_rule` (`name`, `code`, `type`, `action`, `points`, `max_daily`, `max_total`, `description`, `icon`, `is_enabled`, `sort`)
VALUES
('每日签到', 'daily_checkin', 'earn', 'checkin', 10, 10, 0, '每日签到可获得10积分', '📅', 1, 1),
('完善资料', 'complete_profile', 'earn', 'profile', 50, 50, 50, '完善个人资料可获得50积分', '👤', 1, 2),
('邀请好友', 'invite_friend', 'earn', 'invite', 100, 0, 0, '邀请好友注册可获得100积分', '👥', 1, 3),
('分享运势', 'share_fortune', 'earn', 'share', 5, 20, 0, '分享运势可获得5积分，每日上限20', '📤', 1, 4),
('新用户注册', 'register_reward', 'earn', 'register', 100, 100, 100, '新用户注册赠送100积分', '🎁', 1, 5),
('八字排盘', 'bazi_divination', 'consume', 'bazi', 10, 0, 0, '八字排盘消耗10积分', '🔮', 1, 10),
('流年运势', 'yearly_fortune', 'consume', 'fortune', 50, 0, 0, '查看流年运势消耗50积分', '📊', 1, 11),
('八字合婚', 'bazi_marriage', 'consume', 'marriage', 30, 0, 0, '八字合婚消耗30积分', '💕', 1, 12),
('塔罗占卜', 'tarot_reading', 'consume', 'tarot', 20, 0, 0, '塔罗占卜消耗20积分', '🃏', 1, 13),
('取名建议', 'name_suggestion', 'consume', 'naming', 80, 0, 0, '取名建议消耗80积分', '📝', 1, 14)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `type` = VALUES(`type`),
    `points` = VALUES(`points`),
    `max_daily` = VALUES(`max_daily`),
    `max_total` = VALUES(`max_total`),
    `description` = VALUES(`description`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =====================================================
-- 3. 积分订单表（积分商城购买）
-- =====================================================
CREATE TABLE IF NOT EXISTS `tc_points_order` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_no` VARCHAR(50) NOT NULL COMMENT '订单号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `package_id` INT UNSIGNED DEFAULT 0 COMMENT '套餐ID',
    `points` INT NOT NULL COMMENT '积分数量',
    `amount` DECIMAL(10, 2) NOT NULL COMMENT '订单金额',
    `pay_type` VARCHAR(20) DEFAULT '' COMMENT '支付方式',
    `pay_time` DATETIME NULL COMMENT '支付时间',
    `status` TINYINT DEFAULT 0 COMMENT '状态 0待支付 1已支付 2已取消 3已退款',
    `remark` VARCHAR(500) DEFAULT '' COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分订单表';

-- =====================================================
-- 4. 系统日志表
-- =====================================================
CREATE TABLE IF NOT EXISTS `tc_system_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `level` VARCHAR(20) DEFAULT 'info' COMMENT '日志级别 debug/info/warn/error',
    `module` VARCHAR(50) DEFAULT '' COMMENT '模块',
    `action` VARCHAR(100) DEFAULT '' COMMENT '操作',
    `admin_id` INT UNSIGNED DEFAULT 0 COMMENT '管理员ID',
    `admin_name` VARCHAR(50) DEFAULT '' COMMENT '管理员名称',
    `ip` VARCHAR(45) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT '用户代理',
    `request_id` VARCHAR(50) DEFAULT '' COMMENT '请求ID',
    `message` TEXT DEFAULT '' COMMENT '日志消息',
    `data` JSON NULL COMMENT '附加数据',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    KEY `idx_level` (`level`),
    KEY `idx_module` (`module`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统日志表';

-- =====================================================
-- 5. 定时任务表
-- =====================================================
CREATE TABLE IF NOT EXISTS `tc_cron_task` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '任务名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '任务代码',
    `cron_expression` VARCHAR(50) NOT NULL COMMENT 'Cron表达式',
    `command` VARCHAR(500) NOT NULL COMMENT '执行命令',
    `params` JSON NULL COMMENT '参数',
    `description` VARCHAR(500) DEFAULT '' COMMENT '任务描述',
    `status` TINYINT DEFAULT 0 COMMENT '状态 0停止 1运行',
    `last_run_at` DATETIME NULL COMMENT '上次运行时间',
    `next_run_at` DATETIME NULL COMMENT '下次运行时间',
    `run_count` INT UNSIGNED DEFAULT 0 COMMENT '运行次数',
    `error_count` INT UNSIGNED DEFAULT 0 COMMENT '错误次数',
    `last_error` TEXT DEFAULT '' COMMENT '最后错误信息',
    `timeout` INT DEFAULT 300 COMMENT '超时时间(秒)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY `idx_status` (`status`),
    KEY `idx_code` (`code`),
    KEY `idx_next_run_at` (`next_run_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='定时任务表';

-- 插入默认定时任务数据
INSERT INTO `tc_cron_task` (`name`, `code`, `cron_expression`, `command`, `params`, `description`, `status`, `timeout`, `sort`)
VALUES
('每日运势生成', 'daily_fortune', '0 1 * * *', 'php think fortune:generate', NULL, '每日凌晨1点生成次日运势', 1, 300, 1),
('统计日报', 'daily_stats', '0 2 * * *', 'php think stats:daily', NULL, '每日凌晨2点生成昨日统计数据', 1, 600, 2),
('清理过期验证码', 'clean_sms_code', '0 */6 * * *', 'php think clean:sms', NULL, '每6小时清理过期的短信验证码', 1, 60, 3),
('清理过期临时文件', 'clean_temp', '0 3 * * *', 'php think clean:temp', NULL, '每日凌晨3点清理临时文件', 1, 300, 4),
('VIP到期检查', 'check_vip_expire', '0 */12 * * *', 'php think vip:expire', NULL, '每12小时检查VIP到期情况', 1, 180, 5)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `cron_expression` = VALUES(`cron_expression`),
    `command` = VALUES(`command`),
    `description` = VALUES(`description`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =====================================================
-- 6. 管理员待办事项表
-- =====================================================
CREATE TABLE IF NOT EXISTS `tc_admin_todo` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
    `title` VARCHAR(200) NOT NULL COMMENT '待办标题',
    `content` TEXT DEFAULT '' COMMENT '待办内容',
    `priority` TINYINT DEFAULT 0 COMMENT '优先级 0普通 1重要 2紧急',
    `status` TINYINT DEFAULT 0 COMMENT '状态 0待办 1已完成',
    `due_date` DATETIME NULL COMMENT '截止日期',
    `completed_at` DATETIME NULL COMMENT '完成时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员待办事项表';
