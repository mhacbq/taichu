# 后端修复专家 - 执行记录

## 2026-03-18 12:30 用户详情 / 调积分 / 黄历 / 神煞 / SEO 修复记录（本次）

- 本轮直接处理 `TODO.md` 顶部最新 5 个后台运营阻塞项：用户详情用户名仍回填手机号、手动调积分业务码 500、黄历首屏列表失败、神煞新增失败、SEO 配置新增失败。
- 关键代码：`backend/app/controller/admin/User.php` 统一详情 `username` / `nickname` 口径，并同步清洗嵌套 `user` 对象；`backend/app/service/AdminStatsService.php` 修复管理员日志 `params` JSON 兼容写入，并让日志失败不再回滚积分事务；`backend/app/controller/admin/Almanac.php` 改为用 `date('t')` 计算月底，移除 `calendar` 扩展依赖；`backend/app/controller/admin/Shensha.php` 兼容 PUT 请求体并为缺失的 `check_rule` / `check_code` 提供默认值；`backend/app/controller/admin/Seo.php` 优先落到 `tc_seo_*` 标准表，并按实际 schema 动态组装保存 payload。
- 验证：已对上述 5 个 PHP 文件执行 IDE 诊断检查（均 0 条）；`git diff --check -- backend/app/controller/admin/User.php backend/app/service/AdminStatsService.php backend/app/controller/admin/Almanac.php backend/app/controller/admin/Shensha.php backend/app/controller/admin/Seo.php` 通过；`docker exec taichu-app php -l` 对 5 个目标文件均返回语法通过。
- TODO：已把本轮 5 个运营问题回写为完成状态；Git 提交待本轮收尾统一执行。

## 2026-03-18 03:45 支付统计 / 退款兼容修复记录（本次）


- 本轮继续围绕后台支付主链路收口 5 类后端问题：充值统计表名硬编码、趋势接口表名硬编码、订单状态筛选固定 schema、退款流程写死订单/用户表、退款积分流水写死 `tc_points_record` 且字段兼容性不足。
- 关键代码：`backend/app/controller/admin/Payment.php` 新增充值订单/用户/积分记录动态表解析；`getStats()` 与 `getTrend()` 缺表降级；`performRefund()` 兼容 `status/pay_status` 与退款字段差异，并补齐退款积分流水兼容写入。
- 验证：已对 `Payment.php` 执行 IDE 诊断检查（0 条），`git diff --check -- backend/app/controller/admin/Payment.php` 通过；当前环境仍无 `php` CLI，未执行 `php -l`。
- Git：已提交 `8d1ecac`，提交信息为 `"fix-backend-payment-refund-compat-20260318"`；但该提交额外带入了提交前已在暂存区中的无关文档文件，尝试清理时命令被环境拒绝，尚未自动修正。

## 2026-03-18 03:20 知识库 / SEO / VIP 套餐兼容修复记录（本次）


- 核对 `TODO.md` 后，当前已无未完成的 `[代码]` / `[运营]` 后端条目；本轮转为修补与近期运营问题强相关的 5 类后台兼容缺口。
- 关键代码：`backend/app/controller/admin/Knowledge.php` 增加知识库文章/分类动态表解析并修复文章更新对 PUT 请求体读取失败；`backend/app/controller/admin/Seo.php` 让 SEO 统计接口统一走 `resolveSeoTable()` 并在缺表时降级；`backend/app/controller/admin/Order.php` 修复 VIP 套餐保存只支持 POST、写死 `tc_vip_package`、`features` 入参不稳的问题。
- 验证：已对 `Knowledge.php`、`Seo.php`、`Order.php`、`Payment.php` 执行 IDE 诊断检查，均为 0 条；`git diff --check`（目标文件）通过；当前环境仍无 `php` CLI，未执行 `php -l`。
- Git：已提交 `f8c72c2`，提交信息为 `"fix-backend-knowledge-seo-vip-compat-20260318"`。

## 2026-03-18 02:10 Dashboard / 用户 / 充值统计兼容修复记录（本次）


