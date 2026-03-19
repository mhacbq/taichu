-- =============================================================
-- 太初命理网站 - 管理后台初始化 SQL
-- 文件名保留为 20260317_create_admin_users_table.sql，兼容现有错误提示与导入文档
-- 适用场景：phpstudy / 本地 MySQL 缺少后台管理员与权限相关表时手动执行
-- 幂等设计：IF NOT EXISTS / ON DUPLICATE KEY UPDATE / INSERT IGNORE
-- 默认管理员：admin / admin123
-- =============================================================

SET NAMES utf8mb4;
USE taichu;

START TRANSACTION;

-- 管理员主表
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

-- 管理员权限表
CREATE TABLE IF NOT EXISTS `tc_admin_permission` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '权限名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '权限标识',
    `module` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '所属模块',
    `description` VARCHAR(255) DEFAULT '' COMMENT '权限说明',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_code` (`code`),
    KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员权限表';

-- 管理员角色表
CREATE TABLE IF NOT EXISTS `tc_admin_role` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '角色标识',
    `description` VARCHAR(255) DEFAULT '' COMMENT '角色描述',
    `is_super` TINYINT DEFAULT 0 COMMENT '是否超级管理员',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色表';

-- 角色权限关联表
CREATE TABLE IF NOT EXISTS `tc_admin_role_permission` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `permission_id` INT UNSIGNED NOT NULL COMMENT '权限ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_role_permission` (`role_id`, `permission_id`),
    KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限关联表';

-- 管理员角色关联表
CREATE TABLE IF NOT EXISTS `tc_admin_user_role` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_admin_role` (`admin_id`, `role_id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色关联表';

-- 管理员操作日志表
CREATE TABLE IF NOT EXISTS `tc_admin_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
    `admin_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '管理员名称',
    `action` VARCHAR(50) NOT NULL COMMENT '操作类型',
    `module` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作模块',
    `target_id` INT UNSIGNED DEFAULT 0 COMMENT '操作目标ID',
    `target_type` VARCHAR(50) DEFAULT '' COMMENT '操作目标类型',
    `detail` TEXT NULL COMMENT '操作详情',
    `before_data` JSON NULL COMMENT '操作前数据',
    `after_data` JSON NULL COMMENT '操作后数据',
    `ip` VARCHAR(45) DEFAULT '' COMMENT 'IP地址',
    `request_url` VARCHAR(500) DEFAULT '' COMMENT '请求地址',
    `request_method` VARCHAR(10) DEFAULT '' COMMENT '请求方法',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT '用户代理',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0失败 1成功',
    `error_msg` VARCHAR(500) DEFAULT '' COMMENT '错误信息',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_action` (`action`),
    KEY `idx_module` (`module`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员操作日志表';

-- 基础权限种子
INSERT INTO `tc_admin_permission` (`name`, `code`, `module`, `description`) VALUES
('用户查看', 'user_view', 'user', '查看用户列表和信息'),
('用户编辑', 'user_edit', 'user', '编辑用户信息'),
('用户删除', 'user_delete', 'user', '删除用户'),
('积分查看', 'points_view', 'points', '查看积分记录'),
('积分调整', 'points_adjust', 'points', '调整用户积分'),
('配置管理', 'config_manage', 'config', '管理系统配置'),
('配置查看', 'config_view', 'config', '查看系统配置'),
('日志查看', 'log_view', 'log', '查看操作日志'),
('数据统计', 'stats_view', 'stats', '查看统计数据'),
('内容管理', 'content_manage', 'content', '管理内容数据'),
('内容审核', 'content_audit', 'content', '审核内容'),
('订单查看', 'order_view', 'order', '查看订单'),
('订单处理', 'order_process', 'order', '处理订单'),
('VIP管理', 'vip_manage', 'vip', '管理VIP会员'),
('营销管理', 'marketing_manage', 'marketing', '管理营销活动'),
('黄历查看', 'almanac_view', 'content', '查看黄历数据'),
('黄历编辑', 'almanac_edit', 'content', '编辑黄历数据')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `module` = VALUES(`module`),
    `description` = VALUES(`description`);

-- 基础角色种子
INSERT INTO `tc_admin_role` (`name`, `code`, `description`, `is_super`) VALUES
('超级管理员', 'super_admin', '拥有所有后台权限', 1),
('普通管理员', 'normal_admin', '拥有常规管理权限', 0),
('运营人员', 'operator', '仅限查看和部分编辑权限', 0),
('客服人员', 'customer_service', '仅限处理用户反馈和订单', 0)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `is_super` = VALUES(`is_super`);

-- 普通管理员权限
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `tc_admin_role` r
JOIN `tc_admin_permission` p ON p.code IN (
    'user_view', 'user_edit', 'points_view', 'config_view',
    'stats_view', 'content_manage', 'order_view', 'order_process'
)
WHERE r.code = 'normal_admin'
ON DUPLICATE KEY UPDATE
    `permission_id` = VALUES(`permission_id`);

-- 运营人员权限（补齐黄历 / 内容 / 积分等常用后台口径）
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `tc_admin_role` r
JOIN `tc_admin_permission` p ON p.code IN (
    'user_view', 'points_view', 'points_adjust', 'stats_view',
    'content_manage', 'marketing_manage', 'almanac_view', 'almanac_edit'
)
WHERE r.code = 'operator'
ON DUPLICATE KEY UPDATE
    `permission_id` = VALUES(`permission_id`);

-- 客服人员权限
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `tc_admin_role` r
JOIN `tc_admin_permission` p ON p.code IN (
    'user_view', 'order_view', 'order_process'
)
WHERE r.code = 'customer_service'
ON DUPLICATE KEY UPDATE
    `permission_id` = VALUES(`permission_id`);

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
WHERE a.`username` = 'admin'
  AND (a.`role_id` = 0 OR a.`role_id` IS NULL);

COMMIT;
