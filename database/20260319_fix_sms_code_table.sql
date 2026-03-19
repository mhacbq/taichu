-- =============================================================
-- 修复短信验证码表 tc_sms_code 缺失 / 旧字段漂移
-- 目标：兼容 sms_codes 旧表名，以及 used / expired_at 老字段
-- 设计：幂等执行；不合并真实业务数据，只补齐当前代码所需结构
-- =============================================================

SET NAMES utf8mb4;

-- 1. 若仍是旧表名 sms_codes，且 tc_sms_code 尚不存在，则安全重命名
SET @has_tc_sms_code := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
);
SET @has_sms_codes := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sms_codes'
);
SET @sql_rename_sms_codes := IF(
    @has_tc_sms_code = 0 AND @has_sms_codes > 0,
    "ALTER TABLE `sms_codes` RENAME TO `tc_sms_code`",
    "SELECT 'skip rename sms_codes -> tc_sms_code' AS info"
);
PREPARE stmt_rename_sms_codes FROM @sql_rename_sms_codes;
EXECUTE stmt_rename_sms_codes;
DEALLOCATE PREPARE stmt_rename_sms_codes;

-- 2. 缺表时直接创建代码当前期望的标准结构
CREATE TABLE IF NOT EXISTS `tc_sms_code` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
    `code` VARCHAR(10) NOT NULL COMMENT '验证码',
    `type` VARCHAR(20) NOT NULL DEFAULT 'register' COMMENT '类型 register/login/reset',
    `expire_time` DATETIME NOT NULL COMMENT '过期时间',
    `is_used` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已使用 0否 1是',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'User-Agent',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_phone` (`phone`),
    INDEX `idx_code` (`code`),
    INDEX `idx_type` (`type`),
    INDEX `idx_is_used` (`is_used`),
    INDEX `idx_phone_type` (`phone`, `type`),
    INDEX `idx_expire_time` (`expire_time`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信验证码表';

-- 3. 兼容旧结构：补列
SET @has_sms_code_expire_time := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND COLUMN_NAME = 'expire_time'
);
SET @has_sms_code_is_used := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND COLUMN_NAME = 'is_used'
);
SET @has_sms_code_user_agent := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND COLUMN_NAME = 'user_agent'
);
SET @has_sms_code_expired_at := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND COLUMN_NAME = 'expired_at'
);
SET @has_sms_code_used := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND COLUMN_NAME = 'used'
);

SET @sql_add_expire_time := IF(
    @has_sms_code_expire_time = 0,
    "ALTER TABLE `tc_sms_code` ADD COLUMN `expire_time` DATETIME NULL DEFAULT NULL COMMENT '过期时间' AFTER `type`",
    "SELECT 'skip add expire_time' AS info"
);
PREPARE stmt_add_expire_time FROM @sql_add_expire_time;
EXECUTE stmt_add_expire_time;
DEALLOCATE PREPARE stmt_add_expire_time;

SET @sql_add_is_used := IF(
    @has_sms_code_is_used = 0,
    "ALTER TABLE `tc_sms_code` ADD COLUMN `is_used` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已使用 0否 1是' AFTER `expire_time`",
    "SELECT 'skip add is_used' AS info"
);
PREPARE stmt_add_is_used FROM @sql_add_is_used;
EXECUTE stmt_add_is_used;
DEALLOCATE PREPARE stmt_add_is_used;

SET @sql_add_user_agent := IF(
    @has_sms_code_user_agent = 0,
    "ALTER TABLE `tc_sms_code` ADD COLUMN `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'User-Agent' AFTER `ip`",
    "SELECT 'skip add user_agent' AS info"
);
PREPARE stmt_add_user_agent FROM @sql_add_user_agent;
EXECUTE stmt_add_user_agent;
DEALLOCATE PREPARE stmt_add_user_agent;

-- 4. 兼容旧结构：回填旧字段数据，不删除旧列，避免不可逆变更
SET @sql_backfill_expire_time := IF(
    @has_sms_code_expired_at > 0,
    "UPDATE `tc_sms_code` SET `expire_time` = `expired_at` WHERE `expire_time` IS NULL AND `expired_at` IS NOT NULL",
    "SELECT 'skip backfill expire_time' AS info"
);
PREPARE stmt_backfill_expire_time FROM @sql_backfill_expire_time;
EXECUTE stmt_backfill_expire_time;
DEALLOCATE PREPARE stmt_backfill_expire_time;