- 本轮聚焦 5 个后台运营主链路问题：Dashboard statistics 500、Dashboard trend 500、用户列表加载失败、用户详情加载失败、充值统计 500。
- 关键代码：`backend/app/service/AdminStatsService.php` 改为用 schema 探测 + 实时快照降级处理 Dashboard 与用户列表，兼容 `tc_user_vip/user_vip`、`status/pay_status`、缺失命理记录表；`backend/app/controller/admin/User.php` 为详情链路补齐缺表降级；`backend/app/controller/admin/Payment.php` 改为兼容旧版充值订单状态字段并保留正确的去重用户统计。
- 验证：已对 `AdminStatsService.php`、`admin/User.php`、`admin/Payment.php` 执行 IDE 诊断检查，结果均为 0 条；`git diff --check -- backend/app/service/AdminStatsService.php backend/app/controller/admin/User.php backend/app/controller/admin/Payment.php` 通过；当前环境仍无 `php` CLI，无法执行 `php -l`。
- TODO：已把 Dashboard、用户、充值统计三条运营待办回写为完成状态；Git 提交尚未执行。

## 2026-03-18 后台运营只读保护与知识库入口修复记录（本次）


- 完成 5 个后台 / 运营类问题：补齐独立后台知识库文章/分类管理入口；为 Dashboard、用户列表、黄历/神煞、充值订单、VIP 订单页统一增加显式错误态、重试入口与只读保护；神煞后台补 `AdminAuth`、`content_manage` 权限、`status` 筛选与局部更新兼容；相关 admin API 封装支持 `showErrorMessage: false` 由页面接管错误态。
- 关键代码：新增 `admin/src/api/knowledge.js`、`admin/src/views/site-content/knowledge.vue`；更新 `admin/src/router/index.js`、`admin/src/views/dashboard/index.vue`、`admin/src/views/payment/{orders,vip-orders}.vue`、`admin/src/views/user/list.vue`、`admin/src/views/content/{almanac,shensha}.vue`、`backend/app/controller/admin/Shensha.php`、`TODO.md`。
- 验证：已对本轮目标前端 / PHP 文件执行 IDE 诊断检查（0 条），执行 `git diff --check` 通过，并完成 `npm --prefix admin run build` 构建验证；仍只有既有 Sass legacy JS API 与大 chunk 警告，未阻塞构建。
- Git：本轮提交信息使用 `fix-backend-admin-ops-readonly-20260318-0115`。

## 2026-03-18 00:30 后台鉴权与系统权限边界修复记录（本次）


- 完成 5 个后台 / 运营类问题：移除后台 JWT 固定默认密钥回退、补齐管理员 Token 声明与启用状态校验、为 `auth/info` 返回真实角色/权限/状态、收紧系统角色/权限/字典接口的 `config_manage` 权限边界、统一以 `SchemaInspector` 替代 MySQL `SHOW TABLES / SHOW COLUMNS` 探测。
- 关键代码：新增 `backend/app/service/SchemaInspector.php`；更新 `backend/app/controller/admin/Auth.php`、`backend/app/middleware/AdminAuth.php`、`backend/app/service/AdminAuthService.php`、`backend/app/service/AdminStatsService.php`、`backend/app/controller/admin/System.php`，并已在 `TODO.md` 回写第二十四轮完成项。
- 验证：已对 `backend/app/controller/admin/{Auth,System}.php`、`backend/app/middleware/AdminAuth.php`、`backend/app/service/{AdminAuthService,AdminStatsService,SchemaInspector}.php` 执行 IDE 诊断检查，结果均为 0 条；待提交前继续执行 `git diff --check` 做补丁格式校验。
- Git：本轮提交信息使用 `fix-backend-admin-auth-hardening-20260318-0030`。

## 2026-03-17 23:45 管理后台角色/日志/风控修复记录（本次）


- 完成 5 个后台 / 运营类问题：统一独立后台代理端口与文档口径、修复侧边栏按角色过滤与路由守卫、补齐管理员保存/删除与积分统计接口、补齐风控规则更新与日志清理/导出接口、把统计表与反作弊表纳入 fresh setup 初始化链路。
- 关键代码：`backend/app/controller/Admin.php` 新增 `saveAdminUser()` / `deleteAdminUser()` / `pointsStats()` / `updateRiskRule()` / `clearLogs()` / `exportLogs()`；`admin/src/views/system/admin.vue`、`admin/src/views/anticheat/rules.vue`、`admin/src/views/log/operation.vue` 已接回真实接口；新增 SQL：`database/20260317_create_admin_stats_tables.sql`、`database/20260317_create_anticheat_tables.sql`。
- 验证：已对本轮改动文件执行 IDE 诊断检查（0 条），执行 `git diff --check` 通过，并完成 `npm --prefix admin run build` 构建验证；仅保留 Sass legacy JS API 与大 chunk 的现有警告，未阻塞构建。
- Git：本轮提交信息使用 `fix-backend-admin-ops-20260317-2345`。



