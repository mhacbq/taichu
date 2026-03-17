## 待处理项目

### 🔴 高优先级
- [x] [2026-03-18] [代码] 后端部分控制器直接返回 `$e->getMessage()` 给前端，可能泄露敏感异常信息 - backend/app/controller/{Admin,AiPrompt,AiAnalysis}.php - 已将反作弊规则新增/更新、AI 提示词 JSON 校验、AI 配置保存与连接测试等入口统一切换到 `respondBusinessException()` / `respondSystemException()`，避免原始异常信息直接暴露给前端。
- [x] [运营] 重建到当前仓库代码后，管理员已能成功登录，但 Dashboard 的 `/api/admin/dashboard/statistics` 与 `/api/admin/dashboard/trend` 仍持续返回 500，首页只能停留在“运营看板加载失败”错误态，日常查看用户数、订单数、收入趋势与快捷决策被直接阻断 - 后台首页 / Dashboard - 已在 `backend/app/service/AdminStatsService.php` 改为统一走 schema 探测与运行态降级：趋势统计不再依赖 `column('*')` 的隐式行为，`site_daily_stats` 缺失时自动回退实时业务表；`refresh-stats` 也改为复用实时快照落库，避免旧表名/旧字段继续导致 Dashboard 统计与趋势接口 500。
- [x] [运营] 管理员成功登录后，用户列表 `/user/list` 与用户详情 `/user/detail/:id` 仍会直接进入“加载失败”只读态；浏览器网络显示列表/详情请求都已命中 `GET /api/admin/users*`，但页面无法消费真实数据，导致用户搜索、详情查看和列表内调积分链路全部不可用 - 用户管理 / 用户列表 / 用户详情 - 已在 `backend/app/service/AdminStatsService.php` 将用户列表改为基于运行态表探测的子查询方案，兼容 `tc_user_vip/user_vip` 与缺失命理记录表；同时在 `backend/app/controller/admin/User.php` 为积分记录、八字、塔罗、六爻、订单信息补齐缺表降级，恢复列表/详情链路。


### 🟡 中优先级
- [x] [运营] 充值订单页 `GET /api/admin/payment/orders` 已能返回列表，但统计接口 `/api/admin/payment/stats` 仍返回 500，页面顶部“支付订单 / 累计实收 / 累计发放积分 / 待支付订单”继续显示默认 0 值，运营容易把异常误判成真实订单表现 - 订单管理 / 充值订单 - 已在 `backend/app/controller/admin/Payment.php` 修正去重用户统计写法，并把统计/趋势查询改为兼容 `status` / `pay_status` 两套状态字段；旧 schema 下也能正确计算支付、待支付与趋势数据，不再因状态列漂移直接 500。


### 🟢 低优先级


---

## 🔍 代码逻辑检查报告 - 第二十六轮 (2026-03-18)

作为代码重构与质量维护负责人，我继续聚焦 `TODO.md` 中的 `[代码]` 维护方向，完成以下 5 个不重复的日常优化点：

### 🔴 高优先级
- [x] [代码] 后端部分控制器仍会把 `InvalidArgumentException` 原文直接回给前端，反作弊规则、AI 提示词和 AI 配置入口都存在敏感异常透出风险 - `backend/app/controller/{Admin,AiPrompt,AiAnalysis}.php` - 已统一接入 `respondBusinessException()` / `respondSystemException()`，让前端只接收安全业务文案，异常细节改走结构化日志。

### 🟡 中优先级
- [x] [代码] 系统设置保存失败时会把完整 `settings` 与 `trace` 原文写入日志，后续一旦写入密钥类配置就有敏感信息落盘风险 - `backend/app/controller/Admin.php` - 已改为仅记录 `config_keys`、`settings_count` 等结构化上下文，去掉完整配置和堆栈文本。
- [x] [代码] AI 解盘普通/流式链路失败日志仍携带原始 trace，排障信息虽多但噪音大且不利于统一脱敏 - `backend/app/controller/AiAnalysis.php` - 已统一改为记录 `prompt_key`、`prompt_used`、`has_custom_prompt`、`bazi_keys` 等必要字段，移除原始堆栈落盘。
- [x] [代码] AI 提示词保存/复制失败仍使用手写字符串日志，JSON 字段校验也没有纳入统一异常收口，日志格式和其它控制器不一致 - `backend/app/controller/AiPrompt.php` - 已接入统一控制器异常 helper，并补齐 `key/type/operator_id` 等上下文，顺手清理未使用的 `Log` 导入。

### 🟢 低优先级
- [x] [代码] AI 提示词编辑弹窗保留“设为默认”开关，但保存接口不会处理该字段，属于误导性冗余逻辑 - `admin/src/views/ai/prompts.vue` - 已移除无效开关，编辑/提交时改为只回填和发送后端真实支持的字段，避免前后端契约继续漂移。

---

## 🛠 第二十六轮后台运营修复记录 (2026-03-18)


作为后台修复负责人，我继续围绕独立后台的运营主链路，对知识库、Dashboard、用户、内容、订单等核心页面做联动修复，完成以下 5 个不重复问题：

### 🔴 高优先级（运营阻塞问题）
- [x] [运营] 当前独立后台虽已有 `/api/admin/knowledge/articles`、`/api/admin/knowledge/categories` 等后端接口，但后台仍缺少真正可用的文章/分类管理入口，运营无法在独立后台发布、编辑或分类命理文章 - 内容管理 / 知识库文章管理 - 已新增 `admin/src/api/knowledge.js` 与 `admin/src/views/site-content/knowledge.vue`，并在 `admin/src/router/index.js`、Dashboard 快捷操作中接入 `/site/knowledge` 入口，后台现可直接管理知识库文章与分类。
- [x] [运营] Dashboard 在统计、趋势、实时数据任一接口失败时仍继续展示默认 0 值或“尚未加载”，运营难以分辨“确实无数据”还是“接口未成功返回”，并可能导出无效快照 - 后台首页 / Dashboard - 已在 `admin/src/views/dashboard/index.vue` 为整页看板补齐显式错误态、重试入口和只读保护；任一模块加载失败即停止展示默认空数据，并统一按 `roles` 数组过滤快捷操作与导出入口。

