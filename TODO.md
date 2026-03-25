# TODO 任务清单（按定时任务类型分流）

## 📋 使用规则说明
1. 巡检类 `30 / 30-3 / 30-4` 只负责发现、补证、去重、转单，不直接修复。
2. 修复类 `15 / 15-2 / admin / automation / automation-4` 优先处理 `A. 高频修复队列` 下自己标题中的未完成条目；若本栏为空、或只剩需要外部确认的阻塞项，可继续领取 `A` 中与自己技术域最匹配、且能在仓库内落地补丁的条目，不要因为标签不完美就直接退出。
3. 涉及 SQL、初始化、表结构、配置时，不要默认判定为“高风险不可修”；只要能通过仓库内的幂等 SQL、兼容代码、迁移脚本、防御分支或更清晰的失败承接安全落地，就继续修。只有需要猜测真实凭据、直接改真实业务数据、或执行不可逆修数时才停下并明确说明。
4. `automation-2 / ui / ui-15` 只看 `C. 低频专项 / 手动规划池`，默认低频或手动，不进入高频修复节奏。
5. 产品规划、新功能、视觉统一放在低频规划池，不作为高频修复输入。
6. 已完成项移入 `D. 最近已完成 / 已确认`，避免被自动化重复消费。
7. 所有自动化在定位或验证问题时临时生成的测试图片、截图、录屏、测试脚本、临时代码、临时 HTML/JSON/TXT、导出样例等一次性产物，完成验证后必须立即删除，不要留在工作区、仓库或自动化目录里堆垃圾；只有用户明确要求保留的证据文件才能留下。
8.设计的时候: "参考首页的色调：白色背景 #fffefb，金色强调 #d4a03e，深棕文字 #5e4318，保持一致"
## 🔴 A. 管理端功能修复队列

### 严重问题（API 路径错误 / 功能完全不可用）

- [ ] **[管理端] 管理员管理 API 路径错误**
  - 问题：前端 `admin/src/api/system.js` 调用 `/system/admin-list`，但后端路由注册的是 `/system/admins`（GET/POST）和 `/system/admins/:id`（DELETE），路径不匹配导致接口 404。
  - 修复：将 `system.js` 中所有 `/system/admin-list` 改为 `/system/admins`，删除操作路径改为 `/system/admins/:id`。

- [ ] **[管理端] 流年运势管理入口缺失**
  - 问题：后端已有完整的 `yearly-fortune-manage` CRUD 路由，但管理端路由文件和 `views/result/` 目录下均无对应页面，导致流年运势记录无法在管理端查看和管理。
  - 修复：在 `admin/src/views/result/` 下新建 `YearlyFortuneManage.vue`，并在 `admin/src/router/index.js` 的测算管理模块中添加对应路由。

- [x] **[管理端] SEO 统计接口不存在** — 已确认后端 `admin.php` 有 `GET seo/stats` 路由，路径正常，此条目关闭。

- [ ] **[管理端] SEO 路由别名混乱**
  - 问题：后端同时存在 `/system/seo/configs`、`/seo/configs`、`/seo/list` 三套路由（历史兼容别名堆叠），前端调用路径不统一。
  - 修复：统一保留 `/seo/configs` 一套路由，删除其余别名，前端 `siteContent.js` 同步修改调用路径。

### 中等问题（功能残缺 / 按钮无事件）

- [ ] **[管理端] VIP 套餐页面按钮无事件绑定**
  - 问题：`admin/src/views/payment/vip-packages.vue` 只有只读列表，新增/编辑/删除按钮未绑定任何事件，VIP 套餐无法通过界面管理。
  - 修复：补充新增弹窗、编辑弹窗、删除确认逻辑，调用 `payment.js` 中对应的 CRUD 接口。

- [ ] **[管理端] 管理员管理页面按钮无事件绑定**
  - 问题：`admin/src/views/system/admins.vue` 只有只读列表，新增/编辑/重置密码/删除按钮未绑定任何事件，管理员无法通过界面管理。
  - 修复：补充新增弹窗、编辑弹窗、重置密码弹窗、删除确认逻辑，调用 `system.js` 中对应的 CRUD 接口（同步修复上方 API 路径问题后）。