SET @sql_backfill_is_used := IF(
    @has_sms_code_used > 0,
    "UPDATE `tc_sms_code` SET `is_used` = `used` WHERE `is_used` = 0 AND `used` <> 0",
    "SELECT 'skip backfill is_used' AS info"
);
PREPARE stmt_backfill_is_used FROM @sql_backfill_is_used;
EXECUTE stmt_backfill_is_used;
DEALLOCATE PREPARE stmt_backfill_is_used;

-- 5. 补齐关键索引，兼容频控 / 校验 / 清理查询
SET @has_idx_phone := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND INDEX_NAME = 'idx_phone'
);
SET @has_idx_code := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND INDEX_NAME = 'idx_code'
);
SET @has_idx_type := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND INDEX_NAME = 'idx_type'
);
SET @has_idx_is_used := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND INDEX_NAME = 'idx_is_used'
);
SET @has_idx_phone_type := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND INDEX_NAME = 'idx_phone_type'
);
SET @has_idx_expire_time := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND INDEX_NAME = 'idx_expire_time'
);
SET @has_idx_created_at := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND INDEX_NAME = 'idx_created_at'
);

SET @sql_add_idx_phone := IF(
    @has_idx_phone = 0,
    "ALTER TABLE `tc_sms_code` ADD INDEX `idx_phone` (`phone`)",
    "SELECT 'skip add idx_phone' AS info"
);
PREPARE stmt_add_idx_phone FROM @sql_add_idx_phone;
EXECUTE stmt_add_idx_phone;
DEALLOCATE PREPARE stmt_add_idx_phone;

SET @sql_add_idx_code := IF(
    @has_idx_code = 0,
    "ALTER TABLE `tc_sms_code` ADD INDEX `idx_code` (`code`)",
    "SELECT 'skip add idx_code' AS info"
);
PREPARE stmt_add_idx_code FROM @sql_add_idx_code;
EXECUTE stmt_add_idx_code;
DEALLOCATE PREPARE stmt_add_idx_code;

SET @sql_add_idx_type := IF(
    @has_idx_type = 0,
    "ALTER TABLE `tc_sms_code` ADD INDEX `idx_type` (`type`)",
    "SELECT 'skip add idx_type' AS info"
);
PREPARE stmt_add_idx_type FROM @sql_add_idx_type;
EXECUTE stmt_add_idx_type;
DEALLOCATE PREPARE stmt_add_idx_type;

SET @sql_add_idx_is_used := IF(
    @has_idx_is_used = 0,
    "ALTER TABLE `tc_sms_code` ADD INDEX `idx_is_used` (`is_used`)",
    "SELECT 'skip add idx_is_used' AS info"
);
PREPARE stmt_add_idx_is_used FROM @sql_add_idx_is_used;
EXECUTE stmt_add_idx_is_used;
DEALLOCATE PREPARE stmt_add_idx_is_used;

SET @sql_add_idx_phone_type := IF(
    @has_idx_phone_type = 0,
    "ALTER TABLE `tc_sms_code` ADD INDEX `idx_phone_type` (`phone`, `type`)",
    "SELECT 'skip add idx_phone_type' AS info"
);
PREPARE stmt_add_idx_phone_type FROM @sql_add_idx_phone_type;
EXECUTE stmt_add_idx_phone_type;
DEALLOCATE PREPARE stmt_add_idx_phone_type;

SET @sql_add_idx_expire_time := IF(
    @has_idx_expire_time = 0,
    "ALTER TABLE `tc_sms_code` ADD INDEX `idx_expire_time` (`expire_time`)",
    "SELECT 'skip add idx_expire_time' AS info"
);
PREPARE stmt_add_idx_expire_time FROM @sql_add_idx_expire_time;
EXECUTE stmt_add_idx_expire_time;
DEALLOCATE PREPARE stmt_add_idx_expire_time;

SET @sql_add_idx_created_at := IF(
    @has_idx_created_at = 0,
    "ALTER TABLE `tc_sms_code` ADD INDEX `idx_created_at` (`created_at`)",
    "SELECT 'skip add idx_created_at' AS info"
);
PREPARE stmt_add_idx_created_at FROM @sql_add_idx_created_at;
EXECUTE stmt_add_idx_created_at;
DEALLOCATE PREPARE stmt_add_idx_created_at;
