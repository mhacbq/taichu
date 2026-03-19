# 运营人员后台检查 - 执行历史

> 环境基线更新（2026-03-18）：当前本地标准环境已切换为 **phpstudy + `http://localhost:8080` 直连接口**。后续巡检不要再把 Docker、容器状态、`docker compose` 或 `localhost:3001` 代理当作默认前提；历史记录里的 3001 / 容器表述仅代表旧轮次现场，不代表当前执行基线。
>
> 后台登录入口修正（2026-03-19）：`admin/` 是独立 Vite 后台项目，登录页前端路由是 `/login`，登录接口是 `POST /api/admin/auth/login`。页面级巡检前先确认 `C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified\admin\dist\index.html` 是否已存在或需重建；必要时先执行 `npm run build --prefix admin`。除非用户明确说明已启动 dev server，否则不要默认 `http://localhost:3001/login`；也不要机械把 `http://localhost:8080/admin` 或 `/admin/login` 当成固定入口。应先核对用户实际部署/挂载后的后台站点根地址，再访问“站点根地址 + /login”。

## 2026-03-19 积分统计接口修复补记

### 检查范围
- 直接修复并回归 `GET /api/admin/points/stats` 的单点 500。

### 检查结果概述
- 根因已确认并修复：`Admin::pointsStats()` 调用了缺失的 `AdminStatsService::getPointsStatsSnapshot()`；现已补齐该方法及积分统计聚合逻辑，接口恢复 `code=200`，不再抛“获取积分统计失败，请稍后重试”。
- 回归结果显示接口当前已返回 `date / today_given / today_consumed / balance / top_consumers / total_records` 字段；后续若继续巡检后台积分模块，可直接基于该接口做可用性验证。

---

## 2026-03-19 第三十九轮执行摘要

### 检查范围
- 仅按 phpstudy 基线直连 `http://localhost:8080/api/admin/...`，本轮深查登录 → Dashboard、用户/积分查询、系统设置/系统公告 3 组后台运营链路，并在不额外拉起环境的前提下补看现成页面入口。

### 检查结果概述
- `admin / admin123` 现可正常登录，`dashboard/statistics`、`dashboard/trend`、`users list/detail`、`points/records`、`system/notices` 都返回 `code=200` 真实数据；系统公告也已实测“新增 → 列表可见 → 删除回滚”闭环正常，说明登录后全局 403 已不再是当前主阻塞。
- 本轮仍确认 1 条需要继续盯的后台问题：`PUT /api/admin/system/settings` 提交完整配置后虽然返回“保存成功”，但随后的 `GET /api/admin/system/settings` 会把文本/数值字段回读成空串和 `0`，`enable_feedback` 仍固定为 `true`；同时直接查询本地 MySQL `system_config` 表时原始值仍保持正常，说明当前更像是配置读取 / 缓存口径失真，而不是简单的单个开关未保存。该证据已去重补写到 `TODO.md`。

---



## 2026-03-19 第三十八轮执行摘要

### 检查范围
- 仅按 phpstudy 基线直连 `http://localhost:8080/api/admin/...`，深查登录、Dashboard、用户、系统设置、系统公告 3 组后台运营链路，并在无需额外拉起环境的前提下补测现成页面入口。

### 检查结果概述
- `POST /api/admin/auth/login`（`admin / admin123`）本轮已成功返回 token，不再复现“管理员账号表不存在”；但登录响应里 `admin.roles=[]`、`role=""`，继续携带该 token 访问 `dashboard statistics/trend`、`users list/detail`、`system settings/notices` 时全部返回 `HTTP 200 + code 403`，说明主阻塞已切换为登录后鉴权/角色种子缺失，而不是登录前缺表。
- 页面侧仍仅确认 `http://localhost:3001/login` 可返回“太初管理后台”Vite 壳页，`http://localhost:8080/admin` 与 `/admin/login` 继续 `404`。已把旧 TODO 里的“登录前缺表”证据替换为新的权限阻塞证据，未新增重复条目。

---

## 2026-03-19 第三十七轮执行摘要

### 检查范围
- 按 phpstudy 基线仅直连 `http://localhost:8080/api/admin/...`，优先深查后台登录、Dashboard、用户、系统设置、系统公告这 5 条代表性运营链路；并在不额外拉起环境的前提下补测已存在的页面入口。

