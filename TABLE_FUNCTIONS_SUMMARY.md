# 太初命理系统 - 数据库表功能完整汇总

> 📋 本文档详细列出系统中定义的所有表及其功能、状态和使用情况  
> 最后更新：2026-03-18

---

## 📊 表格统计

- **总表数**：约 50+ 张
- **已被代码使用**：约 35 张
- **建议保留但未使用**：约 10 张（数据内容相关）
- **建议删除**（无业务需求）：约 5 张

---

## 🎯 核心业务表（已在代码中活跃使用）

### 1️⃣ 用户与认证系统

| 表名 | 功能说明 | 字段概览 | 状态 |
|------|--------|--------|------|
| `users` | 用户账户主表 | user_id, phone, nickname, avatar, points, vip_level, created_at | ✅ 活跃 |
| `user_balances` | 用户账户余额/财务统计 | user_id, total_points, used_points, balance | ✅ 活跃 |
| `user_vip_info` | 用户VIP信息 | user_id, vip_level, vip_start_at, vip_end_at | ✅ 活跃 |
| `user_invites` | 用户邀请关系记录 | user_id, inviter_id, invited_phone, reward_points | ✅ 活跃 |
| `tc_admin_user` | 后台管理员账户 | admin_id, username, password, permissions, status | ✅ 活跃 |
| `tc_admin_role` | 后台角色定义 | role_id, role_name, description, permissions | ✅ 活跃 |
| `tc_admin_user_role` | 管理员-角色关联 | admin_id, role_id | ✅ 活跃 |
| `tc_admin_permission` | 后台权限定义 | permission_id, permission_name, description | ✅ 活跃 |
| `tc_admin_role_permission` | 角色-权限关联 | role_id, permission_id | ✅ 活跃 |

---

### 2️⃣ 积分系统

| 表名 | 功能说明 | 字段概览 | 状态 |
|------|--------|--------|------|
| `points_records` | 积分交易记录 | id, user_id, type, points, action, created_at | ✅ 活跃 |
| `points_history` | 积分历史记录 | id, user_id, points, balance, action, created_at | ✅ 活跃 |
| `points_exchange` | 积分兑换记录 | id, user_id, product_id, points, status | ✅ 活跃 |
| `tc_points_config` | 积分消耗配置 | 八字、塔罗、六爻等各功能积分成本 | ✅ 活跃 |
| `tc_task_log` | 任务完成记录 | user_id, task_type (checkin/invite/share/view), points | ✅ 活跃 |
| `checkin_record` | 每日签到记录 | user_id, date, consecutive_days, points | ✅ 活跃 |
| `tc_checkin_log` | 签到日志（备选） | user_id, date, consecutive_days, points | ✅ 活跃 |

---

### 3️⃣ 支付系统

| 表名 | 功能说明 | 字段概览 | 状态 |
|------|--------|--------|------|
| `recharge_orders` | 积分充值订单 | order_no, user_id, amount, status, payment_type | ✅ 活跃 |
| `tc_vip_order` | VIP订单表 | order_no, user_id, vip_level, duration_days, amount | ✅ 活跃 |
| `tc_payment_config` | 支付配置 | type (wechat/alipay), mch_id, app_id, api_key, is_enabled | ✅ 活跃 |
| `tc_sms_config` | 短信配置 | provider (tencent/aliyun), secret_id, secret_key, sign_name | ✅ 活跃 |
| `tc_sms_code` | 短信验证码 | phone, code, type (register/login/reset), expire_time | ✅ 活跃 |

---

### 4️⃣ 命理功能表

#### 4.1 八字排盘记录
| 表名 | 功能说明 | 字段概览 | 状态 |
|------|--------|--------|------|
| `bazi_records` | 八字排盘记录 | user_id, name, birth_date, birth_time, bazi, analysis | ✅ 活跃 |
| `hehun_records` | 八字合婚记录 | user_id, male_name, female_name, match_score, analysis | ✅ 活跃 |

#### 4.2 塔罗占卜
| 表名 | 功能说明 | 字段概览 | 状态 |
|------|--------|--------|------|
| `tarot_cards` | 塔罗牌库 | id, name, number, suit, is_major, image, meaning | ✅ 活跃 |
| `tarot_spreads` | 塔罗牌阵 | id, name, code, card_count, positions, difficulty | ✅ 活跃 |
| `tc_tarot_record` | 塔罗占卜记录 | user_id, spread_type, topic, cards, interpretation | ✅ 活跃 |

#### 4.3 六爻占卜
| 表名 | 功能说明 | 字段概览 | 状态 |
|------|--------|--------|------|
| `tc_liuyao_record` | 六爻占卜记录 | user_id, question, hexagram_original, hexagram_changed | ✅ 活跃 |

