-- 为八字记录表添加 AI 分析缓存字段
-- 迁移时间：2026-03-24

SET @db_name = DATABASE();

-- 添加 AI 解盘结果缓存字段
SET @sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'ai_analysis'),
    'SELECT "skip ai_analysis"',
    'ALTER TABLE `tc_bazi_record` ADD COLUMN `ai_analysis` MEDIUMTEXT NULL COMMENT "AI解盘结果缓存" AFTER `analysis`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加 AI 大运评分字段
SET @sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'dayun_scores'),
    'SELECT "skip dayun_scores"',
    'ALTER TABLE `tc_bazi_record` ADD COLUMN `dayun_scores` TEXT NULL COMMENT "AI大运评分JSON" AFTER `ai_analysis`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
