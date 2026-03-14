-- 太初命理数据库结构
-- 数据库: taichu

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 用户表
-- ----------------------------
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

-- ----------------------------
-- 八字排盘记录表
-- ----------------------------
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

-- ----------------------------
-- 积分记录表
-- ----------------------------
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

-- ----------------------------
-- 每日运势表
-- ----------------------------
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

-- ----------------------------
-- 用户反馈表
-- ----------------------------
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

-- ----------------------------
-- 签到记录表
-- ----------------------------
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

-- ----------------------------
-- 邀请记录表
-- ----------------------------
DROP TABLE IF EXISTS `tc_invite_record`;
CREATE TABLE `tc_invite_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `inviter_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '邀请人ID',
  `invitee_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被邀请人ID',
  `invite_code` varchar(20) NOT NULL DEFAULT '' COMMENT '邀请码',
  `points_reward` int(11) NOT NULL DEFAULT '20' COMMENT '奖励积分',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invitee` (`invitee_id`),
  KEY `idx_inviter` (`inviter_id`),
  KEY `idx_invite_code` (`invite_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请记录表';

SET FOREIGN_KEY_CHECKS = 1;