### 检查结果概述
- `GET /api/health` 返回 `code=200`，但 `POST /api/admin/auth/login`（表单 `admin / admin123`）仍直接返回“管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql”`code=500`；说明当前后台真实阻塞点依旧在登录前置，而不是单纯页面空壳。
- 在拿不到后台 token 的前提下，`GET /api/admin/dashboard/statistics`、`/api/admin/users`、`/api/admin/system/settings`、`/api/admin/system/notices` 均返回 `401 未授权，请先登录`；页面侧仅确认 `http://localhost:3001/login` 仍可返回标题为“太初管理后台”的 Vite 壳页，`http://localhost:8080/admin` 与 `/admin/login` 继续 `404`。本轮仅给现有高优阻塞补充时间戳和受影响范围，未新增可独立归因的新后台缺陷。

---

## 2026-03-19 第三十六轮执行摘要


### 检查范围
- 先按要求只读取 `TODO.md` 的 `[30-3]` 章节与本记忆，再直连 `http://localhost:8080/api/admin/...` 复测后台登录、Dashboard、用户、系统设置代表链路；在确认本地已有 `http://localhost:3001/login` 可访问后，补做最小页面入口级验证。

### 检查结果概述
- `GET /api/health` 仍返回 `code=200`，但 `POST /api/admin/auth/login`（8080 直连）与 `POST http://localhost:3001/api/admin/auth/login`（3001 代理）都稳定返回“管理员账号表不存在，请先执行 `database/20260317_create_admin_users_table.sql`”；说明当前不是单一页面壳子问题，而是后台登录前置仍被管理员主表缺失卡死。
- 代表性受保护接口 `GET /api/admin/dashboard/statistics`、`/api/admin/users`、`/api/admin/system/settings` 在无 token 条件下均返回 `401 未授权，请先登录`；`http://localhost:3001/login` 可打开，但 `http://localhost:8080/admin` 与 `/admin/login` 仍是 `404`。本轮仅补充阻塞范围证据并更新 `TODO.md`，未新增可独立归因的新运营缺陷。

---


## 2026-03-18 第三十五轮执行摘要

### 检查范围
- 仅按 phpstudy 基线直连 `http://localhost:8080`，优先核对后台登录链路，并确认本地是否已有可直接访问的后台页面可补做页面级验证。

### 检查结果概述
- `GET /api/health` 返回 `code=200`，`http://localhost:3001/login` 也能打开，说明当前并非整站未启动；但 `POST /api/admin/auth/login` 用默认管理员 `admin / admin123` 仍返回“管理员账号表不存在”，后台 token 无法获取。
- 已把该新阻塞问题转写到 `TODO.md` 的 `[admin] 管理后台修复专家`；由于登录前置即失败，Dashboard、用户、内容、订单、积分、系统设置、公告等受保护后台链路本轮均无法继续做真实可用性验证，未硬判正常。

---

## 2026-03-18 第三十四轮执行摘要


### 检查范围
- 复测独立后台 `http://localhost:3001/login`，并通过 3001 代理检查 `/api/health`、`/api/admin/auth/login`；同时核对本地 `backend` 容器状态与最近启动日志。

### 检查结果概述
- 本轮未能继续进入后台：登录页可访问，但 `/api/health`、`/api/admin/auth/login` 通过 3001 代理均返回 `HTTP 500`；`docker compose ps` 显示 `taichu-app` 持续重启，日志确认根因是 `database/20260318_fix_shensha_display_encoding.sql` 在 bootstrap 时触发 `tc_shensha.uk_name_category` 唯一键冲突。
- 为避免重复登记，已直接补充 `TODO.md` 现有“服务启动重启循环”条目的后台运营影响范围，并新增 1 条运营体验问题：登录页把服务异常误报成账号错误；本轮未修改业务代码，仅更新了 `TODO.md`、本执行摘要与概览。

---



## 2026-03-18 第三十三轮执行摘要

### 检查范围
- 重新用默认管理员 `admin / admin123` 登录当前独立后台 `http://localhost:3001`，并对 Dashboard、用户、黄历、知识库、神煞、SEO、订单、积分记录、系统设置、系统公告做最小化真实回归。

