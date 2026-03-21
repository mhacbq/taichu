-- =====================================================
-- 太初命理系统 - 基础数据插入脚本
-- 包含系统运行必需的基础配置数据
-- =====================================================

USE taichu;

-- =====================================================
-- 1. 插入系统配置数据
-- =====================================================

INSERT INTO `tc_system_config` (`key`, `value`, `type`, `group`, `description`, `is_public`, `sort`) VALUES
-- 站点配置
('site.name', '太初命理', 'string', 'site', '站点名称', 1, 1),
('site.description', '专业的八字排盘、塔罗占卜、运势分析平台', 'string', 'site', '站点描述', 1, 2),
('site.logo', '', 'string', 'site', '站点Logo', 1, 3),
('site.icp', '', 'string', 'site', 'ICP备案号', 1, 4),
('site.contact_email', 'support@taichu.com', 'string', 'site', '联系邮箱', 0, 5),
('site.contact_phone', '', 'string', 'site', '联系电话', 0, 6),

-- 积分配置
('points.register', '100', 'int', 'points', '新用户注册赠送积分', 1, 1),
('points.checkin', '10', 'int', 'points', '每日签到赠送积分', 1, 2),
('points.checkin_continuous', '5', 'int', 'points', '连续签到额外奖励', 1, 3),
('points.invite', '20', 'int', 'points', '邀请好友奖励积分', 1, 4),
('points.share', '5', 'int', 'points', '分享奖励积分', 1, 5),
('points.profile_complete', '20', 'int', 'points', '完善资料奖励积分', 1, 6),

-- 功能消耗积分配置
('points.bazi.basic', '0', 'int', 'points_cost', '八字基础排盘消耗', 1, 1),
('points.bazi.report', '50', 'int', 'points_cost', '八字详细报告消耗', 1, 2),
('points.tarot', '10', 'int', 'points_cost', '塔罗占卜消耗', 1, 3),
('points.daily_fortune', '0', 'int', 'points_cost', '每日运势消耗', 1, 4),
('points.yearly_fortune', '50', 'int', 'points_cost', '流年运势消耗', 1, 5),
('points.hehun', '80', 'int', 'points_cost', '八字合婚消耗', 1, 6),
('points.qiming', '100', 'int', 'points_cost', '取名建议消耗', 1, 7),
('points.jiri', '20', 'int', 'points_cost', '吉日查询消耗', 1, 8),

-- VIP配置
('vip.month.price', '19.90', 'string', 'vip', '月度VIP价格', 1, 1),
('vip.month.points', '100', 'int', 'vip', '月度VIP赠送积分', 1, 2),
('vip.quarter.price', '49.00', 'string', 'vip', '季度VIP价格', 1, 3),
('vip.quarter.points', '300', 'int', 'vip', '季度VIP赠送积分', 1, 4),
('vip.year.price', '168.00', 'string', 'vip', '年度VIP价格', 1, 5),
('vip.year.points', '1200', 'int', 'vip', '年度VIP赠送积分', 1, 6),
('vip.discount', '10', 'int', 'vip', 'VIP积分折扣(%)', 1, 7),

-- 充值配置
('recharge.option.1', '{"amount":10,"points":100,"gift":0}', 'json', 'recharge', '充值选项1', 1, 1),
('recharge.option.2', '{"amount":30,"points":330,"gift":30}', 'json', 'recharge', '充值选项2', 1, 2),
('recharge.option.3', '{"amount":50,"points":600,"gift":100}', 'json', 'recharge', '充值选项3', 1, 3),
('recharge.option.4', '{"amount":100,"points":1300,"gift":300}', 'json', 'recharge', '充值选项4', 1, 4),
('recharge.option.5', '{"amount":200,"points":2800,"gift":800}', 'json', 'recharge', '充值选项5', 1, 5),

-- 营销配置
('marketing.new_user_discount', 'true', 'bool', 'marketing', '新用户优惠', 1, 1),
('marketing.new_user_discount_rate', '50', 'int', 'marketing', '新用户折扣率(%)', 1, 2),
('marketing.limited_time_offer', 'false', 'bool', 'marketing', '限时优惠', 1, 3),
('marketing.limited_time_discount', '20', 'int', 'marketing', '限时优惠折扣(%)', 1, 4)
ON DUPLICATE KEY UPDATE
`value` = VALUES(`value`),
`type` = VALUES(`type`),
`group` = VALUES(`group`),
`description` = VALUES(`description`),
`is_public` = VALUES(`is_public`),
`sort` = VALUES(`sort`);

