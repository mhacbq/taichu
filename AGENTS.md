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
15. **业务码判断必须用 `code === 0`，绝不是 `200`** — HTTP 状态码与业务码是两个不同层次的概念：axios 拦截器已处理 HTTP 层（`response.status`），`response.data.code` 是业务层，后端统一约定 `0` 为成功（见第78行 API 规范）。写 `code === 200` 会导致"静默失败"——API 请求成功（HTTP 200）但业务判断失败，数据不显示且无任何报错，极难排查。**历史教训：前台 + 管理端共 108 处此类错误，导致几乎所有核心功能（登录/八字/合婚/六爻/塔罗/充值/VIP）数据不显示，2026-03-25 批量修复。** 变量名不限（`res`、`response`、`userRes`、`data` 等），只要是业务响应码，一律 `=== 0`。

16. **TODO.md 条目必须有复现步骤，禁止纯静态分析生成** — 历史教训：AI 通过静态代码扫描批量生成了大量"看起来像 bug"的 TODO 条目，但实际验证后约 60% 不存在（代码已有保护、设计如此、或功能已实现）。**强制规则**：
    - 每条 TODO 条目必须包含**复现步骤**（哪个页面 → 哪个操作 → 看到什么现象），否则不得入队
    - 纯靠静态代码分析生成的条目，必须标注 `[待确认]`，不得直接进入修复队列
    - 修复前必须先**实际验证**问题存在（运行代码、查看日志、或阅读完整代码上下文），禁止"看到代码模式就认为是 bug"
    - 体验优化类（按钮尺寸、间距调整等）不属于 Bug，应单独开迭代，不得混入 Bug 修复队列
    - **写入 TODO.md 前必须先读取文件头的「📝 条目格式模板」章节，按模板格式填写**（包含复现步骤、来源、预期行为三个字段），不符合模板格式的条目不得写入

17. **`admin.php` 路由顺序：静态路由必须在动态路由之前** — ThinkPHP 路由按定义顺序匹配，同一前缀下若动态路由（`/:id`）定义在静态路由（`/list`、`/types`、`/packages`）之前，静态路由的路径段会被当作 `:id` 参数捕获，导致接口永远 404 或返回错误数据。**强制规则**：
    - 同一前缀下，所有静态路由（路径中无 `:` 参数的路由）必须定义在动态路由之前
    - 新增路由时，检查同前缀下是否已有 `/:id` 等动态路由，若有则将新静态路由插入到动态路由之前
    - 已知历史案例：`order/packages` 被 `order/:id` 拦截、`seo/page-types` 被 `seo/:id` 拦截、`ai-prompts/types` 被 `ai-prompts/:id` 拦截（均已修复，2026-03-27）
    - ✅ 正确顺序示例：
      ```php
      Route::get('order', 'admin.Order/index');
      Route::post('order/refund', 'admin.Order/refund');   // 静态路由在前
      Route::get('order/packages', 'admin.Order/packages'); // 静态路由在前
      Route::get('order/:id', 'admin.Order/detail');        // 动态路由在后
      ```
    - ❌ 错误顺序示例：
      ```php
      Route::get('order/:id', 'admin.Order/detail');        // 动态路由在前 ← 会拦截下面所有静态路由
      Route::get('order/packages', 'admin.Order/packages'); // 永远不会匹配！
      ```

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

---

## 9. UI/UX 质量约束 ⚠️【AI 必读】

> 本章节是 AI 进行任何前端改动时的强制约束，违反任何一条都视为质量事故。

### 9.1 视觉规范红线（禁止违反）

| 规范项 | 约束值 | 说明 |
|--------|--------|------|
| **主背景色** | `#FFFFFF`（白色） | 禁止改为深色/灰色背景，用户已明确拒绝深色方案 |
| **主题金色** | `#D4AF37` / `#FFD700` | 所有强调色、标题色、图标色必须使用此色系 |
| **正文字色** | `#333333` / `#666666` | 禁止使用纯黑 `#000000` 作为正文色 |
| **圆角规范** | 卡片 `8px`，按钮 `6px`，输入框 `4px` | 不得随意修改圆角值 |
| **间距单位** | 使用 `4px` 倍数（8/12/16/24/32px） | 禁止出现奇数间距（如 `7px`、`13px`） |
| **字体大小** | 标题 `20-28px`，正文 `14-16px`，辅助 `12px` | 禁止正文字体小于 `12px` |

### 9.2 AI 开发禁止行为

1. **禁止删除现有 CSS 类** — 改动样式时只允许覆盖/追加，不允许删除已有类名（可能影响其他页面）
2. **禁止修改全局样式文件** — `frontend/src/styles/` 下的文件不得随意修改，修改前必须说明影响范围
3. **禁止硬编码颜色值** — 所有颜色必须使用 CSS 变量或已定义的 Element Plus 主题变量
4. **禁止改动配色方案** — 白色背景 + 金色主题是固定方案，任何"优化"提案都不得改变这一基调
5. **禁止引入新的 UI 框架** — 项目已使用 Element Plus，禁止引入 Ant Design、Naive UI 等其他组件库
6. **禁止在移动端隐藏核心功能** — 移动端适配只能调整布局，不能隐藏功能入口