## 2026-03-17 22:15 初始化与审计修复记录（本次）

- 完成 5 个后端 / 运营类问题：管理员 bootstrap SQL 纳入容器与手工初始化流程、Dashboard 权限检查兼容、神煞表及种子脚本补齐并接入初始化、后台操作日志字段兼容写入、知识库文章表初始化流程补齐。
- 新增 SQL：`database/20260317_create_shensha_table.sql`；并更新 `backend/docker-compose.yml`、`database/backup/README.md`、`backend/app/BaseController.php`、`backend/app/middleware/AdminAuth.php`、`backend/app/service/AdminStatsService.php`、`TODO.md`。
- 验证：已对本轮 PHP 文件执行 IDE 诊断检查（0 条），并通过 `git diff --cached --check`。
- Git：已提交 `e62d6aa`，提交信息为 `"fix-backend-init-and-admin-log-20260317-2215"`。

## 2026-03-17 管理后台兼容修复记录（本次）

### 本次完成的后端问题
1. **管理员登录与后台管理员列表表名兼容**
   - `backend/app/controller/admin/Auth.php` 已兼容 `tc_admin/admin`，并统一回退 `ADMIN_JWT_SECRET/JWT_SECRET`。
   - `backend/app/controller/Admin.php` 的 `getAdminUsers` 也补齐了同类兼容，避免登录修好后管理员列表继续依赖硬编码 `admin` 表。
2. **后台鉴权兜底修复**
   - `backend/app/middleware/AdminAuth.php` 不再因缺少 `ADMIN_JWT_SECRET` 在构造阶段直接抛异常，改为回退到 `JWT_SECRET`，最后再回退开发默认值并记录 warning。
3. **Dashboard / 用户详情 / 用户列表兼容层落地**
   - `backend/app/service/AdminStatsService.php` 补齐 `statistics/user_trend/bazi_trend/tarot_trend` 与用户列表参数别名兼容。
   - `backend/app/controller/admin/User.php` 同时兼容平铺详情结构与 `points` / `type+amount` 两套积分调整入参。
4. **黄历 REST 路由与表结构兼容**
   - `backend/route/admin.php` 已补齐 `/api/admin/content/almanac*` 路由。
   - `backend/app/controller/Admin.php` 已兼容 `tc_almanac/almanac` 表名、CRUD 与月度生成逻辑。
5. **管理员初始化 SQL 补齐**
   - 新增 `database/20260317_create_admin_users_table.sql`，创建 `tc_admin` 主表，并补默认账号 `admin / admin123`、超级管理员角色与角色绑定。

### 验证摘要
- 已对本轮关键 PHP / 路由 / TODO 文件执行 IDE 诊断检查：0 条问题。
- 已执行 `git diff --check`（本轮目标文件）：通过。
- 当前环境未提供 `php` CLI，无法执行 `php -l`。

### Git 提交
- 提交 ID：`060061d`
- 提交信息：`"fix-backend-admin-compat-20260317"`
- 说明：本次仅提交目标后端修复文件，未带入仓库中其他无关改动。

---

## 2026-03-17 19:22 执行记录（本次）


### 本次完成的5个后端问题
1. **用户积分手动调账闭环**
   - 补齐 `admin/User` 详情中的积分流水、VIP订单、充值订单与调账能力标记。
   - `AdminStatsService::adjustUserPoints` 改为事务化返回余额变化、流水ID，并在提交后触发积分变动通知。
2. **站点内容后台分页**
   - `SiteContent::getContentList` 新增 `current/pageSize` 分页参数与 `total` 返回，避免内容项增多时全量拉取。
3. **独立知识库后台落地**
   - 新增 `backend/app/controller/admin/Knowledge.php`，把知识库文章/分类接口从大控制器中独立出来。
   - 新增 `database/20260317_create_knowledge_tables.sql`，提供 `tc_article` / `tc_article_category` 表结构与默认分类种子。
