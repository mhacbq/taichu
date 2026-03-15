-- =====================================================
-- 太初命理系统 - 测试数据插入脚本
-- 包含测试用户和示例数据（可选执行）
-- =====================================================

USE taichu;

-- =====================================================
-- 1. 插入测试用户
-- =====================================================

INSERT INTO `tc_user` (`id`, `openid`, `phone`, `nickname`, `avatar`, `gender`, `birth_date`, `birth_time`, `birth_place`, `points`, `vip_level`, `status`, `last_login_at`, `created_at`) VALUES
(1, 'test_openid_001', '13800138000', '测试用户', 'https://example.com/avatar.jpg', 1, '1990-05-20', '08:30:00', '北京市', 500, 0, 1, NOW(), NOW()),
(2, 'test_openid_002', '13800138001', '张三', '', 1, '1985-08-15', '14:20:00', '上海市', 1000, 1, 1, NOW(), NOW()),
(3, 'test_openid_003', '13800138002', '李四', '', 2, '1992-03-10', '09:00:00', '广州市', 200, 0, 1, NOW(), NOW()),
(4, 'test_openid_004', '13800138003', '王五', '', 1, '1988-11-25', '16:45:00', '深圳市', 3000, 3, 1, NOW(), NOW()),
(5, 'test_openid_005', '13800138004', '赵六', '', 2, '1995-07-08', '11:30:00', '杭州市', 50, 0, 1, NOW(), NOW());

-- 重置自增ID
ALTER TABLE `tc_user` AUTO_INCREMENT = 6;

-- =====================================================
-- 2. 插入积分记录
-- =====================================================

