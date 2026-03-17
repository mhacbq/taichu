-- 知识库分类表
CREATE TABLE IF NOT EXISTS `tc_article_category` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '分类名称',
    `slug` VARCHAR(50) NOT NULL UNIQUE COMMENT '分类标识',
    `description` VARCHAR(200) DEFAULT '' COMMENT '分类描述',
    `parent_id` INT UNSIGNED DEFAULT 0 COMMENT '父分类ID',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_status` (`status`),
    INDEX `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章分类表';

-- 知识库文章表
CREATE TABLE IF NOT EXISTS `tc_article` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL COMMENT '分类ID',
    `title` VARCHAR(200) NOT NULL COMMENT '文章标题',
    `slug` VARCHAR(100) NOT NULL UNIQUE COMMENT '文章标识',
    `summary` VARCHAR(500) DEFAULT '' COMMENT '文章摘要',
    `content` LONGTEXT NOT NULL COMMENT '文章内容',
    `thumbnail` VARCHAR(255) DEFAULT '' COMMENT '缩略图',
    `author_id` INT UNSIGNED DEFAULT 0 COMMENT '作者ID',
    `author_name` VARCHAR(50) DEFAULT '' COMMENT '作者名称',
    `view_count` INT UNSIGNED DEFAULT 0 COMMENT '阅读量',
    `like_count` INT UNSIGNED DEFAULT 0 COMMENT '点赞数',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0草稿 1已发布 2置顶 3隐藏',
    `is_hot` TINYINT DEFAULT 0 COMMENT '是否热门: 0否 1是',
    `allow_comment` TINYINT DEFAULT 1 COMMENT '是否允许评论: 0否 1是',
    `meta_title` VARCHAR(200) DEFAULT '' COMMENT 'SEO标题',
    `meta_description` VARCHAR(500) DEFAULT '' COMMENT 'SEO描述',
    `meta_keywords` VARCHAR(300) DEFAULT '' COMMENT 'SEO关键词',
    `published_at` DATETIME NULL COMMENT '发布时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_category_id` (`category_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_is_hot` (`is_hot`),
    INDEX `idx_published_at` (`published_at`),
    FULLTEXT INDEX `idx_fulltext` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章表';

-- 插入默认分类
INSERT INTO `tc_article_category` (`name`, `slug`, `description`, `sort_order`) VALUES
('命理基础', 'basics', '八字、五行、天干地支基础知识', 1),
('塔罗指南', 'tarot-guide', '塔罗牌意解析与牌阵学习', 2),
('风水常识', 'fengshui', '家居风水、办公风水小常识', 3),
('民俗文化', 'culture', '传统节庆、民俗习惯介绍', 4);
