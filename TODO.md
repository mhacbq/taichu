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
- **状态**：[x] 已完成（2026-03-27）

  拆分结果：
  - `admin/Anticheat.php` — 反作弊系统（8个方法）
  - `admin/Task.php` — 任务调度（11个方法）
  - `admin/DailyFortuneManage.php` — 每日运势管理（4个方法）
  - `admin/AdminUser.php` — 管理员账号管理（4个方法）
  - `admin/SystemSettings.php` — 系统设置（2个方法）
  - `admin/Dashboard.php` 追加 — 图表/实时看板（3个方法）
  - `admin/User.php` 追加 — 用户导出（1个方法）
  - `route/admin.php` 全部路由已切换到子控制器，`Admin/xxx` 路由清零

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
| 🟡 P2 | P2-1 | `Admin.php` 巨型控制器 | ✅ 已完成（拆分7个子控制器）|
| 🟡 P2 | P2-2 | `Admin/chartData` 等方法是否存在 | ✅ 已验证存在 |
| 🟡 P2 | P2-3 | `feedback/assign/:id/status` 参数问题 | ✅ 已修复 |

---

# 前台功能问题清单 (TODO)

> 生成时间：2026-03-27  
> 检查范围：`frontend/src/views/` + `frontend/src/api/` + `backend/app/controller/Vip.php`

---

## 🔴 P0 严重 — 会导致功能直接报错/不可用

### [FE-P0-1] VIP 控制器缺少 4 个前端调用的方法
- **文件**：`backend/app/controller/Vip.php`
- **问题**：路由 `app.php` 已注册 `packages/purchase/status/records` 4 个路由，但 `Vip.php` 控制器中**没有对应方法**，调用时直接 500 报错
- **缺失方法对照**：

| 路由 | 前端 API | 缺失方法 | 现有替代 |
|------|---------|---------|---------|
| `GET /vip/packages` | `getVipPackages()` | `packages()` | 无（`benefits()` 只返回权益，不含套餐列表） |
| `POST /vip/purchase` | `purchaseVip({package_id, payment_method})` | `purchase()` | `subscribe()` 参数格式不同 |
| `GET /vip/status` | `getUserVipStatus()` | `status()` | `info()` 返回格式不同 |
| `GET /vip/records` | `getVipRecords(params)` | `records()` | `orders()` 功能相同但路由不同 |

- **修复方案**：在 `Vip.php` 中补充 4 个方法（可复用 `VipService` 现有逻辑）：
  - `packages()`：返回套餐列表（从 `ConfigService` 读取价格，组装成前端期望的格式）
  - `purchase()`：接收 `package_id` + `payment_method`，调用 `VipService::createOrder()`
  - `status()`：返回 `{is_vip, expire_time}`（前端期望格式）
  - `records()`：等同于 `orders()`，支持分页参数
- **状态**：[ ] 待修复

---

### [FE-P0-2] VIP 购买弹窗提示"消耗 X 积分"但实际是人民币金额
- **文件**：`frontend/src/views/Vip/useVip.js` 第 155 行
- **问题**：`handleSubscribe` 弹窗文案 `将消耗 ${plan.price} 积分`，但 `plan.price` 是人民币金额（29/68/198），严重误导用户
- **同时**：`payment_method` 写死为 `'points'`（积分支付），与 VIP 真实付费逻辑矛盾
- **修复方案**：
  1. 弹窗文案改为 `将支付 ¥${plan.price} 开通 VIP`
  2. `payment_method` 改为 `'alipay'`（或根据用户选择）
- **状态**：[ ] 待修复

---

## 🟠 P1 中等 — 功能存在但行为异常

### [FE-P1-1] 每日运势：明日预告星级是随机数，非真实数据
- **文件**：`frontend/src/views/Daily/useDaily.js` 第 38-43 行
- **问题**：`tomorrowStarCount.value = Math.floor(Math.random() * 3) + 3`，每次刷新星级随机变化；`tomorrowSummary` 是硬编码文案，与实际运势无关
- **现象**：用户 18 点后看到明日预告，刷新后星级变化，内容是假数据，严重影响可信度
- **修复方案**：后端 `Daily/fortune` 接口增加 `tomorrow` 字段，或新增 `GET /daily/tomorrow` 接口
- **状态**：[ ] 待修复

### [FE-P1-2] 每日运势：签到功能缺失
- **文件**：`frontend/src/views/Daily/useDaily.js`、`frontend/src/views/Daily/index.vue`
- **问题**：后端有 `POST /api/daily/checkin` 接口，`api/index.js` 也有 `dailyCheckin` 函数，但 Daily 页面**完全没有调用签到 API**，签到按钮/入口不存在
- **现象**：用户无法在每日运势页面签到，个人中心任务列表的签到跳转也无效
- **修复方案**：在 `Daily/index.vue` 增加签到按钮，`useDaily.js` 增加 `handleCheckin()` 方法
- **状态**：[ ] 待修复

