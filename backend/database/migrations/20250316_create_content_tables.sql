-- FAQ表
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
    INDEX `idx_is_hot` (`is_hot`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='FAQ表';

-- 插入默认FAQ
INSERT INTO `faqs` (`question`, `answer`, `category`, `sort_order`, `is_hot`) VALUES
('什么是八字？', '八字是指一个人出生的年、月、日、时，用天干地支表示，共八个字。通过分析这八个字的五行生克关系，可以了解一个人的性格特点、运势走向等信息。', 'bazi', 1, 1),
('塔罗牌占卜准确吗？', '塔罗牌是一种心灵指引工具，其准确性取决于问卜者的心态和解读者的经验。塔罗牌更侧重于反映当下的心理状态和潜在的发展趋势，而非预测绝对的未来。', 'tarot', 2, 1),
('如何获得积分？', '您可以通过以下方式获得积分：1.每日签到 2.分享小程序 3.邀请好友 4.完善个人资料 5.首次排盘 6.绑定微信 7.关注公众号 8.浏览文章。VIP会员还可获得积分倍数加成。', 'points', 3, 1),
('VIP会员有什么特权？', 'VIP会员享有以下特权：1.每日积分双倍奖励 2.排盘次数无限制 3.解锁基础报告 4.解锁合婚功能 5.优先客服支持。', 'vip', 4, 1),
('积分会过期吗？', '获得的积分永久有效，不会过期。您可以随时使用积分兑换功能或商品。', 'points', 5, 0);

-- 每日运势模板表
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
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_score_range` (`score_range_min`, `score_range_max`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日运势模板表';

-- 用户评价表
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
    INDEX `idx_is_featured` (`is_featured`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户评价表';

-- 插入默认评价
INSERT INTO `testimonials` (`user_name`, `content`, `rating`, `feature`, `is_featured`, `sort_order`) VALUES
('张先生', '八字排盘非常准确，分析报告详细专业，帮我更好地了解了自己的运势走向。', 5, 'bazi', 1, 1),
('李女士', '塔罗牌占卜给了我很大的心灵慰藉，解读非常到位，推荐！', 5, 'tarot', 1, 2),
('王先生', '和女朋友一起测了合婚，分析很专业，给我们很多相处建议，很有帮助。', 5, 'hehun', 1, 3);

-- 网站内容表
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
    
    INDEX `idx_group` (`group`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='网站内容表';

-- 插入默认网站内容
INSERT INTO `site_contents` (`key`, `title`, `content`, `type`, `group`) VALUES
('home_banner_title', '首页横幅标题', '探索命理奥秘，掌握人生运势', 'text', 'home'),
('home_banner_subtitle', '首页横幅副标题', '专业的八字排盘、塔罗占卜、每日运势分析', 'text', 'home'),
('about_us', '关于我们', '<p>我们是一支专注于传统命理与现代科技结合的团队，致力于为用户提供专业、准确的命理分析服务。</p>', 'html', 'about'),
('contact_us', '联系我们', '如有任何问题，请联系客服：support@taichu.com', 'text', 'help'),
('privacy_policy', '隐私政策', '我们重视您的隐私保护，所有数据均经过加密处理，不会泄露给第三方。', 'text', 'help'),
('terms_of_service', '服务条款', '使用本服务即表示您同意我们的服务条款。', 'text', 'help');

-- 问题模板表
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
    
    INDEX `idx_category` (`category`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问题模板表';

-- 插入默认问题模板
INSERT INTO `question_templates` (`name`, `category`, `question`, `description`, `sort_order`) VALUES
('今日运势', 'general', '今天我的运势如何？', '询问今日整体运势', 1),
('事业发展', 'bazi', '我的事业发展前景如何？', '询问事业发展方向', 2),
('感情婚姻', 'bazi', '我的感情婚姻状况如何？', '询问感情婚姻运势', 3),
('财运分析', 'bazi', '我的财运如何？', '询问财运走势', 4),
('感情发展', 'tarot', '我和TA的感情会如何发展？', '询问感情发展', 5),
('事业选择', 'tarot', '我应该选择哪个方向？', '询问事业选择', 6);
