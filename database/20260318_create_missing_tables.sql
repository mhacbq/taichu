-- =============================================================
-- 太初命理网站 - 缺失表补充迁移脚本
-- 创建时间：2026-03-18
-- 用途：补齐代码中引用但数据库中尚未创建的所有表
--       支持重复执行（IF NOT EXISTS / ON DUPLICATE KEY UPDATE）
-- =============================================================

SET NAMES utf8mb4;
USE taichu;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================
-- 1. 积分历史记录表（points_history）
--    MD Report 4.1 中定义的必需表
-- =============================================================
CREATE TABLE IF NOT EXISTS `points_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL COMMENT '类型 add/reduce',
    `points` INT NOT NULL COMMENT '变动积分',
    `balance` INT NOT NULL COMMENT '变动后余额',
    `action` VARCHAR(100) NOT NULL COMMENT '动作说明',
    `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分历史记录表';

-- =============================================================
-- 2. 积分兑换记录表（points_exchange）
--    MD Report 4.2 中定义的必需表
-- =============================================================
CREATE TABLE IF NOT EXISTS `points_exchange` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `product_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
    `product_name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `points` INT NOT NULL COMMENT '消耗积分',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0待处理 1已完成 2已取消',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分兑换记录表';

-- =============================================================
-- 3. 签到记录表（checkin_record）
--    MD Report 4.4 中定义的必需表（不同于 tc_checkin_log）
-- =============================================================
CREATE TABLE IF NOT EXISTS `checkin_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '签到日期',
    `consecutive_days` INT NOT NULL DEFAULT 1 COMMENT '连续签到天数',
    `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_date` (`user_id`, `date`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到记录表';

-- =============================================================
-- 4. 合婚记录表（hehun_records）
--    模型：HehunRecord，兼容旧表名 tc_hehun_record
-- =============================================================
CREATE TABLE IF NOT EXISTS `hehun_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `male_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '男方姓名',
    `female_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '女方姓名',
    `male_birth_date` DATE NOT NULL COMMENT '男方出生日期',
    `male_birth_time` TIME NOT NULL DEFAULT '00:00:00' COMMENT '男方出生时间',
    `female_birth_date` DATE NOT NULL COMMENT '女方出生日期',
    `female_birth_time` TIME NOT NULL DEFAULT '00:00:00' COMMENT '女方出生时间',
    `male_bazi` JSON NULL COMMENT '男方八字JSON',
    `female_bazi` JSON NULL COMMENT '女方八字JSON',
    `bazi_match` JSON NULL COMMENT '八字合婚分析JSON（兼容旧字段）',
    `wuxing_match` JSON NULL COMMENT '五行匹配分析JSON',
    `result` JSON NULL COMMENT '完整合婚结果JSON',
    `analysis` TEXT NULL COMMENT '合婚文字分析（兼容旧结构）',
    `score` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '合婚评分 0-100',
    `level` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '评级: excellent/good/medium/fair/poor',
    `ai_analysis` JSON NULL COMMENT 'AI深度分析JSON',
    `is_ai_analysis` TINYINT NOT NULL DEFAULT 0 COMMENT '是否含AI分析 0否 1是',
    `points_cost` INT NOT NULL DEFAULT 0 COMMENT '消耗积分数（兼容字段名 points_used）',
    `is_premium` TINYINT NOT NULL DEFAULT 0 COMMENT '是否为完整付费报告',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='八字合婚记录表';