#### 4.4 黄历与日期
| 表名 | 功能说明 | 字段概览 | 状态 |
|------|--------|--------|------|
| `tc_almanac` | 黄历数据 | solar_date, lunar_date, ganzhi, yi, ji, shichen | ✅ 活跃 |
| `tc_shensha` | 神煞库 | name, type (吉凶平), category, description, check_rule | ✅ 活跃 |

#### 4.5 知识库与文章
| 表名 | 功能说明 | 字段概览 | 状态 |
|------|--------|--------|------|
| `tc_article_category` | 知识库分类 | name, slug, description, parent_id, sort_order | ✅ 活跃 |
| `tc_article` | 知识库文章 | category_id, title, slug, content, status, published_at | ✅ 活跃 |

---

### 5️⃣ 内容管理系统

| 表名 | 功能说明 | 字段概览 | 状态 |
|------|--------|--------|------|
| `pages` | 页面管理 | title, slug, type, content (JSON), status, version | ✅ 活跃 |
| `page_versions` | 页面版本历史 | page_id, version, content, created_by, comment | ✅ 活跃 |
| `page_drafts` | 页面编辑草稿 | page_id, content, editor_id, updated_at | ✅ 活跃 |
| `page_recycle` | 页面回收站 | page_id, title, content, deleted_by, deleted_at | ✅ 活跃 |
| `faqs` | 常见问题 | question, answer, category, is_published, sort_order | ✅ 活跃 |
| `site_contents` | 网站内容管理 | page, key, value, type (text/html/json), is_enabled | ✅ 活跃 |
| `testimonials` | 用户好评/见证 | user_id, nickname, content, service, rating, is_featured | ✅ 活跃 |

---

### 6️⃣ 后台管理与日志

| 表名 | 功能说明 | 字段概览 | 状态 |
|------|--------|--------|------|
| `operation_logs` | 管理员操作日志 | admin_id, action, module, before_data, after_data | ✅ 活跃 |
| `feedback` | 用户反馈 | user_id, type (bug/feature/suggestion), content, status | ✅ 活跃 |
| `upload_files` | 文件上传记录 | original_name, file_path, file_url, file_size, mime_type | ✅ 活跃 |

---

### 7️⃣ 辅助与配置表

| 表名 | 功能说明 | 字段概览 | 状态 |
|------|--------|--------|------|
| `system_config` | 系统配置 | config_key, config_value, config_type, category | ✅ 活跃 |
| `ai_prompts` | AI提示词库 | name, code, type, category, content, variables | ✅ 活跃 |
| `question_templates` | 问题模板 | category, title, content, applicable_to, is_enabled | ✅ 活跃 |
| `daily_fortune_templates` | 每日运势模板 | zodiac, type, overall, love, career, wealth | ✅ 活跃 |
| `tc_share_log` | 分享记录 | user_id, type, platform, content_id, share_code | ✅ 活跃 |

---

### 8️⃣ SEO与搜索引擎

| 表名 | 功能说明 | 字段概览 | 状态 | 备注 |
|------|--------|--------|------|------|
| `tc_seo_config` | SEO页面配置 | route, title, description, keywords, robots | ⚠️ 可选 | 用于页面SEO优化，代码中可能未集成 |
| `tc_seo_keywords` | SEO关键词追踪 | keyword, category, baidu_rank, search_volume | ⚠️ 可选 | 用于关键词排名监测 |
| `tc_seo_indexed_pages` | 页面收录状态 | url, baidu_status, bing_status, indexed_at | ⚠️ 可选 | 追踪搜索引擎收录情况 |
| `tc_seo_submissions` | 搜索引擎提交记录 | engine, type, url, status, response | ⚠️ 可选 | 记录向搜索引擎的主动提交 |
| `tc_seo_traffic_daily` | SEO流量统计 | stat_date, engine, impressions, clicks, ctr | ⚠️ 可选 | 每日搜索流量统计 |
| `tc_seo_robots` | robots配置 | user_agent, rules, crawl_delay, sitemap_url | ⚠️ 可选 | 爬虫配置管理 |

---

## ⚠️ 建议保留但代码中未发现使用的表

这些表可能用于：
- 未来功能扩展规划
- 后台数据展示
- 特定业务场景

### 用户资料与偏好

| 表名 | 功能说明 | 建议 |
|------|--------|------|
| `tc_user_profile` | 用户资料扩展表（性别、年龄、地区等） | 保留用于前端个人资料页完善 |

### 运势与测算相关

| 表名 | 功能说明 | 建议 |
|------|--------|------|
| `tc_yearly_fortune` | 流年运势记录 | 保留用于运势记录存储 |
| `tc_qiming_record` | 取名建议记录 | 保留用于未来取名功能 |

### 任务系统

