CREATE TABLE IF NOT EXISTS `tc_faq` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `category`   VARCHAR(50)  NOT NULL DEFAULT 'general' COMMENT '分类：general/bazi/tarot/account/points',
  `question`   VARCHAR(500) NOT NULL DEFAULT '' COMMENT '问题',
  `answer`     TEXT         NOT NULL COMMENT '回答',
  `sort_order` INT          NOT NULL DEFAULT 0 COMMENT '排序（越小越靠前）',
  `view_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览次数',
  `is_enabled` TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '是否启用：1启用 0禁用',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_sort`     (`sort_order`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='FAQ常见问题表';
