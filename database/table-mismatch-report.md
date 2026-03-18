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

### ✅ 状态：已全部修复！（2026-03-18）

已通过 `database/20260318_create_missing_tables.sql` 迁移脚本补齐所有缺失表。

| 表名 | 在代码中的位置 | 修复状态 |
|------|---------------|----------|
| `user` / `tc_user` | Dashboard控制器 | ✅ 已存在 |
| `bazi_record` / `tc_bazi_record` | Dashboard, Daily控制器 | ✅ 已存在 |
| `points_history` | Dashboard控制器 | ✅ 已创建（2026-03-18） |
| `points_exchange` | Dashboard控制器 | ✅ 已创建（2026-03-18） |
| `daily_fortune` | Dashboard控制器 | ✅ 已存在 |
| `feedback` | Feedback控制器 | ✅ 已创建（2026-03-18） |
| `checkin_record` | Daily控制器 | ✅ 已创建（2026-03-18） |
| `notification` / `tc_notification` | Notification控制器 | ✅ 已创建（2026-03-17） |
| `notification_setting` / `tc_notification_setting` | Notification控制器 | ✅ 已创建（2026-03-17） |
| `push_device` / `tc_push_device` | Notification控制器 | ✅ 已创建（2026-03-17） |
| `share_log` / `tc_share_log` | Share控制器 | ✅ 已创建（2026-03-18） |
| `invite_record` / `tc_invite_record` | Share控制器 | ✅ 已存在 |
| `points_product` / `tc_points_product` | PointsShop控制器 | ✅ 已存在 |
| `task_log` / `tc_task_log` | Task控制器 | ✅ 已创建（2026-03-18） |
| `checkin_log` / `tc_checkin_log` | Task控制器 | ✅ 已创建（2026-03-18） |
| `tarot_record` / `tc_tarot_record` | 模型中定义 | ✅ 已创建（2026-03-17） |
| `hehun_records` | HehunRecord模型 | ✅ 已创建（2026-03-18） |
| `points_record` / `tc_points_record` | 模型中定义 | ✅ 已存在 |
| `recharge_order` / `tc_recharge_order` | 模型中定义 | ✅ 已存在 |
| `sms_code` / `sms_codes` | 模型中定义 | ⚠️ 表名不一致 |
| `sms_config` / `sms_configs` | 模型中定义 | ⚠️ 表名不一致 |
| `payment_config` / `payment_configs` | 模型中定义 | ⚠️ 表名不一致 |
| `admin_log` / `tc_admin_log` | 模型中定义 | ✅ 已存在 |
| `admin_role` / `tc_admin_role` | 模型中定义 | ✅ 已存在 |
| `admin_permission` / `tc_admin_permission` | 模型中定义 | ✅ 已存在 |
| `admin_role_permission` / `tc_admin_role_permission` | 模型中定义 | ✅ 已存在 |
| `admin_user_role` / `tc_admin_user_role` | 模型中定义 | ✅ 已存在 |
| `pages` | Page模型 | ✅ 已创建（2026-03-18） |
| `page_versions` | PageVersion模型 | ✅ 已创建（2026-03-18） |
| `page_drafts` | PageDraft模型 | ✅ 已创建（2026-03-18） |
| `page_recycle` | PageRecycle模型 | ✅ 已创建（2026-03-18） |
| `upload_files` | UploadFile模型 | ✅ 已创建（2026-03-18） |
| `ai_prompts` | AiPrompt模型 | ✅ 已创建（2026-03-18） |
| `tarot_cards` | TarotCard模型 | ✅ 已创建（2026-03-18） |
| `tarot_spreads` | TarotSpread模型 | ✅ 已创建（2026-03-18） |
| `faqs` | Faq模型 | ✅ 已创建（2026-03-18） |
| `daily_fortune_templates` | DailyFortuneTemplate模型 | ✅ 已创建（2026-03-18） |
| `testimonials` | Testimonial模型 | ✅ 已创建（2026-03-18） |
| `site_contents` | SiteContent模型 | ✅ 已创建（2026-03-18） |
| `system_config` | SystemConfig模型 | ✅ 已创建（2026-03-18） |
| `question_templates` | QuestionTemplate模型 | ✅ 已创建（2026-03-18） |