### 🟡 中优先级（运营体验问题）
- [x] [运营] 用户列表在加载失败时只有 toast 提示，表格主体仍可能显示空表或旧数据，状态切换、批量启停和积分调整仍可继续操作，容易误判为“没有用户”或误改用户状态 - 用户管理 / 用户列表 - 已在 `admin/src/views/user/list.vue` 补齐页级错误态与只读保护；列表加载失败时不再展示默认空数据，并统一禁用状态修改、批量操作、积分调整和导出入口。
- [x] [运营] 黄历/神煞管理在接口失败时仍主要依赖 toast，页面主体继续保留空表或旧列表，写操作未统一进入只读保护；同时神煞页筛选项与后端 options、状态筛选、状态切换更新链路存在脱节 - 内容管理 / 黄历与神煞 - 已在 `admin/src/views/content/{almanac,shensha}.vue` 补齐显式错误态、重试入口与只读保护，并改为真实读取 `/api/admin/system/shensha/options`；同时加固 `backend/app/controller/admin/Shensha.php`，补上 `AdminAuth`、`content_manage` 权限、`status` 筛选与局部更新兼容。
- [x] [运营] 充值订单与 VIP 订单页在加载失败时仍主要依赖 toast 报错，页面主体会继续显示空表或旧数据，统计卡片也会沿用默认值或旧列表结果，运营很难判断“暂无数据”还是“接口没加载成功” - 订单管理 / 充值订单 / VIP 订单 - 已在 `admin/src/views/payment/{orders,vip-orders}.vue` 补齐整页错误态、重试入口与只读保护，统一改为基于 `roles` 数组判断管理员写权限，并在失败态下禁用补单、退款等写操作。

### 🟢 低优先级（运营优化建议）
- [x] [代码] 本轮同步把 `admin/src/api/{dashboard,content,payment,user,points}.js` 的相关接口扩展为可传 `showErrorMessage: false`，让页面统一接管错误态与只读保护，减少重复 toast 干扰。

---

## 🛠 第二十五轮后台运营检查报告 (2026-03-18)

作为网站运营人员，我先按真实运营路径打开独立后台 `http://localhost:3001/login`，并用默认账号 `admin / admin123` 发起登录；浏览器与直连接口都返回“管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql”。在登录链路仍被阻断的前提下，我继续结合当前后台运行态、已注册路由与核心页面实现复核 Dashboard、用户、内容、订单、系统配置链路，仅记录本轮新增且未与现有 `TODO.md` 重复的项：

### 🔴 高优先级（运营阻塞问题）
- [x] [运营] 当前独立后台仍没有任何“知识库文章/分类管理”页面或菜单入口，`admin/src/router/index.js` 与 `admin/src/views/` 都未接入知识库模块；虽然后端已经注册 `/api/admin/knowledge/articles`、`/api/admin/knowledge/categories` 等接口，运营仍无法在后台实际发布、编辑或分类命理文章 - 内容管理 / 知识库文章管理 - 已复核确认该项已在现有代码落地：`admin/src/router/index.js` 已注册 `/site/knowledge` 菜单，`admin/src/views/site-content/knowledge.vue` 与 `admin/src/api/knowledge.js` 已接通文章/分类管理 UI，`backend/app/controller/admin/Knowledge.php` 与 `backend/route/admin.php` 也已完整提供文章、分类的列表/详情/保存/删除接口。

### 🟡 中优先级（运营体验问题）
- [x] [运营] VIP 订单页在加载失败时仍主要依赖 toast 报错，页面主体会继续显示空表或旧数据，顶部统计也会沿用旧列表结果，运营很难判断“暂无数据”还是“接口没加载成功” - 订单管理 / VIP 订单 - 已在 `admin/src/views/payment/vip-orders.vue` 补齐整页错误态、重试入口、失败时清空旧列表/统计与只读保护；同时为 `admin/src/api/payment.js` 的 VIP 订单请求补上可选参数透传，页面可统一关闭默认 toast 并改为页内错误承接。

### 🟢 低优先级（运营优化建议）

---


## 🛠 第二十四轮后台运营检查报告 (2026-03-18)


作为网站运营人员，我按真实运营路径再次实测独立后台 `http://localhost:3001`：先用默认账号尝试登录，确认账号密码登录仍返回“管理员账号表不存在”；随后使用可通过 `auth/info` 的测试 token 进入 Dashboard、用户、黄历、SEO、公告、系统设置等页面，并结合接口探测与前端逻辑交叉核验。以下仅记录本轮新增且未与 `TODO.md` 现有问题重复的项：

### 🔴 高优先级（运营阻塞问题）
- [x] [运营] 基础配置、SEO 管理、系统公告等页面在接口返回 403 时仍继续渲染默认 0 值或“暂无数据”，运营无法区分“真的没数据”和“其实无权限/加载失败”，继续保存还可能误把默认空配置覆盖到线上 - 系统配置 / SEO / 通知配置 - 已在 `admin/src/views/system/settings.vue`、`admin/src/views/site-content/seo.vue`、`admin/src/views/system/notice.vue` 补齐显式错误态与只读保护；接口 403/加载失败时不再展示默认空数据，并统一禁用保存、发布、删除、Robots 保存与收录提交等写操作。

### 🟡 中优先级（运营体验问题）
- [x] [运营] `auth/info` 返回的是 `roles` 数组，但 Dashboard 快捷操作与充值/VIP订单页仍读取 `userInfo.role` 单值判断权限，实测 admin 进入首页时看不到 SEO/系统设置快捷入口，订单页后续即便有数据也不会显示“补单/退款”等处理按钮 - 后台首页 / 订单管理 - 已在 `backend/app/controller/admin/Auth.php` 与 `backend/app/middleware/AdminAuth.php` 改为实时查库返回 `roles + role + permissions + status`，并统一基于有效管理员账号状态回填角色，后台仍读取单值 `role` 的页面也能正确识别管理员权限。

### 🟢 低优先级（运营优化建议）

---

## 🔍 代码逻辑检查报告 - 第二十四轮 (2026-03-18)


作为代码审查专家，我对太初命理网站的代码进行了全面检查，发现以下问题：

