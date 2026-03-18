-- =============================================================
-- 太初命理网站 - 缺失表修复脚本 (Repair Missing Tables)
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
USE taichu;

-- 1. 管理员主表 (tc_admin)
CREATE TABLE IF NOT EXISTS `tc_admin` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(255) NOT NULL COMMENT '密码哈希',
    `nickname` VARCHAR(50) DEFAULT '' COMMENT '昵称',
    `email` VARCHAR(100) DEFAULT '' COMMENT '邮箱',
    `phone` VARCHAR(20) DEFAULT '' COMMENT '手机号',
    `avatar` VARCHAR(500) DEFAULT '' COMMENT '头像',
    `role_id` INT UNSIGNED DEFAULT 0 COMMENT '角色ID',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间',
    `last_login_ip` VARCHAR(45) DEFAULT '' COMMENT '最后登录IP',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_username` (`username`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- 插入默认管理员 (admin / admin123)
INSERT IGNORE INTO `tc_admin` (`username`, `password`, `nickname`, `status`)
VALUES ('admin', '$2a$10$69ekdLT1xe/Niyazb/kBSegJPAx0uJf6uhq5mz.LfZ.2rJ5YtUjoC', '系统管理员', 1);

-- 2. 反作弊相关表
CREATE TABLE IF NOT EXISTS `tc_anti_cheat_event` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '事件类型',
    `level` VARCHAR(20) DEFAULT 'medium' COMMENT '风险等级',
    `detail` JSON NULL COMMENT '详细信息',
    `ip` VARCHAR(45) DEFAULT '' COMMENT 'IP地址',
    `device_id` VARCHAR(100) DEFAULT '' COMMENT '设备ID',
    `status` TINYINT DEFAULT 0 COMMENT '状态',
    `handler_id` INT UNSIGNED DEFAULT 0 COMMENT '处理人ID',
    `handle_remark` VARCHAR(255) DEFAULT '' COMMENT '处理备注',
    `handle_at` DATETIME NULL COMMENT '处理时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_rule` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '规则名称',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '规则代码',
    `type` VARCHAR(50) NOT NULL COMMENT '规则类型',
    `config` JSON NULL COMMENT '配置',
    `action` VARCHAR(50) DEFAULT 'log' COMMENT '动作',
    `status` TINYINT DEFAULT 1 COMMENT '状态',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tc_anti_cheat_device` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `device_id` VARCHAR(100) NOT NULL UNIQUE COMMENT '设备ID',
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '用户ID',
    `platform` VARCHAR(20) DEFAULT '' COMMENT '平台',
    `info` JSON NULL COMMENT '设备信息',
    `is_blocked` TINYINT DEFAULT 0 COMMENT '是否拉黑',
    `block_reason` VARCHAR(255) DEFAULT '' COMMENT '拉黑原因',
    `last_active_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 知识库文章系统 (兼容旧表 tc_knowledge_*)