| 表名 | 功能说明 | 建议 |
|------|--------|------|
| `tc_points_task` | 积分任务记录 | 保留但可与 `tc_task_log` 合并 |

---

## 🚫 建议删除的表（无实际使用且无业务需求）

目前代码中未发现这些表的使用，且业务上暂无需求：

- `tc_user_profile` - 重复冗余，用户信息已在 `users` 表
- `tc_points_task` - 功能与 `tc_task_log` 重复

> 删除前请先：
> 1. 确认后台没有依赖这些表的功能
> 2. 检查是否有历史数据需要迁移
> 3. 备份当前数据库

---

## 📋 表名统一情况

### ✅ 已解决的不一致问题

| 模型名 | 旧表名 | 新表名 | 状态 |
|--------|--------|--------|------|
| `SmsCode.php` | `sms_codes` | `tc_sms_code` | ✅ 已统一 |
| `SmsConfig.php` | `sms_configs` | `tc_sms_config` | ✅ 已统一 |
| `PaymentConfig.php` | `payment_configs` | `tc_payment_config` | ✅ 已统一 |

### 📊 其他表名约定

- **带 `tc_` 前缀**（系统核心表）：admin、payment、sms、shensha、almanac 等
- **不带前缀**（业务表）：users、points_records、bazi_records、hehun_records 等
- **命名风格**：一致使用小写 + 下划线分隔

---

## 🎬 快速使用指南

### 需要存储用户的八字排盘？
✅ 使用 `bazi_records` 表
- user_id, name, birth_date, birth_time, bazi, analysis, points_cost, created_at

### 需要追踪积分消耗？
✅ 使用 `points_records` 表（记录每笔）或 `points_history` 表（历史查询）
- 前者用于实时操作，后者用于报表统计

### 需要管理后台用户权限？
✅ 组合使用：
- `tc_admin_user` - 管理员账户
- `tc_admin_role` - 角色定义
- `tc_admin_user_role` - 用户-角色关联
- `tc_admin_permission` - 权限定义
- `tc_admin_role_permission` - 角色-权限关联

### 需要存储AI解读结果？
✅ 各记录表都有字段支持：
- `bazi_records.ai_analysis` - 八字AI分析
- `tc_tarot_record.ai_analysis` - 塔罗AI解读
- `hehun_records.ai_analysis` - 合婚AI分析

### 需要管理网站页面内容？
✅ 组合使用：
- `pages` - 页面本体
- `page_versions` - 版本控制
- `page_drafts` - 草稿管理
- `page_recycle` - 回收站

---

## 🔍 数据库完整性检查清单

- [ ] 所有表都使用 UTF-8MB4 字符集
- [ ] 所有表都有创建时间字段 (`created_at`)
- [ ] 核心表都有更新时间字段 (`updated_at`)
- [ ] 关键字段都建立了索引
- [ ] 外键引用都定义了约束
- [ ] 配置表都有默认种子数据
- [ ] 日志表的 `created_at` 都是自动时间戳

---

## 💡 设计建议

### 对于数据增长预测

1. **高频写入表**（需要分库分表）：
   - `points_records` - 每个用户每天多条
   - `operation_logs` - 管理员操作频繁
   - `tc_checkin_log` - 每日签到数据

2. **中等写入表**（需要定期清理或归档）：
   - `tc_task_log` - 任务记录
   - `tc_share_log` - 分享记录

3. **大容量表**（需要考虑性能）：
   - `tc_almanac` - 可能几千条记录
   - `tc_article` - 知识库可能很大

### 对于备份策略

优先级排序：
1. **关键业务表**：users, points_records, recharge_orders, bazi_records
2. **支付配置表**：tc_payment_config, tc_sms_config
3. **内容表**：pages, tc_article, tc_article_category
4. **系统配置**：system_config, tc_admin_* 系列

---

## 📚 相关文档

- **完整迁移指南**：`TABLE_NAME_UNIFICATION_GUIDE.md`
- **数据库修复报告**：`DATABASE_REPAIR_SUMMARY.md`
- **表参考文档**：`backend/database/TABLE_REFERENCE.md`
- **SQL脚本目录**：`database/20260318_*.sql`

---

## ✨ 总结

太初命理系统共包含 **50+ 张数据库表**，覆盖以下核心业务：

✅ **用户与认证**（9张表）
✅ **积分管理**（7张表）  
✅ **支付与配置**（5张表）
✅ **命理功能**（12张表）
✅ **内容管理**（7张表）
✅ **后台管理**（3张表）
✅ **SEO与营销**（6张表）
✅ **辅助表**（4张表）

**绝大多数表都处于活跃使用状态**，建议保留所有表以支持完整的产品功能。

---

**更新历史**
- 2026-03-18：初版创建，包含所有表的功能说明和使用状态