### 🔴 高优先级
- [x] [代码] 后台 JWT 鉴权仍会回退固定默认密钥，环境变量缺失时后台接口会落到已知密钥上运行 - `backend/app/middleware/AdminAuth.php`、`backend/app/controller/admin/Auth.php` - 已移除固定默认密钥回退，统一改为仅接受 `ADMIN_JWT_SECRET / JWT_SECRET` 的真实配置，缺失时返回明确配置错误并写入告警日志。
- [x] [代码] 后台鉴权中间件只要能解开 JWT 就放行，未校验 `iss / is_admin`，也不会回查管理员状态，普通用户 token 或已停用管理员会话存在误入后台风险 - `backend/app/middleware/AdminAuth.php`、`backend/app/service/AdminAuthService.php` - 已增加 `taichu-admin` 签发者与 `is_admin=true` 校验，并在每次请求时实时检查管理员账号是否存在且启用，再按数据库角色回填 `roles / role / permissions`。

### 🟡 中优先级
- [x] [代码] 系统角色、权限、字典接口此前只有 `AdminAuth`，缺少 `config_manage` 级别门禁，任意已登录后台账号都可能越权查看或修改系统配置 - `backend/app/controller/admin/System.php` - 已补统一 `ensureSystemReadAccess()` / `ensureSystemWriteAccess()`，为角色、权限、字典的读写接口统一加上权限校验。
- [x] [代码] 后台统计/鉴权链路多处直接写死 MySQL `SHOW TABLES / SHOW COLUMNS` 探测表结构，数据库驱动兼容性和可维护性都偏弱 - `backend/app/service/SchemaInspector.php`、`backend/app/service/AdminStatsService.php`、`backend/app/controller/admin/Auth.php`、`backend/app/middleware/AdminAuth.php` - 已收敛为统一的 schema 探测服务，改用 ThinkORM 的表字段信息能力读取表/字段存在性。

### 🟢 低优先级
- [x] [代码] `auth/info` 之前直接回 token 内角色并写死 `permissions=['*']`，角色调整后前端展示与实际权限容易长期不一致 - `backend/app/controller/admin/Auth.php` - 已改为基于中间件注入的有效管理员身份实时查库，返回真实 `roles / role / permissions / status`，减少后台权限展示与实际授权脱节。

代码整体质量观察：

**前端代码 (frontend/src)**
- Vue组件结构清晰，错误处理完善
- API请求封装良好，有统一的请求/响应拦截器
- 路由配置完整，权限控制通过meta字段实现
- 未发现语法错误、类型错误或明显的逻辑问题

**管理后台 (admin/src)**
- 路由配置完整，使用动态权限角色控制
- 权限控制通过meta字段实现，基于角色数组校验
- 数据表格和表单正常

**后端代码 (backend/app)**
- 使用ThinkPHP框架，遵循MVC架构
- 数据库操作使用查询构建器，防止SQL注入
- API返回格式统一使用 `$this->success()` 和 `$this->error()`
- 中间件实现正确，JWT认证流程完善
- 统一异常处理和日志脱敏机制完善

---

## 🔍 代码逻辑检查报告 - 第二十五轮 (2026-03-18)

作为代码重构与质量维护负责人，我继续聚焦 `TODO.md` 中的 `[代码]` 维护方向，完成以下 3 个不重复的日常优化点：

### 🟡 中优先级
- [x] [代码] 前端请求缓存工具存在未定义 `instance`、未使用导入和残留调试输出，`preloadAPI()` 实际调用时会直接命中运行时错误 - `frontend/src/utils/requestCache.js` - 已重写缓存请求工具，统一通过可注入缓存 client 执行预加载，接入 `requestDeduper` 做并发去重，并将 `clearAPICache()` 改为显式返回清理结果，移除冗余控制台输出。
- [x] [代码] 前端埋点模块在 Vite 环境下仍使用 `process.env.NODE_ENV`，同时默认采集完整 URL / referrer / query / params / stack，存在错误环境判断与敏感信息落日志风险 - `frontend/src/utils/analytics.js` - 已改为 `import.meta.env.DEV` 判定开发环境，并统一对路径、来源域名、路由键、异常栈摘要做裁剪脱敏，开发态仅输出事件摘要，不再打印原始属性。
- [x] [代码] AI 服务对 DeepSeek / OpenAI / Claude 的第三方请求逻辑重复，失败时直接记录原始响应体或异常原文，日志不够结构化且不利于脱敏 - `backend/app/service/AiService.php` - 已收敛为统一请求发送 helper，复用公共 system prompt / 响应提取逻辑，并改为记录 `provider / status / error_code / response_hash` 等结构化字段，避免把完整第三方返回体直接落盘。

### 🟢 低优先级
- [x] [代码] 本轮同步清理了一批工具层冗余逻辑：缓存预加载入口不再依赖隐式全局实例，AI 多提供商调用改为统一出口，后续维护成本更低。

---

## 🛠 第二十三轮后台运营检查报告 (2026-03-17)


作为网站运营人员，我继续沿着独立后台 `http://localhost:3001` 的真实使用路径复核管理员管理、风控规则、日志管理与本地 fresh setup 链路，并结合前后端契约、已注册路由与初始化 SQL 再次交叉核验，补充记录以下 5 个已落地修复项：

### 🔴 高优先级（运营阻塞问题）
- [x] [运营] 管理员管理页只有列表壳子，`/api/admin/system/admins` 的保存/删除接口未实现，新增/编辑/删除管理员链路全部中断 - 系统设置 / 管理员管理 - 已在 `backend/app/controller/Admin.php` 新增 `saveAdminUser()`、`deleteAdminUser()`，补齐角色绑定、用户名唯一性校验、自删保护与操作日志；同时 `admin/src/views/system/admin.vue` 已接回真实接口，支持列表加载、新增、编辑、删除和状态切换。
- [x] [运营] 积分统计接口 `GET /api/admin/points/stats` 已在路由注册，但控制器缺失实现，后台无法获取独立积分统计快照 - 积分管理 / 统计概览 - 已在 `backend/app/controller/Admin.php` 新增 `pointsStats()`，并为 `backend/app/service/AdminStatsService.php` 增加公开复用入口 `getPointsStatsSnapshot()`；同时补上 `points_balance` 的实时兜底与每日统计写回。
- [x] [运营] 风控规则更新接口缺失，现有 `tc_anti_cheat_rule.config` JSON 结构与前端 `threshold` 字段也不兼容，新增/编辑规则会直接失败 - 反作弊系统 / 风险规则 - 已在 `backend/app/controller/Admin.php` 补齐 `updateRiskRule()`，并重写规则读写逻辑，统一完成 `threshold <-> config` 映射、规则编码生成、默认动作/状态兜底和审计日志记录；`admin/src/views/anticheat/rules.vue` 也已接回真实接口。

