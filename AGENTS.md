# AGENTS.md — 太初 AI Agent 工作手册

> 核心原则：**人类引导，Agent 执行。** 遵循 [AGENTS.md 开放标准](https://agents.md)

---

## 1. 项目概览

| 层级 | 技术栈 | 目录 |
|------|--------|------|
| 前台前端 | Vue 3 + Composition API + Vite + Element Plus + Pinia | `frontend/` |
| 管理后台 | Vue 3 + Vite + Element Plus + ECharts + Pinia | `admin/` |
| 后端 API | PHP 8.0+ / ThinkPHP 8 / MySQL 8.0 / JWT / Swoole | `backend/` |
| 数据库 | MySQL 8.0，表前缀 `tc_`，字符集 `utf8mb4` | `database/` |
| 部署 | 宝塔面板 + Swoole + Nginx + Git Webhook | `deploy/` |

**线上域名**: `taichu.chat` | **管理后台入口**: `/maodou/`

---

## 2. 仓库结构

```text
taichu-unified/
├── frontend/src/
│   ├── api/          # API 请求封装（request.js 为基础 axios 实例）
│   ├── components/   # 通用组件（PascalCase 命名）
│   ├── composables/  # 组合式函数（use*.js / use*.ts）
│   ├── views/        # 页面视图（每个功能一个目录：index.vue + style.css + useXxx.js）
│   ├── router/       # Vue Router 路由配置
│   └── styles/       # 全局样式 / 主题
├── admin/src/
│   ├── api/          # 后台 API 封装
│   ├── components/   # 后台通用组件（CommonTable, SearchForm, Chart 等）
│   ├── hooks/        # 后台 Composables（useTable, useForm, useDialog）
│   ├── views/        # 后台页面视图
│   └── stores/       # Pinia stores
├── backend/app/
│   ├── controller/       # 前台控制器
│   ├── controller/admin/ # 后台管理控制器
│   ├── model/            # ORM 模型
│   ├── service/          # 业务服务层
│   ├── middleware/       # 中间件（Auth, CORS, RateLimit 等）
│   └── command/          # CLI 命令（定时任务等）
├── backend/route/    # 路由定义（app.php 前台，admin.php 后台）
├── database/         # SQL 迁移脚本（YYYYMMDD_描述.sql）
└── deploy/           # 部署脚本和 Nginx 配置
```

---

## 3. 编码规范

### 前端（Vue 3）

- **必须**使用 `<script setup>` + Composition API，禁止 Options API
- 组件文件名 **PascalCase**，模板中使用 **PascalCase 自闭合标签**
- 优先使用 `ref` 而非 `reactive`；`v-for` 必须搭配 `:key`，禁止与 `v-if` 同级
- 样式必须加 `scoped`，深度选择器使用 `:deep()`
- **前台页面三文件模式**：`views/Xxx/index.vue` + `style.css` + `useXxx.js`
  - `index.vue` **只允许**：`import` 语句 + 调用一个 `useXxx()` 并解构其返回值，**禁止**在 `<script setup>` 里写任何 `ref`、`computed`、`watch`、`fetch`、`onMounted` 等业务逻辑
  - 所有状态、计算属性、副作用、API 调用**必须**放在对应的 `useXxx.js` 中
  - ✅ 正确示例（`index.vue` 的 `<script setup>`）：
    ```js
    import { useLiuyao } from './useLiuyao'
    const { form, result, submitDivination, ... } = useLiuyao()
    ```
  - ❌ 错误示例（在 `index.vue` 里写逻辑）：
    ```js
    const list = ref([])
    onMounted(() => { fetchList().then(r => list.value = r) })
    ```
- **后台**：列表用 `hooks/useTable.js`，表单用 `hooks/useForm.js`，弹窗用 `hooks/useDialog.js`

### 后端（ThinkPHP）

- 分层：Controller（参数验证）→ Service（业务逻辑）→ Model（数据操作）
- API 统一响应格式：`{ code: 0, msg: "success", data: {...} }`，`code: 0` 为成功
- 路由文件：`route/app.php`（前台）、`route/admin.php`（后台）
- 中间件链：Cors → JwtAuth/AdminAuth → RateLimit → 业务逻辑

### 数据库

- 表前缀 `tc_`，蛇形命名（如 `tc_bazi_records`）
- **`database/init.sql` 是唯一真相来源** — 它始终代表数据库的完整最新状态，新环境直接导入这一个文件即可
- **修改表结构时必须同步更新 `init.sql`**，而不是新建 `YYYYMMDD_xxx.sql` 补丁文件
- 上线后的增量变更（字段新增、索引调整）才放 `database/migrations/YYYYMMDD_描述.sql`，且 **SQL 必须幂等**（`IF NOT EXISTS`、`ON DUPLICATE KEY UPDATE` 等）
- 禁止创建与现有表功能重复的新表（如 `tc_system_config` 与 `system_config` 并存），发现重复表先确认哪个是代码实际使用的，再删除废弃表并更新 `init.sql`

---

## 4. API 路由约定

| 前缀 | 用途 | 认证 |
|------|------|------|
| `/api/auth/` | 用户认证 | 无 |
| `/api/paipan/` | 八字排盘 | JWT |
| `/api/tarot/` | 塔罗占卜 | JWT |
| `/api/daily/` | 每日运势/签到 | JWT/OptionalAuth |
| `/api/hehun/` | 合婚分析 | JWT |
| `/api/liuyao/` | 六爻占卜 | JWT |
| `/api/points/` | 积分系统 | JWT |
| `/api/maodou/` | 管理后台接口 | AdminAuth |

---

## 5. 关键架构

- **认证**：前台 JWT（`middleware/JwtAuth.php`）；后台独立 JWT（`middleware/AdminAuth.php`）+ RBAC
- **AI 分析**：DeepSeek API（`service/DeepSeekService.php`），Prompt 可在后台配置
- **积分**：`service/PointsService.php`；**VIP**：`service/VipService.php`
- **支付**：支付宝（`controller/Alipay.php`）+ 微信（`service/WechatPayService.php`）

---

## 6. 验证清单

**前端改动**
- [ ] `npm run build` 成功（`frontend/` 和/或 `admin/`）
- [ ] 新组件遵循项目文件组织模式，使用 Element Plus 现有组件

**后端改动**
- [ ] 新路由已在 `route/app.php` 或 `route/admin.php` 注册
- [ ] 业务逻辑在 Service 层，API 返回统一格式
- [ ] 涉及数据库变更时，创建了幂等的 `database/YYYYMMDD_xxx.sql`

**新增接口**
- [ ] 前端 `api/*.js` 中的请求路径与 `route/app.php` 或 `route/admin.php` 中注册的路由完全一致
- [ ] 前端期望的响应字段（如 `res.data.xxx`）与后端实际返回的字段名称、层级、类型完全匹配
- [ ] 后端返回的字段在前端模板/图表中均有对应消费，无"写了但没接通"的死字段

**通用**
- [ ] 改动最小化，无遗留 `console.log`，无硬编码密钥

---

## 7. 常见陷阱 ⚠️

1. **后台入口是 `/maodou/`** — 不是 `/admin/`，登录接口是 `POST /api/maodou/auth/login`
2. **本地后端走 phpstudy** — `http://localhost:8080`，不是 Docker
3. **大文件注意** — `Admin.php`（172KB）、`Hehun.php`（121KB）、`Paipan.php`（86KB），用精确替换而非全量重写
4. **前后台是独立 Vite 项目** — `frontend/` 和 `admin/` 各有独立 `package.json`，不要混淆
5. **线上用 Swoole** — 后端运行在 Swoole `127.0.0.1:8080`，Nginx 做反向代理
6. **不要猜测 API** — 查看 `route/app.php` 和 `route/admin.php` 获取实际路由
7. **API 路径唯一真相来源** — 新增 API 时，以 `backend/route/admin.php` 为唯一路径真相来源，前端 `admin/src/api/` 对应文件必须同步更新，禁止在页面组件里硬编码路径。路径不一致是历史 Bug 的主要根因（如前端调 `/site/faqs`，后端注册的是 `/faqs`）。
9. **控制器实现了但路由没注册** — 历史遗留问题：后端 Controller 方法已写好，但 `route/app.php` 或 `route/admin.php` 中忘记注册对应路由，导致前端调用 404。已知案例：`tarot/ai-analysis`（`Tarot::aiAnalysis`）、`liuyao/ai-analysis`（`Liuyao::aiAnalysis`）、`PUT points/rules`（`admin\Points::saveRules`）、前台 `site/faqs` 公开接口。**每次新增 Controller 方法后，必须立即在路由文件中注册，两者必须同步提交。**
8. **数据库表唯一真相来源** — `database/init.sql` 是数据库的唯一真相来源。每次修改表结构（新增字段、新建表、删除废弃表）都必须同步更新 `init.sql`，而不是堆叠新的补丁文件。历史教训：19 个迁移文件叠加导致同一张表出现 2-3 个版本并存（如 `tc_system_config` vs `system_config`、`hehun_records` vs `tc_hehun_record`），最终需要全量重整。
10. **`Db::table()` vs `Db::name()`** — ThinkPHP 中 `Db::table('bazi_record')` 是完整表名（不加前缀），`Db::name('bazi_record')` 才会自动加 `tc_` 前缀。所有原生查询必须用 `Db::name()`，否则查询必然失败。已知案例：`Statistics.php`、`BaziRecordController.php` 的 `statistics()` 方法。
11. **孤儿控制器** — 历史遗留问题：Controller 文件存在但 `route/app.php` 和 `route/admin.php` 均无路由注册，与 `admin/` 层功能重复。已知案例：`PointsController.php`、`UserController.php`。发现此类文件先确认无调用方后直接删除，避免混淆。
12. **空壳页面文件** — 历史遗留问题：`.vue` 文件存在（体积极小 ≤ 1KB）但内容为空壳，且未在路由中注册。已知案例：`content/bazi.vue`、`content/tarot.vue`。发现此类文件直接删除，不要保留占位文件。
13. **前端响应字段与后端返回不匹配** — 前端期望 `res.data.chart_data` 等字段，但后端实际未返回，导致图表渲染使用硬编码假数据。新增接口时必须对齐前后端字段，验证清单中已有对应检查项。
14. **页面按钮无事件绑定** — 历史遗留问题：页面只有只读列表，新增/编辑/删除按钮存在于模板中但未绑定任何 `@click` 事件。已知案例：`vip-packages.vue`、`system/admins.vue`。新建管理页面时必须同步实现所有操作按钮的事件逻辑。

---

## 8. 文件索引（快速定位）

| 要找什么 | 去哪里看 |
|---------|---------|
| 前台路由 | `frontend/src/router/index.js` |
| 后台路由 | `admin/src/router/index.js` |
| 后端 API 路由 | `backend/route/app.php`（前台）、`backend/route/admin.php`（后台） |
| 前台 API 封装 | `frontend/src/api/index.js`、`frontend/src/api/request.js` |
| 数据库模型 | `backend/app/model/` |
| 业务逻辑 | `backend/app/service/` |
| 数据库迁移 | `database/` |
| 部署配置 | `deploy/deploy.sh`、`deploy/nginx/taichu.conf` |
| 环境变量模板 | `backend/.env.example` |
