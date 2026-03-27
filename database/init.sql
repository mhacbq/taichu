-- =============================================================
-- 太初命理系统 - 全量初始化脚本 (init.sql)
-- 生成时间: 2026-03-25
-- 用途: 全新环境一键导入，直接替换 full_import_for_navicat.sql
--
-- 包含内容:
--   [1] 用户相关表 (tc_user, tc_user_profile)
--   [2] 积分相关表 (tc_points_record, tc_points_task, tc_points_rule)
--   [3] VIP & 充值表 (tc_vip_package, tc_vip_order, tc_recharge_order, tc_payment_config)
--   [4] 业务功能表 (八字/合婚/取名/每日运势/流年/塔罗/六爻)
--   [5] 短信 & 邀请 & 反馈表
--   [6] 系统配置表 (tc_system_config, tc_feature_switch)
--   [7] 管理员相关表 (tc_admin, 角色, 权限, 日志)
--   [8] 日志表 (tc_admin_login_log, tc_api_log)
--   [9] 内容管理表 (tc_faq, tc_ai_prompt, tc_article*)
--   [10] 通知推送表 (tc_notification, tc_notification_setting, tc_push_device)
--   [11] 黄历 & 神煞表 (tc_almanac, tc_shensha)
--   [12] SEO 相关表
--   [13] 统计表 (tc_site_daily_stats)
--   [14] 反作弊表
--   [15] 文件上传表 (tc_upload_file)
--   [16] 塔罗牌库表 (tc_tarot_card)
--   [17] 初始数据 (管理员账号/角色/权限/配置/神煞/塔罗/FAQ)
--
-- 幂等设计: IF NOT EXISTS / ON DUPLICATE KEY UPDATE
-- 重复执行安全，可放心多次运行。
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `taichu` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `taichu`;

-- =============================================================
-- [1] 用户相关表
-- =============================================================

