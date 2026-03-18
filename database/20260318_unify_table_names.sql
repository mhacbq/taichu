-- =============================================================
-- 太初命理网站 - 表名统一迁移脚本
-- 创建时间：2026-03-18
-- 用途：将不一致的表名改为统一的 tc_ 前缀格式
--       这样方便模型配置与数据库表名保持一致
-- =============================================================

SET NAMES utf8mb4;
USE taichu;

-- 检查表是否存在，然后重命名
-- 方法：通过存储过程避免"unknown table"错误

-- 1. 重命名 sms_codes 为 tc_sms_code
SET @check_sms_codes := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = 'taichu' AND TABLE_NAME = 'sms_codes'
);

SET @sql_sms_codes := IF(
    @check_sms_codes > 0,
    "ALTER TABLE `sms_codes` RENAME TO `tc_sms_code`",
    "SELECT '表 sms_codes 不存在，跳过' AS info"
);

PREPARE stmt_sms_codes FROM @sql_sms_codes;
EXECUTE stmt_sms_codes;
DEALLOCATE PREPARE stmt_sms_codes;

-- 2. 重命名 sms_configs 为 tc_sms_config
SET @check_sms_configs := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_configs'
);


SET @sql_sms_configs := IF(
    @check_sms_configs > 0,
    "ALTER TABLE `sms_configs` RENAME TO `tc_sms_config`",
    "SELECT '表 sms_configs 不存在，跳过' AS info"
);

PREPARE stmt_sms_configs FROM @sql_sms_configs;
EXECUTE stmt_sms_configs;
DEALLOCATE PREPARE stmt_sms_configs;

-- 3. 重命名 payment_configs 为 tc_payment_config
SET @check_payment_configs := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = 'taichu' AND TABLE_NAME = 'payment_configs'
);

SET @sql_payment_configs := IF(
    @check_payment_configs > 0,
    "ALTER TABLE `payment_configs` RENAME TO `tc_payment_config`",
    "SELECT '表 payment_configs 不存在，跳过' AS info"
);

PREPARE stmt_payment_configs FROM @sql_payment_configs;
EXECUTE stmt_payment_configs;
DEALLOCATE PREPARE stmt_payment_configs;

-- 验证结果
SELECT '表名统一迁移完成' AS message;
SELECT
    candidates.table_name,
    CASE WHEN actual.TABLE_NAME IS NULL THEN 'missing' ELSE 'exists' END AS status
FROM (
    SELECT 'tc_sms_code' AS table_name
    UNION ALL SELECT 'tc_sms_config'
    UNION ALL SELECT 'tc_payment_config'
) AS candidates
LEFT JOIN information_schema.TABLES AS actual
    ON actual.TABLE_SCHEMA = DATABASE()
   AND actual.TABLE_NAME = candidates.table_name;


