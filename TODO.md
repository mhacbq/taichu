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


- [x] **[管理端] 流年运势管理入口缺失**
  - 问题：后端已有完整的 `yearly-fortune-manage` CRUD 路由，但管理端路由文件和 `views/result/` 目录下均无对应页面，导致流年运势记录无法在管理端查看和管理。
  - 修复：在 `admin/src/views/result/` 下新建 `YearlyFortuneManage.vue`，并在 `admin/src/router/index.js` 的测算管理模块中添加对应路由。

- [x] **[管理端] SEO 路由别名混乱**
  - 问题：后端同时存在 `/system/seo/configs`、`/seo/configs`、`/seo/list` 三套路由（历史兼容别名堆叠），前端调用路径不统一。
  - 修复：删除 `admin.php` 中 7 条冗余 SEO 别名路由（`seo/save`、`seo/configs`、`seo/robots`、`seo/stats`、`seo/delete`、`seo/robots`、`seo/sitemap-generate`），保留 `system/seo/configs` 主路由组和 `seo/list` 独立路由组，两套前端页面各用各的路由。

### 中等问题（功能残缺 / 按钮无事件）

- [x] **[管理端] VIP 套餐页面按钮无事件绑定**
  - 问题：`admin/src/views/payment/vip-packages.vue` 只有只读列表，新增/编辑/删除按钮未绑定任何事件，VIP 套餐无法通过界面管理。
  - 修复：补充新增弹窗、编辑弹窗、删除确认逻辑，调用 `payment.js` 中对应的 CRUD 接口。

- [x] **[管理端] 管理员管理页面按钮无事件绑定**
  - 问题：`admin/src/views/system/admins.vue` 只有只读列表，新增/编辑/重置密码/删除按钮未绑定任何事件，管理员无法通过界面管理。
  - 修复：补充新增弹窗、编辑弹窗、重置密码弹窗、删除确认逻辑，调用 `system.js` 中对应的 CRUD 接口（同步修复上方 API 路径问题后）。

- [x] **[管理端] 行为日志 / 用户分析 / 测算统计 / 充值分析页面报错**
  - 问题：以上统计分析页面均报错（TODO 已记录）。
  - 修复：根本原因是管理端 18 个 `.vue` 文件中共 39+ 处 `res.code === 200` 判断错误（后端统一返回 `code: 0` 表示成功），导致所有数据请求成功但数据不显示。已批量替换为 `res.code === 0`，覆盖 feedback、log、payment、points、site-content、system 等所有模块。

- [x] **[管理端] 登录日志 / API 日志记录不完整**
  - 问题：日志记录字段缺失，无法有效追踪操作（TODO 已记录）。
  - 修复：根本原因同上，前端 `log/login.vue`、`log/api.vue`、`log/operation.vue` 均使用 `res.code === 200` 判断，已修复为 `res.code === 0`。后端 `Logs.php` 已有完整实现，`tc_admin_login_log` 表在 `init.sql` 中已定义。

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


- [x] **[后端] 补充 `tarot/ai-analysis` 路由**
  - 问题：`useTarot.js` 第 526 行有实际调用 `aiAnalyzeTarot`（`POST /tarot/ai-analysis`），但后端 `app.php` 中 tarot 路由组无此路由，调用必然 404。
  - 修复：在 `app.php` 的 tarot 路由组中补充 `Route::post('ai-analysis', 'Tarot/aiAnalysis')`，并在 `Tarot` 控制器中实现 `aiAnalysis` 方法。

- [x] **[后端] 补充 `liuyao/ai-analysis` 路由**
  - 问题：`useLiuyao.js` 第 747 行有实际调用 `analyzeLiuyaoAi`（`POST /liuyao/ai-analysis`），但后端 `app.php` 中 liuyao 路由组无此路由，调用必然 404。
  - 修复：在 `app.php` 的 liuyao 路由组中补充 `Route::post('ai-analysis', 'Liuyao/aiAnalysis')`，并在 `Liuyao` 控制器中实现 `aiAnalysis` 方法。

