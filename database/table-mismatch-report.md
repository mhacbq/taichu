# 数据库表缺失检查报告

## 生成时间
2025年3月15日

## 1. SQL脚本中有定义，但代码中未使用的表

| 表名 | 说明 | 建议 |
|------|------|------|
| `tc_user_profile` | 用户资料扩展表 | 如不需要可删除 |
| `tc_points_task` | 积分任务记录表 | 如不需要可删除 |
| `tc_vip_order` | VIP订单表 | 如不需要可删除 |
| `tc_qiming_record` | 取名建议记录表 | 如不需要可删除 |
| `tc_yearly_fortune` | 流年运势记录表 | 如不需要可删除 |
| `tc_payment_config` | 支付配置表 | 代码中使用的是 `payment_configs` |
| `tc_sms_config` | 短信配置表 | 代码中使用的是 `sms_configs` |
| `tc_admin_permission` | 管理员权限表 | 代码中使用但表名一致 |
| `tc_admin_role` | 管理员角色表 | 代码中使用但表名一致 |
| `tc_admin_role_permission` | 角色权限关联表 | 代码中使用但表名一致 |
| `tc_admin_user_role` | 用户角色关联表 | 代码中使用但表名一致 |
| `tc_page` | 页面表 | 代码中使用的是 `pages` |
| `tc_upload_file` | 文件上传表 | 代码中使用的是 `upload_files` |
| `tc_ai_prompt` | AI提示词表 | 代码中使用的是 `ai_prompts` |
| `tc_tarot_card` | 塔罗牌表 | 代码中使用的是 `tarot_cards` |
| `tc_shensha` | 神煞表 | 如不需要可删除 |
| `tc_almanac` | 黄历表 | 如不需要可删除 |
| `tc_knowledge_category` | 命理知识分类表 | 如不需要可删除 |
| `tc_knowledge_article` | 命理知识文章表 | 如不需要可删除 |
| `tc_user_feedback` | 用户反馈表(新) | 代码中使用的是 `feedback` |
| `tc_system_config` (mingli_tables.sql中) | 系统配置表 | 重复定义 |
| `tc_jieqi` | 节气表 | 如不需要可删除 |
| `tc_seo_config` | SEO配置表 | 代码中可能未使用 |
| `tc_seo_keywords` | 关键词排名表 | 代码中可能未使用 |
| `tc_seo_indexed_pages` | 页面收录状态表 | 代码中可能未使用 |
| `tc_seo_submissions` | 搜索引擎提交记录表 | 代码中可能未使用 |
| `tc_seo_traffic_daily` | SEO流量统计表 | 代码中可能未使用 |
| `tc_seo_robots` | robots.txt配置表 | 代码中可能未使用 |

## 2. 代码中使用，但SQL脚本中缺少的表

### ⚠️ 重要：这些表需要立即创建