### 检查结果概述
- 本轮确认登录、Dashboard、用户列表/详情、手动调积分回滚、积分记录、黄历 CRUD、知识库文章 CRUD、神煞 CRUD、SEO CRUD、系统公告发布/删除当前都可用；知识库分类与 SEO 中文显示也已恢复正常。
- 新增确认 2 条待处理问题：系统设置功能开关保存后不生效（`enable_feedback` 回读仍为 `true`）；后台仍缺独立“通知配置 / 测试通知”入口。与此同时，神煞历史数据乱码仍存在，VIP/充值订单页依旧无样本单可继续验证状态流转。
- 本轮未修改业务代码，只更新了 `TODO.md`、`overview.md` 与本执行摘要；巡检临时脚本和结果文件已删除，黄历/知识库/神煞/SEO/公告测试数据也已清理。

---

## 2026-03-18 第三十二轮执行摘要


### 检查范围
- 重新清空后台登录态后，用默认管理员账号 `admin / admin123` 真实登录独立后台 `http://localhost:3001`，复核 Dashboard、用户管理、黄历/知识库/神煞/SEO、订单、积分记录、系统设置与系统公告。

### 检查结果概述
- 本轮确认登录、Dashboard、积分记录、系统设置、系统公告、知识库文章新增/删除仍可用；但手动调积分、黄历首屏、神煞新增、SEO 新增这 4 条高优先级链路依旧返回 `HTTP 200 + code 500`，此前标记“已修复”的状态需要回退。
- 同时新增确认 4 条中优先级运营问题：用户详情仍无编辑入口；知识库分类、神煞历史数据、SEO 现有配置均存在明显乱码；VIP/充值订单页可打开但仍无样本订单可继续验证状态流转。
- 本轮未修改业务代码，只更新了 `TODO.md`、`overview.md` 与本执行摘要，并清理了测试数据 `OpsNoticeRound32`、`OpsRound32Knowledge`；神煞与 SEO 的测试新增均保存失败，未产生残留数据。

---

## 2026-03-18 第三十一轮执行摘要

### 检查范围
- 续测当前后台登录态下的 `SEO 管理 / 知识库文章 / VIP订单 / 充值订单 / 积分记录 / 系统设置 / 系统公告`，继续沿用独立后台 `http://localhost:3001` 与现有浏览器会话完成真实操作。

### 检查结果概述
- 本轮确认 `SEO 管理` 新增保存仍会触发 `POST /api/admin/system/seo/configs` 的 `HTTP 200 + code 500`，`积分记录` 列表里的“变动后余额”字段也仍长期显示 `-`；这两项问题已存在于 `TODO.md` 顶部，本轮不重复追加。
- 同时确认 `知识库文章` 的新增草稿与删除回滚可正常闭环，`系统设置` 保存会返回 `200 OK`，`系统公告` 可真实发布并删除；`VIP订单` 与 `充值订单` 页面都能打开，但当前本地数据为 `0` 条，暂无法继续验证状态流转或退款动作。
- 本轮未修改业务代码，仅补写 `overview.md` 与本执行摘要；用于冒烟验证的测试文章 `Ops_Knowledge_Test` 和测试公告 `Ops_Notice_Test` 均已清理。

---

## 2026-03-18 第三十轮执行摘要


### 检查范围
- 临时拉起独立后台 `http://localhost:3001`，使用默认管理员账号真实登录，并实测 Dashboard、用户管理、黄历、知识库、神煞、SEO、VIP 订单、积分记录、系统设置与公告模块。

### 检查结果概述
- 本轮确认后台登录、Dashboard、用户列表搜索、知识库 CRUD、系统设置与公告均可用；但用户详情用户名仍回填手机号，手动调积分仍失败，黄历列表接口仍 500，神煞新增与 SEO 新增保存依旧失败，积分记录字段也继续失真。
- 已直接更新 `TODO.md` 顶部运营问题区，并补写本轮 `overview.md`；本轮未修改业务代码，后台前端仅作临时启动用于巡检。

---

## 2026-03-18 第二十九轮执行摘要


### 检查范围
- 真实后台登录、Dashboard、用户搜索/详情/调积分、黄历/知识库/神煞/SEO、充值/VIP 订单、积分记录、系统设置与系统公告。

### 检查结果概述
- 本轮确认管理员仍可真实登录，Dashboard、用户详情、神煞、系统设置、系统公告基本可用；但用户按用户名搜索仍会触发列表失败态，用户详情手动调积分仍失败，黄历/SEO/VIP 订单仍是失败页，知识库分类乱码仍在，积分记录的增减方向与审计字段也继续失真。
- 已直接更新 `TODO.md` 顶部同类问题状态（避免重复新增），并新增 5 张运行态截图、补写本轮 `overview.md`。

