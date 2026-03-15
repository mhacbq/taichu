-- AI提示词表
CREATE TABLE IF NOT EXISTS `ai_prompts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '提示词名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '提示词编码',
    `type` VARCHAR(50) NOT NULL COMMENT '类型: bazi/hehun/tarot/daily/etc',
    `system_prompt` TEXT COMMENT '系统提示词',
    `user_prompt` TEXT COMMENT '用户提示词模板',
    `model` VARCHAR(50) DEFAULT 'gpt-4' COMMENT 'AI模型',
    `temperature` DECIMAL(3,2) DEFAULT 0.7 COMMENT '温度参数',
    `max_tokens` INT DEFAULT 2000 COMMENT '最大Token数',
    `variables` JSON NULL COMMENT '变量定义',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `description` VARCHAR(500) DEFAULT '' COMMENT '描述',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_code` (`code`),
    INDEX `idx_type` (`type`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI提示词表';

-- 插入默认AI提示词
INSERT INTO `ai_prompts` (`name`, `code`, `type`, `system_prompt`, `user_prompt`, `description`) VALUES
('八字基础分析', 'bazi_basic', 'bazi', '你是一位精通中国传统命理的资深命理师，擅长八字分析。请用专业但易懂的语言为用户解读八字。', 
'请分析以下八字信息：\n出生时间：{birth_date} {birth_time}\n性别：{gender}\n八字：{bazi}\n五行：{wuxing}\n请提供详细的命理分析。', '八字基础分析提示词'),

('八字合婚分析', 'hehun_analysis', 'hehun', '你是一位精通中国传统命理的资深命理师，擅长八字合婚分析。请用专业但易懂的语言为情侣提供合婚建议。',
'请分析以下两人的八字合婚：\n男方：{male_name}，出生时间：{male_birth}\n女方：{female_name}，出生时间：{female_birth}\n男方八字：{male_bazi}\n女方八字：{female_bazi}\n请提供详细的合婚分析和建议。', '八字合婚分析提示词'),

('塔罗牌解读', 'tarot_interpret', 'tarot', '你是一位经验丰富的塔罗牌解读师，擅长结合牌面为用户提供 insightful 的解读。请用温暖、积极的语言进行解读。',
'请解读以下塔罗牌阵：\n问题：{question}\n牌阵类型：{spread_type}\n抽到的牌：{cards}\n请提供详细的牌义解读和建议。', '塔罗牌解读提示词'),

('每日运势', 'daily_fortune', 'daily', '你是一位运势分析师，擅长结合八字和星象为用户提供每日运势指导。请用积极、实用的语言撰写运势。',
'请为以下用户生成今日运势：\n八字：{bazi}\n今日干支：{today_ganzhi}\n今日五行：{today_wuxing}\n请从事业、财运、感情、健康等方面分析今日运势。', '每日运势生成提示词');
