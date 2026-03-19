-- 2026-03-19 每日运势表结构兼容补丁
-- 背景：部分本地库仍保留旧版 tc_daily_fortune 结构（user_id/fortune_type/card_* 口径），
-- 会导致当前 DailyFortune 模型插入 public snapshot 时缺列或缺默认值直接 500。

CREATE TABLE IF NOT EXISTS `tc_daily_fortune` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL COMMENT '日期',
    `lunar_date` VARCHAR(50) DEFAULT '' COMMENT '农历日期',
    `overall_score` INT DEFAULT 70 COMMENT '综合评分 0-100',
    `summary` VARCHAR(500) DEFAULT '' COMMENT '运势摘要',
    `career_score` INT DEFAULT 70 COMMENT '事业评分',
    `career_desc` VARCHAR(500) DEFAULT '' COMMENT '事业描述',
    `wealth_score` INT DEFAULT 70 COMMENT '财富评分',
    `wealth_desc` VARCHAR(500) DEFAULT '' COMMENT '财富描述',
    `love_score` INT DEFAULT 70 COMMENT '感情评分',
    `love_desc` VARCHAR(500) DEFAULT '' COMMENT '感情描述',
    `health_score` INT DEFAULT 70 COMMENT '健康评分',
    `health_desc` VARCHAR(500) DEFAULT '' COMMENT '健康描述',
    `yi` VARCHAR(500) DEFAULT '' COMMENT '宜（宜做的事）',
    `ji` VARCHAR(500) DEFAULT '' COMMENT '忌（忌做的事）',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_date` (`date`),
    INDEX `idx_overall_score` (`overall_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日运势表';

SET @db_name = DATABASE();

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'lunar_date'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `lunar_date` VARCHAR(50) DEFAULT '''' COMMENT ''农历日期'' AFTER `date`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'overall_score'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `overall_score` INT DEFAULT 70 COMMENT ''综合评分 0-100'' AFTER `lunar_date`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'summary'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `summary` VARCHAR(500) DEFAULT '''' COMMENT ''运势摘要'' AFTER `overall_score`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'career_score'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `career_score` INT DEFAULT 70 COMMENT ''事业评分'' AFTER `summary`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'career_desc'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `career_desc` VARCHAR(500) DEFAULT '''' COMMENT ''事业描述'' AFTER `career_score`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'wealth_score'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `wealth_score` INT DEFAULT 70 COMMENT ''财富评分'' AFTER `career_desc`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'wealth_desc'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `wealth_desc` VARCHAR(500) DEFAULT '''' COMMENT ''财富描述'' AFTER `wealth_score`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'love_score'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `love_score` INT DEFAULT 70 COMMENT ''感情评分'' AFTER `wealth_desc`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'love_desc'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `love_desc` VARCHAR(500) DEFAULT '''' COMMENT ''感情描述'' AFTER `love_score`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'health_score'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `health_score` INT DEFAULT 70 COMMENT ''健康评分'' AFTER `love_desc`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'health_desc'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `health_desc` VARCHAR(500) DEFAULT '''' COMMENT ''健康描述'' AFTER `health_score`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'yi'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `yi` VARCHAR(500) DEFAULT '''' COMMENT ''宜（宜做的事）'' AFTER `health_desc`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'ji'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `ji` VARCHAR(500) DEFAULT '''' COMMENT ''忌（忌做的事）'' AFTER `yi`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'updated_at'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD COLUMN `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'user_id'
    ),
    'ALTER TABLE `tc_daily_fortune` MODIFY COLUMN `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''兼容旧结构的占位用户ID''',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND COLUMN_NAME = 'fortune_type'
    ),
    'ALTER TABLE `tc_daily_fortune` MODIFY COLUMN `fortune_type` VARCHAR(20) NOT NULL DEFAULT ''daily'' COMMENT ''兼容旧结构的每日运势类型''',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_daily_fortune' AND INDEX_NAME = 'idx_overall_score'
    ),
    'SELECT 1',
    'ALTER TABLE `tc_daily_fortune` ADD INDEX `idx_overall_score` (`overall_score`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
