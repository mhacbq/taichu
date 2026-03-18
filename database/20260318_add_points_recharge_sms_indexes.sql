SET NAMES utf8mb4;
USE taichu;

-- =============================================================
-- 高频查询与定时清理索引补齐
-- 1. tc_points_record(user_id, created_at)
-- 2. tc_recharge_order(user_id, status, created_at)
-- 3. tc_sms_code(expire_time, is_used)
-- 幂等执行：通过 information_schema 检查后再创建
-- =============================================================

SET @has_points_user_created := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_points_record'
      AND INDEX_NAME = 'idx_user_created'
);
SET @sql_points_user_created := IF(
    @has_points_user_created = 0,
    "ALTER TABLE `tc_points_record` ADD INDEX `idx_user_created` (`user_id`, `created_at`)",
    'SELECT 1'
);
PREPARE stmt_points_user_created FROM @sql_points_user_created;
EXECUTE stmt_points_user_created;
DEALLOCATE PREPARE stmt_points_user_created;

SET @has_recharge_user_status_created := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_recharge_order'
      AND INDEX_NAME = 'idx_user_status_created'
);
SET @sql_recharge_user_status_created := IF(
    @has_recharge_user_status_created = 0,
    "ALTER TABLE `tc_recharge_order` ADD INDEX `idx_user_status_created` (`user_id`, `status`, `created_at`)",
    'SELECT 1'
);
PREPARE stmt_recharge_user_status_created FROM @sql_recharge_user_status_created;
EXECUTE stmt_recharge_user_status_created;
DEALLOCATE PREPARE stmt_recharge_user_status_created;

SET @has_sms_expire_used := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tc_sms_code'
      AND INDEX_NAME = 'idx_expire_used'
);
SET @sql_sms_expire_used := IF(
    @has_sms_expire_used = 0,
    "ALTER TABLE `tc_sms_code` ADD INDEX `idx_expire_used` (`expire_time`, `is_used`)",
    'SELECT 1'
);
PREPARE stmt_sms_expire_used FROM @sql_sms_expire_used;
EXECUTE stmt_sms_expire_used;
DEALLOCATE PREPARE stmt_sms_expire_used;

SELECT '索引补齐完成' AS migration_result;