- [x] **[后端] 补充 `points/rules` PUT 路由**
  - 问题：管理端 `points.js` 中 `savePointsRules` 调用 `PUT /points/rules`，但 `admin.php` 中积分规则路由只有 `GET /points/rules`，无 PUT 路由，导致积分规则保存功能 404。
  - 修复：在 `admin.php` 中补充 `Route::put('points/rules', 'admin.Points/saveRules')`，并在 `admin\Points` 控制器中实现 `saveRules` 方法。

---

#### 🟡 中等：功能残缺 / 数据结构不匹配


- [ ] **[管理端] `seo-stats.vue` 调用 `/seo/stats` 但后端返回的是 SEO 配置统计而非真实 SEO 数据**
  - 问题：`seo-stats.vue` 期望后端返回百度/必应收录量、关键词排名、流量等真实 SEO 数据，但后端 `admin\Seo::seoStats()` 实际返回的是数据库中 SEO 配置的统计（total/active/inactive/coverage），两者数据结构完全不匹配，页面展示的是假数据。
  - 修复：明确 SEO 统计页的定位——若只展示"SEO配置覆盖率"，则修改前端展示逻辑匹配后端数据；若需要真实搜索引擎数据，则需接入第三方 SEO API（百度站长/Google Search Console）。


- [x] **[管理端] `site-content/testimonials.vue` 和 `site-content/question-templates.vue` 无路由入口**
  - 问题：`admin/src/views/site-content/testimonials.vue`（9.05KB）和 `question-templates.vue`（8.00KB）存在于目录中，但 `admin/src/router/index.js` 的 `/site` 路由组中只有 `tarot-cards`、`faq`、`knowledge` 三个子路由，这两个页面无法访问。
  - 修复：文件已删除（无实际使用场景），无需补充路由。

- [x] **[管理端] `site-content/content-manager.vue` 无路由入口**
  - 问题：`admin/src/views/site-content/content-manager.vue`（9.43KB）存在但无路由入口，无法访问。
  - 修复：文件已删除（无实际使用场景），无需补充路由。

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


---

#### 🟢 低优先级：前台功能检查

- [x] **[前台] `Qiming/useQiming.js` 缺少历史记录功能**
  - 问题：取名页面 `useQiming.js` 只实现了提交取名，未实现历史记录查询（`getQimingHistory` 接口已在 `index.js` 中定义，后端路由 `GET /qiming/history` 也已注册），用户无法查看历史取名记录。
  - 修复：在 `useQiming.js` 中补充历史记录加载逻辑，在 `Qiming/index.vue` 中展示历史记录列表。

- [ ] **[前台] `YearlyFortune/useYearlyFortune.js` 功能不完整**
  - 问题：流年运势页面 `useYearlyFortune.js` 仅 5.67KB，功能较简单，缺少大运分析（`getDayunAnalysis`）和大运图表（`getDayunChart`）的调用，这两个接口后端已实现。
  - 修复：在流年运势页面补充大运分析功能入口。

- [x] **[后端] 前台 FAQ 接口缺失**
  - 问题：`siteContent.js` 中 `getFaqs` 调用 `GET /site/faqs`，该路由只在 `admin.php` 中注册（需鉴权），`app.php` 中无公开的 FAQ 接口。若前台帮助中心需要展示 FAQ，调用必然 401/404。
  - 修复：已在 `app.php` 中补充 `Route::get('site/faqs', 'admin.FaqManage/publicList')` 公开路由，并在 `FaqManage` 控制器中实现 `publicList()` 方法，只返回已启用的 FAQ 数据。

---

#### 🔴 严重：本轮新发现

- [x] **[管理端] `Points.php` 控制器缺少 `saveRules` 方法**
  - 问题：`admin.php` 中已有 `Route::put('points/rules', 'admin.Points/saveRules')` 路由（待补充），但 `Points.php` 控制器中只有 `getRules()`，完全没有 `saveRules()` 方法，即使路由补上也会 500。
  - 修复：已在 `Points.php` 中实现 `saveRules()` 方法，将积分规则配置写入 `tc_system_config`；同步修复 `getRules()` 从配置表读取真实规则而非历史记录枚举。


