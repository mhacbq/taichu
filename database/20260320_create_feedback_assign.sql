-- 反馈分配表
CREATE TABLE IF NOT EXISTS `tc_feedback_assign` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `feedback_id` bigint unsigned NOT NULL COMMENT '反馈ID',
  `assigned_by` bigint unsigned NOT NULL COMMENT '分配人ID（管理员）',
  `assigned_to` bigint unsigned NOT NULL COMMENT '被分配人ID（管理员）',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：1-处理中 2-已完成',
  `note` varchar(500) DEFAULT NULL COMMENT '分配备注',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '分配时间',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT '完成时间',
  PRIMARY KEY (`id`),
  KEY `idx_feedback_id` (`feedback_id`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反馈分配表';

-- 反馈处理记录表
CREATE TABLE IF NOT EXISTS `tc_feedback_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `feedback_id` bigint unsigned NOT NULL COMMENT '反馈ID',
  `admin_id` bigint unsigned NOT NULL COMMENT '操作人ID（管理员）',
  `action` varchar(50) NOT NULL COMMENT '操作类型：assign-分配 reply-回复 status-更新状态 note-备注',
  `content` text COMMENT '操作内容',
  `old_value` varchar(255) DEFAULT NULL COMMENT '变更前的值',
  `new_value` varchar(255) DEFAULT NULL COMMENT '变更后的值',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `idx_feedback_id` (`feedback_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反馈处理记录表';
