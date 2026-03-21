-- 帮助中心相关表结构
-- `site_contents` 和 `faqs`

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for site_contents
-- ----------------------------
create table if not exists `site_contents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `page` varchar(50) NOT NULL DEFAULT 'home' COMMENT '页面标识',
  `key` varchar(50) NOT NULL COMMENT '键名',
  `value` text COMMENT '键值',
  `type` varchar(20) DEFAULT 'text' COMMENT '类型: text, image, json, html',
  `description` varchar(255) DEFAULT '' COMMENT '描述',
  `app_id` int(10) unsigned DEFAULT 0 COMMENT '应用ID(预留)',
  `sort_order` int(10) unsigned DEFAULT 0 COMMENT '排序',
  `is_enabled` tinyint(1) unsigned DEFAULT 1 COMMENT '是否启用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_page_key` (`page`,`key`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='站点内容配置表';

-- ----------------------------
-- Table structure for faqs
-- ----------------------------
create table if not exists `faqs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `category` varchar(50) NOT NULL DEFAULT 'general' COMMENT '分类: general, bazi, tarot, account, points',
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(10) unsigned DEFAULT 0 COMMENT '排序',
  `is_enabled` tinyint(1) unsigned DEFAULT 1 COMMENT '是否启用',
  `view_count` int(10) unsigned DEFAULT 0 COMMENT '浏览量',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_sort` (`sort_order`),
  KEY `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='常见问题表';

SET FOREIGN_KEY_CHECKS = 1;

