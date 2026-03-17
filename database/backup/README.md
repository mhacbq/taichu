# 太初命理系统 - 数据库导入说明

## 文件说明

本文件夹包含太初命理系统完整的数据库脚本，按顺序执行即可搭建完整的测试环境。

### 文件列表

| 文件名 | 说明 | 是否必需 |
|-------|------|---------|
| `01_create_database.sql` | 创建数据库 | 是 |
| `02_create_tables.sql` | 创建核心数据表 | 是 |
| `03_insert_basic_data.sql` | 插入基础配置数据 | 是 |
| `../20260317_create_admin_users_table.sql` | 创建管理员主表、默认角色与默认管理员 | 是 |
| `../20260317_create_shensha_table.sql` | 创建神煞表并写入默认神煞种子 | 是 |
| `../20260317_create_knowledge_tables.sql` | 创建后台知识库文章/分类表并写入默认分类 | 是 |
| `04_insert_test_data.sql` | 插入测试数据（可选） | 否 |

## 导入步骤

### 方法一：使用 MySQL 命令行（推荐）

1. **登录 MySQL**
   ```bash
   mysql -u root -p
   ```

2. **执行 SQL 文件（按顺序）**
   ```bash
   # 创建数据库和核心表结构
   mysql -u root -p < 01_create_database.sql
   mysql -u root -p taichu < 02_create_tables.sql
   mysql -u root -p taichu < 03_insert_basic_data.sql
   mysql -u root -p taichu < ../20260317_create_admin_users_table.sql
   mysql -u root -p taichu < ../20260317_create_shensha_table.sql
   mysql -u root -p taichu < ../20260317_create_knowledge_tables.sql
   
   # 可选：导入测试数据
   mysql -u root -p taichu < 04_insert_test_data.sql
   ```

### 方法二：使用 MySQL Workbench / Navicat

1. 打开 MySQL Workbench 或 Navicat
2. 连接到本地 MySQL 服务器
3. 创建名为 `taichu` 的数据库（字符集：utf8mb4）
4. 按顺序打开 SQL 文件并执行

### 方法三：一键导入脚本

创建 `import.bat`（Windows）或 `import.sh`（Mac/Linux）：

**Windows (import.bat):**
```batch
@echo off
set MYSQL_USER=root
set MYSQL_PASSWORD=your_password
set DATABASE_NAME=taichu

echo Creating database...
mysql -u%MYSQL_USER% -p%MYSQL_PASSWORD% < 01_create_database.sql

echo Creating tables...
mysql -u%MYSQL_USER% -p%MYSQL_PASSWORD% %DATABASE_NAME% < 02_create_tables.sql

echo Inserting basic data...
mysql -u%MYSQL_USER% -p%MYSQL_PASSWORD% %DATABASE_NAME% < 03_insert_basic_data.sql

echo Bootstrapping admin/content tables...
mysql -u%MYSQL_USER% -p%MYSQL_PASSWORD% %DATABASE_NAME% < ..\20260317_create_admin_users_table.sql
mysql -u%MYSQL_USER% -p%MYSQL_PASSWORD% %DATABASE_NAME% < ..\20260317_create_shensha_table.sql
mysql -u%MYSQL_USER% -p%MYSQL_PASSWORD% %DATABASE_NAME% < ..\20260317_create_knowledge_tables.sql

echo Inserting test data...
mysql -u%MYSQL_USER% -p%MYSQL_PASSWORD% %DATABASE_NAME% < 04_insert_test_data.sql

echo Done!
pause
```

**Mac/Linux (import.sh):**
```bash
#!/bin/bash
MYSQL_USER="root"
MYSQL_PASSWORD="your_password"
DATABASE_NAME="taichu"

echo "Creating database..."
mysql -u$MYSQL_USER -p$MYSQL_PASSWORD < 01_create_database.sql

echo "Creating tables..."
mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $DATABASE_NAME < 02_create_tables.sql

echo "Inserting basic data..."
mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $DATABASE_NAME < 03_insert_basic_data.sql

echo "Bootstrapping admin/content tables..."
mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $DATABASE_NAME < ../20260317_create_admin_users_table.sql
mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $DATABASE_NAME < ../20260317_create_shensha_table.sql
mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $DATABASE_NAME < ../20260317_create_knowledge_tables.sql

echo "Inserting test data..."
mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $DATABASE_NAME < 04_insert_test_data.sql

echo "Done!"
```

## 表结构说明

### 核心表

| 表名 | 说明 | 主要字段 |
|-----|------|---------|
| `tc_user` | 用户表 | id, openid, phone, nickname, points, vip_level |
| `tc_points_record` | 积分记录 | user_id, type, amount, balance, reason |
| `tc_vip_order` | VIP订单 | order_no, user_id, vip_type, amount, status |
| `tc_recharge_order` | 充值订单 | order_no, user_id, amount, points, pay_status |

### 功能表

| 表名 | 说明 |
|-----|------|
| `tc_bazi_record` | 八字排盘记录 |
| `tc_hehun_record` | 八字合婚记录 |
| `tc_qiming_record` | 取名建议记录 |
| `tc_daily_fortune` | 每日运势记录 |
| `tc_yearly_fortune` | 流年运势记录 |

### 系统表

| 表名 | 说明 |
|-----|------|
| `tc_system_config` | 系统配置 |
| `tc_feature_switch` | 功能开关 |
| `tc_admin_permission` | 管理员权限 |
| `tc_admin_role` | 管理员角色 |
| `tc_admin_log` | 操作日志 |

## 测试账号

导入测试数据后，可以使用以下账号测试：

| 用户ID | 昵称 | 手机号 | 积分 | VIP等级 |
|-------|------|--------|------|---------|
| 1 | 测试用户 | 13800138000 | 500 | 普通用户 |
| 2 | 张三 | 13800138001 | 1000 | 月度VIP |
| 3 | 李四 | 13800138002 | 200 | 普通用户 |
| 4 | 王五 | 13800138003 | 3000 | 年度VIP（管理员） |
| 5 | 赵六 | 13800138004 | 50 | 普通用户 |

## 配置说明

### 后端配置

复制 `backend/.env.example` 为 `backend/.env`，并修改数据库配置：

```ini
# 数据库配置
DB_HOST = 127.0.0.1
DB_PORT = 3306
DB_NAME = taichu
DB_USER = root
DB_PASSWORD = your_password
DB_CHARSET = utf8mb4
DB_PREFIX = tc_

# JWT配置（必须设置强密钥）
JWT_SECRET = your-generated-secret-key

# API签名密钥
API_SIGN_KEY = your-api-sign-key
```

### 前端配置

修改 `frontend/.env` 或 `frontend/.env.local`：

```ini
VUE_APP_API_URL = http://localhost:8000/api
```

## 常见问题

### 1. 导入时出现乱码

确保数据库和表的字符集为 `utf8mb4`：
```sql
ALTER DATABASE taichu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. 表已存在错误

如果重新导入，先删除现有表：
```sql
DROP DATABASE IF EXISTS taichu;
```

### 3. 外键约束错误

确保按顺序执行 SQL 文件，先执行 `02_create_tables.sql` 创建核心表，再执行 `20260317_create_admin_users_table.sql`、`20260317_create_shensha_table.sql`、`20260317_create_knowledge_tables.sql` 补齐后台管理与内容管理所需表。

## 技术支持

如有问题，请查看项目文档或联系技术支持。
