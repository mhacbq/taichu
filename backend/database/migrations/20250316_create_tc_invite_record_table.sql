-- 邀请记录表（模型使用tc_invite_record）
CREATE TABLE IF NOT EXISTS `tc_invite_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inviter_id` INT UNSIGNED NOT NULL COMMENT '邀请人ID',
    `invited_id` INT UNSIGNED DEFAULT 0 COMMENT '被邀请人ID',
    `invite_code` VARCHAR(20) NOT NULL COMMENT '邀请码',
    `points_reward` INT DEFAULT 0 COMMENT '奖励积分',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0无效 1有效',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_inviter_id` (`inviter_id`),
    INDEX `idx_invited_id` (`invited_id`),
    INDEX `idx_invite_code` (`invite_code`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邀请记录表';
