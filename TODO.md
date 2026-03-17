## 🛠 第二十二轮后台运营检查报告 (2026-03-17)

作为网站运营人员，我继续按真实运营路径巡检后台：先实测默认独立后台 `http://localhost:3001/login`，确认登录页可打开但提交会命中失效的 `8000` 端口；随后临时拉起直连 `8080` 的后台实例、注入管理员 token 进入 Dashboard，并结合受保护接口实测、运行中容器代码比对与错误日志交叉核验，新增以下不重复问题：

### 🔴 高优先级（运营阻塞问题）
- [ ] [运营] 当前运行中的后台容器仍在跑旧代码/旧初始化状态，仓库里已补的登录初始化、`checkPermission()` 兼容和神煞建表修复都没有在运行态生效，导致管理员登录继续报“管理员账号表不存在”，Dashboard 统计/趋势继续 500，神煞管理继续报错 - 管理员登录 / Dashboard / 内容管理 - 这不是单点功能问题，而是部署/重建链路没有把最新修复真正发布到当前后台，运营后台现状仍不可用。

### 🟡 中优先级（运营体验问题）
- [ ] [运营] 独立后台默认本地运行配置仍把 `/api` 代理到 `http://localhost:8000`，而当前标准本地后端健康地址是 `http://localhost:8080/api/health`，按文档直接打开 `http://localhost:3001/login` 提交登录会打到空端口卡死 - 管理员登录 / 本地联调入口 - 建议统一 `admin/vite.config.js`、启动脚本和本地文档里的后台 API 端口口径。
- [ ] [运营] 后台侧边栏没有按角色过滤菜单，`admin/src/layout/components/Sidebar/index.vue` 直接把 `asyncRoutes` 全量渲染，运营人员会看到“短信管理 / AI管理 / 系统设置 / 日志管理 / 任务调度”等无权限入口，点进去后才会被拦截 - 后台导航 / 权限体验 - 建议按路由 `meta.roles` 过滤侧边栏，避免误导运营人员进入不可用模块。

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
- [ ] [UI] 每日运势“幸运色/幸运方位”个性化区块使用了未落地的结构类名，视觉呈现容易像未完成态 - `frontend/src/views/Daily.vue` - 模板使用 `.personal-lucky-grid / .personal-lucky-item / .personal-lucky-label / .personal-lucky-values`，但样式表只在移动端零散覆写了前两个类，缺少基础栅格、卡片和文本层级定义，建议补齐完整样式并与“今日宜忌”做清晰视觉区分。

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
- [ ] [2026-03-17] 六爻控制器缺少API方法 - `backend/app/controller/Liuyao.php` - 路由定义了 `Liuyao/getPricing` 和 `Liuyao/divination`，但控制器中只有 `qiGua()` 方法，缺少对应的 `getPricing()` 和 `divination()` 方法，导致前端调用 `/api/liuyao/pricing` 和 `/api/liuyao/divination` 时返回404 - 建议在 Liuyao.php 中添加这两个方法，或者修改路由指向现有的 `qiGua()` 方法。

### 🟢 低优先级



作为网站运营人员，我对太初命理网站后台管理系统进行了全流程检查，发现以下影响业务运营的问题：

### 🟡 中优先级（运营体验问题）

- [x] [运营] 列表批量处理能力缺失 - 用户管理/订单管理 - 已补齐用户批量状态路由、充值订单批量状态修改与订单导出接口。
- [x] [运营] 知识库分类联动不顺畅 - 内容管理 - 已返回分类树、文章计数、选中路径，并补齐父子分类与文章分类写入校验。

### 🟢 低优先级（运营优化建议）

- [x] [运营] Dashboard 实时数据导出 - 首页看板 - 已补齐后台 dashboard/export-realtime 路由，支持实时快照 CSV 导出。
- [ ] [运营] 控制器代码架构优化 - 后端 Admin.php - 本轮已继续拆出公告、黄历、SEO 模块，但主控制器仍承载较多历史接口，后续仍需继续向模块化控制器（admin/目录）迁移。



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
- [ ] [2026-03-17] 后端Admin控制器代码量过大 - backend/app/controller/Admin.php - 本轮已迁出公告、黄历、SEO 模块并删除旧实现，但主控制器仍较大，建议继续向 admin/ 目录迁移剩余历史接口。


---

## 🔮 占卜爱好者深度体验检查报告 - 第二十轮 (2026-03-17)

作为精通东西方命理占卜的资深爱好者，我对太初命理网站进行了新一轮实测与代码交叉核验，新增以下不重复问题：

