# 太初 (Taichu) - 命理运势网站

太初是一个基于 Vue 3 + ThinkPHP 8 的命理运势网站，提供八字排盘、塔罗占卜、每日运势等功能。

> 当前仓库**已不再使用 Docker**。以下说明统一按 **本地 phpstudy**、**线上宝塔 + Swoole + Git Webhook** 的实际口径整理。

## 当前运行口径

- **本地开发环境**：phpstudy + MySQL，本地后端默认走 `http://localhost:8080`
- **线上运行环境**：宝塔面板 + Swoole
- **代码更新方式**：Git Webhook 触发拉取代码
- **前台站点根目录**：`/www/wwwroot/taichu.chat/frontend/dist`
- **管理后台页面入口**：`/maodou/`，后台登录页前端路由是 `/maodou/login`
- **后台登录接口**：`POST /api/maodou/auth/login`

## 项目结构

```text
taichu-unified/
├── frontend/             # Vue 3 前端项目（线上主站构建产物来源）
├── admin/                # Vue 3 独立管理后台（独立构建，线上挂在 /maodou/）
├── backend/              # ThinkPHP 8 后端项目（本地 phpstudy / 线上 Swoole 提供接口）
├── database/             # 增量 SQL、初始化补丁、迁移脚本
└── deploy/               # 部署相关脚本与配置参考
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
- Swoole

## 本地开发（phpstudy）

### 1. 启动后端

当前本地标准环境是 **phpstudy + `http://localhost:8080` 直连接口**。

建议先确认健康检查：

```text
http://localhost:8080/api/health
```

如果你需要手动用 CLI 拉起 ThinkPHP，也可以执行：

```bash
cd backend
composer install
cp .env.example .env
# 按本机数据库实际情况编辑 .env
php think run --port 8080
```

### 2. 启动前端开发环境

```bash
cd frontend
npm install
npm run dev
```

前端开发服务通常运行在 `http://localhost:5173`。

### 3. 构建 / 验证管理后台

`admin/` 是独立 Vite 项目。后台页面登录验证不要再默认猜 `http://localhost:3001/login`、`http://localhost:8080/maodou` 或 `/admin/login`。

正确口径是：

- 登录页前端路由：`/maodou/login`
- 登录接口：`POST /api/maodou/auth/login`
- 页面级验证前先确认是否已有构建产物 `admin/dist/index.html`
- 如果已知后台挂载根地址，应访问“**站点根地址 + `/maodou/login`**”

先做构建校验：

```bash
cd admin
npm install
npm run build
```

如果你只是临时需要热更新，再手动启动后台 dev server：

```powershell
Set-Location "C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified\admin"
npm install
$env:VITE_PROXY_TARGET = 'http://localhost:8080'
npm run dev
```

终端会打印 dev URL。它**可能**是 `http://localhost:3001`，但这只是开发态地址，不是项目的固定后台站点口径。

## 线上部署（宝塔 + Swoole + Git Webhook）

### 1. 代码发布方式

线上通过 **Git Webhook** 拉取代码更新。常规发布链路建议理解为：

1. 推送代码到 Git 仓库
2. Webhook 触发服务器拉取最新代码
3. 视改动范围重新构建 `frontend/` 与 `admin/`
4. 保持 / 重载 Swoole 服务

> 如果本次提交涉及前端页面、后台页面、静态资源或路由，拉代码后别忘了重新构建；不然线上会出现“代码到了，页面没到”的经典节目。

### 2. 线上目录口径

- 项目目录：`/www/wwwroot/taichu.chat`
- 前台构建产物目录：`/www/wwwroot/taichu.chat/frontend/dist`
- 后台页面目录：`/www/wwwroot/taichu.chat/admin/dist/`
- Swoole 后端监听地址：`127.0.0.1:8080`

推荐在服务器上按需执行：

