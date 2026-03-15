-- 页面表
CREATE TABLE IF NOT EXISTS `pages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL COMMENT '页面标题',
    `slug` VARCHAR(100) NOT NULL UNIQUE COMMENT '页面标识',
    `content` LONGTEXT COMMENT '页面内容',
    `meta_title` VARCHAR(200) DEFAULT '' COMMENT 'SEO标题',
    `meta_description` VARCHAR(500) DEFAULT '' COMMENT 'SEO描述',
    `meta_keywords` VARCHAR(300) DEFAULT '' COMMENT 'SEO关键词',
    `template` VARCHAR(50) DEFAULT 'default' COMMENT '模板',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0草稿 1已发布 2隐藏',
    `is_home` TINYINT DEFAULT 0 COMMENT '是否首页: 0否 1是',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `view_count` INT DEFAULT 0 COMMENT '浏览次数',
    `published_at` DATETIME NULL COMMENT '发布时间',
    `created_by` INT UNSIGNED DEFAULT 0 COMMENT '创建人ID',
    `updated_by` INT UNSIGNED DEFAULT 0 COMMENT '更新人ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_slug` (`slug`),
    INDEX `idx_status` (`status`),
    INDEX `idx_is_home` (`is_home`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面表';

-- 页面版本表
CREATE TABLE IF NOT EXISTS `page_versions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT UNSIGNED NOT NULL COMMENT '页面ID',
    `version` INT NOT NULL COMMENT '版本号',
    `title` VARCHAR(200) NOT NULL COMMENT '页面标题',
    `content` LONGTEXT COMMENT '页面内容',
    `created_by` INT UNSIGNED DEFAULT 0 COMMENT '创建人ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_page_id` (`page_id`),
    INDEX `idx_version` (`version`),
    UNIQUE KEY `uk_page_version` (`page_id`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面版本表';

-- 页面草稿表
CREATE TABLE IF NOT EXISTS `page_drafts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT UNSIGNED NOT NULL COMMENT '页面ID',
    `title` VARCHAR(200) NOT NULL COMMENT '页面标题',
    `content` LONGTEXT COMMENT '页面内容',
    `autosave` TINYINT DEFAULT 0 COMMENT '是否自动保存: 0否 1是',
    `created_by` INT UNSIGNED DEFAULT 0 COMMENT '创建人ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_page_id` (`page_id`),
    INDEX `idx_autosave` (`autosave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面草稿表';

-- 上传文件表
CREATE TABLE IF NOT EXISTS `upload_files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '上传用户ID',
    `name` VARCHAR(255) NOT NULL COMMENT '原始文件名',
    `filename` VARCHAR(255) NOT NULL COMMENT '存储文件名',
    `path` VARCHAR(500) NOT NULL COMMENT '文件路径',
    `url` VARCHAR(500) NOT NULL COMMENT '访问URL',
    `mime_type` VARCHAR(100) DEFAULT '' COMMENT 'MIME类型',
    `extension` VARCHAR(20) DEFAULT '' COMMENT '文件扩展名',
    `size` INT UNSIGNED DEFAULT 0 COMMENT '文件大小(字节)',
    `width` INT UNSIGNED DEFAULT 0 COMMENT '图片宽度',
    `height` INT UNSIGNED DEFAULT 0 COMMENT '图片高度',
    `type` VARCHAR(50) DEFAULT '' COMMENT '文件类型: image/video/audio/document/other',
    `storage` VARCHAR(20) DEFAULT 'local' COMMENT '存储位置: local/oss/cos',
    `is_image` TINYINT DEFAULT 0 COMMENT '是否图片: 0否 1是',
    `used_count` INT DEFAULT 0 COMMENT '使用次数',
    `ip` VARCHAR(45) DEFAULT '' COMMENT '上传IP',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_extension` (`extension`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='上传文件表';
