-- =============================================================
-- 太初命理网站 - 全量修复脚本 (All-in-One Repair)
-- 生成时间: 2026-03-19
-- 适用场景: 全新 phpStudy 本地环境、或 Docker 迁移后的修复补丁
--
-- 包含内容（按执行顺序）：
--   [1] 补建缺失表        - tc_admin / 反作弊 / 通知 / 统计 / 业务新表
--   [2] 神煞乱码回填      - tc_shensha 显示修复
--   [3] 管理员角色绑定    - 修复全局 403（tc_admin_user_role 种子缺失）
--   [4] 积分记录列补齐    - tc_points_record 缺 points/action/related_id/description
--
-- 幂等设计：ON DUPLICATE KEY / IF NOT EXISTS / PREPARE-EXECUTE
-- 重复执行安全，可放心多次运行。
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
USE taichu;

-- ============================================================
-- [1/4] repair_missing_tables_v2.sql
--       补建缺失表：tc_admin / 反作弊 / 知识库 / 通知 / 统计 / 业务新表
-- ============================================================

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反作弊风险事件表';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反作弊规则表';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备指纹表';

-- 3. 知识库文章系统
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章分类表';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章表';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='站内通知表';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户通知设置表';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户推送设备表';

-- 5. 每日统计表
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='网站每日统计表';

-- 6. 业务新表：配置 / 塔罗 / 文件 / 六爻
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗牌表';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗牌阵表';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗占卜记录表';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='六爻占卜记录表';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件上传记录表';

-- system_config 默认数据（合婚积分配置）
INSERT IGNORE INTO `system_config` (`config_key`, `config_value`, `config_type`, `description`, `category`, `is_editable`, `sort_order`)
VALUES
('feature_hehun_enabled',  '1',  'bool', '八字合婚功能开关',       'feature',     1, 7),
('points_cost_hehun',      '80', 'int',  '八字合婚基础积分消耗',   'points_cost', 1, 7),
('points_cost_hehun_export','30', 'int', '八字合婚导出报告积分',   'points_cost', 1, 8),
('vip_unlock_hehun',       '1',  'bool', 'VIP是否解锁合婚功能',    'vip',         1, 7),
('new_user_offer_enabled',  '1', 'bool', '新用户优惠开关',          'new_user',    1, 1),
('new_user_discount',      '50', 'int',  '新用户折扣(%)',            'new_user',    1, 2);

SELECT '✅ [1/4] 缺失表补建完成' AS step_result;

-- ============================================================
-- [2/4] 20260318_fix_shensha_display_encoding.sql
--       神煞历史数据乱码回填（description / effect / check_rule）
-- ============================================================