### 🟡 中优先级（运营体验问题）
- [x] [运营] 日志管理缺少清空/导出后端实现，且前端列表字段与 `tc_admin_log` 原始结构错位，日志筛选、详情和导出都不可用 - 日志管理 / 操作日志 - 已在 `backend/app/controller/Admin.php` 补齐 `clearLogs()`、`exportLogs()`，统一适配 `operator/module/action/dateRange` 查询条件与 `operator/description/request/response` 展示字段；`admin/src/api/log.js` 改为独立 blob 下载，`admin/src/views/log/operation.vue` 已新增导出按钮并接通真实接口。
- [x] [运营] fresh setup 仍未纳入 `site_daily_stats` 与反作弊相关表，补完接口后首次初始化依然会因为缺表失败 - 本地初始化 / 后台依赖表 - 已新增 `database/20260317_create_admin_stats_tables.sql`、`database/20260317_create_anticheat_tables.sql`（含默认风控规则种子），并把它们挂入 `backend/docker-compose.yml`、`README.md`、`database/backup/README.md` 的初始化链路说明中。

---

## 🛠 第二十二轮后台运营检查报告 (2026-03-17)


作为网站运营人员，我继续按真实运营路径巡检后台：先实测默认独立后台 `http://localhost:3001/login`，确认登录页可打开但提交会命中失效的 `8000` 端口；随后临时拉起直连 `8080` 的后台实例、注入管理员 token 进入 Dashboard，并结合受保护接口实测、运行中容器代码比对与错误日志交叉核验，新增以下不重复问题：

### 🔴 高优先级（运营阻塞问题）
- [x] [运营] 当前运行中的后台容器仍在跑旧代码/旧初始化状态，仓库里已补的登录初始化、`checkPermission()` 兼容和神煞建表修复都没有在运行态生效，导致管理员登录继续报“管理员账号表不存在”，Dashboard 统计/趋势继续 500，神煞管理继续报错 - 管理员登录 / Dashboard / 内容管理 - 已在 `backend/Dockerfile` 安装 `default-mysql-client`，并让 `backend/docker-compose.yml` 把 `database/` 补丁目录挂载进后端容器；`backend/docker-entrypoint.sh` 现会在每次启动时等待数据库就绪后自动补跑 `20260317_create_admin_users_table.sql`、`20260317_create_shensha_table.sql`、`20260317_create_knowledge_tables.sql` 等幂等 SQL，旧数据卷也能补齐后台依赖表；同时根 `README.md` / `本地启用.md` 已统一为 `cd backend && docker compose up -d --build` 口径，避免继续启动旧镜像。


### 🟡 中优先级（运营体验问题）
- [x] [运营] 独立后台默认本地运行配置仍把 `/api` 代理到 `http://localhost:8000`，而当前标准本地后端健康地址是 `http://localhost:8080/api/health`，按文档直接打开 `http://localhost:3001/login` 提交登录会打到空端口卡死 - 管理员登录 / 本地联调入口 - 已将 `admin/vite.config.js` 改为默认代理 `http://localhost:8080`，并支持通过 `VITE_PROXY_TARGET` 覆盖；`start-local.ps1` 在拉起后台 dev server 时会显式注入该变量，`README.md`、`本地启用.md`、`database/backup/README.md` 也已统一到 8080 口径。
- [x] [运营] 后台侧边栏没有按角色过滤菜单，`admin/src/layout/components/Sidebar/index.vue` 直接把 `asyncRoutes` 全量渲染，运营人员会看到“短信管理 / AI管理 / 系统设置 / 日志管理 / 任务调度”等无权限入口，点进去后才会被拦截 - 后台导航 / 权限体验 - 已在 `admin/src/stores/user.js` 增加角色归一化与递归路由过滤逻辑，`Sidebar/index.vue` 改为消费 `accessRoutes`，并同步修正 `admin/src/router/index.js` 的守卫为基于角色数组校验/刷新时自动补拉用户信息，运营侧不会再看到无权限菜单。

---

## 🛠 第二十一轮后台运营检查报告 (2026-03-17)


作为网站运营人员，我在本地重建最新后端后，再次实测 `http://localhost:3001/login`、使用默认账号 `admin / admin123` 执行真实登录，并结合浏览器自动化、后台关键接口探测与运行日志核验，重点复核管理员登录、Dashboard、内容管理与日志链路，新增以下不重复问题：

### 🔴 高优先级（运营阻塞问题）
- [x] [运营] 本地/部署初始化流程未执行 `database/20260317_create_admin_users_table.sql`，默认管理员主表与角色绑定缺失，账号密码登录仍返回“管理员账号表不存在”，即使绕过登录受保护模块也统一无权限 - 管理员登录/登录后权限验证 - 已将 `database/20260317_create_admin_users_table.sql` 纳入 `backend/docker-compose.yml` 初始化链路，并在 `database/backup/README.md` 补齐标准导入顺序，fresh setup 会自动创建默认管理员与角色绑定。
- [x] [运营] Dashboard 统计与趋势接口控制器仍调用不存在的 `checkPermission()`，`/api/admin/dashboard/statistics` 与 `/api/admin/dashboard/trend` 实测直接 500，首页看板只能显示 0 和“尚未加载” - 后台首页 Dashboard - 已在 `backend/app/BaseController.php` 增加兼容 `checkPermission()`，`admin/Dashboard` 等已模块化控制器可继续复用旧权限判断写法，不再因方法缺失直接 500。
- [x] [运营] 神煞管理依赖的 `tc_shensha` 数据表未纳入主初始化脚本，`/api/admin/system/shensha` 实测返回“获取神煞列表失败，请稍后重试”，内容管理链路无法正常使用 - 内容管理/神煞数据 - 已新增 `database/20260317_create_shensha_table.sql`（含表结构与默认种子），并纳入容器初始化与手工导库流程，后台神煞管理 fresh setup 后即可直接使用。

### 🟡 中优先级（运营体验问题）
- [x] [运营] 后台操作日志写入字段与 `tc_admin_log` 当前表结构不一致，请求期间持续报“后台操作日志写入失败”，日志管理与审计记录失真 - 日志管理/系统审计 - 已把 `backend/app/middleware/AdminAuth.php` 与 `backend/app/service/AdminStatsService.php` 改为按实际列名自适应写入 `detail/request_url/request_method` 等字段，并兼容旧版 `method/url/params` 结构。

