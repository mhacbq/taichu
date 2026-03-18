# 太初命理网站 - 数据库迁移目录

## 目录结构

```
database/
├── backup/                             # 原始建库脚本（历史存档）
│   ├── 01_create_database.sql          # 建库与用户授权
│   ├── 02_create_tables.sql            # 核心业务表（带 tc_ 前缀）
│   ├── 03_insert_basic_data.sql        # 基础数据
│   ├── 04_insert_test_data.sql         # 测试数据（仅开发环境）
│   ├── 05_mingli_tables.sql            # 命理专业表（神煞/黄历/知识库）
│   └── 06_seo_tables.sql               # SEO相关表
│
├── 20260317_*.sql                      # 2026-03-17 新增迁移
├── 20260318_*.sql                      # 2026-03-18 新增迁移
├── 20260318_create_missing_tables.sql  # 缺失表补充（22张表）
├── 20260318_master_migration.sql       # ← 全量迁移主入口
├── table-mismatch-report.md            # 表名不一致检查报告
└── README.md                           # 本文件
```

---

## 快速部署（首次）

```bash
# 1. 建库（需要 root 权限）
mysql -u root -p < database/backup/01_create_database.sql

# 2. 核心业务表
mysql -u taichu -p taichu < database/backup/02_create_tables.sql

# 3. 基础数据（积分规则、支付配置等）
mysql -u taichu -p taichu < database/backup/03_insert_basic_data.sql

# 4. 运行全量迁移（补充2026-03-17/18所有新表与字段）
mysql -u taichu -p taichu < database/20260318_master_migration.sql
```

> ⚠️ SOURCE 指令需要在 mysql 客户端中执行（不是 shell）：
> ```sql
> mysql> USE taichu;
> mysql> SOURCE /path/to/database/20260318_master_migration.sql;
> ```

---

## 各迁移文件说明

| 文件 | 说明 | 幂等 |
|------|------|------|
| `20260317_create_admin_users_table.sql` | 管理员主表、角色表、默认 admin 账号 | ✅ |
| `20260317_create_anticheat_tables.sql`  | 反作弊事件/规则/设备表 | ✅ |
| `20260317_create_knowledge_tables.sql`  | 知识库分类与文章表 | ✅ |
| `20260317_create_notification_tables.sql` | 站内通知/通知设置/推送设备表 | ✅ |
| `20260317_create_shensha_table.sql`     | 神煞主表 + 15条默认种子 | ✅ |
| `20260317_create_admin_stats_tables.sql` | 网站每日统计表 | ✅ |
| `20260317_add_points_record_compat_fields.sql` | tc_points_record 兼容字段 | ✅ |
| `20260317_add_recharge_order_refund_fields.sql` | tc_recharge_order 退款审计字段 | ⚠️ 用 IF NOT EXISTS 包裹执行 |
| `20260318_create_almanac_table.sql`     | 黄历数据表 | ✅ |
| `20260318_create_seo_tables.sql`        | SEO配置/关键词/收录/流量/robots | ✅ |
| `20260318_add_points_record_audit_fields.sql` | tc_points_record amount/balance/reason | ✅ |
| `20260318_fix_admin_role_permissions.sql` | 补充运营员黄历权限 | ✅ |
| `20260318_fix_hehun_points_config.sql`  | 合婚功能 system_config 配置 | ✅ |
| `20260318_fix_knowledge_category_encoding.sql` | 修复知识库分类中文名乱码 | ✅ |
| **`20260318_create_missing_tables.sql`** | **补充22张缺失表（见下表）** | **✅** |
| `20260318_master_migration.sql`         | 全量主脚本（按序 SOURCE 所有上述文件） | ✅ |

---

## 缺失表补充清单（20260318_create_missing_tables.sql）

| 表名 | 用途 | 模型/控制器 |
|------|------|-------------|
| `hehun_records` | 八字合婚记录（兼容旧 tc_hehun_record） | `HehunRecord` |
| `system_config` | 系统配置（兼容旧 tc_system_config） | `SystemConfig` |
| `tarot_cards` | 塔罗牌基础数据（兼容旧 tc_tarot_card） | `TarotCard` |
| `tarot_spreads` | 塔罗牌阵定义 | `TarotSpread` |
| `tc_tarot_record` | 塔罗占卜历史记录 | 后台统计/Share |
| `tc_liuyao_record` | 六爻占卜历史记录 | 后台统计/Share |
| `upload_files` | 文件上传记录（兼容旧 tc_upload_file） | `UploadFile` |
| `ai_prompts` | AI提示词配置（兼容旧 tc_ai_prompt） | `AiPrompt` |
| `pages` | 页面管理（兼容旧 tc_page） | `Page` |
| `page_versions` | 页面版本历史 | `PageVersion` |
| `page_drafts` | 页面草稿 | `PageDraft` |
| `page_recycle` | 页面回收站 | `PageRecycle` |
| `faqs` | 常见问题（兼容旧 tc_faq） | `Faq` |
| `feedback` | 用户反馈（兼容旧 tc_feedback） | `Feedback` |
| `daily_fortune_templates` | 每日运势模板 | `DailyFortuneTemplate` |
| `question_templates` | 占卜问题模板 | `QuestionTemplate` |
| `testimonials` | 用户好评/见证 | `Testimonial` |
| `site_contents` | 网站内容管理 | `SiteContent` |
| `operation_logs` | 管理员操作日志 | `OperationLog` |
| `tc_checkin_log` | 用户签到日志 | Task/Daily |
| `tc_share_log` | 内容分享记录 | Share |
| `tc_task_log` | 任务完成记录 | Task |
| `tc_vip_order` | VIP订单（兼容旧结构） | 后台统计 |

---

## 注意事项

1. **字符集**：所有表使用 `utf8mb4` + `utf8mb4_unicode_ci`，确保正确支持 emoji 与中文。
2. **幂等性**：所有建表语句使用 `CREATE TABLE IF NOT EXISTS`，所有插入使用 `ON DUPLICATE KEY UPDATE`，可安全重复执行。
3. **backup/ 目录**：存放原始历史脚本，已替代为带日期的迁移文件，不再修改。
4. **backend/database-fix*.sql**：旧版临时补丁文件，已被本目录的迁移文件取代，可在确认部署完成后归档或删除。
5. **编码问题**：2026-03-18 已统一将所有 SQL 文件重新保存为 UTF-8（无 BOM）编码。

---

## 默认管理员账号

| 字段 | 值 |
|------|----|
| 用户名 | `admin` |
| 密码 | `admin123` |
| 角色 | 超级管理员 |

> ⚠️ **生产环境务必立即修改默认密码！**

---

*最后更新：2026-03-18*

