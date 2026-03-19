-- 缺失表补丁 SQL
-- 创建后端代码中引用但数据库 backup 中缺失的表
-- 生成日期: 2026-03-19

-- --------------------------------------------------------
-- 通知相关表
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tc_notification` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `type` varchar(50) NOT NULL DEFAULT '' COMMENT '通知类型',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '通知标题',
  `content` text COMMENT '通知内容',
  `data` text COMMENT '附加数据(JSON)',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已读(0未读1已读)',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `read_at` datetime DEFAULT NULL COMMENT '读取时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户通知表';

CREATE TABLE IF NOT EXISTS `tc_notification_setting` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `type` varchar(50) NOT NULL DEFAULT '' COMMENT '通知类型',
  `push_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '推送开关',
  `email_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '邮件通知开关',
  `quiet_start` varchar(5) DEFAULT NULL COMMENT '免打扰开始时间(HH:MM)',
  `quiet_end` varchar(5) DEFAULT NULL COMMENT '免打扰结束时间(HH:MM)',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_type` (`user_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知设置表';

CREATE TABLE IF NOT EXISTS `tc_push_device` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `device_token` varchar(500) NOT NULL DEFAULT '' COMMENT '设备推送Token',
  `platform` varchar(20) NOT NULL DEFAULT 'web' COMMENT '平台(web/ios/android)',
  `device_info` text COMMENT '设备信息(JSON)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否有效',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_device_token` (`device_token`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推送设备表';

-- --------------------------------------------------------
-- 反作弊相关表
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_event` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `type` varchar(50) NOT NULL DEFAULT '' COMMENT '事件类型',
  `level` varchar(20) NOT NULL DEFAULT 'low' COMMENT '风险等级(low/medium/high/critical)',
  `description` varchar(500) DEFAULT '' COMMENT '描述',
  `detail` text COMMENT '详细数据(JSON)',
  `ip` varchar(50) DEFAULT '' COMMENT 'IP地址',
  `device_id` varchar(200) DEFAULT '' COMMENT '设备指纹',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '处理状态(0未处理1已处理2误报)',
  `handler_id` int(11) DEFAULT NULL COMMENT '处理人ID',
  `handle_note` varchar(500) DEFAULT '' COMMENT '处理备注',
  `handled_at` datetime DEFAULT NULL COMMENT '处理时间',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_level` (`level`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='反作弊事件表';

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_rule` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
  `type` varchar(50) NOT NULL DEFAULT '' COMMENT '规则类型',
  `description` varchar(500) DEFAULT '' COMMENT '规则描述',
  `condition_json` text COMMENT '触发条件(JSON)',
  `action` varchar(50) NOT NULL DEFAULT 'alert' COMMENT '触发动作(alert/block/ban)',
  `level` varchar(20) NOT NULL DEFAULT 'low' COMMENT '风险等级',
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否启用',
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT '优先级',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='反作弊规则表';

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_device` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fingerprint` varchar(200) NOT NULL DEFAULT '' COMMENT '设备指纹',
  `user_ids` text COMMENT '关联用户ID列表(JSON)',
  `os` varchar(50) DEFAULT '' COMMENT '操作系统',
  `browser` varchar(100) DEFAULT '' COMMENT '浏览器',
  `ip` varchar(50) DEFAULT '' COMMENT '最后IP',
  `risk_score` int(11) NOT NULL DEFAULT 0 COMMENT '风险评分(0-100)',
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否封禁',
  `block_reason` varchar(500) DEFAULT '' COMMENT '封禁原因',
  `blocked_at` datetime DEFAULT NULL COMMENT '封禁时间',
  `last_active_at` datetime DEFAULT NULL COMMENT '最后活跃时间',
  `device_count` int(11) NOT NULL DEFAULT 1 COMMENT '设备使用次数',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fingerprint` (`fingerprint`),
  KEY `idx_risk_score` (`risk_score`),
  KEY `idx_is_blocked` (`is_blocked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='反作弊设备表';

-- --------------------------------------------------------
-- 文章相关表
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tc_article` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL DEFAULT 0 COMMENT '分类ID',
  `title` varchar(300) NOT NULL DEFAULT '' COMMENT '文章标题',
  `slug` varchar(200) DEFAULT '' COMMENT 'URL别名',
  `summary` varchar(500) DEFAULT '' COMMENT '摘要',
  `content` longtext COMMENT '文章内容',
  `cover_image` varchar(500) DEFAULT '' COMMENT '封面图',
  `author` varchar(100) DEFAULT '' COMMENT '作者',
  `source` varchar(200) DEFAULT '' COMMENT '来源',
  `tags` varchar(500) DEFAULT '' COMMENT '标签(逗号分隔)',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态(0草稿1已发布)',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否精选',
  `view_count` int(11) NOT NULL DEFAULT 0 COMMENT '浏览次数',
  `seo_title` varchar(300) DEFAULT '' COMMENT 'SEO标题',
  `seo_description` varchar(500) DEFAULT '' COMMENT 'SEO描述',
  `seo_keywords` varchar(300) DEFAULT '' COMMENT 'SEO关键词',
  `published_at` datetime DEFAULT NULL COMMENT '发布时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

CREATE TABLE IF NOT EXISTS `tc_article_category` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT 0 COMMENT '父分类ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '分类名称',
  `slug` varchar(100) DEFAULT '' COMMENT 'URL别名',
  `description` varchar(500) DEFAULT '' COMMENT '分类描述',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否启用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章分类表';

-- --------------------------------------------------------
-- 系统任务相关表
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tc_system_task` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '任务名称',
  `script_id` int(11) NOT NULL DEFAULT 0 COMMENT '脚本ID',
  `cron` varchar(100) DEFAULT '' COMMENT 'Cron表达式',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态(0停用1启用)',
  `last_run_time` datetime DEFAULT NULL COMMENT '上次执行时间',
  `next_run_time` datetime DEFAULT NULL COMMENT '下次执行时间',
  `last_result` varchar(200) DEFAULT '' COMMENT '最后执行结果',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统定时任务表';

CREATE TABLE IF NOT EXISTS `tc_system_task_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL DEFAULT 0 COMMENT '任务ID',
  `task_name` varchar(100) DEFAULT '' COMMENT '任务名称',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '执行状态(0成功1失败)',
  `duration` int(11) DEFAULT 0 COMMENT '执行耗时(ms)',
  `message` text COMMENT '执行消息',
  `output` text COMMENT '执行输出',
  `created_at` datetime DEFAULT NULL COMMENT '执行时间',
  PRIMARY KEY (`id`),
  KEY `idx_task_id` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统任务执行日志';

CREATE TABLE IF NOT EXISTS `tc_system_script` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '脚本名称',
  `description` varchar(500) DEFAULT '' COMMENT '描述',
  `type` varchar(50) NOT NULL DEFAULT 'php' COMMENT '脚本类型(php/shell)',
  `content` text COMMENT '脚本内容',
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否启用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统脚本表';

-- --------------------------------------------------------
-- 塔罗记录表（完整字段）
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tc_tarot_record` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `spread_type` varchar(50) NOT NULL DEFAULT 'single' COMMENT '牌阵类型(single/three/celtic)',
  `question` varchar(500) DEFAULT '' COMMENT '用户问题',
  `cards` text COMMENT '抽取的牌(JSON)',
  `interpretation` longtext COMMENT 'AI解读结果',
  `is_public` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否公开',
  `points_cost` int(11) NOT NULL DEFAULT 0 COMMENT '消耗积分',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='塔罗占卜记录表';

-- --------------------------------------------------------
-- 任务日志表（积分任务）
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tc_task_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `task_type` varchar(50) NOT NULL DEFAULT '' COMMENT '任务类型',
  `task_name` varchar(100) DEFAULT '' COMMENT '任务名称',
  `points` int(11) NOT NULL DEFAULT 0 COMMENT '获得积分',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态(1成功0失败)',
  `extra` text COMMENT '扩展数据(JSON)',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_task_type` (`task_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分任务执行记录表';
