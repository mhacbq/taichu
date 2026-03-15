-- 每日运势表（模型使用tc_daily_fortune）
CREATE TABLE IF NOT EXISTS `tc_daily_fortune` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL COMMENT '日期',
    `lunar_date` VARCHAR(50) DEFAULT '' COMMENT '农历日期',
    `overall_score` INT DEFAULT 70 COMMENT '综合评分 0-100',
    `summary` VARCHAR(500) DEFAULT '' COMMENT '运势摘要',
    `career_score` INT DEFAULT 70 COMMENT '事业评分',
    `career_desc` VARCHAR(500) DEFAULT '' COMMENT '事业描述',
    `wealth_score` INT DEFAULT 70 COMMENT '财富评分',
    `wealth_desc` VARCHAR(500) DEFAULT '' COMMENT '财富描述',
    `love_score` INT DEFAULT 70 COMMENT '感情评分',
    `love_desc` VARCHAR(500) DEFAULT '' COMMENT '感情描述',
    `health_score` INT DEFAULT 70 COMMENT '健康评分',
    `health_desc` VARCHAR(500) DEFAULT '' COMMENT '健康描述',
    `yi` VARCHAR(500) DEFAULT '' COMMENT '宜（宜做的事）',
    `ji` VARCHAR(500) DEFAULT '' COMMENT '忌（忌做的事）',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_date` (`date`),
    INDEX `idx_overall_score` (`overall_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日运势表';
