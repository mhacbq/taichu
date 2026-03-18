SET NAMES utf8mb4;
USE taichu;

INSERT INTO `tc_admin_permission` (`name`, `code`, `module`, `description`)
VALUES
    ('黄历查看', 'almanac_view', 'content', '查看黄历数据'),
    ('黄历编辑', 'almanac_edit', 'content', '编辑黄历数据')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `module` = VALUES(`module`),
    `description` = VALUES(`description`);

INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `tc_admin_role` r
JOIN `tc_admin_permission` p ON p.code IN ('points_adjust', 'content_manage', 'almanac_view', 'almanac_edit')
WHERE r.code = 'operator'
ON DUPLICATE KEY UPDATE
    `permission_id` = VALUES(`permission_id`);
