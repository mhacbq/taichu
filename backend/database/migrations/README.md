# 数据库表创建脚本

## 文件说明

本目录包含了太初命理网站所有缺少的数据库表创建脚本。

## 文件列表

### 单个表创建脚本（推荐按需执行）

| 文件名 | 表名 | 说明 |
|--------|------|------|
| `20250316_create_feedback_table.sql` | `feedback` | 用户反馈表 |
| `20250316_create_checkin_record_table.sql` | `checkin_record` | 每日签到记录表 |
| `20250316_create_hehun_records_table.sql` | `hehun_records` | 八字合婚记录表 |
| `20250316_create_notification_tables.sql` | `tc_notification`, `tc_notification_setting`, `tc_push_device` | 通知相关表 |
| `20250316_create_points_history_table.sql` | `points_history`, `points_exchange` | 积分历史记录表 |
| `20250316_create_tc_points_record_table.sql` | `tc_points_record` | 积分记录表（模型使用） |
| `20250316_create_tc_points_exchange_table.sql` | `tc_points_exchange` | 积分兑换记录表（模型使用） |
| `20250316_create_tc_points_product_table.sql` | `tc_points_product` | 积分商品表（模型使用） |
| `20250316_create_share_log_table.sql` | `tc_share_log` | 分享记录表 |
| `20250316_create_task_tables.sql` | `tc_task_log`, `tc_checkin_log` | 任务记录表和签到日志表 |
| `20250316_create_tc_invite_record_table.sql` | `tc_invite_record` | 邀请记录表（模型使用） |
| `20250316_create_tc_recharge_order_table.sql` | `tc_recharge_order` | 充值订单表（模型使用） |
| `20250316_create_tc_tarot_record_table.sql` | `tc_tarot_record` | 塔罗占卜记录表（模型使用） |
| `20250316_create_tc_daily_fortune_table.sql` | `tc_daily_fortune` | 每日运势表（模型使用） |
| `20250316_create_tc_bazi_record_table.sql` | `tc_bazi_record` | 八字排盘记录表（模型使用） |
| `20250316_create_payment_configs_table.sql` | `payment_configs` | 支付配置表 |
| `20250316_create_sms_codes_table.sql` | `sms_codes` | 短信验证码表 |
| `20250316_create_sms_configs_table.sql` | `sms_configs` | 短信配置表 |
| `20250316_create_cms_tables.sql` | `pages`, `page_versions`, `page_drafts`, `upload_files` | CMS相关表 |
| `20250316_create_ai_prompts_table.sql` | `ai_prompts` | AI提示词表 |
| `20250316_create_tarot_spreads_table.sql` | `tarot_spreads` | 塔罗牌阵表 |
| `20250316_create_content_tables.sql` | `faqs`, `daily_fortune_templates`, `testimonials`, `site_contents`, `question_templates` | 内容管理表 |

### 汇总执行脚本（一键创建所有表）

| 文件名 | 包含表数 | 说明 |
|--------|---------|------|
| `20250316_create_all_missing_tables.sql` | 24个表 | 包含所有缺少表的完整创建脚本 |

## 使用方法

### 方法一：一键创建所有表（推荐）

```bash
# 登录MySQL
mysql -u root -p taichu

# 执行完整脚本
source 20250316_create_all_missing_tables.sql
```

### 方法二：按需创建单个表

```bash
# 登录MySQL
mysql -u root -p taichu

# 执行单个脚本
source 20250316_create_feedback_table.sql
```

## 表名说明

### 前缀说明

- `tc_` 前缀的表：与代码模型直接对应的表名
- 无前缀的表：代码中使用的表名（如 `feedback`, `pages` 等）

### 表名对应关系

| 代码中使用的表名 | 模型中定义的表名 | 说明 |
|-----------------|-----------------|------|
| `feedback` | `feedback` | 用户反馈表 |
| `checkin_record` | `checkin_record` | 签到记录表 |
| `hehun_records` | `hehun_records` | 合婚记录表 |
| `tc_notification` | `tc_notification` | 通知表 |
| `tc_points_record` | `tc_points_record` | 积分记录表 |
| `tc_points_exchange` | `tc_points_exchange` | 积分兑换记录表 |
| `tc_points_product` | `tc_points_product` | 积分商品表 |
| `tc_recharge_order` | `tc_recharge_order` | 充值订单表 |
| `tc_tarot_record` | `tc_tarot_record` | 塔罗记录表 |
| `tc_daily_fortune` | `tc_daily_fortune` | 每日运势表 |
| `tc_bazi_record` | `tc_bazi_record` | 八字记录表 |
| `payment_configs` | `payment_configs` | 支付配置表 |
| `sms_codes` | `sms_codes` | 短信验证码表 |
| `sms_configs` | `sms_configs` | 短信配置表 |

## 默认数据

以下表创建时会插入默认数据：

1. `tc_points_product` - 默认积分商品（积分充值、VIP会员）
2. `ai_prompts` - 默认AI提示词（八字分析、塔罗解读等）
3. `tarot_spreads` - 默认塔罗牌阵（单张牌、三张牌、凯尔特十字等）
4. `faqs` - 默认常见问题
5. `testimonials` - 默认用户评价
6. `site_contents` - 默认网站内容
7. `question_templates` - 默认问题模板

## 注意事项

1. 所有表使用 `utf8mb4` 字符集和 `utf8mb4_unicode_ci` 排序规则
2. 所有表使用 `InnoDB` 引擎
3. 所有表都包含 `created_at` 和 `updated_at` 时间戳字段
4. 关键字段都建立了索引以优化查询性能
5. JSON字段用于存储灵活的结构化数据
