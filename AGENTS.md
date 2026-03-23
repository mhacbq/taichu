# AGENTS.md — 太初 (Taichu) AI Agent 工作手册

> 本文件是 AI 编码 Agent 在本仓库中工作的**唯一权威指南**。
> 遵循 [AGENTS.md 开放标准](https://agents.md) 与 [Harness Engineering](https://openai.com/index/harness-engineering/) 理念编写。
> 核心原则：**人类引导，Agent 执行 (Humans steer. Agents execute.)**

---

## 1. 项目概览

**太初 (Taichu)** 是一个命理运势网站，提供八字排盘、塔罗占卜、六爻占卜、合婚分析、每日运势、起名等功能。

| 层级 | 技术栈 | 目录 |
|------|--------|------|
| 前台前端 | Vue 3 + Composition API + Vite 8 + Element Plus + Pinia | `frontend/` |
| 管理后台 | Vue 3 + Vite 5 + Element Plus + ECharts + Pinia | `admin/` |
| 后端 API | PHP 8.0+ / ThinkPHP 8 / MySQL 8.0 / JWT / Swoole | `backend/` |
| 数据库 | MySQL 8.0，表前缀 `tc_`，字符集 `utf8mb4` | `database/` |
| 部署 | 宝塔面板 + Swoole + Nginx + Git Webhook | `deploy/` |

**线上域名**: `taichu.chat`
**管理后台入口**: `/maodou/`

---

## 2. 仓库结构

```text
taichu-unified/
├── frontend/                 # 前台 Vue 3 项目
│   ├── src/
│   │   ├── api/              # API 请求封装（request.js 为基础 axios 实例）
│   │   ├── components/       # 通用组件（PascalCase 命名）
│   │   ├── composables/      # 组合式函数（use*.js / use*.ts）
│   │   ├── views/            # 页面视图（每个功能一个目录：index.vue + style.css + useXxx.js）
│   │   ├── router/           # Vue Router 路由配置
│   │   ├── utils/            # 工具函数
│   │   └── styles/           # 全局样式 / 主题
│   └── tests/                # 前端测试
├── admin/                    # 管理后台 Vue 3 项目
│   ├── src/
│   │   ├── api/              # 后台 API 封装
│   │   ├── components/       # 后台通用组件（CommonTable, SearchForm, Chart 等）
│   │   ├── hooks/            # 后台 Composables（useTable, useForm, useDialog）
│   │   ├── views/            # 后台页面视图
│   │   ├── stores/           # Pinia stores
│   │   ├── layout/           # 后台布局组件
│   │   ├── directives/       # 自定义指令（权限指令等）
│   │   └── utils/            # 工具函数
│   └── vite.config.js
├── backend/                  # ThinkPHP 8 后端
│   ├── app/
│   │   ├── controller/       # 前台控制器
│   │   ├── controller/admin/ # 后台管理控制器
│   │   ├── model/            # ORM 模型
│   │   ├── service/          # 业务服务层
│   │   ├── middleware/       # 中间件（Auth, CORS, RateLimit 等）
│   │   ├── exception/        # 异常处理
│   │   └── command/          # CLI 命令（定时任务、缓存清理等）
│   ├── route/                # 路由定义（app.php, admin.php 等）
│   ├── config/               # 框架配置
│   └── tests/                # 后端测试
├── database/                 # SQL 迁移脚本（按日期命名：YYYYMMDD_描述.sql）
├── deploy/                   # 部署脚本和 Nginx 配置
└── docs/                     # 项目文档
```

---

## 3. 开发环境与构建命令

### 3.1 后端

```bash
# 安装依赖
cd backend && composer install

# 配置环境
cp .env.example .env
# 编辑 .env 设置数据库连接

# 启动开发服务器
php think run --port 8080

# 运行测试
cd backend && php tests/run.php

# 健康检查
curl http://localhost:8080/api/health
```

### 3.2 前台前端

```bash
cd frontend
npm install
npm run dev          # 启动开发服务器（http://localhost:5173）
npm run build        # 构建生产版本
npm run test         # 运行测试（node tests/utils.test.js）
npm run icon:audit   # 审计 Element Plus 图标使用
```

### 3.3 管理后台

```bash
cd admin
npm install
npm run dev          # 启动开发服务器
npm run build        # 构建生产版本
npm run lint         # ESLint 检查
```

> ⚠️ 后台开发时需要设置代理目标：`$env:VITE_PROXY_TARGET = 'http://localhost:8080'`（PowerShell）

---

## 4. 编码规范与约定

### 4.1 前端通用规范（Vue 3）

- **必须**使用 `<script setup lang="ts">` 或 `<script setup>`（当前项目以 JS 为主）
- **必须**使用 Composition API，禁止 Options API
- 组件文件名使用 **PascalCase**（如 `ShareButton.vue`）
- 模板中组件引用使用 **PascalCase 自闭合标签**（如 `<ShareButton />`）
- Props 定义使用 **类型声明**（`defineProps<Props>()`）
- 优先使用 `ref` 而非 `reactive`
- 指令简写：`:` 代替 `v-bind:`，`@` 代替 `v-on:`
- `v-for` 必须搭配 `:key`，禁止与 `v-if` 同级
- 样式必须加 `scoped`，深度选择器使用 `:deep()`

### 4.2 前台页面结构模式

前台每个功能页面遵循**三文件模式**：

```text
views/Xxx/
├── index.vue       # 模板 + 最小逻辑胶水
├── style.css       # 页面专属样式
└── useXxx.js       # 页面核心逻辑（Composable）
```

- `index.vue` 负责模板渲染，从 `useXxx.js` 解构出状态和方法
- `useXxx.js` 封装业务逻辑、API 调用、状态管理
- `style.css` 包含该页面专属的所有样式

### 4.3 管理后台模式

- 使用 `hooks/useTable.js` 进行列表页数据管理
- 使用 `hooks/useForm.js` 进行表单管理
- 使用 `hooks/useDialog.js` 管理弹窗状态
- 组件库：CommonTable、SearchForm、StatCard、Chart 等
- Stores 使用 Pinia setup 语法，文件位于 `stores/` 目录

### 4.4 后端 PHP 规范

- 控制器层 (`controller/`)：处理请求参数验证和响应格式化
- 服务层 (`service/`)：封装核心业务逻辑，控制器调用 Service
- 模型层 (`model/`)：ORM 映射，含数据校验和关联
- 路由文件：`route/app.php`（前台）、`route/admin.php`（后台管理）
- 中间件链：Cors → JwtAuth/AdminAuth → RateLimit → 业务逻辑
- API 统一响应格式：`{ code: 0, msg: "success", data: {...} }`
- 错误码：`code: 0` 为成功，非 0 为各类错误
- 数据库表前缀：`tc_`

### 4.5 API 路由约定

| 前缀 | 用途 | 认证 |
|------|------|------|
| `/api/auth/` | 用户认证（登录/注册） | 无 |
| `/api/paipan/` | 八字排盘 | JWT |
| `/api/tarot/` | 塔罗占卜 | JWT |
| `/api/daily/` | 每日运势/签到 | JWT/OptionalAuth |
| `/api/hehun/` | 合婚分析 | JWT |
| `/api/liuyao/` | 六爻占卜 | JWT |
| `/api/points/` | 积分系统 | JWT |
| `/api/maodou/` | 管理后台接口 | AdminAuth |

### 4.6 数据库迁移约定

- 迁移文件放在 `database/` 目录
- 命名格式：`YYYYMMDD_描述.sql`（如 `20260323_ensure_manage_tables.sql`）
- SQL 必须是**幂等的**（使用 `IF NOT EXISTS`、`IF EXISTS` 等）
- 表名使用 `tc_` 前缀 + 蛇形命名（如 `tc_bazi_records`）

---

## 5. 关键架构决策

### 5.1 认证体系

- **前台用户认证**：JWT（`middleware/JwtAuth.php`），支持手机号登录（`PhoneAuth`）
- **后台管理认证**：独立 JWT（`middleware/AdminAuth.php`），RBAC 权限模型
- 可选认证中间件：`OptionalAuth`（未登录也可访问，但登录后有更多功能）

### 5.2 AI 分析服务

- 使用 DeepSeek API 进行 AI 解读（`service/DeepSeekService.php`）
- AI Prompt 模板可在后台管理中配置（`AiPrompt` 模型）
- AI 分析结果缓存机制：`service/CacheService.php`

### 5.3 积分系统

- 积分获取：签到、邀请、充值、任务完成
- 积分消耗：八字排盘、塔罗占卜、合婚分析等付费功能
- 核心逻辑在 `service/PointsService.php`
- VIP 体系在 `service/VipService.php`

### 5.4 支付系统

- 支持支付宝（`controller/Alipay.php`）
- 支持微信支付（`service/WechatPayService.php`）
- 订单模型：`model/RechargeOrder.php`、`model/VipOrder.php`

---

## 6. 验证清单

Agent 完成任务后，应按以下清单自查：

### 6.1 前端改动

- [ ] `npm run build` 在 `frontend/` 和/或 `admin/` 下成功
- [ ] 无 ESLint 错误（`npm run lint`，如有配置）
- [ ] 新组件遵循项目现有的文件组织模式
- [ ] 使用了 Element Plus 现有组件，而非自造轮子
- [ ] 样式使用 `scoped` 或写入页面专属 `style.css`

### 6.2 后端改动

- [ ] 新路由已在 `route/app.php` 或 `route/admin.php` 中注册
- [ ] 新控制器方法包含参数验证
- [ ] 业务逻辑放在 Service 层而非 Controller 层
- [ ] API 返回统一格式 `{ code, msg, data }`
- [ ] 涉及数据库变更时，创建了 `database/YYYYMMDD_xxx.sql` 迁移脚本
- [ ] SQL 是幂等的

### 6.3 通用

- [ ] 改动最小化，不修改无关模块
- [ ] 代码可编译、可运行
- [ ] 没有遗留 `console.log` 调试语句
- [ ] 没有硬编码密钥或敏感信息

---

## 7. 常见陷阱 ⚠️

1. **后台入口不是 `/admin/`** — 管理后台挂载在 `/maodou/`，登录页是 `/maodou/login`，登录接口是 `POST /api/maodou/auth/login`。
2. **本地后端走 phpstudy** — 默认 `http://localhost:8080`，不是 Docker。
3. **大文件注意** — `controller/Admin.php`（172KB）、`controller/Hehun.php`（121KB）、`controller/Paipan.php`（86KB）等为大文件，修改时使用精确替换而非全量重写。
4. **数据库前缀** — 所有表名以 `tc_` 为前缀，ORM 模型中可能已配置前缀。
5. **前后台是独立 Vite 项目** — `frontend/` 和 `admin/` 各有独立的 `package.json`、`vite.config.js`，不要混淆。
6. **线上用 Swoole，非 Apache/Nginx-FPM** — 后端运行在 Swoole 上，`127.0.0.1:8080`，Nginx 做反向代理。
7. **不要猜测 API** — 查看 `route/app.php` 和 `route/admin.php` 获取实际路由定义。
8. **SQL 幂等** — 数据库迁移脚本必须可重复执行，使用 `IF NOT EXISTS` 等保护。

---

## 8. 环境信息

| 项目 | 说明 |
|------|------|
| 本地后端 | `http://localhost:8080`（phpstudy） |
| 本地前端 | `http://localhost:5173`（Vite dev server） |
| 线上后端 | Swoole `127.0.0.1:8080`（Nginx 反向代理） |
| 线上前台 | `/www/wwwroot/taichu.chat/frontend/dist` |
| 线上后台 | `/www/wwwroot/taichu.chat/admin/dist/`（挂在 `/maodou/`） |
| 部署方式 | Git Webhook 触发拉取 → 按需重新构建前端 → 重载 Swoole |

---

## 9. 工作流程指引

### 9.1 新增一个功能页面（前台）

1. 在 `frontend/src/views/` 下创建目录，包含 `index.vue` + `style.css` + `useXxx.js`
2. 在 `frontend/src/router/index.js` 中添加路由
3. 如需新 API，在 `frontend/src/api/` 下添加或扩展
4. 后端在 `backend/app/controller/` 下添加控制器
5. 在 `backend/route/app.php` 中注册路由
6. 如需数据库变更，创建 `database/YYYYMMDD_xxx.sql`

### 9.2 新增一个管理后台页面

1. 在 `admin/src/views/` 对应模块目录下创建 `.vue` 文件
2. 在 `admin/src/router/index.js` 中添加路由
3. 如需新 API，在 `admin/src/api/` 下添加
4. 后端在 `backend/app/controller/admin/` 下添加控制器
5. 在 `backend/route/admin.php` 中注册路由

### 9.3 修复一个 Bug

1. 先理解现有代码逻辑，阅读相关 Controller → Service → Model 链路
2. 定位问题根因，最小化修改
3. 验证修改不影响其他功能

---

## 10. 文件索引（快速定位）

| 要找什么 | 去哪里看 |
|---------|---------|
| 前台路由 | `frontend/src/router/index.js` |
| 后台路由 | `admin/src/router/index.js` |
| 后端 API 路由 | `backend/route/app.php`（前台）、`backend/route/admin.php`（后台） |
| 前台 API 封装 | `frontend/src/api/index.js`、`frontend/src/api/request.js` |
| 后台 API 封装 | `admin/src/api/request.js` |
| 数据库模型 | `backend/app/model/` |
| 业务逻辑 | `backend/app/service/` |
| 中间件 | `backend/app/middleware/` |
| 定时任务 | `backend/app/command/` |
| 数据库迁移 | `database/` |
| 部署配置 | `deploy/deploy.sh`、`deploy/nginx/taichu.conf` |
| 环境变量模板 | `backend/.env.example` |
