-- 塔罗牌阵表
CREATE TABLE IF NOT EXISTS `tarot_spreads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '牌阵名称',
    `name_en` VARCHAR(50) DEFAULT '' COMMENT '英文名称',
    `code` VARCHAR(30) NOT NULL UNIQUE COMMENT '牌阵编码',
    `description` TEXT COMMENT '牌阵描述',
    `positions` JSON NOT NULL COMMENT '牌位定义 [{"name":"过去","meaning":"代表过去的影响"},...]',
    `card_count` INT NOT NULL COMMENT '牌数',
    `category` VARCHAR(30) DEFAULT 'general' COMMENT '类别: general/love/career/decision',
    `difficulty` VARCHAR(20) DEFAULT 'beginner' COMMENT '难度: beginner/intermediate/advanced',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否启用: 0禁用 1启用',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `image` VARCHAR(255) DEFAULT '' COMMENT '牌阵示意图',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_code` (`code`),
    INDEX `idx_category` (`category`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='塔罗牌阵表';

-- 插入默认牌阵
INSERT INTO `tarot_spreads` (`name`, `name_en`, `code`, `description`, `positions`, `card_count`, `category`, `difficulty`, `sort_order`) VALUES
('单张牌', 'Single Card', 'single', '最简洁的占卜方式，适合快速获得指引。', 
'[{"name":"指引","meaning":"当前情况的核心信息或建议"}]', 1, 'general', 'beginner', 1),

('三张牌', 'Three Cards', 'three', '经典的三张牌阵，代表过去、现在、未来。',
'[{"name":"过去","meaning":"代表过去的影响和经历"},{"name":"现在","meaning":"代表当前的状况和挑战"},{"name":"未来","meaning":"代表可能的发展趋势"}]', 
3, 'general', 'beginner', 2),

('凯尔特十字', 'Celtic Cross', 'celtic_cross', '最经典的塔罗牌阵之一，提供全面的分析。',
'[{"name":"现状","meaning":"代表当前的核心状况"},{"name":"障碍","meaning":"代表面临的阻碍或挑战"},{"name":"基础","meaning":"代表事情的根本基础"},{"name":"过去","meaning":"代表过去的影响"},{"name":"目标","meaning":"代表期望达成的目标"},{"name":"未来","meaning":"代表近期的发展趋势"},{"name":"自我","meaning":"代表你现在的状态"},{"name":"环境","meaning":"代表外部环境和他人看法"},{"name":"希望","meaning":"代表你的希望和恐惧"},{"name":"结果","meaning":"代表最终可能的结果"}]',
10, 'general', 'advanced', 3),

('爱情关系', 'Relationship Spread', 'relationship', '专为感情关系设计的牌阵。',
'[{"name":"你","meaning":"代表你在这段关系中的状态"},{"name":"对方","meaning":"代表对方在这段关系中的状态"},{"name":"关系","meaning":"代表你们关系的现状"},{"name":"挑战","meaning":"代表关系面临的挑战"},{"name":"建议","meaning":"代表改善关系的建议"}]',
5, 'love', 'intermediate', 4),

('事业抉择', 'Career Choice', 'career', '帮助分析事业发展和决策的牌阵。',
'[{"name":"当前","meaning":"代表当前事业状况"},{"name":"选择A","meaning":"代表选择A的结果"},{"name":"选择B","meaning":"代表选择B的结果"},{"name":"建议","meaning":"代表最终建议"}]',
4, 'career', 'intermediate', 5);
