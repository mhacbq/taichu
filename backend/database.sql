-- ============================================================
-- 太初命理网站 - 完整数据库结构
-- 创建时间: 2026-03-15
-- 适用环境: MySQL 8.0+
-- 字符集: utf8mb4
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 第一部分：核心用户表
-- ============================================================

-- 用户表
DROP TABLE IF EXISTS `tc_user`;
CREATE TABLE `tc_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `openid` varchar(100) NOT NULL DEFAULT '',
  `unionid` varchar(100) NOT NULL DEFAULT '',
  `nickname` varchar(100) NOT NULL DEFAULT '',
  `avatar` varchar(500) NOT NULL DEFAULT '',
  `gender` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0未知 1男 2女',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `points` int(11) NOT NULL DEFAULT '0' COMMENT '积分余额',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0禁用 1正常',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- ============================================================
-- 第二部分：八字排盘相关表
-- ============================================================

-- 八字排盘记录表
DROP TABLE IF EXISTS `tc_bazi_record`;
CREATE TABLE `tc_bazi_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `birth_date` varchar(50) NOT NULL DEFAULT '',
  `gender` varchar(10) NOT NULL DEFAULT '',
  `location` varchar(100) NOT NULL DEFAULT '',
  `year_gan` varchar(10) NOT NULL DEFAULT '',
  `year_zhi` varchar(10) NOT NULL DEFAULT '',
  `month_gan` varchar(10) NOT NULL DEFAULT '',
  `month_zhi` varchar(10) NOT NULL DEFAULT '',
  `day_gan` varchar(10) NOT NULL DEFAULT '',
  `day_zhi` varchar(10) NOT NULL DEFAULT '',
  `hour_gan` varchar(10) NOT NULL DEFAULT '',
  `hour_zhi` varchar(10) NOT NULL DEFAULT '',
  `analysis` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='八字排盘记录表';

-- 八字合婚记录表
DROP TABLE IF EXISTS `hehun_records`;
CREATE TABLE `hehun_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `male_name` varchar(50) DEFAULT NULL COMMENT '男方姓名',
  `male_birth` datetime NOT NULL COMMENT '男方出生时间',
  `female_name` varchar(50) DEFAULT NULL COMMENT '女方姓名',
  `female_birth` datetime NOT NULL COMMENT '女方出生时间',
  `male_bazi` json DEFAULT NULL COMMENT '男方八字',
  `female_bazi` json DEFAULT NULL COMMENT '女方八字',
  `result` json DEFAULT NULL COMMENT '合婚结果',
  `score` int(11) DEFAULT NULL COMMENT '匹配分数',
  `analysis` text COMMENT '详细分析',
  `consumed_points` int(11) DEFAULT 0 COMMENT '消耗积分',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='八字合婚记录表';

-- 取名记录表
DROP TABLE IF EXISTS `qiming_records`;
CREATE TABLE `qiming_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `surname` varchar(10) NOT NULL COMMENT '姓氏',
  `birth` datetime NOT NULL COMMENT '出生时间',
  `gender` tinyint(1) DEFAULT 0 COMMENT '性别：0男，1女',
  `bazi` json DEFAULT NULL COMMENT '八字',
  `wuxing` json DEFAULT NULL COMMENT '五行分析',
  `names` json DEFAULT NULL COMMENT '推荐名字列表',
  `consumed_points` int(11) DEFAULT 0 COMMENT '消耗积分',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='取名记录表';

-- 吉日查询记录表
DROP TABLE IF EXISTS `jiri_records`;
CREATE TABLE `jiri_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `query_type` varchar(50) NOT NULL COMMENT '查询类型：marriage, move, open, travel等',
  `start_date` date NOT NULL COMMENT '开始日期',
  `end_date` date NOT NULL COMMENT '结束日期',
  `result` json DEFAULT NULL COMMENT '查询结果',
  `consumed_points` int(11) DEFAULT 0 COMMENT '消耗积分',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='吉日查询记录表';

-- ============================================================
-- 第三部分：塔罗占卜相关表
-- ============================================================

-- 塔罗占卜记录表
DROP TABLE IF EXISTS `tc_tarot_record`;
CREATE TABLE `tc_tarot_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `spread_type` varchar(50) NOT NULL COMMENT '牌阵类型: single/three/celtic/relationship',
  `question` varchar(500) DEFAULT '' COMMENT '占卜问题',
  `cards` json DEFAULT NULL COMMENT '抽到的牌（JSON数组）',
  `interpretation` text COMMENT '解读内容',
  `ai_analysis` text COMMENT 'AI深度分析',
  `is_public` tinyint(1) DEFAULT 0 COMMENT '是否公开: 0私密 1公开',
  `share_code` varchar(50) DEFAULT '' COMMENT '分享码',
  `view_count` int(11) DEFAULT 0 COMMENT '查看次数',
  `client_ip` varchar(45) DEFAULT '' COMMENT '客户端IP',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_share_code` (`share_code`),
  KEY `idx_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='塔罗占卜记录表';

-- 塔罗牌阵表
DROP TABLE IF EXISTS `tarot_spreads`;
CREATE TABLE `tarot_spreads` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '牌阵名称',
  `name_en` varchar(50) DEFAULT '' COMMENT '英文名称',
  `code` varchar(30) NOT NULL COMMENT '牌阵编码',
  `description` text COMMENT '牌阵描述',
  `positions` json NOT NULL COMMENT '牌位定义',
  `card_count` int(11) NOT NULL COMMENT '牌数',
  `category` varchar(30) DEFAULT 'general' COMMENT '类别: general/love/career/decision',
  `difficulty` varchar(20) DEFAULT 'beginner' COMMENT '难度: beginner/intermediate/advanced',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `image` varchar(255) DEFAULT '' COMMENT '牌阵示意图',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='塔罗牌阵表';

