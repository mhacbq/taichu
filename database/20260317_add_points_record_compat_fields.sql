-- 积分记录兼容字段补齐
-- 创建时间：2026-03-17

USE taichu;

ALTER TABLE `tc_points_record`
    ADD COLUMN IF NOT EXISTS `action` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '操作名称' AFTER `user_id`,
    ADD COLUMN IF NOT EXISTS `points` INT NOT NULL DEFAULT 0 COMMENT '有符号积分变动值' AFTER `action`,
    ADD COLUMN IF NOT EXISTS `related_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联ID' AFTER `type`,
    ADD COLUMN IF NOT EXISTS `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '简要说明' AFTER `reason`;

UPDATE `tc_points_record`
SET
    `action` = CASE
        WHEN `action` = '' THEN COALESCE(NULLIF(`reason`, ''), '系统积分变动')
        ELSE `action`
    END,
    `points` = CASE
        WHEN `points` = 0 THEN CASE
            WHEN `type` IN ('reduce', 'consume', 'deduct', 'expense') THEN -ABS(COALESCE(`amount`, 0))
            ELSE ABS(COALESCE(`amount`, 0))
        END
        ELSE `points`
    END,
    `related_id` = CASE
        WHEN `related_id` = 0 THEN COALESCE(`source_id`, 0)
        ELSE `related_id`
    END,
    `description` = CASE
        WHEN `description` = '' THEN COALESCE(NULLIF(`reason`, ''), '系统积分变动')
        ELSE `description`
    END;