UPDATE `tc_shensha`
SET
    `description` = CASE
        WHEN `sort` = 1  AND `type` = 'daji' AND `category` = 'guiren'  THEN '最吉之神，命中逢之，遇事有人帮，遇危难有人救'
        WHEN `sort` = 2  AND `type` = 'ji'   AND `category` = 'xueye'   THEN '主聪明好学，利文途考学'
        WHEN `sort` = 3  AND `type` = 'ji'   AND `category` = 'guiren'  THEN '主人聪明好学，喜神秘文化'
        WHEN `sort` = 4  AND `type` = 'daji' AND `category` = 'guiren'  THEN '天地德秀之气，逢凶化吉之神'
        WHEN `sort` = 5  AND `type` = 'daji' AND `category` = 'guiren'  THEN '乃太阴之德，功能与天德略同而稍逊'
        WHEN `sort` = 6  AND `type` = 'ji'   AND `category` = 'guiren'  THEN '主人一生福禄无缺，格局配合得当，必然多福多寿'
        WHEN `sort` = 7  AND `type` = 'ping' AND `category` = 'ganqing' THEN '主人漂亮多情，风流潇洒'
        WHEN `sort` = 8  AND `type` = 'xiong'AND `category` = 'jiankang'THEN '司刑之星，性情刚强'
        WHEN `sort` = 9  AND `type` = 'xiong'AND `category` = 'caiyun'  THEN '主破财、阻碍'
        WHEN `sort` = 10 AND `type` = 'xiong'AND `category` = 'ganqing' THEN '主孤独，不利婚姻'
        WHEN `sort` = 11 AND `type` = 'xiong'AND `category` = 'ganqing' THEN '主孤独，不利婚姻'
        WHEN `sort` = 12 AND `type` = 'xiong'AND `category` = 'ganqing' THEN '主婚姻不顺'
        WHEN `sort` = 13 AND `type` = 'xiong'AND `category` = 'caiyun'  THEN '主不善理财，花钱大手大脚'
        WHEN `sort` = 14 AND `type` = 'ji'   AND `category` = 'caiyun'  THEN '主富贵，聪明富贵'
        WHEN `sort` = 15 AND `type` = 'ping' AND `category` = 'guiren'  THEN '主聪明好学，喜艺术、宗教'
        ELSE `description`
    END,
    `effect` = CASE
        WHEN `sort` = 1  AND `type` = 'daji' AND `category` = 'guiren'  THEN '遇难成祥，逢凶化吉，人缘极佳，易得他人帮助'
        WHEN `sort` = 2  AND `type` = 'ji'   AND `category` = 'xueye'   THEN '聪明过人，学业有成，考试顺利，利于文职'
        WHEN `sort` = 3  AND `type` = 'ji'   AND `category` = 'guiren'  THEN '悟性高，对命理、宗教、玄学有兴趣，逢凶化吉'
        WHEN `sort` = 4  AND `type` = 'daji' AND `category` = 'guiren'  THEN '一生安逸，不犯刑律，不遇凶祸，福气好'
        WHEN `sort` = 5  AND `type` = 'daji' AND `category` = 'guiren'  THEN '逢凶化吉，灾少福多，一生少病痛'
        WHEN `sort` = 6  AND `type` = 'ji'   AND `category` = 'guiren'  THEN '一生福禄无缺，享福深厚，平安幸福'
        WHEN `sort` = 7  AND `type` = 'ping' AND `category` = 'ganqing' THEN '人缘好，异性缘佳，感情丰富，但可能感情复杂'
        WHEN `sort` = 8  AND `type` = 'xiong'AND `category` = 'jiankang'THEN '性格刚烈，易有刀伤手术，但也代表能力强'
        WHEN `sort` = 9  AND `type` = 'xiong'AND `category` = 'caiyun'  THEN '破财、阻碍、是非、意外'
        WHEN `sort` = 10 AND `type` = 'xiong'AND `category` = 'ganqing' THEN '孤独，少依靠，婚姻不顺，与亲人缘薄'
        WHEN `sort` = 11 AND `type` = 'xiong'AND `category` = 'ganqing' THEN '孤独，婚姻不顺，女命尤其注意'
        WHEN `sort` = 12 AND `type` = 'xiong'AND `category` = 'ganqing' THEN '婚姻不利，夫妻不和，男克妻女克夫'
        WHEN `sort` = 13 AND `type` = 'xiong'AND `category` = 'caiyun'  THEN '不善理财，花钱如流水，难以积蓄'
        WHEN `sort` = 14 AND `type` = 'ji'   AND `category` = 'caiyun'  THEN '聪明富贵，性柔貌愿，举止温和'
        WHEN `sort` = 15 AND `type` = 'ping' AND `category` = 'guiren'  THEN '聪明好学，喜艺术、玄学、宗教，有出世思想'
        ELSE `effect`
    END,
    `check_rule` = CASE
        WHEN `sort` = 1  AND `type` = 'daji' AND `category` = 'guiren'  THEN '甲戊见牛羊，乙己鼠猴乡，丙丁猪鸡位，壬癸兔蛇藏，庚辛逢虎马，此是贵人方'
        WHEN `sort` = 2  AND `type` = 'ji'   AND `category` = 'xueye'   THEN '甲乙巳午报君知，丙戊申宫丁己鸡，庚猪辛鼠壬逢虎，癸人见卯入云梯'
        WHEN `sort` = 3  AND `type` = 'ji'   AND `category` = 'guiren'  THEN '甲乙生人子午中，丙丁鸡兔定亨通，戊己两干临四季，庚辛寅亥禄丰隆，壬癸巳申偏喜美'
        WHEN `sort` = 4  AND `type` = 'daji' AND `category` = 'guiren'  THEN '正丁二坤宫，三壬四辛同，五乾六甲上，七癸八艮逢，九丙十居乙，子巽丑庚中'
        WHEN `sort` = 5  AND `type` = 'daji' AND `category` = 'guiren'  THEN '寅午戌月在丙，申子辰月在壬，亥卯未月在甲，巳酉丑月在庚'
        WHEN `sort` = 6  AND `type` = 'ji'   AND `category` = 'guiren'  THEN '甲丙相邀入虎乡，更游鼠穴最高强，戊猴己未丁宜亥，乙癸逢牛卯禄昌，庚赶马头辛到巳，壬骑龙背喜非常'
        WHEN `sort` = 7  AND `type` = 'ping' AND `category` = 'ganqing' THEN '申子辰在酉，巳酉丑在午，亥卯未在子，寅午戌在卯'
        WHEN `sort` = 8  AND `type` = 'xiong'AND `category` = 'jiankang'THEN '甲刃在卯，乙刃在寅，丙戊刃在午，丁己刃在巳，庚刃在酉，辛刃在申，壬刃在子，癸刃在亥'
        WHEN `sort` = 9  AND `type` = 'xiong'AND `category` = 'caiyun'  THEN '申子辰见巳，亥卯未见申，寅午戌见亥，巳酉丑见寅'
        WHEN `sort` = 10 AND `type` = 'xiong'AND `category` = 'ganqing' THEN '亥子丑人，见寅为孤辰，见戌为寡宿'
        WHEN `sort` = 11 AND `type` = 'xiong'AND `category` = 'ganqing' THEN '亥子丑人，见戌为寡宿，见寅为孤辰'
        WHEN `sort` = 12 AND `type` = 'xiong'AND `category` = 'ganqing' THEN '丙子、丁丑、戊寅、辛卯、壬辰、癸巳、丙午、丁未、戊申、辛酉、壬戌、癸亥'
        WHEN `sort` = 13 AND `type` = 'xiong'AND `category` = 'caiyun'  THEN '甲辰、乙巳、丙申、丁亥、戊戌、己丑、庚辰、辛巳、壬申、癸亥'
        WHEN `sort` = 14 AND `type` = 'ji'   AND `category` = 'caiyun'  THEN '甲龙乙蛇丙戊羊，丁己猴歌庚犬方，辛猪壬牛癸逢虎'
        WHEN `sort` = 15 AND `type` = 'ping' AND `category` = 'guiren'  THEN '寅午戌见戌，巳酉丑见丑，申子辰见辰，亥卯未见未'
        ELSE `check_rule`
    END,
    `updated_at` = NOW()
