SET NAMES utf8mb4;
USE taichu;

ALTER TABLE `tc_points_record`
  ADD COLUMN IF NOT EXISTS `amount` INT NOT NULL DEFAULT 0 COMMENT '变动数量（绝对值）' AFTER `points`,
  ADD COLUMN IF NOT EXISTS `balance` INT NULL DEFAULT NULL COMMENT '变动后余额' AFTER `amount`,
  ADD COLUMN IF NOT EXISTS `reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '变动原因' AFTER `remark`;

UPDATE `tc_points_record`
SET `amount` = ABS(COALESCE(`points`, 0))
WHERE (`amount` IS NULL OR `amount` = 0) AND `points` IS NOT NULL;

UPDATE `tc_points_record`
SET `reason` = COALESCE(
  NULLIF(`reason`, ''),
  NULLIF(`remark`, ''),
  NULLIF(`description`, ''),
  NULLIF(`action`, ''),
  '积分变动'
)
WHERE `reason` IS NULL OR `reason` = '';
