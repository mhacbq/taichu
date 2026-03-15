-- 八字合婚记录表
CREATE TABLE IF NOT EXISTS `hehun_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `male_name` VARCHAR(50) DEFAULT '' COMMENT '男方姓名',
    `male_birth_date` DATE NOT NULL COMMENT '男方出生日期',
    `male_birth_time` TIME NOT NULL COMMENT '男方出生时间',
    `male_lunar` TINYINT DEFAULT 0 COMMENT '男方是否农历: 0公历 1农历',
    `female_name` VARCHAR(50) DEFAULT '' COMMENT '女方姓名',
    `female_birth_date` DATE NOT NULL COMMENT '女方出生日期',
    `female_birth_time` TIME NOT NULL COMMENT '女方出生时间',
    `female_lunar` TINYINT DEFAULT 0 COMMENT '女方是否农历: 0公历 1农历',
    `male_bazi` JSON NULL COMMENT '男方八字数据',
    `female_bazi` JSON NULL COMMENT '女方八字数据',
    `score` INT DEFAULT 0 COMMENT '合婚评分 0-100',
    `result` TEXT COMMENT '合婚结果概述',
    `analysis` JSON NULL COMMENT '详细分析数据',
    `advice` TEXT COMMENT '建议内容',
    `is_public` TINYINT DEFAULT 0 COMMENT '是否公开: 0私密 1公开',
    `share_code` VARCHAR(20) DEFAULT '' COMMENT '分享码',
    `view_count` INT DEFAULT 0 COMMENT '查看次数',
    `points_used` INT DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_share_code` (`share_code`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='八字合婚记录表';
