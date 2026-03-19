-- Full Database Import for Navicat
-- Generated at 03/18/2026 14:00:00

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Ensure database exists
CREATE DATABASE IF NOT EXISTS `taichu` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `taichu`;

-- =====================================================
-- 太初命理系统 - 表结构创建脚本
-- 按依赖顺序创建所有表
-- =====================================================

-- =====================================================
-- 1. 用户相关表
-- =====================================================

-- 用户表
CREATE TABLE IF NOT EXISTS `tc_user` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `openid` VARCHAR(100) DEFAULT '' COMMENT '微信openid',
    `unionid` VARCHAR(100) DEFAULT '' COMMENT '微信unionid',
    `phone` VARCHAR(20) DEFAULT '' COMMENT '手机号',
    `password` VARCHAR(255) DEFAULT '' COMMENT '密码',
    `nickname` VARCHAR(50) DEFAULT '' COMMENT '昵称',
    `avatar` VARCHAR(500) DEFAULT '' COMMENT '头像',
    `gender` TINYINT DEFAULT 0 COMMENT '性别 0未知 1男 2女',
    `birth_date` DATE NULL COMMENT '出生日期',
    `birth_time` TIME NULL COMMENT '出生时间',
    `birth_place` VARCHAR(200) DEFAULT '' COMMENT '出生地点',
    `points` INT UNSIGNED DEFAULT 0 COMMENT '积分余额',
    `vip_level` TINYINT DEFAULT 0 COMMENT 'VIP等级 0普通 1月度 2季度 3年度',
    `vip_expire_at` DATETIME NULL COMMENT 'VIP到期时间',
    `status` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1正常',
    `last_login_at` DATETIME NULL COMMENT '最后登录时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_openid` (`openid`),
    UNIQUE KEY `uk_phone` (`phone`),
    INDEX `idx_status` (`status`),
    INDEX `idx_vip` (`vip_level`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- 用户资料扩展表
CREATE TABLE IF NOT EXISTS `tc_user_profile` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `real_name` VARCHAR(50) DEFAULT '' COMMENT '真实姓名',
    `id_number` VARCHAR(20) DEFAULT '' COMMENT '身份证号',
    `address` VARCHAR(500) DEFAULT '' COMMENT '地址',
    `email` VARCHAR(100) DEFAULT '' COMMENT '邮箱',
    `wechat` VARCHAR(50) DEFAULT '' COMMENT '微信号',
    `qq` VARCHAR(20) DEFAULT '' COMMENT 'QQ号',
    `bio` TEXT COMMENT '个人简介',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_user_id` (`user_id`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户资料扩展表';

-- =====================================================
-- 2. 积分相关表
-- =====================================================

-- 积分记录表
CREATE TABLE IF NOT EXISTS `tc_points_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL COMMENT '类型 add增加 reduce扣除',
    `amount` INT NOT NULL COMMENT '变动数量',
    `balance` INT NOT NULL COMMENT '变动后余额',
    `reason` VARCHAR(255) NOT NULL COMMENT '变动原因',
    `source_id` INT UNSIGNED DEFAULT 0 COMMENT '来源ID',
    `source_type` VARCHAR(50) DEFAULT '' COMMENT '来源类型',
    `remark` VARCHAR(500) DEFAULT '' COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分记录表';

-- 积分任务记录表
CREATE TABLE IF NOT EXISTS `tc_points_task` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `task_type` VARCHAR(50) NOT NULL COMMENT '任务类型',
    `task_name` VARCHAR(100) NOT NULL COMMENT '任务名称',
    `points` INT NOT NULL COMMENT '奖励积分',
    `completed` TINYINT DEFAULT 0 COMMENT '是否完成 0否 1是',
    `completed_at` DATETIME NULL COMMENT '完成时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_user_task` (`user_id`, `task_type`, `created_at`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_task_type` (`task_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分任务记录表';

-- =====================================================
-- 3. VIP相关表
-- =====================================================

-- VIP订单表
CREATE TABLE IF NOT EXISTS `tc_vip_order` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_no` VARCHAR(50) NOT NULL COMMENT '订单号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `vip_type` TINYINT NOT NULL COMMENT 'VIP类型 1月度 2季度 3年度',
    `duration` INT NOT NULL COMMENT '时长(月)',
    `amount` DECIMAL(10, 2) NOT NULL COMMENT '订单金额',
    `pay_type` VARCHAR(20) DEFAULT '' COMMENT '支付方式',
    `pay_time` DATETIME NULL COMMENT '支付时间',
    `status` TINYINT DEFAULT 0 COMMENT '状态 0待支付 1已支付 2已取消',
    `start_date` DATE NULL COMMENT '开始日期',
    `end_date` DATE NULL COMMENT '结束日期',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_order_no` (`order_no`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VIP订单表';

-- =====================================================
-- 4. 八字相关表
-- =====================================================

-- 八字排盘记录表
CREATE TABLE IF NOT EXISTS `tc_bazi_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '用户ID 0为游客',
    `name` VARCHAR(50) DEFAULT '' COMMENT '姓名',
    `gender` TINYINT NOT NULL COMMENT '性别 1男 2女',
    `birth_date` DATE NOT NULL COMMENT '出生日期',
    `birth_time` TIME NOT NULL COMMENT '出生时间',
    `birth_place` VARCHAR(200) DEFAULT '' COMMENT '出生地点',
    `longitude` DECIMAL(10, 6) NULL COMMENT '经度',
    `latitude` DECIMAL(10, 6) NULL COMMENT '纬度',
    `year_pillar` VARCHAR(10) DEFAULT '' COMMENT '年柱',
    `month_pillar` VARCHAR(10) DEFAULT '' COMMENT '月柱',
    `day_pillar` VARCHAR(10) DEFAULT '' COMMENT '日柱',
    `hour_pillar` VARCHAR(10) DEFAULT '' COMMENT '时柱',
    `wuxing_analysis` JSON NULL COMMENT '五行分析',
    `shishen_analysis` JSON NULL COMMENT '十神分析',
    `dayun` JSON NULL COMMENT '大运数据',
    `liunian` JSON NULL COMMENT '流年数据',
    `report_data` JSON NULL COMMENT '完整报告数据',
    `is_paid` TINYINT DEFAULT 0 COMMENT '是否付费解锁完整报告',
    `points_used` INT DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='八字排盘记录表';

-- 八字合婚记录表
CREATE TABLE IF NOT EXISTS `tc_hehun_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `male_name` VARCHAR(50) DEFAULT '' COMMENT '男方姓名',
    `male_birth_date` DATE NOT NULL COMMENT '男方出生日期',
    `male_birth_time` TIME NOT NULL COMMENT '男方出生时间',
    `female_name` VARCHAR(50) DEFAULT '' COMMENT '女方姓名',
    `female_birth_date` DATE NOT NULL COMMENT '女方出生日期',
    `female_birth_time` TIME NOT NULL COMMENT '女方出生时间',
    `bazi_match` JSON NULL COMMENT '八字合婚分析',
    `wuxing_match` JSON NULL COMMENT '五行匹配分析',
    `score` TINYINT DEFAULT 0 COMMENT '合婚评分',
    `result` TEXT COMMENT '合婚结果',
    `points_used` INT DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='八字合婚记录表';

-- 取名建议记录表
CREATE TABLE IF NOT EXISTS `tc_qiming_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `surname` VARCHAR(10) NOT NULL COMMENT '姓氏',
    `gender` TINYINT NOT NULL COMMENT '性别',
    `birth_date` DATE NOT NULL COMMENT '出生日期',
    `birth_time` TIME NOT NULL COMMENT '出生时间',
    `wuxing_lack` VARCHAR(50) DEFAULT '' COMMENT '五行缺失',
    `name_suggestions` JSON NULL COMMENT '名字建议列表',
    `points_used` INT DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='取名建议记录表';

-- =====================================================
-- 5. 运势相关表
-- =====================================================

-- 每日运势记录表
CREATE TABLE IF NOT EXISTS `tc_daily_fortune` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '日期',
    `fortune_type` VARCHAR(20) NOT NULL COMMENT '运势类型 tarot/meihao/etc',
    `card_id` INT DEFAULT 0 COMMENT '塔罗牌ID',
    `card_name` VARCHAR(50) DEFAULT '' COMMENT '塔罗牌名称',
    `card_image` VARCHAR(500) DEFAULT '' COMMENT '塔罗牌图片',
    `fortune_score` TINYINT DEFAULT 0 COMMENT '运势评分',
    `fortune_desc` TEXT COMMENT '运势描述',
    `suggestions` JSON NULL COMMENT '建议',
    `is_shared` TINYINT DEFAULT 0 COMMENT '是否已分享',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_user_date_type` (`user_id`, `date`, `fortune_type`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日运势记录表';

-- 流年运势记录表
CREATE TABLE IF NOT EXISTS `tc_yearly_fortune` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `year` INT NOT NULL COMMENT '年份',
    `bazi_id` INT UNSIGNED NOT NULL COMMENT '八字记录ID',
    `overall_score` TINYINT DEFAULT 0 COMMENT '综合评分',
    `career_score` TINYINT DEFAULT 0 COMMENT '事业评分',
    `wealth_score` TINYINT DEFAULT 0 COMMENT '财运评分',
    `love_score` TINYINT DEFAULT 0 COMMENT '感情评分',
    `health_score` TINYINT DEFAULT 0 COMMENT '健康评分',
    `overall_analysis` TEXT COMMENT '综合分析',
    `monthly_fortune` JSON NULL COMMENT '月度运势',
    `important_days` JSON NULL COMMENT '重要日期',
    `is_paid` TINYINT DEFAULT 0 COMMENT '是否付费解锁',
    `points_used` INT DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_user_year` (`user_id`, `year`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流年运势记录表';

-- =====================================================
-- 6. 支付相关表
-- =====================================================

-- 充值订单表
CREATE TABLE IF NOT EXISTS `tc_recharge_order` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_no` VARCHAR(50) NOT NULL COMMENT '订单号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `amount` DECIMAL(10, 2) NOT NULL COMMENT '充值金额',
    `points` INT NOT NULL COMMENT '获得积分',
    `pay_type` VARCHAR(20) DEFAULT 'wechat' COMMENT '支付方式 wechat/alipay',
    `pay_status` TINYINT DEFAULT 0 COMMENT '支付状态 0待支付 1已支付 2已取消',
    `pay_time` DATETIME NULL COMMENT '支付时间',
    `transaction_id` VARCHAR(100) DEFAULT '' COMMENT '第三方支付订单号',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_order_no` (`order_no`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_pay_status` (`pay_status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值订单表';

-- 支付配置表
CREATE TABLE IF NOT EXISTS `tc_payment_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(20) NOT NULL COMMENT '配置类型 wechat/alipay',
    `app_id` VARCHAR(100) NOT NULL COMMENT '应用ID',
    `mch_id` VARCHAR(50) DEFAULT '' COMMENT '商户号',
    `api_key` VARCHAR(255) DEFAULT '' COMMENT 'API密钥',
    `notify_url` VARCHAR(500) DEFAULT '' COMMENT '通知URL',
    `status` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付配置表';

-- =====================================================
-- 7. 短信相关表
-- =====================================================

-- 短信验证码表
CREATE TABLE IF NOT EXISTS `tc_sms_code` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
    `code` VARCHAR(10) NOT NULL COMMENT '验证码',
    `type` VARCHAR(20) NOT NULL DEFAULT 'register' COMMENT '类型 register/login/reset',
    `expire_time` DATETIME NOT NULL COMMENT '过期时间',
    `is_used` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已使用 0否 1是',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'User-Agent',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_phone` (`phone`),
    INDEX `idx_code` (`code`),
    INDEX `idx_type` (`type`),
    INDEX `idx_is_used` (`is_used`),
    INDEX `idx_phone_type` (`phone`, `type`),
    INDEX `idx_expire_time` (`expire_time`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信验证码表';

-- 短信配置表
CREATE TABLE IF NOT EXISTS `tc_sms_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `provider` VARCHAR(20) NOT NULL COMMENT '服务商 tencent/aliyun',
    `app_id` VARCHAR(100) NOT NULL COMMENT '应用ID',
    `secret_id` VARCHAR(100) NOT NULL COMMENT '密钥ID',
    `secret_key` VARCHAR(255) NOT NULL COMMENT '密钥',
    `sign_name` VARCHAR(50) NOT NULL COMMENT '签名',
    `template_code` VARCHAR(50) NOT NULL COMMENT '模板代码',
    `status` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信配置表';

-- =====================================================
-- 8. 邀请相关表
-- =====================================================

-- 邀请记录表
CREATE TABLE IF NOT EXISTS `tc_invite_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inviter_id` INT UNSIGNED NOT NULL COMMENT '邀请人ID',
    `invitee_id` INT UNSIGNED DEFAULT 0 COMMENT '被邀请人ID',
    `invite_code` VARCHAR(20) NOT NULL COMMENT '邀请码',
    `points_reward` INT DEFAULT 0 COMMENT '奖励积分',
    `status` TINYINT DEFAULT 1 COMMENT '状态 0无效 1有效',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_invite_code` (`invite_code`),
    INDEX `idx_inviter_id` (`inviter_id`),
    INDEX `idx_invitee_id` (`invitee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邀请记录表';

-- =====================================================
-- 9. 反馈相关表
-- =====================================================

-- 用户反馈表
CREATE TABLE IF NOT EXISTS `tc_feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL COMMENT '类型 bug/feature/other',
    `title` VARCHAR(200) NOT NULL COMMENT '标题',
    `content` TEXT NOT NULL COMMENT '内容',
    `images` JSON NULL COMMENT '图片列表',
    `contact` VARCHAR(100) DEFAULT '' COMMENT '联系方式',
    `status` VARCHAR(20) DEFAULT 'pending' COMMENT '状态 pending/processing/resolved',
    `reply` TEXT COMMENT '回复内容',
    `replied_at` DATETIME NULL COMMENT '回复时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户反馈表';

-- =====================================================
-- 10. 系统配置表
-- =====================================================

-- 系统配置表
CREATE TABLE IF NOT EXISTS `tc_system_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL COMMENT '配置键',
    `value` TEXT COMMENT '配置值',
    `type` VARCHAR(20) DEFAULT 'string' COMMENT '值类型 string/int/bool/json',
    `group` VARCHAR(50) DEFAULT 'general' COMMENT '配置分组',
    `description` VARCHAR(255) DEFAULT '' COMMENT '配置描述',
    `is_public` TINYINT DEFAULT 0 COMMENT '是否公开 0否 1是',
    `sort` INT DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_key` (`key`),
    INDEX `idx_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- 功能开关表
CREATE TABLE IF NOT EXISTS `tc_feature_switch` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(50) NOT NULL COMMENT '功能标识',
    `name` VARCHAR(100) NOT NULL COMMENT '功能名称',
    `enabled` TINYINT DEFAULT 1 COMMENT '是否启用 0否 1是',
    `description` VARCHAR(255) DEFAULT '' COMMENT '功能描述',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='功能开关表';

-- =====================================================
-- 11. 管理员权限相关表
-- =====================================================

-- 管理员权限表
CREATE TABLE IF NOT EXISTS `tc_admin_permission` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '权限名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '权限标识',
    `module` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '所属模块',
    `description` VARCHAR(255) DEFAULT '' COMMENT '权限描述',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_code` (`code`),
    INDEX `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员权限表';

-- 管理员角色表
CREATE TABLE IF NOT EXISTS `tc_admin_role` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '角色标识',
    `description` VARCHAR(255) DEFAULT '' COMMENT '角色描述',
    `is_super` TINYINT DEFAULT 0 COMMENT '是否超级管理员',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色表';

-- 角色权限关联表
CREATE TABLE IF NOT EXISTS `tc_admin_role_permission` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `permission_id` INT UNSIGNED NOT NULL COMMENT '权限ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uniq_role_permission` (`role_id`, `permission_id`),
    INDEX `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限关联表';

-- 用户角色关联表
CREATE TABLE IF NOT EXISTS `tc_admin_user_role` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uniq_admin_role` (`admin_id`, `role_id`),
    INDEX `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户角色关联表';

-- 管理员操作日志表
CREATE TABLE IF NOT EXISTS `tc_admin_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
    `admin_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '管理员名称',
    `action` VARCHAR(50) NOT NULL COMMENT '操作类型',
    `module` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作模块',
    `target_id` INT UNSIGNED DEFAULT 0 COMMENT '操作目标ID',
    `target_type` VARCHAR(50) DEFAULT '' COMMENT '操作目标类型',
    `detail` TEXT COMMENT '操作详情',
    `before_data` JSON NULL COMMENT '操作前数据',
    `after_data` JSON NULL COMMENT '操作后数据',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '操作IP',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User-Agent',
    `request_url` VARCHAR(500) DEFAULT '' COMMENT '请求URL',
    `request_method` VARCHAR(10) DEFAULT '' COMMENT '请求方法',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 1成功 0失败',
    `error_msg` VARCHAR(500) DEFAULT '' COMMENT '错误信息',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_module` (`module`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员操作日志表';

-- =====================================================
-- 12. 内容管理表
-- =====================================================

-- 页面表
CREATE TABLE IF NOT EXISTS `tc_page` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL COMMENT '页面标识',
    `title` VARCHAR(200) NOT NULL COMMENT '页面标题',
    `content` LONGTEXT COMMENT '页面内容',
    `meta_title` VARCHAR(200) DEFAULT '' COMMENT 'SEO标题',
    `meta_description` VARCHAR(500) DEFAULT '' COMMENT 'SEO描述',
    `meta_keywords` VARCHAR(300) DEFAULT '' COMMENT 'SEO关键词',
    `status` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_slug` (`slug`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面表';

-- FAQ表
CREATE TABLE IF NOT EXISTS `tc_faq` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category` VARCHAR(50) DEFAULT '' COMMENT '分类',
    `question` VARCHAR(500) NOT NULL COMMENT '问题',
    `answer` TEXT NOT NULL COMMENT '答案',
    `sort` INT DEFAULT 0 COMMENT '排序',
    `status` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_category` (`category`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='FAQ表';

-- 塔罗牌表
CREATE TABLE IF NOT EXISTS `tc_tarot_card` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '牌名',
    `name_en` VARCHAR(100) DEFAULT '' COMMENT '英文名',
    `type` VARCHAR(20) NOT NULL COMMENT '类型 major/minor',
    `suit` VARCHAR(20) DEFAULT '' COMMENT '花色 cups/wands/swords/pentacles',
    `number` INT DEFAULT 0 COMMENT '数字',
    `image` VARCHAR(500) DEFAULT '' COMMENT '图片',
    `upright_meaning` TEXT COMMENT '正位含义',
    `reversed_meaning` TEXT COMMENT '逆位含义',
    `keywords` VARCHAR(500) DEFAULT '' COMMENT '关键词',
    `element` VARCHAR(20) DEFAULT '' COMMENT '元素',
    `planet` VARCHAR(20) DEFAULT '' COMMENT '行星',
    `zodiac` VARCHAR(20) DEFAULT '' COMMENT '星座',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_type` (`type`),
    INDEX `idx_suit` (`suit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗牌表';

-- 文件上传表
CREATE TABLE IF NOT EXISTS `tc_upload_file` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '用户ID',
    `original_name` VARCHAR(255) DEFAULT '' COMMENT '原始文件名',
    `file_name` VARCHAR(255) NOT NULL COMMENT '存储文件名',
    `file_path` VARCHAR(500) NOT NULL COMMENT '文件路径',
    `file_url` VARCHAR(500) NOT NULL COMMENT '文件URL',
    `file_type` VARCHAR(50) DEFAULT '' COMMENT '文件类型',
    `file_size` INT UNSIGNED DEFAULT 0 COMMENT '文件大小(字节)',
    `mime_type` VARCHAR(100) DEFAULT '' COMMENT 'MIME类型',
    `used` TINYINT DEFAULT 0 COMMENT '是否已使用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_file_type` (`file_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件上传表';

-- AI提示词表
CREATE TABLE IF NOT EXISTS `tc_ai_prompt` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '提示词名称',
    `type` VARCHAR(50) NOT NULL COMMENT '类型 bazi/fortune/tarot/etc',
    `prompt` TEXT NOT NULL COMMENT '提示词内容',
    `variables` JSON NULL COMMENT '变量列表',
    `status` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_name` (`name`),
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI提示词表';


-- Source: backup/03_insert_basic_data.sql
-- =====================================================
-- 太初命理系统 - 基础数据插入脚本
-- 包含系统运行必需的基础配置数据
-- =====================================================

USE taichu;

-- =====================================================
-- 1. 插入系统配置数据
-- =====================================================

INSERT INTO `tc_system_config` (`key`, `value`, `type`, `group`, `description`, `is_public`, `sort`) VALUES
-- 站点配置
('site.name', '太初命理', 'string', 'site', '站点名称', 1, 1),
('site.description', '专业的八字排盘、塔罗占卜、运势分析平台', 'string', 'site', '站点描述', 1, 2),
('site.logo', '', 'string', 'site', '站点Logo', 1, 3),
('site.icp', '', 'string', 'site', 'ICP备案号', 1, 4),
('site.contact_email', 'support@taichu.com', 'string', 'site', '联系邮箱', 0, 5),
('site.contact_phone', '', 'string', 'site', '联系电话', 0, 6),

-- 积分配置
('points.register', '100', 'int', 'points', '新用户注册赠送积分', 1, 1),
('points.checkin', '10', 'int', 'points', '每日签到赠送积分', 1, 2),
('points.checkin_continuous', '5', 'int', 'points', '连续签到额外奖励', 1, 3),
('points.invite', '20', 'int', 'points', '邀请好友奖励积分', 1, 4),
('points.share', '5', 'int', 'points', '分享奖励积分', 1, 5),
('points.profile_complete', '20', 'int', 'points', '完善资料奖励积分', 1, 6),

-- 功能消耗积分配置
('points.bazi.basic', '0', 'int', 'points_cost', '八字基础排盘消耗', 1, 1),
('points.bazi.report', '50', 'int', 'points_cost', '八字详细报告消耗', 1, 2),
('points.tarot', '10', 'int', 'points_cost', '塔罗占卜消耗', 1, 3),
('points.daily_fortune', '0', 'int', 'points_cost', '每日运势消耗', 1, 4),
('points.yearly_fortune', '50', 'int', 'points_cost', '流年运势消耗', 1, 5),
('points.hehun', '80', 'int', 'points_cost', '八字合婚消耗', 1, 6),
('points.qiming', '100', 'int', 'points_cost', '取名建议消耗', 1, 7),
('points.jiri', '20', 'int', 'points_cost', '吉日查询消耗', 1, 8),

-- VIP配置
('vip.month.price', '19.90', 'string', 'vip', '月度VIP价格', 1, 1),
('vip.month.points', '100', 'int', 'vip', '月度VIP赠送积分', 1, 2),
('vip.quarter.price', '49.00', 'string', 'vip', '季度VIP价格', 1, 3),
('vip.quarter.points', '300', 'int', 'vip', '季度VIP赠送积分', 1, 4),
('vip.year.price', '168.00', 'string', 'vip', '年度VIP价格', 1, 5),
('vip.year.points', '1200', 'int', 'vip', '年度VIP赠送积分', 1, 6),
('vip.discount', '10', 'int', 'vip', 'VIP积分折扣(%)', 1, 7),

-- 充值配置
('recharge.option.1', '{"amount":10,"points":100,"gift":0}', 'json', 'recharge', '充值选项1', 1, 1),
('recharge.option.2', '{"amount":30,"points":330,"gift":30}', 'json', 'recharge', '充值选项2', 1, 2),
('recharge.option.3', '{"amount":50,"points":600,"gift":100}', 'json', 'recharge', '充值选项3', 1, 3),
('recharge.option.4', '{"amount":100,"points":1300,"gift":300}', 'json', 'recharge', '充值选项4', 1, 4),
('recharge.option.5', '{"amount":200,"points":2800,"gift":800}', 'json', 'recharge', '充值选项5', 1, 5),

-- 营销配置
('marketing.new_user_discount', 'true', 'bool', 'marketing', '新用户优惠', 1, 1),
('marketing.new_user_discount_rate', '50', 'int', 'marketing', '新用户折扣率(%)', 1, 2),
('marketing.limited_time_offer', 'false', 'bool', 'marketing', '限时优惠', 1, 3),
('marketing.limited_time_discount', '20', 'int', 'marketing', '限时优惠折扣(%)', 1, 4);

-- =====================================================
-- 2. 插入功能开关数据
-- =====================================================

INSERT INTO `tc_feature_switch` (`key`, `name`, `enabled`, `description`) VALUES
('vip', 'VIP会员', 1, 'VIP会员功能'),
('points', '积分系统', 1, '积分系统功能'),
('ai_analysis', 'AI解盘', 1, 'AI智能分析功能'),
('recharge', '充值功能', 1, '积分充值功能'),
('share', '分享功能', 1, '分享和邀请功能'),
('hehun', '八字合婚', 1, '八字合婚功能'),
('qiming', '取名建议', 1, '取名建议功能'),
('jiri', '吉日查询', 1, '吉日查询功能'),
('daily_fortune', '每日运势', 1, '每日运势功能'),
('yearly_fortune', '流年运势', 1, '流年运势功能'),
('tarot', '塔罗占卜', 1, '塔罗占卜功能'),
('feedback', '用户反馈', 1, '用户反馈功能'),
('checkin', '每日签到', 1, '每日签到功能'),
('invite', '邀请好友', 1, '邀请好友功能');

-- =====================================================
-- 3. 插入管理员权限数据
-- =====================================================

INSERT INTO `tc_admin_permission` (`name`, `code`, `module`, `description`) VALUES
('用户查看', 'user_view', 'user', '查看用户列表和信息'),
('用户编辑', 'user_edit', 'user', '编辑用户信息'),
('用户删除', 'user_delete', 'user', '删除用户'),
('积分查看', 'points_view', 'points', '查看积分记录'),
('积分调整', 'points_adjust', 'points', '调整用户积分'),
('配置管理', 'config_manage', 'config', '管理系统配置'),
('配置查看', 'config_view', 'config', '查看系统配置'),
('日志查看', 'log_view', 'log', '查看操作日志'),
('数据统计', 'stats_view', 'stats', '查看统计数据'),
('内容管理', 'content_manage', 'content', '管理内容数据'),
('内容审核', 'content_audit', 'content', '审核内容'),
('订单查看', 'order_view', 'order', '查看订单'),
('订单处理', 'order_process', 'order', '处理订单'),
('VIP管理', 'vip_manage', 'vip', '管理VIP会员'),
('营销管理', 'marketing_manage', 'marketing', '管理营销活动');

-- =====================================================
-- 4. 插入管理员角色数据
-- =====================================================

INSERT INTO `tc_admin_role` (`name`, `code`, `description`, `is_super`) VALUES
('超级管理员', 'super_admin', '拥有所有权限', 1),
('普通管理员', 'normal_admin', '拥有常规管理权限', 0),
('运营人员', 'operator', '仅限查看和部分编辑权限', 0),
('客服人员', 'customer_service', '仅限处理用户反馈和订单', 0);

-- =====================================================
-- 5. 插入角色权限关联数据
-- =====================================================

-- 普通管理员权限
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT 2, id FROM `tc_admin_permission` WHERE code IN (
    'user_view', 'user_edit', 'points_view', 'config_view', 
    'stats_view', 'content_manage', 'order_view', 'order_process'
);

-- 运营人员权限
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT 3, id FROM `tc_admin_permission` WHERE code IN (
    'user_view', 'points_view', 'stats_view', 'marketing_manage'
);

-- 客服人员权限
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT 4, id FROM `tc_admin_permission` WHERE code IN (
    'user_view', 'order_view', 'order_process'
);

-- =====================================================
-- 6. 插入塔罗牌数据（基础22张大阿卡纳）
-- =====================================================

INSERT INTO `tc_tarot_card` (`name`, `name_en`, `type`, `number`, `element`, `keywords`, `upright_meaning`, `reversed_meaning`) VALUES
('愚者', 'The Fool', 'major', 0, 'air', '开始、自由、冒险', 
 '新的开始，冒险精神，不受束缚，信任直觉，充满潜力', 
 '鲁莽冲动，缺乏计划，盲目乐观，轻率决定，冒险过度'),

('魔术师', 'The Magician', 'major', 1, 'air', '创造、力量、行动',
 '拥有资源和能力，将想法变为现实，主动创造，充满信心',
 '欺骗、操纵，滥用权力，缺乏信心，资源浪费，能力不足'),

('女祭司', 'The High Priestess', 'major', 2, 'water', '直觉、神秘、智慧',
 '直觉敏锐，内在智慧，神秘力量，潜意识觉醒，静待时机',
 '忽视直觉，表面判断，秘密被揭露，缺乏耐心，直觉受阻'),

('皇后', 'The Empress', 'major', 3, 'earth', '丰饶、创造、母性',
 '创造力旺盛，享受生活，自然之美，丰盛收获，母性光辉',
 '创造力受阻，依赖他人，过度放纵，不孕，缺乏灵感'),

('皇帝', 'The Emperor', 'major', 4, 'fire', '权威、结构、控制',
 '建立秩序，领导能力，稳定结构，理性决策，掌控局面',
 '专制统治，僵化死板，权力滥用，失去控制，缺乏纪律'),

('教皇', 'The Hierophant', 'major', 5, 'earth', '传统、信仰、教导',
 '遵循传统，精神指引，寻求指导，学习知识，遵循规则',
 '打破传统，叛逆精神，非传统方法，缺乏指导，信仰危机'),

('恋人', 'The Lovers', 'major', 6, 'air', '爱情、选择、和谐',
 '爱情关系，重要选择，价值观一致，和谐关系，情感连接',
 '关系失衡，错误选择，价值观冲突，不和谐，感情受挫'),

('战车', 'The Chariot', 'major', 7, 'water', '意志、胜利、控制',
 '坚定意志，克服挑战，取得胜利，自我控制，前进动力',
 '失控，方向不明，缺乏纪律，失败，意志力薄弱'),

('力量', 'Strength', 'major', 8, 'fire', '勇气、耐心、内在力量',
 '内在力量，温柔坚持，克服困难，耐心面对，以柔克刚',
 '软弱无力，缺乏耐心，暴力相向，信心不足，放弃'),

('隐士', 'The Hermit', 'major', 9, 'earth', '内省、独处、寻找',
 '独自探索，内在寻找，智慧之光，退隐思考，寻求真理',
 '孤独隔离，拒绝帮助，迷失方向，社交退缩，固步自封'),

('命运之轮', 'Wheel of Fortune', 'major', 10, 'fire', '变化、命运、周期',
 '命运转折，机会来临，周期变化，好运降临，顺应变化',
 '厄运降临，抗拒变化，坏运气，错失良机，停滞不前'),

('正义', 'Justice', 'major', 11, 'air', '公正、平衡、真理',
 '公正裁决，因果报应，寻求真理，平衡各方，理性判断',
 '不公正，偏见，逃避责任，失衡，错误判断'),

('倒吊人', 'The Hanged Man', 'major', 12, 'water', '牺牲、等待、新视角',
 '换个角度，暂停等待，牺牲奉献，新视野，顺其自然',
 '抗拒改变，无意义的牺牲，拖延，固执，错失良机'),

('死神', 'Death', 'major', 13, 'water', '结束、转变、新生',
 '结束阶段，重大转变，旧事物消亡，新的开始，必要改变',
 '抗拒结束，停滞不变，害怕改变，僵化，错失重生机会'),

('节制', 'Temperance', 'major', 14, 'fire', '平衡、节制、融合',
 '平衡和谐，适度节制，融合统一，耐心调和，中庸之道',
 '极端行为，失衡，过度放纵，缺乏节制，冲突加剧'),

('恶魔', 'The Devil', 'major', 15, 'earth', '束缚、诱惑、物质',
 '物质束缚，不良习惯，诱惑陷阱，依赖成瘾，受困于欲望',
 '摆脱束缚，重获自由，打破枷锁，拒绝诱惑，觉醒'),

('塔', 'The Tower', 'major', 16, 'fire', '突变、灾难、觉醒',
 '突然改变，打破旧有，真相揭露，危机转机，强制觉醒',
 '避免改变，灾难延迟，内心恐惧，抗拒真相，固执己见'),

('星星', 'The Star', 'major', 17, 'air', '希望、灵感、宁静',
 '希望之光，灵感涌现，内心宁静，信任未来，精神指引',
 '希望渺茫，失去信心，绝望，缺乏灵感，迷失方向'),

('月亮', 'The Moon', 'major', 18, 'water', '幻觉、恐惧、潜意识',
 '潜意识活跃，面对恐惧，不确定中前行，直觉增强，神秘力量',
 '恐惧消散，真相大白，走出迷雾，幻觉破灭，焦虑减轻'),

('太阳', 'The Sun', 'major', 19, 'fire', '成功、快乐、活力',
 '成功喜悦，充满活力，光明正大，自信满满，幸福时光',
 '暂时的阴霾，自信受挫，快乐短暂，过度自信，盲目乐观'),

('审判', 'Judgement', 'major', 20, 'fire', '重生、评价、召唤',
 '内心召唤，自我评价，重生觉醒，过去的总结，新的开始',
 '自我怀疑，拒绝召唤，逃避评价，错失机会，固步自封'),

('世界', 'The World', 'major', 21, 'earth', '完成、圆满、成就',
 '目标达成，圆满完整，旅程结束，成就荣耀，新的开始',
 '未完成的遗憾，缺乏 Closure，目标未达，延迟完成，完美主义');

-- =====================================================
-- 7. 插入FAQ数据
-- =====================================================

INSERT INTO `tc_faq` (`category`, `question`, `answer`, `sort`, `status`) VALUES
('general', '什么是八字？', '八字是中国传统命理学的重要组成部分，根据一个人出生的年、月、日、时四柱，每柱两个字，共八个字来推算命运。', 1, 1),
('general', '八字排盘准确吗？', '八字排盘是基于传统命理学的计算方法，具有一定的参考价值。但命运也受后天努力、环境等因素影响，仅供参考。', 2, 1),
('general', '需要提供哪些信息？', '进行八字排盘需要提供准确的出生年、月、日、时，以及出生地点（用于计算真太阳时）。', 3, 1),

('points', '如何获得积分？', '您可以通过以下方式获得积分：新用户注册赠送100积分、每日签到、邀请好友、完善个人资料、分享运势等。', 1, 1),
('points', '积分有什么用？', '积分可用于解锁详细的命理报告、流年运势分析、八字合婚、取名建议等高级功能。', 2, 1),
('points', '积分会过期吗？', '目前积分长期有效，不会过期。但请注意查看平台公告，如有调整会提前通知。', 3, 1),

('vip', 'VIP有什么特权？', 'VIP会员可享受：无限次排盘、所有报告免费解锁、积分充值折扣、专属客服、新功能优先体验等特权。', 1, 1),
('vip', 'VIP如何购买？', '您可以在个人中心的VIP页面选择合适的套餐进行购买，支持微信支付等多种支付方式。', 2, 1),
('vip', 'VIP到期后会怎样？', 'VIP到期后将恢复普通用户权限，已解锁的内容仍然可以查看，但新排盘将受相应限制。', 3, 1),

('function', '什么是流年运势？', '流年运势是根据您的八字，结合当年的天干地支，分析您在这一年各方面的运势走向。', 1, 1),
('function', '八字合婚准吗？', '八字合婚是基于双方八字的五行生克关系进行分析，可以作为参考，但婚姻幸福还需要双方共同经营。', 2, 1),
('function', '取名服务如何使用？', '提供姓氏、性别、出生时间等信息，系统会根据五行八字分析，为您推荐合适的名字建议。', 3, 1);

-- =====================================================
-- 8. 插入AI提示词数据
-- =====================================================

INSERT INTO `tc_ai_prompt` (`name`, `type`, `prompt`, `variables`, `status`) VALUES
('八字基础分析', 'bazi', 
'你是一位专业的命理大师。请根据以下八字信息进行分析：
年柱：{{year_pillar}}
月柱：{{month_pillar}}
日柱：{{day_pillar}}
时柱：{{hour_pillar}}
性别：{{gender}}

请从以下几个方面进行分析：
1. 五行分析（五行分布和强弱）
2. 十神分析（十神配置和特点）
3. 日主分析（日主强弱和特点）
4. 性格特征
5. 事业财运
6. 感情婚姻
7. 健康提示

请用通俗易懂的语言，给出详细且专业的分析。',
'["year_pillar","month_pillar","day_pillar","hour_pillar","gender"]',
1),

('流年运势分析', 'fortune',
'你是一位专业的命理大师。请根据以下信息分析流年运势：
八字：{{bazi}}
流年：{{year}} {{year_ganzi}}
大运：{{dayun}}

请分析以下内容：
1. 整体运势评分（1-10分）
2. 事业运势
3. 财运分析
4. 感情运势
5. 健康状况
6. 重要提示和建议
7. 吉凶月份预测

请给出专业且实用的建议。',
'["bazi","year","year_ganzi","dayun"]',
1),

('塔罗牌解读', 'tarot',
'你是一位专业的塔罗牌师。请解读以下塔罗牌：
牌名：{{card_name}}
位置：{{position}}
问题：{{question}}

请从以下角度解读：
1. 牌面基本含义
2. 在本情境中的具体含义
3. 给出的建议和指引
4. 需要注意的方面

请用温暖且专业的语言进行解读。',
'["card_name","position","question"]',
1);


-- Source: 20260317_create_admin_users_table.sql
SET NAMES utf8mb4;
USE taichu;

START TRANSACTION;

-- 管理员主表：兼容后台登录、管理员列表与 JWT 鉴权
CREATE TABLE IF NOT EXISTS `tc_admin` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(255) NOT NULL COMMENT '密码哈希',
    `nickname` VARCHAR(50) DEFAULT '' COMMENT '昵称',
    `email` VARCHAR(100) DEFAULT '' COMMENT '邮箱',
    `phone` VARCHAR(20) DEFAULT '' COMMENT '手机号',
    `avatar` VARCHAR(500) DEFAULT '' COMMENT '头像',
    `role_id` INT UNSIGNED DEFAULT 0 COMMENT '角色ID（兼容旧结构）',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间',
    `last_login_ip` VARCHAR(45) DEFAULT '' COMMENT '最后登录IP',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_username` (`username`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- 角色表：确保超级管理员权限链路可用
CREATE TABLE IF NOT EXISTS `tc_admin_role` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '角色标识',
    `description` VARCHAR(255) DEFAULT '' COMMENT '角色描述',
    `is_super` TINYINT DEFAULT 0 COMMENT '是否超级管理员',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色表';

-- 管理员角色关联表：确保权限服务能查到角色
CREATE TABLE IF NOT EXISTS `tc_admin_user_role` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_admin_role` (`admin_id`, `role_id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色关联表';

-- 默认超级管理员角色
INSERT INTO `tc_admin_role` (`name`, `code`, `description`, `is_super`)
VALUES ('超级管理员', 'super_admin', '拥有所有后台权限', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `is_super` = VALUES(`is_super`);

-- 默认管理员账号：admin / admin123
-- bcrypt 哈希通过 bcrypt-cli 生成，避免明文密码落库
INSERT INTO `tc_admin` (
    `username`,
    `password`,
    `nickname`,
    `email`,
    `phone`,
    `avatar`,
    `status`
)
VALUES (
    'admin',
    '$2a$10$69ekdLT1xe/Niyazb/kBSegJPAx0uJf6uhq5mz.LfZ.2rJ5YtUjoC',
    '系统管理员',
    'admin@example.com',
    '',
    '',
    1
)
ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`),
    `nickname` = VALUES(`nickname`),
    `email` = VALUES(`email`),
    `status` = VALUES(`status`),
    `updated_at` = CURRENT_TIMESTAMP;

-- 为默认管理员绑定超级管理员角色
INSERT IGNORE INTO `tc_admin_user_role` (`admin_id`, `role_id`, `created_at`)
SELECT a.`id`, r.`id`, NOW()
FROM `tc_admin` a
INNER JOIN `tc_admin_role` r ON r.`code` = 'super_admin'
WHERE a.`username` = 'admin';

-- 兼容旧结构中的 role_id 字段
UPDATE `tc_admin` a
INNER JOIN `tc_admin_role` r ON r.`code` = 'super_admin'
SET a.`role_id` = r.`id`
WHERE a.`username` = 'admin' AND (a.`role_id` = 0 OR a.`role_id` IS NULL);

COMMIT;


-- Source: 20260318_fix_admin_role_permissions.sql
SET NAMES utf8mb4;
USE taichu;

INSERT INTO `tc_admin_permission` (`name`, `code`, `module`, `description`)
VALUES
    ('黄历查看', 'almanac_view', 'content', '查看黄历数据'),
    ('黄历编辑', 'almanac_edit', 'content', '编辑黄历数据')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `module` = VALUES(`module`),
    `description` = VALUES(`description`);

INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `tc_admin_role` r
JOIN `tc_admin_permission` p ON p.code IN ('points_adjust', 'content_manage', 'almanac_view', 'almanac_edit')
WHERE r.code = 'operator'
ON DUPLICATE KEY UPDATE
    `permission_id` = VALUES(`permission_id`);


-- Source: 20260317_create_anticheat_tables.sql
SET NAMES utf8mb4;
USE taichu;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_event` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '事件类型: login_anomaly/ip_frequency/device_risk',
    `level` VARCHAR(20) DEFAULT 'medium' COMMENT '风险等级: low/medium/high/critical',
    `detail` JSON NULL COMMENT '详细信息',
    `ip` VARCHAR(45) DEFAULT '' COMMENT 'IP地址',
    `device_id` VARCHAR(100) DEFAULT '' COMMENT '设备ID',
    `status` TINYINT DEFAULT 0 COMMENT '处理状态: 0待处理 1已确认 2已忽略',
    `handler_id` INT UNSIGNED DEFAULT 0 COMMENT '处理人ID',
    `handle_remark` VARCHAR(255) DEFAULT '' COMMENT '处理备注',
    `handle_at` DATETIME NULL COMMENT '处理时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反作弊风险事件表';

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_rule` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '规则名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '规则代码',
    `type` VARCHAR(50) NOT NULL COMMENT '规则类型',
    `config` JSON NULL COMMENT '规则配置',
    `action` VARCHAR(50) DEFAULT 'log' COMMENT '触发动作: log/block/captcha',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反作弊规则表';

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_device` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `device_id` VARCHAR(100) NOT NULL UNIQUE COMMENT '设备唯一标识',
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '关联最后用户ID',
    `platform` VARCHAR(20) DEFAULT '' COMMENT '平台: ios/android/web',
    `info` JSON NULL COMMENT '设备硬件信息',
    `is_blocked` TINYINT DEFAULT 0 COMMENT '是否黑名单: 0否 1是',
    `block_reason` VARCHAR(255) DEFAULT '' COMMENT '拉黑原因',
    `last_active_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_blocked` (`is_blocked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备指纹表';

INSERT INTO `tc_anti_cheat_rule` (`name`, `code`, `type`, `config`, `action`, `status`)
VALUES
    ('IP访问限频', 'ip_rate_default', 'ip_rate', JSON_OBJECT('threshold', 60, 'window_minutes', 1), 'block', 1),
    ('单设备注册限制', 'device_rate_default', 'device_rate', JSON_OBJECT('threshold', 3, 'window_minutes', 1), 'block', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `type` = VALUES(`type`),
    `config` = VALUES(`config`),
    `action` = VALUES(`action`),
    `status` = VALUES(`status`),
    `updated_at` = CURRENT_TIMESTAMP;

COMMIT;


-- Source: 20260317_create_knowledge_tables.sql
-- 知识库/文章管理表结构
-- 创建时间：2026-03-17

USE taichu;

CREATE TABLE IF NOT EXISTS `tc_article_category` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name` VARCHAR(100) NOT NULL COMMENT '分类名称',
    `slug` VARCHAR(120) NOT NULL COMMENT '分类标识',
    `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '分类描述',
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父分类ID',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序值，越小越靠前',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_article_category_slug` (`slug`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台知识库文章分类表';

CREATE TABLE IF NOT EXISTS `tc_article` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `category_id` INT UNSIGNED NOT NULL COMMENT '分类ID',
    `title` VARCHAR(200) NOT NULL COMMENT '文章标题',
    `slug` VARCHAR(160) NOT NULL COMMENT '文章标识',
    `summary` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '文章摘要',
    `content` LONGTEXT NOT NULL COMMENT '文章正文',
    `thumbnail` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '封面图地址',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0草稿 1发布 2定时发布 3归档',
    `is_hot` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否热门',
    `author_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '作者管理员ID',
    `author_name` VARCHAR(80) NOT NULL DEFAULT '' COMMENT '作者名称',
    `published_at` DATETIME NULL DEFAULT NULL COMMENT '发布时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_article_slug` (`slug`),
    KEY `idx_category_status` (`category_id`, `status`),
    KEY `idx_is_hot` (`is_hot`),
    KEY `idx_published_at` (`published_at`),
    FULLTEXT KEY `ft_title_summary_content` (`title`, `summary`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台知识库文章表';

-- 默认分类种子数据
INSERT INTO `tc_article_category` (`name`, `slug`, `description`, `parent_id`, `sort_order`, `status`)
VALUES
    ('八字基础', 'bazi-basic', '八字入门、排盘基础、十神速查等基础内容', 0, 10, 1),
    ('十神格局', 'shishen-geju', '十神、格局、旺衰与喜忌专题', 0, 20, 1),
    ('大运流年', 'dayun-liunian', '大运、流年、流月专题文章', 0, 30, 1),
    ('塔罗体系', 'tarot-system', '塔罗牌义、牌阵与占卜指引', 0, 40, 1),
    ('风水择吉', 'fengshui-zeji', '风水布局、择吉黄历与应用文章', 0, 50, 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `parent_id` = VALUES(`parent_id`),
    `sort_order` = VALUES(`sort_order`),
    `status` = VALUES(`status`);


-- Source: 20260317_create_notification_tables.sql
-- 推送通知相关表结构
-- 创建时间：2026-03-17

USE taichu;

CREATE TABLE IF NOT EXISTS `tc_notification` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '通知类型',
    `title` VARCHAR(200) NOT NULL COMMENT '通知标题',
    `content` TEXT NULL COMMENT '通知内容',
    `data` JSON NULL COMMENT '附加数据',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已读',
    `read_at` DATETIME NULL DEFAULT NULL COMMENT '已读时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_created_at` (`user_id`, `created_at`),
    KEY `idx_user_is_read` (`user_id`, `is_read`),
    KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='站内通知表';

CREATE TABLE IF NOT EXISTS `tc_notification_setting` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `daily_fortune` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '每日运势通知开关',
    `system_notice` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '系统公告通知开关',
    `activity` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '活动通知开关',
    `recharge` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '充值通知开关',
    `points_change` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '积分变动通知开关',
    `push_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用设备推送',
    `sound_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用声音提醒',
    `vibration_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用震动提醒',
    `quiet_hours_start` CHAR(5) NOT NULL DEFAULT '22:00' COMMENT '免打扰开始时间',
    `quiet_hours_end` CHAR(5) NOT NULL DEFAULT '08:00' COMMENT '免打扰结束时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_notification_setting_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户通知设置表';

CREATE TABLE IF NOT EXISTS `tc_push_device` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `platform` VARCHAR(20) NOT NULL COMMENT '平台 ios/android/web',
    `device_token` VARCHAR(500) NOT NULL COMMENT '推送令牌',
    `device_id` VARCHAR(255) NOT NULL COMMENT '设备唯一标识',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否激活',
    `last_active_at` DATETIME NULL DEFAULT NULL COMMENT '最近活跃时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_push_device_device_id` (`device_id`),
    KEY `idx_user_platform` (`user_id`, `platform`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户推送设备表';

-- 为已有用户补齐默认通知设置，避免首次打开接口时为空
INSERT INTO `tc_notification_setting` (
    `user_id`,
    `daily_fortune`,
    `system_notice`,
    `activity`,
    `recharge`,
    `points_change`,
    `push_enabled`,
    `sound_enabled`,
    `vibration_enabled`,
    `quiet_hours_start`,
    `quiet_hours_end`
)
SELECT
    u.id,
    1,
    1,
    1,
    1,
    1,
    1,
    1,
    1,
    '22:00',
    '08:00'
FROM `tc_user` u
LEFT JOIN `tc_notification_setting` ns ON ns.user_id = u.id
WHERE ns.user_id IS NULL;


-- Source: 20260317_create_shensha_table.sql
SET NAMES utf8mb4;

USE taichu;

-- 神煞主表：供后台内容管理 / 神煞管理使用
CREATE TABLE IF NOT EXISTS `tc_shensha` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '神煞名称',
    `type` VARCHAR(20) NOT NULL COMMENT '类型: daji大吉 ji吉 ping平 xiong凶 daxiong大凶',
    `category` VARCHAR(30) NOT NULL COMMENT '分类: guiren贵人 xueye学业 ganqing感情 jiankang健康 caiyun财运 qita其他',
    `description` VARCHAR(500) NOT NULL COMMENT '含义说明',
    `effect` VARCHAR(500) DEFAULT NULL COMMENT '影响描述',
    `check_rule` TEXT NOT NULL COMMENT '查法规则说明',
    `check_code` TEXT DEFAULT NULL COMMENT '查法实现代码',
    `gan_rules` JSON DEFAULT NULL COMMENT '天干查法规则',
    `zhi_rules` JSON DEFAULT NULL COMMENT '地支查法规则',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `status` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_name_category` (`name`, `category`),
    INDEX `idx_type` (`type`),
    INDEX `idx_category` (`category`),
    INDEX `idx_status` (`status`),
    INDEX `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='神煞表';

-- 默认神煞种子，保证 fresh setup 后后台神煞管理可直接使用
INSERT INTO `tc_shensha` (`name`, `type`, `category`, `description`, `effect`, `check_rule`, `gan_rules`, `zhi_rules`, `sort`, `status`) VALUES
('天乙贵人', 'daji', 'guiren', '最吉之神，命中逢之，遇事有人帮，遇危难有人救', '遇难成祥，逢凶化吉，人缘极佳，易得他人帮助', '甲戊见牛羊，乙己鼠猴乡，丙丁猪鸡位，壬癸兔蛇藏，庚辛逢虎马，此是贵人方', '{"甲":["丑","未"],"戊":["丑","未"],"乙":["子","未"],"己":["子","未"],"丙":["亥","酉"],"丁":["亥","酉"],"壬":["卯","巳"],"癸":["卯","巳"],"庚":["午","寅"],"辛":["午","寅"]}', NULL, 1, 1),
('文昌贵人', 'ji', 'xueye', '主聪明好学，利文途考学', '聪明过人，学业有成，考试顺利，利于文职', '甲乙巳午报君知，丙戊申宫丁己鸡，庚猪辛鼠壬逢虎，癸人见卯入云梯', '{"甲":["巳","午"],"乙":["巳","午"],"丙":["申"],"戊":["申"],"丁":["酉"],"己":["酉"],"庚":["亥"],"辛":["子"],"壬":["寅"],"癸":["卯"]}', NULL, 2, 1),
('太极贵人', 'ji', 'guiren', '主人聪明好学，喜神秘文化', '悟性高，对命理、宗教、玄学有兴趣，逢凶化吉', '甲乙生人子午中，丙丁鸡兔定亨通，戊己两干临四季，庚辛寅亥禄丰隆，壬癸巳申偏喜美', '{"甲":["子","午"],"乙":["子","午"],"丙":["酉","卯"],"丁":["酉","卯"],"戊":["辰","戌","丑","未"],"己":["辰","戌","丑","未"],"庚":["寅","亥"],"辛":["寅","亥"],"壬":["巳","申"],"癸":["巳","申"]}', NULL, 3, 1),
('天德贵人', 'daji', 'guiren', '天地德秀之气，逢凶化吉之神', '一生安逸，不犯刑律，不遇凶祸，福气好', '正丁二坤宫，三壬四辛同，五乾六甲上，七癸八艮逢，九丙十居乙，子巽丑庚中', NULL, '{"子":["巳"],"丑":["庚"],"寅":["丁"],"卯":["申"],"辰":["壬"],"巳":["辛"],"午":["甲"],"未":["癸"],"申":["寅"],"酉":["丙"],"戌":["乙"],"亥":["巳"]}', 4, 1),
('月德贵人', 'daji', 'guiren', '乃太阴之德，功能与天德略同而稍逊', '逢凶化吉，灾少福多，一生少病痛', '寅午戌月在丙，申子辰月在壬，亥卯未月在甲，巳酉丑月在庚', NULL, '{"寅":["丙"],"午":["丙"],"戌":["丙"],"申":["壬"],"子":["壬"],"辰":["壬"],"亥":["甲"],"卯":["甲"],"未":["甲"],"巳":["庚"],"酉":["庚"],"丑":["庚"]}', 5, 1),
('福星贵人', 'ji', 'guiren', '主人一生福禄无缺，格局配合得当，必然多福多寿', '一生福禄无缺，享福深厚，平安幸福', '甲丙相邀入虎乡，更游鼠穴最高强，戊猴己未丁宜亥，乙癸逢牛卯禄昌，庚赶马头辛到巳，壬骑龙背喜非常', '{"甲":["寅","子"],"丙":["寅","子"],"戊":["申"],"己":["未"],"丁":["亥"],"乙":["丑","卯"],"癸":["丑","卯"],"庚":["午"],"辛":["巳"],"壬":["辰"]}', NULL, 6, 1),
('桃花', 'ping', 'ganqing', '主人漂亮多情，风流潇洒', '人缘好，异性缘佳，感情丰富，但可能感情复杂', '申子辰在酉，巳酉丑在午，亥卯未在子，寅午戌在卯', NULL, '{"申":["酉"],"子":["酉"],"辰":["酉"],"巳":["午"],"酉":["午"],"丑":["午"],"亥":["子"],"卯":["子"],"未":["子"],"寅":["卯"],"午":["卯"],"戌":["卯"]}', 7, 1),
('羊刃', 'xiong', 'jiankang', '司刑之星，性情刚强', '性格刚烈，易有刀伤手术，但也代表能力强', '甲刃在卯，乙刃在寅，丙戊刃在午，丁己刃在巳，庚刃在酉，辛刃在申，壬刃在子，癸刃在亥', '{"甲":["卯"],"乙":["寅"],"丙":["午"],"戊":["午"],"丁":["巳"],"己":["巳"],"庚":["酉"],"辛":["申"],"壬":["子"],"癸":["亥"]}', NULL, 8, 1),
('劫煞', 'xiong', 'caiyun', '主破财、阻碍', '破财、阻碍、是非、意外', '申子辰见巳，亥卯未见申，寅午戌见亥，巳酉丑见寅', NULL, '{"申":["巳"],"子":["巳"],"辰":["巳"],"亥":["申"],"卯":["申"],"未":["申"],"寅":["亥"],"午":["亥"],"戌":["亥"],"巳":["寅"],"酉":["寅"],"丑":["寅"]}', 9, 1),
('孤辰', 'xiong', 'ganqing', '主孤独，不利婚姻', '孤独，少依靠，婚姻不顺，与亲人缘薄', '亥子丑人，见寅为孤辰，见戌为寡宿', NULL, '{"亥":["寅"],"子":["寅"],"丑":["寅"]}', 10, 1),
('寡宿', 'xiong', 'ganqing', '主孤独，不利婚姻', '孤独，婚姻不顺，女命尤其注意', '亥子丑人，见戌为寡宿，见寅为孤辰', NULL, '{"亥":["戌"],"子":["戌"],"丑":["戌"]}', 11, 1),
('阴差阳错', 'xiong', 'ganqing', '主婚姻不顺', '婚姻不利，夫妻不和，男克妻女克夫', '丙子、丁丑、戊寅、辛卯、壬辰、癸巳、丙午、丁未、戊申、辛酉、壬戌、癸亥', NULL, NULL, 12, 1),
('十恶大败', 'xiong', 'caiyun', '主不善理财，花钱大手大脚', '不善理财，花钱如流水，难以积蓄', '甲辰、乙巳、丙申、丁亥、戊戌、己丑、庚辰、辛巳、壬申、癸亥', NULL, NULL, 13, 1),
('金舆', 'ji', 'caiyun', '主富贵，聪明富贵', '聪明富贵，性柔貌愿，举止温和', '甲龙乙蛇丙戊羊，丁己猴歌庚犬方，辛猪壬牛癸逢虎', '{"甲":["辰"],"乙":["巳"],"丙":["未"],"戊":["未"],"丁":["申"],"己":["申"],"庚":["戌"],"辛":["亥"],"壬":["丑"],"癸":["寅"]}', NULL, 14, 1),
('华盖', 'ping', 'guiren', '主聪明好学，喜艺术、宗教', '聪明好学，喜艺术、玄学、宗教，有出世思想', '寅午戌见戌，巳酉丑见丑，申子辰见辰，亥卯未见未', NULL, '{"寅":["戌"],"午":["戌"],"戌":["戌"],"巳":["丑"],"酉":["丑"],"丑":["丑"],"申":["辰"],"子":["辰"],"辰":["辰"],"亥":["未"],"卯":["未"],"未":["未"]}', 15, 1)
ON DUPLICATE KEY UPDATE
    `type` = VALUES(`type`),
    `category` = VALUES(`category`),
    `description` = VALUES(`description`),
    `effect` = VALUES(`effect`),
    `check_rule` = VALUES(`check_rule`),
    `check_code` = VALUES(`check_code`),
    `gan_rules` = VALUES(`gan_rules`),
    `zhi_rules` = VALUES(`zhi_rules`),
    `sort` = VALUES(`sort`),
    `status` = VALUES(`status`),
    `updated_at` = CURRENT_TIMESTAMP;


-- Source: 20260317_create_admin_stats_tables.sql
SET NAMES utf8mb4;
USE taichu;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `site_daily_stats` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `stat_date` DATE NOT NULL COMMENT '统计日期',
    `new_users` INT NOT NULL DEFAULT 0 COMMENT '新增用户数',
    `active_users` INT NOT NULL DEFAULT 0 COMMENT '活跃用户数',
    `total_users` INT NOT NULL DEFAULT 0 COMMENT '累计用户数',
    `points_given` INT NOT NULL DEFAULT 0 COMMENT '发放积分总数',
    `points_consumed` INT NOT NULL DEFAULT 0 COMMENT '消耗积分总数',
    `points_balance` INT NOT NULL DEFAULT 0 COMMENT '用户积分余额总和',
    `bazi_count` INT NOT NULL DEFAULT 0 COMMENT '八字排盘次数',
    `tarot_count` INT NOT NULL DEFAULT 0 COMMENT '塔罗占卜次数',
    `liuyao_count` INT NOT NULL DEFAULT 0 COMMENT '六爻占卜次数',
    `hehun_count` INT NOT NULL DEFAULT 0 COMMENT '合婚次数',
    `daily_fortune_count` INT NOT NULL DEFAULT 0 COMMENT '每日运势查看次数',
    `order_count` INT NOT NULL DEFAULT 0 COMMENT '订单数',
    `order_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
    `paid_count` INT NOT NULL DEFAULT 0 COMMENT '支付成功订单数',
    `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际支付金额',
    `refund_count` INT NOT NULL DEFAULT 0 COMMENT '退款订单数',
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
    `pv_count` INT NOT NULL DEFAULT 0 COMMENT '页面浏览量',
    `uv_count` INT NOT NULL DEFAULT 0 COMMENT '独立访客数',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_stat_date` (`stat_date`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='网站每日统计表';

COMMIT;


-- Source: 20260318_create_almanac_table.sql
SET NAMES utf8mb4;
USE taichu;

CREATE TABLE IF NOT EXISTS `tc_almanac` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `solar_date` DATE NOT NULL COMMENT '公历日期',
    `lunar_date` VARCHAR(50) DEFAULT '' COMMENT '农历日期',
    `lunar_year` VARCHAR(20) DEFAULT '' COMMENT '农历年',
    `lunar_month` TINYINT DEFAULT NULL COMMENT '农历月',
    `lunar_day` TINYINT DEFAULT NULL COMMENT '农历日',
    `ganzhi_year` VARCHAR(10) DEFAULT '' COMMENT '年柱干支',
    `ganzhi_month` VARCHAR(10) DEFAULT '' COMMENT '月柱干支',
    `ganzhi_day` VARCHAR(10) DEFAULT '' COMMENT '日柱干支',
    `ganzhi` VARCHAR(10) DEFAULT '' COMMENT '兼容后台展示的干支字段',
    `nayin` VARCHAR(50) DEFAULT '' COMMENT '纳音',
    `wuxing` VARCHAR(50) DEFAULT '' COMMENT '五行',
    `constellation` VARCHAR(20) DEFAULT '' COMMENT '星座',
    `xingsu` VARCHAR(20) DEFAULT '' COMMENT '星宿',
    `pengzu` VARCHAR(100) DEFAULT '' COMMENT '彭祖百忌',
    `yi` JSON NULL COMMENT '宜事项',
    `ji` JSON NULL COMMENT '忌事项',
    `chong` VARCHAR(10) DEFAULT '' COMMENT '冲',
    `sha` VARCHAR(10) DEFAULT '' COMMENT '煞方',
    `chong_desc` VARCHAR(200) DEFAULT '' COMMENT '冲煞说明',
    `zhiri` VARCHAR(10) DEFAULT '' COMMENT '十二值日',
    `xiu` VARCHAR(20) DEFAULT '' COMMENT '二十八宿',
    `taishen` VARCHAR(100) DEFAULT '' COMMENT '胎神占方',
    `jishen` JSON NULL COMMENT '吉神',
    `xiongsha` JSON NULL COMMENT '凶煞',
    `shichen` JSON NULL COMMENT '时辰吉凶',
    `shengxiao_teji` VARCHAR(50) DEFAULT '' COMMENT '特吉生肖',
    `shengxiao_ciji` VARCHAR(50) DEFAULT '' COMMENT '次吉生肖',
    `shengxiao_daidai` VARCHAR(50) DEFAULT '' COMMENT '带衰生肖',
    `fangwei_xishen` VARCHAR(20) DEFAULT '' COMMENT '喜神方位',
    `fangwei_caishen` VARCHAR(20) DEFAULT '' COMMENT '财神方位',
    `fangwei_fushen` VARCHAR(20) DEFAULT '' COMMENT '福神方位',
    `fangwei_yanggui` VARCHAR(20) DEFAULT '' COMMENT '阳贵神方位',
    `fangwei_yingui` VARCHAR(20) DEFAULT '' COMMENT '阴贵神方位',
    `is_jieqi` TINYINT DEFAULT 0 COMMENT '是否节气日',
    `jieqi_name` VARCHAR(20) DEFAULT '' COMMENT '节气名称',
    `status` TINYINT DEFAULT 1 COMMENT '状态',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_solar_date` (`solar_date`),
    KEY `idx_status` (`status`),
    KEY `idx_jieqi` (`is_jieqi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='黄历数据表';


-- Source: 20260318_create_seo_tables.sql
SET NAMES utf8mb4;
USE taichu;

CREATE TABLE IF NOT EXISTS `tc_seo_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `route` VARCHAR(255) NOT NULL COMMENT '页面路由',
    `title` VARCHAR(255) NOT NULL COMMENT '页面标题',
    `description` VARCHAR(500) NOT NULL COMMENT '页面描述',
    `keywords` JSON NOT NULL COMMENT '关键词数组',
    `image` VARCHAR(500) DEFAULT '' COMMENT '分享图片URL',
    `robots` VARCHAR(50) DEFAULT 'index,follow' COMMENT 'robots指令',
    `og_type` VARCHAR(50) DEFAULT 'website' COMMENT 'Open Graph类型',
    `canonical` VARCHAR(500) DEFAULT '' COMMENT '规范链接',
    `priority` DECIMAL(2,1) DEFAULT 0.5 COMMENT '站点地图优先级',
    `changefreq` VARCHAR(20) DEFAULT 'weekly' COMMENT '更新频率',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_route` (`route`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面SEO配置表';

CREATE TABLE IF NOT EXISTS `tc_seo_keywords` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `keyword` VARCHAR(255) NOT NULL COMMENT '关键词',
    `category` VARCHAR(50) DEFAULT 'general' COMMENT '分类',
    `baidu_rank` INT DEFAULT 0 COMMENT '百度排名',
    `bing_rank` INT DEFAULT 0 COMMENT '必应排名',
    `360_rank` INT DEFAULT 0 COMMENT '360排名',
    `sogou_rank` INT DEFAULT 0 COMMENT '搜狗排名',
    `search_volume` INT DEFAULT 0 COMMENT '月搜索量',
    `competition` VARCHAR(20) DEFAULT 'medium' COMMENT '竞争程度',
    `is_target` TINYINT DEFAULT 1 COMMENT '是否目标关键词',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_keyword` (`keyword`),
    KEY `idx_category` (`category`),
    KEY `idx_target` (`is_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO关键词表';

CREATE TABLE IF NOT EXISTS `tc_seo_indexed_pages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `url` VARCHAR(500) NOT NULL COMMENT '页面URL',
    `page_route` VARCHAR(255) DEFAULT '' COMMENT '页面路由',
    `title` VARCHAR(255) DEFAULT '' COMMENT '页面标题',
    `baidu_status` VARCHAR(20) DEFAULT 'pending' COMMENT '百度收录状态',
    `bing_status` VARCHAR(20) DEFAULT 'pending' COMMENT '必应收录状态',
    `baidu_last_crawl` DATETIME DEFAULT NULL COMMENT '百度最后抓取时间',
    `bing_last_crawl` DATETIME DEFAULT NULL COMMENT '必应最后抓取时间',
    `organic_traffic` INT DEFAULT 0 COMMENT '自然搜索流量',
    `indexed_at` DATETIME DEFAULT NULL COMMENT '收录时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_url` (`url`),
    KEY `idx_baidu_status` (`baidu_status`),
    KEY `idx_bing_status` (`bing_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面收录状态表';

CREATE TABLE IF NOT EXISTS `tc_seo_submissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `engine` VARCHAR(50) NOT NULL COMMENT '搜索引擎',
    `type` VARCHAR(50) NOT NULL COMMENT '提交类型',
    `url` VARCHAR(500) NOT NULL COMMENT '提交URL',
    `status` VARCHAR(20) DEFAULT 'pending' COMMENT '提交状态',
    `response` TEXT NULL COMMENT '返回结果',
    `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,
    KEY `idx_engine` (`engine`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='搜索引擎提交记录表';

CREATE TABLE IF NOT EXISTS `tc_seo_traffic_daily` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `stat_date` DATE NOT NULL COMMENT '统计日期',
    `engine` VARCHAR(50) NOT NULL COMMENT '搜索引擎',
    `impressions` INT DEFAULT 0 COMMENT '展现量',
    `clicks` INT DEFAULT 0 COMMENT '点击量',
    `ctr` DECIMAL(5,2) DEFAULT 0 COMMENT '点击率',
    `avg_position` DECIMAL(4,1) DEFAULT 0 COMMENT '平均排名',
    `organic_sessions` INT DEFAULT 0 COMMENT '自然搜索会话数',
    `organic_users` INT DEFAULT 0 COMMENT '自然搜索用户数',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_date_engine` (`stat_date`, `engine`),
    KEY `idx_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO流量统计表';

CREATE TABLE IF NOT EXISTS `tc_seo_robots` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_agent` VARCHAR(255) NOT NULL COMMENT 'User-agent',
    `rules` JSON NOT NULL COMMENT '规则数组',
    `crawl_delay` INT DEFAULT 0 COMMENT '抓取延迟',
    `sitemap_url` VARCHAR(500) DEFAULT '' COMMENT '站点地图地址',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否启用',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='robots配置表';

INSERT INTO `tc_seo_config` (`route`, `title`, `description`, `keywords`, `image`, `priority`, `changefreq`, `is_active`)
VALUES
('/', '太初命理 - 专业八字排盘_塔罗占卜_每日运势', '太初命理是专业的AI智能命理分析平台，提供八字排盘、塔罗占卜、每日运势等服务。', JSON_ARRAY('八字排盘', '塔罗占卜', '每日运势', '命理分析'), '/images/og-home.jpg', 1.0, 'daily', 1),
('/bazi', '免费八字排盘_在线生辰八字测算', '免费在线八字排盘工具，输入出生日期即可生成专业八字命盘。', JSON_ARRAY('八字排盘', '免费八字', '生辰八字'), '/images/og-bazi.jpg', 0.9, 'weekly', 1),
('/tarot', '免费塔罗牌占卜_在线塔罗测试', '免费在线塔罗牌占卜，涵盖爱情、事业、财运、运势等多个维度。', JSON_ARRAY('塔罗占卜', '塔罗牌', '免费塔罗'), '/images/og-tarot.jpg', 0.9, 'weekly', 1),
('/daily', '今日运势查询_每日星座运势_黄历宜忌', '查看今日运势，包含十二星座每日运势、黄历宜忌、时辰吉凶。', JSON_ARRAY('今日运势', '每日运势', '黄历查询'), '/images/og-daily.jpg', 0.9, 'daily', 1)
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `keywords` = VALUES(`keywords`),
    `image` = VALUES(`image`),
    `priority` = VALUES(`priority`),
    `changefreq` = VALUES(`changefreq`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

INSERT INTO `tc_seo_robots` (`user_agent`, `rules`, `crawl_delay`, `sitemap_url`, `is_active`, `sort_order`)
SELECT '*', JSON_ARRAY(JSON_OBJECT('type', 'allow', 'path', '/'), JSON_OBJECT('type', 'disallow', 'path', '/admin/'), JSON_OBJECT('type', 'disallow', 'path', '/api/')), 1, 'https://taichu.chat/sitemap.xml', 1, 0
WHERE NOT EXISTS (SELECT 1 FROM `tc_seo_robots` LIMIT 1);


-- Source: 20260317_add_points_record_compat_fields.sql
-- 积分记录兼容字段补齐
-- 创建时间：2026-03-17

USE taichu;

ALTER TABLE `tc_points_record`
    ADD COLUMN IF NOT EXISTS `action` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '操作名称' AFTER `user_id`,
    ADD COLUMN IF NOT EXISTS `points` INT NOT NULL DEFAULT 0 COMMENT '有符号积分变动值' AFTER `action`,
    ADD COLUMN IF NOT EXISTS `related_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联ID' AFTER `type`,
    ADD COLUMN IF NOT EXISTS `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '简要说明' AFTER `reason`;

UPDATE `tc_points_record`
SET
    `action` = CASE
        WHEN `action` = '' THEN COALESCE(NULLIF(`reason`, ''), '系统积分变动')
        ELSE `action`
    END,
    `points` = CASE
        WHEN `points` = 0 THEN CASE
            WHEN `type` IN ('reduce', 'consume', 'deduct', 'expense') THEN -ABS(COALESCE(`amount`, 0))
            ELSE ABS(COALESCE(`amount`, 0))
        END
        ELSE `points`
    END,
    `related_id` = CASE
        WHEN `related_id` = 0 THEN COALESCE(`source_id`, 0)
        ELSE `related_id`
    END,
    `description` = CASE
        WHEN `description` = '' THEN COALESCE(NULLIF(`reason`, ''), '系统积分变动')
        ELSE `description`
    END;


-- Source: 20260318_add_points_record_audit_fields.sql
SET NAMES utf8mb4;
USE taichu;

SET @has_amount := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tc_points_record'
    AND COLUMN_NAME = 'amount'
);
SET @sql_amount := IF(
  @has_amount = 0,
  "ALTER TABLE `tc_points_record` ADD COLUMN `amount` INT NOT NULL DEFAULT 0 COMMENT '变动数量（绝对值）' AFTER `points`",
  'SELECT 1'
);
PREPARE stmt_amount FROM @sql_amount;
EXECUTE stmt_amount;
DEALLOCATE PREPARE stmt_amount;

SET @has_balance := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tc_points_record'
    AND COLUMN_NAME = 'balance'
);
SET @sql_balance := IF(
  @has_balance = 0,
  "ALTER TABLE `tc_points_record` ADD COLUMN `balance` INT NULL DEFAULT NULL COMMENT '变动后余额' AFTER `amount`",
  'SELECT 1'
);
PREPARE stmt_balance FROM @sql_balance;
EXECUTE stmt_balance;
DEALLOCATE PREPARE stmt_balance;

SET @has_reason := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tc_points_record'
    AND COLUMN_NAME = 'reason'
);
SET @sql_reason := IF(
  @has_reason = 0,
  "ALTER TABLE `tc_points_record` ADD COLUMN `reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '变动原因' AFTER `remark`",
  'SELECT 1'
);
PREPARE stmt_reason FROM @sql_reason;
EXECUTE stmt_reason;
DEALLOCATE PREPARE stmt_reason;

UPDATE `tc_points_record`
SET `amount` = ABS(COALESCE(`points`, 0))
WHERE (`amount` IS NULL OR `amount` = 0) AND `points` IS NOT NULL;

UPDATE `tc_points_record`
SET `reason` = COALESCE(
  NULLIF(`reason`, ''),
  NULLIF(`remark`, ''),
  NULLIF(`action`, ''),
  '积分变动'
)
WHERE `reason` IS NULL OR `reason` = '';


-- Source: 20260318_add_recharge_order_refund_fields.sql
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


-- Source: 20260318_unify_table_names.sql
-- =============================================================
-- 太初命理网站 - 表名统一迁移脚本
-- 创建时间：2026-03-18
-- 用途：将不一致的表名改为统一的 tc_ 前缀格式
--       这样方便模型配置与数据库表名保持一致
-- =============================================================

SET NAMES utf8mb4;
USE taichu;

-- 检查表是否存在，然后重命名
-- 方法：通过存储过程避免"unknown table"错误

-- 1. 重命名 sms_codes 为 tc_sms_code
SET @check_sms_codes := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_codes'
);
SET @check_tc_sms_code := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_sms_code'
);

SET @sql_sms_codes := IF(
    @check_sms_codes > 0 AND @check_tc_sms_code = 0,
    "ALTER TABLE `sms_codes` RENAME TO `tc_sms_code`",
    "SELECT '表 sms_codes 不存在或 tc_sms_code 已存在，跳过' AS info"
);

PREPARE stmt_sms_codes FROM @sql_sms_codes;
EXECUTE stmt_sms_codes;
DEALLOCATE PREPARE stmt_sms_codes;

-- 1.1 修复 tc_sms_code 旧字段兼容（used / expired_at -> is_used / expire_time）
SET @has_sms_code_expire_time := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND COLUMN_NAME = 'expire_time'
);
SET @has_sms_code_is_used := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND COLUMN_NAME = 'is_used'
);
SET @has_sms_code_user_agent := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND COLUMN_NAME = 'user_agent'
);
SET @has_sms_code_expired_at := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND COLUMN_NAME = 'expired_at'
);
SET @has_sms_code_used := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND COLUMN_NAME = 'used'
);

SET @sql_add_sms_expire_time := IF(
    @has_sms_code_expire_time = 0,
    "ALTER TABLE `tc_sms_code` ADD COLUMN `expire_time` DATETIME NULL DEFAULT NULL COMMENT '过期时间' AFTER `type`",
    'SELECT 1'
);
PREPARE stmt_add_sms_expire_time FROM @sql_add_sms_expire_time;
EXECUTE stmt_add_sms_expire_time;
DEALLOCATE PREPARE stmt_add_sms_expire_time;

SET @sql_add_sms_is_used := IF(
    @has_sms_code_is_used = 0,
    "ALTER TABLE `tc_sms_code` ADD COLUMN `is_used` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已使用 0否 1是' AFTER `expire_time`",
    'SELECT 1'
);
PREPARE stmt_add_sms_is_used FROM @sql_add_sms_is_used;
EXECUTE stmt_add_sms_is_used;
DEALLOCATE PREPARE stmt_add_sms_is_used;

SET @sql_add_sms_user_agent := IF(
    @has_sms_code_user_agent = 0,
    "ALTER TABLE `tc_sms_code` ADD COLUMN `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'User-Agent' AFTER `ip`",
    'SELECT 1'
);
PREPARE stmt_add_sms_user_agent FROM @sql_add_sms_user_agent;
EXECUTE stmt_add_sms_user_agent;
DEALLOCATE PREPARE stmt_add_sms_user_agent;

SET @sql_backfill_sms_expire_time := IF(
    @has_sms_code_expired_at > 0,
    "UPDATE `tc_sms_code` SET `expire_time` = `expired_at` WHERE `expire_time` IS NULL AND `expired_at` IS NOT NULL",
    'SELECT 1'
);
PREPARE stmt_backfill_sms_expire_time FROM @sql_backfill_sms_expire_time;
EXECUTE stmt_backfill_sms_expire_time;
DEALLOCATE PREPARE stmt_backfill_sms_expire_time;

SET @sql_backfill_sms_is_used := IF(
    @has_sms_code_used > 0,
    "UPDATE `tc_sms_code` SET `is_used` = `used` WHERE `is_used` = 0 AND `used` <> 0",
    'SELECT 1'
);
PREPARE stmt_backfill_sms_is_used FROM @sql_backfill_sms_is_used;
EXECUTE stmt_backfill_sms_is_used;
DEALLOCATE PREPARE stmt_backfill_sms_is_used;

-- 2. 重命名 sms_configs 为 tc_sms_config
SET @check_sms_configs := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_configs'
);


SET @sql_sms_configs := IF(
    @check_sms_configs > 0,
    "ALTER TABLE `sms_configs` RENAME TO `tc_sms_config`",
    "SELECT '表 sms_configs 不存在，跳过' AS info"
);

PREPARE stmt_sms_configs FROM @sql_sms_configs;
EXECUTE stmt_sms_configs;
DEALLOCATE PREPARE stmt_sms_configs;

-- 3. 重命名 payment_configs 为 tc_payment_config
SET @check_payment_configs := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = 'taichu' AND TABLE_NAME = 'payment_configs'
);

SET @sql_payment_configs := IF(
    @check_payment_configs > 0,
    "ALTER TABLE `payment_configs` RENAME TO `tc_payment_config`",
    "SELECT '表 payment_configs 不存在，跳过' AS info"
);

PREPARE stmt_payment_configs FROM @sql_payment_configs;
EXECUTE stmt_payment_configs;
DEALLOCATE PREPARE stmt_payment_configs;

-- 验证结果
SELECT '表名统一迁移完成' AS message;
SELECT
    candidates.table_name,
    CASE WHEN actual.TABLE_NAME IS NULL THEN 'missing' ELSE 'exists' END AS status
FROM (
    SELECT 'tc_sms_code' AS table_name
    UNION ALL SELECT 'tc_sms_config'
    UNION ALL SELECT 'tc_payment_config'
) AS candidates
LEFT JOIN information_schema.TABLES AS actual
    ON actual.TABLE_SCHEMA = DATABASE()
   AND actual.TABLE_NAME = candidates.table_name;




-- Source: 20260318_create_missing_tables.sql
-- =============================================================
-- 太初命理网站 - 缺失表补充迁移脚本
-- 创建时间：2026-03-18
-- 用途：补齐代码中引用但数据库中尚未创建的所有表
--       支持重复执行（IF NOT EXISTS / ON DUPLICATE KEY UPDATE）
-- =============================================================

SET NAMES utf8mb4;
USE taichu;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================
-- 1. 积分历史记录表（points_history）
--    MD Report 4.1 中定义的必需表
-- =============================================================
CREATE TABLE IF NOT EXISTS `points_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL COMMENT '类型 add/reduce',
    `points` INT NOT NULL COMMENT '变动积分',
    `balance` INT NOT NULL COMMENT '变动后余额',
    `action` VARCHAR(100) NOT NULL COMMENT '动作说明',
    `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分历史记录表';

-- =============================================================
-- 2. 积分兑换记录表（points_exchange）
--    MD Report 4.2 中定义的必需表
-- =============================================================
CREATE TABLE IF NOT EXISTS `points_exchange` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `product_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
    `product_name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `points` INT NOT NULL COMMENT '消耗积分',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0待处理 1已完成 2已取消',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分兑换记录表';

-- =============================================================
-- 3. 签到记录表（checkin_record）
--    MD Report 4.4 中定义的必需表（不同于 tc_checkin_log）
-- =============================================================
CREATE TABLE IF NOT EXISTS `checkin_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '签到日期',
    `consecutive_days` INT NOT NULL DEFAULT 1 COMMENT '连续签到天数',
    `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_date` (`user_id`, `date`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到记录表';

-- =============================================================
-- 4. 合婚记录表（hehun_records）
--    模型：HehunRecord，兼容旧表名 tc_hehun_record
-- =============================================================
CREATE TABLE IF NOT EXISTS `hehun_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `male_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '男方姓名',
    `female_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '女方姓名',
    `male_birth_date` DATE NOT NULL COMMENT '男方出生日期',
    `male_birth_time` TIME NOT NULL DEFAULT '00:00:00' COMMENT '男方出生时间',
    `female_birth_date` DATE NOT NULL COMMENT '女方出生日期',
    `female_birth_time` TIME NOT NULL DEFAULT '00:00:00' COMMENT '女方出生时间',
    `male_bazi` JSON NULL COMMENT '男方八字JSON',
    `female_bazi` JSON NULL COMMENT '女方八字JSON',
    `bazi_match` JSON NULL COMMENT '八字合婚分析JSON（兼容旧字段）',
    `wuxing_match` JSON NULL COMMENT '五行匹配分析JSON',
    `result` JSON NULL COMMENT '完整合婚结果JSON',
    `analysis` TEXT NULL COMMENT '合婚文字分析（兼容旧结构）',
    `score` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '合婚评分 0-100',
    `level` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '评级: excellent/good/medium/fair/poor',
    `ai_analysis` JSON NULL COMMENT 'AI深度分析JSON',
    `is_ai_analysis` TINYINT NOT NULL DEFAULT 0 COMMENT '是否含AI分析 0否 1是',
    `points_cost` INT NOT NULL DEFAULT 0 COMMENT '消耗积分数（兼容字段名 points_used）',
    `is_premium` TINYINT NOT NULL DEFAULT 0 COMMENT '是否为完整付费报告',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='八字合婚记录表';

-- =============================================================
-- 2. 系统配置表（system_config）
--    模型：SystemConfig，兼容旧表名 tc_system_config
-- =============================================================
CREATE TABLE IF NOT EXISTS `system_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(100) NOT NULL COMMENT '配置键',
    `config_value` TEXT NOT NULL DEFAULT '' COMMENT '配置值',
    `config_type` VARCHAR(20) NOT NULL DEFAULT 'string' COMMENT '值类型: string/int/float/bool/json',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '配置说明',
    `category` VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT '分类',
    `is_editable` TINYINT NOT NULL DEFAULT 1 COMMENT '是否允许后台编辑 0否 1是',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_config_key` (`config_key`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- 写入核心默认配置（幂等）
INSERT INTO `system_config` (`config_key`, `config_value`, `config_type`, `description`, `category`, `is_editable`, `sort_order`)
VALUES
-- 积分消耗配置
('points_cost_bazi',         '30',   'int',    '八字排盘基础积分消耗',       'points_cost', 1, 1),
('points_cost_bazi_ai',      '50',   'int',    '八字AI深度解盘积分消耗',     'points_cost', 1, 2),
('points_cost_tarot',        '20',   'int',    '塔罗占卜基础积分消耗',       'points_cost', 1, 3),
('points_cost_tarot_ai',     '40',   'int',    '塔罗AI解读积分消耗',         'points_cost', 1, 4),
('points_cost_liuyao',       '25',   'int',    '六爻占卜积分消耗',           'points_cost', 1, 5),
('points_cost_hehun',        '80',   'int',    '八字合婚基础积分消耗',       'points_cost', 1, 6),
('points_cost_hehun_export', '30',   'int',    '合婚导出报告积分消耗',       'points_cost', 1, 7),
-- 新用户首测优惠
('points_free_bazi_first',   '1',    'bool',   '新用户首次八字是否免费',     'new_user',    1, 1),
('points_free_tarot_first',  '1',    'bool',   '新用户首次塔罗是否免费',     'new_user',    1, 2),
('new_user_offer_enabled',   '1',    'bool',   '新用户优惠开关',             'new_user',    1, 3),
('new_user_discount',        '50',   'int',    '新用户折扣（百分比）',       'new_user',    1, 4),
-- 功能开关
('feature_bazi_enabled',     '1',    'bool',   '八字功能开关',               'feature',     1, 1),
('feature_tarot_enabled',    '1',    'bool',   '塔罗功能开关',               'feature',     1, 2),
('feature_liuyao_enabled',   '1',    'bool',   '六爻功能开关',               'feature',     1, 3),
('feature_hehun_enabled',    '1',    'bool',   '合婚功能开关',               'feature',     1, 4),
('feature_daily_enabled',    '1',    'bool',   '每日运势功能开关',           'feature',     1, 5),
-- VIP配置
('vip_price_month',          '68',   'int',    'VIP月度价格（元）',          'vip',         1, 1),
('vip_price_quarter',        '168',  'int',    'VIP季度价格（元）',          'vip',         1, 2),
('vip_price_year',           '498',  'int',    'VIP年度价格（元）',          'vip',         1, 3),
('vip_unlock_hehun',         '1',    'bool',   'VIP是否解锁合婚功能',        'vip',         1, 4),
-- 积分充值档位（JSON）
('points_recharge_tiers',    '[{"points":100,"price":10},{"points":300,"price":28},{"points":600,"price":50},{"points":1000,"price":78}]',
                              'json', '积分充值档位配置',                   'recharge',    1, 1),
-- 站点基础信息
('site_name',                '太初命理',  'string', '站点名称',              'site',        1, 1),
('site_domain',              'taichu.chat', 'string', '站点域名',           'site',        0, 2),
('site_icp',                 '',          'string', 'ICP备案号',            'site',        1, 3),
('site_copyright',           '© 2026 太初命理', 'string', '版权信息',       'site',        1, 4),
-- 客服与联系方式
('contact_wechat',           '',    'string',   '客服微信号',               'contact',     1, 1),
('contact_email',            '',    'string',   '客服邮箱',                 'contact',     1, 2)
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`),
    `category` = VALUES(`category`),
    `is_editable` = VALUES(`is_editable`),
    `sort_order` = VALUES(`sort_order`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =============================================================
-- 3. 塔罗牌表（tarot_cards）
--    模型：TarotCard，兼容旧表名 tc_tarot_card
-- =============================================================
CREATE TABLE IF NOT EXISTS `tarot_cards` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '牌名（中文）',
    `name_en` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '牌名（英文）',
    `number` INT NOT NULL DEFAULT 0 COMMENT '牌号（大阿卡纳 0-21，小阿卡纳 1-14）',
    `suit` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '牌组: wands/cups/swords/pentacles/major',
    `is_major` TINYINT NOT NULL DEFAULT 0 COMMENT '是否大阿卡纳 0否 1是',
    `image` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '牌面图片URL',
    `image_reversed` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '逆位图片URL（可空）',
    `keywords` JSON NULL COMMENT '关键词列表',
    `keywords_reversed` JSON NULL COMMENT '逆位关键词列表',
    `meaning_general` TEXT NULL COMMENT '通用正位含义',
    `meaning_general_reversed` TEXT NULL COMMENT '通用逆位含义',
    `meaning_love` TEXT NULL COMMENT '感情正位',
    `meaning_love_reversed` TEXT NULL COMMENT '感情逆位',
    `meaning_career` TEXT NULL COMMENT '事业正位',
    `meaning_career_reversed` TEXT NULL COMMENT '事业逆位',
    `meaning_health` TEXT NULL COMMENT '健康正位',
    `meaning_health_reversed` TEXT NULL COMMENT '健康逆位',
    `meaning_wealth` TEXT NULL COMMENT '财运正位',
    `meaning_wealth_reversed` TEXT NULL COMMENT '财运逆位',
    `description` TEXT NULL COMMENT '牌义详细描述',
    `element` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '对应元素: fire/water/air/earth',
    `astrological` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '对应星座/星体',
    `numerology` INT NOT NULL DEFAULT 0 COMMENT '数字学对应',
    `is_enabled` TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_number_suit` (`number`, `suit`),
    KEY `idx_suit` (`suit`),
    KEY `idx_is_major` (`is_major`),
    KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗牌表';

-- =============================================================
-- 4. 塔罗牌阵表（tarot_spreads）
--    模型：TarotSpread
-- =============================================================
CREATE TABLE IF NOT EXISTS `tarot_spreads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '牌阵名称',
    `code` VARCHAR(50) NOT NULL COMMENT '牌阵代码标识',
    `description` TEXT NULL COMMENT '牌阵描述',
    `card_count` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '需要抽取的牌数',
    `positions` JSON NULL COMMENT '牌位定义JSON [{"position":1,"name":"过去","desc":"..."}]',
    `suitable_for` JSON NULL COMMENT '适用场景 ["love","career","general"]',
    `difficulty` VARCHAR(20) NOT NULL DEFAULT 'beginner' COMMENT '难度: beginner/intermediate/advanced',
    `is_free` TINYINT NOT NULL DEFAULT 0 COMMENT '是否免费牌阵 0否 1是',
    `points_cost` INT NOT NULL DEFAULT 0 COMMENT '使用消耗积分',
    `is_enabled` TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗牌阵表';

-- 默认牌阵种子
INSERT INTO `tarot_spreads` (`name`, `code`, `description`, `card_count`, `positions`, `suitable_for`, `difficulty`, `is_free`, `is_enabled`, `sort_order`)
VALUES
('单张牌', 'single', '抽取一张牌，快速获取当下指引', 1,
 '[{"position":1,"name":"当下指引","desc":"当前时刻的核心提示"}]',
 '["general","love","career","daily"]', 'beginner', 1, 1, 1),
('三牌阵', 'three_card', '过去-现在-未来，洞见时间维度', 3,
 '[{"position":1,"name":"过去","desc":"影响当前局面的过往"},{"position":2,"name":"现在","desc":"当前面临的核心情况"},{"position":3,"name":"未来","desc":"当前趋势延续的走向"}]',
 '["general","love","career"]', 'beginner', 0, 1, 2),
('凯尔特十字', 'celtic_cross', '最经典的十张牌阵，全面深入解析', 10,
 '[{"position":1,"name":"当前处境","desc":"当前核心情况"},{"position":2,"name":"阻碍因素","desc":"影响或阻碍当前情况的因素"},{"position":3,"name":"潜意识","desc":"深层心理状态"},{"position":4,"name":"过去根源","desc":"情况的起源或过去"},{"position":5,"name":"可能结果","desc":"可能的最佳结果"},{"position":6,"name":"近期未来","desc":"即将到来的影响"},{"position":7,"name":"自身态度","desc":"你自己的立场与感受"},{"position":8,"name":"外部影响","desc":"他人与外部环境的影响"},{"position":9,"name":"希望与恐惧","desc":"内心的期望与担忧"},{"position":10,"name":"最终结果","desc":"综合走向的最终答案"}]',
 '["general","love","career","health"]', 'advanced', 0, 1, 3),
('爱情牌阵', 'love', '聚焦感情关系，四牌深度剖析', 4,
 '[{"position":1,"name":"我方能量","desc":"你在感情中的状态"},{"position":2,"name":"对方能量","desc":"对方在感情中的状态"},{"position":3,"name":"关系现状","desc":"双方关系的实质"},{"position":4,"name":"发展方向","desc":"感情走向的建议"}]',
 '["love"]', 'intermediate', 0, 1, 4)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `card_count` = VALUES(`card_count`),
    `positions` = VALUES(`positions`),
    `is_enabled` = VALUES(`is_enabled`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =============================================================
-- 5. 塔罗占卜记录表（tc_tarot_record）
--    用于后台统计与历史查询
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_tarot_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `spread_type` VARCHAR(50) NOT NULL DEFAULT 'single' COMMENT '牌阵类型',
    `topic` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '占卜主题',
    `question` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '占卜问题',
    `cards` JSON NULL COMMENT '抽到的牌列表',
    `interpretation` TEXT NULL COMMENT 'AI/系统解读内容',
    `ai_analysis` TEXT NULL COMMENT 'AI深度分析',
    `points_used` INT NOT NULL DEFAULT 0 COMMENT '消耗积分',
    `is_free` TINYINT NOT NULL DEFAULT 0 COMMENT '是否首免',
    `is_public` TINYINT NOT NULL DEFAULT 0 COMMENT '是否公开',
    `share_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分享码',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '查看次数',
    `client_ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '客户端IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_spread_type` (`spread_type`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗占卜记录表';

-- =============================================================
-- 6. 六爻占卜记录表（tc_liuyao_record）
--    用于后台统计与历史查询
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_liuyao_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `question` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '占卜问题',
    `method` VARCHAR(30) NOT NULL DEFAULT 'coin' COMMENT '起卦方式: coin/number/random',
    `hexagram_original` JSON NULL COMMENT '本卦数据',
    `hexagram_changed` JSON NULL COMMENT '变卦数据',
    `hexagram_mutual` JSON NULL COMMENT '互卦数据',
    `lines` JSON NULL COMMENT '六爻爻辞列表',
    `moving_lines` JSON NULL COMMENT '动爻列表',
    `analysis` TEXT NULL COMMENT '系统分析',
    `ai_analysis` TEXT NULL COMMENT 'AI深度分析',
    `points_used` INT NOT NULL DEFAULT 0 COMMENT '消耗积分',
    `is_free` TINYINT NOT NULL DEFAULT 0 COMMENT '是否首免',
    `is_public` TINYINT NOT NULL DEFAULT 0 COMMENT '是否公开',
    `share_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分享码',
    `client_ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '客户端IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='六爻占卜记录表';

-- =============================================================
-- 7. 文件上传记录表（upload_files）
--    模型：UploadFile，兼容旧表名 tc_upload_file
-- =============================================================
CREATE TABLE IF NOT EXISTS `upload_files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `original_name` VARCHAR(255) NOT NULL COMMENT '原始文件名',
    `file_name` VARCHAR(255) NOT NULL COMMENT '存储文件名',
    `file_path` VARCHAR(500) NOT NULL COMMENT '存储路径',
    `file_url` VARCHAR(500) NOT NULL COMMENT '访问URL',
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小（字节）',
    `mime_type` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'MIME类型',
    `extension` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '文件扩展名',
    `storage` VARCHAR(30) NOT NULL DEFAULT 'local' COMMENT '存储方式: local/oss/cos',
    `category` VARCHAR(50) NOT NULL DEFAULT 'common' COMMENT '文件分类',
    `uploaded_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '上传者ID（管理员）',
    `is_deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已删除',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_category` (`category`),
    KEY `idx_uploaded_by` (`uploaded_by`),
    KEY `idx_mime_type` (`mime_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件上传记录表';

-- =============================================================
-- 8. AI提示词表（ai_prompts）
--    模型：AiPrompt，兼容旧表名 tc_ai_prompt
-- =============================================================
CREATE TABLE IF NOT EXISTS `ai_prompts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '提示词名称',
    `code` VARCHAR(50) NOT NULL COMMENT '提示词代码标识',
    `type` VARCHAR(50) NOT NULL DEFAULT 'system' COMMENT '类型: system/user/few_shot',
    `category` VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT '分类: bazi/tarot/liuyao/hehun/general',
    `content` TEXT NOT NULL COMMENT '提示词内容',
    `variables` JSON NULL COMMENT '变量列表 [{"name":"birth_date","desc":"出生日期"}]',
    `model` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '适用模型（空=通用）',
    `version` VARCHAR(20) NOT NULL DEFAULT '1.0' COMMENT '版本号',
    `is_active` TINYINT NOT NULL DEFAULT 1 COMMENT '是否激活 0否 1是',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_category` (`category`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI提示词配置表';

-- =============================================================
-- 9. 页面管理表（pages）
--    模型：Page，兼容旧表名 tc_page
-- =============================================================
CREATE TABLE IF NOT EXISTS `pages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL COMMENT '页面标题',
    `slug` VARCHAR(200) NOT NULL COMMENT '页面路由标识',
    `type` VARCHAR(30) NOT NULL DEFAULT 'custom' COMMENT '类型: custom/landing/article',
    `content` JSON NULL COMMENT '页面内容（JSON结构）',
    `settings` JSON NULL COMMENT '页面设置（SEO、发布等）',
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT '状态: draft/published/archived',
    `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '当前版本号',
    `updated_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后更新者（管理员ID）',
    `published_at` DATETIME NULL COMMENT '发布时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面管理表';

-- =============================================================
-- 10. 页面版本表（page_versions）
--    模型：PageVersion
-- =============================================================
CREATE TABLE IF NOT EXISTS `page_versions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT UNSIGNED NOT NULL COMMENT '页面ID',
    `version` INT UNSIGNED NOT NULL COMMENT '版本号',
    `content` JSON NULL COMMENT '该版本内容快照',
    `settings` JSON NULL COMMENT '该版本设置快照',
    `comment` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '版本说明',
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作者（管理员ID）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_page_version` (`page_id`, `version`),
    KEY `idx_page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面版本历史表';

-- =============================================================
-- 11. 页面草稿表（page_drafts）
--    模型：PageDraft
-- =============================================================
CREATE TABLE IF NOT EXISTS `page_drafts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT UNSIGNED NOT NULL COMMENT '页面ID（0=新建未保存）',
    `content` JSON NULL COMMENT '草稿内容',
    `settings` JSON NULL COMMENT '草稿设置',
    `editor_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '编辑者（管理员ID）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_page_id` (`page_id`),
    KEY `idx_editor_id` (`editor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面草稿表';

-- =============================================================
-- 12. 页面回收站表（page_recycle）
--    模型：PageRecycle
-- =============================================================
CREATE TABLE IF NOT EXISTS `page_recycle` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT UNSIGNED NOT NULL COMMENT '原页面ID',
    `title` VARCHAR(255) NOT NULL COMMENT '页面标题（快照）',
    `slug` VARCHAR(200) NOT NULL COMMENT '原路由标识',
    `content` JSON NULL COMMENT '页面内容快照',
    `deleted_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除者（管理员ID）',
    `deleted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '删除时间',
    KEY `idx_page_id` (`page_id`),
    KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面回收站表';

-- =============================================================
-- 13. 常见问题表（faqs）
--    模型：Faq，兼容旧表名 tc_faq
-- =============================================================
CREATE TABLE IF NOT EXISTS `faqs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `question` VARCHAR(500) NOT NULL COMMENT '问题',
    `answer` TEXT NOT NULL COMMENT '回答',
    `category` VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT '分类: general/bazi/tarot/payment/account',
    `is_published` TINYINT NOT NULL DEFAULT 1 COMMENT '是否发布 0否 1是',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序（越小越靠前）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_category` (`category`),
    KEY `idx_published` (`is_published`),
    KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='常见问题表';

-- 默认FAQ种子
INSERT INTO `faqs` (`question`, `answer`, `category`, `is_published`, `sort_order`)
VALUES
('如何获取积分？',     '注册赠送初始积分，每日签到、邀请好友均可获得积分奖励。', 'account', 1, 1),
('积分可以提现吗？',   '积分仅供平台功能消耗使用，不支持提现，但可用于兑换VIP会员。', 'account', 1, 2),
('八字解盘准确吗？',   '本平台结合传统命理学与AI算法，解盘结论仅供参考，请理性看待。', 'bazi', 1, 3),
('如何联系客服？',     '可通过"关于我们"页面的微信/邮箱与我们取得联系。', 'general', 1, 4)
ON DUPLICATE KEY UPDATE
    `answer` = VALUES(`answer`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =============================================================
-- 14. 用户反馈表（feedback）
--    模型：Feedback，兼容旧表名 tc_feedback
-- =============================================================
CREATE TABLE IF NOT EXISTS `feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID（0=虚拟用户）',
    `type` VARCHAR(30) NOT NULL DEFAULT 'suggestion' COMMENT '类型: bug/feature/suggestion/other',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '标题',
    `content` TEXT NOT NULL COMMENT '反馈内容',
    `images` JSON NULL COMMENT '附图列表',
    `contact` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '联系方式',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '处理状态: 0待处理 1处理中 2已回复 3已关闭',
    `reply` TEXT NULL COMMENT '回复内容',
    `replied_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复管理员ID',
    `replied_at` DATETIME NULL COMMENT '回复时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户反馈表';

-- =============================================================
-- 15. 每日运势模板表（daily_fortune_templates）
--    模型：DailyFortuneTemplate
-- =============================================================
CREATE TABLE IF NOT EXISTS `daily_fortune_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `zodiac` VARCHAR(20) NOT NULL COMMENT '星座/生肖标识',
    `type` VARCHAR(20) NOT NULL DEFAULT 'zodiac' COMMENT '模板类型: zodiac/shengxiao/element',
    `date_pattern` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '日期模式（月日，如 03-18 或空=通用）',
    `overall` TEXT NULL COMMENT '综合运势模板（可含变量）',
    `love` TEXT NULL COMMENT '感情运势模板',
    `career` TEXT NULL COMMENT '事业运势模板',
    `wealth` TEXT NULL COMMENT '财运模板',
    `health` TEXT NULL COMMENT '健康模板',
    `lucky_color` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '幸运颜色',
    `lucky_number` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '幸运数字',
    `lucky_direction` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '幸运方位',
    `score_min` TINYINT UNSIGNED NOT NULL DEFAULT 60 COMMENT '评分下限（算法随机区间）',
    `score_max` TINYINT UNSIGNED NOT NULL DEFAULT 95 COMMENT '评分上限',
    `is_active` TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_zodiac` (`zodiac`),
    KEY `idx_type` (`type`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日运势模板表';

-- =============================================================
-- 16. 问题模板表（question_templates）
--    模型：QuestionTemplate
-- =============================================================
CREATE TABLE IF NOT EXISTS `question_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category` VARCHAR(50) NOT NULL COMMENT '分类: love/career/wealth/health/general',
    `title` VARCHAR(200) NOT NULL COMMENT '模板标题',
    `content` VARCHAR(500) NOT NULL COMMENT '模板问题内容',
    `applicable_to` JSON NULL COMMENT '适用功能 ["tarot","liuyao","bazi"]',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `is_enabled` TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_category` (`category`),
    KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问题模板表';

-- 默认问题模板种子
INSERT INTO `question_templates` (`category`, `title`, `content`, `applicable_to`, `sort_order`, `is_enabled`)
VALUES
('love',    '感情走向',   '最近与伴侣/暗恋对象的感情走势会如何？',       '["tarot","liuyao"]', 1, 1),
('love',    '姻缘时机',   '我今年的桃花运和姻缘时机如何？',               '["tarot","bazi"]',   2, 1),
('career',  '职场发展',   '当前工作/事业的整体发展方向如何？',             '["tarot","liuyao"]', 3, 1),
('career',  '跳槽决策',   '现在是否适合换工作或开展新的事业？',             '["tarot","liuyao"]', 4, 1),
('wealth',  '财运状况',   '近期的财运如何？有哪些需要注意的地方？',         '["tarot","liuyao"]', 5, 1),
('health',  '健康提示',   '近期有哪些健康方面需要特别关注的？',             '["tarot"]',          6, 1),
('general', '整体运势',   '请帮我分析一下近期的综合运势与整体状态。',       '["tarot","bazi"]',   7, 1)
ON DUPLICATE KEY UPDATE
    `content` = VALUES(`content`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =============================================================
-- 17. 用户好评/见证表（testimonials）
--    模型：Testimonial
-- =============================================================
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID（0=虚拟用户）',
    `nickname` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '展示昵称',
    `avatar` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '头像URL',
    `content` TEXT NOT NULL COMMENT '好评内容',
    `service` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '涉及服务: bazi/tarot/liuyao/hehun/general',
    `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '评分 1-5',
    `is_featured` TINYINT NOT NULL DEFAULT 0 COMMENT '是否首页精选 0否 1是',
    `is_published` TINYINT NOT NULL DEFAULT 1 COMMENT '是否发布 0否 1是',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_service` (`service`),
    KEY `idx_featured` (`is_featured`),
    KEY `idx_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户好评/见证表';

-- =============================================================
-- 18. 网站内容表（site_contents）
--    模型：SiteContent
-- =============================================================
CREATE TABLE IF NOT EXISTS `site_contents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page` VARCHAR(50) NOT NULL COMMENT '所属页面: home/about/service/pricing',
    `key` VARCHAR(100) NOT NULL COMMENT '内容键名',
    `value` TEXT NULL COMMENT '内容值',
    `type` VARCHAR(20) NOT NULL DEFAULT 'text' COMMENT '内容类型: text/html/json/image',
    `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '说明',
    `is_enabled` TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建者',
    `updated_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新者',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_page_key` (`page`, `key`),
    KEY `idx_page` (`page`),
    KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='网站内容管理表';

-- 默认首页内容种子
INSERT INTO `site_contents` (`page`, `key`, `value`, `type`, `description`, `is_enabled`, `sort_order`)
VALUES
('home', 'hero_title',      '洞见天机，照见本心',   'text', '首页主标题',         1, 1),
('home', 'hero_subtitle',   '专业八字命理 · AI智能解盘 · 为你拨开命运迷雾', 'text', '首页副标题', 1, 2),
('home', 'hero_cta_text',   '立即免费测算',          'text', '首页CTA按钮文字',    1, 3),
('home', 'hero_cta_link',   '/bazi',                 'text', '首页CTA按钮链接',    1, 4),
('home', 'free_trial_note', '注册即享首次免费八字排盘', 'text', '首免说明文字',   1, 5)
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =============================================================
-- 19. 操作日志表（operation_logs）
--    模型：OperationLog
-- =============================================================
CREATE TABLE IF NOT EXISTS `operation_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作管理员ID',
    `admin_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作管理员名称（冗余）',
    `action` VARCHAR(100) NOT NULL COMMENT '操作行为',
    `module` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作模块',
    `target_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作对象ID',
    `target_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作对象类型',
    `detail` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '操作描述',
    `before_data` JSON NULL COMMENT '操作前数据快照',
    `after_data` JSON NULL COMMENT '操作后数据快照',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '操作IP',
    `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'User-Agent',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_action` (`action`),
    KEY `idx_module` (`module`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员操作日志表';

-- =============================================================
-- 20. 签到日志表（tc_checkin_log）
--    Task控制器 / Daily控制器使用
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_checkin_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '签到日期',
    `consecutive_days` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '连续签到天数',
    `points` INT NOT NULL DEFAULT 0 COMMENT '本次获得积分',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_date` (`user_id`, `date`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户签到日志表';

-- =============================================================
-- 21. 分享记录表（tc_share_log）
--    Share控制器使用
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_share_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '分享类型: bazi/tarot/liuyao/hehun/daily',
    `platform` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '分享平台: wechat/weibo/link/copy',
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分享内容ID（记录ID）',
    `share_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分享码',
    `points_reward` INT NOT NULL DEFAULT 0 COMMENT '分享获得积分',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '分享IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_share_code` (`share_code`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容分享记录表';

-- =============================================================
-- 22. 任务记录表（tc_task_log）
--    Task控制器使用
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_task_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `task_type` VARCHAR(50) NOT NULL COMMENT '任务类型: checkin/invite/share/view/first_bazi',
    `task_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '任务名称',
    `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
    `extra` JSON NULL COMMENT '额外信息',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user_id` (`user_id`),
    KEY `idx_task_type` (`task_type`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务完成记录表';

-- =============================================================
-- 23. VIP订单表 username 字段兼容补齐（tc_vip_order 可能已存在）
-- =============================================================
-- 仅在表不存在时创建（若已存在由 02_create_tables.sql 负责）
CREATE TABLE IF NOT EXISTS `tc_vip_order` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_no` VARCHAR(50) NOT NULL COMMENT '订单号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `vip_level` TINYINT NOT NULL DEFAULT 1 COMMENT 'VIP等级 1月度 2季度 3年度',
    `duration_days` INT NOT NULL DEFAULT 30 COMMENT '有效天数',
    `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实付金额',
    `payment_type` VARCHAR(30) NOT NULL DEFAULT 'wechat_jsapi' COMMENT '支付方式',
    `pay_order_no` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '支付平台订单号',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending/paid/expired/cancelled',
    `paid_at` DATETIME NULL COMMENT '支付时间',
    `vip_start_at` DATETIME NULL COMMENT 'VIP开始时间',
    `vip_end_at` DATETIME NULL COMMENT 'VIP到期时间',
    `callback_data` JSON NULL COMMENT '回调原文',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_vip_end_at` (`vip_end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VIP订单表';

-- =============================================================
-- 24. 短信验证码表（tc_sms_code）
--    模型：SmsCode，用于短信验证码管理
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_sms_code` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
    `code` VARCHAR(10) NOT NULL COMMENT '验证码',
    `type` VARCHAR(20) NOT NULL DEFAULT 'register' COMMENT '类型 register/login/reset',
    `expire_time` DATETIME NOT NULL COMMENT '过期时间',
    `is_used` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已使用 0否 1是',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'User-Agent',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_phone` (`phone`),
    INDEX `idx_code` (`code`),
    INDEX `idx_type` (`type`),
    INDEX `idx_is_used` (`is_used`),
    INDEX `idx_phone_type` (`phone`, `type`),
    INDEX `idx_expire_time` (`expire_time`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信验证码表';

-- =============================================================
-- 25. 短信配置表（tc_sms_config）
--    模型：SmsConfig，用于存储腾讯云/其他SMS供应商的API配置
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_sms_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `provider` VARCHAR(50) NOT NULL DEFAULT 'tencent' COMMENT '供应商: tencent/aliyun/other',
    `secret_id` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '腾讯云SecretId 或 阿里云AccessKeyId',
    `secret_key` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '腾讯云SecretKey 或 阿里云AccessKeySecret',
    `sdk_app_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '腾讯云SDK App ID',
    `sign_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '短信签名',
    `template_code` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '短信模板ID',
    `template_register` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '注册验证码模板ID',
    `template_reset` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '密码重置验证码模板ID',
    `is_enabled` TINYINT NOT NULL DEFAULT 0 COMMENT '是否启用 0否 1是',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_provider` (`provider`),
    KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信配置表';

-- =============================================================
-- 26. 支付配置表（tc_payment_config）
--    模型：PaymentConfig，用于存储微信支付/支付宝的API配置
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_payment_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL DEFAULT 'wechat_jsapi' COMMENT '支付类型: wechat_jsapi/wechat_native/alipay_web/alipay_mobile',
    `mch_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '商户号（微信）/ 商家ID（支付宝）',
    `app_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '应用ID（微信）/ 应用ID（支付宝）',
    `api_key` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'API密钥（微信）/ 私钥（支付宝）',
    `api_cert` LONGTEXT COMMENT '微信支付证书（pem格式）',
    `api_key_pem` LONGTEXT COMMENT '微信支付私钥（pem格式）',
    `notify_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '支付回调通知URL',
    `return_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '支付成功返回URL（支付宝用）',
    `is_enabled` TINYINT NOT NULL DEFAULT 0 COMMENT '是否启用 0否 1是',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_type` (`type`),
    KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付配置表';

SET FOREIGN_KEY_CHECKS = 1;

-- 完成提示
SELECT CONCAT(
    '✅ 缺失表补充完成。共创建/确认以下表：',
    'hehun_records, system_config, tarot_cards, tarot_spreads, ',
    'tc_tarot_record, tc_liuyao_record, upload_files, ai_prompts, ',
    'pages, page_versions, page_drafts, page_recycle, faqs, feedback, ',
    'daily_fortune_templates, question_templates, testimonials, site_contents, ',
    'operation_logs, tc_checkin_log, tc_share_log, tc_task_log, ',
    'points_history, points_exchange, checkin_record, ',
    'tc_sms_code, tc_sms_config, tc_payment_config'
) AS migration_result;



-- Source: 20260318_add_points_recharge_sms_indexes.sql
SET NAMES utf8mb4;
USE taichu;

-- =============================================================
-- 高频查询与定时清理索引补齐
-- 1. tc_points_record(user_id, created_at)
-- 2. tc_recharge_order(user_id, status, created_at)
-- 3. tc_sms_code(expire_time, is_used)
-- 幂等执行：通过 information_schema 检查后再创建
-- =============================================================

SET @has_points_user_created := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_points_record'
      AND INDEX_NAME = 'idx_user_created'
);
SET @sql_points_user_created := IF(
    @has_points_user_created = 0,
    "ALTER TABLE `tc_points_record` ADD INDEX `idx_user_created` (`user_id`, `created_at`)",
    'SELECT 1'
);
PREPARE stmt_points_user_created FROM @sql_points_user_created;
EXECUTE stmt_points_user_created;
DEALLOCATE PREPARE stmt_points_user_created;

SET @has_recharge_user_status_created := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_recharge_order'
      AND INDEX_NAME = 'idx_user_status_created'
);
SET @sql_recharge_user_status_created := IF(
    @has_recharge_user_status_created = 0,
    "ALTER TABLE `tc_recharge_order` ADD INDEX `idx_user_status_created` (`user_id`, `status`, `created_at`)",
    'SELECT 1'
);
PREPARE stmt_recharge_user_status_created FROM @sql_recharge_user_status_created;
EXECUTE stmt_recharge_user_status_created;
DEALLOCATE PREPARE stmt_recharge_user_status_created;

SET @has_sms_expire_used := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND INDEX_NAME = 'idx_expire_used'
);
SET @sql_sms_expire_used := IF(
    @has_sms_expire_used = 0,
    "ALTER TABLE `tc_sms_code` ADD INDEX `idx_expire_used` (`expire_time`, `is_used`)",
    'SELECT 1'
);
PREPARE stmt_sms_expire_used FROM @sql_sms_expire_used;
EXECUTE stmt_sms_expire_used;
DEALLOCATE PREPARE stmt_sms_expire_used;

SELECT '索引补齐完成' AS migration_result;


-- Source: 20260318_fix_hehun_points_config.sql
-- 修复合婚积分配置缺失/写入错误表导致的 pricing/calculate 500
-- 适用范围：system_config 表已存在，但缺少合婚相关默认配置的环境

INSERT INTO `system_config` (`config_key`, `config_value`, `config_type`, `description`, `category`, `is_editable`, `sort_order`, `created_at`, `updated_at`) VALUES
('feature_hehun_enabled', '1', 'bool', '八字合婚功能开关', 'feature', 1, 7, NOW(), NOW()),
('points_cost_hehun', '80', 'int', '八字合婚基础积分消耗', 'points_cost', 1, 7, NOW(), NOW()),
('points_cost_hehun_export', '30', 'int', '八字合婚导出报告积分', 'points_cost', 1, 8, NOW(), NOW()),
('vip_unlock_hehun', '1', 'bool', 'VIP是否解锁合婚功能', 'vip', 1, 7, NOW(), NOW()),
('new_user_offer_enabled', '1', 'bool', '新用户优惠开关', 'new_user', 1, 1, NOW(), NOW()),
('new_user_discount', '50', 'int', '新用户折扣(%)', 'new_user', 1, 2, NOW(), NOW()),
('new_user_valid_hours', '24', 'int', '新用户优惠有效期(小时)', 'new_user', 1, 3, NOW(), NOW()),
('limited_offer_enabled', '0', 'bool', '限时优惠开关', 'limited_offer', 1, 1, NOW(), NOW()),
('limited_offer_discount', '30', 'int', '限时优惠折扣(%)', 'limited_offer', 1, 2, NOW(), NOW()),
('limited_offer_start_time', '', 'string', '限时优惠开始时间', 'limited_offer', 1, 3, NOW(), NOW()),
('limited_offer_end_time', '', 'string', '限时优惠结束时间', 'limited_offer', 1, 4, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `config_value` = VALUES(`config_value`),
  `config_type` = VALUES(`config_type`),
  `description` = VALUES(`description`),
  `category` = VALUES(`category`),
  `is_editable` = VALUES(`is_editable`),
  `sort_order` = VALUES(`sort_order`),
  `updated_at` = VALUES(`updated_at`);


-- Source: 20260318_fix_shensha_display_encoding.sql
SET NAMES utf8mb4;

USE taichu;

-- 修复历史神煞数据中的 ?? / ???? / 乱码占位。
-- 仅针对默认种子对应的 sort + type + category 组合执行兜底回填，避免误伤自定义神煞。
-- 注意：`tc_shensha` 在导入链路里存在 `uk_name_category(name, category)` 唯一键。
-- 启动 / 导入阶段不直接改 `name`，避免把历史乱码行更新成已存在的标准名称（如“福星贵人”）后撞唯一键。
-- 标准中文名称由 `20260317_create_shensha_table.sql` 的默认种子兜底，后台读取时也有 DisplayTextRepairService 按 sort/type/category 做展示修复。
UPDATE `tc_shensha`
SET
    `description` = CASE
        WHEN `sort` = 1 AND `type` = 'daji' AND `category` = 'guiren' THEN '最吉之神，命中逢之，遇事有人帮，遇危难有人救'
        WHEN `sort` = 2 AND `type` = 'ji' AND `category` = 'xueye' THEN '主聪明好学，利文途考学'
        WHEN `sort` = 3 AND `type` = 'ji' AND `category` = 'guiren' THEN '主人聪明好学，喜神秘文化'
        WHEN `sort` = 4 AND `type` = 'daji' AND `category` = 'guiren' THEN '天地德秀之气，逢凶化吉之神'
        WHEN `sort` = 5 AND `type` = 'daji' AND `category` = 'guiren' THEN '乃太阴之德，功能与天德略同而稍逊'
        WHEN `sort` = 6 AND `type` = 'ji' AND `category` = 'guiren' THEN '主人一生福禄无缺，格局配合得当，必然多福多寿'
        WHEN `sort` = 7 AND `type` = 'ping' AND `category` = 'ganqing' THEN '主人漂亮多情，风流潇洒'
        WHEN `sort` = 8 AND `type` = 'xiong' AND `category` = 'jiankang' THEN '司刑之星，性情刚强'
        WHEN `sort` = 9 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '主破财、阻碍'
        WHEN `sort` = 10 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '主孤独，不利婚姻'
        WHEN `sort` = 11 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '主孤独，不利婚姻'
        WHEN `sort` = 12 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '主婚姻不顺'
        WHEN `sort` = 13 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '主不善理财，花钱大手大脚'
        WHEN `sort` = 14 AND `type` = 'ji' AND `category` = 'caiyun' THEN '主富贵，聪明富贵'
        WHEN `sort` = 15 AND `type` = 'ping' AND `category` = 'guiren' THEN '主聪明好学，喜艺术、宗教'
        ELSE `description`
    END,
    `effect` = CASE
        WHEN `sort` = 1 AND `type` = 'daji' AND `category` = 'guiren' THEN '遇难成祥，逢凶化吉，人缘极佳，易得他人帮助'
        WHEN `sort` = 2 AND `type` = 'ji' AND `category` = 'xueye' THEN '聪明过人，学业有成，考试顺利，利于文职'
        WHEN `sort` = 3 AND `type` = 'ji' AND `category` = 'guiren' THEN '悟性高，对命理、宗教、玄学有兴趣，逢凶化吉'
        WHEN `sort` = 4 AND `type` = 'daji' AND `category` = 'guiren' THEN '一生安逸，不犯刑律，不遇凶祸，福气好'
        WHEN `sort` = 5 AND `type` = 'daji' AND `category` = 'guiren' THEN '逢凶化吉，灾少福多，一生少病痛'
        WHEN `sort` = 6 AND `type` = 'ji' AND `category` = 'guiren' THEN '一生福禄无缺，享福深厚，平安幸福'
        WHEN `sort` = 7 AND `type` = 'ping' AND `category` = 'ganqing' THEN '人缘好，异性缘佳，感情丰富，但可能感情复杂'
        WHEN `sort` = 8 AND `type` = 'xiong' AND `category` = 'jiankang' THEN '性格刚烈，易有刀伤手术，但也代表能力强'
        WHEN `sort` = 9 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '破财、阻碍、是非、意外'
        WHEN `sort` = 10 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '孤独，少依靠，婚姻不顺，与亲人缘薄'
        WHEN `sort` = 11 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '孤独，婚姻不顺，女命尤其注意'
        WHEN `sort` = 12 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '婚姻不利，夫妻不和，男克妻女克夫'
        WHEN `sort` = 13 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '不善理财，花钱如流水，难以积蓄'
        WHEN `sort` = 14 AND `type` = 'ji' AND `category` = 'caiyun' THEN '聪明富贵，性柔貌愿，举止温和'
        WHEN `sort` = 15 AND `type` = 'ping' AND `category` = 'guiren' THEN '聪明好学，喜艺术、玄学、宗教，有出世思想'
        ELSE `effect`
    END,
    `check_rule` = CASE
        WHEN `sort` = 1 AND `type` = 'daji' AND `category` = 'guiren' THEN '甲戊见牛羊，乙己鼠猴乡，丙丁猪鸡位，壬癸兔蛇藏，庚辛逢虎马，此是贵人方'
        WHEN `sort` = 2 AND `type` = 'ji' AND `category` = 'xueye' THEN '甲乙巳午报君知，丙戊申宫丁己鸡，庚猪辛鼠壬逢虎，癸人见卯入云梯'
        WHEN `sort` = 3 AND `type` = 'ji' AND `category` = 'guiren' THEN '甲乙生人子午中，丙丁鸡兔定亨通，戊己两干临四季，庚辛寅亥禄丰隆，壬癸巳申偏喜美'
        WHEN `sort` = 4 AND `type` = 'daji' AND `category` = 'guiren' THEN '正丁二坤宫，三壬四辛同，五乾六甲上，七癸八艮逢，九丙十居乙，子巽丑庚中'
        WHEN `sort` = 5 AND `type` = 'daji' AND `category` = 'guiren' THEN '寅午戌月在丙，申子辰月在壬，亥卯未月在甲，巳酉丑月在庚'
        WHEN `sort` = 6 AND `type` = 'ji' AND `category` = 'guiren' THEN '甲丙相邀入虎乡，更游鼠穴最高强，戊猴己未丁宜亥，乙癸逢牛卯禄昌，庚赶马头辛到巳，壬骑龙背喜非常'
        WHEN `sort` = 7 AND `type` = 'ping' AND `category` = 'ganqing' THEN '申子辰在酉，巳酉丑在午，亥卯未在子，寅午戌在卯'
        WHEN `sort` = 8 AND `type` = 'xiong' AND `category` = 'jiankang' THEN '甲刃在卯，乙刃在寅，丙戊刃在午，丁己刃在巳，庚刃在酉，辛刃在申，壬刃在子，癸刃在亥'
        WHEN `sort` = 9 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '申子辰见巳，亥卯未见申，寅午戌见亥，巳酉丑见寅'
        WHEN `sort` = 10 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '亥子丑人，见寅为孤辰，见戌为寡宿'
        WHEN `sort` = 11 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '亥子丑人，见戌为寡宿，见寅为孤辰'
        WHEN `sort` = 12 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '丙子、丁丑、戊寅、辛卯、壬辰、癸巳、丙午、丁未、戊申、辛酉、壬戌、癸亥'
        WHEN `sort` = 13 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '甲辰、乙巳、丙申、丁亥、戊戌、己丑、庚辰、辛巳、壬申、癸亥'
        WHEN `sort` = 14 AND `type` = 'ji' AND `category` = 'caiyun' THEN '甲龙乙蛇丙戊羊，丁己猴歌庚犬方，辛猪壬牛癸逢虎'
        WHEN `sort` = 15 AND `type` = 'ping' AND `category` = 'guiren' THEN '寅午戌见戌，巳酉丑见丑，申子辰见辰，亥卯未见未'
        ELSE `check_rule`
    END,
    `updated_at` = NOW()
WHERE (
    (`name` <> '' AND `name` NOT REGEXP '[一-龥]')
    OR (`description` <> '' AND `description` NOT REGEXP '[一-龥]')
    OR (`effect` <> '' AND `effect` NOT REGEXP '[一-龥]')
    OR (`check_rule` <> '' AND `check_rule` NOT REGEXP '[一-龥]')
)
AND (
    (`sort` = 1 AND `type` = 'daji' AND `category` = 'guiren')
    OR (`sort` = 2 AND `type` = 'ji' AND `category` = 'xueye')
    OR (`sort` = 3 AND `type` = 'ji' AND `category` = 'guiren')
    OR (`sort` = 4 AND `type` = 'daji' AND `category` = 'guiren')
    OR (`sort` = 5 AND `type` = 'daji' AND `category` = 'guiren')
    OR (`sort` = 6 AND `type` = 'ji' AND `category` = 'guiren')
    OR (`sort` = 7 AND `type` = 'ping' AND `category` = 'ganqing')
    OR (`sort` = 8 AND `type` = 'xiong' AND `category` = 'jiankang')
    OR (`sort` = 9 AND `type` = 'xiong' AND `category` = 'caiyun')
    OR (`sort` = 10 AND `type` = 'xiong' AND `category` = 'ganqing')
    OR (`sort` = 11 AND `type` = 'xiong' AND `category` = 'ganqing')
    OR (`sort` = 12 AND `type` = 'xiong' AND `category` = 'ganqing')
    OR (`sort` = 13 AND `type` = 'xiong' AND `category` = 'caiyun')
    OR (`sort` = 14 AND `type` = 'ji' AND `category` = 'caiyun')
    OR (`sort` = 15 AND `type` = 'ping' AND `category` = 'guiren')
);

SET FOREIGN_KEY_CHECKS = 1;

