-- 为八字记录表添加 AI 分析缓存字段
-- 迁移时间：2026-03-24

-- 添加 AI 解盘结果缓存字段
ALTER TABLE `tc_bazi_record`
    ADD COLUMN IF NOT EXISTS `ai_analysis` MEDIUMTEXT NULL COMMENT 'AI解盘结果缓存' AFTER `analysis`,
    ADD COLUMN IF NOT EXISTS `dayun_scores` TEXT NULL COMMENT 'AI大运评分JSON，格式：{"0":85,"1":62}' AFTER `ai_analysis`;