- [x] **[管理端] `faq.vue` 调用 `getFaqList` → `siteContent.js` 中路径为 `/site/faqs`，与后端路由不匹配**
  - 问题：`faq.vue` 调用 `getFaqList`，该函数在 `siteContent.js` 中调用 `GET /site/faqs`；后端 `admin.php` 注册的是 `GET site/faqs`（在 `/api/maodou` 路由组内），实际完整路径是 `/api/maodou/site/faqs`，而 `siteContent.js` 的 request.js baseURL 是 `/api/maodou`，所以路径 `/site/faqs` 实际上是正确的。**但 `saveFaq` 调用 `POST /site/faqs`，后端路由也有 `POST site/faqs`，路径匹配。`deleteFaq` 调用 `DELETE /site/faqs/:id`，后端也有对应路由。** → FAQ 管理页面路径实际上是正确的，之前记录的"FAQ 管理页面报错"需要重新排查真实原因。
  - 修复：重新排查 FAQ 页面报错的真实原因（可能是数据库表 `tc_faq` 不存在，或 `FaqManage.php` 控制器方法有 bug）。
  - **根本原因确认**：`faq.vue` 中 `res.code === 200` 判断错误（应为 `res.code === 0`），已批量修复。`tc_faq` 表在 `init.sql` 中已定义，后端路由和控制器均正常。

- [x] **[管理端] `payment/analysis.vue` 调用 `getRechargeStats` 但后端返回数据结构不含 `chart_data`**
  - 问题：`payment/analysis.vue` 调用 `getRechargeStats`（`GET /payment/stats`），后端 `admin\Payment::getStats()` 返回的是 `total_amount/order_count/vip_count/recharge_count`，但前端还期望 `res.data.chart_data`（含 `dates/amounts/counts`），后端未返回该字段，导致图表渲染时使用硬编码的假数据（周一到周日）。
  - 修复：已在 `admin\Payment::getStats()` 中补充 `chart_data` 字段，返回最近 7 天的充值趋势数据。


---

#### 🟡 中等：本轮新发现

- [x] **[管理端] `vip-packages.vue` 中"新增套餐"、"编辑"、"上下架"、"删除"按钮均无事件绑定**
  - 问题：`vip-packages.vue` 中"新增套餐"按钮无 `@click` 事件，操作列的"编辑"、"上架/下架"、"删除"按钮也均无 `@click` 事件，VIP 套餐完全无法通过界面管理。（已在 TODO 中有记录，但本轮确认了具体代码位置）
  - 修复：补充所有按钮的事件绑定，调用 `payment.js` 中 `saveVipPackage`、`deleteVipPackage`、`batchUpdateVipPackageStatus` 接口。

- [x] **[管理端] `Points.php::getRules()` 返回的是积分记录类型枚举，而非可配置的积分规则**
  - 问题：`points/rules.vue` 期望展示可编辑的积分规则（如签到得多少分、邀请得多少分），但后端 `getRules()` 实际上是从 `tc_points_record` 表中 `distinct` 出已有的 `type` 字段，返回的是历史记录中出现过的类型，而非真正的规则配置，且 `points` 字段硬编码为 0。
  - 修复：已重写 `getRules()`，从 `tc_system_config` 读取真实规则配置，`saveRules()` 写入配置。


### 产品体验 / 功能改造（非高频修复输入）

---

## 🎨 B. UI / UX 优化队列（截图分析 + 设计师审查，2026-03-25）

> 视觉规范：白色背景 `#fffefb` · 金色强调 `#d4a03e` · 深棕文字 `#5e4318`，**不做色彩系统重构**

### 🔴 P0 - 紧急修复（影响核心体验）