### 9.3 每次前端改动后必须自查（AI 强制执行）

**视觉自查**
- [ ] 改动后页面背景是否仍为白色？
- [ ] 主题金色 `#D4AF37` 是否保持一致？
- [ ] 新增元素的间距是否符合 4px 倍数规范？
- [ ] 移动端（375px 宽度）是否出现横向滚动条？
- [ ] 文字是否有溢出容器的情况？

**功能自查**
- [ ] 新增/修改的按钮点击后是否有响应（loading 状态 / 成功提示）？
- [ ] 表单提交是否有错误提示（空字段、格式错误）？
- [ ] API 调用失败时是否有友好的错误提示（非 console.error）？
- [ ] 列表为空时是否有空状态提示（非空白页面）？
- [ ] 页面加载时是否有骨架屏或 loading 状态？

**移动端自查**
- [ ] 按钮点击区域是否 ≥ 44px（iOS 最小可点击区域）？
- [ ] 弹窗/抽屉是否在移动端正常显示，不超出屏幕？
- [ ] 输入框在软键盘弹出时是否被遮挡？

### 9.4 AI 巡查协议（像真实用户一样检查）

每次执行 UI 巡查时，AI 必须按以下顺序模拟真实用户行为：

**第一步：首屏检查**
- 页面加载是否有 loading 状态
- 首屏内容是否在 3 秒内可见
- 导航栏是否完整显示，Logo/标题是否正确

**第二步：核心功能路径**
- 未登录状态：首页 → 点击功能 → 是否正确引导登录
- 登录后：首页 → 八字排盘 → 填写信息 → 提交 → 查看结果
- 积分路径：查看积分余额 → 消耗积分功能 → 积分扣减是否正确

**第三步：边界场景**
- 网络慢时：按钮是否有 disabled 防重复提交
- 数据为空时：是否有空状态提示
- 表单错误时：是否有明确的错误信息

**第四步：移动端专项**
- 375px 宽度下所有页面是否正常
- 底部导航是否被内容遮挡
- 长列表是否有滚动加载

**巡查输出格式**：
```
## 巡查报告 - [日期]

### 🔴 严重问题（影响核心功能）
- [页面] [问题描述] [复现步骤]

### 🟡 中等问题（影响用户体验）
- [页面] [问题描述]

### 🟢 轻微问题（视觉/细节）
- [页面] [问题描述]

### ✅ 正常项
- [通过检查的功能列表]
```

---

## 10. Git 提交规范 ⚠️【AI 必须执行】

> 每次完成一批整体修改后，**必须**将代码提交到 Git 远程仓库，这是强制要求，不可省略。

### 10.1 提交时机（以下任一场景必须提交）

- 完成一个完整功能的开发或修复
- 完成一轮 UI/UX 优化
- 完成一次 Bug 修复
- 完成数据库结构变更（含 init.sql 更新）
- 完成一次代码重构
- 用户明确说"好了/完成/可以了"时

### 10.2 提交流程（AI 强制执行）

```bash
# 第一步：确认当前在正确目录
cd /data/workspace/taichu

# 第二步：查看变更文件
git status

# 第三步：添加所有变更
git add -A

# 第四步：提交（commit message 必须描述本次改动内容）
git commit -m "feat/fix/chore: 简短描述本次改动"

# 第五步：推送到远程
git push origin master
```

### 10.3 Commit Message 规范

| 前缀 | 适用场景 | 示例 |
|------|---------|------|
| `feat:` | 新功能 | `feat: 八字合婚页面添加历史记录功能` |
| `fix:` | Bug 修复 | `fix: 修复移动端菜单抽屉初始状态异常` |
| `style:` | 纯样式/UI 调整 | `style: 优化首页卡片间距和字体大小` |
| `refactor:` | 代码重构 | `refactor: 提取公共 useForm 组合式函数` |
| `chore:` | 配置/规范/文档 | `chore: 更新 AGENTS.md 添加 Git 提交规范` |
| `perf:` | 性能优化 | `perf: 添加 Redis 缓存减少数据库查询` |
| `docs:` | 文档更新 | `docs: 更新 API 接口文档` |

### 10.4 禁止行为

1. **禁止只提交不推送** — `git commit` 后必须紧跟 `git push`
2. **禁止空 commit message** — 必须有描述性的提交信息
3. **禁止一次性提交所有历史积累** — 应按功能模块分批提交
4. **禁止提交敏感信息** — `.env` 文件、密钥、密码不得提交

### 10.5 AI 自查（每次提交前）

- [ ] 本次改动是否已完整（没有遗漏文件）？
- [ ] `git status` 确认变更文件列表是否符合预期？
- [ ] commit message 是否清晰描述了改动内容？
- [ ] 是否已执行 `git push origin master`？
- [ ] 推送是否成功（无报错）？