- [ ] **[管理端] 塔罗牌库页面报错**
  - 问题：塔罗牌库管理页面加载报错（TODO 已记录）。
  - 修复：排查报错原因（接口路径 / 数据结构 / 组件初始化），修复后确保列表正常加载。

- [ ] **[管理端] FAQ 管理页面报错**
  - 问题：FAQ 管理页面加载报错（TODO 已记录）。
  - 修复：排查 `siteContent.js` 中 FAQ 接口路径是否与后端路由一致，修复不匹配项。

- [ ] **[管理端] 积分记录页面报错**
  - 问题：积分记录页面加载报错（TODO 已记录）。
  - 修复：排查接口路径和响应数据结构，修复后确保积分记录列表正常展示。

- [ ] **[管理端] 反馈列表页面报错**
  - 问题：反馈列表页面加载报错（TODO 已记录）。
  - 修复：排查接口路径，修复后确保用户反馈列表正常展示。

- [ ] **[管理端] 行为日志 / 用户分析 / 测算统计 / 充值分析页面报错**
  - 问题：以上统计分析页面均报错（TODO 已记录）。
  - 修复：统一排查各页面对应的 API 路径，逐一修复接口调用错误。

- [ ] **[管理端] 登录日志 / API 日志记录不完整**
  - 问题：日志记录字段缺失，无法有效追踪操作（TODO 已记录）。
  - 修复：补充后端日志记录中间件，确保关键字段（IP、操作人、时间、结果）完整写入。

### 冗余清理（可选，建议第三批处理）

- [ ] **[管理端] 删除无用页面：页面管理、问题模板、用户评价、系统配置（与基础配置重复）**
  - 问题：以上页面无实际使用场景，或功能已在其他页面实现，造成菜单冗余。
  - 修复：从路由文件中移除对应路由，删除对应 `.vue` 文件，清理菜单配置。

- [ ] **[管理端] 积分调整独立页可移除**
  - 问题：`adjust.vue` 功能已在用户详情页实现，独立页重复。
  - 修复：从路由中移除该入口，删除对应文件。

---

### 本轮深度检查新发现问题

---

#### 🔴 严重：路由/控制器不一致

- [ ] **[管理端] `system/admin-list` 路由与控制器双重混乱**
  - 问题：`admin.php` 中同时存在两条路由：`Route::get('system/admin-list', 'admin.System/adminList')` 和 `Route::get('system/admins', 'Admin/getAdminUsers')`，前者指向 `admin\System::adminList()`（只读列表，无 CRUD），后者指向 `Admin::getAdminUsers()`（完整 CRUD）。前端 `system.js` 调用 `/system/admin-list`，实际命中的是只读接口，导致新增/删除操作全部 404。
  - 修复：删除 `admin.php` 中 `system/admin-list` 这条冗余路由，前端 `system.js` 统一改为 `/system/admins`，并补全 POST/DELETE 方法调用。

- [x] **[前台] `index.js` 存在大量幽灵 API（路由不存在）**
  - 已删除：`/cultural/` 路由组全部函数（10个）、`/decision/` 路由组全部函数（两处共9个）、`/relationship/` 路由组全部函数（4个）、`wechatLogin`、`getMyTasks`、`calculateCultural`、`setCulturalSharePublic`
  - 保留：`analyzeLiuyaoAi`（useLiuyao.js 有调用）、`aiAnalyzeTarot`（useTarot.js 有调用）

- [x] **[前台] `getBaziShare` 路径不一致** — 经验证路径原本正确：`/bazi/share` + baseURL `/api` = `/api/bazi/share`，与后端路由匹配。命名不统一属于风格问题，不是 bug，已回滚误改。

- [x] **[前台] `wechatLogin` 调用不存在的路由** — 已从 `index.js` 删除