4. **控制器模块化迁移推进**
   - `backend/route/admin.php` 已将知识库路由全部切换到 `admin.Knowledge/*`，减少 `Admin.php` 的职责扩散。
5. **推送集成可落库**
   - 新增 `database/20260317_create_notification_tables.sql` 与 `20260317_add_points_record_compat_fields.sql`，补齐通知/设备/设置表及积分流水兼容字段。
   - `PushService` 增加 provider 别名归一化，兼容 JPush/FCM/Webhook 配置写法。

### 验证摘要
- 已对本轮修改的 PHP/路由文件执行 IDE 诊断检查：0 条问题。
- 已执行 `git diff --check`（仅限本轮修改文件）：通过。
- 当前环境未找到可用 `php` CLI，暂未执行 `php -l`。

---



## 2026-03-17 20:15 收尾核对记录（本次）

### 本次核对结论
- 已重新核对本轮“5 个后端问题”相关代码、`TODO.md` 与 Git 历史，确认这批修复并非停留在未提交状态，而是已经进入仓库历史。
- 当前工作区未发现这些后端文件的待提交变更；目前仅存在与本轮任务无关的前端改动：`frontend/src/App.vue`、`frontend/src/styles/theme-white.scss`。

### 已确认落库的关键提交
1. **后端与路由主体修复**
   - 提交：`c30f2f7`（`报错修复`）
   - 包含文件：`backend/route/admin.php`、`backend/route/app.php`、`backend/app/controller/admin/Payment.php`、`backend/app/controller/Admin.php`、`backend/app/model/RechargeOrder.php`、`backend/.env.example` 等。
2. **退款数据库字段 SQL**
   - 提交：`402fd14`（`报错修复`）
   - 包含文件：`database/20260317_add_recharge_order_refund_fields.sql`。
3. **TODO 完成状态回写**
   - 提交：`ee8e9fa`（`chore-todo-clear-resolved-divination-items`）
   - 已确认 `TODO.md` 中以下条目为完成状态：
     - 微信退款接口未实现
     - 第三方推送服务未实现
     - 列表批量处理能力缺失
     - 知识库分类联动不顺畅
     - Dashboard 实时数据导出

### 本次未再执行的动作
- 未新增 Git 提交：原因是目标后端修复已存在于提交历史，继续提交只会制造空转。
- 未处理前端未提交改动：原因是它们与本轮后端任务无关，避免误带入提交。

---

## 2026-03-17 15:00 执行记录（本次）


### 本次完成的后端收尾
1. **支付管理接口闭环**
   - 文件：`backend/app/controller/AdminPayment.php`、`backend/route/admin.php`
   - 修复：补齐后台订单状态更新/退款接口，统一按路由参数读取订单标识，兼容 `id` 与 `order_no`，并增加退款事务、积分回退与日志记录。
2. **后台系统管理接口强化**
   - 文件：`backend/app/controller/admin/System.php`
   - 修复：统一改为 `Db::table('tc_*')` 显式表访问，补充角色、权限、字典的参数校验、唯一性校验、存在性校验，并在角色权限变更后清理管理员权限缓存。
3. **后台路由权限边界清理**
   - 文件：`backend/route/app.php`
   - 修复：移除重复的 `/api/admin` 路由组，避免普通用户鉴权中间件误暴露后台前缀。

### 验证结果
- 已对本轮重点文件执行 `read_lints`，结果为 0 条诊断。
- 已执行 `git diff --check`，当前 `AdminPayment.php`、`System.php`、`backend/route/admin.php`、`backend/route/app.php` 通过基础格式检查。
- 当前环境未提供 `php` 命令，暂未执行 `php -l` 语法检查。

### 提交状态
- 提交 ID：`965e024`
- 提交信息：`fix-backend-admin-payment-and-system-20260317-1500`
- 已推送到：origin/master

---

## 2026-03-17 17:00 执行记录（本次）

### 本次修复的5个后端问题

1. **后台 AI 路由权限边界错误**（安全）
   - 文件：`backend/route/ai.php`、`backend/route/aiprompt.php`
   - 问题：后台管理接口误用普通用户鉴权 `Auth::class`，未受 AdminAuth 保护
   - 修复：改为 `AdminAuth::class`，确保仅管理员可访问

