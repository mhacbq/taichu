# ⚡ 表名统一 - 快速参考卡

## 📌 一句话总结

**3 张表的名称已统一为 `tc_` 前缀，代码与数据库保持一致。**

## 🔄 变更对照表

| 功能 | 旧表名 | 新表名 | PHP模型 | 状态 |
|------|--------|--------|---------|------|
| SMS验证码 | `sms_codes` | `tc_sms_code` | `SmsCode.php` | ✅ 已更新 |
| SMS配置 | `sms_configs` | `tc_sms_config` | `SmsConfig.php` | ✅ 已更新 |
| 支付配置 | `payment_configs` | `tc_payment_config` | `PaymentConfig.php` | ✅ 已更新 |

## 🚀 立即执行

### 本地/开发环境

```bash
# 已有旧表
mysql -u root -p taichu < database/20260318_unify_table_names.sql

# 或全新环境
mysql -u root -p taichu < database/20260318_create_missing_tables.sql

# 验证
mysql -u root -p taichu -e "SHOW TABLES LIKE 'tc_%';"

# 重启应用
systemctl restart php-fpm
```

### 可选：Docker 环境（仅仍在使用容器时）

> 当前本地默认环境是 phpstudy / 本机 MySQL，上面的“本地/开发环境”命令应优先适用。下面这组 Docker 命令只给仍在跑旧容器链路的场景参考。

```bash
# 进入 MySQL 容器
docker exec -it mysql_container mysql -u root -p taichu < database/20260318_unify_table_names.sql

# 或
docker exec -it mysql_container bash
mysql -u root -p -e "source /sql/20260318_unify_table_names.sql;"
```

## 📝 改动清单

✅ **代码改动**
- `backend/app/model/SmsCode.php` → `protected $name = 'tc_sms_code';`
- `backend/app/model/SmsConfig.php` → `protected $name = 'tc_sms_config';`
- `backend/app/model/PaymentConfig.php` → `protected $name = 'tc_payment_config';`

✅ **脚本创建**
- `database/20260318_unify_table_names.sql` - 表重命名
- `database/20260318_create_missing_tables.sql` - 包含新表定义

✅ **文档更新**
- `backend/database/TABLE_REFERENCE.md` - 表对照表
- `DATABASE_REPAIR_SUMMARY.md` - 状态更新至 99%

## 🎯 验证清单

执行后检查以下内容：

- [ ] `tc_sms_code` 表存在
- [ ] `tc_sms_config` 表存在  
- [ ] `tc_payment_config` 表存在
- [ ] 应用可正常启动
- [ ] 前端登录页面可获取验证码
- [ ] 前端充值页面可正常显示
- [ ] 后台短信配置页面可访问
- [ ] 后台支付配置页面可访问

## ⚠️ 常见问题

**Q: 我应该选择哪个脚本？**  
A: 
- 已有旧表 → 用 `20260318_unify_table_names.sql`
- 全新环境 → 用 `20260318_create_missing_tables.sql`

**Q: 执行脚本前需要备份吗？**  
A: 是的，特别是生产环境。使用 `mysqldump` 备份。

**Q: 表重命名会丢失数据吗？**  
A: 不会。`ALTER TABLE ... RENAME` 只改名，数据完全保留。

**Q: 应用启动失败怎么办？**  
A: 
1. 检查 `protected $name` 是否已更新
2. 清缓存：`php think cache:clear`
3. 重启 PHP-FPM

## 📚 详细文档

| 文件 | 用途 |
|-----|-----|
| `TABLE_NAME_SOLUTION_SUMMARY.md` | 完整解决方案说明 |
| `database/TABLE_NAME_UNIFICATION_GUIDE.md` | 详细执行指南 + 故障排查 |
| `DATABASE_REPAIR_SUMMARY.md` | 整体修复进度报告 |

## 💾 重要文件位置

```
项目根目录/
├── database/
│   ├── 20260318_unify_table_names.sql          ← 表重命名脚本
│   ├── 20260318_create_missing_tables.sql      ← 表创建脚本
│   └── TABLE_NAME_UNIFICATION_GUIDE.md         ← 执行指南
├── backend/
│   ├── app/model/SmsCode.php                   ← 已更新 ✅
│   ├── app/model/SmsConfig.php                 ← 已更新 ✅
│   ├── app/model/PaymentConfig.php             ← 已更新 ✅
│   └── database/TABLE_REFERENCE.md             ← 已更新 ✅
├── TABLE_NAME_SOLUTION_SUMMARY.md              ← 完整方案
└── DATABASE_REPAIR_SUMMARY.md                  ← 状态报告 ✅
```

## ⏱️ 预计时间

- 备份：5-10 分钟（数据量决定）
- 执行脚本：< 1 分钟
- 验证测试：5 分钟
- **总计**：15-20 分钟

## 📞 技术支持

遇到问题？查看：
1. `database/TABLE_NAME_UNIFICATION_GUIDE.md` 的"故障排查"部分
2. `TABLE_NAME_SOLUTION_SUMMARY.md` 中的"后续执行步骤"

---

**最后更新**：2026-03-18  
**状态**：✅ 已完成所有代码改动，等待数据库迁移  
**下一步**：执行指定的 SQL 脚本