-- =====================================================
-- 2. 插入功能开关数据
-- =====================================================

INSERT INTO `tc_feature_switch` (`key`, `name`, `enabled`, `description`) VALUES
('vip', 'VIP会员', 1, 'VIP会员功能'),
('points', '积分系统', 1, '积分系统功能'),
('ai_analysis', 'AI解盘', 1, 'AI智能分析功能'),
('recharge', '充值功能', 1, '积分充值功能'),
('share', '分享功能', 1, '分享和邀请功能'),
('hehun', '八字合婚', 1, '八字合婚功能'),
('qiming', '取名建议', 1, '取名建议功能'),
('jiri', '吉日查询', 1, '吉日查询功能'),
('daily_fortune', '每日运势', 1, '每日运势功能'),
('yearly_fortune', '流年运势', 1, '流年运势功能'),
('tarot', '塔罗占卜', 1, '塔罗占卜功能'),
('feedback', '用户反馈', 1, '用户反馈功能'),
('checkin', '每日签到', 1, '每日签到功能'),
('invite', '邀请好友', 1, '邀请好友功能');

-- =====================================================
-- 3. 插入管理员权限数据
-- =====================================================

INSERT INTO `tc_admin_permission` (`name`, `code`, `module`, `description`) VALUES
('用户查看', 'user_view', 'user', '查看用户列表和信息'),
('用户编辑', 'user_edit', 'user', '编辑用户信息'),
('用户删除', 'user_delete', 'user', '删除用户'),
('积分查看', 'points_view', 'points', '查看积分记录'),
('积分调整', 'points_adjust', 'points', '调整用户积分'),
('配置管理', 'config_manage', 'config', '管理系统配置'),
('配置查看', 'config_view', 'config', '查看系统配置'),
('日志查看', 'log_view', 'log', '查看操作日志'),
('数据统计', 'stats_view', 'stats', '查看统计数据'),
('内容管理', 'content_manage', 'content', '管理内容数据'),
('内容审核', 'content_audit', 'content', '审核内容'),
('订单查看', 'order_view', 'order', '查看订单'),
('订单处理', 'order_process', 'order', '处理订单'),
('VIP管理', 'vip_manage', 'vip', '管理VIP会员'),
('营销管理', 'marketing_manage', 'marketing', '管理营销活动');

-- =====================================================
-- 4. 插入管理员角色数据
-- =====================================================

INSERT INTO `tc_admin_role` (`name`, `code`, `description`, `is_super`) VALUES
('超级管理员', 'super_admin', '拥有所有权限', 1),
('普通管理员', 'normal_admin', '拥有常规管理权限', 0),
('运营人员', 'operator', '仅限查看和部分编辑权限', 0),
('客服人员', 'customer_service', '仅限处理用户反馈和订单', 0);

-- =====================================================
-- 5. 插入角色权限关联数据
-- =====================================================

-- 普通管理员权限
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT 2, id FROM `tc_admin_permission` WHERE code IN (
    'user_view', 'user_edit', 'points_view', 'config_view', 
    'stats_view', 'content_manage', 'order_view', 'order_process'
);

-- 运营人员权限
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT 3, id FROM `tc_admin_permission` WHERE code IN (
    'user_view', 'points_view', 'stats_view', 'marketing_manage'
);

-- 客服人员权限
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT 4, id FROM `tc_admin_permission` WHERE code IN (
    'user_view', 'order_view', 'order_process'
);

-- =====================================================
-- 6. 插入塔罗牌数据（基础22张大阿卡纳）
-- =====================================================

INSERT INTO `tc_tarot_card` (`name`, `name_en`, `type`, `number`, `element`, `keywords`, `upright_meaning`, `reversed_meaning`) VALUES
('愚者', 'The Fool', 'major', 0, 'air', '开始、自由、冒险', 
 '新的开始，冒险精神，不受束缚，信任直觉，充满潜力', 
 '鲁莽冲动，缺乏计划，盲目乐观，轻率决定，冒险过度'),

('魔术师', 'The Magician', 'major', 1, 'air', '创造、力量、行动',
 '拥有资源和能力，将想法变为现实，主动创造，充满信心',
 '欺骗、操纵，滥用权力，缺乏信心，资源浪费，能力不足'),

('女祭司', 'The High Priestess', 'major', 2, 'water', '直觉、神秘、智慧',
 '直觉敏锐，内在智慧，神秘力量，潜意识觉醒，静待时机',
 '忽视直觉，表面判断，秘密被揭露，缺乏耐心，直觉受阻'),