```bash
cd /www/wwwroot/taichu.chat/frontend && npm install && npm run build
cd /www/wwwroot/taichu.chat/admin && npm install && npm run build
```

### 3. 宝塔 / Nginx 站点配置要点

现网采用：

- 前台页面走 `frontend/dist`
- `/api/` 反向代理到 Swoole `127.0.0.1:8080`
- `/maodou/` 单独映射后台页面目录

可按以下配置理解当前线上路由口径：

```nginx
# --- 1. API 接口 (最高优先级) ---
# 使用 ^~ 确保匹配到 /api/ 后不再往下匹配正则，防止被静态资源规则拦截
location ^~ /api/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header REMOTE-HOST $remote_addr;
    
    # 支持 Swoole WebSocket
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_http_version 1.1;

    # 接口不缓存
    add_header Cache-Control no-cache;
}

# --- 2. 管理端页面 (maodou路径) ---
location ^~ /maodou/ {
    alias /www/wwwroot/taichu.chat/admin/dist/;
    index index.html;
    try_files $uri $uri/ /maodou/index.html;
}

# --- 3. 静态资源缓存 (全局匹配) ---
# 合并了你之前重复的正则，涵盖了所有常见格式
location ~* \.(gif|png|jpg|jpeg|ico|svg|css|js|woff|woff2|ttf|eot)$ {
    # 这里的 root 建议指向前端 dist，因为大部分资源在那
    root /www/wwwroot/taichu.chat/frontend/dist;
    expires 30d;
    add_header Cache-Control "public, no-transform";
    
    # 如果在 frontend 找不到，尝试在 maodou 找（可选补丁）
    try_files $uri /maodou/$uri =404;
}

# --- 4. 前端页面 (兜底路径) ---
location / {
    root /www/wwwroot/taichu.chat/frontend/dist;
    index index.html;
    
    # 针对 VitePress 的特化支持：
    # 1. 找原始文件 2. 找带 .html 的文件 3. 找目录 4. 回退到 index.html
    try_files $uri $uri.html $uri/ /index.html;
}
```

### 4. 线上发布后建议检查

- `GET /api/health` 是否返回正常
- 前台首页是否能正常打开
- 后台 `站点根地址 + /maodou/login` 是否可打开
- 后台登录接口 `POST /api/maodou/auth/login` 是否正常
- 前后台静态资源是否命中最新构建产物
- Webhook 拉取后是否已实际重建并生效

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

- 首次初始化时，可使用 `backend/database.sql` 创建核心表结构。
- `database/` 目录下的管理员、知识库、神煞、SEO、积分等补丁 SQL，都是仓库内的最终落库版本。
- **本项目当前不再依赖 Docker 容器自动补 SQL。** 无论本地 phpstudy 还是线上环境，缺表 / 缺列 / 缺初始化数据时，都应按实际情况手动执行对应的 `database/*.sql`。
- 如果是全新环境，建议先完成基础库初始化，再按功能模块补跑对应增量 SQL。

## 环境变量

### 本地 phpstudy 示例 (.env)

```env
APP_DEBUG = true
DB_HOST = 127.0.0.1
DB_PORT = 3306
DB_NAME = taichu
DB_USER = root
DB_PASSWORD = root
JWT_SECRET = your-jwt-secret-key
```

### 线上环境说明

线上环境请按宝塔 / Swoole / 实际数据库配置填写，不要直接照抄本地示例。至少应区分：

- `APP_DEBUG = false`
- 数据库地址、账号、密码使用线上真实值
- Swoole 监听与站点反向代理保持一致

## 目录权限

Linux 服务器如遇运行时目录写入失败，可检查以下权限：

```bash
chmod -R 755 /www/wwwroot/taichu.chat/backend/runtime
chmod -R 755 /www/wwwroot/taichu.chat/backend/public/uploads
```

## 开发者

- 项目：太初命理运势网站
- 框架：Vue 3 + ThinkPHP 8

## License

MIT License
