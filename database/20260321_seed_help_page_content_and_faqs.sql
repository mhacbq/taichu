-- 帮助中心页面内容与 FAQ 默认种子
-- 执行方式：在已导入基础表结构后运行本文件

INSERT INTO `site_contents` (`page`, `key`, `value`, `type`, `description`, `is_enabled`, `sort_order`)
VALUES
('help', 'page_title', '帮助中心', 'text', '帮助中心页面标题', 1, 1),
('help', 'search_title', '有问题？我们来帮您', 'text', '帮助中心搜索标题', 1, 2),
('help', 'search_placeholder', '搜索问题关键词...', 'text', '帮助中心搜索框占位文案', 1, 3),
('help', 'hot_tags', '["积分","八字","登录","塔罗","充值"]', 'json', '帮助中心热门搜索标签(JSON数组)', 1, 4),
('help', 'contact_title', '还有其他问题？', 'text', '帮助中心联系区域标题', 1, 5),
('help', 'contact_desc', '如果以上问题没有解答您的疑问，欢迎联系我们', 'text', '帮助中心联系区域说明', 1, 6),
('help', 'contact_service_label', '在线客服', 'text', '联系渠道1名称', 1, 7),
('help', 'contact_service_value', '工作日 9:00-18:00', 'text', '联系渠道1内容', 1, 8),
('help', 'contact_email_label', '邮箱', 'text', '联系渠道2名称', 1, 9),
('help', 'contact_email_value', 'support@taichu.com', 'text', '联系渠道2内容', 1, 10),
('help', 'contact_wechat_label', '微信公众号', 'text', '联系渠道3名称', 1, 11),
('help', 'contact_wechat_value', '太初命理', 'text', '联系渠道3内容', 1, 12),
('help', 'feedback_button_text', '提交反馈', 'text', '帮助中心反馈按钮文案', 1, 13)
ON DUPLICATE KEY UPDATE
`value` = VALUES(`value`),
`type` = VALUES(`type`),
`description` = VALUES(`description`),
`is_enabled` = VALUES(`is_enabled`),
`sort_order` = VALUES(`sort_order`),
`updated_at` = CURRENT_TIMESTAMP;

INSERT INTO `faqs` (`question`, `answer`, `category`, `sort_order`)
SELECT '如何获取积分？', '注册可获得初始积分；每日签到、平台活动等也能持续领取积分。', 'points', 1
WHERE NOT EXISTS (SELECT 1 FROM `faqs` WHERE `question` = '如何获取积分？');

INSERT INTO `faqs` (`question`, `answer`, `category`, `sort_order`)
SELECT '八字排盘需要填写到几点几分吗？', '如果知道精确出生时间，建议尽量填写到分钟；若不确定，也可以选择估算时段或未知时辰。', 'bazi', 2
WHERE NOT EXISTS (SELECT 1 FROM `faqs` WHERE `question` = '八字排盘需要填写到几点几分吗？');

INSERT INTO `faqs` (`question`, `answer`, `category`, `sort_order`)
SELECT '塔罗占卜适合问什么问题？', '适合聚焦在当下的关系、工作、选择与情绪状态，问题越具体，得到的参考越清晰。', 'tarot', 3
WHERE NOT EXISTS (SELECT 1 FROM `faqs` WHERE `question` = '塔罗占卜适合问什么问题？');

INSERT INTO `faqs` (`question`, `answer`, `category`, `sort_order`)
SELECT '登录后为什么要完善资料？', '完善基础资料后，系统才能更准确地为你计算八字、同步积分与保存历史记录。', 'account', 4
WHERE NOT EXISTS (SELECT 1 FROM `faqs` WHERE `question` = '登录后为什么要完善资料？');

