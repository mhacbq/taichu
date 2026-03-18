-- 知识库/文章管理表结构
-- 创建时间：2026-03-17

USE taichu;

CREATE TABLE IF NOT EXISTS `tc_article_category` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name` VARCHAR(100) NOT NULL COMMENT '分类名称',
    `slug` VARCHAR(120) NOT NULL COMMENT '分类标识',
    `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '分类描述',
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父分类ID',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序值，越小越靠前',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_article_category_slug` (`slug`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台知识库文章分类表';

CREATE TABLE IF NOT EXISTS `tc_article` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `category_id` INT UNSIGNED NOT NULL COMMENT '分类ID',
    `title` VARCHAR(200) NOT NULL COMMENT '文章标题',
    `slug` VARCHAR(160) NOT NULL COMMENT '文章标识',
    `summary` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '文章摘要',
    `content` LONGTEXT NOT NULL COMMENT '文章正文',
    `thumbnail` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '封面图地址',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0草稿 1发布 2定时发布 3归档',
    `is_hot` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否热门',
    `author_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '作者管理员ID',
    `author_name` VARCHAR(80) NOT NULL DEFAULT '' COMMENT '作者名称',
    `published_at` DATETIME NULL DEFAULT NULL COMMENT '发布时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_article_slug` (`slug`),
    KEY `idx_category_status` (`category_id`, `status`),
    KEY `idx_is_hot` (`is_hot`),
    KEY `idx_published_at` (`published_at`),
    FULLTEXT KEY `ft_title_summary_content` (`title`, `summary`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台知识库文章表';

-- 默认分类种子数据
INSERT INTO `tc_article_category` (`name`, `slug`, `description`, `parent_id`, `sort_order`, `status`)
VALUES
    ('八字基础', 'bazi-basic', '八字入门、排盘基础、十神速查等基础内容', 0, 10, 1),
    ('十神格局', 'shishen-geju', '十神、格局、旺衰与喜忌专题', 0, 20, 1),
    ('大运流年', 'dayun-liunian', '大运、流年、流月专题文章', 0, 30, 1),
    ('塔罗体系', 'tarot-system', '塔罗牌义、牌阵与占卜指引', 0, 40, 1),
    ('风水择吉', 'fengshui-zeji', '风水布局、择吉黄历与应用文章', 0, 50, 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `parent_id` = VALUES(`parent_id`),
    `sort_order` = VALUES(`sort_order`),
    `status` = VALUES(`status`);