-- 塔罗牌表
DROP TABLE IF EXISTS `tarot_cards`;
CREATE TABLE `tarot_cards` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '牌名',
  `name_en` varchar(50) DEFAULT '' COMMENT '英文名',
  `number` int(11) NOT NULL COMMENT '牌号',
  `arcana` varchar(20) NOT NULL COMMENT '大/小阿尔卡纳: major/minor',
  `suit` varchar(20) DEFAULT NULL COMMENT '花色: cups/wands/swords/pentacles',
  `element` varchar(20) NOT NULL COMMENT '元素: 火,水,木,金,土,风',
  `emoji` varchar(10) NOT NULL COMMENT '表情符号',
  `color` varchar(20) NOT NULL COMMENT '颜色',
  `keywords` varchar(255) NOT NULL COMMENT '关键词',
  `meaning` text NOT NULL COMMENT '基本含义',
  `upright_meaning` text NOT NULL COMMENT '正位含义',
  `reversed_meaning` text NOT NULL COMMENT '逆位含义',
  `advice` text NOT NULL COMMENT '建议',
  `career_meaning` text COMMENT '事业含义',
  `love_meaning` text COMMENT '感情含义',
  `wealth_meaning` text COMMENT '财富含义',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_number` (`number`),
  KEY `idx_arcana` (`arcana`),
  KEY `idx_element` (`element`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='塔罗牌表';

-- ============================================================
-- 第四部分：每日运势相关表
-- ============================================================

-- 每日运势表
DROP TABLE IF EXISTS `tc_daily_fortune`;
CREATE TABLE `tc_daily_fortune` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `lunar_date` varchar(50) NOT NULL DEFAULT '',
  `overall_score` tinyint(3) unsigned NOT NULL DEFAULT '80',
  `summary` varchar(500) NOT NULL DEFAULT '',
  `career_score` tinyint(3) unsigned NOT NULL DEFAULT '80',
  `career_desc` varchar(500) NOT NULL DEFAULT '',
  `wealth_score` tinyint(3) unsigned NOT NULL DEFAULT '80',
  `wealth_desc` varchar(500) NOT NULL DEFAULT '',
  `love_score` tinyint(3) unsigned NOT NULL DEFAULT '80',
  `love_desc` varchar(500) NOT NULL DEFAULT '',
  `health_score` tinyint(3) unsigned NOT NULL DEFAULT '80',
  `health_desc` varchar(500) NOT NULL DEFAULT '',
  `yi` varchar(500) NOT NULL DEFAULT '' COMMENT '宜',
  `ji` varchar(500) NOT NULL DEFAULT '' COMMENT '忌',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='每日运势表';

-- 每日运势模板表
DROP TABLE IF EXISTS `daily_fortune_templates`;
CREATE TABLE `daily_fortune_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '模板名称',
  `type` varchar(50) NOT NULL COMMENT '类型: general/love/career/wealth/health',
  `content` text NOT NULL COMMENT '模板内容',
  `variables` json DEFAULT NULL COMMENT '变量定义',
  `score_range_min` int(11) DEFAULT 0 COMMENT '适用评分下限',
  `score_range_max` int(11) DEFAULT 100 COMMENT '适用评分上限',
  `wuxing_preference` varchar(50) DEFAULT '' COMMENT '五行偏好',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
  `use_count` int(11) DEFAULT 0 COMMENT '使用次数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_score_range` (`score_range_min`, `score_range_max`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='每日运势模板表';

-- ============================================================
-- 第五部分：积分系统相关表
-- ============================================================

-- 积分记录表
DROP TABLE IF EXISTS `tc_points_record`;
CREATE TABLE `tc_points_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `action` varchar(100) NOT NULL DEFAULT '' COMMENT '动作描述',
  `points` int(11) NOT NULL DEFAULT '0' COMMENT '变动积分（正数增加，负数减少）',
  `type` varchar(50) NOT NULL DEFAULT '' COMMENT '类型',
  `related_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联ID',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分记录表';

-- 积分兑换记录表
DROP TABLE IF EXISTS `tc_points_exchange`;
CREATE TABLE `tc_points_exchange` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `exchange_no` varchar(50) NOT NULL COMMENT '兑换单号',
  `product_id` int(11) unsigned NOT NULL COMMENT '商品ID',
  `product_name` varchar(100) NOT NULL COMMENT '商品名称',
  `product_type` varchar(50) NOT NULL COMMENT '商品类型: points/vip/service/physical/coupon',
  `points_cost` int(11) NOT NULL COMMENT '消耗积分',
  `quantity` int(11) DEFAULT 1 COMMENT '兑换数量',
  `redeem_code` varchar(50) DEFAULT '' COMMENT '兑换码',
  `valid_until` datetime DEFAULT NULL COMMENT '有效期至',
  `completed_at` datetime DEFAULT NULL COMMENT '完成时间',
  `cancelled_at` datetime DEFAULT NULL COMMENT '取消时间',
  `cancel_reason` varchar(255) DEFAULT '' COMMENT '取消原因',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',
  `status` tinyint(1) DEFAULT 0 COMMENT '状态: 0待处理 1已完成 2已取消 3失败',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_exchange_no` (`exchange_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分兑换记录表';