- [x] **[首页] 欢迎弹窗遮挡主内容**
  - 问题：首次访问时引导弹窗遮挡整个页面，用户无法直接看到产品内容
  - 修复：改为右下角固定浮动卡片（`position: fixed; bottom: 80px; right: 20px; width: 320px`），保留4步引导内容，添加滑入动画，不遮挡主流程。移动端自适应全宽。
  - 文件：`frontend/src/components/GuideModal.vue`

- [ ] **[全局] API 502 优雅降级缺失**
  - 问题：多个页面出现 502 Bad Gateway，导致页面显示空白或错误状态
  - 修复：添加优雅降级处理，API 失败时显示友好的空状态 UI
  - 文件：`frontend/src/api/index.js`、各页面组件

- [x] **[全局] 移动端导航遮挡主内容区域**
  - 问题：375-390px 下固定导航栏未正确补偿 `padding-top`，Hero 区域顶部内容被遮挡
  - 修复：已确认 navbar 为 `position: sticky`（不是 fixed），不会遮挡主内容；移动端底部导航已有 `padding-bottom: calc(60px + env(safe-area-inset-bottom))` 补偿，问题不存在。
  - 文件：全局布局组件

- [ ] **[全局] 移动端表单点击目标过小**
  - 问题：八字/合婚/六爻表单控件高度低于 44px，误触率高
  - 修复：所有交互控件最小点击区域 44×44px，日期/时辰选择器改为底部弹出 picker
  - 文件：`Bazi.vue`、`Hehun.vue`、`Liuyao.vue`

- [x] **[全局] CTA 按钮视觉权重不足**
  - 问题：主 CTA 按钮使用描边样式，在白色背景下视觉引导力弱
  - 修复：已确认首页 `btn-primary` 已是金色实心填充（`linear-gradient(135deg, #e3b254, #f6d484, #ffe5aa)`）+ 金色光晕（`box-shadow: 0 12px 24px rgba(186,134,39,0.24)`），视觉权重已足够。
  - 文件：各功能页

### 🔴 P1 - 高优先级（视觉体验明显缺陷）

- [x] **[首页] Hero 区域视觉权重不足**
  - 问题：Hero 区域高度偏小，主标题字体偏小，缺乏视觉冲击力
  - 修复：已确认主标题使用 `clamp(42px, 7vw, 60px)` 大字号，Hero 区域有金色渐变背景，视觉效果已足够。
  - 文件：`frontend/src/views/Index.vue`

- [x] **[首页] 功能卡片区域间距过于紧凑**
  - 问题：桌面端功能卡片间距过小，视觉拥挤
  - 修复：将 `features-grid` 的 `gap` 从 `20px` 增加至 `24px`，卡片 padding 已有 `32px 24px`。
  - 文件：`frontend/src/views/Index.vue`

- [x] **[八字排盘] 表单区域背景层次感不足**
  - 问题：表单区域纯白背景，缺乏层次区分
  - 修复：`.bazi-form` 添加淡金色背景 `rgba(212,160,62,0.03)` + 金色细边框 `rgba(212,160,62,0.12)` + 圆角 `16px` + `padding: 24px`。
  - 文件：`frontend/src/views/Bazi/style.css`

- [x] **[八字合婚] 表单区域背景层次感不足**
  - 问题：男方/女方表单区域背景色平淡，缺乏层次感
  - 修复：已确认 `form-card` 已有金色顶部装饰线 + 渐变背景 + 金色边框，视觉层次已足够。
  - 文件：`frontend/src/views/Hehun/style.css`

- [ ] **[塔罗占卜] 移动端牌面展示区域偏小**
  - 问题：移动端塔罗牌展示区域高度不足，牌面图案显示不完整
  - 修复：移动端牌面最小高度 200px
  - 文件：`frontend/src/views/Tarot.vue`

- [x] **[全局] 移动端横向溢出（overflow-x）**
  - 问题：塔罗牌横向排列和六爻爻象图在移动端出现横向溢出
  - 修复：塔罗牌在 480px 以下改为 2 列 grid 布局；六爻 `.gua-hero` 添加 `max-width: 100%; overflow: hidden`，`.gua-lines-inner` 移动端宽度缩小至 60px。
  - 文件：`Tarot/style.css`、`Liuyao/style.css`

