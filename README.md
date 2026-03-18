# 太初 (Taichu) - 命理运势网站

太初是一个基于 Vue 3 + ThinkPHP 8 的命理运势网站，提供八字排盘、塔罗占卜、每日运势等功能。

## 项目结构

```
taichu-unified/
├── frontend/             # Vue 3 前端项目
├── admin/                # Vue 3 独立管理后台
├── backend/              # ThinkPHP 8 后端项目（含 Docker 配置）
├── database/             # 增量 SQL 与初始化补丁
├── 本地启用.md          # 本地联调说明
└── deploy/deploy.sh      # 服务器部署脚本
```

## 技术栈

### 前端
- Vue 3 + Composition API
- Vue Router
- Pinia
- Vite
- Element Plus

### 后端
- PHP 8.0+
- ThinkPHP 8
- MySQL 8.0
- JWT 认证

## 快速开始

### 本地开发

#### 1. 推荐：使用一键本地启动脚本

```powershell
Set-Location "C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified"
.\start-local.ps1
```

默认会执行 `backend/docker-compose.yml` 的 `docker compose up -d --build`，并在后端容器启动时自动补跑后台所需的幂等 SQL 补丁。标准本地后端地址为 `http://localhost:8080`。

如需同时启动独立管理后台：

```powershell
.\start-local.ps1 -WithAdmin
```

独立后台默认访问地址：`http://localhost:3001`，其 `/api/*` 会代理到 `http://localhost:8080`。

更多参数见 `本地启用.md`。

#### 2. 手动启动后端服务

```bash
cd backend
composer install
cp .env.example .env
# 编辑 .env 配置数据库连接
php think run --port 8080
```

#### 3. 启动前端服务

```bash
cd frontend
npm install
npm run dev
```

前端服务将运行在 http://localhost:5173

如需启动独立后台管理端，可在 `admin/` 目录执行 `npm install && npm run dev`，默认访问地址为 `http://localhost:3001`，并通过 Vite 代理转发到 `http://localhost:8080/api/admin`。


### Docker 部署

#### 1. 构建前端

```bash
cd frontend
npm install
npm run build
```

#### 2. 启动所有服务

```bash
cd backend
docker compose up -d --build
```

如果需要让已有数据卷补齐后台管理员 / 统计 / 反作弊 / 神煞 / 知识库等缺失表，只需重启后端容器；入口脚本会自动补跑 `database/` 目录中的幂等 SQL 补丁。


服务启动后：
- 网站访问：http://localhost
- 后端 API：http://localhost:8080
- MySQL：localhost:3306

#### 3. 停止服务

```bash
cd backend
docker compose down
```

#### 4. 查看日志

```bash
cd backend
docker compose logs -f
```

## 服务器部署

### 使用部署脚本

1. 将项目上传到服务器
2. 执行部署脚本：

```bash
chmod +x deploy/deploy.sh
deploy/deploy.sh
```

### 手动部署

1. 安装 Docker 和 Docker Compose
2. 克隆项目到服务器
3. 构建前端：`cd frontend && npm install && npm run build`
4. 启动服务：`cd backend && docker compose up -d --build`
5. 配置 Nginx 或直接使用部署目录下的 Nginx 配置

## API 接口文档

### 用户认证
- `POST /api/auth/login` - 用户登录/注册
- `GET /api/auth/profile` - 获取用户信息

### 八字排盘
- `POST /api/paipan/bazi` - 八字排盘计算
- `GET /api/paipan/history` - 获取排盘历史

### 塔罗占卜
- `POST /api/tarot/draw` - 抽取塔罗牌
- `GET /api/tarot/cards` - 获取塔罗牌列表

### 每日运势
- `GET /api/daily/fortune` - 获取今日运势
- `POST /api/daily/checkin` - 每日签到

### 积分系统
- `GET /api/points/balance` - 查询积分余额
- `GET /api/points/records` - 查询积分记录

## 数据库初始化

- 首次初始化时，MySQL 容器会执行 `backend/database.sql` 创建核心表结构。
- 后端容器每次启动都会自动补跑 `database/20260317_create_admin_users_table.sql`、`database/20260317_create_shensha_table.sql`、`database/20260317_create_knowledge_tables.sql` 等幂等补丁，确保旧数据卷也能补齐后台依赖表。
- 如果基础库已损坏或想彻底重建，再执行 `start-local.ps1 -ResetData` / `docker compose down -v`。

## 环境变量

### 后端环境变量 (.env)

```
APP_DEBUG = false
DB_HOST = mysql
DB_PORT = 3306
DB_NAME = taichu
DB_USER = taichu
DB_PASSWORD = taichu123
JWT_SECRET = your-jwt-secret-key
```

## 目录权限

如果使用传统方式部署（非 Docker），需要设置以下目录权限：

```bash
chmod -R 755 backend/runtime
chmod -R 755 backend/public/uploads
```

## 开发者

- 项目：太初命理运势网站
- 框架：Vue 3 + ThinkPHP 8

## License

MIT License
