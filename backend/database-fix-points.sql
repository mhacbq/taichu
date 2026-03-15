-- ============================================================
-- 太初命理网站 - tc_points_record 表字段修复
-- 说明：如果提示"Duplicate column name"表示字段已存在，可忽略
-- ============================================================

SET NAMES utf8mb4;

-- 查看当前表结构（调试用）
-- DESCRIBE `tc_points_record`;

-- 添加 action 字段
ALTER TABLE `tc_points_record` ADD COLUMN `action` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '动作描述' AFTER `user_id`;

-- 添加 points 字段
ALTER TABLE `tc_points_record` ADD COLUMN `points` INT(11) NOT NULL DEFAULT '0' COMMENT '变动积分（正数增加，负数减少）' AFTER `action`;

-- 添加 type 字段
ALTER TABLE `tc_points_record` ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '类型' AFTER `points`;

-- 添加 related_id 字段
ALTER TABLE `tc_points_record` ADD COLUMN `related_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关联ID' AFTER `type`;

-- 添加 remark 字段
ALTER TABLE `tc_points_record` ADD COLUMN `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注' AFTER `related_id`;

-- 添加索引
ALTER TABLE `tc_points_record` ADD INDEX `idx_user_id` (`user_id`);
ALTER TABLE `tc_points_record` ADD INDEX `idx_type` (`type`);
ALTER TABLE `tc_points_record` ADD INDEX `idx_created_at` (`created_at`);

-- 完成
SELECT 'tc_points_record 表修复完成' AS message;