WHERE (
    (`description` <> '' AND `description` NOT REGEXP '[一-龥]')
    OR (`effect`   <> '' AND `effect`      NOT REGEXP '[一-龥]')
    OR (`check_rule`<>'' AND `check_rule`  NOT REGEXP '[一-龥]')
)
AND (
    (`sort` = 1  AND `type` = 'daji' AND `category` = 'guiren')
    OR (`sort` = 2  AND `type` = 'ji'   AND `category` = 'xueye')
    OR (`sort` = 3  AND `type` = 'ji'   AND `category` = 'guiren')
    OR (`sort` = 4  AND `type` = 'daji' AND `category` = 'guiren')
    OR (`sort` = 5  AND `type` = 'daji' AND `category` = 'guiren')
    OR (`sort` = 6  AND `type` = 'ji'   AND `category` = 'guiren')
    OR (`sort` = 7  AND `type` = 'ping' AND `category` = 'ganqing')
    OR (`sort` = 8  AND `type` = 'xiong'AND `category` = 'jiankang')
    OR (`sort` = 9  AND `type` = 'xiong'AND `category` = 'caiyun')
    OR (`sort` = 10 AND `type` = 'xiong'AND `category` = 'ganqing')
    OR (`sort` = 11 AND `type` = 'xiong'AND `category` = 'ganqing')
    OR (`sort` = 12 AND `type` = 'xiong'AND `category` = 'ganqing')
    OR (`sort` = 13 AND `type` = 'xiong'AND `category` = 'caiyun')
    OR (`sort` = 14 AND `type` = 'ji'   AND `category` = 'caiyun')
    OR (`sort` = 15 AND `type` = 'ping' AND `category` = 'guiren')
);

SELECT '✅ [2/4] 神煞乱码回填完成' AS step_result;

-- ============================================================
-- [3/4] 20260319_fix_admin_role_binding.sql
--       管理员角色绑定修复（修复全局 403）
-- ============================================================