- [ ] **[后端] 补充 `tarot/ai-analysis` 路由**
  - 问题：`useTarot.js` 第 526 行有实际调用 `aiAnalyzeTarot`（`POST /tarot/ai-analysis`），但后端 `app.php` 中 tarot 路由组无此路由，调用必然 404。
  - 修复：在 `app.php` 的 tarot 路由组中补充 `Route::post('ai-analysis', 'Tarot/aiAnalysis')`，并在 `Tarot` 控制器中实现 `aiAnalysis` 方法（可复用 `AiAnalysis` 服务）。

- [ ] **[后端] 补充 `liuyao/ai-analysis` 路由**
  - 问题：`useLiuyao.js` 第 747 行有实际调用 `analyzeLiuyaoAi`（`POST /liuyao/ai-analysis`），但后端 `app.php` 中 liuyao 路由组无此路由，调用必然 404。
  - 修复：在 `app.php` 的 liuyao 路由组中补充 `Route::post('ai-analysis', 'Liuyao/aiAnalysis')`，并在 `Liuyao` 控制器中实现 `aiAnalysis` 方法。

- [ ] **[后端] 补充 `points/rules` PUT 路由**
  - 问题：管理端 `points.js` 中 `savePointsRules` 调用 `PUT /points/rules`，但 `admin.php` 中积分规则路由只有 `GET /points/rules`，无 PUT 路由，导致积分规则保存功能 404。
  - 修复：在 `admin.php` 中补充 `Route::put('points/rules', 'admin.Points/saveRules')`，并在 `admin\Points` 控制器中实现 `saveRules` 方法。

- [x] **[前台] `tasks/my-tasks` 路由不存在** — `getMyTasks` 无任何调用处，已从 `index.js` 删除

---

#### 🟡 中等：功能残缺 / 数据结构不匹配

- [x] **[管理端] `points.js` 中 `savePointsRules` 使用 PUT 但后端无对应路由** — 已移至「后端补路由」分组统一处理。

- [ ] **[管理端] `seo-stats.vue` 调用 `/seo/stats` 但后端返回的是 SEO 配置统计而非真实 SEO 数据**
  - 问题：`seo-stats.vue` 期望后端返回百度/必应收录量、关键词排名、流量等真实 SEO 数据，但后端 `admin\Seo::seoStats()` 实际返回的是数据库中 SEO 配置的统计（total/active/inactive/coverage），两者数据结构完全不匹配，页面展示的是假数据。
  - 修复：明确 SEO 统计页的定位——若只展示"SEO配置覆盖率"，则修改前端展示逻辑匹配后端数据；若需要真实搜索引擎数据，则需接入第三方 SEO API（百度站长/Google Search Console）。

- [x] **[管理端] `user/list-improved.vue` 存在但未被路由引用** — 已删除废弃文件。

- [x] **[管理端] `system/admin.vue` 与 `system/admins.vue` 功能重复** — 已删除废弃文件 `admin.vue`。

- [ ] **[管理端] `content/bazi.vue` 和 `content/tarot.vue` 是空壳页面**
  - 问题：`admin/src/views/content/bazi.vue`（1021B）和 `content/tarot.vue`（1.00KB）文件极小，疑似空壳或占位文件，但这两个路径未在 `admin/src/router/index.js` 中注册，属于无用文件。
  - 修复：确认是否需要这两个页面，若无则删除。

- [ ] **[管理端] `site-content/testimonials.vue` 和 `site-content/question-templates.vue` 无路由入口**
  - 问题：`admin/src/views/site-content/testimonials.vue`（9.05KB）和 `question-templates.vue`（8.00KB）存在于目录中，但 `admin/src/router/index.js` 的 `/site` 路由组中只有 `tarot-cards`、`faq`、`knowledge` 三个子路由，这两个页面无法访问。
  - 修复：若需要这两个功能，在路由中补充入口；若不需要，删除文件。

- [ ] **[管理端] `site-content/content-manager.vue` 无路由入口**
  - 问题：`admin/src/views/site-content/content-manager.vue`（9.43KB）存在但无路由入口，无法访问。
  - 修复：同上，确认是否需要，决定补路由或删除文件。

