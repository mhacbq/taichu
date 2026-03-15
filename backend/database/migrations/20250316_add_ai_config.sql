-- 添加AI服务配置
INSERT INTO `system_configs` (`config_key`, `config_value`, `value_type`, `description`, `category`, `sort_order`) VALUES
-- AI功能开关
('feature_ai_analysis_enabled', '1', 'bool', 'AI分析功能开关', 'feature', 5),

-- AI提供商配置
('ai_provider', 'deepseek', 'string', 'AI提供商(deepseek/openai/claude)', 'ai', 1),

-- DeepSeek配置
('ai_deepseek_api_key', '', 'string', 'DeepSeek API密钥', 'ai', 10),
('ai_deepseek_base_url', 'https://api.deepseek.com', 'string', 'DeepSeek API地址', 'ai', 11),
('ai_deepseek_model', 'deepseek-chat', 'string', 'DeepSeek模型', 'ai', 12),

-- OpenAI配置
('ai_openai_api_key', '', 'string', 'OpenAI API密钥', 'ai', 20),
('ai_openai_base_url', 'https://api.openai.com', 'string', 'OpenAI API地址', 'ai', 21),
('ai_openai_model', 'gpt-3.5-turbo', 'string', 'OpenAI模型', 'ai', 22),

-- Claude配置
('ai_claude_api_key', '', 'string', 'Claude API密钥', 'ai', 30),
('ai_claude_base_url', 'https://api.anthropic.com', 'string', 'Claude API地址', 'ai', 31),
('ai_claude_model', 'claude-3-sonnet-20240229', 'string', 'Claude模型', 'ai', 32),

ON DUPLICATE KEY UPDATE 
`config_value` = VALUES(`config_value`),
`description` = VALUES(`description`);
