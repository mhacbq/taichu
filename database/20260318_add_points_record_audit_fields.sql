SET NAMES utf8mb4;
USE taichu;

SET @has_amount := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tc_points_record'
    AND COLUMN_NAME = 'amount'
);
SET @sql_amount := IF(
  @has_amount = 0,
  "ALTER TABLE `tc_points_record` ADD COLUMN `amount` INT NOT NULL DEFAULT 0 COMMENT '变动数量（绝对值）' AFTER `points`",
  'SELECT 1'
);
PREPARE stmt_amount FROM @sql_amount;
EXECUTE stmt_amount;
DEALLOCATE PREPARE stmt_amount;

SET @has_balance := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tc_points_record'
    AND COLUMN_NAME = 'balance'
);
SET @sql_balance := IF(
  @has_balance = 0,
  "ALTER TABLE `tc_points_record` ADD COLUMN `balance` INT NULL DEFAULT NULL COMMENT '变动后余额' AFTER `amount`",
  'SELECT 1'
);
PREPARE stmt_balance FROM @sql_balance;
EXECUTE stmt_balance;
DEALLOCATE PREPARE stmt_balance;

SET @has_reason := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tc_points_record'
    AND COLUMN_NAME = 'reason'
);
SET @sql_reason := IF(
  @has_reason = 0,
  "ALTER TABLE `tc_points_record` ADD COLUMN `reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '变动原因' AFTER `remark`",
  'SELECT 1'
);
PREPARE stmt_reason FROM @sql_reason;
EXECUTE stmt_reason;
DEALLOCATE PREPARE stmt_reason;

UPDATE `tc_points_record`
SET `amount` = ABS(COALESCE(`points`, 0))
WHERE (`amount` IS NULL OR `amount` = 0) AND `points` IS NOT NULL;

UPDATE `tc_points_record`
SET `reason` = COALESCE(
  NULLIF(`reason`, ''),
  NULLIF(`remark`, ''),
  NULLIF(`action`, ''),
  '积分变动'
)
WHERE `reason` IS NULL OR `reason` = '';
