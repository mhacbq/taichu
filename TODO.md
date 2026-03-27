# 管理端问题清单 (TODO)

> 生成时间：2026-03-27  
> 检查范围：`backend/route/admin.php` + `backend/app/controller/admin/`

---

## 🔴 P0 严重 — 会导致功能直接报错/不可用

### [P0-1] 路由冲突：`order/packages` 被 `order/:id` 拦截
- **文件**：`backend/route/admin.php` 第 163-166 行
- **问题**：`order/:id` 定义在 `order/packages` 之前，访问 `GET /order/packages` 时 `packages` 会被当作 `:id` 参数，导致套餐列表接口永远返回 404 或错误数据
- **修复**：将 `order/packages` 和 `order/save-package` 移到 `order/:id` 之前
- **状态**：[x] 已修复

```php
// 当前错误顺序：
Route::get('order', 'admin.Order/index');
Route::get('order/:id', 'admin.Order/detail');      // ← 先定义了动态路由
Route::post('order/refund', 'admin.Order/refund');
Route::get('order/packages', 'admin.Order/packages'); // ← 被上面拦截！
Route::post('order/save-package', 'admin.Order/savePackage'); // ← 同样被拦截！

// 正确顺序：
Route::get('order', 'admin.Order/index');
Route::post('order/refund', 'admin.Order/refund');
Route::get('order/packages', 'admin.Order/packages');
Route::post('order/save-package', 'admin.Order/savePackage');
Route::get('order/:id', 'admin.Order/detail');      // ← 动态路由放最后
```

---

### [P0-2] 路由冲突：`seo/list` 和 `seo/page-types` 被 `seo/:id` 拦截
- **文件**：`backend/route/admin.php` 第 293-298 行
- **问题**：`seo/:id` 定义在 `seo/list`、`seo/page-types` 之前，`list` 和 `page-types` 会被当作 `:id` 参数
- **修复**：将静态路由移到动态路由之前
- **状态**：[x] 已修复

```php
// 当前错误顺序：
Route::get('seo/list', 'admin.Seo/getList');
Route::get('seo/:id', 'admin.Seo/getDetail');       // ← 动态路由在中间
Route::post('seo/save', 'admin.Seo/save');
Route::delete('seo/:id', 'admin.Seo/delete');
Route::post('seo/batch-status', 'admin.Seo/batchUpdateStatus');
Route::get('seo/page-types', 'admin.Seo/getPageTypes'); // ← 被 seo/:id 拦截！

// 正确顺序：
Route::get('seo/list', 'admin.Seo/getList');
Route::post('seo/save', 'admin.Seo/save');
Route::post('seo/batch-status', 'admin.Seo/batchUpdateStatus');
Route::get('seo/page-types', 'admin.Seo/getPageTypes');
Route::get('seo/:id', 'admin.Seo/getDetail');       // ← 动态路由放最后
Route::delete('seo/:id', 'admin.Seo/delete');
```

---

### [P0-3] `FeedbackAssign` 控制器未继承 `BaseController`，完全绕过认证体系
- **文件**：`backend/app/controller/admin/FeedbackAssign.php`
- **问题**：
  1. 类声明 `class FeedbackAssign`（未继承 `BaseController`），没有 `$middleware` 声明
  2. 响应格式用 `json([...])` 而非 `$this->success()`/`$this->error()`，与其他接口格式不一致
  3. 没有任何权限检查（其他控制器都有 `hasAdminPermission()`）
- **修复**：重构为继承 `BaseController`，添加权限检查，统一响应格式
- **状态**：[x] 已修复

---

### [P0-4] `Feedback.php` 和 `FeedbackAssign.php` 使用了错误的用户字段 `$request->user`
- **文件**：
  - `backend/app/controller/admin/Feedback.php`（`reply` 方法第 115 行，`updateStatus` 方法第 170 行）
  - `backend/app/controller/admin/FeedbackAssign.php`（`assign`、`updateStatus`、`myAssignments` 方法）
