# admin 自动化执行记忆

> 环境基线更新（2026-03-18）：当前本地标准环境已切换为 **phpstudy + `http://localhost:8080` 直连接口**。后续后台修复不要再默认依赖 Docker、`docker compose`、源码挂载或容器重建；历史记录中的容器相关内容仅保留为旧排障背景。

- 2026-03-19 07:02：继续按 phpstudy `http://localhost:8080` 复现 `[admin]` 唯一高优主问题；`/api/health` 返回 `code=200`，`POST /api/admin/auth/login`（`admin / admin123`）仍返回“管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql”，`GET /api/admin/dashboard/statistics` 仍为 `401`，`GET /admin/login` 仍是 `404`。复核 `backend/app/controller/admin/Auth.php`、`backend/app/service/AdminAuthService.php`、`database/20260317_create_admin_users_table.sql`、`backend/docker-entrypoint.sh` 后，结论仍是 phpstudy 本机 `taichu` 库未导入管理员初始化 SQL；该项属于登录鉴权 + 数据库迁移高风险边界，本轮未改业务代码、未动 SQL、未更新 TODO 完成状态，建议人工导入 SQL 后先复测 `login -> auth/info -> dashboard/statistics`。




- 2026-03-19 04:58：再次按 phpstudy `http://localhost:8080` 口径复现 `[admin]` 唯一高优主问题；`/api/health` 返回 `code=200`，`POST /api/admin/auth/login`（`admin / admin123`）仍返回“管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql”，`GET /api/admin/dashboard/statistics` 仍为 `401`，`GET /admin/login` 仍是 `404`。复核 `backend/app/controller/admin/Auth.php`、`backend/app/service/AdminAuthService.php`、`database/20260317_create_admin_users_table.sql`、`backend/docker-entrypoint.sh` 后，结论不变：管理员初始化 SQL 只覆盖容器 bootstrap，phpstudy 本机 `taichu` 库仍需人工导入；该项属于登录鉴权 + 数据库迁移高风险边界，本轮未改业务代码、未动 SQL、未更新 TODO 完成状态。


- 2026-03-19：本轮继续只处理 `[admin]` 唯一高优主问题；用 phpstudy `http://localhost:8080` 复测确认 `/api/health` 正常、`POST /api/admin/auth/login` 对 `admin / admin123` 仍返回“管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql”，`GET /api/admin/dashboard/statistics` 仍为 `401`，`GET /admin/login` 仍 `404`。复核 `Auth.php`、`AdminAuthService.php` 与初始化 SQL 后，结论未变：phpstudy 本机 `taichu` 库仍缺少管理员初始化 SQL；该项属登录鉴权 + 数据库迁移高风险边界，本轮未改代码、未动 SQL、未更新 TODO 完成状态，下一步仍需人工导入 SQL 后再测 `login -> auth/info -> dashboard/statistics`。


- 2026-03-18：本轮清掉 `TODO.md` 当前唯一未完成的 `[运营]` 登录项，已把 `admin/src/views/login/index.vue` 改为区分账号错误、后台异常、代理/网络故障与超时，并在 `admin/src/api/request.js` 保留 `businessCode/httpStatus/transportCode` 供页面精细提示；`admin/src/api/user.js`、`admin/src/stores/user.js` 已支持登录透传 options，`TODO.md` 已勾选完成，admin 构建通过。



- 2026-03-18：本轮继续处理 `TODO.md` 顶部 4 条高优先级 `[运营]` 阻塞项，已确认根因不是仓库缺修补，而是本地 `backend` 容器仍跑旧代码；同时新增修复 `backend/app/middleware/AdminAuth.php`（缺失 `sanitizeParams` 导致所有已登录请求都可能 500）与 `backend/app/controller/Notification.php`（静态方法签名冲突导致调积分通知阶段致命错误），并在 `backend/docker-compose.yml` 为 backend 服务补上源码挂载 `./:/var/www/html`，随后 `docker compose up -d --build backend` 重建容器；真实运行态冒烟已通过：admin 登录、用户列表、手动调积分 `+1/-1`、黄历列表、神煞新增/删除、SEO 新增/删除 全部恢复，`TODO.md` 与 `overview.md` 已同步更新。

- 2026-03-18：本轮继续核查 `TODO.md` 中的 `[运营]` 项，已确认当前不存在未勾选的 `[运营]` 待办；同时更新 `overview.md`，把“手动调积分未闭环”的旧描述改为已由 `database/20260318_fix_admin_role_permissions.sql` 权限补丁闭环，后续如继续推进应转向 `TODO.md` 的 `[UI]` 项。