| 表名 | 在代码中的位置 | 紧急程度 |
|------|---------------|----------|
| `user` / `tc_user` | Dashboard控制器 | 已存在 |
| `bazi_record` / `tc_bazi_record` | Dashboard, Daily控制器 | 已存在 |
| `points_history` | Dashboard控制器 | **缺少** |
| `points_exchange` | Dashboard控制器 | **缺少** |
| `daily_fortune` | Dashboard控制器 | 已存在 |
| `feedback` | Feedback控制器 | **缺少** |
| `checkin_record` | Daily控制器 | **缺少** |
| `notification` / `tc_notification` | Notification控制器 | **缺少** |
| `notification_setting` / `tc_notification_setting` | Notification控制器 | **缺少** |
| `push_device` / `tc_push_device` | Notification控制器 | **缺少** |
| `share_log` / `tc_share_log` | Share控制器 | **缺少** |
| `invite_record` / `tc_invite_record` | Share控制器 | 已存在 |
| `points_product` / `tc_points_product` | PointsShop控制器 | 已存在 |
| `task_log` / `tc_task_log` | Task控制器 | **缺少** |
| `checkin_log` / `tc_checkin_log` | Task控制器 | **缺少** |
| `tarot_record` / `tc_tarot_record` | 模型中定义 | 已存在 |
| `hehun_records` | HehunRecord模型 | **缺少** |
| `points_record` / `tc_points_record` | 模型中定义 | 已存在 |
| `recharge_order` / `tc_recharge_order` | 模型中定义 | 已存在 |
| `sms_code` / `sms_codes` | 模型中定义 | **表名不一致** |
| `sms_config` / `sms_configs` | 模型中定义 | **表名不一致** |
| `payment_config` / `payment_configs` | 模型中定义 | **表名不一致** |
| `admin_log` / `tc_admin_log` | 模型中定义 | 已存在 |
| `admin_role` / `tc_admin_role` | 模型中定义 | 已存在 |
| `admin_permission` / `tc_admin_permission` | 模型中定义 | 已存在 |
| `admin_role_permission` / `tc_admin_role_permission` | 模型中定义 | 已存在 |
| `admin_user_role` / `tc_admin_user_role` | 模型中定义 | 已存在 |
| `pages` | Page模型 | **缺少** |
| `page_versions` | PageVersion模型 | **缺少** |
| `page_drafts` | PageDraft模型 | **缺少** |
| `upload_files` | UploadFile模型 | **缺少** |
| `ai_prompts` | AiPrompt模型 | **缺少** |
| `tarot_cards` | TarotCard模型 | **缺少** |
| `tarot_spreads` | TarotSpread模型 | **缺少** |
| `faqs` | Faq模型 | **缺少** |
| `daily_fortune_templates` | DailyFortuneTemplate模型 | **缺少** |
| `testimonials` | Testimonial模型 | **缺少** |
| `site_contents` | SiteContent模型 | **缺少** |
| `system_config` | SystemConfig模型 | **缺少** |
| `question_templates` | QuestionTemplate模型 | **缺少** |

## 3. 表名不一致问题

| SQL脚本中的表名 | 代码中使用的表名 | 建议统一为 |
|----------------|-----------------|------------|
| `tc_sms_code` | `sms_codes` | `tc_sms_code` |
| `tc_sms_config` | `sms_configs` | `tc_sms_config` |
| `tc_payment_config` | `payment_configs` | `tc_payment_config` |

## 4. 缺少的关键表结构（需要创建）

### 4.1 `points_history` - 积分历史记录表
```sql
CREATE TABLE IF NOT EXISTS `points_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL COMMENT '类型 add/reduce',
    `points` INT NOT NULL COMMENT '变动积分',
    `balance` INT NOT NULL COMMENT '变动后余额',
    `action` VARCHAR(100) NOT NULL COMMENT '动作说明',
    `remark` VARCHAR(500) DEFAULT '' COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分历史记录表';
```

### 4.2 `points_exchange` - 积分兑换记录表
```sql
CREATE TABLE IF NOT EXISTS `points_exchange` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `product_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
    `product_name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `points` INT NOT NULL COMMENT '消耗积分',
    `status` TINYINT DEFAULT 0 COMMENT '状态 0待处理 1已完成 2已取消',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分兑换记录表';
```

### 4.3 `feedback` - 用户反馈表
```sql
CREATE TABLE IF NOT EXISTS `feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL COMMENT '类型 bug/feature/suggestion',
    `title` VARCHAR(200) NOT NULL COMMENT '标题',
    `content` TEXT NOT NULL COMMENT '内容',
    `images` JSON NULL COMMENT '图片列表',
    `contact` VARCHAR(100) DEFAULT '' COMMENT '联系方式',
    `status` TINYINT DEFAULT 0 COMMENT '状态 0待处理 1处理中 2已回复',
    `reply` TEXT COMMENT '回复内容',
    `replied_at` DATETIME NULL COMMENT '回复时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户反馈表';
```