---

## 🟡 P2 轻微 — 代码规范/潜在风险

### [FE-P2-1] `payment.js` 与 `index.js` 中 `getRechargeHistory` 定义重复且不一致
- **文件**：`frontend/src/api/payment.js` 第 13 行 vs `frontend/src/api/index.js`
- **问题**：`payment.js` 的 `getRechargeHistory` 没有 `params` 参数，无法分页；`useRecharge.js` 从 `payment.js` 导入，导致充值记录无法分页
- **修复方案**：`payment.js` 中补充 `params` 参数：`(params) => request.get('/payment/history', { params })`
- **状态**：[ ] 待修复

### [FE-P2-2] 未登录用户进入每日运势页面直接弹出生日设置弹窗，体验不佳
- **文件**：`frontend/src/views/Daily/useDaily.js` 第 302 行
- **问题**：未登录时直接 `showBirthdayDialog.value = true`，应先引导登录
- **修复方案**：未登录时跳转登录页，登录后再检查生日
- **状态**：[ ] 待修复

### [FE-P2-3] `alipay.js` URL 路径风格不统一（绝对路径 vs 相对路径）
- **文件**：`frontend/src/api/alipay.js`
- **问题**：`alipay.js` 使用绝对路径 `/api/alipay/...`（绕过 baseURL），其他 API 文件使用相对路径（依赖 baseURL），风格不一致，容易引起误解
- **状态**：[ ] 低优先级，可搁置

---

## 📋 前台修复优先级汇总

| 优先级 | ID | 问题 | 状态 |
|--------|-----|------|------|
| 🔴 P0 | FE-P0-1 | VIP 控制器缺少 4 个方法（packages/purchase/status/records） | ✅ 已修复 |
| 🔴 P0 | FE-P0-2 | VIP 购买弹窗提示"消耗积分"但实为人民币 | ✅ 已修复 |
| 🟠 P1 | FE-P1-1 | 明日预告星级随机数 | ⏳ 待修复 |
| 🟠 P1 | FE-P1-2 | 每日运势签到功能缺失 | ⏳ 待修复 |
| 🟡 P2 | FE-P2-1 | `getRechargeHistory` 无分页参数 | ⏳ 待修复 |
| 🟡 P2 | FE-P2-2 | 未登录直接弹生日弹窗 | ⏳ 待修复 |
| 🟡 P2 | FE-P2-3 | `alipay.js` 路径风格不统一 | ⏸️ 搁置 |

---

# 自动巡查发现的新 Bug（2026-03-27）

> 来源：`node check-bugs.js http://localhost:8080 13800138000 123456 admin admin123`  
> 巡查时间：2026-03-27 11:34  
> 结果：42 项检查，通过 25，失败 17  
> 所有条目均有后端日志或 HTTP 响应作为复现依据

---

## 🔴 P0 严重 — 直接导致接口 500 / 功能不可用

### [NEW-P0-1] `tc_sms_code` 表缺少 `is_used` 字段，发送短信验证码 500
- **复现步骤**：前台登录页 → 输入手机号 → 点击「获取验证码」→ 接口返回 500
- **来源**：后端日志 `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'is_used' in 'where clause'`
- **根因**：`SmsCode` 模型的 `schema` 定义了 `is_used` 字段，但 `database/init.sql` 中 `tc_sms_code` 表没有该字段
- **修复**：在 `database/init.sql` 的 `tc_sms_code` 表中补充 `is_used TINYINT DEFAULT 0` 字段，并执行 `ALTER TABLE tc_sms_code ADD COLUMN is_used TINYINT DEFAULT 0`
- **状态**：[ ] 待修复

---

### [NEW-P0-2] `tc_points_task` 表缺少 `points_reward` 字段，用户信息接口 500
- **复现步骤**：登录后访问任意需要用户信息的页面 → 接口 `/api/auth/userinfo` 返回 500
- **来源**：后端日志 `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'points_reward' in 'field list'`
- **根因**：代码中查询 `tc_points_task` 时使用了 `points_reward` 字段，但数据库表中该字段不存在（可能字段名已改为 `reward_points` 或类似名称）
- **修复**：检查 `tc_points_task` 表实际字段名，统一代码与数据库字段名
- **状态**：[ ] 待修复

---

