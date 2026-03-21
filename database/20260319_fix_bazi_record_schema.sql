-- 2026-03-19
-- 修复 tc_bazi_record 与当前八字接口口径不一致的问题
-- 目标：补齐 location / 四柱拆分 / 分享相关字段，兼容旧 rich schema 与当前控制器

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @db_name = DATABASE();

-- 1) 补齐当前运行时直接依赖的字段
SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'location'),
  'SELECT ''skip location''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `location` VARCHAR(100) NOT NULL DEFAULT '''' COMMENT ''出生地点（当前接口口径）'' AFTER `birth_time`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'year_gan'),
  'SELECT ''skip year_gan''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `year_gan` VARCHAR(10) NOT NULL DEFAULT '''' COMMENT ''年干'' AFTER `latitude`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'year_zhi'),
  'SELECT ''skip year_zhi''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `year_zhi` VARCHAR(10) NOT NULL DEFAULT '''' COMMENT ''年支'' AFTER `year_gan`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'month_gan'),
  'SELECT ''skip month_gan''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `month_gan` VARCHAR(10) NOT NULL DEFAULT '''' COMMENT ''月干'' AFTER `year_zhi`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'month_zhi'),
  'SELECT ''skip month_zhi''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `month_zhi` VARCHAR(10) NOT NULL DEFAULT '''' COMMENT ''月支'' AFTER `month_gan`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'day_gan'),
  'SELECT ''skip day_gan''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `day_gan` VARCHAR(10) NOT NULL DEFAULT '''' COMMENT ''日干'' AFTER `month_zhi`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'day_zhi'),
  'SELECT ''skip day_zhi''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `day_zhi` VARCHAR(10) NOT NULL DEFAULT '''' COMMENT ''日支'' AFTER `day_gan`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'hour_gan'),
  'SELECT ''skip hour_gan''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `hour_gan` VARCHAR(10) NOT NULL DEFAULT '''' COMMENT ''时干'' AFTER `day_zhi`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'hour_zhi'),
  'SELECT ''skip hour_zhi''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `hour_zhi` VARCHAR(10) NOT NULL DEFAULT '''' COMMENT ''时支'' AFTER `hour_gan`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'analysis'),
  'SELECT ''skip analysis''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `analysis` TEXT NULL COMMENT ''文本分析摘要'' AFTER `hour_pillar`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'is_first'),
  'SELECT ''skip is_first''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `is_first` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''是否首次排盘'' AFTER `analysis`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'points_used'),
  'SELECT ''skip points_used''',
  IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'is_paid'),
    'ALTER TABLE `tc_bazi_record` ADD COLUMN `points_used` INT NOT NULL DEFAULT 0 COMMENT ''消耗积分'' AFTER `is_paid`',
    'ALTER TABLE `tc_bazi_record` ADD COLUMN `points_used` INT NOT NULL DEFAULT 0 COMMENT ''消耗积分'' AFTER `is_first`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'is_public'),
  'SELECT ''skip is_public''',
  IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'points_used'),
    'ALTER TABLE `tc_bazi_record` ADD COLUMN `is_public` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''是否公开分享'' AFTER `points_used`',
    'ALTER TABLE `tc_bazi_record` ADD COLUMN `is_public` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''是否公开分享'' AFTER `is_first`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'share_code'),
  'SELECT ''skip share_code''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `share_code` VARCHAR(50) NOT NULL DEFAULT '''' COMMENT ''分享码'' AFTER `is_public`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND COLUMN_NAME = 'view_count'),
  'SELECT ''skip view_count''',
  'ALTER TABLE `tc_bazi_record` ADD COLUMN `view_count` INT NOT NULL DEFAULT 0 COMMENT ''查看次数'' AFTER `share_code`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) 补齐索引
SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'tc_bazi_record' AND INDEX_NAME = 'idx_share_code'),
  'SELECT ''skip idx_share_code''',
  'ALTER TABLE `tc_bazi_record` ADD INDEX `idx_share_code` (`share_code`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) 回填 location 与四柱拆分，保证旧 rich schema 的历史记录也能被当前接口读出
UPDATE `tc_bazi_record`
SET `location` = CASE
    WHEN (`location` IS NULL OR `location` = '') AND `birth_place` IS NOT NULL AND `birth_place` <> '' THEN `birth_place`
    ELSE `location`
END;

UPDATE `tc_bazi_record`
SET
  `year_gan` = CASE WHEN (`year_gan` = '' OR `year_gan` IS NULL) AND `year_pillar` IS NOT NULL AND CHAR_LENGTH(`year_pillar`) >= 2 THEN SUBSTRING(`year_pillar`, 1, 1) ELSE `year_gan` END,
  `year_zhi` = CASE WHEN (`year_zhi` = '' OR `year_zhi` IS NULL) AND `year_pillar` IS NOT NULL AND CHAR_LENGTH(`year_pillar`) >= 2 THEN SUBSTRING(`year_pillar`, 2, 1) ELSE `year_zhi` END,
  `month_gan` = CASE WHEN (`month_gan` = '' OR `month_gan` IS NULL) AND `month_pillar` IS NOT NULL AND CHAR_LENGTH(`month_pillar`) >= 2 THEN SUBSTRING(`month_pillar`, 1, 1) ELSE `month_gan` END,
  `month_zhi` = CASE WHEN (`month_zhi` = '' OR `month_zhi` IS NULL) AND `month_pillar` IS NOT NULL AND CHAR_LENGTH(`month_pillar`) >= 2 THEN SUBSTRING(`month_pillar`, 2, 1) ELSE `month_zhi` END,
  `day_gan` = CASE WHEN (`day_gan` = '' OR `day_gan` IS NULL) AND `day_pillar` IS NOT NULL AND CHAR_LENGTH(`day_pillar`) >= 2 THEN SUBSTRING(`day_pillar`, 1, 1) ELSE `day_gan` END,
  `day_zhi` = CASE WHEN (`day_zhi` = '' OR `day_zhi` IS NULL) AND `day_pillar` IS NOT NULL AND CHAR_LENGTH(`day_pillar`) >= 2 THEN SUBSTRING(`day_pillar`, 2, 1) ELSE `day_zhi` END,
  `hour_gan` = CASE WHEN (`hour_gan` = '' OR `hour_gan` IS NULL) AND `hour_pillar` IS NOT NULL AND CHAR_LENGTH(`hour_pillar`) >= 2 THEN SUBSTRING(`hour_pillar`, 1, 1) ELSE `hour_gan` END,
  `hour_zhi` = CASE WHEN (`hour_zhi` = '' OR `hour_zhi` IS NULL) AND `hour_pillar` IS NOT NULL AND CHAR_LENGTH(`hour_pillar`) >= 2 THEN SUBSTRING(`hour_pillar`, 2, 1) ELSE `hour_zhi` END;

SET FOREIGN_KEY_CHECKS = 1;
