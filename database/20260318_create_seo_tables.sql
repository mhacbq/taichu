SET NAMES utf8mb4;

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