- [ ] **[管理端] `siteContent.js` 中大量接口调用不存在的后端路由**
  - 问题：`admin/src/api/siteContent.js` 中以下接口在 `admin.php` 中无对应路由：
    - `GET /site/home`、`GET /site/page`（后端无 `/site/` 路由组）
    - `GET /site/testimonials`、`POST /site/testimonials`、`DELETE /site/testimonials/:id`（无路由）
    - `GET /site/spreads`、`POST /site/spreads`（无路由）
    - `GET /site/questions`、`POST /site/questions`（无路由）
    - `GET /site/enums`（无路由）
    - `GET /site/content/list`、`POST /site/content/save`、`POST /site/content/batch`、`DELETE /site/content/:id`（无路由）
    - `GET /site/fortune-templates`、`POST /site/fortune-templates`（无路由）
  - 修复：这些接口对应的功能（用户评价、牌阵、问题模板、运势模板）后端均未实现，需要决策：要么补充后端实现，要么删除前端相关页面和接口。

- [ ] **[管理端] `tarot-cards.vue` 中"添加塔罗牌"按钮无实际新增功能**
  - 问题：`site-content/tarot-cards.vue` 中点击"添加塔罗牌"会打开弹窗，但 `saveTarotCard` 调用 `PUT /tarot-cards/:id`，当 `form.id` 为 null 时 URL 变为 `/tarot-cards/null`，导致新增操作实际上是 PUT 请求到错误路径。
  - 修复：`siteContent.js` 中 `saveTarotCard` 需区分新增（POST `/tarot-cards`）和编辑（PUT `/tarot-cards/:id`），后端也需补充 `POST /tarot-cards` 路由。

---

#### 🟢 低优先级：前台功能检查

- [ ] **[前台] `Qiming/useQiming.js` 缺少历史记录功能**
  - 问题：取名页面 `useQiming.js` 只实现了提交取名，未实现历史记录查询（`getQimingHistory` 接口已在 `index.js` 中定义，后端路由 `GET /qiming/history` 也已注册），用户无法查看历史取名记录。
  - 修复：在 `useQiming.js` 中补充历史记录加载逻辑，在 `Qiming/index.vue` 中展示历史记录列表。

- [ ] **[前台] `YearlyFortune/useYearlyFortune.js` 功能不完整**
  - 问题：流年运势页面 `useYearlyFortune.js` 仅 5.67KB，功能较简单，缺少大运分析（`getDayunAnalysis`）和大运图表（`getDayunChart`）的调用，这两个接口后端已实现。
  - 修复：在流年运势页面补充大运分析功能入口。

- [ ] **[后端] 前台 FAQ 接口缺失**
  - 问题：`siteContent.js` 中 `getFaqs` 调用 `GET /site/faqs`，该路由只在 `admin.php` 中注册（需鉴权），`app.php` 中无公开的 FAQ 接口。若前台帮助中心需要展示 FAQ，调用必然 401/404。
  - 修复：在 `app.php` 中补充 `Route::get('site/faqs', 'admin.FaqManage/publicList')` 公开路由（或新建 `Faq` 控制器），只返回已启用的 FAQ 数据。

---

#### 🔴 严重：本轮新发现

- [ ] **[管理端] `Points.php` 控制器缺少 `saveRules` 方法**
  - 问题：`admin.php` 中已有 `Route::put('points/rules', 'admin.Points/saveRules')` 路由（待补充），但 `Points.php` 控制器中只有 `getRules()`，完全没有 `saveRules()` 方法，即使路由补上也会 500。
  - 修复：在 `Points.php` 中实现 `saveRules()` 方法，将积分规则配置写入 `tc_system_config` 或专用配置表。

- [x] **[管理端] `system.js` 中 `saveAdminUser` 和 `deleteAdminUser` 路径错误** — 已修复，统一改为 `/system/admins`。