- **问题**：管理端中间件 `AdminAuth` 注入的是 `$request->adminUser`，但代码中使用了 `$request->user['id']`，导致 `admin_id` 始终为 `null`，反馈日志写入错误数据
- **修复**：将所有 `$request->user['id']` 改为 `$request->adminUser['id']`（或使用 `$this->getAdminId()`）
- **状态**：[x] 已修复

具体位置：
```php
// Feedback.php reply() 方法
'admin_id' => $request->user['id'],  // ❌ 错误
'admin_id' => $this->getAdminId(),   // ✅ 正确

// Feedback.php updateStatus() 方法
'admin_id' => $request->user['id'],  // ❌ 错误

// FeedbackAssign.php assign() 方法
'assigned_by' => $request->user['id'],  // ❌ 错误
'admin_id' => $request->user['id'],     // ❌ 错误

// FeedbackAssign.php updateStatus() 方法
'admin_id' => $request->user['id'],  // ❌ 错误

// FeedbackAssign.php myAssignments() 方法
$adminId = $request->user['id'];  // ❌ 错误
```

---

## 🟠 P1 中等 — 功能存在但行为异常

### [P1-1] `ai-prompts/types` 路由被 `ai-prompts/:id` 拦截
- **文件**：`backend/route/admin.php` 第 214-221 行
- **问题**：`ai-prompts/types` 定义在 `ai-prompts/:id` 之后，`types` 会被当作 `:id` 参数
- **修复**：将 `ai-prompts/types` 移到 `ai-prompts/:id` 之前
- **状态**：[x] 已修复

```php
// 当前错误顺序：
Route::get('ai-prompts/detail/:id', 'admin.AiPrompt/getDetail');
Route::post('ai-prompts/save', 'admin.AiPrompt/save');
Route::delete('ai-prompts/:id', 'admin.AiPrompt/delete');
Route::post('ai-prompts/:id/default', 'admin.AiPrompt/setDefault');
Route::post('ai-prompts/:id/preview', 'admin.AiPrompt/preview');
Route::post('ai-prompts/:id/duplicate', 'admin.AiPrompt/duplicate');
Route::get('ai-prompts/types', 'admin.AiPrompt/getTypes'); // ← 被 ai-prompts/:id 拦截！

// 正确顺序：
Route::get('ai-prompts/types', 'admin.AiPrompt/getTypes'); // ← 移到最前
Route::get('ai-prompts/detail/:id', 'admin.AiPrompt/getDetail');
...
```

---

### [P1-2] `tasks/logs` 和 `tasks/scripts` 被 `tasks/:id` 拦截
- **文件**：`backend/route/admin.php` 第 306-316 行
- **问题**：`tasks/logs`、`tasks/scripts` 定义在 `tasks/:id` 之前，但 `tasks/:id` 在第 312 行，`tasks/logs` 在第 307 行，顺序正确。**需要再次确认**：`tasks/logs` 在 `tasks/:id` 之前，暂时无冲突。但 `tasks/scripts` 在第 308 行，`tasks/:id` 在第 312 行，也无冲突。
- **状态**：[x] 经核查无冲突，可关闭

---

### [P1-3] `vip-packages/detail/:id` 路由格式不统一
- **文件**：`backend/route/admin.php` 第 127-133 行
- **问题**：`vip-packages/detail/:id` 的 `detail` 是多余的路径段，其他模块（如 `bazi-manage/:id`）直接用 `/:id`，风格不一致，前端调用时容易混淆
- **建议**：统一为 `vip-packages/:id`（需同步前端调用）
- **状态**：[ ] 搁置（`detail` 在 `:id` 之前，不存在路由冲突，仅风格问题；改动需同步前端，成本高，暂不处理）

---

### [P1-4] `Content` 控制器使用了不存在的 `$request->adminId` / `$request->adminName` 字段
- **文件**：`backend/app/controller/Content.php`
- **问题**：控制器内 10 处使用了 `$request->adminId` 和 `$request->adminName`，但 `AdminAuth` 中间件只注入 `$request->adminUser`（含 `id`、`username` 等字段），导致所有涉及操作者记录的字段（`author_id`、`updated_by`、`deleted_by` 等）均为 `null`
- **修复**：将所有 `$request->adminId` 改为 `$this->getAdminId()`，`$request->adminName` 改为 `$this->request->adminUser['username'] ?? ''`
- **状态**：[x] 已修复（共修复 10 处）

