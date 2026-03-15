-- 管理员操作日志表
CREATE TABLE IF NOT EXISTS `tc_admin_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
    `admin_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '管理员名称',
    `action` VARCHAR(50) NOT NULL COMMENT '操作类型',
    `module` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作模块',
    `target_id` INT UNSIGNED DEFAULT 0 COMMENT '操作目标ID',
    `target_type` VARCHAR(50) DEFAULT '' COMMENT '操作目标类型',
    `detail` TEXT COMMENT '操作详情',
    `before_data` JSON NULL COMMENT '操作前数据',
    `after_data` JSON NULL COMMENT '操作后数据',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '操作IP',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User-Agent',
    `request_url` VARCHAR(500) DEFAULT '' COMMENT '请求URL',
    `request_method` VARCHAR(10) DEFAULT '' COMMENT '请求方法',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 1成功 0失败',
    `error_msg` VARCHAR(500) DEFAULT '' COMMENT '错误信息',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_module` (`module`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员操作日志表';

-- 管理员权限表
CREATE TABLE IF NOT EXISTS `tc_admin_permission` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '权限名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '权限标识',
    `module` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '所属模块',
    `description` VARCHAR(255) DEFAULT '' COMMENT '权限描述',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_code` (`code`),
    INDEX `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员权限表';

-- 管理员角色表
CREATE TABLE IF NOT EXISTS `tc_admin_role` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '角色标识',
    `description` VARCHAR(255) DEFAULT '' COMMENT '角色描述',
    `is_super` TINYINT DEFAULT 0 COMMENT '是否超级管理员',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色表';

-- 管理员角色权限关联表
CREATE TABLE IF NOT EXISTS `tc_admin_role_permission` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `permission_id` INT UNSIGNED NOT NULL COMMENT '权限ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uniq_role_permission` (`role_id`, `permission_id`),
    INDEX `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限关联表';

-- 管理员角色关联表
CREATE TABLE IF NOT EXISTS `tc_admin_user_role` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
    `role_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uniq_admin_role` (`admin_id`, `role_id`),
    INDEX `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员角色关联表';

-- 插入默认权限
INSERT INTO `tc_admin_permission` (`name`, `code`, `module`, `description`) VALUES
('用户查看', 'user_view', 'user', '查看用户列表和信息'),
('用户编辑', 'user_edit', 'user', '编辑用户信息'),
('积分调整', 'points_adjust', 'points', '调整用户积分'),
('积分查看', 'points_view', 'points', '查看积分记录'),
('配置管理', 'config_manage', 'config', '管理系统配置'),
('日志查看', 'log_view', 'log', '查看操作日志'),
('数据统计', 'stats_view', 'stats', '查看统计数据'),
('内容管理', 'content_manage', 'content', '管理内容数据');

-- 插入默认角色
INSERT INTO `tc_admin_role` (`name`, `code`, `description`, `is_super`) VALUES
('超级管理员', 'super_admin', '拥有所有权限', 1),
('普通管理员', 'normal_admin', '拥有常规管理权限', 0),
('运营人员', 'operator', '仅限查看和部分编辑权限', 0);

-- 为普通管理员角色分配权限
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT 2, id FROM `tc_admin_permission` WHERE code IN ('user_view', 'points_view', 'stats_view');

-- 为运营人员角色分配权限
INSERT INTO `tc_admin_role_permission` (`role_id`, `permission_id`)
SELECT 3, id FROM `tc_admin_permission` WHERE code IN ('user_view', 'stats_view');
