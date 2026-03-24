# TOOLS.md — 太初项目命令速查

---

## 环境信息

| 项目 | 地址 |
|------|------|
| 本地前端 | `http://localhost:5173` |
| 本地后台 | `http://localhost:5174` |
| 本地 API | `http://localhost:8080`（phpstudy） |
| 线上 | `taichu.chat`，Swoole `127.0.0.1:8080`，Nginx 反向代理 |

---

## 常用命令

### 前台前端 (`frontend/`)
```bash
npm install
npm run dev        # 开发服务器
npm run build      # 构建生产版本
npm run test       # 运行测试
npm run icon:audit # 审计 Element Plus 图标
```

### 管理后台 (`admin/`)
```bash
npm install
$env:VITE_PROXY_TARGET = 'http://localhost:8080'  # PowerShell 设置代理
npm run dev        # 开发服务器
npm run build      # 构建生产版本
npm run lint       # ESLint 检查
```

### 后端 (`backend/`)
```bash
composer install
cp .env.example .env   # 配置数据库和 JWT
php think run --port 8080  # 启动开发服务器
php tests/run.php          # 运行测试
php think clear            # 清空缓存
curl http://localhost:8080/api/health  # 健康检查
```

### 数据库
```bash
mysqldump -u root -p taichu > backup.sql                    # 备份
mysql -u root -p taichu < backup.sql                        # 恢复
mysql -u root -p taichu < database/YYYYMMDD_xxx.sql         # 执行迁移
```

---

## 环境变量（关键配置）

**`backend/.env`**
```bash
DB_HOST=localhost
DB_DATABASE=taichu
DB_USERNAME=root
DB_PASSWORD=your_password
JWT_SECRET=your_jwt_secret
DEEPSEEK_API_KEY=your_deepseek_key
```

**`frontend/.env`**
```bash
VITE_API_BASE_URL=http://localhost:8080
```

---

## 故障排查

```bash
# 前端构建失败 → 清理缓存
rm -rf node_modules && npm install

# 后端日志
cat backend/runtime/log/YYYYMMDD.log

# 数据库连接测试
mysql -u root -p -h localhost -P 3306
```

---

> 提示：所有工具命令都应在对应的目录下执行，确保路径正确。