### [NEW-P0-3] `checkin_record` 表不存在（缺少 `tc_` 前缀），签到状态接口 500
- **复现步骤**：登录后访问每日运势页面 → 签到状态接口 `/api/daily/checkin-status` 返回 500
- **来源**：后端日志 `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'taichu.checkin_record' doesn't exist`
- **根因**：代码使用 `Db::table('checkin_record')` 而非 `Db::name('checkin_record')`，导致查询时不加 `tc_` 前缀，实际表名应为 `tc_checkin_record`
- **修复**：将相关代码中的 `Db::table('checkin_record')` 改为 `Db::name('checkin_record')`（或直接 `Db::table('tc_checkin_record')`）
- **状态**：[ ] 待修复

---

### [NEW-P0-4] `HehunRecord.php` 第 56 行语法错误，合婚相关接口全部 500
- **复现步骤**：登录后访问合婚页面 → 历史记录接口 `/api/hehun/history` 返回 500；合婚定价接口 `/api/hehun/pricing` 也返回 500
- **来源**：后端日志 `[0]语法错误: unexpected identifier "male_birth", expecting "]"[...backend/app/model/HehunRecord.php:56]`
- **根因**：`HehunRecord.php` 第 56 行附近的 PHP 数组语法错误，`male_birth` 前可能缺少引号或逗号
- **修复**：打开 `backend/app/model/HehunRecord.php` 第 56 行，修复数组语法错误
- **状态**：[ ] 待修复

---

### [NEW-P0-5] `tc_user_vip` 表不存在，VIP 状态接口 500
- **复现步骤**：登录后访问 VIP 页面 → 接口 `/api/vip/status` 返回 500
- **来源**：后端日志 `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'taichu.tc_user_vip' doesn't exist`
- **根因**：代码查询 `tc_user_vip` 表，但 `database/init.sql` 中没有该表（VIP 信息存储在 `tc_user` 表的 `vip_level`/`vip_expire_at` 字段中）
- **修复**：检查 VIP 相关 Service/Controller，将 `tc_user_vip` 查询改为从 `tc_user` 表读取 `vip_level` 和 `vip_expire_at` 字段
- **状态**：[ ] 待修复

---

### [NEW-P0-6] `tc_task_log` 表不存在，任务列表接口 500
- **复现步骤**：登录后访问个人中心任务页面 → 接口 `/api/tasks/list` 返回 500
- **来源**：后端日志 `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'taichu.tc_task_log' doesn't exist`
- **根因**：代码查询 `tc_task_log` 表，但 `database/init.sql` 中没有该表（积分任务记录存储在 `tc_points_record` 表中）
- **修复**：在 `database/init.sql` 中补充 `tc_task_log` 表定义，或将代码改为查询 `tc_points_record`
- **状态**：[ ] 待修复

---

### [NEW-P0-7] `admin\User` 控制器中 `adjustPoints()` 方法重复声明，用户列表接口 500
- **复现步骤**：管理后台 → 用户管理页面 → 接口 `/api/maodou/users` 返回 500
- **来源**：后端日志 `[64]Cannot redeclare app\controller\admin\User::adjustPoints()[...backend/app/controller/admin/User.php:651]`
- **根因**：`backend/app/controller/admin/User.php` 第 651 行附近存在重复的 `adjustPoints()` 方法定义（可能是合并代码时重复粘贴）
- **修复**：打开 `User.php`，删除重复的 `adjustPoints()` 方法，保留一个
- **状态**：[ ] 待修复

---

### [NEW-P0-8] `admin\AiPrompt` 类重复声明，AI 提示词接口全部 500
- **复现步骤**：管理后台 → AI 提示词管理页面 → 接口 `/api/maodou/ai-prompts/list` 和 `/api/maodou/ai-prompts/types` 均返回 500
- **来源**：后端日志 `[64]Cannot declare class app\controller\admin\AiPrompt because the name is already in use[...backend/app/controller/admin/AiPrompt.php:9]`
- **根因**：`AiPrompt.php` 被 PHP 加载了两次（可能存在两个同名文件，或 `require`/`include` 重复引入）
- **修复**：检查是否存在重复的 `AiPrompt.php` 文件（如 `admin/AiPrompt.php` 和 `controller/AiPrompt.php`），删除重复文件
- **状态**：[ ] 待修复

---

## 🟠 P1 中等 — 接口可访问但功能受限

### [NEW-P1-1] VIP 套餐公开接口 `/api/vip/packages` 被 Auth 中间件拦截，未登录用户无法查看套餐
- **复现步骤**：未登录状态 → 访问 VIP 页面 → 接口 `/api/vip/packages` 返回 401
- **来源**：HTTP 响应 `401 Unauthorized`
- **根因**：`Vip.php` 控制器的 `$middleware` 配置未排除 `packages` 方法，导致未登录用户无法查看套餐列表
- **修复**：在 `Vip.php` 的中间件配置中添加 `except`：`[\app\middleware\Auth::class => ['except' => ['packages', 'info', 'benefits']]]`
- **状态**：[ ] 待修复

---

