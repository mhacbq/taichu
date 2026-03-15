-- ============================================================
-- 太初命理网站 - 补充缺少的数据库表
-- 创建时间: 2026-03-15
-- ============================================================

-- 1. 用户反馈表
CREATE TABLE IF NOT EXISTS `feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL DEFAULT 'suggestion' COMMENT '类型: bug/feature/suggestion/other',
    `title` VARCHAR(200) NOT NULL COMMENT '标题',
    `content` TEXT NOT NULL COMMENT '内容',
    `images` JSON NULL COMMENT '图片列表',
    `contact` VARCHAR(100) DEFAULT '' COMMENT '联系方式',
    `status` TINYINT DEFAULT 0 COMMENT '状态: 0待处理 1处理中 2已回复 3已关闭',
    `reply` TEXT COMMENT '回复内容',
    `replied_by` INT UNSIGNED DEFAULT 0 COMMENT '回复人ID',
    `replied_at` DATETIME NULL COMMENT '回复时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户反馈表';

-- 2. 每日签到记录表
CREATE TABLE IF NOT EXISTS `checkin_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '签到日期',
    `consecutive_days` INT DEFAULT 1 COMMENT '连续签到天数',
    `points` INT DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_date` (`user_id`, `date`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日签到记录表';

-- 3. 八字合婚记录表
CREATE TABLE IF NOT EXISTS `hehun_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `male_name` VARCHAR(50) DEFAULT '' COMMENT '男方姓名',
    `male_birth_date` DATE NOT NULL COMMENT '男方出生日期',
    `male_birth_time` TIME NOT NULL COMMENT '男方出生时间',
    `male_lunar` TINYINT DEFAULT 0 COMMENT '男方是否农历: 0公历 1农历',
    `female_name` VARCHAR(50) DEFAULT '' COMMENT '女方姓名',
    `female_birth_date` DATE NOT NULL COMMENT '女方出生日期',
    `female_birth_time` TIME NOT NULL COMMENT '女方出生时间',
    `female_lunar` TINYINT DEFAULT 0 COMMENT '女方是否农历: 0公历 1农历',
    `male_bazi` JSON NULL COMMENT '男方八字数据',
    `female_bazi` JSON NULL COMMENT '女方八字数据',
    `score` INT DEFAULT 0 COMMENT '合婚评分 0-100',
    `result` TEXT COMMENT '合婚结果概述',
    `analysis` JSON NULL COMMENT '详细分析数据',
    `advice` TEXT COMMENT '建议内容',
    `is_public` TINYINT DEFAULT 0 COMMENT '是否公开: 0私密 1公开',
    `share_code` VARCHAR(20) DEFAULT '' COMMENT '分享码',
    `view_count` INT DEFAULT 0 COMMENT '查看次数',
    `points_used` INT DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='八字合婚记录表';

-- 4. 通知相关表
CREATE TABLE IF NOT EXISTS `tc_notification` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '通知类型: system/points/vip/activity/reminder',
    `title` VARCHAR(200) NOT NULL COMMENT '标题',
    `content` TEXT COMMENT '内容',
    `data` JSON NULL COMMENT '附加数据',
    `is_read` TINYINT DEFAULT 0 COMMENT '是否已读: 0未读 1已读',
    `read_at` DATETIME NULL COMMENT '阅读时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知表';

CREATE TABLE IF NOT EXISTS `tc_notification_setting` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '通知类型',
    `enabled` TINYINT DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_type` (`user_id`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知设置表';

CREATE TABLE IF NOT EXISTS `tc_push_device` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `device_id` VARCHAR(255) NOT NULL COMMENT '设备ID',
    `platform` VARCHAR(20) NOT NULL COMMENT '平台: ios/android/web',
    `token` VARCHAR(500) NOT NULL COMMENT '推送令牌',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否激活: 0禁用 1启用',
    `last_used_at` DATETIME NULL COMMENT '最后使用时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送设备表';

-- 5. 积分相关表
CREATE TABLE IF NOT EXISTS `tc_points_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `action` VARCHAR(100) NOT NULL COMMENT '动作说明',
    `points` INT NOT NULL COMMENT '变动积分（正数为增加，负数为减少）',
    `type` VARCHAR(50) DEFAULT '' COMMENT '类型: signin/share/exchange/consume/etc',
    `related_id` INT UNSIGNED DEFAULT 0 COMMENT '关联ID',
    `remark` VARCHAR(500) DEFAULT '' COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分记录表';