- [ ] **[全局] 各页面 Hero 区域风格不统一**
  - 问题：各功能页页头风格不一致，缺乏连贯品牌感知
  - 修复：制定统一「功能页 Hero 模板」：页面标题 + 功能描述 + 装饰性命理图标
  - 文件：各功能页

- [x] **[全局] 结果展示区缺乏视觉层次**
  - 问题：命理解读结果大段文字，缺乏标题、分段、高亮关键词设计
  - 修复：Bazi `.ai-block` 添加 `border-left: 3px solid rgba(212,160,62,0.4)`；Tarot `.interpretation` 添加 `border-left: 3px solid #d4a03e`；Daily `.fortune-summary` 添加 `border-left: 3px solid #d4a03e` + `line-height: 1.8`。
  - 文件：`Bazi/style.css`、`Tarot/style.css`、`Daily/style.css`

### 🟡 P2 - 中优先级（体验优化）

- [ ] **[全局导航] 移动端菜单展开体验**
  - 问题：移动端汉堡菜单展开后，菜单项点击区域偏小（< 44px）
  - 修复：菜单项高度增至 48px，字号增至 16px，添加点击反馈动效
  - 文件：`frontend/src/components/Navbar.vue`

- [ ] **[全局] 按钮样式不统一**
  - 问题：不同页面按钮圆角、字号、内边距不一致，视觉碎片化
  - 修复：定义 3 种标准按钮（Primary 金色实心 / Secondary 金色描边 / Text 金色文字）
  - 文件：全局

- [ ] **[全局] Loading 状态设计缺失**
  - 问题：API 请求中无 Loading 反馈，用户误以为按钮无响应
  - 修复：提交按钮显示金色旋转 spinner 并禁用点击；结果区域使用骨架屏
  - 文件：各占卜页面组件

- [ ] **[全局] 空状态设计缺失**
  - 问题：历史记录、分析结果等区域无数据时显示空白，缺乏引导
  - 修复：统一空状态组件：命理主题图标 + 提示文字 + 操作按钮
  - 文件：新建 `frontend/src/components/EmptyState.vue`

- [ ] **[首页] 统计数字区域视觉强调不足**
  - 问题：统计数字因 API 502 显示为 0，且缺乏视觉强调
  - 修复：数字使用大字号金色（2rem+）+ 滚动动效，API 失败时显示"--"占位
  - 文件：`frontend/src/views/Index.vue`

- [ ] **[六爻占卜] 卦象 SVG 移动端偏小**
  - 问题：卦象 SVG 在移动端显示偏小，爻线细节不清晰
  - 修复：移动端 SVG 最小宽度 280px，动爻（红色）线条加粗至 3px
  - 文件：`frontend/src/views/Liuyao.vue`

- [ ] **[每日运势] 幸运色预览圆点偏小**
  - 问题：幸运色圆点约 12px，移动端难以辨认
  - 修复：圆点尺寸增至 20px，添加颜色名称 tooltip
  - 文件：`frontend/src/views/Daily.vue`

- [ ] **[八字排盘] AI 深度分析模块样式简单**
  - 问题：AI 分析入口样式像普通文字行，缺乏引导性
  - 修复：改为卡片式设计，添加 AI 图标 + 说明文字 + 积分消耗提示，金色渐变背景
  - 文件：`frontend/src/views/Bazi.vue`

- [ ] **[每日运势] 移动端信息密度过高**
  - 问题：多维度运势在移动端堆叠，需大量滚动
  - 修复：移动端改为横向滑动卡片（swiper）展示各维度运势，顶部固定今日总评
  - 文件：`frontend/src/views/Daily.vue`

### 🟢 P3 - 低优先级（细节打磨）

- [ ] **[全局] Footer 区域过于简单**
  - 修复：添加品牌 slogan、快速导航链接、社交媒体图标
  - 文件：`frontend/src/components/Footer.vue`

