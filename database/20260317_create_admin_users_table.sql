SET NAMES utf8mb4;
USE taichu;

START TRANSACTION;

-- 管理员主表：兼容后台登录、管理员列表与 JWT 鉴权
CREATE TABLE IF NOT EXISTS `tc_admin` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(255) NOT NULL COMMENT '密码哈希',
    `nickname` VARCHAR(50) DEFAULT '' COMMENT '昵称',
    `email` VARCHAR(100) DEFAULT '' COMMENT '邮箱',
    `phone` VARCHAR(20) DEFAULT '' COMMENT '手机号',
    `avatar` VARCHAR(500) DEFAULT '' COMMENT '头像',
    `role_id` INT UNSIGNED DEFAULT 0 COMMENT '角色ID（兼容旧结构）',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间',
    `last_login_ip` VARCHAR(45) DEFAULT '' COMMENT '最后登录IP',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_username` (`username`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- 角色表：确保超级管理员权限链路可用
CREATE TABLE IF NOT EXISTS `tc_admin_role` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '角色标识',
    `description` VARCHAR(255) DEFAULT '' COMMENT '角色描述',
    `is_super` TINYINT DEFAULT 0 COMMENT '是否超级管理员',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色表';

-- 管理员角色关联表：确保权限服务能查到角色
CREATE TABLE IF NOT EXISTS `tc_admin_user_role` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_admin_role` (`admin_id`, `role_id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色关联表';

-- 默认超级管理员角色
INSERT INTO `tc_admin_role` (`name`, `code`, `description`, `is_super`)
VALUES ('超级管理员', 'super_admin', '拥有所有后台权限', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `is_super` = VALUES(`is_super`);

-- 默认管理员账号：admin / admin123
-- bcrypt 哈希通过 bcrypt-cli 生成，避免明文密码落库
INSERT INTO `tc_admin` (
    `username`,
    `password`,
    `nickname`,
    `email`,
    `phone`,
    `avatar`,
    `status`
)
VALUES (
    'admin',
    '$2a$10$69ekdLT1xe/Niyazb/kBSegJPAx0uJf6uhq5mz.LfZ.2rJ5YtUjoC',
    '系统管理员',
    'admin@example.com',
    '',
    '',
    1
)
ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`),
    `nickname` = VALUES(`nickname`),
    `email` = VALUES(`email`),
    `status` = VALUES(`status`),
    `updated_at` = CURRENT_TIMESTAMP;

-- 为默认管理员绑定超级管理员角色
INSERT IGNORE INTO `tc_admin_user_role` (`admin_id`, `role_id`, `created_at`)
SELECT a.`id`, r.`id`, NOW()
FROM `tc_admin` a
INNER JOIN `tc_admin_role` r ON r.`code` = 'super_admin'
WHERE a.`username` = 'admin';

-- 兼容旧结构中的 role_id 字段
UPDATE `tc_admin` a
INNER JOIN `tc_admin_role` r ON r.`code` = 'super_admin'
SET a.`role_id` = r.`id`
WHERE a.`username` = 'admin' AND (a.`role_id` = 0 OR a.`role_id` IS NULL);

COMMIT;