-- 积分商品表
DROP TABLE IF EXISTS `tc_points_product`;
CREATE TABLE `tc_points_product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '商品名称',
  `description` varchar(500) DEFAULT '' COMMENT '商品简介',
  `detail` text COMMENT '商品详情',
  `type` varchar(50) NOT NULL COMMENT '类型: points/vip/service/physical/coupon',
  `points_price` int(11) NOT NULL COMMENT '积分价格',
  `original_price` decimal(10,2) DEFAULT 0 COMMENT '原价（元）',
  `stock` int(11) DEFAULT 0 COMMENT '库存数量，-1为无限',
  `sold_count` int(11) DEFAULT 0 COMMENT '已售数量',
  `icon` varchar(255) DEFAULT '' COMMENT '商品图标',
  `images` json DEFAULT NULL COMMENT '商品图片列表',
  `tags` json DEFAULT NULL COMMENT '标签列表',
  `purchase_limit` int(11) DEFAULT 0 COMMENT '购买限制，0为无限制',
  `validity_days` int(11) DEFAULT 0 COMMENT '有效期天数，0为永久',
  `usage_guide` text COMMENT '使用说明',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 0 COMMENT '状态: 0已下架 1上架中 2已售罄',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分商品表';

-- ============================================================
-- 第六部分：充值订单相关表
-- ============================================================

-- 充值订单表
DROP TABLE IF EXISTS `tc_recharge_order`;
CREATE TABLE `tc_recharge_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `amount` decimal(10,2) NOT NULL COMMENT '充值金额',
  `points` int(11) NOT NULL COMMENT '获得积分',
  `status` varchar(20) DEFAULT 'pending' COMMENT '状态: pending/paid/cancelled/refunded/processing',
  `payment_type` varchar(30) DEFAULT 'wechat_jsapi' COMMENT '支付方式',
  `pay_order_no` varchar(100) DEFAULT '' COMMENT '支付平台订单号',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `expire_time` datetime DEFAULT NULL COMMENT '过期时间',
  `client_ip` varchar(45) DEFAULT '' COMMENT '客户端IP',
  `user_agent` varchar(500) DEFAULT '' COMMENT 'User-Agent',
  `callback_data` json DEFAULT NULL COMMENT '回调数据',
  `notify_id` varchar(100) DEFAULT '' COMMENT '通知ID（幂等性验证）',
  `notify_time` datetime DEFAULT NULL COMMENT '通知处理时间',
  `process_log` json DEFAULT NULL COMMENT '处理日志',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值订单表';

-- VIP会员订单表
DROP TABLE IF EXISTS `vip_orders`;
CREATE TABLE `vip_orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `order_no` varchar(64) NOT NULL COMMENT '订单号',
  `vip_type` varchar(20) NOT NULL COMMENT '会员类型：month, quarter, year',
  `duration_days` int(11) NOT NULL COMMENT '时长（天）',
  `amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `status` tinyint(1) DEFAULT 0 COMMENT '状态：0待支付，1已支付，2已取消',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `pay_method` varchar(20) DEFAULT NULL COMMENT '支付方式',
  `transaction_id` varchar(100) DEFAULT NULL COMMENT '第三方支付流水号',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='VIP订单表';

-- 用户VIP状态表
DROP TABLE IF EXISTS `user_vip`;
CREATE TABLE `user_vip` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `vip_type` varchar(20) DEFAULT NULL COMMENT '当前会员类型',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `total_paid` decimal(10,2) DEFAULT 0 COMMENT '累计支付金额',
  `is_active` tinyint(1) DEFAULT 0 COMMENT '是否有效',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_id` (`user_id`),
  KEY `idx_end_time` (`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户VIP状态表';

-- ============================================================
-- 第七部分：签到邀请相关表
-- ============================================================

