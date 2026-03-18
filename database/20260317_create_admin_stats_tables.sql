SET NAMES utf8mb4;
USE taichu;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `site_daily_stats` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `stat_date` DATE NOT NULL COMMENT '统计日期',
    `new_users` INT NOT NULL DEFAULT 0 COMMENT '新增用户数',
    `active_users` INT NOT NULL DEFAULT 0 COMMENT '活跃用户数',
    `total_users` INT NOT NULL DEFAULT 0 COMMENT '累计用户数',
    `points_given` INT NOT NULL DEFAULT 0 COMMENT '发放积分总数',
    `points_consumed` INT NOT NULL DEFAULT 0 COMMENT '消耗积分总数',
    `points_balance` INT NOT NULL DEFAULT 0 COMMENT '用户积分余额总和',
    `bazi_count` INT NOT NULL DEFAULT 0 COMMENT '八字排盘次数',
    `tarot_count` INT NOT NULL DEFAULT 0 COMMENT '塔罗占卜次数',
    `liuyao_count` INT NOT NULL DEFAULT 0 COMMENT '六爻占卜次数',
    `hehun_count` INT NOT NULL DEFAULT 0 COMMENT '合婚次数',
    `daily_fortune_count` INT NOT NULL DEFAULT 0 COMMENT '每日运势查看次数',
    `order_count` INT NOT NULL DEFAULT 0 COMMENT '订单数',
    `order_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
    `paid_count` INT NOT NULL DEFAULT 0 COMMENT '支付成功订单数',
    `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际支付金额',
    `refund_count` INT NOT NULL DEFAULT 0 COMMENT '退款订单数',
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
    `pv_count` INT NOT NULL DEFAULT 0 COMMENT '页面浏览量',
    `uv_count` INT NOT NULL DEFAULT 0 COMMENT '独立访客数',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_stat_date` (`stat_date`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='网站每日统计表';

COMMIT;