### 🔴 高优先级（逻辑错误/准确性问题）
- [ ] [占卜] 六爻接口路由与控制器方法完全失配 - 六爻占卜 - 前端调用 `/api/liuyao/pricing`、`/api/liuyao/divination`，后端实际只有 `qiGua/records/recordDetail` 等方法，实测返回“方法不存在: app\controller\Liuyao->getPricing()/divination()”，核心起卦链路不可用。


### 🟡 中优先级（体验问题）
- [ ] [占卜] 六爻前端缺少手动起卦与时间起卦入口 - 六爻占卜 - 页面只有问题输入框与 AI 勾选，未暴露手动摇卦、时间起卦、日辰信息等专业参数，无法满足六爻爱好者的标准问卦流程。
- [ ] [占卜] 每日运势缺少吉神凶煞与时辰吉凶信息 - 每日运势 - 当前只展示综合分、分项分数与宜忌标签，未提供黄历常用的吉神凶煞、值日神、时辰吉凶，专业参考价值偏弱。


### 🟢 低优先级（专业性优化）

---

## 🔮 占卜爱好者深度体验检查报告 - 第二十一轮 (2026-03-17)

作为精通东西方命理占卜的资深爱好者，我继续结合真实页面操作、接口探针与传统理论交叉核验，新增以下不重复问题：

### 🔴 高优先级（逻辑错误/准确性问题）
- [ ] [占卜] 专业版八字排盘对测试生辰直接 500 - 八字排盘 - 以 `1990-05-15 10:30 男` 调用 `/api/paipan/bazi` 实测返回 `Undefined array key "gan_index"`，四柱、十神、纳音、大运流年整页均无法生成，核心排盘链路被阻断。
- [ ] [占卜] 合婚定价接口空配置直传导致页面一打开就 500 - 合婚配对 - `/api/hehun/pricing` 在 `Hehun.php` 调 `ConfigService::calculatePointsCost()` 时把 `null` 传给 `int $basePoints`，页面首屏即连续弹出“服务器错误”，用户无法得知积分消耗与解锁条件。
- [ ] [占卜] 合婚基础版与完整版提交仍会读取不存在的 `zhi_index` - 合婚配对 - 按测试样例男 `1990-05-15 10:30`、女 `1992-08-20 09:00` 提交 `free` / `premium` 都返回 `Undefined array key "zhi_index"`，生肖配对、婚姻指数和详细建议全链路报废。
- [ ] [占卜] 每日运势黄历年号仍与真实农历不符 - 每日运势 - `/api/daily/fortune` 在 `2026-03-17` 返回 `甲子年 1月16日`，与当日真实农历不一致，会直接误导宜忌、吉时与日运参考判断。

### 🟡 中优先级（体验问题）


### 🟢 低优先级（专业性优化）

---

## 🔮 占卜爱好者深度体验检查报告 - 第二十二轮 (2026-03-17)

作为精通东西方命理占卜的资深爱好者，我继续结合真实页面操作、接口实测、代码交叉核验与黄历对照，新增以下不重复问题：

### 🔴 高优先级（逻辑错误/准确性问题）

- [ ] [占卜] 八字简化版月柱仍按错月令，测试生辰被排成丑月 - 八字排盘 - 以 `1990-05-15 10:30 男` 调 `/api/paipan/bazi`（simple）返回月柱 `己丑`；按节气与《五虎遁月》规则，1990-05-15 已过立夏，应属巳月且庚年巳月应为 `辛巳`，月令错置会连带影响日主旺衰、喜用神与大运判断。
- [ ] [占卜] 每日运势个性化今日干支计算错误，专属建议随之失真 - 每日运势 - `2026-03-17` 的 `/api/daily/fortune` 返回 `todayGanZhi=庚戌`、据此判成“比劫”，而公开黄历当日应为 `庚寅日`；日干支错了会直接带偏日主关系、幸运方位和个性化建议。

### 🟡 中优先级（体验问题）

### 🟢 低优先级（专业性优化）

---

## 🎨 第三十九轮UI设计检查报告 (2026-03-17)

作为资深产品经理和UI设计师，我继续从首页登录态可信度、八字结果归属、塔罗积分首屏反馈、合婚输入门槛与每日运势错误承接几个角度复核太初命理网站，确认以下问题尚未在当前 `TODO.md` 中登记后，新增如下：

### 🔴 高优先级（功能性问题）
- [ ] [UI] 合婚页把双方精确出生时间设为硬门槛，没有“未知时辰 / 仅生日 / 大概时间段”入口，容易逼用户随便填时间后得到伪精确结论 - `frontend/src/views/Hehun.vue` - 增加时辰精度选项，并在低精度场景明确提示结果可信度下降。

### 🟡 中优先级（体验问题）


### 🟢 低优先级（美观问题）
