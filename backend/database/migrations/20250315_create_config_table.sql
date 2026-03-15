-- 系统配置表
CREATE TABLE IF NOT EXISTS `system_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `config_type` varchar(20) DEFAULT 'string' COMMENT '值类型：string,json,int,bool,float',
  `description` varchar(255) DEFAULT NULL COMMENT '配置说明',
  `category` varchar(50) DEFAULT 'general' COMMENT '配置分类',
  `is_editable` tinyint(1) DEFAULT 1 COMMENT '是否可在后台编辑',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_config_key` (`config_key`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- 插入默认配置数据
INSERT INTO `system_config` (`config_key`, `config_value`, `config_type`, `description`, `category`, `is_editable`, `sort_order`) VALUES
-- 功能开关
('feature_vip_enabled', '1', 'bool', 'VIP会员功能开关', 'feature', 1, 1),
('feature_points_enabled', '1', 'bool', '积分系统开关', 'feature', 1, 2),
('feature_ai_analysis_enabled', '1', 'bool', 'AI解盘功能开关', 'feature', 1, 3),
('feature_yearly_fortune_enabled', '1', 'bool', '流年运势功能开关', 'feature', 1, 4),
('feature_dayun_analysis_enabled', '1', 'bool', '大运分析功能开关', 'feature', 1, 5),
('feature_dayun_chart_enabled', '1', 'bool', '运势K线图功能开关', 'feature', 1, 6),
('feature_hehun_enabled', '1', 'bool', '八字合婚功能开关', 'feature', 1, 7),
('feature_qiming_enabled', '1', 'bool', '取名建议功能开关', 'feature', 1, 8),
('feature_share_poster_enabled', '1', 'bool', '分享海报功能开关', 'feature', 1, 9),
('feature_invite_enabled', '1', 'bool', '邀请好友功能开关', 'feature', 1, 10),
('feature_limited_offer_enabled', '1', 'bool', '限时优惠功能开关', 'feature', 1, 11),
('feature_package_enabled', '1', 'bool', '组合套餐功能开关', 'feature', 1, 12),
('feature_report_tier_enabled', '1', 'bool', '报告分层功能开关', 'feature', 1, 13),
('feature_tasks_enabled', '1', 'bool', '积分任务功能开关', 'feature', 1, 14),

-- VIP配置
('vip_month_price', '19.9', 'float', '月度VIP价格（元）', 'vip', 1, 1),
('vip_quarter_price', '49', 'float', '季度VIP价格（元）', 'vip', 1, 2),
('vip_year_price', '168', 'float', '年度VIP价格（元）', 'vip', 1, 3),
('vip_daily_points_multiplier', '2', 'int', 'VIP每日积分倍数', 'vip', 1, 4),
('vip_paipan_limit', '-1', 'int', 'VIP每日排盘次数（-1为无限）', 'vip', 1, 5),
('vip_unlock_basic_report', '1', 'bool', 'VIP是否解锁基础报告', 'vip', 1, 6),
('vip_unlock_hehun', '1', 'bool', 'VIP是否解锁合婚功能', 'vip', 1, 7),
('vip_unlock_qiming', '0', 'bool', 'VIP是否解锁取名功能', 'vip', 1, 8),

-- 积分配置
('points_sign_daily', '10', 'int', '每日签到积分', 'points', 1, 1),
('points_sign_continuous_7', '20', 'int', '连续7天签到额外积分', 'points', 1, 2),
('points_sign_continuous_30', '50', 'int', '连续30天签到额外积分', 'points', 1, 3),
('points_share_app', '20', 'int', '分享小程序积分', 'points', 1, 4),
('points_invite_friend', '50', 'int', '邀请好友积分', 'points', 1, 5),
('points_complete_profile', '30', 'int', '完善资料积分', 'points', 1, 6),
('points_first_paipan', '20', 'int', '首次排盘积分', 'points', 1, 7),
('points_bind_wechat', '30', 'int', '绑定微信积分', 'points', 1, 8),
('points_follow_mp', '20', 'int', '关注公众号积分', 'points', 1, 9),
('points_browse_article', '5', 'int', '浏览文章积分', 'points', 1, 10),

-- 积分消耗配置
('points_cost_save_record', '10', 'int', '保存排盘记录积分', 'points_cost', 1, 1),
('points_cost_share_poster', '20', 'int', '分享海报积分', 'points_cost', 1, 2),
('points_cost_unlock_report', '50', 'int', '解锁详细报告积分', 'points_cost', 1, 3),
('points_cost_yearly_fortune', '30', 'int', '流年运势分析积分', 'points_cost', 1, 4),
('points_cost_dayun_analysis', '50', 'int', '大运运势评分积分', 'points_cost', 1, 5),
('points_cost_dayun_chart', '30', 'int', '运势K线图积分', 'points_cost', 1, 6),
('points_cost_hehun', '80', 'int', '八字合婚积分', 'points_cost', 1, 7),
('points_cost_qiming', '100', 'int', '取名建议积分', 'points_cost', 1, 8),
('points_cost_jiri', '20', 'int', '吉日查询积分', 'points_cost', 1, 9),

-- 限时优惠配置
('limited_offer_enabled', '0', 'bool', '是否开启限时优惠', 'limited_offer', 1, 1),
('limited_offer_discount', '50', 'int', '优惠折扣（%）', 'limited_offer', 1, 2),
('limited_offer_start_time', '', 'string', '优惠开始时间', 'limited_offer', 1, 3),
('limited_offer_end_time', '', 'string', '优惠结束时间', 'limited_offer', 1, 4),
('limited_offer_applicable_features', '["yearly_fortune","dayun_analysis"]', 'json', '适用的功能列表', 'limited_offer', 1, 5),

-- 组合套餐配置
('package_enabled', '1', 'bool', '是否开启组合套餐', 'package', 1, 1),
('packages', '{"fortune_combo":{"name":"运势分析套餐","original_points":110,"sale_points":80,"includes":["yearly_fortune","dayun_analysis","dayun_chart"],"description":"流年+大运+K线图打包"},"all_in_one":{"name":"全部解锁","original_points":200,"sale_points":150,"includes":["unlock_report","yearly_fortune","dayun_analysis"],"description":"专业版报告+全部高级分析"}}', 'json', '套餐配置', 'package', 1, 2),

-- 新用户优惠配置
('new_user_offer_enabled', '1', 'bool', '是否开启新用户优惠', 'new_user', 1, 1),
('new_user_discount', '50', 'int', '新用户折扣（%）', 'new_user', 1, 2),
('new_user_valid_hours', '24', 'int', '新用户优惠有效期（小时）', 'new_user', 1, 3),

-- 充值配置
('recharge_enabled', '1', 'bool', '是否开启充值功能', 'recharge', 1, 1),
('recharge_ratio', '10', 'int', '充值比例（1元=多少积分）', 'recharge', 1, 2),
('recharge_options', '[{"amount":10,"points":100,"bonus":0},{"amount":30,"points":300,"bonus":30},{"amount":50,"points":500,"bonus":80},{"amount":100,"points":1000,"bonus":200}]', 'json', '充值选项配置', 'recharge', 1, 3),

-- 报告分层配置
('report_tier_enabled', '1', 'bool', '是否开启报告分层', 'report_tier', 1, 1),
('basic_report_items', '["bazi","wuxing","shishen_basic","nayin"]', 'json', '基础报告包含项', 'report_tier', 1, 2),
('premium_report_items', '["mingpan_detail","xingge_analysis","shiye_caiyun","hunyin_ganqing","jiankang_tixing","yunshi_zonghe"]', 'json', '高级报告包含项', 'report_tier', 1, 3),
('premium_report_points', '50', 'int', '解锁高级报告所需积分', 'report_tier', 1, 4);