- [ ] **[管理端] `faq.vue` 调用 `getFaqList` → `siteContent.js` 中路径为 `/site/faqs`，与后端路由不匹配**
  - 问题：`faq.vue` 调用 `getFaqList`，该函数在 `siteContent.js` 中调用 `GET /site/faqs`；后端 `admin.php` 注册的是 `GET site/faqs`（在 `/api/maodou` 路由组内），实际完整路径是 `/api/maodou/site/faqs`，而 `siteContent.js` 的 request.js baseURL 是 `/api/maodou`，所以路径 `/site/faqs` 实际上是正确的。**但 `saveFaq` 调用 `POST /site/faqs`，后端路由也有 `POST site/faqs`，路径匹配。`deleteFaq` 调用 `DELETE /site/faqs/:id`，后端也有对应路由。** → FAQ 管理页面路径实际上是正确的，之前记录的"FAQ 管理页面报错"需要重新排查真实原因。
  - 修复：重新排查 FAQ 页面报错的真实原因（可能是数据库表 `tc_faq` 不存在，或 `FaqManage.php` 控制器方法有 bug）。

- [ ] **[管理端] `payment/analysis.vue` 调用 `getRechargeStats` 但后端返回数据结构不含 `chart_data`**
  - 问题：`payment/analysis.vue` 调用 `getRechargeStats`（`GET /payment/stats`），后端 `admin\Payment::getStats()` 返回的是 `total_amount/order_count/vip_count/recharge_count`，但前端还期望 `res.data.chart_data`（含 `dates/amounts/counts`），后端未返回该字段，导致图表渲染时使用硬编码的假数据（周一到周日）。
  - 修复：在 `admin\Payment::getStats()` 中补充 `chart_data` 字段，返回最近 7 天的充值趋势数据。

- [x] **[管理端] `result/analysis.vue` 中 `renderUserRankChart` 函数从未被调用** — 已修复，在 `loadData()` 中补充了 `renderUserRankChart(data.user_ranking)` 调用。

---

#### 🟡 中等：本轮新发现

- [ ] **[管理端] `vip-packages.vue` 中"新增套餐"、"编辑"、"上下架"、"删除"按钮均无事件绑定**
  - 问题：`vip-packages.vue` 中"新增套餐"按钮无 `@click` 事件，操作列的"编辑"、"上架/下架"、"删除"按钮也均无 `@click` 事件，VIP 套餐完全无法通过界面管理。（已在 TODO 中有记录，但本轮确认了具体代码位置）
  - 修复：补充所有按钮的事件绑定，调用 `payment.js` 中 `saveVipPackage`、`deleteVipPackage`、`batchUpdateVipPackageStatus` 接口。

- [ ] **[管理端] `Points.php::getRules()` 返回的是积分记录类型枚举，而非可配置的积分规则**
  - 问题：`points/rules.vue` 期望展示可编辑的积分规则（如签到得多少分、邀请得多少分），但后端 `getRules()` 实际上是从 `tc_points_record` 表中 `distinct` 出已有的 `type` 字段，返回的是历史记录中出现过的类型，而非真正的规则配置，且 `points` 字段硬编码为 0。
  - 修复：设计积分规则配置表（或使用 `tc_system_config`），`getRules()` 从配置中读取，`saveRules()` 写入配置。

- [ ] **[管理端] `user/behavior.vue` 调用 `getUserBehavior` 但后端 `users/behavior` 接口返回数据结构未知**
  - 问题：`user/behavior.vue` 调用 `GET /users/behavior`，后端 `admin\User::behavior()` 方法存在，但该接口返回的字段（`list/total`）与前端期望的 `username/type/content/ip` 等字段是否匹配未经验证，且前端未处理 `res.code !== 200` 的情况（直接解构 `res.data`）。
  - 修复：验证后端 `behavior()` 方法返回的字段，确保与前端展示字段一致；补充前端错误处理。

- [ ] **[管理端] `content/bazi.vue` 和 `content/tarot.vue` 是空壳文件但被路由引用**
  - 问题：`content/bazi.vue`（1021B）和 `content/tarot.vue`（1.00KB）文件极小，内容为空壳，但 `admin.php` 中有 `GET content/bazi` 和 `GET content/tarot` 路由（指向旧的 `Admin` 控制器），这两个路由与新的 `BaziManage`/`TarotManage` 功能重复，造成混乱。
  - 修复：确认 `content/bazi` 和 `content/tarot` 路由是否仍需要，若不需要则删除 `admin.php` 中对应路由和 `content/bazi.vue`、`content/tarot.vue` 文件。