---

## 🛠 第二十轮后台运营检查报告 (2026-03-17)


作为网站运营人员，我继续基于实际登录页操作（http://localhost:3001/login）、直连接口探测和后台前后端代码交叉核验，重点复核 Dashboard、用户详情/筛选、通知配置等链路，新增以下不重复问题：

### 🔴 高优先级（运营阻塞问题）
- [x] [运营] Dashboard 首页统计卡片与后端响应结构错位，核心经营指标无法可靠展示 - 后台首页 Dashboard - 已在 `backend/app/service/AdminStatsService.php` 补齐 `statistics/user_trend/bazi_trend/tarot_trend` 兼容结构，独立后台可继续按 `res.data.statistics.*` 渲染统计卡片与趋势图。
- [x] [运营] 用户详情页渲染结构和积分调整入参都未对齐后端 - 用户管理/用户详情与积分调整 - 已在 `backend/app/controller/admin/User.php` 同时返回平铺字段与 `user/stats/actions` 嵌套结构，并兼容 `/points/adjust` 的 `points` 与 `type/amount` 两套入参。

### 🟡 中优先级（运营体验问题）
- [x] [运营] 用户列表搜索筛选与分页参数未对齐后端，按用户名/手机号/时间筛选和每页条数调整可能无效 - 用户管理/用户列表 - 已在 `backend/app/service/AdminStatsService.php` 兼容 `username、phone、dateRange、pageSize` 等参数别名，并补齐列表展示所需的 `username/avatar/bazi_count/tarot_count` 字段。

- [x] [运营] 系统公告页面仍是静态壳子，通知配置无法实际加载、发布或删除 - 系统配置/通知配置 - 已为 `admin/src/views/system/notice.vue` 接入 `getNotices/saveNotice/deleteNotice`，补齐加载、发布/编辑、删除与提交状态处理，后台可直接维护公告通知。


### 🟢 低优先级（运营优化建议）
- [x] [运营] Dashboard 缺少面向运营的快捷操作入口 - 后台首页 Dashboard - 已在 `admin/src/views/dashboard/index.vue` 增加黄历、订单、公告、反馈、SEO、系统设置等快捷入口，并按角色过滤展示，运营高频操作可一键直达。

---

## 🛠 第十九轮后台运营检查报告 (2026-03-17)

作为网站运营人员，我实际拉起了独立管理后台（http://localhost:3001），确认登录页可以访问；随后结合真实接口请求、数据库状态与后台前端代码，对登录、Dashboard、用户、内容、订单、系统配置等链路进行了交叉核验，新增以下不重复问题：

### 🔴 高优先级（运营阻塞问题）
- [x] [运营] 管理后台账号密码登录直接 500 - 管理员登录 - 已在 `backend/app/controller/admin/Auth.php` 兼容 `tc_admin/admin` 表名与 `ADMIN_JWT_SECRET/JWT_SECRET`，并新增 `database/20260317_create_admin_users_table.sql` 用于初始化管理员主表与默认管理员账号。
- [x] [运营] 后台鉴权中间件缺少 `ADMIN_JWT_SECRET` 导致受保护接口全量 500 - 登录后跳转和权限验证 - 已在 `backend/app/middleware/AdminAuth.php` 去除构造阶段硬依赖，改为优先读 `ADMIN_JWT_SECRET`，回退 `JWT_SECRET`，最后使用开发默认值并记录 warning。
- [x] [运营] 独立管理后台业务路由未真正注册 - 后台首页 Dashboard - 已改为在 `admin/src/router/index.js` 中注册 `constantRoutes + asyncRoutes`，并清理 `admin/src/stores/user.js` 中未落地的伪动态路由逻辑，登录后可正常进入后台业务页。

- [x] [运营] 黄历管理 CRUD 请求路径与后端路由不一致 - 内容管理/黄历数据 - 已在 `backend/route/admin.php` 补齐 `/api/admin/content/almanac*` REST 路由，并在 `backend/app/controller/Admin.php` 兼容 `tc_almanac/almanac` 表结构、CRUD 与月度生成逻辑。

- [x] [运营] 支付配置、充值订单、VIP订单接口路径错误且 VIP 路由缺失 - 订单/积分管理 - 已修正 `admin/src/api/payment.js` 的支付/订单请求地址，补齐 `backend/route/admin.php` 中的 `/api/admin/order*` 路由，并对充值订单、支付配置、VIP订单页面字段映射做了联调，后台可直接使用现有接口。
- [x] [运营] SEO 管理前端界面联调缺失 - 内容管理/SEO内容 - 已在 `admin/src/views/site-content/seo.vue` 补齐搜索、分页、编辑、Robots 保存与搜索引擎收录提交 UI，并改为按后端返回的 `list/sitemap/submitStatus` 结构渲染。
- [x] [运营] 系统设置获取存在硬编码且修改后未即时同步 - 系统设置 - 已移除 `admin/src/views/system/settings.vue` 的硬编码默认值，修复 Logo 上传地址和返回解析，同时在 `backend/app/controller/Admin.php` 保存后清理 `ConfigService` 缓存，确保后台修改后前台新请求立即读取最新配置。


### 🟡 中优先级（运营体验问题）

### 🟢 低优先级（运营优化建议）

---

## 🎨 第三十八轮UI设计检查报告 (2026-03-17)

作为资深产品经理和UI设计师，我继续从首页数据可信度、结果页移动端信息承载、塔罗多牌阵上下文、个性化运势模块样式完整性与合婚长报告可读性几个角度复核太初命理网站，新增以下不重复问题：

### 🔴 高优先级（功能性问题）

### 🟡 中优先级（体验问题）


### 🟢 低优先级（美观问题）


---

## 🎨 第三十七轮UI设计检查报告 (2026-03-17)


作为资深产品经理和UI设计师，我继续从关键转化文案、合婚交互预期、异常反馈承接、全局导航一致性与移动端菜单交互几个角度复核太初命理网站，新增以下不重复问题：

### 🔴 高优先级（功能性问题）

### 🟡 中优先级（体验问题）

### 🟢 低优先级（美观问题）

---

## 🎨 第三十六轮UI设计检查报告 (2026-03-17)


作为资深产品经理和UI设计师，我继续从首页信息表达、共享组件对比度、交互可发现性、移动端引导区密度与动效可访问性几个角度复核太初命理网站，新增以下不重复问题：

