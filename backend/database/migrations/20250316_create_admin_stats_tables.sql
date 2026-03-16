-- 网站统计表（按日统计）
CREATE TABLE IF NOT EXISTS `site_daily_stats` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `stat_date` date NOT NULL COMMENT '统计日期',
  
  -- 用户统计
  `new_users` int(11) NOT NULL DEFAULT '0' COMMENT '新增用户数',
  `active_users` int(11) NOT NULL DEFAULT '0' COMMENT '活跃用户数',
  `total_users` int(11) NOT NULL DEFAULT '0' COMMENT '累计用户数',
  
  -- 积分统计
  `points_given` int(11) NOT NULL DEFAULT '0' COMMENT '发放积分总数',
  `points_consumed` int(11) NOT NULL DEFAULT '0' COMMENT '消耗积分总数',
  `points_balance` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分余额总和',
  
  -- 占卜统计
  `bazi_count` int(11) NOT NULL DEFAULT '0' COMMENT '八字排盘次数',
  `tarot_count` int(11) NOT NULL DEFAULT '0' COMMENT '塔罗占卜次数',
  `liuyao_count` int(11) NOT NULL DEFAULT '0' COMMENT '六爻占卜次数',
  `hehun_count` int(11) NOT NULL DEFAULT '0' COMMENT '合婚次数',
  `daily_fortune_count` int(11) NOT NULL DEFAULT '0' COMMENT '每日运势查看次数',
  
  -- 收入统计
  `order_count` int(11) NOT NULL DEFAULT '0' COMMENT '订单数',
  `order_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `paid_count` int(11) NOT NULL DEFAULT '0' COMMENT '支付成功订单数',
  `paid_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际支付金额',
  `refund_count` int(11) NOT NULL DEFAULT '0' COMMENT '退款订单数',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  
  -- 访问统计
  `pv_count` int(11) NOT NULL DEFAULT '0' COMMENT '页面浏览量',
  `uv_count` int(11) NOT NULL DEFAULT '0' COMMENT '独立访客数',
  
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stat_date` (`stat_date`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站每日统计表';

-- 管理员角色表（扩展已有角色表）
CREATE TABLE IF NOT EXISTS `tc_admin_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL DEFAULT '' COMMENT '角色名称',
  `role_code` varchar(50) NOT NULL DEFAULT '' COMMENT '角色代码：super_admin/operator/customer_service',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '角色描述',
  `permissions` text COMMENT '权限列表（JSON格式）',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0禁用 1启用',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_code` (`role_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员角色表';

-- 管理员用户角色关联表
CREATE TABLE IF NOT EXISTS `tc_admin_user_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `role_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '角色ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admin_role` (`admin_id`, `role_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员角色关联表';

-- 订单表（扩展已有充值订单表，支持VIP购买）
CREATE TABLE IF NOT EXISTS `tc_vip_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(64) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `vip_package_id` int(11) NOT NULL DEFAULT '0' COMMENT 'VIP套餐ID',
  `package_name` varchar(100) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `package_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '套餐价格',
  `package_duration` int(11) NOT NULL DEFAULT '0' COMMENT '套餐时长（天）',
  
  -- 支付信息
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际支付金额',
  `pay_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付方式：0未支付 1微信支付 2支付宝',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `pay_trade_no` varchar(128) NOT NULL DEFAULT '' COMMENT '第三方支付流水号',
  
  -- 订单状态
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '订单状态：0待支付 1已支付 2已取消 3已退款',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `refund_time` datetime DEFAULT NULL COMMENT '退款时间',
  `refund_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '退款原因',
  
  -- 有效期
  `vip_start_time` datetime DEFAULT NULL COMMENT 'VIP开始时间',
  `vip_end_time` datetime DEFAULT NULL COMMENT 'VIP结束时间',
  
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_pay_time` (`pay_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='VIP订单表';

-- VIP套餐表
CREATE TABLE IF NOT EXISTS `tc_vip_package` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `package_name` varchar(100) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `duration` int(11) NOT NULL DEFAULT '0' COMMENT '时长（天）',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格',
  `original_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原价',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '套餐描述',
  `features` text COMMENT '套餐权益（JSON格式）',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `is_recommend` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐：0否 1是',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0禁用 1启用',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='VIP套餐表';