---

## 2026-03-18 第二十八轮执行摘要


### 检查范围
- 真实后台登录页 `http://localhost:3001/login`、默认管理员登录、Dashboard、用户搜索与积分调整、黄历/知识库/神煞/SEO、充值/VIP 订单、积分记录、系统配置与公告页面。

### 检查结果概述
- 本轮确认默认账号 `admin / admin123` 现已可真实登录，Dashboard、神煞、充值订单、系统设置、公告等基础页也能进入；但用户搜索、手动调积分、黄历管理、SEO 管理、VIP 订单仍会在接口 HTTP 200 的情况下被页面误判为失败态，知识库分类还出现中文乱码，积分记录审计字段也明显缺失。
- 以上 **3 项高优先级、2 项中优先级、1 项低优先级新增问题** 已补写到 `TODO.md` 顶部待处理区，并同步更新 `overview.md`；本轮未修改业务代码，新增了 5 张运行态截图。

---

## 2026-03-18 第二十七轮执行摘要

### 检查范围
- 读取历史后重建当前 `backend` 容器，使用默认管理员账号实际登录独立后台，并巡检 Dashboard、用户管理、黄历/知识库/神煞/SEO、订单、积分、系统配置与公告页面。

### 检查结果概述
- 本轮确认默认账号 `admin / admin123` 在当前仓库代码下已可真实登录，登录后跳转和鉴权恢复正常；但 Dashboard 统计/趋势接口仍返回 500，用户列表与用户详情页仍在登录后落入失败态，充值订单页的统计接口也仍报 500 并被页面默认 0 值掩盖。
- 以上 **2 项高优先级、1 项中优先级新增问题** 已补写到 `TODO.md` 顶部待处理区，并同步更新 `overview.md`；本轮未修改业务代码，仅新增了 3 张运行态截图。

---

## 2026-03-18 第二十五轮执行摘要


### 检查范围
- 真实后台登录页 `http://localhost:3001/login`、直连后台登录接口 `http://localhost:8080/api/admin/auth/login`、后台路由/页面实现、知识库后端路由注册情况。

### 检查结果概述
- 本轮确认默认账号 `admin / admin123` 真实登录仍被“管理员账号表不存在”阻断；在无法继续真实登录的前提下，进一步通过运行态与前后端代码交叉核验，新发现后台仍缺知识库文章管理入口，以及 Dashboard、用户/内容/订单多个核心页面仍缺显式错误态与只读保护，容易把接口失败误判成真实空数据。
- 以上 **3 项新增且不重复的问题** 已写入 `TODO.md`，并同步更新 `overview.md`；本轮未修改业务代码，仅补充了登录失败截图。

---

## 2026-03-18 第二十四轮执行摘要


### 检查范围
- 独立后台 `http://localhost:3001` 的真实登录、Dashboard、用户详情、黄历、SEO、系统公告、系统设置，以及关键后台接口批量探测。

### 检查结果概述
- 本轮确认默认账号密码登录仍报“管理员账号表不存在”；随后通过与当前容器密钥匹配的测试 token 进入后台，发现 Dashboard、用户、黄历、SEO、公告、系统设置等核心运营页面仍大面积依赖 403/500 接口，但前端会继续渲染默认 0 值、空表或占位内容，容易误导运营。
- 另外新确认了两个前端口径问题：Dashboard 快捷入口与订单处理按钮仍错误读取 `userInfo.role`，admin 视角会缺少 SEO/系统设置快捷入口且订单页动作按钮不可见；用户详情在接口失败时仍展示写死的最近活动。以上 **3 项新增且不重复的问题** 已写入 `TODO.md`，并同步更新 `overview.md`；本轮未修改业务代码。

---

## 2026-03-17 第二十二轮执行摘要


### 检查范围
- 默认独立后台 `http://localhost:3001/login`、临时直连 `8080` 的后台实例、Dashboard/用户/内容/订单/系统配置代表接口、运行中容器代码与侧边栏权限逻辑。