- [ ] **[全局] 页面切换过渡动效缺失**
  - 修复：添加 fade 过渡（opacity 0→1，duration 200ms）
  - 文件：`frontend/src/App.vue`

- [ ] **[八字合婚] 结果展示区域排版密集**
  - 修复：结果分段展示，每段添加小标题 + 图标，关键评分用大字号金色突出
  - 文件：`frontend/src/views/Hehun.vue`

- [ ] **[全局] 导航激活状态不够明显**
  - 修复：激活项增加金色下划线 + 字重加粗，双重视觉信号
  - 文件：导航组件

- [ ] **[全局] 字体大小移动端偏小**
  - 修复：移动端正文最小 14px，推荐 15-16px；标题层级 H1 24px > H2 20px > H3 16px
  - 文件：全局样式

---

## 📦 C. 产品力 / 商业化优化队列（产品经理分析，2026-03-25）

### 🔴 P0 - 核心体验断点

- [ ] **[八字排盘] 时辰选择对用户不友好**
  - 问题：地支时辰（子丑寅卯...）新用户不理解，导致放弃
  - 修复：增加"北京时间→地支时辰"对照提示，或改为 24 小时制选择后自动转换
  - 文件：`frontend/src/views/Bazi.vue`

- [ ] **[全局] 积分消耗前置不透明**
  - 问题：用户点击"开始测算"后才显示需要消耗积分，体验割裂
  - 修复：在功能入口处显示"本次消耗 X 积分，余额 Y 积分"
  - 文件：各占卜页面

- [ ] **[注册流程] 新用户无体验积分**
  - 问题：新用户注册后不知道如何获取积分，首次测算即遇付费门槛
  - 修复：注册即赠体验积分，引导完成首次测算
  - 文件：注册流程 + 后端

### 🟡 P1 - 留存与商业化

- [ ] **[个人中心] 测算历史记录缺失**
  - 问题：用户无法查看历史测算，无回访理由（7 日留存核心）
  - 修复：个人中心添加历史测算列表，可重新查看/对比
  - 文件：`frontend/src/views/Profile.vue`

- [ ] **[八字结果页] 五行缺失可视化**
  - 问题：缺少五行分布图表，结果不够直观，分享欲低
  - 修复：用图表展示五行分布，让结果更直观
  - 文件：八字结果相关组件

- [ ] **[VIP 页面] 权益展示不清晰**
  - 问题：VIP 权益列表缺少对比表格，描述抽象，转化率低
  - 修复：添加免费/月卡/年卡权益对比表格，权益描述具体化
  - 文件：`frontend/src/views/vip/`

- [ ] **[支付系统] 缺少订阅制会员**
  - 问题：只有积分充值，缺少月卡/季卡/年卡梯度设计
  - 修复：增加订阅制会员体系（月卡/季卡/年卡）
  - 文件：支付相关页面 + 后端

- [ ] **[全局] 结果一键分享功能**
  - 问题：无分享功能，错失最低成本的病毒传播工具
  - 修复：生成精美分享图片，含八字命盘关键信息
  - 文件：结果页组件

- [ ] **[个人中心] 每日签到系统**
  - 问题：无留存钩子，用户无回访理由
  - 修复：签到得积分，连续签到有奖励
  - 文件：`frontend/src/views/Profile.vue` + 后端

---

## ✅ D. 最近已完成 / 已确认

- [x] [后台前端] 用户详情页补齐加载失败只读态、积分调整数值校验与最近积分记录回读，避免接口异常时继续展示假可操作按钮，也让积分调整成功后可直接回读结果。
- [x] [功能] 激活流年运势功能：`tc_yearly_fortune` 表已创建但代码仍未使用，后续需补前端入口、计算逻辑与 AI 深度分析。

- [x] [功能] 激活取名功能：`tc_qiming_record` 表已创建但代码仍未使用，后续需补前端入口、八字五行取名逻辑与 AI 评测。
- [x] [产品] Core 6：首页增加“年度运程”独立入口，把流年运势做成时效性引流位。