CREATE TABLE IF NOT EXISTS `tc_article_category` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) NOT NULL DEFAULT '',
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tc_article` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(160) NOT NULL,
    `summary` VARCHAR(500) NOT NULL DEFAULT '',
    `content` LONGTEXT NOT NULL,
    `thumbnail` VARCHAR(500) NOT NULL DEFAULT '',
    `status` TINYINT NOT NULL DEFAULT 0,
    `is_hot` TINYINT(1) NOT NULL DEFAULT 0,
    `author_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `author_name` VARCHAR(80) NOT NULL DEFAULT '',
    `published_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_category` (`category_id`),
    FULLTEXT KEY `ft_content` (`title`, `summary`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 推送通知系统
CREATE TABLE IF NOT EXISTS `tc_notification` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `content` TEXT NULL,
    `data` JSON NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tc_notification_setting` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `daily_fortune` TINYINT(1) NOT NULL DEFAULT 1,
    `system_notice` TINYINT(1) NOT NULL DEFAULT 1,
    `activity` TINYINT(1) NOT NULL DEFAULT 1,
    `recharge` TINYINT(1) NOT NULL DEFAULT 1,
    `points_change` TINYINT(1) NOT NULL DEFAULT 1,
    `push_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `sound_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `vibration_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `quiet_hours_start` CHAR(5) NOT NULL DEFAULT '22:00',
    `quiet_hours_end` CHAR(5) NOT NULL DEFAULT '08:00',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tc_push_device` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `platform` VARCHAR(20) NOT NULL,
    `device_token` VARCHAR(500) NOT NULL,
    `device_id` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_active_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_device` (`device_id`),
    KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 统计表
CREATE TABLE IF NOT EXISTS `site_daily_stats` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `stat_date` DATE NOT NULL COMMENT '统计日期',
    `new_users` INT NOT NULL DEFAULT 0,
    `active_users` INT NOT NULL DEFAULT 0,
    `total_users` INT NOT NULL DEFAULT 0,
    `points_given` INT NOT NULL DEFAULT 0,
    `points_consumed` INT NOT NULL DEFAULT 0,
    `points_balance` INT NOT NULL DEFAULT 0,
    `bazi_count` INT NOT NULL DEFAULT 0,
    `tarot_count` INT NOT NULL DEFAULT 0,
    `liuyao_count` INT NOT NULL DEFAULT 0,
    `hehun_count` INT NOT NULL DEFAULT 0,
    `daily_fortune_count` INT NOT NULL DEFAULT 0,
    `order_count` INT NOT NULL DEFAULT 0,
    `order_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
    `paid_count` INT NOT NULL DEFAULT 0,
    `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
    `refund_count` INT NOT NULL DEFAULT 0,
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
    `pv_count` INT NOT NULL DEFAULT 0,
    `uv_count` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 缺失的业务表 (New Models)
CREATE TABLE IF NOT EXISTS `system_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(100) NOT NULL COMMENT '配置键',
    `config_value` TEXT COMMENT '配置值',
    `config_type` VARCHAR(20) NOT NULL DEFAULT 'string' COMMENT '值类型',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '配置说明',
    `category` VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT '分类',
    `is_editable` TINYINT NOT NULL DEFAULT 1 COMMENT '是否允许后台编辑',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_config_key` (`config_key`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tarot_cards` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) NOT NULL DEFAULT '',
    `number` INT NOT NULL DEFAULT 0,
    `suit` VARCHAR(20) NOT NULL DEFAULT '',
    `is_major` TINYINT NOT NULL DEFAULT 0,
    `image` VARCHAR(500) NOT NULL DEFAULT '',
    `image_reversed` VARCHAR(500) NOT NULL DEFAULT '',
    `keywords` JSON NULL,
    `keywords_reversed` JSON NULL,
    `meaning_general` TEXT NULL,
    `meaning_general_reversed` TEXT NULL,
    `meaning_love` TEXT NULL,
    `meaning_love_reversed` TEXT NULL,
    `meaning_career` TEXT NULL,
    `meaning_career_reversed` TEXT NULL,
    `meaning_health` TEXT NULL,
    `meaning_health_reversed` TEXT NULL,
    `meaning_wealth` TEXT NULL,
    `meaning_wealth_reversed` TEXT NULL,
    `description` TEXT NULL,
    `element` VARCHAR(20) NOT NULL DEFAULT '',
    `astrological` VARCHAR(50) NOT NULL DEFAULT '',
    `numerology` INT NOT NULL DEFAULT 0,
    `is_enabled` TINYINT NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_number_suit` (`number`, `suit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tarot_spreads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `card_count` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `positions` JSON NULL,
    `suitable_for` JSON NULL,
    `difficulty` VARCHAR(20) NOT NULL DEFAULT 'beginner',
    `is_free` TINYINT NOT NULL DEFAULT 0,
    `points_cost` INT NOT NULL DEFAULT 0,
    `is_enabled` TINYINT NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tc_tarot_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `spread_type` VARCHAR(50) NOT NULL DEFAULT 'single',
    `topic` VARCHAR(100) NOT NULL DEFAULT '',
    `question` VARCHAR(500) NOT NULL DEFAULT '',
    `cards` JSON NULL,
    `interpretation` TEXT NULL,
    `ai_analysis` TEXT NULL,
    `points_used` INT NOT NULL DEFAULT 0,
    `is_free` TINYINT NOT NULL DEFAULT 0,
    `is_public` TINYINT NOT NULL DEFAULT 0,
    `share_code` VARCHAR(50) NOT NULL DEFAULT '',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `client_ip` VARCHAR(45) NOT NULL DEFAULT '',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tc_liuyao_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `question` VARCHAR(500) NOT NULL DEFAULT '',
    `method` VARCHAR(30) NOT NULL DEFAULT 'coin',
    `hexagram_original` JSON NULL,
    `hexagram_changed` JSON NULL,
    `hexagram_mutual` JSON NULL,
    `lines` JSON NULL,
    `moving_lines` JSON NULL,
    `analysis` TEXT NULL,
    `ai_analysis` TEXT NULL,
    `points_used` INT NOT NULL DEFAULT 0,
    `is_free` TINYINT NOT NULL DEFAULT 0,
    `is_public` TINYINT NOT NULL DEFAULT 0,
    `share_code` VARCHAR(50) NOT NULL DEFAULT '',
    `client_ip` VARCHAR(45) NOT NULL DEFAULT '',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `upload_files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `original_name` VARCHAR(255) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_url` VARCHAR(500) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
    `mime_type` VARCHAR(100) NOT NULL DEFAULT '',
    `extension` VARCHAR(20) NOT NULL DEFAULT '',
    `storage` VARCHAR(30) NOT NULL DEFAULT 'local',
    `category` VARCHAR(50) NOT NULL DEFAULT 'common',
    `uploaded_by` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_deleted` TINYINT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- 补回 SystemConfig 默认数据 (合婚)
INSERT IGNORE INTO `system_config` (`config_key`, `config_value`, `config_type`, `description`, `category`, `is_editable`, `sort_order`)
VALUES
('feature_hehun_enabled', '1', 'bool', '八字合婚功能开关', 'feature', 1, 7),
('points_cost_hehun', '80', 'int', '八字合婚基础积分消耗', 'points_cost', 1, 7),
('points_cost_hehun_export', '30', 'int', '八字合婚导出报告积分', 'points_cost', 1, 8),
('vip_unlock_hehun', '1', 'bool', 'VIP是否解锁合婚功能', 'vip', 1, 7),
('new_user_offer_enabled', '1', 'bool', '新用户优惠开关', 'new_user', 1, 1),
('new_user_discount', '50', 'int', '新用户折扣(%)', 'new_user', 1, 2);

SELECT '✅ 修复完成：tc_admin, 反作弊, 知识库, 通知, 统计, 业务新表 已创建' AS repair_result;