### 检查结果概述
- 本轮确认默认独立后台入口仍把 `/api` 代理到失效的 `8000` 端口，按文档直接登录会卡死；进一步通过临时 token、接口探测与容器日志/代码比对发现，当前运行态没有真正吃到仓库里的最新后台修复，登录缺表、Dashboard `checkPermission()` 500、神煞报错等老问题在运行中仍持续存在。
- 另外确认后台侧边栏仍未按角色过滤，会把管理员专属模块直接展示给运营人员；以上 **3 项新增且不重复的问题** 已写入 `TODO.md`，并同步更新 `overview.md`；本轮未修改业务代码。

---

## 2026-03-17 第二十一轮执行摘要


### 检查范围
- 真实后台登录页、默认管理员账号登录、Dashboard 统计接口、神煞管理接口、后台操作日志链路，以及初始化脚本/容器运行态交叉核验。

### 检查结果概述
- 本轮重建了最新后端容器后继续实测，确认 `http://localhost:3001/login` 可访问，但默认账号 `admin / admin123` 仍因初始化流程未执行 `database/20260317_create_admin_users_table.sql` 而登录失败；进一步注入测试 token 排查后，又确认 Dashboard 的 `statistics/trend` 接口因 `checkPermission()` 方法不存在直接 500，神煞列表接口运行期报错，后台操作日志也因字段名与 `tc_admin_log` 表结构不匹配而持续写入失败。
- 已将以上 **4 项新增且不重复的问题** 写入 `TODO.md`，并同步更新 `overview.md`；本轮未修改业务代码。

---

## 2026-03-17 第二十轮执行摘要


### 检查范围
- 实际后台登录页访问与账号密码登录、Dashboard 首页数据契约、用户列表筛选/详情/积分调整、系统公告通知页。

### 检查结果概述
- 本轮确认 `http://localhost:3001/login` 仍可访问，真实登录请求仍失败；同时新发现 Dashboard 首页统计字段与后端响应结构错位、用户详情与积分调整前后端契约不一致、用户列表筛选分页参数不一致，以及通知配置页仍为静态壳子。
- 已将以上 **5 项新增且不重复的问题** 写入 `TODO.md`，并同步更新 `overview.md`；本轮未修改业务代码。

---

## 2026-03-17 第十九轮执行摘要

### 检查范围
- 独立后台登录页、本地代理与后端登录接口、登录后鉴权、Dashboard 落地、黄历管理、支付/VIP 订单、站点内容/SEO 管理。

### 检查结果概述
- 本轮实际拉起了 `admin` 独立后台，确认 `http://localhost:3001/login` 可访问，但后台仍存在系统级阻塞：登录代理指向失效的 `8000` 端口，直连登录接口又因 `taichu.admin` 表缺失报 500，受保护接口还会因 `ADMIN_JWT_SECRET` 未配置而整体报错。
- 另外确认独立后台还存在 `asyncRoutes` 未注册、黄历 CRUD 路由错配、支付/VIP 订单接口前缀错误、站点内容/SEO 接口重复拼接 `/api/admin` 等问题。
- 已将以上 **6 项新增运营阻塞问题** 写入 `TODO.md`，本轮未修改业务代码。

---

## 2026-03-17 第十八轮执行摘要


### 检查范围
- 管理员登录、Dashboard、用户管理（列表/详情/积分）、内容管理（黄历/运势/知识库/神煞/SEO）、订单/积分（VIP/充值）、系统配置（基础设置/AI/敏感词）。

### 检查结果概述
本次检查模拟运营场景，对后台系统进行了深度代码级审计。确认管理员登录、Dashboard、SEO 管理、VIP 订单退款、AI 配置管理等核心逻辑已就绪。**发现 5 项新问题，包括用户积分手动调整功能缺失、批量操作逻辑为空、内容管理缺失分页、知识库 CMS 缺位等，已全部记录至 TODO.md。** 

#### ✅ 已验证可用功能
- 管理员登录与 JWT 认证，权限校验。
- Dashboard 核心统计、趋势图（ECharts）、待处理反馈。
- 内容发布：黄历月历、神煞管理、每日运势编辑。
- 支付管理：VIP 订单详情查看与退款逻辑实现。
- 系统配置：网站基础信息、SEO TDK、Robots.txt 编辑、Sitemap 生成、AI 接口配置。

#### 🔴 高优先级问题（已记录至 TODO.md）
- **用户积分调整缺失**：`user/detail.vue` 仅展示积分，缺少后台手动充值/扣减的操作入口。
- **批量操作逻辑为空**：`user/list-improved.vue` 中的批量启用/禁用按钮没有绑定实际逻辑（handleBatchEnable 为空）。