CREATE TABLE IF NOT EXISTS `tc_points_exchange` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `exchange_no` VARCHAR(50) NOT NULL UNIQUE COMMENT '兑换单号',
    `product_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
    `product_name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `product_type` VARCHAR(50) NOT NULL COMMENT '商品类型: points/vip/service/physical/coupon',
    `points_cost` INT NOT NULL COMMENT '消耗积分',
    `quantity` INT DEFAULT 1 COMMENT '兑换数量',
    `redeem_code` VARCHAR(50) DEFAULT '' COMMENT '兑换码',
    `valid_until` DATETIME NULL COMMENT '有效期至',
    `completed_at` DATETIME NULL COMMENT '完成时间',
    `cancelled_at` DATETIME NULL COMMENT '取消时间',
    `cancel_reason` VARCHAR(255) DEFAULT '' COMMENT '取消原因',
    `remark` VARCHAR(500) DEFAULT '' COMMENT '备注',
    `status` TINYINT DEFAULT 0 COMMENT '状态: 0待处理 1已完成 2已取消 3失败',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_exchange_no` (`exchange_no`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分兑换记录表';

CREATE TABLE IF NOT EXISTS `tc_points_product` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `description` VARCHAR(500) DEFAULT '' COMMENT '商品简介',
    `detail` TEXT COMMENT '商品详情',
    `type` VARCHAR(50) NOT NULL COMMENT '类型: points/vip/service/physical/coupon',
    `points_price` INT NOT NULL COMMENT '积分价格',
    `original_price` DECIMAL(10,2) DEFAULT 0 COMMENT '原价（元）',
    `stock` INT DEFAULT 0 COMMENT '库存数量，-1为无限',
    `sold_count` INT DEFAULT 0 COMMENT '已售数量',
    `icon` VARCHAR(255) DEFAULT '' COMMENT '商品图标',
    `images` JSON NULL COMMENT '商品图片列表',
    `tags` JSON NULL COMMENT '标签列表',
    `purchase_limit` INT DEFAULT 0 COMMENT '购买限制，0为无限制',
    `validity_days` INT DEFAULT 0 COMMENT '有效期天数，0为永久',
    `usage_guide` TEXT COMMENT '使用说明',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `status` TINYINT DEFAULT 0 COMMENT '状态: 0已下架 1上架中 2已售罄',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分商品表';

-- 插入默认积分商品
INSERT INTO `tc_points_product` (`name`, `description`, `type`, `points_price`, `original_price`, `stock`, `icon`, `tags`, `validity_days`, `status`) VALUES
('100积分充值', '充值100积分到账户', 'points', 100, 10.00, -1, '💎', '["积分"]', 0, 1),
('月度VIP会员', '享受30天VIP特权', 'vip', 500, 19.90, -1, '👑', '["会员","热门"]', 30, 1),
('季度VIP会员', '享受90天VIP特权', 'vip', 1200, 49.00, -1, '👑', '["会员","优惠"]', 90, 1),
('年度VIP会员', '享受365天VIP特权', 'vip', 4000, 168.00, -1, '👑', '["会员","超值"]', 365, 1);

-- 6. 分享记录表
CREATE TABLE IF NOT EXISTS `tc_share_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '分享类型: poster/record/app/page',
    `platform` VARCHAR(50) NOT NULL COMMENT '分享平台: wechat/moments/qq/weibo/copy',
    `content_id` INT UNSIGNED DEFAULT 0 COMMENT '内容ID',
    `content_type` VARCHAR(50) DEFAULT '' COMMENT '内容类型: bazi/hehun/tarot/daily/etc',
    `share_code` VARCHAR(20) DEFAULT '' COMMENT '分享码',
    `points_reward` INT DEFAULT 0 COMMENT '奖励积分',
    `ip` VARCHAR(45) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User-Agent',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分享记录表';

