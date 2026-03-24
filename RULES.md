# RULES.md — AI 工作规则

---

## 任务前必读顺序

1. `SOUL.md` — 项目价值观（最高优先级）
2. `AGENTS.md` — 项目结构和编码规范
3. `TOOLS.md` — 命令速查
4. `TODO.md` — 当前任务清单

---

## 工作流程

### 开始任务
1. **理解需求** — 明确目标，避免 XY 问题；动机模糊时先讨论
2. **研究现有代码** — 找 3 个相似功能，理解项目模式
3. **最小化方案** — 设计改动最小的实现路径

### 新增功能页面（前台）
1. `frontend/src/views/Xxx/` 创建 `index.vue` + `style.css` + `useXxx.js`
2. `frontend/src/router/index.js` 添加路由
3. `backend/app/controller/` 添加控制器
4. `backend/route/app.php` 注册路由
5. 如需数据库变更，创建 `database/YYYYMMDD_xxx.sql`

### 新增后台页面
1. `admin/src/views/` 创建 `.vue` 文件
2. `admin/src/router/index.js` 添加路由
3. `backend/app/controller/admin/` 添加控制器
4. `backend/route/admin.php` 注册路由

### 修复 Bug
1. 复现问题 → 定位根因 → 最小化修改 → 验证不影响其他功能

---

## 禁止行为

- ❌ 不读配置文件直接开始编码
- ❌ 猜测代码位置而不使用搜索工具
- ❌ 修改无关模块代码
- ❌ 遗留 `console.log` 或硬编码密钥
- ❌ 同一工具连续失败超过 3 次仍继续重试（换方案）

---

## 完成任务自查

- [ ] 构建通过（`npm run build` / `php think run`）
- [ ] 无 lint 错误
- [ ] 改动最小化，未破坏其他功能
- [ ] 遵循 `AGENTS.md` 编码规范
