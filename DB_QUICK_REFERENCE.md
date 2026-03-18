# 太初命理系统 - 数据库表快速参考卡

> 💡 一页纸总结：所有表的名称、功能、关键字段

---

## 🚀 超快速导航

**查找表的最快方式**：Ctrl+F 搜索表名或功能关键词

---

## 📌 表清单（按功能分类）

### 👤 用户系统 (8)

| # | 表名 | 功能 | 关键字段 | 行数 | 优先级 |
|---|------|------|--------|------|--------|
| 1 | `users` | 用户账户主表 | user_id, phone, nickname, points, vip_level | ⭐⭐⭐ | P0 |
| 2 | `user_balances` | 用户积分余额 | user_id, total_points, used_points, balance | ⭐⭐ | P1 |
| 3 | `user_vip_info` | VIP信息 | user_id, vip_level, vip_start_at, vip_end_at | ⭐⭐ | P1 |
| 4 | `user_invites` | 邀请关系 | user_id, inviter_id, invited_phone | ⭐ | P2 |
| 5 | `tc_admin_user` | 管理员账户 | admin_id, username, password, role_id | ⭐⭐ | P1 |
| 6 | `tc_admin_role` | 管理员角色 | role_id, role_name, permissions | ⭐ | P2 |
| 7 | `tc_admin_user_role` | 用户-角色关联 | admin_id, role_id | ⭐ | P2 |
| 8 | `tc_admin_permission` | 权限定义 | permission_id, permission_name, description | ⭐ | P2 |

### 💰 积分与财务 (8)

| # | 表名 | 功能 | 关键字段 | 用途 | 优先级 |
|---|------|------|--------|------|--------|
| 1 | `points_records` | 积分交易流水 | user_id, type, points, action, created_at | 核心交易记录 | P0 |
| 2 | `points_history` | 积分历史 | user_id, points, balance, action | 报表统计 | P1 |
| 3 | `points_exchange` | 积分兑换 | user_id, product_id, points, status | 积分兑换 | P1 |
| 4 | `tc_points_config` | 积分配置 | config_key, config_value | 成本配置 | P0 |
| 5 | `recharge_orders` | 充值订单 | order_no, user_id, amount, status | 财务记录 | P0 |
| 6 | `tc_vip_order` | VIP订单 | order_no, user_id, vip_level, vip_end_at | VIP管理 | P0 |
| 7 | `tc_task_log` | 任务完成记录 | user_id, task_type, points, created_at | 任务奖励 | P1 |
| 8 | `checkin_record` | 签到记录 | user_id, date, consecutive_days, points | 签到管理 | P1 |

### 🔌 配置与服务 (2)

| # | 表名 | 功能 | 关键字段 | 必需 | 优先级 |
|---|------|------|--------|------|--------|
| 1 | `tc_payment_config` | 支付配置 | type, mch_id, app_id, api_key, is_enabled | ✅ 必需 | P0 |
| 2 | `tc_sms_config` | 短信配置 | provider, secret_id, secret_key, sign_name | ✅ 必需 | P0 |

### 🎴 命理功能 (14)

#### 八字系统
| 表名 | 功能 | 关键字段 | 优先级 |
|------|------|--------|--------|
| `bazi_records` | 八字排盘 | user_id, birth_date, bazi, analysis, ai_analysis | P0 |
| `hehun_records` | 合婚记录 | user_id, male_name, female_name, score, result | P0 |

#### 塔罗系统
| 表名 | 功能 | 关键字段 | 优先级 |
|------|------|--------|--------|
| `tarot_cards` | 塔罗牌库 | id, name, number, suit, meaning, image | P1 |
| `tarot_spreads` | 塔罗牌阵 | id, name, code, card_count, positions | P1 |
| `tc_tarot_record` | 占卜记录 | user_id, spread_type, cards, interpretation | P0 |

#### 六爻系统
| 表名 | 功能 | 关键字段 | 优先级 |
|------|------|--------|--------|
| `tc_liuyao_record` | 六爻占卜 | user_id, question, hexagram_original, analysis | P0 |