-- =============================================================
-- 2. 系统配置表（system_config）
--    模型：SystemConfig，兼容旧表名 tc_system_config
-- =============================================================
CREATE TABLE IF NOT EXISTS `system_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(100) NOT NULL COMMENT '配置键',
    `config_value` TEXT NOT NULL DEFAULT '' COMMENT '配置值',
    `config_type` VARCHAR(20) NOT NULL DEFAULT 'string' COMMENT '值类型: string/int/float/bool/json',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '配置说明',
    `category` VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT '分类',
    `is_editable` TINYINT NOT NULL DEFAULT 1 COMMENT '是否允许后台编辑 0否 1是',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_config_key` (`config_key`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- 写入核心默认配置（幂等）
INSERT INTO `system_config` (`config_key`, `config_value`, `config_type`, `description`, `category`, `is_editable`, `sort_order`)
VALUES
-- 积分消耗配置
('points_cost_bazi',         '30',   'int',    '八字排盘基础积分消耗',       'points_cost', 1, 1),
('points_cost_bazi_ai',      '50',   'int',    '八字AI深度解盘积分消耗',     'points_cost', 1, 2),
('points_cost_tarot',        '20',   'int',    '塔罗占卜基础积分消耗',       'points_cost', 1, 3),
('points_cost_tarot_ai',     '40',   'int',    '塔罗AI解读积分消耗',         'points_cost', 1, 4),
('points_cost_liuyao',       '25',   'int',    '六爻占卜积分消耗',           'points_cost', 1, 5),
('points_cost_hehun',        '80',   'int',    '八字合婚基础积分消耗',       'points_cost', 1, 6),
('points_cost_hehun_export', '30',   'int',    '合婚导出报告积分消耗',       'points_cost', 1, 7),
-- 新用户首测优惠
('points_free_bazi_first',   '1',    'bool',   '新用户首次八字是否免费',     'new_user',    1, 1),
('points_free_tarot_first',  '1',    'bool',   '新用户首次塔罗是否免费',     'new_user',    1, 2),
('new_user_offer_enabled',   '1',    'bool',   '新用户优惠开关',             'new_user',    1, 3),
('new_user_discount',        '50',   'int',    '新用户折扣（百分比）',       'new_user',    1, 4),
-- 功能开关
('feature_bazi_enabled',     '1',    'bool',   '八字功能开关',               'feature',     1, 1),
('feature_tarot_enabled',    '1',    'bool',   '塔罗功能开关',               'feature',     1, 2),
('feature_liuyao_enabled',   '1',    'bool',   '六爻功能开关',               'feature',     1, 3),
('feature_hehun_enabled',    '1',    'bool',   '合婚功能开关',               'feature',     1, 4),
('feature_daily_enabled',    '1',    'bool',   '每日运势功能开关',           'feature',     1, 5),
-- VIP配置
('vip_price_month',          '68',   'int',    'VIP月度价格（元）',          'vip',         1, 1),
('vip_price_quarter',        '168',  'int',    'VIP季度价格（元）',          'vip',         1, 2),
('vip_price_year',           '498',  'int',    'VIP年度价格（元）',          'vip',         1, 3),
('vip_unlock_hehun',         '1',    'bool',   'VIP是否解锁合婚功能',        'vip',         1, 4),
-- 积分充值档位（JSON）
('points_recharge_tiers',    '[{"points":100,"price":10},{"points":300,"price":28},{"points":600,"price":50},{"points":1000,"price":78}]',
                              'json', '积分充值档位配置',                   'recharge',    1, 1),
-- 站点基础信息
('site_name',                '太初命理',  'string', '站点名称',              'site',        1, 1),
('site_domain',              'taichu.chat', 'string', '站点域名',           'site',        0, 2),
('site_icp',                 '',          'string', 'ICP备案号',            'site',        1, 3),
('site_copyright',           '© 2026 太初命理', 'string', '版权信息',       'site',        1, 4),
-- 客服与联系方式
('contact_wechat',           '',    'string',   '客服微信号',               'contact',     1, 1),
('contact_email',            '',    'string',   '客服邮箱',                 'contact',     1, 2)
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`),
    `category` = VALUES(`category`),
    `is_editable` = VALUES(`is_editable`),
    `sort_order` = VALUES(`sort_order`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =============================================================
-- 3. 塔罗牌表（tarot_cards）
--    模型：TarotCard，兼容旧表名 tc_tarot_card
-- =============================================================
CREATE TABLE IF NOT EXISTS `tarot_cards` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '牌名（中文）',
    `name_en` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '牌名（英文）',
    `number` INT NOT NULL DEFAULT 0 COMMENT '牌号（大阿卡纳 0-21，小阿卡纳 1-14）',
    `suit` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '牌组: wands/cups/swords/pentacles/major',
    `is_major` TINYINT NOT NULL DEFAULT 0 COMMENT '是否大阿卡纳 0否 1是',
    `image` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '牌面图片URL',
    `image_reversed` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '逆位图片URL（可空）',
    `keywords` JSON NULL COMMENT '关键词列表',
    `keywords_reversed` JSON NULL COMMENT '逆位关键词列表',
    `meaning_general` TEXT NULL COMMENT '通用正位含义',
    `meaning_general_reversed` TEXT NULL COMMENT '通用逆位含义',
    `meaning_love` TEXT NULL COMMENT '感情正位',
    `meaning_love_reversed` TEXT NULL COMMENT '感情逆位',
    `meaning_career` TEXT NULL COMMENT '事业正位',
    `meaning_career_reversed` TEXT NULL COMMENT '事业逆位',
    `meaning_health` TEXT NULL COMMENT '健康正位',
    `meaning_health_reversed` TEXT NULL COMMENT '健康逆位',
    `meaning_wealth` TEXT NULL COMMENT '财运正位',
    `meaning_wealth_reversed` TEXT NULL COMMENT '财运逆位',
    `description` TEXT NULL COMMENT '牌义详细描述',
    `element` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '对应元素: fire/water/air/earth',
    `astrological` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '对应星座/星体',
    `numerology` INT NOT NULL DEFAULT 0 COMMENT '数字学对应',
    `is_enabled` TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_number_suit` (`number`, `suit`),
    KEY `idx_suit` (`suit`),
    KEY `idx_is_major` (`is_major`),
    KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗牌表';

-- =============================================================
-- 4. 塔罗牌阵表（tarot_spreads）
--    模型：TarotSpread
-- =============================================================
CREATE TABLE IF NOT EXISTS `tarot_spreads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '牌阵名称',
    `code` VARCHAR(50) NOT NULL COMMENT '牌阵代码标识',
    `description` TEXT NULL COMMENT '牌阵描述',
    `card_count` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '需要抽取的牌数',
    `positions` JSON NULL COMMENT '牌位定义JSON [{"position":1,"name":"过去","desc":"..."}]',
    `suitable_for` JSON NULL COMMENT '适用场景 ["love","career","general"]',
    `difficulty` VARCHAR(20) NOT NULL DEFAULT 'beginner' COMMENT '难度: beginner/intermediate/advanced',
    `is_free` TINYINT NOT NULL DEFAULT 0 COMMENT '是否免费牌阵 0否 1是',
    `points_cost` INT NOT NULL DEFAULT 0 COMMENT '使用消耗积分',
    `is_enabled` TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗牌阵表';

-- 默认牌阵种子
INSERT INTO `tarot_spreads` (`name`, `code`, `description`, `card_count`, `positions`, `suitable_for`, `difficulty`, `is_free`, `is_enabled`, `sort_order`)
VALUES
('单张牌', 'single', '抽取一张牌，快速获取当下指引', 1,
 '[{"position":1,"name":"当下指引","desc":"当前时刻的核心提示"}]',
 '["general","love","career","daily"]', 'beginner', 1, 1, 1),
('三牌阵', 'three_card', '过去-现在-未来，洞见时间维度', 3,
 '[{"position":1,"name":"过去","desc":"影响当前局面的过往"},{"position":2,"name":"现在","desc":"当前面临的核心情况"},{"position":3,"name":"未来","desc":"当前趋势延续的走向"}]',
 '["general","love","career"]', 'beginner', 0, 1, 2),
('凯尔特十字', 'celtic_cross', '最经典的十张牌阵，全面深入解析', 10,
 '[{"position":1,"name":"当前处境","desc":"当前核心情况"},{"position":2,"name":"阻碍因素","desc":"影响或阻碍当前情况的因素"},{"position":3,"name":"潜意识","desc":"深层心理状态"},{"position":4,"name":"过去根源","desc":"情况的起源或过去"},{"position":5,"name":"可能结果","desc":"可能的最佳结果"},{"position":6,"name":"近期未来","desc":"即将到来的影响"},{"position":7,"name":"自身态度","desc":"你自己的立场与感受"},{"position":8,"name":"外部影响","desc":"他人与外部环境的影响"},{"position":9,"name":"希望与恐惧","desc":"内心的期望与担忧"},{"position":10,"name":"最终结果","desc":"综合走向的最终答案"}]',
 '["general","love","career","health"]', 'advanced', 0, 1, 3),
('爱情牌阵', 'love', '聚焦感情关系，四牌深度剖析', 4,
 '[{"position":1,"name":"我方能量","desc":"你在感情中的状态"},{"position":2,"name":"对方能量","desc":"对方在感情中的状态"},{"position":3,"name":"关系现状","desc":"双方关系的实质"},{"position":4,"name":"发展方向","desc":"感情走向的建议"}]',
 '["love"]', 'intermediate', 0, 1, 4)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `card_count` = VALUES(`card_count`),
    `positions` = VALUES(`positions`),
    `is_enabled` = VALUES(`is_enabled`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =============================================================
-- 5. 塔罗占卜记录表（tc_tarot_record）
--    用于后台统计与历史查询
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_tarot_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `spread_type` VARCHAR(50) NOT NULL DEFAULT 'single' COMMENT '牌阵类型',
    `topic` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '占卜主题',
    `question` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '占卜问题',
    `cards` JSON NULL COMMENT '抽到的牌列表',
    `interpretation` TEXT NULL COMMENT 'AI/系统解读内容',
    `ai_analysis` TEXT NULL COMMENT 'AI深度分析',
    `points_used` INT NOT NULL DEFAULT 0 COMMENT '消耗积分',
    `is_free` TINYINT NOT NULL DEFAULT 0 COMMENT '是否首免',
    `is_public` TINYINT NOT NULL DEFAULT 0 COMMENT '是否公开',
    `share_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分享码',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '查看次数',
    `client_ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '客户端IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_spread_type` (`spread_type`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗占卜记录表';

-- =============================================================
-- 6. 六爻占卜记录表（tc_liuyao_record）
--    用于后台统计与历史查询
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_liuyao_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `question` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '占卜问题',
    `method` VARCHAR(30) NOT NULL DEFAULT 'coin' COMMENT '起卦方式: coin/number/random',
    `hexagram_original` JSON NULL COMMENT '本卦数据',
    `hexagram_changed` JSON NULL COMMENT '变卦数据',
    `hexagram_mutual` JSON NULL COMMENT '互卦数据',
    `lines` JSON NULL COMMENT '六爻爻辞列表',
    `moving_lines` JSON NULL COMMENT '动爻列表',
    `analysis` TEXT NULL COMMENT '系统分析',
    `ai_analysis` TEXT NULL COMMENT 'AI深度分析',
    `points_used` INT NOT NULL DEFAULT 0 COMMENT '消耗积分',
    `is_free` TINYINT NOT NULL DEFAULT 0 COMMENT '是否首免',
    `is_public` TINYINT NOT NULL DEFAULT 0 COMMENT '是否公开',
    `share_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分享码',
    `client_ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '客户端IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='六爻占卜记录表';

-- =============================================================
-- 7. 文件上传记录表（upload_files）
--    模型：UploadFile，兼容旧表名 tc_upload_file
-- =============================================================
CREATE TABLE IF NOT EXISTS `upload_files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `original_name` VARCHAR(255) NOT NULL COMMENT '原始文件名',
    `file_name` VARCHAR(255) NOT NULL COMMENT '存储文件名',
    `file_path` VARCHAR(500) NOT NULL COMMENT '存储路径',
    `file_url` VARCHAR(500) NOT NULL COMMENT '访问URL',
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小（字节）',
    `mime_type` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'MIME类型',
    `extension` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '文件扩展名',
    `storage` VARCHAR(30) NOT NULL DEFAULT 'local' COMMENT '存储方式: local/oss/cos',
    `category` VARCHAR(50) NOT NULL DEFAULT 'common' COMMENT '文件分类',
    `uploaded_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '上传者ID（管理员）',
    `is_deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已删除',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_category` (`category`),
    KEY `idx_uploaded_by` (`uploaded_by`),
    KEY `idx_mime_type` (`mime_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件上传记录表';

-- =============================================================
-- 8. AI提示词表（ai_prompts）
--    模型：AiPrompt，兼容旧表名 tc_ai_prompt
-- =============================================================
CREATE TABLE IF NOT EXISTS `ai_prompts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '提示词名称',
    `code` VARCHAR(50) NOT NULL COMMENT '提示词代码标识',
    `type` VARCHAR(50) NOT NULL DEFAULT 'system' COMMENT '类型: system/user/few_shot',
    `category` VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT '分类: bazi/tarot/liuyao/hehun/general',
    `content` TEXT NOT NULL COMMENT '提示词内容',
    `variables` JSON NULL COMMENT '变量列表 [{"name":"birth_date","desc":"出生日期"}]',
    `model` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '适用模型（空=通用）',
    `version` VARCHAR(20) NOT NULL DEFAULT '1.0' COMMENT '版本号',
    `is_active` TINYINT NOT NULL DEFAULT 1 COMMENT '是否激活 0否 1是',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_category` (`category`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI提示词配置表';

-- =============================================================
-- 9. 页面管理表（pages）
--    模型：Page，兼容旧表名 tc_page
-- =============================================================
CREATE TABLE IF NOT EXISTS `pages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL COMMENT '页面标题',
    `slug` VARCHAR(200) NOT NULL COMMENT '页面路由标识',
    `type` VARCHAR(30) NOT NULL DEFAULT 'custom' COMMENT '类型: custom/landing/article',
    `content` JSON NULL COMMENT '页面内容（JSON结构）',
    `settings` JSON NULL COMMENT '页面设置（SEO、发布等）',
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT '状态: draft/published/archived',
    `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '当前版本号',
    `updated_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后更新者（管理员ID）',
    `published_at` DATETIME NULL COMMENT '发布时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面管理表';

-- =============================================================
-- 10. 页面版本表（page_versions）
--    模型：PageVersion
-- =============================================================
CREATE TABLE IF NOT EXISTS `page_versions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT UNSIGNED NOT NULL COMMENT '页面ID',
    `version` INT UNSIGNED NOT NULL COMMENT '版本号',
    `content` JSON NULL COMMENT '该版本内容快照',
    `settings` JSON NULL COMMENT '该版本设置快照',
    `comment` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '版本说明',
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作者（管理员ID）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_page_version` (`page_id`, `version`),
    KEY `idx_page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面版本历史表';

-- =============================================================
-- 11. 页面草稿表（page_drafts）
--    模型：PageDraft
-- =============================================================
CREATE TABLE IF NOT EXISTS `page_drafts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT UNSIGNED NOT NULL COMMENT '页面ID（0=新建未保存）',
    `content` JSON NULL COMMENT '草稿内容',
    `settings` JSON NULL COMMENT '草稿设置',
    `editor_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '编辑者（管理员ID）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_page_id` (`page_id`),
    KEY `idx_editor_id` (`editor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面草稿表';

-- =============================================================
-- 12. 页面回收站表（page_recycle）
--    模型：PageRecycle
-- =============================================================
CREATE TABLE IF NOT EXISTS `page_recycle` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT UNSIGNED NOT NULL COMMENT '原页面ID',
    `title` VARCHAR(255) NOT NULL COMMENT '页面标题（快照）',
    `slug` VARCHAR(200) NOT NULL COMMENT '原路由标识',
    `content` JSON NULL COMMENT '页面内容快照',
    `deleted_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除者（管理员ID）',
    `deleted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '删除时间',
    KEY `idx_page_id` (`page_id`),
    KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面回收站表';

-- =============================================================
-- 13. 常见问题表（faqs）
--    模型：Faq，兼容旧表名 tc_faq
-- =============================================================
CREATE TABLE IF NOT EXISTS `faqs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `question` VARCHAR(500) NOT NULL COMMENT '问题',
    `answer` TEXT NOT NULL COMMENT '回答',
    `category` VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT '分类: general/bazi/tarot/payment/account',
    `is_published` TINYINT NOT NULL DEFAULT 1 COMMENT '是否发布 0否 1是',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序（越小越靠前）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_category` (`category`),
    KEY `idx_published` (`is_published`),
    KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='常见问题表';

-- 默认FAQ种子
INSERT INTO `faqs` (`question`, `answer`, `category`, `is_published`, `sort_order`)
VALUES
('如何获取积分？',     '注册赠送初始积分，每日签到、邀请好友均可获得积分奖励。', 'account', 1, 1),
('积分可以提现吗？',   '积分仅供平台功能消耗使用，不支持提现，但可用于兑换VIP会员。', 'account', 1, 2),
('八字解盘准确吗？',   '本平台结合传统命理学与AI算法，解盘结论仅供参考，请理性看待。', 'bazi', 1, 3),
('如何联系客服？',     '可通过"关于我们"页面的微信/邮箱与我们取得联系。', 'general', 1, 4)
ON DUPLICATE KEY UPDATE
    `answer` = VALUES(`answer`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =============================================================
-- 14. 用户反馈表（feedback）
--    模型：Feedback，兼容旧表名 tc_feedback
-- =============================================================
CREATE TABLE IF NOT EXISTS `feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID（0=匿名）',
    `type` VARCHAR(30) NOT NULL DEFAULT 'suggestion' COMMENT '类型: bug/feature/suggestion/other',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '标题',
    `content` TEXT NOT NULL COMMENT '反馈内容',
    `images` JSON NULL COMMENT '附图列表',
    `contact` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '联系方式',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '处理状态: 0待处理 1处理中 2已回复 3已关闭',
    `reply` TEXT NULL COMMENT '回复内容',
    `replied_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复管理员ID',
    `replied_at` DATETIME NULL COMMENT '回复时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户反馈表';

-- =============================================================
-- 15. 每日运势模板表（daily_fortune_templates）
--    模型：DailyFortuneTemplate
-- =============================================================
CREATE TABLE IF NOT EXISTS `daily_fortune_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `zodiac` VARCHAR(20) NOT NULL COMMENT '星座/生肖标识',
    `type` VARCHAR(20) NOT NULL DEFAULT 'zodiac' COMMENT '模板类型: zodiac/shengxiao/element',
    `date_pattern` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '日期模式（月日，如 03-18 或空=通用）',
    `overall` TEXT NULL COMMENT '综合运势模板（可含变量）',
    `love` TEXT NULL COMMENT '感情运势模板',
    `career` TEXT NULL COMMENT '事业运势模板',
    `wealth` TEXT NULL COMMENT '财运模板',
    `health` TEXT NULL COMMENT '健康模板',
    `lucky_color` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '幸运颜色',
    `lucky_number` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '幸运数字',
    `lucky_direction` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '幸运方位',
    `score_min` TINYINT UNSIGNED NOT NULL DEFAULT 60 COMMENT '评分下限（算法随机区间）',
    `score_max` TINYINT UNSIGNED NOT NULL DEFAULT 95 COMMENT '评分上限',
    `is_active` TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_zodiac` (`zodiac`),
    KEY `idx_type` (`type`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日运势模板表';

-- =============================================================
-- 16. 问题模板表（question_templates）
--    模型：QuestionTemplate
-- =============================================================
CREATE TABLE IF NOT EXISTS `question_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category` VARCHAR(50) NOT NULL COMMENT '分类: love/career/wealth/health/general',
    `title` VARCHAR(200) NOT NULL COMMENT '模板标题',
    `content` VARCHAR(500) NOT NULL COMMENT '模板问题内容',
    `applicable_to` JSON NULL COMMENT '适用功能 ["tarot","liuyao","bazi"]',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `is_enabled` TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_category` (`category`),
    KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问题模板表';

-- 默认问题模板种子
INSERT INTO `question_templates` (`category`, `title`, `content`, `applicable_to`, `sort_order`, `is_enabled`)
VALUES
('love',    '感情走向',   '最近与伴侣/暗恋对象的感情走势会如何？',       '["tarot","liuyao"]', 1, 1),
('love',    '姻缘时机',   '我今年的桃花运和姻缘时机如何？',               '["tarot","bazi"]',   2, 1),
('career',  '职场发展',   '当前工作/事业的整体发展方向如何？',             '["tarot","liuyao"]', 3, 1),
('career',  '跳槽决策',   '现在是否适合换工作或开展新的事业？',             '["tarot","liuyao"]', 4, 1),
('wealth',  '财运状况',   '近期的财运如何？有哪些需要注意的地方？',         '["tarot","liuyao"]', 5, 1),
('health',  '健康提示',   '近期有哪些健康方面需要特别关注的？',             '["tarot"]',          6, 1),
('general', '整体运势',   '请帮我分析一下近期的综合运势与整体状态。',       '["tarot","bazi"]',   7, 1)
ON DUPLICATE KEY UPDATE
    `content` = VALUES(`content`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =============================================================
-- 17. 用户好评/见证表（testimonials）
--    模型：Testimonial
-- =============================================================
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID（0=虚拟用户）',
    `nickname` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '展示昵称',
    `avatar` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '头像URL',
    `content` TEXT NOT NULL COMMENT '好评内容',
    `service` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '涉及服务: bazi/tarot/liuyao/hehun/general',
    `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '评分 1-5',
    `is_featured` TINYINT NOT NULL DEFAULT 0 COMMENT '是否首页精选 0否 1是',
    `is_published` TINYINT NOT NULL DEFAULT 1 COMMENT '是否发布 0否 1是',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_service` (`service`),
    KEY `idx_featured` (`is_featured`),
    KEY `idx_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户好评/见证表';

-- =============================================================
-- 18. 网站内容表（site_contents）
--    模型：SiteContent
-- =============================================================
CREATE TABLE IF NOT EXISTS `site_contents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page` VARCHAR(50) NOT NULL COMMENT '所属页面: home/about/service/pricing',
    `key` VARCHAR(100) NOT NULL COMMENT '内容键名',
    `value` TEXT NULL COMMENT '内容值',
    `type` VARCHAR(20) NOT NULL DEFAULT 'text' COMMENT '内容类型: text/html/json/image',
    `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '说明',
    `is_enabled` TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建者',
    `updated_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新者',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_page_key` (`page`, `key`),
    KEY `idx_page` (`page`),
    KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='网站内容管理表';

-- 默认首页内容种子
INSERT INTO `site_contents` (`page`, `key`, `value`, `type`, `description`, `is_enabled`, `sort_order`)
VALUES
('home', 'hero_title',      '洞见天机，照见本心',   'text', '首页主标题',         1, 1),
('home', 'hero_subtitle',   '专业八字命理 · AI智能解盘 · 为你拨开命运迷雾', 'text', '首页副标题', 1, 2),
('home', 'hero_cta_text',   '立即免费测算',          'text', '首页CTA按钮文字',    1, 3),
('home', 'hero_cta_link',   '/bazi',                 'text', '首页CTA按钮链接',    1, 4),
('home', 'free_trial_note', '注册即享首次免费八字排盘', 'text', '首免说明文字',   1, 5)
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `updated_at` = CURRENT_TIMESTAMP;

-- =============================================================
-- 19. 操作日志表（operation_logs）
--    模型：OperationLog
-- =============================================================
CREATE TABLE IF NOT EXISTS `operation_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作管理员ID',
    `admin_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作管理员名称（冗余）',
    `action` VARCHAR(100) NOT NULL COMMENT '操作行为',
    `module` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作模块',
    `target_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作对象ID',
    `target_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作对象类型',
    `detail` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '操作描述',
    `before_data` JSON NULL COMMENT '操作前数据快照',
    `after_data` JSON NULL COMMENT '操作后数据快照',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '操作IP',
    `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'User-Agent',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_action` (`action`),
    KEY `idx_module` (`module`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员操作日志表';

-- =============================================================
-- 20. 签到日志表（tc_checkin_log）
--    Task控制器 / Daily控制器使用
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_checkin_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '签到日期',
    `consecutive_days` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '连续签到天数',
    `points` INT NOT NULL DEFAULT 0 COMMENT '本次获得积分',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_date` (`user_id`, `date`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户签到日志表';

-- =============================================================
-- 21. 分享记录表（tc_share_log）
--    Share控制器使用
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_share_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '分享类型: bazi/tarot/liuyao/hehun/daily',
    `platform` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '分享平台: wechat/weibo/link/copy',
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分享内容ID（记录ID）',
    `share_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分享码',
    `points_reward` INT NOT NULL DEFAULT 0 COMMENT '分享获得积分',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '分享IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_share_code` (`share_code`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容分享记录表';

-- =============================================================
-- 22. 任务记录表（tc_task_log）
--    Task控制器使用
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_task_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `task_type` VARCHAR(50) NOT NULL COMMENT '任务类型: checkin/invite/share/view/first_bazi',
    `task_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '任务名称',
    `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
    `extra` JSON NULL COMMENT '额外信息',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user_id` (`user_id`),
    KEY `idx_task_type` (`task_type`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务完成记录表';

-- =============================================================
-- 23. VIP订单表 username 字段兼容补齐（tc_vip_order 可能已存在）
-- =============================================================
-- 仅在表不存在时创建（若已存在由 02_create_tables.sql 负责）
CREATE TABLE IF NOT EXISTS `tc_vip_order` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_no` VARCHAR(50) NOT NULL COMMENT '订单号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `vip_level` TINYINT NOT NULL DEFAULT 1 COMMENT 'VIP等级 1月度 2季度 3年度',
    `duration_days` INT NOT NULL DEFAULT 30 COMMENT '有效天数',
    `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实付金额',
    `payment_type` VARCHAR(30) NOT NULL DEFAULT 'wechat_jsapi' COMMENT '支付方式',
    `pay_order_no` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '支付平台订单号',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending/paid/expired/cancelled',
    `paid_at` DATETIME NULL COMMENT '支付时间',
    `vip_start_at` DATETIME NULL COMMENT 'VIP开始时间',
    `vip_end_at` DATETIME NULL COMMENT 'VIP到期时间',
    `callback_data` JSON NULL COMMENT '回调原文',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_vip_end_at` (`vip_end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VIP订单表';

-- =============================================================
-- 24. 短信验证码表（tc_sms_code）
--    模型：SmsCode，用于短信验证码管理
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_sms_code` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
    `code` VARCHAR(10) NOT NULL COMMENT '验证码',
    `type` VARCHAR(50) NOT NULL DEFAULT 'register' COMMENT '验证码类型: register/login/reset',
    `expire_time` DATETIME NOT NULL COMMENT '过期时间',
    `is_used` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已使用 0否 1是',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '请求IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_phone_type` (`phone`, `type`),
    INDEX `idx_expire_time` (`expire_time`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信验证码表';

-- =============================================================
-- 25. 短信配置表（tc_sms_config）
--    模型：SmsConfig，用于存储腾讯云/其他SMS供应商的API配置
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_sms_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `provider` VARCHAR(50) NOT NULL DEFAULT 'tencent' COMMENT '供应商: tencent/aliyun/other',
    `secret_id` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '腾讯云SecretId 或 阿里云AccessKeyId',
    `secret_key` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '腾讯云SecretKey 或 阿里云AccessKeySecret',
    `sdk_app_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '腾讯云SDK App ID',
    `sign_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '短信签名',
    `template_code` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '短信模板ID',
    `template_register` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '注册验证码模板ID',
    `template_reset` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '密码重置验证码模板ID',
    `is_enabled` TINYINT NOT NULL DEFAULT 0 COMMENT '是否启用 0否 1是',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_provider` (`provider`),
    KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信配置表';

-- =============================================================
-- 26. 支付配置表（tc_payment_config）
--    模型：PaymentConfig，用于存储微信支付/支付宝的API配置
-- =============================================================
CREATE TABLE IF NOT EXISTS `tc_payment_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL DEFAULT 'wechat_jsapi' COMMENT '支付类型: wechat_jsapi/wechat_native/alipay_web/alipay_mobile',
    `mch_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '商户号（微信）/ 商家ID（支付宝）',
    `app_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '应用ID（微信）/ 应用ID（支付宝）',
    `api_key` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'API密钥（微信）/ 私钥（支付宝）',
    `api_cert` LONGTEXT COMMENT '微信支付证书（pem格式）',
    `api_key_pem` LONGTEXT COMMENT '微信支付私钥（pem格式）',
    `notify_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '支付回调通知URL',
    `return_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '支付成功返回URL（支付宝用）',
    `is_enabled` TINYINT NOT NULL DEFAULT 0 COMMENT '是否启用 0否 1是',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_type` (`type`),
    KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付配置表';

SET FOREIGN_KEY_CHECKS = 1;

-- 完成提示
SELECT CONCAT(
    '✅ 缺失表补充完成。共创建/确认以下表：',
    'hehun_records, system_config, tarot_cards, tarot_spreads, ',
    'tc_tarot_record, tc_liuyao_record, upload_files, ai_prompts, ',
    'pages, page_versions, page_drafts, page_recycle, faqs, feedback, ',
    'daily_fortune_templates, question_templates, testimonials, site_contents, ',
    'operation_logs, tc_checkin_log, tc_share_log, tc_task_log, ',
    'points_history, points_exchange, checkin_record, ',
    'tc_sms_code, tc_sms_config, tc_payment_config'
) AS migration_result;