---

---

### 本轮深度检查新发现（第三轮）

---

#### 🔴 严重：控制器 Bug / 表名错误

- [x] **[后端] `Statistics.php` 使用无前缀表名，查询必然失败** — 已修复，所有 `Db::table()` 改为 `Db::name()`，自动加 `tc_` 前缀。

- [ ] **[后端] `BaziRecordController.php` 的 `statistics()` 方法使用无前缀表名**
  - 问题：`BaziRecordController::statistics()` 中 `Db::table('bazi_record')` 无前缀，会报表不存在；同时 `BaziRecord::count()` 依赖 Model 定义，需确认 `BaziRecord` Model 是否存在。
  - 修复：将 `Db::table('bazi_record')` 改为 `Db::name('bazi_record')`，并确认 `BaziRecord` Model 的表名配置正确。

---

#### 🟡 中等：孤儿文件 / 功能未接通

- [ ] **[后端] `PointsController.php` 和 `UserController.php` 是孤儿控制器**
  - 问题：`backend/app/controller/PointsController.php`（积分记录列表+统计）和 `UserController.php` 在 `admin.php` 和 `app.php` 中均无路由注册，是完全无法访问的废弃文件，与 `admin\Points.php`、`admin\User.php` 功能重复。
  - 修复：确认这两个文件是否有保留价值，若无则删除，避免混淆。

- [ ] **[管理端] `contentEditor.js` 中 4 个函数无对应后端路由**
  - 问题：`admin/src/api/contentEditor.js` 中 `updateBlocks`（`PUT /content/page/:id/blocks`）、`updateBlock`（`PUT /content/page/:id/block/:blockId`）、`deleteBlock`（`DELETE /content/page/:id/block/:blockId`）、`sortBlocks`（`PUT /content/page/:id/sort`）在 `admin.php` 中无对应路由，调用必然 404。
  - 修复：若内容编辑器功能需要这些操作，在 `admin.php` 中补充对应路由并在 `Content.php` 控制器中实现；若不需要，删除 `contentEditor.js` 中这 4 个函数。

- [ ] **[管理端] `admin/src/api/ai.js` 接口路径需核对**
  - 问题：`ai.js` 中调用 `/ai/config`（GET/POST）和 `/ai/test`，后端 `admin.php` 中有对应路由 `admin.Ai/getConfig`、`admin.Ai/saveConfig`、`admin.Ai/testConnection`，路径匹配；但 `admin.Ai` 控制器（`admin/Ai.php`）是否实现了这三个方法需确认。
  - 修复：读取 `admin/Ai.php` 确认方法存在，若缺失则补充实现。

- [ ] **[后端] `Statistics.php` 与 `admin\Dashboard.php` 功能高度重叠**
  - 问题：`admin.php` 中同时存在 `GET statistics/index`（→ `Statistics/index`）和 `GET dashboard/statistics`（→ `admin.Dashboard/index`），两者都返回平台统计数据，功能重叠，且 `Statistics.php` 还有表名 bug，前端 `dashboard.js` 只调用 `dashboard/statistics`，`statistics/index` 无前端调用方。
  - 修复：删除 `statistics/index` 路由和 `Statistics.php` 控制器，统一使用 `admin\Dashboard` 提供统计数据。

---

### 产品体验 / 功能改造（非高频修复输入）



## ✅ D. 最近已完成 / 已确认

- [x] [后台前端] 用户详情页补齐加载失败只读态、积分调整数值校验与最近积分记录回读，避免接口异常时继续展示假可操作按钮，也让积分调整成功后可直接回读结果。
- [x] [功能] 激活流年运势功能：`tc_yearly_fortune` 表已创建但代码仍未使用，后续需补前端入口、计算逻辑与 AI 深度分析。

- [x] [功能] 激活取名功能：`tc_qiming_record` 表已创建但代码仍未使用，后续需补前端入口、八字五行取名逻辑与 AI 评测。
- [x] [产品] Core 6：首页增加“年度运程”独立入口，把流年运势做成时效性引流位。
