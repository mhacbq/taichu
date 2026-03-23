
-- =====================================================
-- 20260323 确保管理端所需的业务记录表存在
-- 包含：合婚(hehun_records)、六爻(tc_liuyao_record)、取名(tc_qiming_record)、流年运势(tc_yearly_fortune)
-- =====================================================

-- 1. 合婚记录表（使用 hehun_records，与 HehunRecord 模型一致）
CREATE TABLE IF NOT EXISTS `hehun_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `male_name` VARCHAR(50) DEFAULT '' COMMENT '男方姓名',
    `male_birth_date` DATE NULL COMMENT '男方出生日期',
    `male_birth_time` TIME NULL COMMENT '男方出生时间',
    `male_birthday` VARCHAR(100) DEFAULT '' COMMENT '男方生辰（展示用）',
    `female_name` VARCHAR(50) DEFAULT '' COMMENT '女方姓名',
    `female_birth_date` DATE NULL COMMENT '女方出生日期',
    `female_birth_time` TIME NULL COMMENT '女方出生时间',
    `female_birthday` VARCHAR(100) DEFAULT '' COMMENT '女方生辰（展示用）',
    `bazi_match` JSON NULL COMMENT '八字合婚分析',
    `wuxing_match` JSON NULL COMMENT '五行匹配分析',
    `score` TINYINT DEFAULT 0 COMMENT '合婚评分',
    `result` TEXT COMMENT '合婚结果',
    `ai_analysis` TEXT NULL COMMENT 'AI深度解读',
    `points_used` INT DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='八字合婚记录表';

-- 同时确保旧格式表也存在（如果全量导入时只建了 tc_hehun_record）
CREATE TABLE IF NOT EXISTS `tc_hehun_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `male_name` VARCHAR(50) DEFAULT '' COMMENT '男方姓名',
    `male_birth_date` DATE NULL COMMENT '男方出生日期',
    `male_birth_time` TIME NULL COMMENT '男方出生时间',
    `female_name` VARCHAR(50) DEFAULT '' COMMENT '女方姓名',
    `female_birth_date` DATE NULL COMMENT '女方出生日期',
    `female_birth_time` TIME NULL COMMENT '女方出生时间',
    `bazi_match` JSON NULL COMMENT '八字合婚分析',
    `wuxing_match` JSON NULL COMMENT '五行匹配分析',
    `score` TINYINT DEFAULT 0 COMMENT '合婚评分',
    `result` TEXT COMMENT '合婚结果',
    `points_used` INT DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='八字合婚记录表(tc前缀)';

-- 2. 六爻占卜记录表
CREATE TABLE IF NOT EXISTS `tc_liuyao_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `question` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '所问之事',
    `method` VARCHAR(30) NOT NULL DEFAULT 'coin' COMMENT '起卦方式: coin/time/number/manual',
    `hexagram_original` JSON NULL COMMENT '本卦',
    `hexagram_changed` JSON NULL COMMENT '变卦',
    `hexagram_mutual` JSON NULL COMMENT '互卦',
    `lines` JSON NULL COMMENT '六爻信息',
    `moving_lines` JSON NULL COMMENT '动爻',
    `analysis` TEXT NULL COMMENT '传统解析',
    `ai_analysis` TEXT NULL COMMENT 'AI解析',
    `result` TEXT NULL COMMENT '综合结果',
    `is_ai` TINYINT NOT NULL DEFAULT 0 COMMENT '是否使用AI分析',
    `points_used` INT NOT NULL DEFAULT 0 COMMENT '消耗积分',
    `is_free` TINYINT NOT NULL DEFAULT 0 COMMENT '是否免费',
    `is_public` TINYINT NOT NULL DEFAULT 0 COMMENT '是否公开',
    `share_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分享码',
    `client_ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '客户端IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='六爻占卜记录表';

-- 3. 取名建议记录表
CREATE TABLE IF NOT EXISTS `tc_qiming_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `surname` VARCHAR(10) NOT NULL COMMENT '姓氏',
    `gender` TINYINT NOT NULL COMMENT '性别: 1男 2女',
    `birth_date` DATE NULL COMMENT '出生日期',
    `birth_time` TIME NULL COMMENT '出生时间',
    `birthday` VARCHAR(100) DEFAULT '' COMMENT '生辰（展示用）',
    `wuxing_lack` VARCHAR(50) DEFAULT '' COMMENT '五行缺失',
    `name_suggestions` JSON NULL COMMENT '名字建议列表',
    `names` JSON NULL COMMENT '推荐名字数组',
    `name_count` INT DEFAULT 0 COMMENT '推荐名字数量',
    `result` TEXT NULL COMMENT '取名分析结果',
    `points_used` INT DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_surname` (`surname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='取名建议记录表';

-- 4. 流年运势记录表
CREATE TABLE IF NOT EXISTS `tc_yearly_fortune` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `year` INT NOT NULL COMMENT '年份',
    `bazi_id` INT UNSIGNED DEFAULT NULL COMMENT '八字记录ID',
    `gender` VARCHAR(10) DEFAULT '' COMMENT '性别',
    `overall_score` TINYINT DEFAULT 0 COMMENT '综合评分',
    `career_score` TINYINT DEFAULT 0 COMMENT '事业评分',
    `wealth_score` TINYINT DEFAULT 0 COMMENT '财运评分',
    `love_score` TINYINT DEFAULT 0 COMMENT '感情评分',
    `health_score` TINYINT DEFAULT 0 COMMENT '健康评分',
    `overall_analysis` TEXT COMMENT '综合分析',
    `fortune_data` JSON NULL COMMENT '运势数据',
    `monthly_fortune` JSON NULL COMMENT '月度运势',
    `important_days` JSON NULL COMMENT '重要日期',
    `ai_analysis` TEXT NULL COMMENT 'AI深度解读',
    `result` TEXT NULL COMMENT '综合结果',
    `is_paid` TINYINT DEFAULT 0 COMMENT '是否付费解锁',
    `points_used` INT DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_year` (`year`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流年运势记录表';