('皇后', 'The Empress', 'major', 3, 'earth', '丰饶、创造、母性',
 '创造力旺盛，享受生活，自然之美，丰盛收获，母性光辉',
 '创造力受阻，依赖他人，过度放纵，不孕，缺乏灵感'),

('皇帝', 'The Emperor', 'major', 4, 'fire', '权威、结构、控制',
 '建立秩序，领导能力，稳定结构，理性决策，掌控局面',
 '专制统治，僵化死板，权力滥用，失去控制，缺乏纪律'),

('教皇', 'The Hierophant', 'major', 5, 'earth', '传统、信仰、教导',
 '遵循传统，精神指引，寻求指导，学习知识，遵循规则',
 '打破传统，叛逆精神，非传统方法，缺乏指导，信仰危机'),

('恋人', 'The Lovers', 'major', 6, 'air', '爱情、选择、和谐',
 '爱情关系，重要选择，价值观一致，和谐关系，情感连接',
 '关系失衡，错误选择，价值观冲突，不和谐，感情受挫'),

('战车', 'The Chariot', 'major', 7, 'water', '意志、胜利、控制',
 '坚定意志，克服挑战，取得胜利，自我控制，前进动力',
 '失控，方向不明，缺乏纪律，失败，意志力薄弱'),

('力量', 'Strength', 'major', 8, 'fire', '勇气、耐心、内在力量',
 '内在力量，温柔坚持，克服困难，耐心面对，以柔克刚',
 '软弱无力，缺乏耐心，暴力相向，信心不足，放弃'),

('隐士', 'The Hermit', 'major', 9, 'earth', '内省、独处、寻找',
 '独自探索，内在寻找，智慧之光，退隐思考，寻求真理',
 '孤独隔离，拒绝帮助，迷失方向，社交退缩，固步自封'),

('命运之轮', 'Wheel of Fortune', 'major', 10, 'fire', '变化、命运、周期',
 '命运转折，机会来临，周期变化，好运降临，顺应变化',
 '厄运降临，抗拒变化，坏运气，错失良机，停滞不前'),

('正义', 'Justice', 'major', 11, 'air', '公正、平衡、真理',
 '公正裁决，因果报应，寻求真理，平衡各方，理性判断',
 '不公正，偏见，逃避责任，失衡，错误判断'),

('倒吊人', 'The Hanged Man', 'major', 12, 'water', '牺牲、等待、新视角',
 '换个角度，暂停等待，牺牲奉献，新视野，顺其自然',
 '抗拒改变，无意义的牺牲，拖延，固执，错失良机'),

('死神', 'Death', 'major', 13, 'water', '结束、转变、新生',
 '结束阶段，重大转变，旧事物消亡，新的开始，必要改变',
 '抗拒结束，停滞不变，害怕改变，僵化，错失重生机会'),

('节制', 'Temperance', 'major', 14, 'fire', '平衡、节制、融合',
 '平衡和谐，适度节制，融合统一，耐心调和，中庸之道',
 '极端行为，失衡，过度放纵，缺乏节制，冲突加剧'),

('恶魔', 'The Devil', 'major', 15, 'earth', '束缚、诱惑、物质',
 '物质束缚，不良习惯，诱惑陷阱，依赖成瘾，受困于欲望',
 '摆脱束缚，重获自由，打破枷锁，拒绝诱惑，觉醒'),

('塔', 'The Tower', 'major', 16, 'fire', '突变、灾难、觉醒',
 '突然改变，打破旧有，真相揭露，危机转机，强制觉醒',
 '避免改变，灾难延迟，内心恐惧，抗拒真相，固执己见'),

('星星', 'The Star', 'major', 17, 'air', '希望、灵感、宁静',
 '希望之光，灵感涌现，内心宁静，信任未来，精神指引',
 '希望渺茫，失去信心，绝望，缺乏灵感，迷失方向'),

('月亮', 'The Moon', 'major', 18, 'water', '幻觉、恐惧、潜意识',
 '潜意识活跃，面对恐惧，不确定中前行，直觉增强，神秘力量',
 '恐惧消散，真相大白，走出迷雾，幻觉破灭，焦虑减轻'),

('太阳', 'The Sun', 'major', 19, 'fire', '成功、快乐、活力',
 '成功喜悦，充满活力，光明正大，自信满满，幸福时光',
 '暂时的阴霾，自信受挫，快乐短暂，过度自信，盲目乐观'),