### 🔴 高优先级（功能性问题）

### 🟡 中优先级（体验问题）

### 🟢 低优先级（美观问题）


---

## 🎨 第三十五轮UI设计检查报告 (2026-03-17)

作为资深产品经理和UI设计师，我继续对太初命理网站做代码级界面审查，重点复核历史记录链路、结果页信息表达、分享流程以及页面样式隔离情况，新增以下不重复问题：

### 🔴 高优先级（功能性问题）

### 🟡 中优先级（体验问题）



作为代码审查专家，我对太初命理网站的代码进行了全面检查，发现以下问题：


### 🔴 高优先级（功能性问题）

- [x] [代码] 微信退款接口未实现 - backend/app/service/AdminStatsService.php - 已补齐充值订单真实微信退款链路，新增退款审计字段与后台退款明细返回。
- [x] [代码] 第三方推送服务未实现 - backend/app/controller/Notification.php - 已开放通知列表/设置/设备注册/测试推送接口，并接入充值成功通知触发链路。

### 🟢 低优先级（代码优化）

- [x] [代码] 未使用的导入检查 - 已清理 `frontend/src/views/Tarot.vue` 的未使用辅助逻辑，并统一塔罗/六爻页面的重复错误处理分支。
- [x] [代码] 后端日志优化 - 已为 `Notification.php`、`AdminStatsService.php`、`admin/Shensha.php` 补充结构化日志，关键信息已做脱敏。


### 📝 代码质量观察

前端代码整体质量较好：
- Vue组件结构清晰，错误处理完善
- API请求封装良好，有统一的重试机制
- 路由配置完整，权限控制合理

后端PHP代码整体质量较好：
- 使用ThinkPHP框架，遵循MVC架构
- 数据库操作使用查询构建器，防止SQL注入
- 中间件实现正确，JWT认证流程完善

---



## 🔮 第十七轮命理算法与功能深度检查报告 (2026-03-17)

作为精通东西方命理占卜的资深爱好者，我对核心占卜逻辑进行了审计，发现以下严重偏离传统理论的算法错误：

### 🟢 低优先级（专业性优化）
- [x] [占卜] 起运年龄精度优化 - 八字排盘 - 已优化为精确到\"X岁Y月Z天\"。

---

## 🎨 第三十三轮UI设计检查报告 (2026-03-17)


### 🔴 高优先级（运营阻塞问题）

### 🟡 中优先级（运营体验问题）
- [x] [运营] 独立知识库/文章管理系统缺失 - 内容管理 - 已在 `backend/route/admin.php` 接入 `admin.Knowledge/*` 独立文章/分类接口，并把 `database/20260317_create_knowledge_tables.sql` 纳入容器初始化与手工导库流程，后台可基于 `tc_article` / `tc_article_category` 发布维护深度文章。


### 🟢 低优先级（运营优化建议）
- [x] [运营] Dashboard 增加手动刷新与统计数据导出 - 运营概览 - 已补齐 `/api/admin/dashboard/refresh-stats` 刷新入口与后台导出按钮，支持手动重算当日统计并下载实时快照 CSV。

## 🎨 第三十四轮UI设计检查报告 (2026-03-17)

作为资深产品经理和UI设计师，我对太初命理网站进行了全面的设计检查。根据我的专业评估，发现以下设计优化点：


### 🟢 低优先级（美观问题）




## 🔍 代码逻辑检查报告 (2026-03-17)

作为代码审查专家，我对太初命理网站的代码进行了全面检查，发现以下问题：

### 🔴 高优先级

### 🟡 中优先级
- [x] [2026-03-17] 六爻控制器缺少API方法 - `backend/app/controller/Liuyao.php` - 本轮复核确认 `getPricing()` 与 `divination()` 已存在且路由已对齐，同时把旧 `qiGua()` 入口改为统一业务/系统异常收口与脱敏日志输出，核销历史误报。

### 🟢 低优先级



作为网站运营人员，我对太初命理网站后台管理系统进行了全流程检查，发现以下影响业务运营的问题：

### 🟡 中优先级（运营体验问题）

- [x] [运营] 列表批量处理能力缺失 - 用户管理/订单管理 - 已补齐用户批量状态路由、充值订单批量状态修改与订单导出接口。
- [x] [运营] 知识库分类联动不顺畅 - 内容管理 - 已返回分类树、文章计数、选中路径，并补齐父子分类与文章分类写入校验。

### 🟢 低优先级（运营优化建议）

- [x] [运营] Dashboard 实时数据导出 - 首页看板 - 已补齐后台 dashboard/export-realtime 路由，支持实时快照 CSV 导出。
- [x] [运营] 控制器代码架构优化 - 后端 Admin.php - 本轮继续将反馈管理与敏感词管理迁移到 `backend/app/controller/admin/{Feedback,SensitiveWord}.php`，并同步把 Dashboard 待处理反馈摘要路由切到独立控制器，后台历史大控制器进一步瘦身。



---

## 🔍 第二轮代码逻辑检查报告 (2026-03-17)

作为代码审查专家，我对太初命理网站的代码进行了全面检查，发现以下问题：

### 🔴 高优先级（功能性问题）

- [x] [代码] 第三方推送服务未实际集成 - backend/app/service/PushService.php - 已补齐通知/设备/设置三张表 SQL，并增强推送 provider 归一化，JPush/FCM/Webhook 可按配置直接落地。

### 🟡 中优先级（待确认已修复）

**以下问题在代码中已找到实现，需验证功能是否正常工作：**
- [x] [代码] 神煞管理API - 已补齐 `app/controller/admin/Shensha.php` 的 `options` 接口，并统一保存/状态更新异常处理与日志输出。
- [x] [代码] 微信退款接口 - 已复核 `AdminStatsService.php` 退款链路，确认调用 `WechatPayService::refund`，并补充后台退款脱敏日志。



### 🟢 低优先级（代码质量观察）

前端代码整体质量良好：
- Vue组件结构清晰，错误处理完善
- API请求封装良好，有统一的请求/响应拦截器
- 路由配置完整，权限控制通过meta字段实现
- 管理后台(admin)路由使用动态权限角色控制

后端PHP代码整体质量良好：
- 使用ThinkPHP框架，遵循MVC架构
- 数据库操作使用查询构建器，防止SQL注入
- 中间件实现正确，JWT认证流程完善
- API返回格式统一使用 `$this->success()` 和 `$this->error()`


