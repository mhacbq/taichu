-- SEO配置表扩展
-- 新增字段：route_path（路由路径）、og_image（分享图片）、robots、structured_data（JSON-LD）、sort_order（排序）

ALTER TABLE `tc_seo_config`
  ADD COLUMN `route_path` varchar(200) DEFAULT NULL COMMENT '路由路径，如 /bazi、/tarot' AFTER `page_type`,
  ADD COLUMN `og_image` varchar(500) DEFAULT NULL COMMENT 'Open Graph分享图片URL' AFTER `description`,
  ADD COLUMN `robots` varchar(100) DEFAULT 'index,follow' COMMENT 'robots指令' AFTER `og_image`,
  ADD COLUMN `structured_data` text DEFAULT NULL COMMENT 'JSON-LD结构化数据' AFTER `robots`,
  ADD COLUMN `sort_order` int(11) DEFAULT '0' COMMENT '排序（越小越前）' AFTER `structured_data`;

-- 为route_path添加索引
ALTER TABLE `tc_seo_config` ADD INDEX `idx_route_path` (`route_path`);

-- 插入默认的SEO配置数据（如果不存在）
INSERT IGNORE INTO `tc_seo_config` (`page_type`, `route_path`, `title`, `keywords`, `description`, `og_image`, `robots`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
('home', '/', '太初命理 - 专业八字排盘_塔罗占卜_每日运势', '八字排盘,塔罗占卜,每日运势,命理分析,AI算命,在线排盘,生辰八字,星座运势', '太初命理是专业的AI智能命理分析平台，提供八字排盘、塔罗占卜、每日运势、紫微斗数等服务。精准分析，科学解读，10万+用户信赖的命理工具。', '/images/og-home.jpg', 'index,follow', 1, 1, NOW(), NOW()),
('bazi', '/bazi', '免费八字排盘_在线生辰八字测算_专业命理分析', '八字排盘,免费八字,生辰八字,四柱八字,八字测算,八字算命,八字命盘,八字分析', '免费在线八字排盘工具，输入出生日期即可生成专业八字命盘。包含四柱、藏干、十神、神煞分析，精准解读您的性格、事业、财运、婚姻运势。', '/images/og-bazi.jpg', 'index,follow', 1, 2, NOW(), NOW()),
('tarot', '/tarot', '免费塔罗牌占卜_在线塔罗测试_AI智能解牌', '塔罗占卜,塔罗牌,塔罗测试,免费塔罗,在线塔罗,塔罗解牌,塔罗牌阵,塔罗爱情', '免费在线塔罗牌占卜，涵盖爱情、事业、财运、运势等多个维度。AI智能解牌，专业塔罗师解读，让塔罗指引您的人生方向。', '/images/og-tarot.jpg', 'index,follow', 1, 3, NOW(), NOW()),
('daily', '/daily', '今日运势查询_每日星座运势_黄历宜忌', '今日运势,每日运势,星座运势,黄历查询,今日宜忌,时辰吉凶,每日运程,运势预测', '查看今日运势，包含十二星座每日运势、黄历宜忌、时辰吉凶。每日更新，科学预测，助您趋吉避凶，把握最佳时机。', '/images/og-daily.jpg', 'index,follow', 1, 4, NOW(), NOW()),
('hehun', '/hehun', '八字合婚_婚姻配对_缘分分析', '八字合婚,婚姻配对,八字匹配,缘分分析,合婚测试', '通过双方八字分析婚姻匹配度，了解缘分深浅，专业AI解读婚姻运势。', '/images/og-default.jpg', 'index,follow', 1, 5, NOW(), NOW()),
('liuyao', '/liuyao', '六爻占卜_周易算卦_在线预测', '六爻占卜,周易,算卦,问事,预测,六爻排盘', '传统周易六爻占卜，解答心中疑惑，AI智能解卦。', '/images/og-default.jpg', 'index,follow', 1, 6, NOW(), NOW()),
('qiming', '/qiming', '取名建议_八字起名_五行取名', '取名,起名,八字取名,五行取名,宝宝取名,姓名分析', '结合生辰八字与五行，由AI为新生儿推荐寓意美好的名字。', '/images/og-default.jpg', 'index,follow', 1, 7, NOW(), NOW()),
('yearly-fortune', '/yearly-fortune', '流年运势_全年运势分析_月运预测', '流年运势,全年运势,运势分析,月运,开运建议', '结合个人八字，AI深度解析全年运势，提供每月吉凶提醒与开运建议。', '/images/og-default.jpg', 'index,follow', 1, 8, NOW(), NOW()),
('help', '/help', '帮助中心_使用指南_常见问题', '帮助中心,使用指南,常见问题,命理教程,八字教程,塔罗教程', '太初命理使用指南和常见问题解答，帮助您更好地使用八字排盘、塔罗占卜等功能。', '/images/og-help.jpg', 'index,follow', 1, 9, NOW(), NOW()),
('recharge', '/recharge', '积分充值_购买命理服务', '积分充值,命理服务,八字付费,塔罗付费', '充值积分，解锁更多专业的AI命理分析服务。八字排盘、塔罗占卜、深度解读等您体验。', '/images/og-recharge.jpg', 'index,follow', 1, 10, NOW(), NOW()),
('agreement', '/legal/agreement', '用户协议 - 太初命理', '用户协议,服务条款', '太初命理用户服务协议。', NULL, 'index,follow', 1, 11, NOW(), NOW()),
('privacy', '/legal/privacy', '隐私政策 - 太初命理', '隐私政策,隐私保护', '太初命理隐私政策说明。', NULL, 'index,follow', 1, 12, NOW(), NOW());
