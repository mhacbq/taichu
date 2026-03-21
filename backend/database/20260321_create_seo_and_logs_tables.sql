-- SEO配置表
CREATE TABLE IF NOT EXISTS `tc_seo_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_type` varchar(50) NOT NULL COMMENT '页面类型',
  `title` varchar(200) NOT NULL COMMENT '页面标题',
  `keywords` varchar(500) DEFAULT NULL COMMENT '关键词',
  `description` varchar(500) DEFAULT NULL COMMENT '页面描述',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1-启用 0-禁用',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '是否删除',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_page_type` (`page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SEO配置表';

-- 操作日志表
CREATE TABLE IF NOT EXISTS `tc_admin_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `admin_name` varchar(50) DEFAULT NULL COMMENT '管理员名称',
  `action` varchar(100) NOT NULL COMMENT '操作动作',
  `module` varchar(50) DEFAULT NULL COMMENT '模块',
  `target_type` varchar(50) DEFAULT NULL COMMENT '目标类型',
  `target_id` int(11) DEFAULT NULL COMMENT '目标ID',
  `request_method` varchar(10) DEFAULT NULL COMMENT '请求方法',
  `request_url` varchar(500) DEFAULT NULL COMMENT '请求URL',
  `request_params` text COMMENT '请求参数',
  `ip_address` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1-成功 0-失败',
  `error_message` text COMMENT '错误信息',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_action` (`action`),
  KEY `idx_module` (`module`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';

-- 登录日志表
CREATE TABLE IF NOT EXISTS `tc_admin_login_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `admin_name` varchar(50) DEFAULT NULL COMMENT '管理员名称',
  `login_time` datetime DEFAULT NULL COMMENT '登录时间',
  `logout_time` datetime DEFAULT NULL COMMENT '退出时间',
  `login_ip` varchar(50) DEFAULT NULL COMMENT '登录IP',
  `login_location` varchar(200) DEFAULT NULL COMMENT '登录位置',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `login_status` tinyint(1) DEFAULT '1' COMMENT '登录状态 1-成功 0-失败',
  `error_message` varchar(500) DEFAULT NULL COMMENT '错误信息',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_login_time` (`login_time`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录日志表';

-- API日志表
CREATE TABLE IF NOT EXISTS `tc_api_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `method` varchar(10) NOT NULL COMMENT '请求方法',
  `path` varchar(500) NOT NULL COMMENT '请求路径',
  `params` text COMMENT '请求参数',
  `request_body` text COMMENT '请求体',
  `response_body` text COMMENT '响应体',
  `status` int(11) DEFAULT NULL COMMENT 'HTTP状态码',
  `code` varchar(20) DEFAULT NULL COMMENT '业务状态码',
  `message` varchar(500) DEFAULT NULL COMMENT '响应消息',
  `duration` int(11) DEFAULT NULL COMMENT '耗时(ms)',
  `ip_address` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_path` (`path`(255)),
  KEY `idx_method` (`method`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API日志表';