## 🔮 占卜爱好者深度体验检查报告 - 第十八轮 (2026-03-17)

作为精通东西方命理占卜的资深爱好者，我对太初命理网站进行了全线功能的深度体验，发现以下严重偏离传统理论或影响准确性的问题：

### 🔴 高优先级（逻辑错误/准确性问题）

### 🟡 中优先级（体验问题/专业深度）

### 🟢 低优先级（专业性优化）




---

## 🔍 第三十七轮代码逻辑检查报告 (2026-03-17)

作为代码审查专家，我对太初命理网站的代码进行了全面检查，发现以下问题：

### 🔴 高优先级

### 🟡 中优先级
- [x] [2026-03-17] 后端控制器异常处理不统一 - backend/app/controller - 已在 `backend/app/BaseController.php` 增加统一业务/系统异常响应与脱敏日志 helper，并将 `admin/System.php`、`admin/Shensha.php` 接入统一处理，去掉直接回传原始异常的分支。


### 🟢 低优先级
- [x] [2026-03-17] 独立后台 API 前缀重复拼接清理 - `admin/src/api/{siteContent,sms,ai,aiPrompt}.js` - 已统一改为相对 `/api/admin` 的实际后端路径，并对齐 SEO/Robots/Sitemap 提交接口。
- [x] [2026-03-17] 短信测试模式日志明文暴露 - `backend/app/service/SmsService.php` - 已改为结构化脱敏日志，不再输出手机号与测试验证码明文。
- [x] [2026-03-17] 后端局部异常回传未脱敏 - `backend/app/controller/admin/Dashboard.php`、`backend/app/controller/SiteContent.php` - 已补结构化日志并改为通用错误文案回前端。
- [x] [2026-03-17] 后端Auth控制器表名硬编码 - `backend/app/controller/admin/Auth.php` - 已改为自动探测 `tc_admin/admin` 表，并在 `backend/app/controller/Admin.php` 的管理员列表接口同步补齐同类兼容逻辑，避免登录修好了但后台管理员列表继续炸。



---

## 🔍 第三十六轮代码逻辑检查报告 (2026-03-17)

作为代码审查专家，我对太初命理网站的代码进行了全面检查，发现以下问题：

### 🔴 高优先级
- [x] [2026-03-17] 前端管理后台路由未配置 - `frontend/src/router/index.js` - 本轮复核确认管理后台路由已注册到 `routes`，`/admin/config`、`/admin/almanac`、`/admin/knowledge`、`/admin/seo`、`/admin/shensha` 等页面均已有实际映射。
- [x] [2026-03-17] 管理端响应码判断不一致 - `frontend/src/views/admin/Config.vue` - 本轮复核确认配置页已统一按 `res.code === 200` 处理成功响应，历史误报已核销。


### 🟡 中优先级
- [x] [2026-03-17] 后端异常信息泄露风险 - backend/app/controller/Admin.php 第678行等 - 本轮复核确认 `Admin.php` 直接回传 `$e->getMessage()` 的分支已收口，同时 SEO 相关接口已迁出主控制器。


### 🟢 低优先级
- [x] [2026-03-17] 后端Admin控制器代码量过大 - backend/app/controller/Admin.php - 本轮已继续迁出反馈与敏感词模块，并让相关路由直接落到 `admin/Feedback`、`admin/SensitiveWord`；同时顺手清理 `exportUsers()` 的重复筛选分支和非标准导出日志动作。


---

## 🔮 占卜爱好者深度体验检查报告 - 第二十轮 (2026-03-17)

作为精通东西方命理占卜的资深爱好者，我对太初命理网站进行了新一轮实测与代码交叉核验，新增以下不重复问题：

### 🔴 高优先级（逻辑错误/准确性问题）


### 🟡 中优先级（体验问题）



### 🟢 低优先级（专业性优化）

---

## 🔮 占卜爱好者深度体验检查报告 - 第二十一轮 (2026-03-17)

作为精通东西方命理占卜的资深爱好者，我继续结合真实页面操作、接口探针与传统理论交叉核验，新增以下不重复问题：

### 🔴 高优先级（逻辑错误/准确性问题）


### 🟡 中优先级（体验问题）


### 🟢 低优先级（专业性优化）

---

## 🔮 占卜爱好者深度体验检查报告 - 第二十二轮 (2026-03-17)

作为精通东西方命理占卜的资深爱好者，我继续结合真实页面操作、接口实测、代码交叉核验与黄历对照，新增以下不重复问题：

### 🔴 高优先级（逻辑错误/准确性问题）



### 🟡 中优先级（体验问题）

### 🟢 低优先级（专业性优化）

---

## 🎨 第三十九轮UI设计检查报告 (2026-03-17)

作为资深产品经理和UI设计师，我继续从首页登录态可信度、八字结果归属、塔罗积分首屏反馈、合婚输入门槛与每日运势错误承接几个角度复核太初命理网站，确认以下问题尚未在当前 `TODO.md` 中登记后，新增如下：

### 🔴 高优先级（功能性问题）


### 🟡 中优先级（体验问题）


### 🟢 低优先级（美观问题）

---

## 🔮 占卜爱好者深度体验检查报告 - 第二十三轮 (2026-03-17)

作为精通东西方命理占卜的资深爱好者，我继续结合真实页面体验、接口实测、代码交叉核验与黄历外部对照，确认以下问题尚未在当前 `TODO.md` 中登记后，新增如下：

### 🔴 高优先级（逻辑错误/准确性问题）
- [x] [占卜] 手机验证码登录在测试模式下仍直接 500，导致八字/塔罗/六爻/合婚等需登录功能无法从真实用户入口进入 - 登录入口 / 占卜功能访问 - 已在 `backend/app/service/SmsService.php` 补回 `think\facade\Log` 导入，测试验证码分支继续沿用脱敏手机号日志，不再把 `Log` 解析成 `app\service\Log` 而直接 500。
- [x] [占卜] 合婚定价与计算链路都会因积分配置取值为空而报 500 - 合婚配对 - 已在 `backend/app/controller/Hehun.php` 为基础积分/导出积分增加显式数值兜底与 warning 日志，并修正 `backend/database/migrations/20250316_add_hehun_config.sql` 的 `system_config` 表写入目标；同时补充 `database/20260318_fix_hehun_points_config.sql` 便于缺配置环境直接回填默认值。

