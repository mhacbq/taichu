# 太初 (Taichu) - 命理运势网站

太初是一个基于 Vue 3 + ThinkPHP 8 的命理运势网站，提供八字排盘、塔罗占卜、每日运势等功能。

## 项目结构

```
taichu-unified/
├── frontend/          # Vue 3 前端项目
├── backend/           # ThinkPHP 8 后端项目
├── docker-compose.yml # Docker 部署配置
├── nginx.conf         # Nginx 配置文件
└── deploy.sh          # 服务器部署脚本
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

#### 1. 启动后端服务

```bash
cd backend
composer install
cp .env.example .env
# 编辑 .env 配置数据库连接
php think run
```

后端服务将运行在 http://localhost:8000

#### 2. 启动前端服务

```bash
cd frontend
npm install
npm run dev
```

前端服务将运行在 http://localhost:5173

### Docker 部署

#### 1. 构建前端

```bash
cd frontend
npm install
npm run build
```

#### 2. 启动所有服务

```bash
docker-compose up -d
```

服务启动后：
- 网站访问：http://localhost
- 后端 API：http://localhost:8080
- MySQL：localhost:3306

#### 3. 停止服务

```bash
docker-compose down
```

#### 4. 查看日志

```bash
docker-compose logs -f
```

## 服务器部署

### 使用部署脚本

1. 将项目上传到服务器
2. 执行部署脚本：

```bash
chmod +x deploy.sh
./deploy.sh
```

### 手动部署

1. 安装 Docker 和 Docker Compose
2. 克隆项目到服务器
3. 构建前端：`cd frontend && npm install && npm run build`
4. 启动服务：`docker-compose up -d`
5. 配置 Nginx 或直接使用 docker-compose 中的 Nginx

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

首次启动时会自动执行 `backend/database.sql` 初始化数据库结构。

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
