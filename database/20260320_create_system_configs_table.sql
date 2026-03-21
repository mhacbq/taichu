-- 统一系统配置表
-- 用于管理支付、AI服务等各类系统配置
-- Created: 2026-03-20

CREATE TABLE IF NOT EXISTS `tc_system_configs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `config_group` varchar(50) NOT NULL COMMENT '配置分组: payment/ai/push/sms等',
  `config_key` varchar(100) NOT NULL COMMENT '配置键名',
  `config_value` text COMMENT '配置值(加密存储)',
  `config_type` varchar(20) NOT NULL DEFAULT 'string' COMMENT '数据类型: string/int/boolean/json/password',
  `is_encrypted` tinyint NOT NULL DEFAULT 0 COMMENT '是否加密存储',
  `is_sensitive` tinyint NOT NULL DEFAULT 0 COMMENT '是否敏感信息(前端不显示)',
  `description` varchar(255) DEFAULT NULL COMMENT '配置说明',
  `sort_order` int NOT NULL DEFAULT 0 COMMENT '排序权重',
  `is_enabled` tinyint NOT NULL DEFAULT 1 COMMENT '是否启用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_key` (`config_group`,`config_key`),
  KEY `idx_group` (`config_group`),
  KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统统一配置表';

-- 插入支付配置
INSERT INTO `tc_system_configs` (`config_group`, `config_key`, `config_value`, `config_type`, `is_encrypted`, `is_sensitive`, `description`, `sort_order`) VALUES
-- 微信支付配置
('payment', 'wechat_mch_id', '', 'string', 1, 1, '微信支付商户号', 1),
('payment', 'wechat_app_id', '', 'string', 0, 0, '微信应用ID', 2),
('payment', 'wechat_api_key', '', 'password', 1, 1, '微信支付API密钥', 3),
('payment', 'wechat_api_cert', '', 'text', 1, 1, '微信支付证书(apiclient_cert.pem内容)', 4),
('payment', 'wechat_api_key_pem', '', 'text', 1, 1, '微信支付私钥(apiclient_key.pem内容)', 5),
('payment', 'wechat_notify_url', 'https://taichu.chat/api/payment/notify', 'string', 0, 0, '微信支付回调通知URL', 6),
('payment', 'wechat_is_enabled', '1', 'boolean', 0, 0, '是否启用微信支付', 7),

-- 支付宝支付配置
('payment', 'alipay_app_id', '', 'string', 1, 1, '支付宝应用ID', 11),
('payment', 'alipay_private_key', '', 'password', 1, 1, '支付宝应用私钥', 12),
('payment', 'alipay_public_key', '', 'text', 0, 1, '支付宝公钥', 13),
('payment', 'alipay_notify_url', 'https://taichu.chat/api/alipay/notify', 'string', 0, 0, '支付宝异步通知URL', 14),
('payment', 'alipay_return_url', 'https://taichu.chat/recharge', 'string', 0, 0, '支付宝同步跳转URL', 15),
('payment', 'alipay_is_enabled', '0', 'boolean', 0, 0, '是否启用支付宝支付', 16);

-- 插入AI服务配置
INSERT INTO `tc_system_configs` (`config_group`, `config_key`, `config_value`, `config_type`, `is_encrypted`, `is_sensitive`, `description`, `sort_order`) VALUES
('ai', 'ai_api_key', '', 'password', 1, 1, 'AI服务API密钥', 1),
('ai', 'ai_api_url', 'https://aiping.cn/api/v1/chat/completions', 'string', 0, 0, 'AI服务API地址', 2),
('ai', 'ai_model', 'DeepSeek-V3.2', 'string', 0, 0, 'AI模型名称', 3),
('ai', 'ai_max_tokens', '4096', 'int', 0, 0, '最大输出Token数', 4),
('ai', 'ai_timeout', '60', 'int', 0, 0, '请求超时时间(秒)', 5),
('ai', 'ai_enable_streaming', '1', 'boolean', 0, 0, '是否启用流式输出', 6),
('ai', 'ai_enable_thinking', '0', 'boolean', 0, 0, '是否启用思维链', 7),
('ai', 'ai_cost_points', '30', 'int', 0, 0, 'AI解盘消耗积分数', 8),
('ai', 'ai_enable_bazi', '1', 'boolean', 0, 0, '是否启用八字分析', 9),
('ai', 'ai_enable_tarot', '1', 'boolean', 0, 0, '是否启用塔罗分析', 10),
('ai', 'ai_is_enabled', '1', 'boolean', 0, 0, '是否启用AI服务', 11);

-- 插入推送服务配置(可选)
INSERT INTO `tc_system_configs` (`config_group`, `config_key`, `config_value`, `config_type`, `is_encrypted`, `is_sensitive`, `description`, `sort_order`) VALUES
('push', 'push_provider', '', 'string', 0, 0, '推送服务提供商: jpush/fcm/webhook', 1),
('push', 'jpush_app_key', '', 'string', 1, 1, '极光推送AppKey', 2),
('push', 'jpush_master_secret', '', 'password', 1, 1, '极光推送MasterSecret', 3),
('push', 'fcm_server_key', '', 'password', 1, 1, 'FCM服务端密钥', 4),
('push', 'webhook_url', '', 'string', 0, 0, '自定义Webhook地址', 5),
('push', 'webhook_bearer', '', 'password', 1, 1, 'Webhook Bearer Token', 6),
('push', 'push_is_enabled', '0', 'boolean', 0, 0, '是否启用消息推送', 7);

-- 插入短信服务配置(可选)
INSERT INTO `tc_system_configs` (`config_group`, `config_key`, `config_value`, `config_type`, `is_encrypted`, `is_sensitive`, `description`, `sort_order`) VALUES
('sms', 'sms_test_mode', '1', 'boolean', 0, 0, '测试模式(使用固定验证码)', 1),
('sms', 'sms_test_code', '123456', 'string', 0, 0, '测试验证码', 2),
('sms', 'tencent_secret_id', '', 'password', 1, 1, '腾讯云SecretId', 3),
('sms', 'tencent_secret_key', '', 'password', 1, 1, '腾讯云SecretKey', 4),
('sms', 'tencent_sdk_app_id', '', 'string', 1, 1, '腾讯云SDKAppId', 5),
('sms', 'tencent_sign_name', '', 'string', 0, 0, '短信签名', 6),
('sms', 'tencent_template_code', '', 'string', 0, 0, '短信模板ID', 7),
('sms', 'sms_is_enabled', '1', 'boolean', 0, 0, '是否启用短信服务', 8);
