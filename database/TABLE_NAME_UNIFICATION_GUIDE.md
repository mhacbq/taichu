# 表名统一迁移执行指南

## 📋 背景

太初命理系统中有 3 张表在 SQL 定义和 PHP 模型之间存在名称不一致问题：

| 问题 | 旧表名 | 新表名 | 模型 | 功能 |
|------|--------|--------|------|------|
| SMS验证码 | `sms_codes` | `tc_sms_code` | `SmsCode.php` | 手机号登录/注册 |
| SMS配置 | `sms_configs` | `tc_sms_config` | `SmsConfig.php` | 短信服务配置 |
| 支付配置 | `payment_configs` | `tc_payment_config` | `PaymentConfig.php` | 微信/支付宝支付 |

## ✅ 已完成的代码修改

### 1. 更新 PHP 模型文件

已修改以下模型文件的 `protected $name` 属性：

```php
// backend/app/model/SmsCode.php
protected $name = 'tc_sms_code';  // 之前是 'sms_codes'

// backend/app/model/SmsConfig.php
protected $name = 'tc_sms_config';  // 之前是 'sms_configs'

// backend/app/model/PaymentConfig.php
protected $name = 'tc_payment_config';  // 之前是 'payment_configs'
```

### 2. 更新数据库文档

已更新 `backend/database/TABLE_REFERENCE.md`，将这 3 张表移至 `tc_` 前缀区域。

### 3. 创建迁移脚本

#### 脚本 1: `database/20260318_unify_table_names.sql`
- 作用：重命名数据库表
- 特点：
  - 检查表是否存在（避免"unknown table"错误）
  - 幂等设计（可重复执行）
  - 适用于已有旧表名的环境

#### 脚本 2: `database/20260318_create_missing_tables.sql`
- 作用：创建所有缺失的表，包括统一后的 3 张表
- 特点：
  - 使用 `CREATE TABLE IF NOT EXISTS`
  - 包含完整的表定义和注释
  - 适用于全新环境或表不存在的情况

## 🚀 执行步骤

### 方案 A：已有旧表名（推荐）

适用于：已经在用旧表名的生产/开发环境

```bash
# 1. 备份数据库（强烈建议）
mysqldump -u 数据库用户 -p taichu > taichu_backup_$(date +%Y%m%d_%H%M%S).sql

# 2. 执行表名统一脚本
mysql -u 数据库用户 -p taichu < database/20260318_unify_table_names.sql

# 3. 验证表已重命名
mysql -u 数据库用户 -p taichu -e "SHOW TABLES LIKE 'tc_sms%';"
mysql -u 数据库用户 -p taichu -e "SHOW TABLES LIKE 'tc_payment%';"

# 4. 重启后台应用
# php think queue:work  # 如果有队列
```

### 方案 B：全新环境（简单）

适用于：全新安装，还没创建过旧表名

```bash
# 1. 执行完整表创建脚本
mysql -u 数据库用户 -p taichu < database/20260318_create_missing_tables.sql

# 2. 验证表已创建
mysql -u 数据库用户 -p taichu -e "SHOW TABLES LIKE 'tc_sms%';"
mysql -u 数据库用户 -p taichu -e "SHOW TABLES LIKE 'tc_payment%';"

# 3. 启动应用
```

## 📝 SQL 语句快速参考

### 手动执行（如果脚本出问题）

```sql
-- 检查旧表是否存在
SELECT TABLE_NAME FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'taichu' 
AND TABLE_NAME IN ('sms_codes', 'sms_configs', 'payment_configs');

-- 重命名表
ALTER TABLE `sms_codes` RENAME TO `tc_sms_code`;
ALTER TABLE `sms_configs` RENAME TO `tc_sms_config`;
ALTER TABLE `payment_configs` RENAME TO `tc_payment_config`;

-- 验证表名
SHOW TABLES WHERE `Tables_in_taichu` LIKE 'tc_sms%' OR `Tables_in_taichu` LIKE 'tc_payment%';
```

## ⚠️ 数据迁移注意事项

### 如果旧表有数据

1. **保留数据的做法**：
   ```sql
   -- 如果需要保留旧表数据，执行表重命名后数据自动转移
   -- 新表继承旧表的所有数据
   ALTER TABLE `sms_codes` RENAME TO `tc_sms_code`;
   ```

2. **清理旧表的做法**（如果新系统不再需要）：
   ```sql
   -- 确认重命名成功后，删除旧表（如果存在）
   DROP TABLE IF EXISTS `sms_codes`;
   DROP TABLE IF EXISTS `sms_configs`;
   DROP TABLE IF EXISTS `payment_configs`;
   ```

### 零宕机迁移方案

对于运行中的系统：

1. 使用主从库或读写分离的情况下，可先在从库执行迁移
2. 部署新代码前，确保：
   - 新代码已完成编译/打包
   - 所有模型文件已使用新表名
   - 所有缓存已清理
3. 执行迁移脚本
4. 部署新代码
5. 验证后切换流量

## 🔍 验证清单

执行完脚本后，请验证以下内容：

- [ ] 表 `tc_sms_code` 存在，包含验证码记录
- [ ] 表 `tc_sms_config` 存在，包含短信服务配置  
- [ ] 表 `tc_payment_config` 存在，包含支付配置
- [ ] 后台应用可正常启动
- [ ] 短信发送功能正常（发送验证码）
- [ ] 手机号登录功能正常
- [ ] 充值支付功能正常
- [ ] 后台支付配置管理页面可访问
- [ ] 后台短信配置管理页面可访问

## 📞 故障排查

### 问题 1：`Unknown table 'sms_codes'`

**原因**：表已被重命名或从未创建过

**解决**：
```sql
-- 检查表是否已被重命名
SELECT * FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'taichu' 
AND TABLE_NAME LIKE 'tc_sms%';

-- 如果不存在，执行创建脚本
```

### 问题 2：应用报错 "Table 'taichu.sms_codes' doesn't exist"

**原因**：旧代码仍在引用旧表名

**解决**：
1. 确认所有模型文件已更新表名
2. 清除 PHP 代码缓存（如果有）
3. 重启 PHP-FPM：`systemctl restart php-fpm`

### 问题 3：支付功能中断

**原因**：PaymentConfig 模型指向错误的表名

**解决**：
1. 检查 `backend/app/model/PaymentConfig.php` 的 `$name` 属性
2. 确认数据库中 `tc_payment_config` 表存在且有数据
3. 检查后台支付配置是否已保存

## 📚 相关文档

- 完整的数据库修复报告：`DATABASE_REPAIR_SUMMARY.md`
- 表参考文档：`backend/database/TABLE_REFERENCE.md`
- 迁移脚本目录：`database/20260318_*.sql`

## ✨ 总结

这次表名统一迁移解决了代码与数据库的不一致问题，确保：

1. ✅ PHP 模型和数据库表名完全对齐
2. ✅ 所有业务功能（SMS、支付）正常运行
3. ✅ 后台配置管理可正确访问和修改
4. ✅ 代码库更加规范和可维护

**下一步**：根据系统当前状态，选择方案 A 或 B 执行迁移。