### 4.4 `checkin_record` - 签到记录表
```sql
CREATE TABLE IF NOT EXISTS `checkin_record` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '签到日期',
    `consecutive_days` INT DEFAULT 1 COMMENT '连续签到天数',
    `points` INT DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_date` (`user_id`, `date`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到记录表';
```

### 4.5 `tc_notification` - 通知表
```sql
CREATE TABLE IF NOT EXISTS `tc_notification` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '通知类型',
    `title` VARCHAR(200) NOT NULL COMMENT '标题',
    `content` TEXT COMMENT '内容',
    `data` JSON NULL COMMENT '附加数据',
    `is_read` TINYINT DEFAULT 0 COMMENT '是否已读',
    `read_at` DATETIME NULL COMMENT '阅读时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知表';
```

### 4.6 `tc_notification_setting` - 通知设置表
```sql
CREATE TABLE IF NOT EXISTS `tc_notification_setting` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '通知类型',
    `enabled` TINYINT DEFAULT 1 COMMENT '是否启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_type` (`user_id`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知设置表';
```

### 4.7 `tc_push_device` - 推送设备表
```sql
CREATE TABLE IF NOT EXISTS `tc_push_device` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `device_id` VARCHAR(255) NOT NULL COMMENT '设备ID',
    `platform` VARCHAR(20) NOT NULL COMMENT '平台 ios/android',
    `token` VARCHAR(500) NOT NULL COMMENT '推送令牌',
    `is_active` TINYINT DEFAULT 1 COMMENT '是否激活',
    `last_used_at` DATETIME NULL COMMENT '最后使用时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送设备表';
```

### 4.8 `tc_share_log` - 分享记录表
```sql
CREATE TABLE IF NOT EXISTS `tc_share_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(50) NOT NULL COMMENT '分享类型',
    `platform` VARCHAR(50) NOT NULL COMMENT '分享平台',
    `content_id` INT UNSIGNED DEFAULT 0 COMMENT '内容ID',
    `points_reward` INT DEFAULT 0 COMMENT '奖励积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分享记录表';
```

### 4.9 `tc_task_log` - 任务记录表
```sql
CREATE TABLE IF NOT EXISTS `tc_task_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `task_type` VARCHAR(50) NOT NULL COMMENT '任务类型',
    `task_name` VARCHAR(100) NOT NULL COMMENT '任务名称',
    `points` INT DEFAULT 0 COMMENT '奖励积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_task_type` (`task_type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务记录表';
```

### 4.10 `tc_checkin_log` - 签到日志表（Task控制器使用）
```sql
CREATE TABLE IF NOT EXISTS `tc_checkin_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date` DATE NOT NULL COMMENT '签到日期',
    `consecutive_days` INT DEFAULT 1 COMMENT '连续签到天数',
    `points` INT DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_date` (`user_id`, `date`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到日志表';
```

### 4.11 `hehun_records` - 合婚记录表
```sql
CREATE TABLE IF NOT EXISTS `hehun_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `male_name` VARCHAR(50) DEFAULT '' COMMENT '男方姓名',
    `male_birth_date` DATE NOT NULL COMMENT '男方出生日期',
    `male_birth_time` TIME NOT NULL COMMENT '男方出生时间',
    `female_name` VARCHAR(50) DEFAULT '' COMMENT '女方姓名',
    `female_birth_date` DATE NOT NULL COMMENT '女方出生日期',
    `female_birth_time` TIME NOT NULL COMMENT '女方出生时间',
    `male_bazi` JSON NULL COMMENT '男方八字',
    `female_bazi` JSON NULL COMMENT '女方八字',
    `score` INT DEFAULT 0 COMMENT '合婚评分',
    `result` TEXT COMMENT '合婚结果',
    `analysis` JSON NULL COMMENT '详细分析',
    `is_public` TINYINT DEFAULT 0 COMMENT '是否公开',
    `share_code` VARCHAR(20) DEFAULT '' COMMENT '分享码',
    `view_count` INT DEFAULT 0 COMMENT '查看次数',
    `points_used` INT DEFAULT 0 COMMENT '消耗积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='合婚记录表';
```

## 5. 建议操作

### 立即需要做的：
1. **创建缺少的关键表**：`feedback`, `checkin_record`, `tc_notification`, `tc_share_log`, `hehun_records`
2. **统一表名**：将代码中的 `sms_codes`, `sms_configs`, `payment_configs` 改为 `tc_` 前缀
3. **删除或归档不用的表**：如果确定不需要命理专业功能，可以删除 `tc_shensha`, `tc_almanac` 等表

### 优先级：
- **P0（紧急）**：`feedback`, `checkin_record`, `hehun_records` - 这些是当前功能必需的
- **P1（重要）**：`tc_notification`, `tc_share_log`, `tc_task_log` - 这些表支持扩展功能
- **P2（一般）**：SEO相关表、知识库表 - 如果暂时不用可以先不创建