## 3. 表名不一致问题

| SQL脚本中的表名 | 代码中使用的表名 | 建议统一为 |
|----------------|-----------------|------------|
| `tc_sms_code` | `sms_codes` | `tc_sms_code` |
| `tc_sms_config` | `sms_configs` | `tc_sms_config` |
| `tc_payment_config` | `payment_configs` | `tc_payment_config` |

## 4. 缺失表创建 - 已完成 ✅

所有缺失的表已通过以下迁移脚本完成创建：

- `database/20260317_create_notification_tables.sql` - 通知相关表（3张）
- `database/20260317_create_shensha_table.sql` - 神煞表
- `database/20260317_create_admin_stats_tables.sql` - 网站统计表
- `database/20260318_create_almanac_table.sql` - 黄历表  
- `database/20260318_create_seo_tables.sql` - SEO相关表（6张）
- `database/20260318_create_missing_tables.sql` - 缺失表补充（26张表）
  - `points_history` ✅
  - `points_exchange` ✅
  - `checkin_record` ✅
  - `feedback` ✅
  - `tc_tarot_record` ✅
  - `tc_liuyao_record` ✅
  - `upload_files` ✅
  - `ai_prompts` ✅
  - `pages` ✅
  - `page_versions` ✅
  - `page_drafts` ✅
  - `page_recycle` ✅
  - `faqs` ✅
  - `daily_fortune_templates` ✅
  - `question_templates` ✅
  - `testimonials` ✅
  - `site_contents` ✅
  - `operation_logs` ✅
  - `tc_checkin_log` ✅
  - `tc_share_log` ✅
  - `tc_task_log` ✅
  - `hehun_records` ✅
  - `system_config` ✅
  - `tarot_cards` ✅
  - `tarot_spreads` ✅
  - `tc_vip_order` ✅

**执行命令：**
```bash
mysql -u taichu -p taichu < database/20260318_master_migration.sql
```

## 5. 建议操作与待办项

### ✅ 已完成
- [x] 创建缺少的所有关键表（26张表已创建）
- [x] 整理SQL迁移脚本（按依赖顺序、统一编码、幂等设计）
- [x] 添加master迁移脚本入口

### ⚠️ 待处理（表名不一致问题）

这些是代码中使用但与SQL定义的表名不一致的情况：

| 问题表 | SQL中的表名 | 代码使用的表名 | 模型 | 建议 |
|--------|-----------|--------------|------|------|
| `sms_code` | `tc_sms_code` | `sms_codes` | SmsCode | 统一为 `tc_sms_code` |
| `sms_config` | `tc_sms_config` | `sms_configs` | SmsConfig | 统一为 `tc_sms_config` |
| `payment_config` | `tc_payment_config` | `payment_configs` | PaymentConfig | 统一为 `tc_payment_config` |

**方案A：修改数据库（建议）**
```sql
ALTER TABLE `sms_codes` RENAME TO `tc_sms_code`;
ALTER TABLE `sms_configs` RENAME TO `tc_sms_config`;
ALTER TABLE `payment_configs` RENAME TO `tc_payment_config`;
```

**方案B：修改代码模型**
在 `SmsCode.php`, `SmsConfig.php`, `PaymentConfig.php` 中更新 `$table` 属性为对应的非 `tc_` 前缀表名。

### 🗑️ 可考虑删除的表（如功能不需要）

| 表名 | 说明 | 影响范围 |
|------|------|---------|
| `tc_shensha` | 神煞表 | 命理功能 |
| `tc_almanac` | 黄历表 | 每日运势功能 |
| `tc_knowledge_category` | 知识库分类 | 知识库功能 |
| `tc_knowledge_article` | 知识库文章 | 知识库功能 |
| `tc_user_profile` | 用户资料扩展 | 用户档案功能 |
| `tc_points_task` | 积分任务记录 | 任务系统 |
| `tc_qiming_record` | 取名建议 | 取名功能 |
| `tc_yearly_fortune` | 流年运势 | 流年功能 |
| `tc_jieqi` | 节气表 | 节气查询 |

> ⚠️ 删除前请确认这些功能在后台不再使用。