---

### [P1-5] `Ai.php` 测试连接仅发送阿里云格式请求体，不兼容标准 OpenAI 接口
- **文件**：`backend/app/controller/admin/Ai.php` 第 107-122 行
- **问题**：`testConnection()` 发送的请求体使用阿里云格式（`input.messages` + `parameters`），对标准 OpenAI 接口（DeepSeek、OpenAI 等）会返回 400 错误，导致测试连接**误报失败**
- **修复**：根据 `ai_api_url` 判断接口类型，或统一改为标准 OpenAI 格式（`messages` 在顶层）
- **状态**：[ ] 已搁置（用户确认当前只用阿里云模式，暂时搁置）

---

## 🟡 P2 轻微 — 代码规范/潜在风险

### [P2-1] 34 个路由指向 `Admin/xxx`（大写，非 `admin.xxx`）
- **文件**：`backend/route/admin.php`
- **问题**：路由中大量使用 `'Admin/chartData'`、`'Admin/riskEvents'` 等格式，指向 `app\controller\Admin.php`（174KB 的巨型控制器）。这些功能未拆分到 `admin/` 子目录，与其他模块风格不一致
- **涉及功能**：Dashboard 图表、用户导出、内容管理（每日运势）、反作弊系统、系统设置、管理员管理、任务调度
- **建议**：逐步将 `Admin.php` 中的方法拆分到对应的子控制器
- **状态**：[ ] 长期技术债，逐步重构

---

### [P2-2] `dashboard/chart/:type`、`dashboard/realtime` 等路由指向不存在的 `Admin/` 方法
- **文件**：`backend/route/admin.php` 第 20-23 行
- **问题**：`Admin/chartData`、`Admin/realtime`、`Admin/exportRealtime` 需要确认在 `Admin.php` 中是否实现
- **状态**：[x] 已验证无问题（三个方法均存在于 `Admin.php` 第 190、3577、3595 行）

---

### [P2-3] `feedback/assign/:id/status` 路由参数传递方式不一致
- **文件**：`backend/route/admin.php` 第 185 行，`backend/app/controller/admin/FeedbackAssign.php` `updateStatus` 方法
- **问题**：路由定义了 `:id` 参数，但 `updateStatus` 方法从 `$request->post()` 中读取 `id`，而非从路由参数获取，导致路由中的 `:id` 参数被忽略
- **状态**：[x] 已修复（P0-3 重构时一并修复，updateStatus 方法签名改为接收路由参数 int $id）

---

## 📋 修复优先级汇总

| 优先级 | ID | 问题 | 影响 |
|--------|-----|------|------|
| 🔴 P0 | P0-1 | `order/packages` 路由冲突 | ✅ 已修复 |
| 🔴 P0 | P0-2 | `seo/page-types` 路由冲突 | ✅ 已修复 |
| 🔴 P0 | P0-3 | `FeedbackAssign` 未继承 BaseController | ✅ 已修复 |
| 🔴 P0 | P0-4 | `$request->user` 字段错误 | ✅ 已修复 |
| 🟠 P1 | P1-1 | `ai-prompts/types` 路由冲突 | ✅ 已修复 |
| 🟠 P1 | P1-4 | `Content` 控制器 `$request->adminId` 字段错误 | ✅ 已修复（10 处）|
| 🟠 P1 | P1-5 | AI 测试连接格式问题 | ⏸️ 已搁置（仅用阿里云模式）|
| 🟡 P2 | P2-1 | `Admin.php` 巨型控制器 | ⏳ 长期技术债 |
| 🟡 P2 | P2-2 | `Admin/chartData` 等方法是否存在 | ✅ 已验证存在 |
| 🟡 P2 | P2-3 | `feedback/assign/:id/status` 参数问题 | ✅ 已修复 |