CREATE TABLE IF NOT EXISTS `tc_user` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `openid` VARCHAR(100) DEFAULT NULL COMMENT '微信openid，手机号注册用户保持 NULL',
    `unionid` VARCHAR(100) DEFAULT NULL COMMENT '微信unionid',
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
    `last_sms_code_time` DATETIME NULL COMMENT '最后发送验证码时间',
    `sms_code_attempts` INT UNSIGNED DEFAULT 0 COMMENT '验证码尝试次数',
    `invite_code` VARCHAR(20) DEFAULT '' COMMENT '我的邀请码',
    `invited_by` INT UNSIGNED DEFAULT 0 COMMENT '邀请人用户ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_openid` (`openid`),
    UNIQUE KEY `uk_phone` (`phone`),
    INDEX `idx_status` (`status`),
    INDEX `idx_vip` (`vip_level`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

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

-- =============================================================
-- [2] 积分相关表
-- =============================================================

CREATE TABLE IF NOT EXISTS `tc_points_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `action` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '操作名称',
    `points` INT NOT NULL DEFAULT 0 COMMENT '有符号积分变动值（正增负减）',
    `type` VARCHAR(20) NOT NULL COMMENT '类型 add增加 reduce扣除',
    `related_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联ID',
    `amount` INT NOT NULL DEFAULT 0 COMMENT '变动数量（绝对值，兼容旧字段）',
    `balance` INT NOT NULL DEFAULT 0 COMMENT '变动后余额',
    `reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '变动原因',
    `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '简要说明',
    `source_id` INT UNSIGNED DEFAULT 0 COMMENT '来源ID',
    `source_type` VARCHAR(50) DEFAULT '' COMMENT '来源类型',
    `remark` VARCHAR(500) DEFAULT '' COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分记录表';

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

-- 积分规则配置表（独立规则表，支持后台可配置积分数值）
CREATE TABLE IF NOT EXISTS `tc_points_rule` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL COMMENT '规则类型（对应 tc_points_record.action）',
    `rule_name` VARCHAR(100) NOT NULL COMMENT '规则名称',
    `points` INT NOT NULL DEFAULT 0 COMMENT '积分数值（正=奖励，负=消耗）',
    `description` VARCHAR(500) DEFAULT '' COMMENT '规则说明',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1-启用 0-禁用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分规则配置表';

-- =============================================================
-- [3] VIP & 充值表
-- =============================================================

CREATE TABLE IF NOT EXISTS `tc_vip_package` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '套餐名称',
    `price` DECIMAL(10, 2) NOT NULL COMMENT '售价',
    `original_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT '原价',
    `points` INT NOT NULL DEFAULT 0 COMMENT '赠送积分',
    `duration` INT NOT NULL DEFAULT 0 COMMENT '有效期(天)，0表示永久',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '套餐描述',
    `is_hot` TINYINT NOT NULL DEFAULT 0 COMMENT '是否热门 0否 1是',
    `is_recommend` TINYINT NOT NULL DEFAULT 0 COMMENT '是否推荐 0否 1是',
    `sort` INT NOT NULL DEFAULT 0 COMMENT '排序，越小越靠前',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态 0下架 1上架',
    `is_deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '是否删除 0否 1是',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VIP套餐表';

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

CREATE TABLE IF NOT EXISTS `tc_recharge_order` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_no` VARCHAR(64) NOT NULL COMMENT '订单号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `amount` DECIMAL(10, 2) NOT NULL COMMENT '充值金额',
    `points` INT NOT NULL COMMENT '获得积分',
    `gift_points` INT NOT NULL DEFAULT 0 COMMENT '赠送积分',
    `pay_type` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '支付方式 alipay/wechat',
    `pay_no` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '第三方支付单号',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0待支付 1已支付 2已取消 3已退款',
    `paid_at` DATETIME NULL COMMENT '支付时间',
    `expire_time` DATETIME NULL COMMENT '订单过期时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_order_no` (`order_no`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值订单表';

CREATE TABLE IF NOT EXISTS `tc_payment_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pay_type` VARCHAR(20) NOT NULL COMMENT '支付类型 alipay/wechat',
    `app_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'AppID',
    `merchant_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '商户号',
    `private_key` TEXT COMMENT '私钥',
    `public_key` TEXT COMMENT '公钥/证书',
    `notify_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '回调地址',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_pay_type` (`pay_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付配置表';

-- =============================================================
-- [4] 业务功能表
-- =============================================================

CREATE TABLE IF NOT EXISTS `tc_bazi_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '姓名',
    `gender` TINYINT NOT NULL DEFAULT 0 COMMENT '性别 1男 2女',
    `birth_date` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '出生日期时间文本',
    `birth_time` TIME NULL DEFAULT NULL COMMENT '出生时分',
    `location` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '出生地点',
    `birth_place` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '出生地点（兼容旧字段）',
    `longitude` DECIMAL(10, 6) NULL COMMENT '经度',
    `latitude` DECIMAL(10, 6) NULL COMMENT '纬度',
    `year_gan` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '年干',
    `year_zhi` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '年支',
    `month_gan` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '月干',
    `month_zhi` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '月支',
    `day_gan` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '日干',
    `day_zhi` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '日支',
    `hour_gan` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '时干',
    `hour_zhi` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '时支',
    `year_pillar` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '年柱',
    `month_pillar` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '月柱',
    `day_pillar` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '日柱',
    `hour_pillar` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '时柱',
    `analysis` TEXT NULL COMMENT '文本分析摘要',
    `report_data` JSON NULL COMMENT '完整报告数据',
    `dayun` JSON NULL COMMENT '大运数据',
    `liunian` JSON NULL COMMENT '流年数据',
    `is_first` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否首次排盘',
    `is_paid` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否付费解锁完整报告',
    `points_used` INT NOT NULL DEFAULT 0 COMMENT '消耗积分',
    `is_public` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否公开分享',
    `share_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分享码',
    `view_count` INT NOT NULL DEFAULT 0 COMMENT '查看次数',
    `ai_analysis_model` VARCHAR(50) DEFAULT '' COMMENT 'AI分析模型',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user_id` (`user_id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='八字排盘记录表';

CREATE TABLE IF NOT EXISTS `tc_hehun_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `male_name` VARCHAR(50) DEFAULT '' COMMENT '男方姓名',
    `male_birth_date` DATE NOT NULL COMMENT '男方出生日期',
    `male_birth_time` TIME NOT NULL DEFAULT '00:00:00' COMMENT '男方出生时间',
    `female_name` VARCHAR(50) DEFAULT '' COMMENT '女方姓名',
    `female_birth_date` DATE NOT NULL COMMENT '女方出生日期',
    `female_birth_time` TIME NOT NULL DEFAULT '00:00:00' COMMENT '女方出生时间',
    `male_bazi` JSON NULL COMMENT '男方八字JSON',
    `female_bazi` JSON NULL COMMENT '女方八字JSON',
    `bazi_match` JSON NULL COMMENT '八字合婚分析',
    `wuxing_match` JSON NULL COMMENT '五行匹配分析',
    `result` JSON NULL COMMENT '完整合婚结果JSON',
    `analysis` TEXT NULL COMMENT '合婚文字分析',
    `score` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '合婚评分 0-100',
    `level` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '评级',
    `ai_analysis` JSON NULL COMMENT 'AI深度分析JSON',
    `is_ai_analysis` TINYINT NOT NULL DEFAULT 0 COMMENT '是否含AI分析',
    `points_used` INT NOT NULL DEFAULT 0 COMMENT '消耗积分',
    `is_premium` TINYINT NOT NULL DEFAULT 0 COMMENT '是否完整付费报告',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='八字合婚记录表';

CREATE TABLE IF NOT EXISTS `tc_qiming_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `surname` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '姓氏',
    `gender` TINYINT NOT NULL DEFAULT 1 COMMENT '性别 1男 2女',
    `birth_date` DATE NOT NULL COMMENT '出生日期',
    `birth_time` TIME NOT NULL DEFAULT '00:00:00' COMMENT '出生时间',
    `birth_place` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '出生地点',
    `style` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '取名风格',
    `names` JSON NULL COMMENT '推荐名字列表',
    `ai_analysis` TEXT NULL COMMENT 'AI分析',
    `points_used` INT NOT NULL DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='取名记录表';

CREATE TABLE IF NOT EXISTS `tc_daily_fortune` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `fortune_date` DATE NOT NULL COMMENT '运势日期',
    `overall_score` TINYINT DEFAULT 0 COMMENT '综合评分',
    `love_score` TINYINT DEFAULT 0 COMMENT '感情评分',
    `career_score` TINYINT DEFAULT 0 COMMENT '事业评分',
    `wealth_score` TINYINT DEFAULT 0 COMMENT '财运评分',
    `health_score` TINYINT DEFAULT 0 COMMENT '健康评分',
    `lucky_color` VARCHAR(20) DEFAULT '' COMMENT '幸运颜色',
    `lucky_number` VARCHAR(20) DEFAULT '' COMMENT '幸运数字',
    `lucky_direction` VARCHAR(20) DEFAULT '' COMMENT '幸运方位',
    `summary` TEXT COMMENT '运势摘要',
    `detail` JSON NULL COMMENT '详细运势数据',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_date` (`user_id`, `fortune_date`),
    INDEX `idx_fortune_date` (`fortune_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日运势表';

CREATE TABLE IF NOT EXISTS `tc_yearly_fortune` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `year` SMALLINT NOT NULL COMMENT '流年年份',
    `bazi_record_id` INT UNSIGNED DEFAULT 0 COMMENT '关联八字记录ID',
    `overall_score` TINYINT DEFAULT 0 COMMENT '综合评分',
    `career_score` TINYINT DEFAULT 0 COMMENT '事业评分',
    `wealth_score` TINYINT DEFAULT 0 COMMENT '财运评分',
    `love_score` TINYINT DEFAULT 0 COMMENT '感情评分',
    `health_score` TINYINT DEFAULT 0 COMMENT '健康评分',
    `summary` TEXT COMMENT '流年摘要',
    `detail` JSON NULL COMMENT '详细流年数据',
    `ai_analysis` TEXT NULL COMMENT 'AI深度分析',
    `points_used` INT NOT NULL DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_year` (`user_id`, `year`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流年运势表';

CREATE TABLE IF NOT EXISTS `tc_tarot_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `spread_type` VARCHAR(50) NOT NULL DEFAULT 'single' COMMENT '牌阵类型',
    `topic` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '占卜主题',
    `question` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '占卜问题',
    `cards` JSON NULL COMMENT '抽到的牌',
    `interpretation` TEXT NULL COMMENT '解读',
    `ai_analysis` TEXT NULL COMMENT 'AI分析',
    `points_used` INT NOT NULL DEFAULT 0 COMMENT '消耗积分',
    `is_free` TINYINT NOT NULL DEFAULT 0 COMMENT '是否免费',
    `is_public` TINYINT NOT NULL DEFAULT 0 COMMENT '是否公开',
    `share_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分享码',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '查看次数',
    `client_ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '客户端IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗占卜记录表';

CREATE TABLE IF NOT EXISTS `tc_liuyao_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `question` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '占卜问题',
    `method` VARCHAR(30) NOT NULL DEFAULT 'coin' COMMENT '起卦方式',
    `hexagram_original` JSON NULL COMMENT '本卦',
    `hexagram_changed` JSON NULL COMMENT '变卦',
    `hexagram_mutual` JSON NULL COMMENT '互卦',
    `lines` JSON NULL COMMENT '爻位数据',
    `moving_lines` JSON NULL COMMENT '动爻',
    `analysis` TEXT NULL COMMENT '分析',
    `ai_analysis` TEXT NULL COMMENT 'AI分析',
    `points_used` INT NOT NULL DEFAULT 0 COMMENT '消耗积分',
    `is_free` TINYINT NOT NULL DEFAULT 0 COMMENT '是否免费',
    `is_public` TINYINT NOT NULL DEFAULT 0 COMMENT '是否公开',
    `share_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分享码',
    `client_ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '客户端IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='六爻占卜记录表';

-- =============================================================
-- [5] 短信 & 邀请 & 反馈表
-- =============================================================

CREATE TABLE IF NOT EXISTS `tc_sms_code` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
    `code` VARCHAR(10) NOT NULL COMMENT '验证码',
    `type` VARCHAR(20) NOT NULL DEFAULT 'login' COMMENT '类型 login/register/reset',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '发送IP',
    `used` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已使用',
    `expired_at` DATETIME NOT NULL COMMENT '过期时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_phone` (`phone`),
    INDEX `idx_expired_at` (`expired_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信验证码表';

CREATE TABLE IF NOT EXISTS `tc_sms_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `provider` VARCHAR(20) NOT NULL COMMENT '服务商 aliyun/tencent',
    `access_key` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'AccessKey',
    `secret_key` VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'SecretKey',
    `sign_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '短信签名',
    `template_login` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '登录模板ID',
    `template_register` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '注册模板ID',
    `template_reset` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '重置密码模板ID',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信配置表';

CREATE TABLE IF NOT EXISTS `tc_invite_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inviter_id` INT UNSIGNED NOT NULL COMMENT '邀请人ID',
    `invite_code` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '使用的邀请码',
    `invitee_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '被邀请人ID',
    `invitee_phone` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '被邀请人手机号',
    `reward_points` INT NOT NULL DEFAULT 0 COMMENT '奖励积分',
    `reward_time` DATETIME NULL COMMENT '奖励发放时间',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0待奖励 1已奖励',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_invitee` (`invitee_id`),
    INDEX `idx_inviter_id` (`inviter_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邀请记录表';

CREATE TABLE IF NOT EXISTS `tc_feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL DEFAULT 'general' COMMENT '类型 bug/suggestion/general',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '标题',
    `content` TEXT NOT NULL COMMENT '内容',
    `images` JSON NULL COMMENT '图片列表',
    `contact` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '联系方式',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0待处理 1处理中 2已解决 3已关闭',
    `reply` TEXT NULL COMMENT '回复内容',
    `replied_at` DATETIME NULL COMMENT '回复时间',
    `handler_id` INT UNSIGNED DEFAULT 0 COMMENT '处理人ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户反馈表';

-- =============================================================
-- [6] 系统配置表
-- =============================================================

-- tc_system_config 已合并到 tc_system_configs（见 [21] 节），此处不再单独建表

CREATE TABLE IF NOT EXISTS `tc_feature_switch` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(50) NOT NULL COMMENT '功能标识',
    `name` VARCHAR(100) NOT NULL COMMENT '功能名称',
    `enabled` TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    `description` VARCHAR(255) DEFAULT '' COMMENT '功能描述',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='功能开关表';

-- =============================================================
-- [7] 管理员相关表
-- =============================================================

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

CREATE TABLE IF NOT EXISTS `tc_admin_role` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '角色标识',
    `description` VARCHAR(255) DEFAULT '' COMMENT '角色描述',
    `is_super` TINYINT DEFAULT 0 COMMENT '是否超级管理员',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色表';

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

CREATE TABLE IF NOT EXISTS `tc_admin_role_permission` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `permission_id` INT UNSIGNED NOT NULL COMMENT '权限ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_role_permission` (`role_id`, `permission_id`),
    INDEX `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限关联表';

CREATE TABLE IF NOT EXISTS `tc_admin_user_role` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_admin_role` (`admin_id`, `role_id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色关联表';

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
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_module` (`module`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员操作日志表';

-- =============================================================
-- [8] 日志表（字段与代码写入逻辑严格对齐）
-- =============================================================

CREATE TABLE IF NOT EXISTS `tc_admin_login_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID',
    `username` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '登录用户名',
    `ip` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '登录IP',
    `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'User-Agent',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '登录状态 1成功 0失败',
    `fail_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '失败原因',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '登录时间',
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员登录日志表';

CREATE TABLE IF NOT EXISTS `tc_api_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `path` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '请求路径',
    `method` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '请求方法',
    `status` INT NOT NULL DEFAULT 200 COMMENT 'HTTP状态码',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID（0为未登录）',
    `ip` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '请求IP',
    `request_body` TEXT NULL COMMENT '请求体（脱敏）',
    `response_code` INT NOT NULL DEFAULT 200 COMMENT '业务响应码',
    `duration_ms` INT NOT NULL DEFAULT 0 COMMENT '耗时（毫秒）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '请求时间',
    KEY `idx_path` (`path`(191)),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API请求日志表';

-- =============================================================
-- [9] 内容管理表
-- =============================================================

CREATE TABLE IF NOT EXISTS `tc_faq` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category` VARCHAR(50) DEFAULT '' COMMENT '分类',
    `question` VARCHAR(500) NOT NULL COMMENT '问题',
    `answer` TEXT NOT NULL COMMENT '答案',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `is_enabled` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `view_count` INT UNSIGNED DEFAULT 0 COMMENT '浏览次数',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='FAQ表';

CREATE TABLE IF NOT EXISTS `tc_ai_prompt` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '提示词名称',
    `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '提示词标题（前台展示名）',
    `type` VARCHAR(50) NOT NULL COMMENT '类型 bazi/fortune/tarot/etc',
    `prompt` TEXT NOT NULL COMMENT '提示词内容',
    `content` TEXT NULL COMMENT '提示词内容（扩展字段）',
    `variables` JSON NULL COMMENT '变量列表',
    `status` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `is_default` TINYINT NOT NULL DEFAULT 0 COMMENT '是否默认提示词',
    `is_deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已删除',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_name` (`name`),
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI提示词表';

CREATE TABLE IF NOT EXISTS `tc_article_category` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '分类名称',
    `slug` VARCHAR(120) NOT NULL COMMENT '标识',
    `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '描述',
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父分类ID',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章分类表';

CREATE TABLE IF NOT EXISTS `tc_article` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL COMMENT '分类ID',
    `title` VARCHAR(200) NOT NULL COMMENT '标题',
    `slug` VARCHAR(160) NOT NULL COMMENT '标识',
    `summary` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '摘要',
    `content` LONGTEXT NOT NULL COMMENT '内容',
    `thumbnail` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '缩略图',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0草稿 1发布',
    `is_hot` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否热门',
    `author_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '作者ID',
    `author_name` VARCHAR(80) NOT NULL DEFAULT '' COMMENT '作者名',
    `published_at` DATETIME NULL DEFAULT NULL COMMENT '发布时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_category` (`category_id`),
    FULLTEXT KEY `ft_content` (`title`, `summary`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章表';

-- =============================================================
-- [10] 通知推送表
-- =============================================================

CREATE TABLE IF NOT EXISTS `tc_notification` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '通知类型',
    `title` VARCHAR(200) NOT NULL COMMENT '标题',
    `content` TEXT NULL COMMENT '内容',
    `data` JSON NULL COMMENT '附加数据',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已读',
    `read_at` DATETIME NULL DEFAULT NULL COMMENT '阅读时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`),
    KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='站内通知表';

CREATE TABLE IF NOT EXISTS `tc_notification_setting` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID，0=系统默认配置',
    `daily_fortune` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '每日运势通知',
    `system_notice` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '系统公告通知',
    `activity` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '活动通知',
    `recharge` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '充值通知',
    `points_change` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '积分变动通知',
    `push_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '推送开关',
    `sound_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '声音开关',
    `vibration_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '震动开关',
    `quiet_hours_start` CHAR(5) NOT NULL DEFAULT '22:00' COMMENT '免打扰开始时间',
    `quiet_hours_end` CHAR(5) NOT NULL DEFAULT '08:00' COMMENT '免打扰结束时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户通知设置表';

CREATE TABLE IF NOT EXISTS `tc_push_device` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `platform` VARCHAR(20) NOT NULL COMMENT '平台 ios/android/web',
    `device_token` VARCHAR(500) NOT NULL COMMENT '设备Token',
    `device_id` VARCHAR(255) NOT NULL COMMENT '设备ID',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否活跃',
    `last_active_at` DATETIME NULL DEFAULT NULL COMMENT '最后活跃时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_device` (`device_id`),
    KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户推送设备表';

-- =============================================================
-- [11] 黄历 & 神煞表
-- =============================================================

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
    `ganzhi` VARCHAR(10) DEFAULT '' COMMENT '干支（兼容字段）',
    `nayin` VARCHAR(50) DEFAULT '' COMMENT '纳音',
    `wuxing` VARCHAR(50) DEFAULT '' COMMENT '五行',
    `constellation` VARCHAR(20) DEFAULT '' COMMENT '星座',
    `xingsu` VARCHAR(20) DEFAULT '' COMMENT '星宿',
    `pengzu` VARCHAR(100) DEFAULT '' COMMENT '彭祖百忌',
    `yi` JSON NULL COMMENT '宜',
    `ji` JSON NULL COMMENT '忌',
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

-- =============================================================
-- [12] SEO 相关表
-- =============================================================

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
    `search_volume` INT DEFAULT 0 COMMENT '月搜索量',
    `competition` VARCHAR(20) DEFAULT 'medium' COMMENT '竞争程度',
    `is_target` TINYINT DEFAULT 1 COMMENT '是否目标关键词',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_keyword` (`keyword`),
    KEY `idx_category` (`category`)
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
    UNIQUE KEY `uk_url` (`url`)
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

-- =============================================================
-- [13] 统计表
-- =============================================================

CREATE TABLE IF NOT EXISTS `tc_site_daily_stats` (
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

-- =============================================================
-- [14] 反作弊表
-- =============================================================

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_event` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '事件类型',
    `level` VARCHAR(20) DEFAULT 'medium' COMMENT '风险等级',
    `detail` JSON NULL COMMENT '详细信息',
    `ip` VARCHAR(45) DEFAULT '' COMMENT 'IP地址',
    `device_id` VARCHAR(100) DEFAULT '' COMMENT '设备ID',
    `status` TINYINT DEFAULT 0 COMMENT '状态 0待处理 1已处理',
    `handler_id` INT UNSIGNED DEFAULT 0 COMMENT '处理人ID',
    `handle_remark` VARCHAR(255) DEFAULT '' COMMENT '处理备注',
    `handle_at` DATETIME NULL COMMENT '处理时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反作弊风险事件表';

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_rule` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '规则名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '规则代码',
    `type` VARCHAR(50) NOT NULL COMMENT '规则类型',
    `config` JSON NULL COMMENT '配置',
    `action` VARCHAR(50) DEFAULT 'log' COMMENT '动作',
    `status` TINYINT DEFAULT 1 COMMENT '状态',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反作弊规则表';

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_device` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `device_id` VARCHAR(100) NOT NULL UNIQUE COMMENT '设备ID',
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '用户ID',
    `platform` VARCHAR(20) DEFAULT '' COMMENT '平台',
    `info` JSON NULL COMMENT '设备信息',
    `is_blocked` TINYINT DEFAULT 0 COMMENT '是否拉黑',
    `block_reason` VARCHAR(255) DEFAULT '' COMMENT '拉黑原因',
    `last_active_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备指纹表';

-- =============================================================
-- [15] 文件上传表
-- =============================================================

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

-- =============================================================
-- [16] 塔罗牌库表
-- =============================================================

CREATE TABLE IF NOT EXISTS `tc_tarot_card` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '牌名',
    `name_en` VARCHAR(100) DEFAULT '' COMMENT '英文名',
    `type` VARCHAR(20) NOT NULL COMMENT '类型 major/minor',
    `is_enabled` TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    `is_major` TINYINT NOT NULL DEFAULT 0 COMMENT '是否大阿卡纳',
    `sort` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `emoji` VARCHAR(10) DEFAULT '' COMMENT '表情符号',
    `color` VARCHAR(20) DEFAULT '' COMMENT '主题色',
    `meaning` TEXT COMMENT '正位含义（主字段）',
    `love_meaning` TEXT COMMENT '爱情正位含义',
    `love_reversed` TEXT COMMENT '爱情逆位含义',
    `career_meaning` TEXT COMMENT '事业正位含义',
    `career_reversed` TEXT COMMENT '事业逆位含义',
    `health_meaning` TEXT COMMENT '健康正位含义',
    `health_reversed` TEXT COMMENT '健康逆位含义',
    `wealth_meaning` TEXT COMMENT '财运正位含义',
    `wealth_reversed` TEXT COMMENT '财运逆位含义',
    `suit` VARCHAR(20) DEFAULT '' COMMENT '花色 cups/wands/swords/pentacles',
    `number` INT DEFAULT 0 COMMENT '数字',
    `image` VARCHAR(500) DEFAULT '' COMMENT '图片',
    `upright_meaning` TEXT COMMENT '正位含义（兼容旧字段）',
    `reversed_meaning` TEXT COMMENT '逆位含义（兼容旧字段）',
    `keywords` VARCHAR(500) DEFAULT '' COMMENT '关键词',
    `element` VARCHAR(20) DEFAULT '' COMMENT '元素',
    `planet` VARCHAR(20) DEFAULT '' COMMENT '行星',
    `zodiac` VARCHAR(20) DEFAULT '' COMMENT '星座',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_type` (`type`),
    INDEX `idx_suit` (`suit`),
    INDEX `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗牌表';

-- =============================================================
-- [17] 初始数据
-- =============================================================

-- 管理员角色
INSERT INTO `tc_admin_role` (`name`, `code`, `description`, `is_super`) VALUES
('超级管理员', 'super_admin', '拥有所有权限', 1),
('普通管理员', 'normal_admin', '拥有常规管理权限', 0),
('运营人员', 'operator', '仅限查看和部分编辑权限', 0),
('客服人员', 'customer_service', '仅限处理用户反馈和订单', 0)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `is_super` = VALUES(`is_super`);

-- 默认管理员账号 (admin / admin123)
INSERT INTO `tc_admin` (`username`, `password`, `nickname`, `email`, `status`)
VALUES ('admin', '$2a$10$69ekdLT1xe/Niyazb/kBSegJPAx0uJf6uhq5mz.LfZ.2rJ5YtUjoC', '系统管理员', 'admin@example.com', 1)
ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`),
    `nickname` = VALUES(`nickname`),
    `status` = VALUES(`status`),
    `updated_at` = CURRENT_TIMESTAMP;

-- 绑定超级管理员角色
INSERT IGNORE INTO `tc_admin_user_role` (`admin_id`, `role_id`)
SELECT a.`id`, r.`id`
FROM `tc_admin` a
JOIN `tc_admin_role` r ON r.`code` = 'super_admin'
WHERE a.`username` = 'admin';

-- 同步 role_id 字段
UPDATE `tc_admin` a
JOIN `tc_admin_role` r ON r.`code` = 'super_admin'
SET a.`role_id` = r.`id`
WHERE a.`username` = 'admin' AND (a.`role_id` = 0 OR a.`role_id` IS NULL);

-- 管理员权限
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
('营销管理', 'marketing_manage', 'marketing', '管理营销活动')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `module` = VALUES(`module`),
    `description` = VALUES(`description`);

-- 功能开关
INSERT INTO `tc_feature_switch` (`key`, `name`, `enabled`, `description`) VALUES
('vip', 'VIP会员', 1, 'VIP会员功能'),
('points', '积分系统', 1, '积分系统功能'),
('ai_analysis', 'AI解盘', 1, 'AI智能分析功能'),
('recharge', '充值功能', 1, '积分充值功能'),
('share', '分享功能', 1, '分享和邀请功能'),
('hehun', '八字合婚', 1, '八字合婚功能'),
('qiming', '取名建议', 1, '取名建议功能'),
('daily_fortune', '每日运势', 1, '每日运势功能'),
('yearly_fortune', '流年运势', 1, '流年运势功能'),
('tarot', '塔罗占卜', 1, '塔罗占卜功能'),
('liuyao', '六爻占卜', 1, '六爻占卜功能'),
('feedback', '用户反馈', 1, '用户反馈功能'),
('checkin', '每日签到', 1, '每日签到功能'),
('invite', '邀请好友', 1, '邀请好友功能')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `enabled` = VALUES(`enabled`),
    `description` = VALUES(`description`);

-- 通用系统配置（已迁移至 tc_system_configs，见 [21] 节）

-- 积分规则初始数据
INSERT INTO `tc_points_rule` (`type`, `rule_name`, `points`, `description`, `status`) VALUES
('register',          '新用户注册',     100,  '新用户注册赠送积分',         1),
('sign_in',           '每日签到',       10,   '每日签到获得积分奖励',       1),
('sign_in_continuous','连续签到奖励',   5,    '连续签到额外奖励积分',       1),
('invite_friend',     '邀请好友',       20,   '邀请好友注册获得积分',       1),
('complete_profile',  '完善资料',       20,   '首次完善个人资料获得积分',   1),
('share',             '分享奖励',       5,    '分享内容获得积分',           1),
('bazi',              '八字排盘消耗',   -30,  '使用八字排盘消耗积分',       1),
('bazi_ai',           '八字AI解盘消耗', -50,  '使用AI深度解盘消耗积分',     1),
('tarot',             '塔罗占卜消耗',   -20,  '使用塔罗占卜消耗积分',       1),
('tarot_ai',          '塔罗AI解读消耗', -40,  '使用AI塔罗解读消耗积分',     1),
('liuyao',            '六爻占卜消耗',   -25,  '使用六爻占卜消耗积分',       1),
('hehun',             '合婚分析消耗',   -80,  '使用八字合婚消耗积分',       1),
('qiming',            '取名建议消耗',   -100, '使用取名建议消耗积分',       1),
('recharge',          '积分充值',       0,    '充值获得积分（数值由档位决定）', 1)
ON DUPLICATE KEY UPDATE
    `rule_name` = VALUES(`rule_name`),
    `description` = VALUES(`description`),
    `status` = VALUES(`status`);

-- SEO 默认配置
INSERT INTO `tc_seo_config` (`route`, `title`, `description`, `keywords`, `image`, `priority`, `changefreq`, `is_active`) VALUES
('/', '太初命理 - 专业八字排盘_塔罗占卜_每日运势', '太初命理是专业的AI智能命理分析平台，提供八字排盘、塔罗占卜、每日运势等服务。', JSON_ARRAY('八字排盘', '塔罗占卜', '每日运势', '命理分析'), '/images/og-home.jpg', 1.0, 'daily', 1),
('/bazi', '免费八字排盘_在线生辰八字测算', '免费在线八字排盘工具，输入出生日期即可生成专业八字命盘。', JSON_ARRAY('八字排盘', '免费八字', '生辰八字'), '/images/og-bazi.jpg', 0.9, 'weekly', 1),
('/tarot', '免费塔罗牌占卜_在线塔罗测试', '免费在线塔罗牌占卜，涵盖爱情、事业、财运、运势等多个维度。', JSON_ARRAY('塔罗占卜', '塔罗牌', '免费塔罗'), '/images/og-tarot.jpg', 0.9, 'weekly', 1),
('/daily', '今日运势查询_每日星座运势_黄历宜忌', '查看今日运势，包含十二星座每日运势、黄历宜忌、时辰吉凶。', JSON_ARRAY('今日运势', '每日运势', '黄历查询'), '/images/og-daily.jpg', 0.9, 'daily', 1)
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `keywords` = VALUES(`keywords`),
    `updated_at` = CURRENT_TIMESTAMP;

-- robots 默认配置
INSERT INTO `tc_seo_robots` (`user_agent`, `rules`, `crawl_delay`, `sitemap_url`, `is_active`, `sort_order`)
SELECT '*', JSON_ARRAY(JSON_OBJECT('type', 'allow', 'path', '/'), JSON_OBJECT('type', 'disallow', 'path', '/admin/'), JSON_OBJECT('type', 'disallow', 'path', '/api/')), 1, 'https://taichu.chat/sitemap.xml', 1, 0
WHERE NOT EXISTS (SELECT 1 FROM `tc_seo_robots` LIMIT 1);

-- 神煞种子数据
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
    `gan_rules` = VALUES(`gan_rules`),
    `zhi_rules` = VALUES(`zhi_rules`),
    `sort` = VALUES(`sort`),
    `status` = VALUES(`status`),
    `updated_at` = CURRENT_TIMESTAMP;

-- 塔罗牌基础数据（22张大阿卡纳）
INSERT INTO `tc_tarot_card` (`name`, `name_en`, `type`, `number`, `element`, `keywords`, `upright_meaning`, `reversed_meaning`) VALUES
('愚者', 'The Fool', 'major', 0, 'air', '开始、自由、冒险', '新的开始，冒险精神，不受束缚，信任直觉，充满潜力', '鲁莽冲动，缺乏计划，盲目乐观，轻率决定，冒险过度'),
('魔术师', 'The Magician', 'major', 1, 'air', '创造、力量、行动', '拥有资源和能力，将想法变为现实，主动创造，充满信心', '欺骗、操纵，滥用权力，缺乏信心，资源浪费，能力不足'),
('女祭司', 'The High Priestess', 'major', 2, 'water', '直觉、神秘、智慧', '直觉敏锐，内在智慧，神秘力量，潜意识觉醒，静待时机', '忽视直觉，表面判断，秘密被揭露，缺乏耐心，直觉受阻'),
('皇后', 'The Empress', 'major', 3, 'earth', '丰饶、创造、母性', '创造力旺盛，享受生活，自然之美，丰盛收获，母性光辉', '创造力受阻，依赖他人，过度放纵，不孕，缺乏灵感'),
('皇帝', 'The Emperor', 'major', 4, 'fire', '权威、结构、控制', '建立秩序，领导能力，稳定结构，理性决策，掌控局面', '专制统治，僵化死板，权力滥用，失去控制，缺乏纪律'),
('教皇', 'The Hierophant', 'major', 5, 'earth', '传统、信仰、教导', '遵循传统，精神指引，寻求指导，学习知识，遵循规则', '打破传统，叛逆精神，非传统方法，缺乏指导，信仰危机'),
('恋人', 'The Lovers', 'major', 6, 'air', '爱情、选择、和谐', '爱情关系，重要选择，价值观一致，和谐关系，情感连接', '关系失衡，错误选择，价值观冲突，不和谐，感情受挫'),
('战车', 'The Chariot', 'major', 7, 'water', '意志、胜利、控制', '坚定意志，克服挑战，取得胜利，自我控制，前进动力', '失控，方向不明，缺乏纪律，失败，意志力薄弱'),
('力量', 'Strength', 'major', 8, 'fire', '勇气、耐心、内在力量', '内在力量，温柔坚持，克服困难，耐心面对，以柔克刚', '软弱无力，缺乏耐心，暴力相向，信心不足，放弃'),
('隐士', 'The Hermit', 'major', 9, 'earth', '内省、独处、寻找', '独自探索，内在寻找，智慧之光，退隐思考，寻求真理', '孤独隔离，拒绝帮助，迷失方向，社交退缩，固步自封'),
('命运之轮', 'Wheel of Fortune', 'major', 10, 'fire', '变化、命运、周期', '命运转折，机会来临，周期变化，好运降临，顺应变化', '厄运降临，抗拒变化，坏运气，错失良机，停滞不前'),
('正义', 'Justice', 'major', 11, 'air', '公正、平衡、真理', '公正裁决，因果报应，寻求真理，平衡各方，理性判断', '不公正，偏见，逃避责任，失衡，错误判断'),
('倒吊人', 'The Hanged Man', 'major', 12, 'water', '牺牲、等待、新视角', '换个角度，暂停等待，牺牲奉献，新视野，顺其自然', '抗拒改变，无意义的牺牲，拖延，固执，错失良机'),
('死神', 'Death', 'major', 13, 'water', '结束、转变、新生', '结束阶段，重大转变，旧事物消亡，新的开始，必要改变', '抗拒结束，停滞不变，害怕改变，僵化，错失重生机会'),
('节制', 'Temperance', 'major', 14, 'fire', '平衡、节制、融合', '平衡和谐，适度节制，融合统一，耐心调和，中庸之道', '极端行为，失衡，过度放纵，缺乏节制，冲突加剧'),
('恶魔', 'The Devil', 'major', 15, 'earth', '束缚、诱惑、物质', '物质束缚，不良习惯，诱惑陷阱，依赖成瘾，受困于欲望', '摆脱束缚，重获自由，打破枷锁，拒绝诱惑，觉醒'),
('塔', 'The Tower', 'major', 16, 'fire', '突变、灾难、觉醒', '突然改变，打破旧有，真相揭露，危机转机，强制觉醒', '避免改变，灾难延迟，内心恐惧，抗拒真相，固执己见'),
('星星', 'The Star', 'major', 17, 'air', '希望、灵感、宁静', '希望之光，灵感涌现，内心宁静，信任未来，精神指引', '希望渺茫，失去信心，绝望，缺乏灵感，迷失方向'),
('月亮', 'The Moon', 'major', 18, 'water', '幻觉、恐惧、潜意识', '潜意识活跃，面对恐惧，不确定中前行，直觉增强，神秘力量', '恐惧消散，真相大白，走出迷雾，幻觉破灭，焦虑减轻'),
('太阳', 'The Sun', 'major', 19, 'fire', '成功、快乐、活力', '成功喜悦，充满活力，光明正大，自信满满，幸福时光', '暂时的阴霾，自信受挫，快乐短暂，过度自信，盲目乐观'),
('审判', 'Judgement', 'major', 20, 'fire', '重生、评价、召唤', '内心召唤，自我评价，重生觉醒，过去的总结，新的开始', '自我怀疑，拒绝召唤，逃避评价，错失机会，固步自封'),
('世界', 'The World', 'major', 21, 'earth', '完成、圆满、成就', '目标达成，圆满完整，旅程结束，成就荣耀，新的开始', '未完成的遗憾，缺乏Closure，目标未达，延迟完成，完美主义')
ON DUPLICATE KEY UPDATE
    `name_en` = VALUES(`name_en`),
    `element` = VALUES(`element`),
    `keywords` = VALUES(`keywords`),
    `upright_meaning` = VALUES(`upright_meaning`),
    `reversed_meaning` = VALUES(`reversed_meaning`);

-- 同步 is_enabled / is_major / sort / meaning 字段（幂等）
UPDATE `tc_tarot_card`
SET
    `is_enabled` = 1,
    `is_major`   = IF(`type` = 'major', 1, 0),
    `sort`       = `id`,
    `meaning`    = COALESCE(NULLIF(`meaning`, ''), `upright_meaning`)
WHERE `meaning` IS NULL OR `meaning` = '';
-- 维度含义数据（感情/事业/健康/财运）见独立文件：
-- database/tarot_dimension_meanings.sql
-- 执行顺序：先执行本文件，再执行 tarot_dimension_meanings.sql


-- FAQ 初始数据
INSERT INTO `tc_faq` (`category`, `question`, `answer`, `sort_order`, `is_enabled`) VALUES
('general', '什么是八字？', '八字是中国传统命理学的重要组成部分，根据一个人出生的年、月、日、时四柱，每柱两个字，共八个字来推算命运。', 1, 1),
('general', '八字排盘准确吗？', '八字排盘是基于传统命理学的计算方法，具有一定的参考价值。但命运也受后天努力、环境等因素影响，仅供参考。', 2, 1),
('general', '需要提供哪些信息？', '进行八字排盘需要提供准确的出生年、月、日、时，以及出生地点（用于计算真太阳时）。', 3, 1),
('points', '如何获得积分？', '您可以通过以下方式获得积分：新用户注册赠送100积分、每日签到、邀请好友、完善个人资料、分享运势等。', 1, 1),
('points', '积分有什么用？', '积分可用于解锁详细的命理报告、流年运势分析、八字合婚、取名建议等高级功能。', 2, 1),
('points', '积分会过期吗？', '目前积分长期有效，不会过期。但请注意查看平台公告，如有调整会提前通知。', 3, 1),
('vip', 'VIP有什么特权？', 'VIP会员可享受：无限次排盘、所有报告免费解锁、积分充值折扣、专属客服、新功能优先体验等特权。', 1, 1),
('vip', 'VIP如何购买？', '您可以在个人中心的VIP页面选择合适的套餐进行购买，支持微信支付等多种支付方式。', 2, 1),
('vip', 'VIP到期后会怎样？', 'VIP到期后将恢复普通用户权限，已解锁的内容仍然可以查看，但新排盘将受相应限制。', 3, 1)
ON DUPLICATE KEY UPDATE
    `answer` = VALUES(`answer`),
    `sort_order` = VALUES(`sort_order`),
    `is_enabled` = VALUES(`is_enabled`);

-- AI 提示词初始数据
INSERT INTO `tc_ai_prompt` (`name`, `type`, `prompt`, `variables`, `status`) VALUES
('八字基础分析', 'bazi',
'你是一位专业的命理大师。请根据以下八字信息进行分析：
年柱：{{year_pillar}}，月柱：{{month_pillar}}，日柱：{{day_pillar}}，时柱：{{hour_pillar}}，性别：{{gender}}

请从以下几个方面进行分析：
1. 五行分析（五行分布和强弱）
2. 十神分析（十神配置和特点）
3. 日主分析（日主强弱和特点）
4. 性格特征
5. 事业财运
6. 感情婚姻
7. 健康提示

请用通俗易懂的语言，给出详细且专业的分析。',
'["year_pillar","month_pillar","day_pillar","hour_pillar","gender"]', 1),

('流年运势分析', 'fortune',
'你是一位专业的命理大师。请根据以下信息分析流年运势：
八字：{{bazi}}，流年：{{year}} {{year_ganzi}}，大运：{{dayun}}

请分析：1.整体运势评分（1-10分）2.事业运势 3.财运分析 4.感情运势 5.健康状况 6.重要提示和建议 7.吉凶月份预测

请给出专业且实用的建议。',
'["bazi","year","year_ganzi","dayun"]', 1),

('塔罗牌解读', 'tarot',
'你是一位专业的塔罗牌师。请解读以下塔罗牌：
牌名：{{card_name}}，位置：{{position}}，问题：{{question}}

请从以下角度解读：1.牌面基本含义 2.在本情境中的具体含义 3.给出的建议和指引 4.需要注意的方面

请用温暖且专业的语言进行解读。',
'["card_name","position","question"]', 1)
ON DUPLICATE KEY UPDATE
    `prompt` = VALUES(`prompt`),
    `variables` = VALUES(`variables`),
    `status` = VALUES(`status`);

-- =============================================================
-- [18] 已废弃（原别名兼容表，现已统一使用 tc_ 前缀，此节保留为空）
-- =============================================================

-- =============================================================
-- [19] 内容管理相关表
-- =============================================================

-- 每日运势模板表
CREATE TABLE IF NOT EXISTS `tc_daily_fortune_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(30) NOT NULL COMMENT '类型: overall/love/career/wealth/health/advice/lucky',
    `level` TINYINT NOT NULL COMMENT '等级: 1-5',
    `content` TEXT NOT NULL COMMENT '模板内容',
    `is_enabled` TINYINT DEFAULT 1 COMMENT '是否启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_type_level` (`type`, `level`),
    INDEX `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日运势模板表';

-- 操作日志表（管理端操作记录）
CREATE TABLE IF NOT EXISTS `tc_operation_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED DEFAULT 0 COMMENT '管理员ID',
    `action` VARCHAR(100) DEFAULT '' COMMENT '操作类型',
    `description` TEXT COMMENT '操作描述',
    `ip` VARCHAR(50) DEFAULT '' COMMENT 'IP地址',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- 页面管理表
CREATE TABLE IF NOT EXISTS `tc_pages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` VARCHAR(100) NOT NULL COMMENT '页面唯一标识',
    `title` VARCHAR(200) DEFAULT '' COMMENT '页面标题',
    `content` JSON COMMENT '页面内容(JSON)',
    `settings` JSON COMMENT '页面设置(JSON)',
    `status` VARCHAR(20) DEFAULT 'draft' COMMENT '状态: draft/published',
    `version` INT DEFAULT 1 COMMENT '版本号',
    `updated_by` INT UNSIGNED DEFAULT 0 COMMENT '最后更新人',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_page_id` (`page_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面管理表';

-- 页面草稿表
CREATE TABLE IF NOT EXISTS `tc_page_drafts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` VARCHAR(100) NOT NULL COMMENT '页面标识',
    `admin_id` INT UNSIGNED DEFAULT 0 COMMENT '管理员ID',
    `content` JSON COMMENT '草稿内容(JSON)',
    `settings` JSON COMMENT '草稿设置(JSON)',
    `auto_save` TINYINT DEFAULT 1 COMMENT '是否自动保存',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_page_admin` (`page_id`, `admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面草稿表';

-- 页面回收站表
CREATE TABLE IF NOT EXISTS `tc_page_recycle` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` VARCHAR(100) NOT NULL COMMENT '页面标识',
    `title` VARCHAR(200) DEFAULT '' COMMENT '页面标题',
    `content` JSON COMMENT '页面内容(JSON)',
    `settings` JSON COMMENT '页面设置(JSON)',
    `version` INT DEFAULT 1 COMMENT '版本号',
    `deleted_by` INT UNSIGNED DEFAULT 0 COMMENT '删除人',
    `deleted_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '删除时间',
    INDEX `idx_page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面回收站表';

-- 页面版本历史表
CREATE TABLE IF NOT EXISTS `tc_page_versions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` VARCHAR(100) NOT NULL COMMENT '页面标识',
    `content` JSON COMMENT '版本内容(JSON)',
    `settings` JSON COMMENT '版本设置(JSON)',
    `version` INT DEFAULT 1 COMMENT '版本号',
    `author_id` INT UNSIGNED DEFAULT 0 COMMENT '作者ID',
    `author_name` VARCHAR(50) DEFAULT '' COMMENT '作者名称',
    `description` VARCHAR(255) DEFAULT '' COMMENT '版本描述',
    `auto_save` TINYINT DEFAULT 0 COMMENT '是否自动保存',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_page_id` (`page_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面版本历史表';

-- 塔罗问题模板表
CREATE TABLE IF NOT EXISTS `tc_question_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category` VARCHAR(30) DEFAULT 'life' COMMENT '分类: love/career/study/life/choice',
    `question` VARCHAR(500) NOT NULL COMMENT '问题内容',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `use_count` INT DEFAULT 0 COMMENT '使用次数',
    `is_enabled` TINYINT DEFAULT 1 COMMENT '是否启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗问题模板表';

-- 网站内容表
CREATE TABLE IF NOT EXISTS `tc_site_contents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page` VARCHAR(50) NOT NULL COMMENT '所属页面: home/about/etc',
    `key` VARCHAR(100) NOT NULL COMMENT '内容键名',
    `value` LONGTEXT COMMENT '内容值',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `is_enabled` TINYINT DEFAULT 1 COMMENT '是否启用',
    `created_by` INT UNSIGNED DEFAULT 0 COMMENT '创建人',
    `updated_by` INT UNSIGNED DEFAULT 0 COMMENT '更新人',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_page_key` (`page`, `key`),
    INDEX `idx_page` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='网站内容表';

-- 塔罗牌阵表
CREATE TABLE IF NOT EXISTS `tc_tarot_spreads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '牌阵名称',
    `type` VARCHAR(30) DEFAULT 'single' COMMENT '类型: single/three/celtic/love/career',
    `description` TEXT COMMENT '牌阵描述',
    `card_count` INT DEFAULT 1 COMMENT '牌数',
    `positions` JSON COMMENT '位置定义(JSON)',
    `is_free` TINYINT DEFAULT 0 COMMENT '是否免费',
    `points_required` INT DEFAULT 0 COMMENT '所需积分',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `is_enabled` TINYINT DEFAULT 1 COMMENT '是否启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_type` (`type`),
    INDEX `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗牌阵表';

-- 用户评价表
CREATE TABLE IF NOT EXISTS `tc_testimonials` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_name` VARCHAR(50) DEFAULT '' COMMENT '用户名称',
    `avatar` VARCHAR(500) DEFAULT '' COMMENT '头像',
    `service_type` VARCHAR(30) DEFAULT 'bazi' COMMENT '服务类型: bazi/tarot/daily',
    `content` TEXT NOT NULL COMMENT '评价内容',
    `rating` TINYINT DEFAULT 5 COMMENT '评分(1-5)',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `is_enabled` TINYINT DEFAULT 1 COMMENT '是否启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_service_type` (`service_type`),
    INDEX `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户评价表';

-- =============================================================
-- [20] 反馈关联表
-- =============================================================

-- 反馈分配表
CREATE TABLE IF NOT EXISTS `tc_feedback_assign` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `feedback_id` INT UNSIGNED NOT NULL COMMENT '反馈ID',
    `assigned_by` INT UNSIGNED DEFAULT 0 COMMENT '分配人ID',
    `assigned_to` INT UNSIGNED DEFAULT 0 COMMENT '被分配人ID',
    `note` TEXT COMMENT '分配备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_feedback_id` (`feedback_id`),
    INDEX `idx_assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反馈分配表';

-- 反馈操作日志表
CREATE TABLE IF NOT EXISTS `tc_feedback_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `feedback_id` INT UNSIGNED NOT NULL COMMENT '反馈ID',
    `admin_id` INT UNSIGNED DEFAULT 0 COMMENT '操作管理员ID',
    `action` VARCHAR(50) DEFAULT '' COMMENT '操作类型: assign/reply/status/note',
    `content` TEXT COMMENT '操作内容',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_feedback_id` (`feedback_id`),
    INDEX `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反馈操作日志表';

-- =============================================================
-- [21] 系统统一配置表（AI/支付/短信/推送等）
-- =============================================================

CREATE TABLE IF NOT EXISTS `tc_system_configs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `config_group` VARCHAR(50) NOT NULL COMMENT '配置分组(ai/payment/sms/push)',
    `config_key` VARCHAR(100) NOT NULL COMMENT '配置键',
    `config_value` TEXT COMMENT '配置值',
    `config_type` VARCHAR(20) DEFAULT 'string' COMMENT '值类型: string/int/boolean/json',
    `is_encrypted` TINYINT DEFAULT 0 COMMENT '是否加密存储',
    `is_sensitive` TINYINT DEFAULT 0 COMMENT '是否敏感信息',
    `description` VARCHAR(255) DEFAULT '' COMMENT '描述',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `is_enabled` TINYINT DEFAULT 1 COMMENT '是否启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_group_key` (`config_group`, `config_key`),
    INDEX `idx_group` (`config_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统统一配置表';

-- AI 配置初始数据
INSERT INTO `tc_system_configs` (`config_group`, `config_key`, `config_value`, `config_type`, `is_encrypted`, `is_sensitive`, `description`, `sort_order`, `is_enabled`) VALUES
('ai', 'ai_is_enabled', '0', 'boolean', 0, 0, '是否启用AI服务', 1, 1),
('ai', 'ai_api_url', 'https://aiping.cn/api/v1/chat/completions', 'string', 0, 0, 'AI服务API地址', 2, 1),
('ai', 'ai_api_key', '', 'string', 1, 1, 'AI服务API密钥', 3, 1),
('ai', 'ai_model', 'DeepSeek-V3.2', 'string', 0, 0, 'AI模型名称', 4, 1),
('ai', 'ai_max_tokens', '4096', 'int', 0, 0, '最大生成token数', 5, 1),
('ai', 'ai_temperature', '0.7', 'string', 0, 0, '温度参数(0-2)', 6, 1),
('ai', 'ai_timeout', '60', 'int', 0, 0, '请求超时时间(秒)', 7, 1),
('ai', 'ai_retry_times', '3', 'int', 0, 0, '失败重试次数', 8, 1),
('ai', 'ai_enable_streaming', '1', 'boolean', 0, 0, '是否启用流式输出', 9, 1),
('ai', 'ai_enable_thinking', '0', 'boolean', 0, 0, '是否启用思维链', 10, 1),
('ai', 'ai_enable_bazi', '1', 'boolean', 0, 0, '是否启用八字分析', 11, 1),
('ai', 'ai_enable_tarot', '1', 'boolean', 0, 0, '是否启用塔罗分析', 12, 1),
('ai', 'ai_cost_points', '30', 'int', 0, 0, 'AI解盘消耗积分', 13, 1),
-- 积分消耗配置
('points_cost', 'points_cost_bazi',         '30',   'int',    0, 0, '八字排盘基础积分消耗',       1, 1),
('points_cost', 'points_cost_bazi_ai',      '50',   'int',    0, 0, '八字AI深度解盘积分消耗',     2, 1),
('points_cost', 'points_cost_tarot',        '20',   'int',    0, 0, '塔罗占卜基础积分消耗',       3, 1),
('points_cost', 'points_cost_tarot_ai',     '40',   'int',    0, 0, '塔罗AI解读积分消耗',         4, 1),
('points_cost', 'points_cost_liuyao',       '25',   'int',    0, 0, '六爻占卜积分消耗',           5, 1),
('points_cost', 'points_cost_liuyao_basic', '15',   'int',    0, 0, '六爻基础占卜积分消耗',       6, 1),
('points_cost', 'points_cost_liuyao_professional', '50', 'int', 0, 0, '六爻专业占卜积分消耗',    7, 1),
('points_cost', 'points_cost_hehun',        '80',   'int',    0, 0, '八字合婚基础积分消耗',       8, 1),
('points_cost', 'points_cost_hehun_export', '30',   'int',    0, 0, '合婚导出报告积分消耗',       9, 1),
-- 新用户优惠
('new_user', 'points_free_bazi_first',  '1',  'boolean', 0, 0, '新用户首次八字是否免费', 1, 1),
('new_user', 'points_free_tarot_first', '1',  'boolean', 0, 0, '新用户首次塔罗是否免费', 2, 1),
('new_user', 'new_user_offer_enabled',  '1',  'boolean', 0, 0, '新用户优惠开关',         3, 1),
('new_user', 'new_user_discount',       '50', 'int',     0, 0, '新用户折扣（百分比）',   4, 1),
-- 功能开关
('feature', 'feature_bazi_enabled',    '1', 'boolean', 0, 0, '八字功能开关',     1, 1),
('feature', 'feature_tarot_enabled',   '1', 'boolean', 0, 0, '塔罗功能开关',     2, 1),
('feature', 'feature_liuyao_enabled',  '1', 'boolean', 0, 0, '六爻功能开关',     3, 1),
('feature', 'feature_hehun_enabled',   '1', 'boolean', 0, 0, '合婚功能开关',     4, 1),
('feature', 'feature_daily_enabled',   '1', 'boolean', 0, 0, '每日运势功能开关', 5, 1),
-- VIP配置
('vip', 'vip_price_month',   '68',  'int',     0, 0, 'VIP月度价格（元）',     1, 1),
('vip', 'vip_price_quarter', '168', 'int',     0, 0, 'VIP季度价格（元）',     2, 1),
('vip', 'vip_price_year',    '498', 'int',     0, 0, 'VIP年度价格（元）',     3, 1),
('vip', 'vip_unlock_hehun',  '1',   'boolean', 0, 0, 'VIP是否解锁合婚功能',  4, 1),
('vip', 'vip_month_points',   '500',  'int', 0, 0, 'VIP月度积分兑换价格',  5, 1),
('vip', 'vip_quarter_points', '1200', 'int', 0, 0, 'VIP季度积分兑换价格',  6, 1),
('vip', 'vip_year_points',    '3600', 'int', 0, 0, 'VIP年度积分兑换价格',  7, 1),
-- 积分充值档位
('recharge', 'points_recharge_tiers', '[{"points":100,"price":10},{"points":300,"price":28},{"points":600,"price":50},{"points":1000,"price":78}]', 'json', 0, 0, '积分充值档位配置', 1, 1),
-- 站点信息
('site', 'site_name',      '太初命理',          'string', 0, 0, '站点名称',   1, 1),
('site', 'site_domain',    'taichu.chat',       'string', 0, 0, '站点域名',   2, 1),
('site', 'site_icp',       '',                  'string', 0, 0, 'ICP备案号',  3, 1),
('site', 'site_copyright', '© 2026 太初命理',   'string', 0, 0, '版权信息',   4, 1),
-- 联系方式
('contact', 'contact_wechat', '', 'string', 0, 0, '客服微信号', 1, 1),
('contact', 'contact_email',  '', 'string', 0, 0, '客服邮箱',   2, 1)
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `description` = VALUES(`description`),
    `sort_order` = VALUES(`sort_order`);

-- =============================================================
-- 字典管理表
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_dict_type` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '字典名称',
    `type` VARCHAR(100) NOT NULL UNIQUE COMMENT '字典类型标识',
    `remark` VARCHAR(255) DEFAULT '' COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典类型表';

CREATE TABLE IF NOT EXISTS `tc_dict_data` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `dict_type` VARCHAR(100) NOT NULL COMMENT '字典类型标识',
    `label` VARCHAR(100) NOT NULL COMMENT '字典标签',
    `value` VARCHAR(100) NOT NULL COMMENT '字典值',
    `sort` INT DEFAULT 0 COMMENT '排序',
    `remark` VARCHAR(255) DEFAULT '' COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_dict_type` (`dict_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典数据表';

-- =============================================================
-- VIP会员表（任务4：tc_user_vip 表不存在修复）
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_user_vip` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `vip_type` VARCHAR(20) NOT NULL DEFAULT 'month' COMMENT 'VIP类型：month/quarter/year',
    `start_time` DATETIME NOT NULL COMMENT '开始时间',
    `end_time` DATETIME NOT NULL COMMENT '到期时间',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1有效 2已过期',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_user` (`user_id`),
    INDEX `idx_status_end` (`status`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户VIP会员表';

-- =============================================================
-- 任务日志表（任务5：tc_task_log 表不存在修复）
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_task_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `task_id` VARCHAR(50) NOT NULL COMMENT '任务ID',
    `task_type` VARCHAR(20) NOT NULL DEFAULT 'daily' COMMENT '任务类型：daily/once/unlimited',
    `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_task` (`user_id`, `task_id`),
    INDEX `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务完成日志表';

-- =============================================================
-- 签到记录表（任务6：checkin_record / tc_checkin_record 表不存在修复）
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_checkin_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '签到日期',
    `consecutive_days` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '连续签到天数',
    `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_user_date` (`user_id`, `date`),
    INDEX `idx_user_date` (`user_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日签到记录表';

-- Task.php 中的签到使用 tc_checkin_log 表名，同步补充
CREATE TABLE IF NOT EXISTS `tc_checkin_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `consecutive_days` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '连续签到天数',
    `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到日志表（Task控制器使用）';

-- =============================================================
-- 收尾
-- =============================================================
SET FOREIGN_KEY_CHECKS = 1;

SELECT '🎉 太初命理数据库初始化完成' AS result;
SELECT CONCAT('共创建表: ', COUNT(*), ' 张') AS table_count FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'taichu';