2. **控制器通用能力缺失**（代码规范/可维护性）
   - 文件：`backend/app/BaseController.php`
   - 问题：
     - 分页校验在 `Admin.php` 等多处重复实现，不一致
     - 上传记录无法正确记录当前管理员ID（`$request->adminId` 不存在）
   - 修复：
     - 新增 `normalizePagination`/`getPaginationParams` 统一分页处理
     - 新增 `getOperatorId` 统一操作者ID读取逻辑（优先管理员）

3. **AI 解盘异常直接暴露给前端**（安全）
   - 文件：`backend/app/controller/AiAnalysis.php`
   - 问题：多处异常把 `$e->getMessage()` 直接返回给客户端，可能泄露内部信息
   - 修复：使用 `Log::error` 记录详细日志，返回通用提示信息

4. **SSE 流式解盘完善**（健壮性）
   - 文件：`backend/app/controller/AiAnalysis.php`
   - 问题：
     - 响应头与结束标记不规范
     - 缺少连接断连/超时处理
     - 兼容旧版路由（`analyze`/`analyzeStream`）缺失
   - 修复：
     - 统一 `prepareSseResponse`、`emitSseDone`、`emitSseError` 方法
     - 增加编码校验、`CURLOPT_CONNECTTIMEOUT`、`connection_aborted()` 检查
     - 补齐 `analyze`/`analyzeStream`/`history` 方法以兼容前端路由

5. **多处分页与异常处理不统一**（代码规范）
   - 文件：`backend/app/controller/Admin.php`、`Upload.php`、`Liuyao.php`、`AiPrompt.php`
   - 问题：
     - 多个控制器重复手写分页校验，未统一上限与边界
     - 异常消息直接回传（`Upload`、`Liuyao`）
     - 日志使用不统一（`Upload` 中混用 `trace`）
   - 修复：
     - 将重复分页逻辑迁移至 `BaseController::normalizePagination/getPaginationParams`
     - 统一使用 `Log::error` 并返回通用错误提示
     - 对 `Upload`、`Liuyao` 的记录/详情接口补充 404 状态码

### 兼容与补充
- 补齐 `AiPrompt` 控制器缺失的 `getDefaultPrompt` 接口（供前台获取默认提示词）
- 修复 `AiPrompt` 控制器参数校验（类型有效性、JSON 字段解析、启用状态、操作者ID）

### Git 提交
- 提交 ID：`2d60dee`
- 提交信息：`fix-backend-standalone-and-api-20260317-1700`
- 已推送到：origin/master

---

## 2026-03-17 16:45 执行记录

### 修复与清理操作
1. **Admin.php 逻辑修正**
   - 修正了 `adminId` 和 `adminName` 从 JWT payload 中获取的字段名（改为 `id` 和 `username`），使其与中间件保持一致。
   - 替换了分页大小的最后残余硬编码 `20` 为 `self::DEFAULT_PAGE_SIZE`。
2. **TODO.md 深度清理**
   - 删除了大量实际上已完成或重复的待办项，包括：
     - 各个控制器的 API 返回格式统一问题（已全部改为 success/error 方法）。
     - `Auth.php` 和 `Admin.php` 中的魔法数字问题（如密码长度、分页大小）。
     - `SiteContent.php` 中的参数验证缺失问题（page 字段正则表达式验证）。
     - `Home.vue` 和 `Bazi.vue` 中的未使用导入和变量问题（User/UserFilled 图标、_index 参数）。
3. **Git 提交**
   - 提交 ID: `e264e74`
   - 已推送至远程仓库。

---

## 2026-03-17 17:15 执行记录（本次）

### 本次修复的5个后端问题

1. **积分规则接口缺失**（API/功能）
   - 文件：`backend/app/controller/Admin.php`
   - 问题：`/api/admin/points/rules` 的读取与保存接口未实现，后台积分规则页无法对接真实后端
   - 修复：新增 `getPointsRules`、`savePointsRules`，基于 `system_config` 的 `points` / `points_cost` 分类提供统一读写能力

2. **敏感词管理接口缺失**（API/安全）
   - 文件：`backend/app/controller/Admin.php`
   - 问题：敏感词列表、增删改、批量导入接口均缺失，后台无法维护内容风控词库
   - 修复：新增 `getSensitiveWords`、`addSensitiveWord`、`updateSensitiveWord`、`deleteSensitiveWord`、`importSensitiveWords`，统一存储到 `system_config` 的 `sensitive_words` 分类，并增加去重校验