-- 7. 任务相关表
CREATE TABLE IF NOT EXISTS `tc_task_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `task_type` VARCHAR(50) NOT NULL COMMENT '任务类型: daily/weekly/once/limit',
    `task_code` VARCHAR(50) NOT NULL COMMENT '任务编码: first_share/complete_profile/etc',
    `task_name` VARCHAR(100) NOT NULL COMMENT '任务名称',
    `points` INT DEFAULT 0 COMMENT '奖励积分',
    `limit_type` VARCHAR(20) DEFAULT '' COMMENT '限制类型: daily/total/none',
    `limit_count` INT DEFAULT 1 COMMENT '限制次数',
    `completed_count` INT DEFAULT 1 COMMENT '已完成次数',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_task_code` (`task_code`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务记录表';

CREATE TABLE IF NOT EXISTS `tc_checkin_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '签到日期',
    `consecutive_days` INT DEFAULT 1 COMMENT '连续签到天数',
    `base_points` INT DEFAULT 0 COMMENT '基础积分',
    `bonus_points` INT DEFAULT 0 COMMENT '奖励积分',
    `total_points` INT DEFAULT 0 COMMENT '总积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_date` (`user_id`, `date`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到日志表';

-- 8. 邀请记录表
CREATE TABLE IF NOT EXISTS `tc_invite_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inviter_id` INT UNSIGNED NOT NULL COMMENT '邀请人ID',
    `invited_id` INT UNSIGNED DEFAULT 0 COMMENT '被邀请人ID',
    `invite_code` VARCHAR(20) NOT NULL COMMENT '邀请码',
    `points_reward` INT DEFAULT 0 COMMENT '奖励积分',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0无效 1有效',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_inviter_id` (`inviter_id`),
    INDEX `idx_invite_code` (`invite_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邀请记录表';

-- 9. 充值订单表
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
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值订单表';

-- 10. 塔罗占卜记录表
CREATE TABLE IF NOT EXISTS `tc_tarot_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `spread_type` VARCHAR(50) NOT NULL COMMENT '牌阵类型: single/three/celtic/relationship',
    `question` VARCHAR(500) DEFAULT '' COMMENT '占卜问题',
    `cards` JSON NULL COMMENT '抽到的牌（JSON数组）',
    `interpretation` TEXT COMMENT '解读内容',
    `ai_analysis` TEXT COMMENT 'AI深度分析',
    `is_public` TINYINT DEFAULT 0 COMMENT '是否公开: 0私密 1公开',
    `share_code` VARCHAR(50) DEFAULT '' COMMENT '分享码',
    `view_count` INT DEFAULT 0 COMMENT '查看次数',
    `client_ip` VARCHAR(45) DEFAULT '' COMMENT '客户端IP',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_share_code` (`share_code`),
    INDEX `idx_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗占卜记录表';

-- 11. 每日运势表
CREATE TABLE IF NOT EXISTS `tc_daily_fortune` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL COMMENT '日期',
    `lunar_date` VARCHAR(50) DEFAULT '' COMMENT '农历日期',
    `overall_score` INT DEFAULT 70 COMMENT '综合评分 0-100',
    `summary` VARCHAR(500) DEFAULT '' COMMENT '运势摘要',
    `career_score` INT DEFAULT 70 COMMENT '事业评分',
    `career_desc` VARCHAR(500) DEFAULT '' COMMENT '事业描述',
    `wealth_score` INT DEFAULT 70 COMMENT '财富评分',
    `wealth_desc` VARCHAR(500) DEFAULT '' COMMENT '财富描述',
    `love_score` INT DEFAULT 70 COMMENT '感情评分',
    `love_desc` VARCHAR(500) DEFAULT '' COMMENT '感情描述',
    `health_score` INT DEFAULT 70 COMMENT '健康评分',
    `health_desc` VARCHAR(500) DEFAULT '' COMMENT '健康描述',
    `yi` VARCHAR(500) DEFAULT '' COMMENT '宜（宜做的事）',
    `ji` VARCHAR(500) DEFAULT '' COMMENT '忌（忌做的事）',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日运势表';

-- 12. 八字排盘记录表
CREATE TABLE IF NOT EXISTS `tc_bazi_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `birth_date` DATE NOT NULL COMMENT '出生日期',
    `birth_time` TIME DEFAULT NULL COMMENT '出生时间',
    `gender` VARCHAR(10) NOT NULL COMMENT '性别: male/female',
    `location` VARCHAR(100) DEFAULT '' COMMENT '出生地点',
    `is_lunar` TINYINT DEFAULT 0 COMMENT '是否农历: 0公历 1农历',
    `year_gan` VARCHAR(10) DEFAULT '' COMMENT '年干',
    `year_zhi` VARCHAR(10) DEFAULT '' COMMENT '年支',
    `month_gan` VARCHAR(10) DEFAULT '' COMMENT '月干',
    `month_zhi` VARCHAR(10) DEFAULT '' COMMENT '月支',
    `day_gan` VARCHAR(10) DEFAULT '' COMMENT '日干',
    `day_zhi` VARCHAR(10) DEFAULT '' COMMENT '日支',
    `hour_gan` VARCHAR(10) DEFAULT '' COMMENT '时干',
    `hour_zhi` VARCHAR(10) DEFAULT '' COMMENT '时支',
    `bazi_json` JSON NULL COMMENT '完整八字数据（JSON）',
    `analysis` TEXT COMMENT '分析报告',
    `is_first` TINYINT DEFAULT 0 COMMENT '是否首次排盘: 0否 1是',
    `is_public` TINYINT DEFAULT 0 COMMENT '是否公开: 0私密 1公开',
    `share_code` VARCHAR(50) DEFAULT '' COMMENT '分享码',
    `view_count` INT DEFAULT 0 COMMENT '查看次数',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='八字排盘记录表';

-- 13. 支付配置表
CREATE TABLE IF NOT EXISTS `payment_configs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL DEFAULT 'wechat_jsapi' COMMENT '支付类型: wechat_jsapi/wechat_native/alipay',
    `name` VARCHAR(100) DEFAULT '' COMMENT '配置名称',
    `mch_id` VARCHAR(50) DEFAULT '' COMMENT '商户号',
    `app_id` VARCHAR(50) DEFAULT '' COMMENT '应用ID',
    `api_key` VARCHAR(255) DEFAULT '' COMMENT 'API密钥',
    `api_cert` TEXT COMMENT 'API证书内容',
    `api_key_pem` TEXT COMMENT 'API密钥证书内容',
    `notify_url` VARCHAR(500) DEFAULT '' COMMENT '支付回调URL',
    `return_url` VARCHAR(500) DEFAULT '' COMMENT '支付完成返回URL',
    `is_enabled` TINYINT DEFAULT 0 COMMENT '是否启用: 0禁用 1启用',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付配置表';

-- 14. 短信验证码表
CREATE TABLE IF NOT EXISTS `sms_codes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
    `code` VARCHAR(10) NOT NULL COMMENT '验证码',
    `type` VARCHAR(20) NOT NULL COMMENT '类型: register/login/reset/bind/unbind',
    `expire_time` DATETIME NOT NULL COMMENT '过期时间',
    `is_used` TINYINT DEFAULT 0 COMMENT '是否已使用: 0否 1是',
    `ip` VARCHAR(45) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User-Agent',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_phone` (`phone`),
    INDEX `idx_phone_type` (`phone`, `type`),
    INDEX `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信验证码表';

-- 15. 短信配置表
CREATE TABLE IF NOT EXISTS `sms_configs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `provider` VARCHAR(50) NOT NULL DEFAULT 'tencent' COMMENT '服务商: tencent/aliyun',
    `name` VARCHAR(100) DEFAULT '' COMMENT '配置名称',
    `secret_id` VARCHAR(255) DEFAULT '' COMMENT 'Secret ID',
    `secret_key` VARCHAR(255) DEFAULT '' COMMENT 'Secret Key',
    `sdk_app_id` VARCHAR(50) DEFAULT '' COMMENT 'SDK App ID',
    `sign_name` VARCHAR(100) DEFAULT '' COMMENT '短信签名',
    `template_code` VARCHAR(50) DEFAULT '' COMMENT '通用验证码模板ID',
    `template_register` VARCHAR(50) DEFAULT '' COMMENT '注册模板ID',
    `template_login` VARCHAR(50) DEFAULT '' COMMENT '登录模板ID',
    `template_reset` VARCHAR(50) DEFAULT '' COMMENT '重置密码模板ID',
    `template_bind` VARCHAR(50) DEFAULT '' COMMENT '绑定手机模板ID',
    `is_enabled` TINYINT DEFAULT 0 COMMENT '是否启用: 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信配置表';

-- 16. CMS相关表
CREATE TABLE IF NOT EXISTS `pages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL COMMENT '页面标题',
    `slug` VARCHAR(100) NOT NULL UNIQUE COMMENT '页面标识',
    `content` LONGTEXT COMMENT '页面内容',
    `meta_title` VARCHAR(200) DEFAULT '' COMMENT 'SEO标题',
    `meta_description` VARCHAR(500) DEFAULT '' COMMENT 'SEO描述',
    `meta_keywords` VARCHAR(300) DEFAULT '' COMMENT 'SEO关键词',
    `template` VARCHAR(50) DEFAULT 'default' COMMENT '模板',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0草稿 1已发布 2隐藏',
    `is_home` TINYINT DEFAULT 0 COMMENT '是否首页: 0否 1是',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `view_count` INT DEFAULT 0 COMMENT '浏览次数',
    `published_at` DATETIME NULL COMMENT '发布时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面表';

CREATE TABLE IF NOT EXISTS `page_versions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT UNSIGNED NOT NULL COMMENT '页面ID',
    `version` INT NOT NULL COMMENT '版本号',
    `title` VARCHAR(200) NOT NULL COMMENT '页面标题',
    `content` LONGTEXT COMMENT '页面内容',
    `created_by` INT UNSIGNED DEFAULT 0 COMMENT '创建人ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_page_version` (`page_id`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面版本表';

CREATE TABLE IF NOT EXISTS `page_drafts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT UNSIGNED NOT NULL COMMENT '页面ID',
    `title` VARCHAR(200) NOT NULL COMMENT '页面标题',
    `content` LONGTEXT COMMENT '页面内容',
    `autosave` TINYINT DEFAULT 0 COMMENT '是否自动保存: 0否 1是',
    `created_by` INT UNSIGNED DEFAULT 0 COMMENT '创建人ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面草稿表';

CREATE TABLE IF NOT EXISTS `upload_files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '上传用户ID',
    `name` VARCHAR(255) NOT NULL COMMENT '原始文件名',
    `filename` VARCHAR(255) NOT NULL COMMENT '存储文件名',
    `path` VARCHAR(500) NOT NULL COMMENT '文件路径',
    `url` VARCHAR(500) NOT NULL COMMENT '访问URL',
    `mime_type` VARCHAR(100) DEFAULT '' COMMENT 'MIME类型',
    `extension` VARCHAR(20) DEFAULT '' COMMENT '文件扩展名',
    `size` INT UNSIGNED DEFAULT 0 COMMENT '文件大小(字节)',
    `width` INT UNSIGNED DEFAULT 0 COMMENT '图片宽度',
    `height` INT UNSIGNED DEFAULT 0 COMMENT '图片高度',
    `type` VARCHAR(50) DEFAULT '' COMMENT '文件类型: image/video/audio/document/other',
    `storage` VARCHAR(20) DEFAULT 'local' COMMENT '存储位置: local/oss/cos',
    `is_image` TINYINT DEFAULT 0 COMMENT '是否图片: 0否 1是',
    `used_count` INT DEFAULT 0 COMMENT '使用次数',
    `ip` VARCHAR(45) DEFAULT '' COMMENT '上传IP',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='上传文件表';

-- 17. AI提示词表
CREATE TABLE IF NOT EXISTS `ai_prompts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '提示词名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '提示词编码',
    `type` VARCHAR(50) NOT NULL COMMENT '类型: bazi/hehun/tarot/daily/etc',
    `system_prompt` TEXT COMMENT '系统提示词',
    `user_prompt` TEXT COMMENT '用户提示词模板',
    `model` VARCHAR(50) DEFAULT 'gpt-4' COMMENT 'AI模型',
    `temperature` DECIMAL(3,2) DEFAULT 0.7 COMMENT '温度参数',
    `max_tokens` INT DEFAULT 2000 COMMENT '最大Token数',
    `variables` JSON NULL COMMENT '变量定义',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `description` VARCHAR(500) DEFAULT '' COMMENT '描述',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_code` (`code`),
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI提示词表';

-- 插入默认AI提示词
INSERT INTO `ai_prompts` (`name`, `code`, `type`, `system_prompt`, `user_prompt`, `description`) VALUES
('八字基础分析', 'bazi_basic', 'bazi', '你是一位精通中国传统命理的资深命理师，擅长八字分析。请用专业但易懂的语言为用户解读八字。', 
'请分析以下八字信息：\n出生时间：{birth_date} {birth_time}\n性别：{gender}\n八字：{bazi}\n五行：{wuxing}\n请提供详细的命理分析。', '八字基础分析提示词'),
('八字合婚分析', 'hehun_analysis', 'hehun', '你是一位精通中国传统命理的资深命理师，擅长八字合婚分析。请用专业但易懂的语言为情侣提供合婚建议。',
'请分析以下两人的八字合婚：\n男方：{male_name}，出生时间：{male_birth}\n女方：{female_name}，出生时间：{female_birth}\n男方八字：{male_bazi}\n女方八字：{female_bazi}\n请提供详细的合婚分析和建议。', '八字合婚分析提示词'),
('塔罗牌解读', 'tarot_interpret', 'tarot', '你是一位经验丰富的塔罗牌解读师，擅长结合牌面为用户提供 insightful 的解读。请用温暖、积极的语言进行解读。',
'请解读以下塔罗牌阵：\n问题：{question}\n牌阵类型：{spread_type}\n抽到的牌：{cards}\n请提供详细的牌义解读和建议。', '塔罗牌解读提示词'),
('每日运势', 'daily_fortune', 'daily', '你是一位运势分析师，擅长结合八字和星象为用户提供每日运势指导。请用积极、实用的语言撰写运势。',
'请为以下用户生成今日运势：\n八字：{bazi}\n今日干支：{today_ganzhi}\n今日五行：{today_wuxing}\n请从事业、财运、感情、健康等方面分析今日运势。', '每日运势生成提示词');

-- 18. 塔罗牌阵表
CREATE TABLE IF NOT EXISTS `tarot_spreads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '牌阵名称',
    `name_en` VARCHAR(50) DEFAULT '' COMMENT '英文名称',
    `code` VARCHAR(30) NOT NULL UNIQUE COMMENT '牌阵编码',
    `description` TEXT COMMENT '牌阵描述',
    `positions` JSON NOT NULL COMMENT '牌位定义',
    `card_count` INT NOT NULL COMMENT '牌数',
    `category` VARCHAR(30) DEFAULT 'general' COMMENT '类别: general/love/career/decision',
    `difficulty` VARCHAR(20) DEFAULT 'beginner' COMMENT '难度: beginner/intermediate/advanced',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `image` VARCHAR(255) DEFAULT '' COMMENT '牌阵示意图',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_code` (`code`),
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗牌阵表';

-- 插入默认牌阵
INSERT INTO `tarot_spreads` (`name`, `name_en`, `code`, `description`, `positions`, `card_count`, `category`, `difficulty`, `sort_order`) VALUES
('单张牌', 'Single Card', 'single', '最简洁的占卜方式，适合快速获得指引。', 
'[{"name":"指引","meaning":"当前情况的核心信息或建议"}]', 1, 'general', 'beginner', 1),
('三张牌', 'Three Cards', 'three', '经典的三张牌阵，代表过去、现在、未来。',
'[{"name":"过去","meaning":"代表过去的影响和经历"},{"name":"现在","meaning":"代表当前的状况和挑战"},{"name":"未来","meaning":"代表可能的发展趋势"}]', 
3, 'general', 'beginner', 2),
('凯尔特十字', 'Celtic Cross', 'celtic_cross', '最经典的塔罗牌阵之一，提供全面的分析。',
'[{"name":"现状","meaning":"代表当前的核心状况"},{"name":"障碍","meaning":"代表面临的阻碍或挑战"},{"name":"基础","meaning":"代表事情的根本基础"},{"name":"过去","meaning":"代表过去的影响"},{"name":"目标","meaning":"代表期望达成的目标"},{"name":"未来","meaning":"代表近期的发展趋势"},{"name":"自我","meaning":"代表你现在的状态"},{"name":"环境","meaning":"代表外部环境和他人看法"},{"name":"希望","meaning":"代表你的希望和恐惧"},{"name":"结果","meaning":"代表最终可能的结果"}]',
10, 'general', 'advanced', 3),
('爱情关系', 'Relationship Spread', 'relationship', '专为感情关系设计的牌阵。',
'[{"name":"你","meaning":"代表你在这段关系中的状态"},{"name":"对方","meaning":"代表对方在这段关系中的状态"},{"name":"关系","meaning":"代表你们关系的现状"},{"name":"挑战","meaning":"代表关系面临的挑战"},{"name":"建议","meaning":"代表改善关系的建议"}]',
5, 'love', 'intermediate', 4);

-- 19. FAQ表
CREATE TABLE IF NOT EXISTS `faqs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `question` VARCHAR(500) NOT NULL COMMENT '问题',
    `answer` TEXT NOT NULL COMMENT '答案',
    `category` VARCHAR(50) DEFAULT 'general' COMMENT '分类: general/bazi/tarot/points/vip',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `is_hot` TINYINT DEFAULT 0 COMMENT '是否热门: 0否 1是',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
    `view_count` INT DEFAULT 0 COMMENT '查看次数',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_is_hot` (`is_hot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='FAQ表';

-- 插入默认FAQ
INSERT INTO `faqs` (`question`, `answer`, `category`, `sort_order`, `is_hot`) VALUES
('什么是八字？', '八字是指一个人出生的年、月、日、时，用天干地支表示，共八个字。通过分析这八个字的五行生克关系，可以了解一个人的性格特点、运势走向等信息。', 'bazi', 1, 1),
('塔罗牌占卜准确吗？', '塔罗牌是一种心灵指引工具，其准确性取决于问卜者的心态和解读者的经验。塔罗牌更侧重于反映当下的心理状态和潜在的发展趋势，而非预测绝对的未来。', 'tarot', 2, 1),
('如何获得积分？', '您可以通过以下方式获得积分：1.每日签到 2.分享小程序 3.邀请好友 4.完善个人资料 5.首次排盘 6.绑定微信 7.关注公众号 8.浏览文章。VIP会员还可获得积分倍数加成。', 'points', 3, 1),
('VIP会员有什么特权？', 'VIP会员享有以下特权：1.每日积分双倍奖励 2.排盘次数无限制 3.解锁基础报告 4.解锁合婚功能 5.优先客服支持。', 'vip', 4, 1);

-- 20. 每日运势模板表
CREATE TABLE IF NOT EXISTS `daily_fortune_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '模板名称',
    `type` VARCHAR(50) NOT NULL COMMENT '类型: general/love/career/wealth/health',
    `content` TEXT NOT NULL COMMENT '模板内容',
    `variables` JSON NULL COMMENT '变量定义',
    `score_range_min` INT DEFAULT 0 COMMENT '适用评分下限',
    `score_range_max` INT DEFAULT 100 COMMENT '适用评分上限',
    `wuxing_preference` VARCHAR(50) DEFAULT '' COMMENT '五行偏好',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
    `use_count` INT DEFAULT 0 COMMENT '使用次数',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_type` (`type`),
    INDEX `idx_score_range` (`score_range_min`, `score_range_max`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日运势模板表';

-- 21. 用户评价表
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '用户ID',
    `user_name` VARCHAR(100) DEFAULT '' COMMENT '用户名称',
    `avatar` VARCHAR(255) DEFAULT '' COMMENT '用户头像',
    `content` TEXT NOT NULL COMMENT '评价内容',
    `rating` TINYINT DEFAULT 5 COMMENT '评分 1-5',
    `feature` VARCHAR(50) DEFAULT '' COMMENT '评价的功能: bazi/tarot/hehun/daily',
    `is_anonymous` TINYINT DEFAULT 0 COMMENT '是否匿名: 0否 1是',
    `is_featured` TINYINT DEFAULT 0 COMMENT '是否精选: 0否 1是',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否显示: 0隐藏 1显示',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_feature` (`feature`),
    INDEX `idx_is_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户评价表';

-- 插入默认评价
INSERT INTO `testimonials` (`user_name`, `content`, `rating`, `feature`, `is_featured`, `sort_order`) VALUES
('张先生', '八字排盘非常准确，分析报告详细专业，帮我更好地了解了自己的运势走向。', 5, 'bazi', 1, 1),
('李女士', '塔罗牌占卜给了我很大的心灵慰藉，解读非常到位，推荐！', 5, 'tarot', 1, 2),
('王先生', '和女朋友一起测了合婚，分析很专业，给我们很多相处建议，很有帮助。', 5, 'hehun', 1, 3);

-- 22. 网站内容表
CREATE TABLE IF NOT EXISTS `site_contents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE COMMENT '内容键',
    `title` VARCHAR(200) DEFAULT '' COMMENT '标题',
    `content` LONGTEXT COMMENT '内容',
    `type` VARCHAR(20) DEFAULT 'text' COMMENT '类型: text/html/json/image',
    `group` VARCHAR(50) DEFAULT 'general' COMMENT '分组: general/home/about/help',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='网站内容表';

-- 插入默认网站内容
INSERT INTO `site_contents` (`key`, `title`, `content`, `type`, `group`) VALUES
('home_banner_title', '首页横幅标题', '探索命理奥秘，掌握人生运势', 'text', 'home'),
('home_banner_subtitle', '首页横幅副标题', '专业的八字排盘、塔罗占卜、每日运势分析', 'text', 'home'),
('about_us', '关于我们', '<p>我们是一支专注于传统命理与现代科技结合的团队，致力于为用户提供专业、准确的命理分析服务。</p>', 'html', 'about'),
('contact_us', '联系我们', '如有任何问题，请联系客服：support@taichu.com', 'text', 'help');

-- 23. 问题模板表
CREATE TABLE IF NOT EXISTS `question_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '模板名称',
    `category` VARCHAR(50) NOT NULL COMMENT '分类: tarot/bazi/general',
    `question` VARCHAR(500) NOT NULL COMMENT '问题模板',
    `description` VARCHAR(500) DEFAULT '' COMMENT '描述',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `use_count` INT DEFAULT 0 COMMENT '使用次数',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问题模板表';

-- 插入默认问题模板
INSERT INTO `question_templates` (`name`, `category`, `question`, `description`, `sort_order`) VALUES
('今日运势', 'general', '今天我的运势如何？', '询问今日整体运势', 1),
('事业发展', 'bazi', '我的事业发展前景如何？', '询问事业发展方向', 2),
('感情婚姻', 'bazi', '我的感情婚姻状况如何？', '询问感情婚姻运势', 3),
('财运分析', 'bazi', '我的财运如何？', '询问财运走势', 4),
('感情发展', 'tarot', '我和TA的感情会如何发展？', '询问感情发展', 5),
('事业选择', 'tarot', '我应该选择哪个方向？', '询问事业选择', 6);

-- 24. 塔罗牌表
CREATE TABLE IF NOT EXISTS `tarot_cards` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '牌名',
    `name_en` VARCHAR(50) DEFAULT '' COMMENT '英文名',
    `number` INT NOT NULL COMMENT '牌号',
    `arcana` VARCHAR(20) NOT NULL COMMENT '大/小阿尔卡纳: major/minor',
    `suit` VARCHAR(20) DEFAULT NULL COMMENT '花色: cups/wands/swords/pentacles',
    `element` VARCHAR(20) NOT NULL COMMENT '元素: 火,水,木,金,土,风',
    `emoji` VARCHAR(10) NOT NULL COMMENT '表情符号',
    `color` VARCHAR(20) NOT NULL COMMENT '颜色',
    `keywords` VARCHAR(255) NOT NULL COMMENT '关键词',
    `meaning` TEXT NOT NULL COMMENT '基本含义',
    `upright_meaning` TEXT NOT NULL COMMENT '正位含义',
    `reversed_meaning` TEXT NOT NULL COMMENT '逆位含义',
    `advice` TEXT NOT NULL COMMENT '建议',
    `career_meaning` TEXT COMMENT '事业含义',
    `love_meaning` TEXT COMMENT '感情含义',
    `wealth_meaning` TEXT COMMENT '财富含义',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_number` (`number`),
    INDEX `idx_arcana` (`arcana`),
    INDEX `idx_element` (`element`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗牌表';

-- ============================================================
-- 所有缺少的表已创建完成
-- ============================================================
