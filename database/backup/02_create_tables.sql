-- =====================================================
-- 太初命理系统 - 表结构创建脚本
-- 按依赖顺序创建所有表
-- =====================================================

USE taichu;

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
