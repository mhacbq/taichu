-- 修复合婚积分配置缺失/写入错误表导致的 pricing/calculate 500
-- 适用范围：system_config 表已存在，但缺少合婚相关默认配置的环境

INSERT INTO `system_config` (`config_key`, `config_value`, `config_type`, `description`, `category`, `is_editable`, `sort_order`, `created_at`, `updated_at`) VALUES
('feature_hehun_enabled', '1', 'bool', '八字合婚功能开关', 'feature', 1, 7, NOW(), NOW()),
('points_cost_hehun', '80', 'int', '八字合婚基础积分消耗', 'points_cost', 1, 7, NOW(), NOW()),
('points_cost_hehun_export', '30', 'int', '八字合婚导出报告积分', 'points_cost', 1, 8, NOW(), NOW()),
('vip_unlock_hehun', '1', 'bool', 'VIP是否解锁合婚功能', 'vip', 1, 7, NOW(), NOW()),
('new_user_offer_enabled', '1', 'bool', '新用户优惠开关', 'new_user', 1, 1, NOW(), NOW()),
('new_user_discount', '50', 'int', '新用户折扣(%)', 'new_user', 1, 2, NOW(), NOW()),
('new_user_valid_hours', '24', 'int', '新用户优惠有效期(小时)', 'new_user', 1, 3, NOW(), NOW()),
('limited_offer_enabled', '0', 'bool', '限时优惠开关', 'limited_offer', 1, 1, NOW(), NOW()),
('limited_offer_discount', '30', 'int', '限时优惠折扣(%)', 'limited_offer', 1, 2, NOW(), NOW()),
('limited_offer_start_time', '', 'string', '限时优惠开始时间', 'limited_offer', 1, 3, NOW(), NOW()),
('limited_offer_end_time', '', 'string', '限时优惠结束时间', 'limited_offer', 1, 4, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `config_value` = VALUES(`config_value`),
  `config_type` = VALUES(`config_type`),
  `description` = VALUES(`description`),
  `category` = VALUES(`category`),
  `is_editable` = VALUES(`is_editable`),
  `sort_order` = VALUES(`sort_order`),
  `updated_at` = VALUES(`updated_at`);