3. **敏感词更新路由缺失**（API规范）
   - 文件：`backend/route/admin.php`
   - 问题：后台前端使用 `PUT /system/sensitive/:id`，但后端路由未定义
   - 修复：补充 `Route::put('system/sensitive/:id', 'Admin/updateSensitiveWord')`

4. **系统公告接口缺失**（API/功能）
   - 文件：`backend/app/controller/Admin.php`
   - 问题：`getNotices`、`saveNotice`、`deleteNotice` 未实现，后台公告管理页无真实数据来源
   - 修复：新增公告列表/保存/删除接口，使用 `system_config` 的 `system_notice` 分类存储，并修正状态筛选后的分页统计

5. **管理员列表接口与权限边界问题**（功能/安全）
   - 文件：`backend/app/controller/Admin.php`、`backend/route/sitecontent.php`
   - 问题：
     - `getAdminUsers` 未实现，后台管理员管理页无法读取数据
     - `sitecontent.php` 后台管理路由使用普通 `Auth` 中间件，权限边界不一致
   - 修复：
     - 新增 `getAdminUsers`，支持角色关联查询与分页返回
     - 将站点内容后台路由统一为 `AdminAuth` 中间件

### TODO 清理
- 将 `Auth.php` 重复导入问题标记为已完成（代码中已无重复导入）
- 将站点内容管理鉴权不一致标记为已完成
- 在运营检查报告中标注：已补齐积分规则、敏感词、系统公告、管理员列表等关键接口，保留剩余未完成项

### Git 提交
- 提交 ID：`d4ce6a3`
- 提交信息：`fix-backend-admin-config-20260317-1715`
- 已推送到：origin/master

---

## 2026-03-17 15:00 执行记录（本次）

### 本次处理概览
- 修复了 5 类后端问题：认证接口兼容、用户中心鉴权与参数校验、支付异常脱敏、签到幂等与空值兜底、站点内容写入审计与异常处理。
- 补回 `backend/route/app.php` 中后台配置路由组，并统一改为 `AdminAuth` 保护；同时统一健康检查接口成功码为 `200`。
- `TODO.md` 当前未包含 `[后端]` / PHP 待办条目，因此本次未做后端条目删除，只基于实际代码审计完成修复。

### 验证结果
- 已对本轮修改文件执行 IDE 诊断检查，未发现新增 lint 问题。
- 尝试执行 PHP CLI 语法校验，但当前环境未提供 `php` 命令，命令行级语法校验被阻塞。

### Git 提交
- 提交 ID：`b99af5d`
- 提交信息：`"fix-backend-multiple-issues-20260317-1459"`
- 已推送到：origin/master

---

## 2026-03-17 15:32 执行记录（本次）

### 本次处理概览
- 修复后台权限与限流相关问题：`BaseController` 新增管理员 ID / 权限判断辅助方法，`AdminPayment`、`AdminSms` 补上读写权限门禁。
- 重写 `RateLimit` 路由匹配逻辑，修正旧配置与真实路由（如 `payment/create-order`、`ai/analyze(-stream)`、动态 `:id` 路由）不一致的问题。
- 为 `backend/route/admin.php`、`ai.php`、`aiprompt.php`、`upload.php`、`content.php`、`sitecontent.php` 补挂限流中间件，并把 `app.php` 的后台仪表盘兼容路由对齐到当前 `Admin` 控制器方法。
- 加固旧版 `backend/app/controller/admin/User.php`、`admin/Order.php`：补 `AdminAuth`、权限校验、操作者 ID 修正，以及异常脱敏日志。
- `TODO.md` 已将“后台 API 限流增强”条目标记完成。

### 验证结果
- 已对本轮修改文件执行 IDE 诊断检查，结果为 0 条新增问题。
- 已执行 `git diff --check`，本轮提交文件通过基础补丁格式检查。
- 本轮未执行 `php -l`，当前环境仍缺少可用的 `php` CLI。

### Git 提交
- 提交 ID：`98cb66f`
- 提交信息：`"fix-backend-multiple-issues-20260317-1532"`
- 已推送到：origin/master
- 说明：本次提交实际额外带入了 `backend/docker-compose.yml`、`backend/docker-entrypoint.sh` 的已暂存内容，不属于本轮核心修复；当前工作区中 `backend/docker-compose.yml` 仍有后续未提交改动。

---

