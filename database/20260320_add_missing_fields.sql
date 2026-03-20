-- 补充 users 表验证码相关字段
ALTER TABLE `tc_user` 
ADD COLUMN `last_sms_code_time` DATETIME NULL COMMENT '最后一次发送验证码时间',
ADD COLUMN `sms_code_attempts` INT UNSIGNED DEFAULT 0 COMMENT '验证码尝试次数';

-- 补充 points_records 表业务分类字段
ALTER TABLE `tc_points_record`
ADD COLUMN `action` VARCHAR(50) DEFAULT '' COMMENT '业务动作枚举（如：register, daily_checkin, bazi_query, tarot_query等）';

-- 补充 bazi_records 表 AI 模型字段
ALTER TABLE `tc_bazi_record`
ADD COLUMN `ai_analysis_model` VARCHAR(50) DEFAULT '' COMMENT 'AI 分析模型来源（如：deepseek-r1, gpt-4等）';