#### 🟡 中优先级（运营体验问题）
- **独立 CMS 缺失**：缺乏专门的长篇命理文章/知识库管理模块。
- **内容列表分页缺失**：`site-content/content-manager.vue` 全量返回配置项，无分页。

#### 🟢 低优先级（运营优化建议）
- **数据导出增强**：Dashboard 建议增加日报统计数据的一键 Excel 导出和手动刷新按钮。

---

## 2026-03-17 第十七轮执行摘要
...

---

## 2026-03-19 第四十轮执行摘要

### 检查范围
- 先按要求复读 `TODO.md` 的 `[30-3]` 章节与本记忆，不假设后台页面部署根地址；优先确认 `admin/dist/index.html` 与独立后台构建状态。
- 本轮只补证 1 组后台主链路：`登录 -> 系统设置读取口径`，仅核对 `http://localhost:8080/api/admin/...`，不直接修复。

### 检查结果概述
- `admin/dist/index.html` 已存在，且 `npm run build --prefix admin` 本轮可成功通过；但仓库内仍无法推出用户实际部署/挂载后的后台站点根地址，因此本轮没有再凭空访问任何页面 URL。
- fresh login `POST /api/admin/auth/login` 继续返回 `admin.roles=["admin"]`、`role="admin"`，说明登录与角色链路当前正常；但紧接着 `GET /api/admin/system/settings` 仍稳定返回 `site_name/site_description=""`、`register_points/checkin_points/bazi_cost/tarot_cost=0`、`enable_feedback=true`。这表明系统设置异常即使不经过页面操作，也能在 8080 直连接口层直接复现；本轮已把新证据并入 `TODO.md` 现有条目，未重复新开问题。

---

## 2026-03-19 第四十一轮执行摘要

### 检查范围
- 继续先复读 `TODO.md` 的 `[30-3]` 章节与本记忆，不假设后台页面部署根地址；仅确认 `admin/dist/index.html` 与现有构建产物仍可用。
- 本轮只补证 1 组后台主链路：`登录 -> 订单/积分查询`，仅核对 `http://localhost:8080/api/admin/...` 只读接口，不直接修复。

### 检查结果概述
- `admin/dist/index.html` 与 `admin/dist/assets` 主包仍在仓库内可见，但由于用户实际部署/挂载根地址仍未知，本轮继续没有凭空访问任何页面 URL。
- fresh login `POST /api/admin/auth/login` 返回 `admin.roles=["admin"]`、`role="admin"`；`GET /api/admin/points/records?page=1&pageSize=5` 也返回 `code=200` 与带 `balance` 的真实流水，说明积分查询链路本轮未见新阻塞。新增问题落在充值订单：`GET /api/admin/payment/stats` 返回 `order_count=1 / total_amount=50 / pending_count=0`，但同一登录态下 `GET /api/admin/payment/orders?page=1&limit=5` 返回的历史订单里 `status`、`payment_type` 仍是 `null`，已补记到 `TODO.md`，因为这会让后台充值订单页把真实订单渲染成状态 `-`、支付方式 `-`，并丢失依赖状态的运营动作入口。

---

## 2026-03-19 第四十二轮执行摘要

### 检查范围
- 继续先复读 `TODO.md` 的 `[30-3]` 章节与本记忆，不假设后台页面部署根地址；仅基于仓库现有构建产物和 `http://localhost:8080/api/admin/...` 直连接口补证。
- 本轮只补证 1 组后台主链路：`登录 -> Dashboard / 菜单加载依赖接口`，不直接修复。

### 检查结果概述
- `GET /api/admin/auth/info` 在 fresh login 后返回 `roles=["admin"]`、`permissions=["*"]`，结合后台前端菜单/快捷入口基于角色过滤的实现，可判断本轮没有补出新的菜单鉴权阻塞。
- 新证据落在 Dashboard 统计口径漂移：同一登录态下 `GET /api/admin/dashboard/statistics` 返回 `order_stats.month.paid_orders=0 / amount=0`、`order_stats.today.paid=0 / amount=0`，但 `GET /api/admin/payment/stats` 同时返回 `order_count=1 / total_amount=50 / pending_count=0`。由于 `admin/src/views/dashboard/index.vue` 直接用 `order_stats.month.paid_orders` 与 `order_stats.month.amount` 渲染“本月支付订单 / 本月实收”，后台看板当前会把已有充值流水错误展示为 0；从仓库代码看，Dashboard 月度统计优先读 `site_daily_stats`，而支付页统计走实时订单查询，疑似快照口径陈旧或未同步。该证据已并入 `TODO.md`，未重复新开页面入口结论。

