-- 添加合婚相关配置
INSERT INTO `system_config` (`config_key`, `config_value`, `config_type`, `description`, `category`, `sort_order`) VALUES
-- 合婚功能开关
('feature_hehun_enabled', '1', 'bool', '八字合婚功能开关', 'feature', 7),

-- 合婚积分消耗
('points_cost_hehun', '80', 'int', '八字合婚基础积分消耗', 'points_cost', 7),
('points_cost_hehun_export', '30', 'int', '八字合婚导出报告积分', 'points_cost', 8),

-- VIP合婚特权
('vip_unlock_hehun', '1', 'bool', 'VIP是否解锁合婚功能', 'vip', 7),

-- 新用户优惠
('new_user_offer_enabled', '1', 'bool', '新用户优惠开关', 'new_user', 1),
('new_user_discount', '50', 'int', '新用户折扣(%)', 'new_user', 2),
('new_user_valid_hours', '24', 'int', '新用户优惠有效期(小时)', 'new_user', 3),

-- 限时优惠（可配置活动）
('limited_offer_enabled', '0', 'bool', '限时优惠开关', 'limited_offer', 1),
('limited_offer_discount', '30', 'int', '限时优惠折扣(%)', 'limited_offer', 2),
('limited_offer_start_time', '', 'string', '限时优惠开始时间', 'limited_offer', 3),
('limited_offer_end_time', '', 'string', '限时优惠结束时间', 'limited_offer', 4),

ON DUPLICATE KEY UPDATE 
`config_value` = VALUES(`config_value`),
`description` = VALUES(`description`);