('审判', 'Judgement', 'major', 20, 'fire', '重生、评价、召唤',
 '内心召唤，自我评价，重生觉醒，过去的总结，新的开始',
 '自我怀疑，拒绝召唤，逃避评价，错失机会，固步自封'),

('世界', 'The World', 'major', 21, 'earth', '完成、圆满、成就',
 '目标达成，圆满完整，旅程结束，成就荣耀，新的开始',
 '未完成的遗憾，缺乏 Closure，目标未达，延迟完成，完美主义');

-- =====================================================
-- 7. 插入FAQ数据
-- =====================================================

INSERT INTO `tc_faq` (`category`, `question`, `answer`, `sort`, `status`) VALUES
('general', '什么是八字？', '八字是中国传统命理学的重要组成部分，根据一个人出生的年、月、日、时四柱，每柱两个字，共八个字来推算命运。', 1, 1),
('general', '八字排盘准确吗？', '八字排盘是基于传统命理学的计算方法，具有一定的参考价值。但命运也受后天努力、环境等因素影响，仅供参考。', 2, 1),
('general', '需要提供哪些信息？', '进行八字排盘需要提供准确的出生年、月、日、时，以及出生地点（用于计算真太阳时）。', 3, 1),

('points', '如何获得积分？', '您可以通过以下方式获得积分：新用户注册赠送100积分、每日签到、邀请好友、完善个人资料、分享运势等。', 1, 1),
('points', '积分有什么用？', '积分可用于解锁详细的命理报告、流年运势分析、八字合婚、取名建议等高级功能。', 2, 1),
('points', '积分会过期吗？', '目前积分长期有效，不会过期。但请注意查看平台公告，如有调整会提前通知。', 3, 1),

('vip', 'VIP有什么特权？', 'VIP会员可享受：无限次排盘、所有报告免费解锁、积分充值折扣、专属客服、新功能优先体验等特权。', 1, 1),
('vip', 'VIP如何购买？', '您可以在个人中心的VIP页面选择合适的套餐进行购买，支持微信支付等多种支付方式。', 2, 1),
('vip', 'VIP到期后会怎样？', 'VIP到期后将恢复普通用户权限，已解锁的内容仍然可以查看，但新排盘将受相应限制。', 3, 1),

('function', '什么是流年运势？', '流年运势是根据您的八字，结合当年的天干地支，分析您在这一年各方面的运势走向。', 1, 1),
('function', '八字合婚准吗？', '八字合婚是基于双方八字的五行生克关系进行分析，可以作为参考，但婚姻幸福还需要双方共同经营。', 2, 1),
('function', '取名服务如何使用？', '提供姓氏、性别、出生时间等信息，系统会根据五行八字分析，为您推荐合适的名字建议。', 3, 1);

-- =====================================================
-- 8. 插入AI提示词数据
-- =====================================================

INSERT INTO `tc_ai_prompt` (`name`, `type`, `prompt`, `variables`, `status`) VALUES
('八字基础分析', 'bazi', 
'你是一位专业的命理大师。请根据以下八字信息进行分析：
年柱：{{year_pillar}}
月柱：{{month_pillar}}
日柱：{{day_pillar}}
时柱：{{hour_pillar}}
性别：{{gender}}

请从以下几个方面进行分析：
1. 五行分析（五行分布和强弱）
2. 十神分析（十神配置和特点）
3. 日主分析（日主强弱和特点）
4. 性格特征
5. 事业财运
6. 感情婚姻
7. 健康提示

请用通俗易懂的语言，给出详细且专业的分析。',
'["year_pillar","month_pillar","day_pillar","hour_pillar","gender"]',
1),

('流年运势分析', 'fortune',
'你是一位专业的命理大师。请根据以下信息分析流年运势：
八字：{{bazi}}
流年：{{year}} {{year_ganzi}}
大运：{{dayun}}

请分析以下内容：
1. 整体运势评分（1-10分）
2. 事业运势
3. 财运分析
4. 感情运势
5. 健康状况
6. 重要提示和建议
7. 吉凶月份预测

请给出专业且实用的建议。',
'["bazi","year","year_ganzi","dayun"]',
1),

('塔罗牌解读', 'tarot',
'你是一位专业的塔罗牌师。请解读以下塔罗牌：
牌名：{{card_name}}
位置：{{position}}
问题：{{question}}

请从以下角度解读：
1. 牌面基本含义
2. 在本情境中的具体含义
3. 给出的建议和指引
4. 需要注意的方面

请用温暖且专业的语言进行解读。',
'["card_name","position","question"]',
1);