-- 签到记录表
DROP TABLE IF EXISTS `tc_checkin_record`;
CREATE TABLE `tc_checkin_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `date` date NOT NULL COMMENT '签到日期',
  `consecutive_days` int(11) NOT NULL DEFAULT '1' COMMENT '连续签到天数',
  `points` int(11) NOT NULL DEFAULT '0' COMMENT '获得积分',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_date` (`user_id`, `date`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到记录表';

-- 签到日志表
DROP TABLE IF EXISTS `tc_checkin_log`;
CREATE TABLE `tc_checkin_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `date` date NOT NULL COMMENT '签到日期',
  `consecutive_days` int(11) DEFAULT 1 COMMENT '连续签到天数',
  `base_points` int(11) DEFAULT 0 COMMENT '基础积分',
  `bonus_points` int(11) DEFAULT 0 COMMENT '奖励积分',
  `total_points` int(11) DEFAULT 0 COMMENT '总积分',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_date` (`user_id`, `date`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到日志表';

-- 邀请记录表
DROP TABLE IF EXISTS `tc_invite_record`;
CREATE TABLE `tc_invite_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `inviter_id` int(11) unsigned NOT NULL COMMENT '邀请人ID',
  `invited_id` int(11) unsigned DEFAULT 0 COMMENT '被邀请人ID',
  `invite_code` varchar(20) NOT NULL COMMENT '邀请码',
  `points_reward` int(11) DEFAULT 0 COMMENT '奖励积分',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态: 0无效 1有效',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inviter_id` (`inviter_id`),
  KEY `idx_invite_code` (`invite_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请记录表';

-- 邀请记录表（新版）
DROP TABLE IF EXISTS `invite_records`;
CREATE TABLE `invite_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `inviter_id` int(11) unsigned NOT NULL COMMENT '邀请人用户ID',
  `invitee_id` int(11) unsigned DEFAULT NULL COMMENT '被邀请人用户ID',
  `invitee_phone` varchar(20) DEFAULT NULL COMMENT '被邀请人手机号',
  `status` tinyint(1) DEFAULT 0 COMMENT '状态：0待注册，1已注册，2已奖励',
  `points_reward` int(11) DEFAULT 0 COMMENT '奖励积分',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_inviter_invitee` (`inviter_id`, `invitee_id`),
  KEY `idx_inviter_id` (`inviter_id`),
  KEY `idx_invitee_phone` (`invitee_phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请记录表';

-- 分享记录表
DROP TABLE IF EXISTS `tc_share_log`;
CREATE TABLE `tc_share_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `type` varchar(50) NOT NULL COMMENT '分享类型: poster/record/app/page',
  `platform` varchar(50) NOT NULL COMMENT '分享平台: wechat/moments/qq/weibo/copy',
  `content_id` int(11) unsigned DEFAULT 0 COMMENT '内容ID',
  `content_type` varchar(50) DEFAULT '' COMMENT '内容类型: bazi/hehun/tarot/daily/etc',
  `share_code` varchar(20) DEFAULT '' COMMENT '分享码',
  `points_reward` int(11) DEFAULT 0 COMMENT '奖励积分',
  `ip` varchar(45) DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT '' COMMENT 'User-Agent',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分享记录表';

-- 分享记录表（旧版）
DROP TABLE IF EXISTS `share_logs`;
CREATE TABLE `share_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '分享者用户ID',
  `share_type` varchar(20) NOT NULL COMMENT '分享类型：poster, app',
  `share_scene` varchar(50) DEFAULT NULL COMMENT '分享场景',
  `points_earned` int(11) DEFAULT 0 COMMENT '获得积分',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分享记录表';

-- 任务记录表
DROP TABLE IF EXISTS `tc_task_log`;
CREATE TABLE `tc_task_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `task_type` varchar(50) NOT NULL COMMENT '任务类型: daily/weekly/once/limit',
  `task_code` varchar(50) NOT NULL COMMENT '任务编码: first_share/complete_profile/etc',
  `task_name` varchar(100) NOT NULL COMMENT '任务名称',
  `points` int(11) DEFAULT 0 COMMENT '奖励积分',
  `limit_type` varchar(20) DEFAULT '' COMMENT '限制类型: daily/total/none',
  `limit_count` int(11) DEFAULT 1 COMMENT '限制次数',
  `completed_count` int(11) DEFAULT 1 COMMENT '已完成次数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_task_code` (`task_code`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务记录表';

-- 用户任务完成记录表
DROP TABLE IF EXISTS `user_task_logs`;
CREATE TABLE `user_task_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `task_type` varchar(50) NOT NULL COMMENT '任务类型',
  `task_name` varchar(100) NOT NULL COMMENT '任务名称',
  `points` int(11) NOT NULL COMMENT '获得积分',
  `completed_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '完成时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_task_type` (`task_type`),
  KEY `idx_completed_at` (`completed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户任务完成记录表';

-- ============================================================
-- 第八部分：通知相关表
-- ============================================================

-- 通知表
DROP TABLE IF EXISTS `tc_notification`;
CREATE TABLE `tc_notification` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `type` varchar(50) NOT NULL COMMENT '通知类型: system/points/vip/activity/reminder',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `content` text COMMENT '内容',
  `data` json DEFAULT NULL COMMENT '附加数据',
  `is_read` tinyint(1) DEFAULT 0 COMMENT '是否已读: 0未读 1已读',
  `read_at` datetime DEFAULT NULL COMMENT '阅读时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知表';

-- 通知设置表
DROP TABLE IF EXISTS `tc_notification_setting`;
CREATE TABLE `tc_notification_setting` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `type` varchar(50) NOT NULL COMMENT '通知类型',
  `enabled` tinyint(1) DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_type` (`user_id`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知设置表';

-- 推送设备表
DROP TABLE IF EXISTS `tc_push_device`;
CREATE TABLE `tc_push_device` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `device_id` varchar(255) NOT NULL COMMENT '设备ID',
  `platform` varchar(20) NOT NULL COMMENT '平台: ios/android/web',
  `token` varchar(500) NOT NULL COMMENT '推送令牌',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否激活: 0禁用 1启用',
  `last_used_at` datetime DEFAULT NULL COMMENT '最后使用时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推送设备表';

-- ============================================================
-- 第九部分：管理员系统相关表
-- ============================================================

-- 管理员操作日志表
DROP TABLE IF EXISTS `tc_admin_log`;
CREATE TABLE `tc_admin_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) unsigned NOT NULL COMMENT '管理员ID',
  `admin_name` varchar(50) NOT NULL DEFAULT '' COMMENT '管理员名称',
  `action` varchar(50) NOT NULL COMMENT '操作类型',
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT '操作模块',
  `target_id` int(11) unsigned DEFAULT 0 COMMENT '操作目标ID',
  `target_type` varchar(50) DEFAULT '' COMMENT '操作目标类型',
  `detail` text COMMENT '操作详情',
  `before_data` json DEFAULT NULL COMMENT '操作前数据',
  `after_data` json DEFAULT NULL COMMENT '操作后数据',
  `ip` varchar(45) NOT NULL DEFAULT '' COMMENT '操作IP',
  `user_agent` varchar(500) DEFAULT '' COMMENT 'User-Agent',
  `request_url` varchar(500) DEFAULT '' COMMENT '请求URL',
  `request_method` varchar(10) DEFAULT '' COMMENT '请求方法',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态: 1成功 0失败',
  `error_msg` varchar(500) DEFAULT '' COMMENT '错误信息',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_action` (`action`),
  KEY `idx_module` (`module`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员操作日志表';

-- 管理员权限表
DROP TABLE IF EXISTS `tc_admin_permission`;
CREATE TABLE `tc_admin_permission` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '权限名称',
  `code` varchar(50) NOT NULL COMMENT '权限标识',
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT '所属模块',
  `description` varchar(255) DEFAULT '' COMMENT '权限描述',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员权限表';

-- 管理员角色表
DROP TABLE IF EXISTS `tc_admin_role`;
CREATE TABLE `tc_admin_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '角色名称',
  `code` varchar(50) NOT NULL COMMENT '角色标识',
  `description` varchar(255) DEFAULT '' COMMENT '角色描述',
  `is_super` tinyint(1) DEFAULT 0 COMMENT '是否超级管理员',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员角色表';

-- 管理员角色权限关联表
DROP TABLE IF EXISTS `tc_admin_role_permission`;
CREATE TABLE `tc_admin_role_permission` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) unsigned NOT NULL COMMENT '角色ID',
  `permission_id` int(11) unsigned NOT NULL COMMENT '权限ID',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_role_permission` (`role_id`, `permission_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限关联表';

-- 管理员角色关联表
DROP TABLE IF EXISTS `tc_admin_user_role`;
CREATE TABLE `tc_admin_user_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) unsigned NOT NULL COMMENT '管理员ID',
  `role_id` int(11) unsigned NOT NULL COMMENT '角色ID',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_role` (`admin_id`, `role_id`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员角色关联表';

-- ============================================================
-- 第十部分：配置系统相关表
-- ============================================================

-- 系统配置表
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `config_type` varchar(20) DEFAULT 'string' COMMENT '值类型：string,json,int,bool,float',
  `description` varchar(255) DEFAULT NULL COMMENT '配置说明',
  `category` varchar(50) DEFAULT 'general' COMMENT '配置分类',
  `is_editable` tinyint(1) DEFAULT 1 COMMENT '是否可在后台编辑',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_config_key` (`config_key`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- 支付配置表
DROP TABLE IF EXISTS `payment_configs`;
CREATE TABLE `payment_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL DEFAULT 'wechat_jsapi' COMMENT '支付类型: wechat_jsapi/wechat_native/alipay',
  `name` varchar(100) DEFAULT '' COMMENT '配置名称',
  `mch_id` varchar(50) DEFAULT '' COMMENT '商户号',
  `app_id` varchar(50) DEFAULT '' COMMENT '应用ID',
  `api_key` varchar(255) DEFAULT '' COMMENT 'API密钥',
  `api_cert` text COMMENT 'API证书内容',
  `api_key_pem` text COMMENT 'API密钥证书内容',
  `notify_url` varchar(500) DEFAULT '' COMMENT '支付回调URL',
  `return_url` varchar(500) DEFAULT '' COMMENT '支付完成返回URL',
  `is_enabled` tinyint(1) DEFAULT 0 COMMENT '是否启用: 0禁用 1启用',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付配置表';

-- 短信验证码表
DROP TABLE IF EXISTS `sms_codes`;
CREATE TABLE `sms_codes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL COMMENT '手机号',
  `code` varchar(10) NOT NULL COMMENT '验证码',
  `type` varchar(20) NOT NULL COMMENT '类型: register/login/reset/bind/unbind',
  `expire_time` datetime NOT NULL COMMENT '过期时间',
  `is_used` tinyint(1) DEFAULT 0 COMMENT '是否已使用: 0否 1是',
  `ip` varchar(45) DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT '' COMMENT 'User-Agent',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_expire_time` (`expire_time`),
  KEY `idx_phone_type` (`phone`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信验证码表';

-- 短信配置表
DROP TABLE IF EXISTS `sms_configs`;
CREATE TABLE `sms_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL DEFAULT 'tencent' COMMENT '服务商: tencent/aliyun',
  `name` varchar(100) DEFAULT '' COMMENT '配置名称',
  `secret_id` varchar(255) DEFAULT '' COMMENT 'Secret ID',
  `secret_key` varchar(255) DEFAULT '' COMMENT 'Secret Key',
  `sdk_app_id` varchar(50) DEFAULT '' COMMENT 'SDK App ID',
  `sign_name` varchar(100) DEFAULT '' COMMENT '短信签名',
  `template_code` varchar(50) DEFAULT '' COMMENT '通用验证码模板ID',
  `template_register` varchar(50) DEFAULT '' COMMENT '注册模板ID',
  `template_login` varchar(50) DEFAULT '' COMMENT '登录模板ID',
  `template_reset` varchar(50) DEFAULT '' COMMENT '重置密码模板ID',
  `template_bind` varchar(50) DEFAULT '' COMMENT '绑定手机模板ID',
  `is_enabled` tinyint(1) DEFAULT 0 COMMENT '是否启用: 0禁用 1启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信配置表';

-- ============================================================
-- 第十一部分：内容管理相关表
-- ============================================================

-- 页面表
DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL COMMENT '页面标题',
  `slug` varchar(100) NOT NULL COMMENT '页面标识',
  `content` longtext COMMENT '页面内容',
  `meta_title` varchar(200) DEFAULT '' COMMENT 'SEO标题',
  `meta_description` varchar(500) DEFAULT '' COMMENT 'SEO描述',
  `meta_keywords` varchar(300) DEFAULT '' COMMENT 'SEO关键词',
  `template` varchar(50) DEFAULT 'default' COMMENT '模板',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态: 0草稿 1已发布 2隐藏',
  `is_home` tinyint(1) DEFAULT 0 COMMENT '是否首页: 0否 1是',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `view_count` int(11) DEFAULT 0 COMMENT '浏览次数',
  `published_at` datetime DEFAULT NULL COMMENT '发布时间',
  `created_by` int(11) unsigned DEFAULT 0 COMMENT '创建人ID',
  `updated_by` int(11) unsigned DEFAULT 0 COMMENT '更新人ID',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_is_home` (`is_home`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='页面表';

-- 页面版本表
DROP TABLE IF EXISTS `page_versions`;
CREATE TABLE `page_versions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(11) unsigned NOT NULL COMMENT '页面ID',
  `version` int(11) NOT NULL COMMENT '版本号',
  `title` varchar(200) NOT NULL COMMENT '页面标题',
  `content` longtext COMMENT '页面内容',
  `created_by` int(11) unsigned DEFAULT 0 COMMENT '创建人ID',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_page_version` (`page_id`, `version`),
  KEY `idx_page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='页面版本表';

-- 页面草稿表
DROP TABLE IF EXISTS `page_drafts`;
CREATE TABLE `page_drafts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(11) unsigned NOT NULL COMMENT '页面ID',
  `title` varchar(200) NOT NULL COMMENT '页面标题',
  `content` longtext COMMENT '页面内容',
  `autosave` tinyint(1) DEFAULT 0 COMMENT '是否自动保存: 0否 1是',
  `created_by` int(11) unsigned DEFAULT 0 COMMENT '创建人ID',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='页面草稿表';

-- 上传文件表
DROP TABLE IF EXISTS `upload_files`;
CREATE TABLE `upload_files` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT 0 COMMENT '上传用户ID',
  `name` varchar(255) NOT NULL COMMENT '原始文件名',
  `filename` varchar(255) NOT NULL COMMENT '存储文件名',
  `path` varchar(500) NOT NULL COMMENT '文件路径',
  `url` varchar(500) NOT NULL COMMENT '访问URL',
  `mime_type` varchar(100) DEFAULT '' COMMENT 'MIME类型',
  `extension` varchar(20) DEFAULT '' COMMENT '文件扩展名',
  `size` int(11) unsigned DEFAULT 0 COMMENT '文件大小(字节)',
  `width` int(11) unsigned DEFAULT 0 COMMENT '图片宽度',
  `height` int(11) unsigned DEFAULT 0 COMMENT '图片高度',
  `type` varchar(50) DEFAULT '' COMMENT '文件类型: image/video/audio/document/other',
  `storage` varchar(20) DEFAULT 'local' COMMENT '存储位置: local/oss/cos',
  `is_image` tinyint(1) DEFAULT 0 COMMENT '是否图片: 0否 1是',
  `used_count` int(11) DEFAULT 0 COMMENT '使用次数',
  `ip` varchar(45) DEFAULT '' COMMENT '上传IP',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_extension` (`extension`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='上传文件表';

-- FAQ表
DROP TABLE IF EXISTS `faqs`;
CREATE TABLE `faqs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(500) NOT NULL COMMENT '问题',
  `answer` text NOT NULL COMMENT '答案',
  `category` varchar(50) DEFAULT 'general' COMMENT '分类: general/bazi/tarot/points/vip',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `is_hot` tinyint(1) DEFAULT 0 COMMENT '是否热门: 0否 1是',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
  `view_count` int(11) DEFAULT 0 COMMENT '查看次数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_hot` (`is_hot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='FAQ表';

-- 用户评价表
DROP TABLE IF EXISTS `testimonials`;
CREATE TABLE `testimonials` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT 0 COMMENT '用户ID',
  `user_name` varchar(100) DEFAULT '' COMMENT '用户名称',
  `avatar` varchar(255) DEFAULT '' COMMENT '用户头像',
  `content` text NOT NULL COMMENT '评价内容',
  `rating` tinyint(1) DEFAULT 5 COMMENT '评分 1-5',
  `feature` varchar(50) DEFAULT '' COMMENT '评价的功能: bazi/tarot/hehun/daily',
  `is_anonymous` tinyint(1) DEFAULT 0 COMMENT '是否匿名: 0否 1是',
  `is_featured` tinyint(1) DEFAULT 0 COMMENT '是否精选: 0否 1是',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否显示: 0隐藏 1显示',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_feature` (`feature`),
  KEY `idx_is_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户评价表';

-- 网站内容表
DROP TABLE IF EXISTS `site_contents`;
CREATE TABLE `site_contents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL COMMENT '内容键',
  `title` varchar(200) DEFAULT '' COMMENT '标题',
  `content` longtext COMMENT '内容',
  `type` varchar(20) DEFAULT 'text' COMMENT '类型: text/html/json/image',
  `group` varchar(50) DEFAULT 'general' COMMENT '分组: general/home/about/help',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`),
  KEY `idx_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站内容表';

-- ============================================================
-- 第十二部分：AI相关表
-- ============================================================

-- AI提示词表
DROP TABLE IF EXISTS `ai_prompts`;
CREATE TABLE `ai_prompts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '提示词名称',
  `code` varchar(50) NOT NULL COMMENT '提示词编码',
  `type` varchar(50) NOT NULL COMMENT '类型: bazi/hehun/tarot/daily/etc',
  `system_prompt` text COMMENT '系统提示词',
  `user_prompt` text COMMENT '用户提示词模板',
  `model` varchar(50) DEFAULT 'gpt-4' COMMENT 'AI模型',
  `temperature` decimal(3,2) DEFAULT 0.7 COMMENT '温度参数',
  `max_tokens` int(11) DEFAULT 2000 COMMENT '最大Token数',
  `variables` json DEFAULT NULL COMMENT '变量定义',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `description` varchar(500) DEFAULT '' COMMENT '描述',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI提示词表';

-- 问题模板表
DROP TABLE IF EXISTS `question_templates`;
CREATE TABLE `question_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '模板名称',
  `category` varchar(50) NOT NULL COMMENT '分类: tarot/bazi/general',
  `question` varchar(500) NOT NULL COMMENT '问题模板',
  `description` varchar(500) DEFAULT '' COMMENT '描述',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `use_count` int(11) DEFAULT 0 COMMENT '使用次数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='问题模板表';

-- ============================================================
-- 第十三部分：用户反馈表
-- ============================================================

-- 用户反馈表
DROP TABLE IF EXISTS `tc_feedback`;
CREATE TABLE `tc_feedback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type` varchar(50) NOT NULL DEFAULT 'suggestion' COMMENT 'suggestion建议 bug错误 complaint投诉 praise表扬 other其他',
  `content` text NOT NULL,
  `contact` varchar(100) NOT NULL DEFAULT '' COMMENT '联系方式',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待处理 1处理中 2已解决 3已关闭',
  `reply` text COMMENT '回复内容',
  `replied_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户反馈表';

-- 用户反馈表（新版）
DROP TABLE IF EXISTS `feedback`;
CREATE TABLE `feedback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `type` varchar(20) NOT NULL DEFAULT 'suggestion' COMMENT '类型: bug/feature/suggestion/other',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `images` json DEFAULT NULL COMMENT '图片列表',
  `contact` varchar(100) DEFAULT '' COMMENT '联系方式',
  `status` tinyint(1) DEFAULT 0 COMMENT '状态: 0待处理 1处理中 2已回复 3已关闭',
  `reply` text COMMENT '回复内容',
  `replied_by` int(11) unsigned DEFAULT 0 COMMENT '回复人ID',
  `replied_at` datetime DEFAULT NULL COMMENT '回复时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户反馈表';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 初始化数据
-- ============================================================

-- 插入默认积分商品
INSERT INTO `tc_points_product` (`name`, `description`, `type`, `points_price`, `original_price`, `stock`, `icon`, `tags`, `validity_days`, `status`) VALUES
('100积分充值', '充值100积分到账户', 'points', 100, 10.00, -1, '💎', '["积分"]', 0, 1),
('月度VIP会员', '享受30天VIP特权', 'vip', 500, 19.90, -1, '👑', '["会员","热门"]', 30, 1),
('季度VIP会员', '享受90天VIP特权', 'vip', 1200, 49.00, -1, '👑', '["会员","优惠"]', 90, 1),
('年度VIP会员', '享受365天VIP特权', 'vip', 4000, 168.00, -1, '👑', '["会员","超值"]', 365, 1);

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

-- 插入默认塔罗牌阵
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

-- 插入默认FAQ
INSERT INTO `faqs` (`question`, `answer`, `category`, `sort_order`, `is_hot`) VALUES
('什么是八字？', '八字是指一个人出生的年、月、日、时，用天干地支表示，共八个字。通过分析这八个字的五行生克关系，可以了解一个人的性格特点、运势走向等信息。', 'bazi', 1, 1),
('塔罗牌占卜准确吗？', '塔罗牌是一种心灵指引工具，其准确性取决于问卜者的心态和解读者的经验。塔罗牌更侧重于反映当下的心理状态和潜在的发展趋势，而非预测绝对的未来。', 'tarot', 2, 1),
('如何获得积分？', '您可以通过以下方式获得积分：1.每日签到 2.分享小程序 3.邀请好友 4.完善个人资料 5.首次排盘 6.绑定微信 7.关注公众号 8.浏览文章。VIP会员还可获得积分倍数加成。', 'points', 3, 1),
('VIP会员有什么特权？', 'VIP会员享有以下特权：1.每日积分双倍奖励 2.排盘次数无限制 3.解锁基础报告 4.解锁合婚功能 5.优先客服支持。', 'vip', 4, 1);

-- 插入默认评价
INSERT INTO `testimonials` (`user_name`, `content`, `rating`, `feature`, `is_featured`, `sort_order`) VALUES
('张先生', '八字排盘非常准确，分析报告详细专业，帮我更好地了解了自己的运势走向。', 5, 'bazi', 1, 1),
('李女士', '塔罗牌占卜给了我很大的心灵慰藉，解读非常到位，推荐！', 5, 'tarot', 1, 2),
('王先生', '和女朋友一起测了合婚，分析很专业，给我们很多相处建议，很有帮助。', 5, 'hehun', 1, 3);

-- 插入默认网站内容
INSERT INTO `site_contents` (`key`, `title`, `content`, `type`, `group`) VALUES
('home_banner_title', '首页横幅标题', '探索命理奥秘，掌握人生运势', 'text', 'home'),
('home_banner_subtitle', '首页横幅副标题', '专业的八字排盘、塔罗占卜、每日运势分析', 'text', 'home'),
('about_us', '关于我们', '<p>我们是一支专注于传统命理与现代科技结合的团队，致力于为用户提供专业、准确的命理分析服务。</p>', 'html', 'about'),
('contact_us', '联系我们', '如有任何问题，请联系客服：support@taichu.com', 'text', 'help');

-- 插入默认问题模板
INSERT INTO `question_templates` (`name`, `category`, `question`, `description`, `sort_order`) VALUES
('今日运势', 'general', '今天我的运势如何？', '询问今日整体运势', 1),
('事业发展', 'bazi', '我的事业发展前景如何？', '询问事业发展方向', 2),
('感情婚姻', 'bazi', '我的感情婚姻状况如何？', '询问感情婚姻运势', 3),
('财运分析', 'bazi', '我的财运如何？', '询问财运走势', 4),
('感情发展', 'tarot', '我和TA的感情会如何发展？', '询问感情发展', 5),
('事业选择', 'tarot', '我应该选择哪个方向？', '询问事业选择', 6);

-- 插入默认权限
INSERT INTO `tc_admin_permission` (`name`, `code`, `module`, `description`) VALUES
('用户查看', 'user_view', 'user', '查看用户列表和信息'),
('用户编辑', 'user_edit', 'user', '编辑用户信息'),
('积分调整', 'points_adjust', 'points', '调整用户积分'),
('积分查看', 'points_view', 'points', '查看积分记录'),
('配置管理', 'config_manage', 'config', '管理系统配置'),
('日志查看', 'log_view', 'log', '查看操作日志'),
('数据统计', 'stats_view', 'stats', '查看统计数据'),
('内容管理', 'content_manage', 'content', '管理内容数据');

-- 插入默认角色
INSERT INTO `tc_admin_role` (`name`, `code`, `description`, `is_super`) VALUES
('超级管理员', 'super_admin', '拥有所有权限', 1),
('普通管理员', 'normal_admin', '拥有常规管理权限', 0),
('运营人员', 'operator', '仅限查看和部分编辑权限', 0);

-- 为普通管理员角色分配权限
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT 2, id FROM `tc_admin_permission` WHERE code IN ('user_view', 'points_view', 'stats_view');

-- 为运营人员角色分配权限
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT 3, id FROM `tc_admin_permission` WHERE code IN ('user_view', 'stats_view');

-- 插入默认系统配置
INSERT INTO `system_config` (`config_key`, `config_value`, `config_type`, `description`, `category`, `is_editable`, `sort_order`) VALUES
-- 功能开关
('feature_vip_enabled', '1', 'bool', 'VIP会员功能开关', 'feature', 1, 1),
('feature_points_enabled', '1', 'bool', '积分系统开关', 'feature', 1, 2),
('feature_ai_analysis_enabled', '1', 'bool', 'AI解盘功能开关', 'feature', 1, 3),
('feature_yearly_fortune_enabled', '1', 'bool', '流年运势功能开关', 'feature', 1, 4),
('feature_dayun_analysis_enabled', '1', 'bool', '大运分析功能开关', 'feature', 1, 5),
('feature_dayun_chart_enabled', '1', 'bool', '运势K线图功能开关', 'feature', 1, 6),
('feature_hehun_enabled', '1', 'bool', '八字合婚功能开关', 'feature', 1, 7),
('feature_qiming_enabled', '1', 'bool', '取名建议功能开关', 'feature', 1, 8),
('feature_share_poster_enabled', '1', 'bool', '分享海报功能开关', 'feature', 1, 9),
('feature_invite_enabled', '1', 'bool', '邀请好友功能开关', 'feature', 1, 10),
('feature_limited_offer_enabled', '1', 'bool', '限时优惠功能开关', 'feature', 1, 11),
('feature_package_enabled', '1', 'bool', '组合套餐功能开关', 'feature', 1, 12),
('feature_report_tier_enabled', '1', 'bool', '报告分层功能开关', 'feature', 1, 13),
('feature_tasks_enabled', '1', 'bool', '积分任务功能开关', 'feature', 1, 14),
-- VIP配置
('vip_month_price', '19.9', 'float', '月度VIP价格（元）', 'vip', 1, 1),
('vip_quarter_price', '49', 'float', '季度VIP价格（元）', 'vip', 1, 2),
('vip_year_price', '168', 'float', '年度VIP价格（元）', 'vip', 1, 3),
('vip_daily_points_multiplier', '2', 'int', 'VIP每日积分倍数', 'vip', 1, 4),
('vip_paipan_limit', '-1', 'int', 'VIP每日排盘次数（-1为无限）', 'vip', 1, 5),
('vip_unlock_basic_report', '1', 'bool', 'VIP是否解锁基础报告', 'vip', 1, 6),
('vip_unlock_hehun', '1', 'bool', 'VIP是否解锁合婚功能', 'vip', 1, 7),
('vip_unlock_qiming', '0', 'bool', 'VIP是否解锁取名功能', 'vip', 1, 8),
-- 积分配置
('points_sign_daily', '10', 'int', '每日签到积分', 'points', 1, 1),
('points_sign_continuous_7', '20', 'int', '连续7天签到额外积分', 'points', 1, 2),
('points_sign_continuous_30', '50', 'int', '连续30天签到额外积分', 'points', 1, 3),
('points_share_app', '20', 'int', '分享小程序积分', 'points', 1, 4),
('points_invite_friend', '50', 'int', '邀请好友积分', 'points', 1, 5),
('points_complete_profile', '30', 'int', '完善资料积分', 'points', 1, 6),
('points_first_paipan', '20', 'int', '首次排盘积分', 'points', 1, 7),
('points_bind_wechat', '30', 'int', '绑定微信积分', 'points', 1, 8),
('points_follow_mp', '20', 'int', '关注公众号积分', 'points', 1, 9),
('points_browse_article', '5', 'int', '浏览文章积分', 'points', 1, 10),
-- 积分消耗配置
('points_cost_save_record', '10', 'int', '保存排盘记录积分', 'points_cost', 1, 1),
('points_cost_share_poster', '20', 'int', '分享海报积分', 'points_cost', 1, 2),
('points_cost_unlock_report', '50', 'int', '解锁详细报告积分', 'points_cost', 1, 3),
('points_cost_yearly_fortune', '30', 'int', '流年运势分析积分', 'points_cost', 1, 4),
('points_cost_dayun_analysis', '50', 'int', '大运运势评分积分', 'points_cost', 1, 5),
('points_cost_dayun_chart', '30', 'int', '运势K线图积分', 'points_cost', 1, 6),
('points_cost_hehun', '80', 'int', '八字合婚积分', 'points_cost', 1, 7),
('points_cost_qiming', '100', 'int', '取名建议积分', 'points_cost', 1, 8),
('points_cost_jiri', '20', 'int', '吉日查询积分', 'points_cost', 1, 9),
-- 限时优惠配置
('limited_offer_enabled', '0', 'bool', '是否开启限时优惠', 'limited_offer', 1, 1),
('limited_offer_discount', '50', 'int', '优惠折扣（%）', 'limited_offer', 1, 2),
('limited_offer_start_time', '', 'string', '优惠开始时间', 'limited_offer', 1, 3),
('limited_offer_end_time', '', 'string', '优惠结束时间', 'limited_offer', 1, 4),
('limited_offer_applicable_features', '["yearly_fortune","dayun_analysis"]', 'json', '适用的功能列表', 'limited_offer', 1, 5),
-- 组合套餐配置
('package_enabled', '1', 'bool', '是否开启组合套餐', 'package', 1, 1),
('packages', '{"fortune_combo":{"name":"运势分析套餐","original_points":110,"sale_points":80,"includes":["yearly_fortune","dayun_analysis","dayun_chart"],"description":"流年+大运+K线图打包"},"all_in_one":{"name":"全部解锁","original_points":200,"sale_points":150,"includes":["unlock_report","yearly_fortune","dayun_analysis"],"description":"专业版报告+全部高级分析"}}', 'json', '套餐配置', 'package', 1, 2),
-- 新用户优惠配置
('new_user_offer_enabled', '1', 'bool', '是否开启新用户优惠', 'new_user', 1, 1),
('new_user_discount', '50', 'int', '新用户折扣（%）', 'new_user', 1, 2),
('new_user_valid_hours', '24', 'int', '新用户优惠有效期（小时）', 'new_user', 1, 3),
-- 充值配置
('recharge_enabled', '1', 'bool', '是否开启充值功能', 'recharge', 1, 1),
('recharge_ratio', '10', 'int', '充值比例（1元=多少积分）', 'recharge', 1, 2),
('recharge_options', '[{"amount":10,"points":100,"bonus":0},{"amount":30,"points":300,"bonus":30},{"amount":50,"points":500,"bonus":80},{"amount":100,"points":1000,"bonus":200}]', 'json', '充值选项配置', 'recharge', 1, 3),
-- 报告分层配置
('report_tier_enabled', '1', 'bool', '是否开启报告分层', 'report_tier', 1, 1),
('basic_report_items', '["bazi","wuxing","shishen_basic","nayin"]', 'json', '基础报告包含项', 'report_tier', 1, 2),
('premium_report_items', '["mingpan_detail","xingge_analysis","shiye_caiyun","hunyin_ganqing","jiankang_tixing","yunshi_zonghe"]', 'json', '高级报告包含项', 'report_tier', 1, 3),
('premium_report_points', '50', 'int', '解锁高级报告所需积分', 'report_tier', 1, 4);

-- 插入默认支付配置占位
INSERT INTO `payment_configs` (`type`, `name`, `notify_url`, `is_enabled`) VALUES
('wechat_jsapi', '微信支付JSAPI', '/api/payment/notify/wechat', 0);

-- ============================================================
-- 数据库创建完成
-- ============================================================