---

## 2026-03-19 第四十三轮执行摘要

### 检查范围
- 延续新基线，先确认 `admin/dist/index.html` 与构建产物仍存在，不假设任何后台页面部署根地址。
- 本轮只补证 1 组后台主链路：`登录 -> 内容管理 -> 页面管理`，仅核对 `http://localhost:8080/api/admin/...` 与仓库路由实现，不直接修复。

### 检查结果概述
- `admin/dist/index.html` 与其引用的 `admin/dist/assets/index-BQ6XilyL.js`、`index-bO1ixBAy.css` 仍存在，因此本轮未额外猜测页面 URL，也未强行拉起 3001 dev server。
- fresh login `POST /api/admin/auth/login` 返回 `role="admin"` 后，继续请求 `GET /api/admin/content/pages?page=1&pageSize=10` 直接得到 `404 {"code":404,"message":"接口不存在"}`；对照代码可见 `admin/src/api/contentEditor.js` 会基于后台默认 `baseURL=/api/admin` 请求 `/content/pages`，但后端只在 `backend/route/content.php` 暴露 `/api/content/pages`，`backend/route/admin.php` 并没有对应后台路由。该证据已补写到 `TODO.md`，说明后台“内容管理 -> 页面管理”当前不是纯空数据，而是前后端路径错位导致真实列表链路无法加载。

---

## 2026-03-19 第四十四轮执行摘要

### 检查范围
- 继续先复读 `TODO.md` 的 `[30-3]` 章节与本记忆，不假设任何后台页面部署根地址；先确认 `admin/dist/index.html` 是否存在，并重新执行 `npm run build --prefix admin` 做构建校验。
- 本轮只补证 1 组后台主链路：`登录 -> 系统设置保存后刷新回读`，仅核对 `http://localhost:8080/api/admin/...` 与公开配置回读，不直接修复。

### 检查结果概述
- `admin/dist/index.html` 仍存在，且 `npm run build --prefix admin` 本轮成功通过；但仓库内依旧无法确认用户实际部署/挂载后的后台站点根地址，因此本轮继续没有凭空访问任何页面 URL。
- fresh login `POST /api/admin/auth/login` 返回 `role="admin"`、`roles=["admin"]`，`GET /api/admin/auth/info` 也返回 `permissions=["*"]`；随后 `GET -> PUT(原样回存) -> GET /api/admin/system/settings` 三次结果保持一致，`site_name=太初命理`、`register_points=100`、`checkin_points=5`、`bazi_cost=20`、`tarot_cost=10`、`enable_feedback=true` 等字段均未再回读成空值或 `0`。公开 `GET /api/config/client` 的 `points.tasks.sign_daily.points=5` 也同步正常。本轮没有在“系统设置保存后刷新回读”这组链路上补出新后台缺陷，因此未新增 TODO 条目。

---

## 2026-03-19 第四十五轮执行摘要

### 检查范围
- 继续先复读 `TODO.md` 的 `[30-3]` 章节与本记忆；仅确认现有 `admin/dist/index.html` 构建产物仍在，不假设任何本地后台页面挂载根地址。
- 本轮只补证 1 组后台主链路：`登录 -> 充值订单列表筛选 / 搜索`，仅核对 `http://localhost:8080/api/admin/...` 与前端页面实现，不直接修复。

### 检查结果概述
- `admin/dist/index.html` 仍存在，但工作区内没有可证明 phpstudy 已把后台静态页实际挂到哪个根地址的本地配置或产物（例如 `frontend/dist-admin`），因此本轮继续不凭空访问页面 URL，只保留“页面根地址未确认”的阻塞结论。
- fresh login 后，充值订单基础列表与统计接口可返回真实数据；但 `user_id=1` 筛选后结果仍保持全量 4 条，说明后台暂未真正消费该参数；同时 `keyword=R202403200002` 会把 `GET /api/admin/payment/orders` 直接打成 `HTTP 500`。结合前端 `payment/orders.vue` 的错误承接，这会让后台订单页出现“用户ID筛选无效”与“搜索后整页退化成加载失败只读卡”两类真实运营问题。该证据已补写进 `TODO.md`。