### [NEW-P1-2] FAQ 公开接口 `/api/site/faqs` 被 AdminAuth 中间件拦截，前台无法加载 FAQ
- **复现步骤**：前台任意页面 → 触发 FAQ 加载 → 接口 `/api/site/faqs` 返回 401
- **来源**：HTTP 响应 `401 Unauthorized`
- **根因**：`FaqManage.php` 控制器顶部有 `AdminAuth` 中间件，但 `publicList` 方法是前台公开接口，被错误拦截
- **修复**：在 `FaqManage.php` 中间件配置中排除 `publicList`：`[\app\middleware\AdminAuth::class => ['except' => ['publicList']]]`
- **状态**：[ ] 待修复

---

### [NEW-P1-3] SEO 配置接口 `/api/seo/active-configs` 返回 500
- **复现步骤**：前台页面加载 → SEO 配置接口 `/api/seo/active-configs` 返回 500
- **来源**：HTTP 响应 `500`（早期日志显示 `Table 'taichu.tc_system_configs' doesn't exist`，表名多了 `s`）
- **根因**：代码中查询 `tc_system_configs`（多了 `s`），实际表名为 `tc_system_config`
- **修复**：检查 SEO 相关 Controller/Service，将 `tc_system_configs` 改为 `tc_system_config`
- **状态**：[ ] 待修复

---

### [NEW-P1-4] 管理后台积分规则接口 `/api/maodou/points/rules` 返回 500
- **复现步骤**：管理后台 → 积分规则页面 → 接口返回 500
- **来源**：HTTP 响应 `code=500`
- **根因**：`admin\Points::getRules` 查询时可能使用了错误的表名（参考 AGENTS.md 陷阱10：`Db::table()` vs `Db::name()`）
- **修复**：检查 `backend/app/controller/admin/Points.php` 的 `getRules` 方法，确认使用 `Db::name()` 而非 `Db::table()`
- **状态**：[ ] 待修复

---

### [NEW-P1-5] 管理后台系统配置路由 `/api/maodou/system-config` 404
- **复现步骤**：管理后台 → 系统配置页面 → 接口 `/api/maodou/system-config` 返回 404
- **来源**：HTTP 响应 `404 Not Found`
- **根因**：`backend/route/admin.php` 中未注册 `system-config` 路由（或被动态路由拦截）
- **修复**：在 `admin.php` 中注册 `Route::get('system-config', 'admin.SystemSettings/getConfig')` 等路由，并确保在动态路由之前
- **状态**：[ ] 待修复

---

## 📋 新 Bug 修复优先级汇总

| 优先级 | ID | 问题 | 接口 | 状态 |
|--------|-----|------|------|------|
| 🔴 P0 | NEW-P0-1 | `tc_sms_code` 缺少 `is_used` 字段 | `POST /api/sms/send-code` | ⏳ 待修复 |
| 🔴 P0 | NEW-P0-2 | `tc_points_task` 缺少 `points_reward` 字段 | `GET /api/auth/userinfo` | ⏳ 待修复 |
| 🔴 P0 | NEW-P0-3 | `checkin_record` 表名缺 `tc_` 前缀 | `GET /api/daily/checkin-status` | ⏳ 待修复 |
| 🔴 P0 | NEW-P0-4 | `HehunRecord.php` 第 56 行语法错误 | `GET /api/hehun/history` | ⏳ 待修复 |
| 🔴 P0 | NEW-P0-5 | `tc_user_vip` 表不存在 | `GET /api/vip/status` | ⏳ 待修复 |
| 🔴 P0 | NEW-P0-6 | `tc_task_log` 表不存在 | `GET /api/tasks/list` | ⏳ 待修复 |
| 🔴 P0 | NEW-P0-7 | `User::adjustPoints()` 重复声明 | `GET /api/maodou/users` | ⏳ 待修复 |
| 🔴 P0 | NEW-P0-8 | `AiPrompt` 类重复声明 | `GET /api/maodou/ai-prompts/*` | ⏳ 待修复 |
| 🟠 P1 | NEW-P1-1 | VIP 套餐接口被 Auth 拦截 | `GET /api/vip/packages` | ⏳ 待修复 |
| 🟠 P1 | NEW-P1-2 | FAQ 接口被 AdminAuth 拦截 | `GET /api/site/faqs` | ⏳ 待修复 |
| 🟠 P1 | NEW-P1-3 | SEO 配置接口 500（表名多 `s`） | `GET /api/seo/active-configs` | ⏳ 待修复 |
| 🟠 P1 | NEW-P1-4 | 积分规则管理接口 500 | `GET /api/maodou/points/rules` | ⏳ 待修复 |
| 🟠 P1 | NEW-P1-5 | 系统配置路由 404 | `GET /api/maodou/system-config` | ⏳ 待修复 |