INSERT INTO `tc_points_record` (`user_id`, `type`, `amount`, `balance`, `reason`, `source_type`, `created_at`) VALUES
(1, 'add', 100, 100, '新用户注册奖励', 'register', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(1, 'add', 200, 300, '积分充值', 'recharge', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(1, 'reduce', 50, 250, '解锁八字详细报告', 'bazi_report', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(1, 'add', 10, 260, '每日签到', 'checkin', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(1, 'add', 240, 500, '积分充值', 'recharge', DATE_SUB(NOW(), INTERVAL 10 DAY)),

(2, 'add', 100, 100, '新用户注册奖励', 'register', DATE_SUB(NOW(), INTERVAL 60 DAY)),
(2, 'add', 500, 600, '开通月度VIP赠送', 'vip_gift', DATE_SUB(NOW(), INTERVAL 55 DAY)),
(2, 'add', 400, 1000, '积分充值', 'recharge', DATE_SUB(NOW(), INTERVAL 30 DAY)),

(4, 'add', 100, 100, '新用户注册奖励', 'register', DATE_SUB(NOW(), INTERVAL 90 DAY)),
(4, 'add', 1200, 1300, '开通年度VIP赠送', 'vip_gift', DATE_SUB(NOW(), INTERVAL 85 DAY)),
(4, 'add', 1700, 3000, '积分充值', 'recharge', DATE_SUB(NOW(), INTERVAL 60 DAY));

-- =====================================================
-- 3. 插入八字排盘记录
-- =====================================================

INSERT INTO `tc_bazi_record` (`user_id`, `name`, `gender`, `birth_date`, `birth_time`, `birth_place`, `year_pillar`, `month_pillar`, `day_pillar`, `hour_pillar`, `is_paid`, `points_used`, `created_at`) VALUES
(1, '测试用户', 1, '1990-05-20', '08:30:00', '北京市', '庚午', '辛巳', '乙卯', '庚辰', 1, 50, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(2, '张三', 1, '1985-08-15', '14:20:00', '上海市', '乙丑', '甲申', '丁亥', '丁未', 1, 50, DATE_SUB(NOW(), INTERVAL 40 DAY)),
(3, '李四', 2, '1992-03-10', '09:00:00', '广州市', '壬申', '癸卯', '乙酉', '辛巳', 0, 0, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(4, '王五', 1, '1988-11-25', '16:45:00', '深圳市', '戊辰', '甲子', '甲寅', '壬申', 1, 0, DATE_SUB(NOW(), INTERVAL 50 DAY));

-- =====================================================
-- 4. 插入每日运势记录
-- =====================================================

INSERT INTO `tc_daily_fortune` (`user_id`, `date`, `fortune_type`, `card_id`, `card_name`, `fortune_score`, `fortune_desc`, `created_at`) VALUES
(1, CURDATE(), 'tarot', 1, '愚者', 8, '今天是充满新机遇的一天，适合尝试新事物，保持开放的心态。', NOW()),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'tarot', 6, '恋人', 9, '人际关系和谐，适合处理感情问题或重要决定。', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, CURDATE(), 'tarot', 17, '星星', 8, '希望和灵感的象征，今天适合制定长期计划和目标。', NOW()),
(4, CURDATE(), 'tarot', 19, '太阳', 10, '充满能量和成功的一天，各方面运势都很好。', NOW());

-- =====================================================
-- 5. 插入用户反馈
-- =====================================================

INSERT INTO `tc_feedback` (`user_id`, `type`, `title`, `content`, `contact`, `status`, `created_at`) VALUES
(1, 'feature', '希望增加紫微斗数功能', '建议增加紫微斗数排盘功能，会更全面。', '13800138000', 'pending', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'bug', '八字排盘时间显示错误', '排盘结果中的真太阳时计算似乎有问题。', '13800138001', 'processing', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 'other', '对报告内容有疑问', '希望能有更详细的解释说明。', '', 'resolved', DATE_SUB(NOW(), INTERVAL 10 DAY));

-- =====================================================
-- 6. 插入邀请记录
-- =====================================================

INSERT INTO `tc_invite_record` (`inviter_id`, `invitee_id`, `invite_code`, `points_reward`, `status`, `created_at`) VALUES
(1, 3, 'A8B9C2D1', 20, 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(2, 4, 'X7Y8Z9W0', 20, 1, DATE_SUB(NOW(), INTERVAL 60 DAY)),
(4, 5, 'P3Q4R5S6', 20, 1, DATE_SUB(NOW(), INTERVAL 90 DAY));

-- =====================================================
-- 7. 插入页面内容
-- =====================================================

INSERT INTO `tc_page` (`slug`, `title`, `content`, `meta_title`, `meta_description`, `status`, `created_at`) VALUES
('about', '关于我们', 
'<h1>关于太初命理</h1>
<p>太初命理是专业的在线命理分析平台，提供八字排盘、塔罗占卜、运势分析等服务。</p>
<p>我们的团队由资深命理师和技术专家组成，致力于将传统命理学与现代科技相结合，为用户提供准确、便捷的命理咨询服务。</p>
<h2>我们的服务</h2>
<ul>
<li>八字排盘与详细分析</li>
<li>流年运势预测</li>
<li>八字合婚配对</li>
<li>取名建议</li>
<li>塔罗占卜</li>
</ul>
<h2>联系我们</h2>
<p>邮箱：support@taichu.com</p>', 
'关于我们 - 太初命理', '了解太初命理平台，专业的在线命理分析服务', 1, NOW()),

('privacy', '隐私政策',
'<h1>隐私政策</h1>
<p>太初命理非常重视用户的隐私保护。本政策说明我们如何收集、使用和保护您的个人信息。</p>
<h2>信息收集</h2>
<p>我们收集的信息包括：</p>
<ul>
<li>注册信息（手机号、昵称等）</li>
<li>出生信息（用于命理分析）</li>
<li>使用记录</li>
</ul>
<h2>信息使用</h2>
<p>我们使用您的信息用于：</p>
<ul>
<li>提供命理分析服务</li>
<li>改进用户体验</li>
<li>发送服务通知</li>
</ul>',
'隐私政策 - 太初命理', '太初命理隐私政策说明', 1, NOW()),

('terms', '用户协议',
'<h1>用户协议</h1>
<p>欢迎使用太初命理服务。使用本服务即表示您同意以下条款：</p>
<h2>服务说明</h2>
<p>太初命理提供的命理分析结果仅供参考，不作为人生决策的唯一依据。</p>
<h2>用户责任</h2>
<p>用户应提供真实准确的个人信息，不得利用本服务从事违法活动。</p>',
'用户协议 - 太初命理', '太初命理用户服务协议', 1, NOW());

-- =====================================================
-- 8. 插入充值订单（测试数据）
-- =====================================================

INSERT INTO `tc_recharge_order` (`order_no`, `user_id`, `amount`, `points`, `pay_type`, `pay_status`, `pay_time`, `created_at`) VALUES
('R202403150001', 1, 30.00, 330, 'wechat', 1, DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY)),
('R202403200002', 1, 50.00, 600, 'wechat', 1, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
('R402150003', 2, 19.90, 100, 'wechat', 1, DATE_SUB(NOW(), INTERVAL 55 DAY), DATE_SUB(NOW(), INTERVAL 55 DAY)),
('R202403100004', 4, 168.00, 1200, 'wechat', 1, DATE_SUB(NOW(), INTERVAL 85 DAY), DATE_SUB(NOW(), INTERVAL 85 DAY));

-- =====================================================
-- 9. 插入VIP订单（测试数据）
-- =====================================================

INSERT INTO `tc_vip_order` (`order_no`, `user_id`, `vip_type`, `duration`, `amount`, `pay_type`, `pay_time`, `status`, `start_date`, `end_date`, `created_at`) VALUES
('V202402150001', 2, 1, 1, 19.90, 'wechat', DATE_SUB(NOW(), INTERVAL 55 DAY), 1, DATE_SUB(CURDATE(), INTERVAL 55 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 55 DAY), INTERVAL 1 MONTH), DATE_SUB(NOW(), INTERVAL 55 DAY)),
('V202312100002', 4, 3, 12, 168.00, 'wechat', DATE_SUB(NOW(), INTERVAL 85 DAY), 1, DATE_SUB(CURDATE(), INTERVAL 85 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 85 DAY), INTERVAL 1 YEAR), DATE_SUB(NOW(), INTERVAL 85 DAY));

-- =====================================================
-- 10. 更新用户VIP信息
-- =====================================================

UPDATE `tc_user` SET 
    `vip_level` = 1, 
    `vip_expire_at` = DATE_ADD(NOW(), INTERVAL 5 DAY) 
WHERE `id` = 2;

UPDATE `tc_user` SET 
    `vip_level` = 3, 
    `vip_expire_at` = DATE_ADD(NOW(), INTERVAL 280 DAY) 
WHERE `id` = 4;

-- =====================================================
-- 11. 插入管理员角色分配（假设用户ID 4是管理员）
-- =====================================================

INSERT INTO `tc_admin_user_role` (`admin_id`, `role_id`, `created_at`) VALUES
(4, 1, NOW());
