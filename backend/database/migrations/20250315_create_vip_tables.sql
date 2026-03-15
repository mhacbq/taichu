-- VIP会员订单表
CREATE TABLE IF NOT EXISTS `vip_orders` (
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
CREATE TABLE IF NOT EXISTS `user_vip` (
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

-- 积分任务完成记录表
CREATE TABLE IF NOT EXISTS `user_task_logs` (
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

-- 分享记录表
CREATE TABLE IF NOT EXISTS `share_logs` (
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

-- 邀请记录表
CREATE TABLE IF NOT EXISTS `invite_records` (
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

-- 八字合婚记录表
CREATE TABLE IF NOT EXISTS `hehun_records` (
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
CREATE TABLE IF NOT EXISTS `qiming_records` (
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
CREATE TABLE IF NOT EXISTS `jiri_records` (
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