- [x] [占卜] 塔罗页首屏把当前积分错误显示为 0，并直接拦截抽牌 - 塔罗牌占卜 - 已在 `frontend/src/views/Tarot.vue` 补齐 `pointsLoading/pointsError/flowError` 状态、积分加载展示与 `points-updated` 刷新监听，首屏不再用默认 0 误判积分不足，抽牌/解读失败时也可直接重试。

### 🟡 中优先级（体验问题）
- [x] [占卜] 每日运势页签到卡片仍依赖不存在的 `checkin_record` 表，首屏长期显示“签到暂不可用” - 每日运势 - 已在 `backend/app/controller/Daily.php` 增加 `checkin_record / tc_checkin_record` 兼容探测，并按 `date/checkin_date`、`consecutive_days/continuous_days` 自动适配查询与写入，签到卡片可复用现有库表恢复显示。

### 🟢 低优先级（专业性优化）



---

## 🎨 第四十轮UI设计检查报告 (2026-03-17)

作为资深产品经理和UI设计师，我继续从首页功能入口清晰度、价格承诺可信度、结果页操作语义、历史记录可辨识性与空状态完整性几个角度复核太初命理网站，确认以下问题尚未在当前 `TODO.md` 与自动化执行记录中重复登记后，新增如下：

### 🔴 高优先级（功能性问题）

### 🟡 中优先级（体验问题）


### 🟢 低优先级（美观问题）

---

## 🎨 第四十一次UI设计检查报告 (2026-03-18)

作为资深产品经理和UI设计师，我继续从首页首屏干扰、入口信息架构、付费信任感与关键交互语义几个角度复核太初命理网站，确认以下问题尚未在当前 `TODO.md` 与自动化执行记录中重复登记后，新增如下：

### 🔴 高优先级（功能性问题）

### 🟡 中优先级（体验问题）

---

## 🎨 第四十二次UI设计检查报告 (2026-03-18)

作为资深产品经理和UI设计师，我继续从异常承接、结果页恢复链路、全局悬浮控件与首页文案可信度几个角度复核太初命理网站，确认以下问题尚未在当前 `TODO.md` 与自动化执行记录中重复登记后，新增如下：

### 🔴 高优先级（功能性问题）

### 🟡 中优先级（体验问题）


### 🟢 低优先级（美观问题）


---

## 🔮 占卜爱好者深度体验检查报告 - 第二十四轮 (2026-03-18)

作为精通东西方命理占卜的资深爱好者，我继续结合真实页面体验、接口审计结果与代码交叉核验，确认以下问题尚未在当前 `TODO.md` 与自动化执行记录中重复登记后，新增如下：

### 🔴 高优先级（逻辑错误/准确性问题）
- [ ] [占卜] 六爻当前运行环境的 `pricing` 和 `divination` 接口都返回“方法不存在”，时间起卦、手动摇卦都无法真正落盘解卦 - 六爻占卜 - `/api/liuyao/pricing` 命中 `app\controller\Liuyao->getPricing()` 不存在，`/api/liuyao/divination` 命中 `app\controller\Liuyao->divination()` 不存在，导致主卦、变卦、动爻、六亲、六神与解卦链路整体失效。

### 🟡 中优先级（体验问题）

### 🟢 低优先级（专业性优化）
- [ ] [占卜] 塔罗解读仍有明显模板拼接痕迹，重复句号、固定“元素互动”段和牌位套话会削弱韦特体系的专业感 - 塔罗牌占卜 - 建议按单张、三张、凯尔特十字分别重写牌位关系逻辑，减少机械复述牌义，补足逆位心理阻抗与牌阵位置之间的因果衔接。

---

## 🔮 占卜爱好者深度体验检查报告 - 第二十五轮 (2026-03-18)

作为精通东西方命理占卜的资深爱好者，我继续结合真实页面操作、运行态接口复测、容器日志与传统命理规则交叉核验，确认以下问题尚未在当前 `TODO.md` 与自动化执行记录中重复登记后，新增如下：

### 🔴 高优先级（逻辑错误/准确性问题）
- [ ] [占卜] 排盘后的流年分析、大运分析和运势图接口会整体 500，深度命盘链路在真实环境不可用 - 八字排盘 - `/api/fortune/yearly` 与 `/api/fortune/dayun-analysis` 在 `Fortune.php` 里直接实例化 `Paipan` 时缺少构造参数，`/api/fortune/yearly` 还额外触发 `YearlyFortuneService.php` 误写 `app\service\Db`，导致大运流年、走势评分与年份图全部无法验证。
- [ ] [占卜] 六爻当前不再是“方法不存在”，而是控制器硬编码 `tc_liuyao_record` 与库中真实表 `liuyao_records` / `liuyao_gua` 不一致，定价、时间起卦、手动摇卦都会直接 500 - 六爻占卜 - `getPricing()` / `divination()` / `history()` 都依赖不存在的表名，结果是主卦、变卦、动爻、六亲、六神和解卦分析整条链路仍然无法体验。
- [ ] [占卜] 每日运势前端路由声明为公开可浏览，但后端 `Daily` 控制器全局挂了鉴权中间件，匿名访问 `/daily` 会因为 `/api/daily/fortune` 返回 401 被前端拦截器直接踢回首页 - 每日运势 - 页面承诺“可直接浏览”与接口权限设计相互冲突，游客实际上根本看不到今日运势、黄历宜忌和时辰吉凶。
- [ ] [占卜] 本地测试模式下“获取验证码”仍返回“短信服务未配置”，不知道隐藏测试码的用户无法从真实页面入口进入八字、塔罗、六爻和合婚 - 登录入口 / 受限占卜功能访问 - `SmsService::sendVerifyCode()` 在 `SMS_TEST_MODE=true` 时仍先强依赖腾讯云短信配置，没有像验证码校验那样走本地测试短路。

### 🟡 中优先级（体验问题）

### 🟢 低优先级（专业性优化）
- [ ] [占卜] 八字基础解读里“喜用神”文案与接口返回字段前后不一致，专业用户会被用神判断绕晕 - 八字排盘 - 同一命盘的 `favorite_wuxing` 返回为“火、木、水”，正文却只写“宜取木与水来疏泄制衡”而漏掉火，喜用神说明缺少统一口径。