#### 黄历与择吉
| 表名 | 功能 | 关键字段 | 优先级 |
|------|------|--------|--------|
| `tc_almanac` | 黄历数据 | solar_date, lunar_date, ganzhi, yi, ji, shichen | P1 |
| `tc_shensha` | 神煞库 | name, type, category, description, check_rule | P1 |

#### 知识库
| 表名 | 功能 | 关键字段 | 优先级 |
|------|------|--------|--------|
| `tc_article_category` | 分类 | id, name, slug, parent_id, sort_order | P1 |
| `tc_article` | 文章 | id, category_id, title, content, status | P0 |

### 📝 内容管理 (7)

| # | 表名 | 功能 | 关键字段 | 场景 | 优先级 |
|---|------|------|--------|------|--------|
| 1 | `pages` | 页面主表 | id, slug, content, status, version | 动态页面 | P0 |
| 2 | `page_versions` | 版本历史 | page_id, version, content, created_by | 版本控制 | P1 |
| 3 | `page_drafts` | 草稿 | page_id, content, editor_id | 编辑管理 | P1 |
| 4 | `page_recycle` | 回收站 | page_id, deleted_by, deleted_at | 数据恢复 | P1 |
| 5 | `faqs` | 常见问题 | id, question, answer, category | 用户FAQ | P2 |
| 6 | `site_contents` | 网站内容 | page, key, value, type | 内容块 | P1 |
| 7 | `testimonials` | 用户见证 | user_id, content, service, rating | 营销 | P2 |

### 🤖 AI与模板 (3)

| # | 表名 | 功能 | 关键字段 | 优先级 |
|---|------|------|--------|--------|
| 1 | `ai_prompts` | AI提示词 | code, content, category, model, is_active | P1 |
| 2 | `question_templates` | 问题模板 | category, title, content, applicable_to | P2 |
| 3 | `daily_fortune_templates` | 每日运势模板 | zodiac, overall, love, career, lucky_* | P1 |

### 📊 运营与日志 (4)

| # | 表名 | 功能 | 关键字段 | 增长速度 | 优先级 |
|---|------|------|--------|---------|--------|
| 1 | `operation_logs` | 操作日志 | admin_id, action, before_data, after_data | ⚡ 快 | P1 |
| 2 | `feedback` | 用户反馈 | user_id, type, content, status, reply | 🐢 慢 | P2 |
| 3 | `tc_share_log` | 分享记录 | user_id, type, platform, share_code | 🚶 中 | P1 |
| 4 | `tc_checkin_log` | 签到日志 | user_id, date, consecutive_days, points | ⚡ 快 | P1 |

### 📁 存储与验证 (2)

| # | 表名 | 功能 | 关键字段 | 优先级 |
|---|------|------|--------|--------|
| 1 | `upload_files` | 文件记录 | file_name, file_url, file_size, mime_type | P1 |
| 2 | `tc_sms_code` | 短信验证码 | phone, code, type, expire_time, is_used | P0 |

### 🔍 SEO（可选） (6)

| # | 表名 | 功能 | 是否必需 | 优先级 |
|---|------|------|---------|--------|
| 1 | `tc_seo_config` | SEO页面配置 | ❌ | P3 |
| 2 | `tc_seo_keywords` | 关键词追踪 | ❌ | P3 |
| 3 | `tc_seo_indexed_pages` | 页面收录 | ❌ | P3 |
| 4 | `tc_seo_submissions` | 提交记录 | ❌ | P3 |
| 5 | `tc_seo_traffic_daily` | 流量统计 | ❌ | P3 |
| 6 | `tc_seo_robots` | robots配置 | ❌ | P3 |

### ⚙️ 系统配置 (1)

| # | 表名 | 功能 | 关键字段 | 优先级 |
|---|------|------|--------|--------|
| 1 | `system_config` | 系统配置 | config_key, config_value, category | P0 |

---

## 🎯 按用途快速查表