-- 1. 确保 super_admin 角色存在且 is_super = 1
INSERT INTO `tc_admin_role` (`name`, `code`, `description`, `is_super`, `created_at`)
VALUES ('超级管理员', 'super_admin', '拥有所有后台权限', 1, NOW())
ON DUPLICATE KEY UPDATE
    `is_super`    = 1,
    `name`        = VALUES(`name`),
    `description` = VALUES(`description`);

-- 2. 确保 tc_admin_user_role 有 admin <-> super_admin 绑定行
INSERT IGNORE INTO `tc_admin_user_role` (`admin_id`, `role_id`, `created_at`)
SELECT a.`id`, r.`id`, NOW()
FROM `tc_admin` a
JOIN `tc_admin_role` r ON r.`code` = 'super_admin'
WHERE a.`username` = 'admin';

-- 3. 同步 tc_admin.role_id 字段（兜底路径）
UPDATE `tc_admin` a
JOIN `tc_admin_role` r ON r.`code` = 'super_admin'
SET a.`role_id` = r.`id`
WHERE a.`username` = 'admin'
  AND (a.`role_id` IS NULL OR a.`role_id` = 0);

SELECT '✅ [3/4] 管理员角色绑定修复完成' AS step_result;

-- ============================================================
-- [4/4] 20260319_fix_points_record_columns.sql
--       tc_points_record 缺失列补齐（兼容 MySQL 5.7）
-- ============================================================

-- action 列
SET @col_action := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_points_record' AND COLUMN_NAME = 'action');
SET @sql_action := IF(@col_action = 0,
    "ALTER TABLE `tc_points_record` ADD COLUMN `action` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '操作名称' AFTER `user_id`",
    'SELECT 1');
PREPARE s FROM @sql_action; EXECUTE s; DEALLOCATE PREPARE s;

-- points 列（有符号变动值，正增负减）
SET @col_points := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_points_record' AND COLUMN_NAME = 'points');
SET @sql_points := IF(@col_points = 0,
    "ALTER TABLE `tc_points_record` ADD COLUMN `points` INT NOT NULL DEFAULT 0 COMMENT '有符号积分变动值（正增负减）' AFTER `action`",
    'SELECT 1');
PREPARE s FROM @sql_points; EXECUTE s; DEALLOCATE PREPARE s;

-- related_id 列
SET @col_related := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_points_record' AND COLUMN_NAME = 'related_id');
SET @sql_related := IF(@col_related = 0,
    "ALTER TABLE `tc_points_record` ADD COLUMN `related_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联ID' AFTER `type`",
    'SELECT 1');
PREPARE s FROM @sql_related; EXECUTE s; DEALLOCATE PREPARE s;

-- description 列
SET @col_desc := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tc_points_record' AND COLUMN_NAME = 'description');
SET @sql_desc := IF(@col_desc = 0,
    "ALTER TABLE `tc_points_record` ADD COLUMN `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '简要说明' AFTER `reason`",
    'SELECT 1');
PREPARE s FROM @sql_desc; EXECUTE s; DEALLOCATE PREPARE s;

-- 回填 points（从 type+amount 推导有符号值，仅对 points=0 的旧记录）
UPDATE `tc_points_record`
SET `points` = CASE
    WHEN `type` IN ('reduce','consume','deduct','expense','cost','exchange','redeem') THEN -ABS(COALESCE(`amount`, 0))
    ELSE ABS(COALESCE(`amount`, 0))
END
WHERE `points` = 0 AND `amount` > 0;

-- 回填 action（从 reason/remark 取第一个非空值）
UPDATE `tc_points_record`
SET `action` = COALESCE(NULLIF(TRIM(`reason`), ''), NULLIF(TRIM(`remark`), ''), '积分变动')
WHERE `action` = '' OR `action` IS NULL;

-- 回填 related_id（从 source_id 兜底）
UPDATE `tc_points_record`
SET `related_id` = COALESCE(`source_id`, 0)
WHERE `related_id` = 0 AND `source_id` > 0;

SELECT '✅ [4/4] tc_points_record 列修复完成' AS step_result;

-- ============================================================
-- 收尾
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

SELECT '🎉 all_repairs.sql 全部执行完成' AS final_result;