- 2026-03-18：本轮继续处理 `[运营]` 高优先级待办，新增 `database/20260318_fix_admin_role_permissions.sql` 为运营角色补发 `points_adjust / content_manage / almanac_view / almanac_edit` 权限，并把补丁挂入 `backend/docker-entrypoint.sh` 自动执行；同时让注册奖励与注册开关改为读取后台系统配置，VIP 订单页错误态补充 `stats_view / config_manage` 线索，`TODO.md` 已同步勾选，admin 构建通过。
- 2026-03-18：本轮继续处理 `[运营]` 高优先级待办，已修复用户列表用户名搜索的 ThinkORM `whereOrLike` 兼容问题，为黄历管理补上 `content_manage / config_manage` 权限兜底与缺表空结构返回；同时补齐 SEO / 黄历初始化 SQL 并挂入 `backend/docker-entrypoint.sh`，SEO 页字段归一化与 VIP 订单写权限判断也已同步收口，`TODO.md` 与 `overview.md` 已更新，admin 构建通过。




- 2026-03-18：本轮复核并核销了第二十五轮 `[运营]` 的知识库页面误报项，并为 `admin/src/views/payment/vip-orders.vue` 补齐整页错误态、重试入口、失败清空旧数据与只读保护；`admin/src/api/payment.js` 已支持 VIP 请求 options 透传，`TODO.md` 与 `overview.md` 已同步更新，相关 lint 与 admin 构建校验通过。

- 2026-03-18：本轮继续清掉第二十四轮未完成 `[运营]` 项，已为系统设置 / SEO / 系统公告补齐 403/加载失败显式错误态与只读保护，并把用户详情页改为真实活动流 + 失败即禁用手动调积分；`TODO.md` 与 `overview.md` 已同步更新。




- 2026-03-17：继续处理第二十二轮 `[运营]` 待办，已补上后端容器启动时自动执行后台补丁 SQL 的链路，统一独立后台到 `8080` 代理口径，并为侧边栏接入按角色过滤的 `accessRoutes`；`TODO.md` 已同步勾选完成。
- 2026-03-17：本轮继续处理 `[运营]` 待办，确认系统公告页已接通真实接口，并新增 Dashboard 快捷操作、手动刷新统计、实时快照 CSV 导出；已同步更新 `TODO.md` 与 `overview.md`。


- 2026-03-17：完成 3 条高优先级运营修复：1）重写 SEO 管理页并接入 `system/seo/*` 接口；2）修正支付/充值/VIP 订单 API 路径并补齐 `/api/admin/order*` 路由，同时重构充值订单、VIP订单、支付配置页面；3）修复系统设置页硬编码默认值、Logo 上传地址与后端 PUT 保存/缓存刷新问题。
- 本轮已同步更新 `TODO.md` 为已完成状态，并更新 `overview.md` 记录改动与验证结果。
- 仍待后续自动化继续处理的运营阻塞项主要是：admin 登录表硬编码、`ADMIN_JWT_SECRET` 环境变量、黄历 CRUD 路由联调等。
- 2026-03-18：本轮按最新规则仅检查 `TODO.md` 的 `## A. 高频修复队列 > ### [admin] 管理后台修复专家`，确认当前仍为“暂无已证实的后台运营修复项”；未进入复现/改码，已直接退出，等待 `30-3` 补证后再接手。
- 2026-03-19：本轮继续命中 `[admin]` 唯一待办；再次用 phpstudy + `http://localhost:8080` 复现 `POST /api/admin/auth/login` 对默认账号 `admin / admin123` 稳定返回“管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql”，并补充确认 `GET http://localhost:8080/admin/login` 仍为 `404`，因此未做页面级回归。复核 `backend/app/controller/admin/Auth.php`、`backend/app/controller/Admin.php`、`backend/app/service/AdminAuthService.php`、`database/20260317_create_admin_users_table.sql`、`backend/docker-entrypoint.sh` 后，结论仍是：phpstudy 本机 `taichu` 库缺少管理员初始化 SQL，而自动补跑只覆盖容器链路。该项仍属登录鉴权 + 数据库迁移高风险边界，本轮未自动执行 SQL、未改业务代码、未更新 TODO 完成状态，只保留人工导入 SQL 后再复测 `login -> auth/info -> dashboard/statistics` 的处置建议。

- 2026-03-18：本轮命中 `[admin]` 唯一待办，已再次用 phpstudy + `http://localhost:8080` 直连接口复现 `POST /api/admin/auth/login` 对默认账号 `admin / admin123` 稳定返回“管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql”；并复核 `Auth.php`、`AdminAuthService.php`、初始化 SQL、`docker-entrypoint.sh` / `docker-compose.yml`，确认自动导入仅覆盖容器链路，phpstudy 本地库仍需手动导入管理员初始化 SQL。该项属于登录鉴权 + 数据库迁移高风险边界，本轮未自动执行 SQL、未改业务代码、未更新 TODO 完成状态，只补充人工处置方案并同步到 `overview.md`。