### "我要查用户的..."
- **积分**: `points_records`, `user_balances`
- **VIP状态**: `user_vip_info`, `tc_vip_order`
- **排盘历史**: `bazi_records`, `tc_tarot_record`, `tc_liuyao_record`
- **邀请关系**: `user_invites`
- **签到记录**: `checkin_record`, `tc_checkin_log`

### "我要查订单的..."
- **充值订单**: `recharge_orders`
- **VIP订单**: `tc_vip_order`
- **支付状态**: `recharge_orders` (field: status, payment_type)
- **支付配置**: `tc_payment_config`

### "我要查内容的..."
- **页面内容**: `pages`, `page_versions`
- **文章**: `tc_article`, `tc_article_category`
- **常见问题**: `faqs`
- **网站配置**: `system_config`, `site_contents`

### "我要查命理的..."
- **八字**: `bazi_records`, `hehun_records`
- **塔罗**: `tc_tarot_record`, `tarot_cards`, `tarot_spreads`
- **六爻**: `tc_liuyao_record`
- **黄历**: `tc_almanac`, `tc_shensha`

### "我要查管理的..."
- **操作日志**: `operation_logs`
- **用户反馈**: `feedback`
- **权限管理**: `tc_admin_role`, `tc_admin_permission`
- **管理员**: `tc_admin_user`, `tc_admin_user_role`

---

## ⚡ 高频查询语句示例

```sql
-- 查用户积分余额
SELECT total_points, used_points 
FROM user_balances 
WHERE user_id = ?;

-- 查用户积分交易记录
SELECT * FROM points_records 
WHERE user_id = ? 
ORDER BY created_at DESC 
LIMIT 10;

-- 查用户VIP状态
SELECT vip_level, vip_end_at 
FROM users 
WHERE user_id = ?;

-- 查八字排盘记录
SELECT * FROM bazi_records 
WHERE user_id = ? 
ORDER BY created_at DESC;

-- 查某日黄历
SELECT * FROM tc_almanac 
WHERE solar_date = CURDATE();

-- 查页面内容
SELECT content FROM pages 
WHERE slug = 'about';
```

---

## 🚨 需要加密的敏感字段

```
tc_payment_config.api_key        ← 微信API密钥
tc_payment_config.mch_id         ← 商户号
tc_sms_config.secret_key         ← 短信服务密钥
tc_sms_config.secret_id          ← 短信服务ID
users.phone                      ← 用户手机号(建议加密)
recharge_orders.pay_order_no     ← 支付订单(不能删除)
```

---

## 📏 表大小参考

```
小于 1MB       : 配置表、静态数据
1-10MB        : 常用但量不大的表
10-100MB      : 高频表、需要优化索引
100MB+        : 需要考虑分库分表的表

表的预期大小（2年运营，10万用户）:
- points_records: 150MB ⚠️
- operation_logs: 100MB ⚠️
- bazi_records: 50MB
- tc_tarot_record: 80MB
```

---

## 🔄 常用操作

### 创建备份
```bash
mysqldump -u user -p taichu > backup_$(date +%Y%m%d).sql
```

### 查看表大小
```sql
SELECT 
    table_name, 
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.tables
WHERE table_schema = 'taichu'
ORDER BY size_mb DESC;
```

### 查看表行数
```sql
SELECT 
    table_name, 
    table_rows
FROM information_schema.tables
WHERE table_schema = 'taichu'
ORDER BY table_rows DESC;
```

### 优化表
```sql
OPTIMIZE TABLE table_name;
```

---

## 📞 快速参考

| 需求 | 查表 |
|------|------|
| 用户是否是VIP | `user_vip_info` 或 `users.vip_level` |
| 用户积分余额 | `user_balances` |
| 某用户的排盘记录 | `bazi_records` 或 `tc_tarot_record` |
| 系统的各项配置 | `system_config` |
| 文字内容(如关于我们) | `pages` |
| 神煞查法规则 | `tc_shensha` |
| 塔罗牌含义 | `tarot_cards` |

---

**版本**: 2026-03-18  
**涵盖表数**: 50+  
**快速参考码**: TAICHU-DB-QR-v1.0

