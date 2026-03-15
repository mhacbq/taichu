-- ========================================================
-- SEO配置相关表
-- 用于存储SEO配置、搜索引擎提交记录、关键词排名等
-- ========================================================

-- 页面SEO配置表
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
  `priority` DECIMAL(2,1) DEFAULT 0.5 COMMENT '站点地图优先级(0.0-1.0)',
  `changefreq` VARCHAR(20) DEFAULT 'weekly' COMMENT '更新频率',
  `is_active` TINYINT DEFAULT 1 COMMENT '是否启用',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_route` (`route`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面SEO配置表';

-- 插入默认SEO配置
INSERT INTO `tc_seo_config` (`route`, `title`, `description`, `keywords`, `image`, `priority`, `changefreq`) VALUES
('/', '太初命理 - 专业八字排盘_塔罗占卜_每日运势', '太初命理是专业的AI智能命理分析平台，提供八字排盘、塔罗占卜、每日运势等服务。精准分析，科学解读，10万+用户信赖的命理工具。', '["八字排盘", "塔罗占卜", "每日运势", "命理分析", "AI算命", "在线排盘"]', '/images/og-home.jpg', 1.0, 'daily'),
('/bazi', '免费八字排盘_在线生辰八字测算_专业命理分析', '免费在线八字排盘工具，输入出生日期即可生成专业八字命盘。包含四柱、藏干、十神、神煞分析，精准解读您的性格、事业、财运、婚姻运势。', '["八字排盘", "免费八字", "生辰八字", "四柱八字", "八字测算", "八字算命"]', '/images/og-bazi.jpg', 0.9, 'weekly'),
('/tarot', '免费塔罗牌占卜_在线塔罗测试_AI智能解牌', '免费在线塔罗牌占卜，涵盖爱情、事业、财运、运势等多个维度。AI智能解牌，专业塔罗师解读，让塔罗指引您的人生方向。', '["塔罗占卜", "塔罗牌", "塔罗测试", "免费塔罗", "在线塔罗", "塔罗解牌"]', '/images/og-tarot.jpg', 0.9, 'weekly'),
('/daily', '今日运势查询_每日星座运势_黄历宜忌', '查看今日运势，包含十二星座每日运势、黄历宜忌、时辰吉凶。每日更新，科学预测，助您趋吉避凶，把握最佳时机。', '["今日运势", "每日运势", "星座运势", "黄历查询", "今日宜忌", "时辰吉凶"]', '/images/og-daily.jpg', 0.9, 'daily'),
('/profile', '个人中心_我的排盘记录_积分管理', '管理您的太初命理个人账户，查看历史排盘记录、收藏内容、积分余额和充值记录。', '["个人中心", "命理记录", "八字记录", "塔罗记录", "积分充值"]', '/images/og-profile.jpg', 0.3, 'monthly'),
('/recharge', '积分充值_购买命理服务 - 太初命理', '充值积分，解锁更多专业的AI命理分析服务。八字排盘、塔罗占卜、深度解读等您体验。', '["积分充值", "命理服务", "八字付费", "塔罗付费"]', '/images/og-recharge.jpg', 0.7, 'monthly'),
('/help', '帮助中心_使用指南_常见问题 - 太初命理', '太初命理使用指南和常见问题解答，帮助您更好地使用八字排盘、塔罗占卜等功能。', '["帮助中心", "使用指南", "常见问题", "命理教程", "八字教程", "塔罗教程"]', '/images/og-help.jpg', 0.6, 'monthly'),
('/404', '页面未找到 - 太初命理', '抱歉，您访问的页面不存在。返回太初命理首页，探索八字排盘、塔罗占卜等AI智能命理服务。', '["404", "页面未找到"]', '/images/og-default.jpg', 0.1, 'never');

-- 关键词排名表
CREATE TABLE IF NOT EXISTS `tc_seo_keywords` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `keyword` VARCHAR(255) NOT NULL COMMENT '关键词',
  `category` VARCHAR(50) DEFAULT 'general' COMMENT '分类(core-核心词,long-长尾词,related-相关词,brand-品牌词)',
  `baidu_rank` INT DEFAULT 0 COMMENT '百度排名(0表示未排名)',
  `bing_rank` INT DEFAULT 0 COMMENT '必应排名(0表示未排名)',
  `360_rank` INT DEFAULT 0 COMMENT '360排名(0表示未排名)',
  `sogou_rank` INT DEFAULT 0 COMMENT '搜狗排名(0表示未排名)',
  `search_volume` INT DEFAULT 0 COMMENT '月搜索量',
  `competition` VARCHAR(20) DEFAULT 'medium' COMMENT '竞争程度(low/medium/high)',
  `is_target` TINYINT DEFAULT 1 COMMENT '是否为目标关键词',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_keyword` (`keyword`),
  KEY `idx_category` (`category`),
  KEY `idx_target` (`is_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='关键词排名表';

-- 插入默认关键词
INSERT INTO `tc_seo_keywords` (`keyword`, `category`, `baidu_rank`, `bing_rank`, `search_volume`, `competition`) VALUES
('八字排盘', 'core', 15, 12, 12500, 'high'),
('免费八字', 'long', 8, 6, 8900, 'medium'),
('生辰八字', 'long', 18, 15, 7300, 'medium'),
('四柱八字', 'long', 22, 19, 5600, 'medium'),
('八字算命', 'core', 35, 28, 18200, 'high'),
('塔罗占卜', 'core', 23, 18, 10200, 'high'),
('塔罗牌', 'core', 56, 42, 22100, 'high'),
('免费塔罗', 'long', 12, 10, 5600, 'medium'),
('塔罗测试', 'long', 19, 16, 4800, 'low'),
('在线塔罗', 'long', 14, 11, 3900, 'low'),
('每日运势', 'core', 45, 38, 15800, 'high'),
('今日运势', 'long', 28, 24, 12300, 'high'),
('星座运势', 'related', 89, 76, 35600, 'high'),
('黄历查询', 'long', 33, 29, 8700, 'medium'),
('命理分析', 'related', 34, 28, 4200, 'low'),
('AI算命', 'brand', 5, 3, 1800, 'low'),
('太初命理', 'brand', 2, 1, 520, 'low'),
('在线排盘', 'long', 41, 35, 3200, 'medium');

-- 页面收录状态表
CREATE TABLE IF NOT EXISTS `tc_seo_indexed_pages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `url` VARCHAR(500) NOT NULL COMMENT '页面URL',
  `page_route` VARCHAR(255) DEFAULT '' COMMENT '页面路由',
  `title` VARCHAR(255) DEFAULT '' COMMENT '页面标题',
  `baidu_status` VARCHAR(20) DEFAULT 'pending' COMMENT '百度收录状态(pending/indexed/not_indexed)',
  `bing_status` VARCHAR(20) DEFAULT 'pending' COMMENT '必应收录状态',
  `baidu_last_crawl` DATETIME DEFAULT NULL COMMENT '百度最后抓取时间',
  `bing_last_crawl` DATETIME DEFAULT NULL COMMENT '必应最后抓取时间',
  `organic_traffic` INT DEFAULT 0 COMMENT '自然搜索流量(近30天)',
  `indexed_at` DATETIME DEFAULT NULL COMMENT '收录时间',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_url` (`url`),
  KEY `idx_baidu_status` (`baidu_status`),
  KEY `idx_bing_status` (`bing_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面收录状态表';

-- 搜索引擎提交记录表
CREATE TABLE IF NOT EXISTS `tc_seo_submissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `engine` VARCHAR(50) NOT NULL COMMENT '搜索引擎(baidu/bing/360/sogou)',
  `type` VARCHAR(50) NOT NULL COMMENT '提交类型(url/sitemap)',
  `url` VARCHAR(500) NOT NULL COMMENT '提交的URL',
  `status` VARCHAR(20) DEFAULT 'pending' COMMENT '提交状态',
  `response` TEXT COMMENT '返回结果',
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  KEY `idx_engine` (`engine`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='搜索引擎提交记录表';

-- SEO流量统计表（按天）
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
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_date_engine` (`stat_date`, `engine`),
  KEY `idx_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO流量统计表';

-- robots.txt 配置表
CREATE TABLE IF NOT EXISTS `tc_seo_robots` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_agent` VARCHAR(255) NOT NULL COMMENT 'User-agent',
  `rules` JSON NOT NULL COMMENT '规则数组(allow/disallow)',
  `crawl_delay` INT DEFAULT 0 COMMENT '抓取延迟(秒)',
  `sitemap_url` VARCHAR(500) DEFAULT '' COMMENT '站点地图地址',
  `is_active` TINYINT DEFAULT 1 COMMENT '是否启用',
  `sort_order` INT DEFAULT 0 COMMENT '排序',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='robots.txt配置表';

-- 插入默认robots配置
INSERT INTO `tc_seo_robots` (`user_agent`, `rules`, `crawl_delay`, `sitemap_url`) VALUES
('*', '[{"type": "allow", "path": "/"}, {"type": "disallow", "path": "/admin/"}, {"type": "disallow", "path": "/profile/"}, {"type": "disallow", "path": "/api/"}]', 1, 'https://taichu.chat/sitemap.xml'),
('Baiduspider', '[{"type": "allow", "path": "/"}, {"type": "disallow", "path": "/admin/"}]', 1, ''),
('Sogou spider', '[{"type": "allow", "path": "/"}]', 1, ''),
('360Spider', '[{"type": "allow", "path": "/"}]', 1, '');

-- ========================================================
-- SEO相关视图
-- ========================================================

-- 关键词排名汇总视图
CREATE OR REPLACE VIEW `v_seo_keywords_summary` AS
SELECT 
  category,
  COUNT(*) as total_count,
  SUM(CASE WHEN baidu_rank > 0 AND baidu_rank <= 10 THEN 1 ELSE 0 END) as baidu_top10,
  SUM(CASE WHEN baidu_rank > 0 AND baidu_rank <= 50 THEN 1 ELSE 0 END) as baidu_top50,
  SUM(CASE WHEN bing_rank > 0 AND bing_rank <= 10 THEN 1 ELSE 0 END) as bing_top10,
  SUM(CASE WHEN bing_rank > 0 AND bing_rank <= 50 THEN 1 ELSE 0 END) as bing_top50
FROM tc_seo_keywords
WHERE is_target = 1
GROUP BY category;

-- 页面收录统计视图
CREATE OR REPLACE VIEW `v_seo_indexed_summary` AS
SELECT 
  COUNT(*) as total_pages,
  SUM(CASE WHEN baidu_status = 'indexed' THEN 1 ELSE 0 END) as baidu_indexed,
  SUM(CASE WHEN bing_status = 'indexed' THEN 1 ELSE 0 END) as bing_indexed,
  SUM(organic_traffic) as total_organic_traffic
FROM tc_seo_indexed_pages;

-- ========================================================
-- 创建SEO统计存储过程
-- ========================================================

DELIMITER //

-- 更新关键词排名
CREATE PROCEDURE `sp_update_keyword_rank`(
  IN p_keyword VARCHAR(255),
  IN p_engine VARCHAR(50),
  IN p_rank INT
)
BEGIN
  IF p_engine = 'baidu' THEN
    UPDATE tc_seo_keywords SET baidu_rank = p_rank, updated_at = NOW() WHERE keyword = p_keyword;
  ELSEIF p_engine = 'bing' THEN
    UPDATE tc_seo_keywords SET bing_rank = p_rank, updated_at = NOW() WHERE keyword = p_keyword;
  ELSEIF p_engine = '360' THEN
    UPDATE tc_seo_keywords SET 360_rank = p_rank, updated_at = NOW() WHERE keyword = p_keyword;
  ELSEIF p_engine = 'sogou' THEN
    UPDATE tc_seo_keywords SET sogou_rank = p_rank, updated_at = NOW() WHERE keyword = p_keyword;
  END IF;
END //

-- 记录搜索引擎提交
CREATE PROCEDURE `sp_record_submission`(
  IN p_engine VARCHAR(50),
  IN p_type VARCHAR(50),
  IN p_url VARCHAR(500),
  IN p_status VARCHAR(20),
  IN p_response TEXT
)
BEGIN
  INSERT INTO tc_seo_submissions (engine, type, url, status, response, completed_at)
  VALUES (p_engine, p_type, p_url, p_status, p_response, NOW());
END //

-- 获取SEO统计数据
CREATE PROCEDURE `sp_get_seo_stats`()
BEGIN
  SELECT 
    (SELECT COUNT(*) FROM tc_seo_keywords WHERE is_target = 1) as total_keywords,
    (SELECT COUNT(*) FROM tc_seo_keywords WHERE is_target = 1 AND baidu_rank > 0 AND baidu_rank <= 10) as baidu_top10,
    (SELECT COUNT(*) FROM tc_seo_indexed_pages WHERE baidu_status = 'indexed') as baidu_indexed,
    (SELECT COUNT(*) FROM tc_seo_indexed_pages WHERE bing_status = 'indexed') as bing_indexed,
    (SELECT SUM(organic_traffic) FROM tc_seo_indexed_pages) as total_organic_traffic;
END //

DELIMITER ;

-- ========================================================
-- 初始化默认页面收录数据
-- ========================================================

INSERT INTO `tc_seo_indexed_pages` (`url`, `page_route`, `title`, `baidu_status`, `bing_status`, `organic_traffic`) VALUES
('https://taichu.chat/', '/', '太初命理 - AI智能命理分析平台', 'indexed', 'indexed', 1250),
('https://taichu.chat/bazi', '/bazi', '免费八字排盘_在线生辰八字测算', 'indexed', 'indexed', 680),
('https://taichu.chat/tarot', '/tarot', '免费塔罗牌占卜_在线塔罗测试', 'indexed', 'indexed', 520),
('https://taichu.chat/daily', '/daily', '今日运势查询_每日星座运势', 'indexed', 'pending', 380),
('https://taichu.chat/help', '/help', '帮助中心_使用指南_常见问题', 'indexed', 'indexed', 95),
('https://taichu.chat/recharge', '/recharge', '积分充值_购买命理服务', 'pending', 'pending', 42);

-- ========================================================
-- 初始化流量统计数据
-- ========================================================

INSERT INTO `tc_seo_traffic_daily` (`stat_date`, `engine`, `impressions`, `clicks`, `ctr`, `avg_position`, `organic_sessions`, `organic_users`) VALUES
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'baidu', 5234, 312, 5.96, 23.5, 298, 245),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'bing', 1842, 128, 6.95, 31.2, 118, 98),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'baidu', 4892, 298, 6.09, 24.1, 285, 232),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'bing', 1721, 115, 6.68, 32.5, 108, 91),
(DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'baidu', 5123, 305, 5.95, 23.8, 292, 240),
(DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'bing', 1805, 122, 6.76, 31.8, 112, 95);
