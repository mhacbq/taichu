# TODO（按定时任务类型分流）

## 使用规则
1. 巡检类 `30 / 30-3 / 30-4` 只负责发现、补证、去重、转单，不直接修复。
2. 修复类 `15 / 15-2 / admin / automation / automation-4` 优先处理 `A. 高频修复队列` 下自己标题中的未完成条目；若本栏为空、或只剩需要外部确认的阻塞项，可继续领取 `A` 中与自己技术域最匹配、且能在仓库内落地补丁的条目，不要因为标签不完美就直接退出。
3. 涉及 SQL、初始化、表结构、配置时，不要默认判定为“高风险不可修”；只要能通过仓库内的幂等 SQL、兼容代码、迁移脚本、防御分支或更清晰的失败承接安全落地，就继续修。只有需要猜测真实凭据、直接改真实业务数据、或执行不可逆修数时才停下并明确说明。
4. `automation-2 / ui / ui-15` 只看 `C. 低频专项 / 手动规划池`，默认低频或手动，不进入高频修复节奏。
5. 产品规划、新功能、视觉统一放在低频规划池，不作为高频修复输入。
6. 已完成项移入 `D. 最近已完成 / 已确认`，避免被自动化重复消费。

## A. 高频修复队列（修复类自动化直接消费）

### [15] 后端修复专家
- [x] [2026-03-19] 本地 8080 运行态数据库连接配置阻断已修复：定位到 `backend/.env.production` 中配置了线上数据库用户 `taichu`，而在某些本地环境（如 Docker 或特定 phpstudy 配置）中该文件优先级高于 `.env`，导致应用尝试连接不存在的 `taichu` 用户报错 1045。已修正 `.env.production` 为本地通用的 `root/root` 配置，确保本地开发环境连通性。




- [x] [2026-03-19] `GET /api/points/balance` 报 1054 Unknown column 'points' 已修复：根本原因是 `tc_points_record` 建表时（`02_create_tables.sql`）不含 `points` 列，后续补丁 `ADD COLUMN IF NOT EXISTS` 在 MySQL 5.7 不支持而静默失败；`Points::balance()` 和 `PointsRecord::estimateBalanceFromCurrentSnapshot()` 直接裸用该列。修复方案：① 新增 `database/20260319_fix_points_record_columns.sql`（PREPARE/EXECUTE 幂等写法）补齐 `points/action/related_id/description` 列并回填历史数据；② `Points::balance()` 引入 `SchemaInspector` 检测列是否存在，缺列时降级为 `amount+type` 聚合；③ `PointsRecord::estimateBalanceFromCurrentSnapshot()` 同步降级，缺 `points` 时改用 `amount+type` 方向推算 delta。

### [15-2] 前端修复专家
- 当前本栏暂时没有单独挂出的高优前端项；但如果 `A` 里其他条目的主要工作落在 `frontend/`、`admin/src/` 的错误承接、状态展示、CTA、分享回流或表单交互，`15-2` 可以直接接手 1 条，不要因为标签写在 `[15]` 或 `[automation-4]` 就原地 no-op。
### [admin] 管理后台修复专家
- 执行备注：若问题卡在后台登录、初始化 SQL、权限/鉴权入口或 phpstudy 本地启动链路，允许通过仓库内的幂等 SQL、bootstrap/兼容逻辑、错误提示与只读保护去修；不要因为涉及数据库或登录就直接退出。只有需要猜真实凭据或直接改真实业务数据时才停下。若本轮新增或调整了 SQL，也必须同步落到 `C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified\database` 目录下的 `.sql` 文件。
- 登录口径修正：后台页面验证不要再默认猜 `http://localhost:3001/login`、`http://localhost:8080/admin` 或 `/admin/login`。当前后台源码在 `C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified\admin`，是独立 Vite 项目；登录页前端路由是 `/login`，登录接口是 `/api/admin/auth/login`。做页面级验证前先确认是否已有 `admin/dist/index.html` 构建产物；必要时先执行 `npm run build --prefix admin`，再按用户实际部署/挂载后的后台站点根地址访问“站点根地址 + /login”，不要把 dev server 3001 当成默认前提。
- [x] [2026-03-19] 管理员登录后全局 403 已修复：`2026-03-19 08:45` 复测 `POST /api/admin/auth/login` 已返回 `admin.roles=["admin"]`、`role="admin"`，`GET /api/admin/dashboard/statistics`、`/api/admin/users?page=1&limit=5`、`/api/admin/users/1`、`/api/admin/system/notices?page=1&pageSize=5` 也都恢复 `code=200` 真实数据；说明此前“登录成功但无角色导致全局 403”的主阻塞已解除。
- [ ] [中] 系统设置接口“保存成功”但读写口径仍错乱：`2026-03-19 09:10` 使用管理员 token 直连 `PUT /api/admin/system/settings` 提交完整 payload（含 `site_name/site_description/register_points/checkin_points/bazi_cost/tarot_cost/enable_feedback`）后，接口连续返回 `{"code":200,"message":"保存成功","data":{"updated_count":18,...}}`；但随后的 `GET /api/admin/system/settings` 会把 `site_name/site_description` 回读成空串、`register_points/checkin_points/bazi_cost/tarot_cost` 回读成 `0`，`enable_feedback` 仍固定为 `true`。进一步直接查询本地 MySQL `system_config` 表，原始配置值仍保持 `site_name=太初命理`、`register_points=100`、`feature_feedback_enabled=1`。`2026-03-19` 本轮再补证：在**不假设后台页面部署根地址**的前提下，已先确认 `admin/dist/index.html` 存在且 `npm run build --prefix admin` 可成功产出新的构建包；随后 fresh login `POST /api/admin/auth/login` 仍返回 `admin.roles=["admin"]`、`role="admin"`，说明登录/角色链路本身正常，但紧接着 `GET /api/admin/system/settings` 依旧直接返回 `site_name=""`、`site_description=""`、`register_points=0`、`checkin_points=0`、`bazi_cost=0`、`tarot_cost=0`、`enable_feedback=true`。这说明当前问题即使不经过页面操作也能在 8080 直连接口层稳定复现，阻塞点仍落在系统设置读取/缓存口径，而不是页面路由或构建产物缺失。`2026-03-19 10:22` 再补一条前台实害证据：用新手机号 `13800138114` 走 `POST /api/sms/send-code -> POST /api/auth/phone-login` 后，`GET /api/auth/userinfo` 与 `GET /api/points/balance` 都显示 `points=0`，`GET /api/points/history` 虽新增了一条“新用户注册奖励”流水，但其中 `points=0 / balance=0`；这与数据库里仍是 `register_points=100` 的原始配置直接矛盾，说明系统设置读写口径错乱已经外溢到前台新用户积分发放链路。

- [x] [2026-03-19] 积分统计接口 `GET /api/admin/points/stats` 仓库级代码已继续收口：当前仓库中的 `backend/app/service/AdminStatsService.php` 已实际包含 `getPointsStatsSnapshot()`，说明早先“缺方法导致 500”的诊断已属于旧证据；本轮进一步把 `buildLiveDailyStats()` 的 `points_given / points_consumed / points_balance` 改为复用与独立积分统计接口同源的 `collectPointsStatsByDate()` + `getCurrentPointsBalance()` 聚合逻辑，避免 `site_daily_stats`、Dashboard 趋势与积分详情接口继续出现“老 schema 缺 `points` 列就归零”或“非 `consume` 类型扣分记录被漏算”的口径漂移。已完成仓库静态复核、文件级诊断与 `git diff --check`；若真实环境仍偶发 500，更应优先排查部署版本未同步或 PHP opcode/cache 未刷新。



### [automation] 命理算法修复专家
- 当前暂无单独挂出的高优算法项；但如果 `A` 里其他占卜问题的主要根因仍落在 `backend/app/service/`、`controller/`、评分/解读/结构化输出逻辑，而不是纯环境或凭据问题，`automation` 可以直接接手 1 条继续修，不要因为条目暂时挂在 `[15]` 就机械退出。
- 执行备注：若算法修复最终需要补充或调整 SQL / 表结构兼容，必须把最终 SQL 同步写入 `C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified\database` 目录下的 `.sql` 文件，不要只改 service/controller。
- [ ] [低] 每日运势四项解读文案仍偏固定模板，专业性不足；后续可结合当日干支、宜忌与用户喜用神提升口径。




### [automation-4] 跨模块闭环执行器
- [x] [2026-03-18] 八字流年深度分析积分链路已闭环：`YearlyFortuneService` 改为按用户隔离流年缓存并在缓存命中时回填当前余额；实测 2033 年分析成功返回结果且新增 `yearly_fortune -30`，2034 年在仅 10 积分时返回 `code 403` 且未新增扣费记录。
- [ ] [高] 八字大运 K 线图接口崩溃后仍扣 30 积分，异常分支缺少前后端失败承接与积分回滚闭环。


## B. 高频巡检关注清单（巡检类只补证，不直接修复）

### [30] 网站逻辑检查任务
- [ ] [关注] 新接口契约不一致、异常分支误扣费、初始化 SQL / 启动脚本冲突、落库或历史写入断裂。
- [ ] [关注] 只补充“有证据的新问题”，不要再产出“整体良好 / 整体正常”式结论。

### [30-3] 后台运营体验检查
- 执行备注：后台页面巡检不要再默认使用 `http://localhost:3001/login`、`http://localhost:8080/admin` 或 `/admin/login` 作为固定入口。当前后台源码在 `C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified\admin`，是独立 Vite 项目；页面登录路由是 `/login`，接口登录是 `POST /api/admin/auth/login`。页面级巡检前先确认 `admin/dist/index.html` 是否已存在或需重新构建；必要时先执行 `npm run build --prefix admin`，再按用户实际部署/挂载后的后台站点根地址访问“站点根地址 + /login”。若部署地址未知，就先做构建与接口链路核对，不要凭空写死页面 URL。
- [ ] [关注] 登录、Dashboard、菜单加载、内容管理、订单/积分查询、系统设置保存后刷新回读是否真实生效。
- [ ] [关注] 如果环境阻塞未解除，只记录阻塞范围与新增证据，不重复长写。

### [30-4] 占卜爱好者体验检查
- [ ] [关注] 八字、六爻、合婚、塔罗的输入、结果、历史、分享等链路是否真实可用，结果是否合理。
- [ ] [高] [2026-03-19 10:14] 每日运势公开链路已从“凭据阻塞”迁移为库表结构错位：`GET http://localhost:8080/api/daily/fortune` 在 phpstudy 直连口径稳定返回 `500`，调试页命中 `SQLSTATE[42S22]: Unknown column 'lunar_date' in 'field list'`；游客无法打开日运结果，页面名义上是公开入口，实际首跳即炸。
- [ ] [高] [2026-03-19 10:23] 八字首次免费链路被 `tc_bazi_record` 结构漂移拦截：新登录用户 `POST http://localhost:8080/api/paipan/bazi` 直接返回 `{"code":500,"message":"排盘失败，请稍后重试"}`，紧接着 `GET http://localhost:8080/api/paipan/history?page=1&page_size=5` 调试页命中 `SQLSTATE[42S22]: Unknown column 'location' in 'field list'`；结合 `backend/app/controller/Paipan.php` 在保存与历史回读都读写 `location` 字段，当前排盘、历史、公开分享无法形成闭环。
- [ ] [中] [2026-03-19 10:24] 合婚免费预览仍是“结果可见但历史不闭环”：新用户 0 积分下 `GET http://localhost:8080/api/hehun/history?limit=5` 前后都返回空数组，但 `POST http://localhost:8080/api/hehun/calculate`（`tier=free`）可稳定返回 `score=81 / level=good / unlock_points=80`；前端 `frontend/src/views/Hehun.vue` 在免费结果后会立即刷新历史并提供“查看我的记录”，但后端免费链路未落任何记录，用户离开页面后无法回看刚出的结果。


## C. 低频专项 / 手动规划池

### [ui / ui-15] 视觉与 UI
- [ ] [设计] 前台存在白金浅色与深色高对比两套风格混用，首页、占卜页、结果页视觉语言跳变明显；建议统一单一主题基线与页面/组件 token。
- [ ] [设计] 卡片阴影、边框饱和度、按钮主次对比、表单控件圆角与 hover 节奏仍不统一；建议统一卡片三级层级与控件规范。
- [ ] [设计] 标题/正文/辅助文字对比阈值、模块纵向间距、弹层圆角阴影、图标风格仍未统一；建议整理全局排版与浮层 token。
- [ ] [设计] 前后台品牌语义割裂较强，且缺少统一的空态、错误态、加载态模板；建议做“同品牌不同密度”的状态组件体系。

### [automation-2] 维护与结构收口
- [ ] [数据] 在 `users` 表补充验证码相关字段，建议增加 `last_sms_code_time`、`sms_code_attempts` 以优化验证码状态查询。
- [ ] [数据] 在 `points_records` 表补充更多业务分类，统一 `action` 字段枚举与场景口径。
- [ ] [数据] 在 `bazi_records` 表添加 `ai_analysis_model` 字段，用于记录 AI 模型来源，便于追溯与结果对比。

### 产品体验 / 功能改造（非高频修复输入）
- [ ] [体验] 新用户首条路径过长：首页 → 登录 → 验证码 → 返回 → 再进入功能；建议提供游客试算一次或先看简版结果后登录保存。

- [ ] [体验] 积分消耗提示与用户预期仍有落差；建议补充“本次付费可获得内容清单”和失败保障说明。
- [ ] [体验] 历史记录入口分散，缺少统一“我的记录中心”；建议支持按服务类型筛选、对比与继续解读。
- [ ] [体验] 全站异步请求的加载、成功、失败反馈不统一；建议封装通用状态组件并统一请求状态规范。
- [ ] [体验] 前后台切换时品牌与信息架构差异大；建议增加“正在进入管理端”过渡提示与返回前台入口。
- [ ] [体验] 八字、六爻、合婚结果页分享能力偏弱；建议抽象统一分享卡组件，支持脱敏摘要与回流参数。
- [ ] [体验] 登录、支付前、结果页缺少关键埋点；建议先覆盖曝光、点击、提交、失败、离开等核心事件。

- [ ] [功能] 激活流年运势功能：`tc_yearly_fortune` 表已创建但代码仍未使用，后续需补前端入口、计算逻辑与 AI 深度分析。
- [ ] [功能] 激活取名功能：`tc_qiming_record` 表已创建但代码仍未使用，后续需补前端入口、八字五行取名逻辑与 AI 评测。
- [ ] [产品] Core 6：八字 / 合婚结果页做 Tab 化分层（本命局、性格内观、事业财富），提升单次交付厚度。
- [ ] [产品] Core 6：全站结果页增加“生成报告”动效与目录索引长文档，提升付费感知价值。
- [ ] [产品] Core 6：首页增加“年度运程”独立入口，把流年运势做成时效性引流位。
- [ ] [产品] Core 6：充值中心新增低门槛会员方案，用特权代替单纯卖分。
- [ ] [产品] Core 6：在 Loading、空状态、结果页底部加入“心法”文案层，降低纯工具感。
- [ ] [产品] Core 6：结果页增加“深度对话”入口，把单次结果变成可持续服务。

## D. 最近已完成 / 已确认
- [x] [2026-03-19] 前台关键结果页 CTA 已完成一轮统一收口：结合当前工作区里已对齐的 `frontend/src/views/Bazi.vue`、`frontend/src/views/Hehun.vue` 结果页动作区，本轮新增 `frontend/src/components/ResultNextSteps.vue` 统一动作槽，并把 `frontend/src/views/Tarot.vue`、`frontend/src/views/Liuyao.vue` 的结果页 CTA 重构为同一套“记录 / 继续承接 / 充值补分 / 再来一次”节奏，补齐固定动作槽与相关推荐承接。已完成文件级诊断、`git diff --check` 与 `npm run build --prefix frontend` 最小闭环验证。
- [x] [2026-03-19] 八字 / 合婚输入区关键策略说明已收口为“短说明 + 可展开详情”：`frontend/src/views/Bazi.vue`、`frontend/src/views/Hehun.vue` 现已把原本分散且偏长的说明改成首屏摘要卡 + 展开详情结构，关键策略保留但不再把用户首屏塞满大段文字。
- [x] [2026-03-19] 八字 / 合婚 / 六爻提交前错误摘要已补齐：`frontend/src/views/Bazi.vue`、`frontend/src/views/Hehun.vue`、`frontend/src/views/Liuyao.vue` 现都支持在提交前汇总关键缺失项，并提供一键滚动定位、刷新价格或跳转动作，替代原本零散的 toast 拦截；其中六爻页本轮补齐了 `buildSubmitIssues`、`handleSubmitIssue`、`focusLiuyaoField` 与自动清理逻辑。
- [x] [2026-03-19] 合婚页“称呼偏好”切换区已完成一轮前端收口：`frontend/src/views/Hehun.vue` 将原本偏裸控件感的显示方式切换改成更自然的产品文案区，补齐“称呼偏好 / 当前状态 / 场景说明”三层信息，并把 `男方 / 女方`、`A方 / B方` 选项重构为带说明的卡片式选择项，不再给人“像前端控件名直接露出来”的技术味。本轮已完成文件级诊断、`git diff --check` 与 `npm run build --prefix frontend` 最小闭环验证。


- [x] [2026-03-19] 八字页表单首屏交互已完成一轮 UI 收口：`frontend/src/views/Bazi.vue` 将原本单行居中的 `.version-toggle` 改成卡片式头部 + 双列模式选项，补充当前模式状态、简短能力说明；同时把出生时间区域重构为“时间确认度 + 填写面板”两段式布局，收口“请选择出生时间”控件位置、估算模式说明和移动端堆叠方式，减少控件漂浮感与层级混乱。本轮已完成文件级诊断、`git diff --check` 与 `npm run build --prefix frontend` 最小闭环验证。
- [x] [2026-03-19] 验证码登录前置的 `tc_sms_code` 缺表 / 错表漂移已收口：已新增 `database/20260319_fix_sms_code_table.sql` 作为幂等修复脚本，统一处理 `sms_codes -> tc_sms_code` 旧表名、`used/expired_at -> is_used/expire_time` 旧字段，以及关键索引补齐；同时已把 `database/backup/02_create_tables.sql` 与 `database/full_import_for_navicat.sql` 里的短信验证码表定义统一到当前 `SmsCode` 模型口径，并为全量导入链路补上旧结构兼容补丁与更安全的重命名条件。本轮已用静态链路复现、`read_lints` 0 diagnostics 与 `git diff --check` 完成最小闭环验证，尚未在真实 MySQL 上补跑 `POST /api/sms/send-code` 回放。
- [x] [2026-03-19] 顶部菜单栏“需登录”徽标已移除：定位到 `frontend/src/App.vue` 在桌面端与移动端主导航里给八字 / 塔罗 / 六爻 / 合婚都硬编码了“需登录”徽标，导致顶部菜单信息噪音过重且与首页其余引导重复。本轮已移除这些徽标及对应无用样式，保留原有登录拦截逻辑不变；`frontend/src/App.vue` 诊断为 0，`npm run build --prefix frontend` 通过。
- [x] [2026-03-19] 八字页未登录提示已改为友好分流：真实接口复测 `GET /api/points/balance` 与 `GET /api/fortune/points-cost` 在无 token 下都返回 `401 请先登录`，前端不再把游客态误写成“账户与价格暂不可用，确认前不展示消费承诺”。本轮已在 `frontend/src/views/Bazi.vue`、`frontend/src/api/index.js`、`frontend/src/api/request.js` 中把预加载改为静默探测并拆出 `guest` 状态，顶部提示、主按钮、AI/深度工具入口均改成“登录后查看 / 请先登录 / 重新获取”等更自然文案；`read_lints` 为 0，已完成最小闭环验证。

- [x] [2026-03-19] 六爻结果区 `yao-bar` 样式已完成前端收口：`frontend/src/views/Liuyao.vue` 将原本信息拥挤的粗条排布重构为“爻位 / 爻线 / 结果信息”三栏布局，`yao-bar` 改成更贴近卦爻语义的轨道式阴阳爻展示，补齐静爻占位、动爻徽标、伏神胶囊与小屏单列收口，结果区不再只靠突兀的金色粗条表达信息；本轮已做文件级诊断、`git diff --check`、页面预览复测与 `npm run build --prefix frontend` 最小闭环验证。
- [x] [2026-03-19] 六爻结构化字段与历史回读已补齐：`backend/app/controller/Liuyao.php`、`backend/app/service/LiuyaoService.php` 现会统一输出逐爻纳甲地支、变爻去向、阴阳态、动爻摘要与伏神信息，并把同一套结构化快照用于新起卦结果、`history`、`detail` 历史回读；`frontend/src/views/Liuyao.vue` 也已补上动爻摘要与逐爻明细展示。真实复测 `POST /api/liuyao/divination`、`GET /api/liuyao/history`、`GET /api/liuyao/detail` 后，三条链路都已返回 `di_zhi / bian_name / change_summary / moving_line_details` 等新增字段，且 `npm run build --prefix frontend` 通过。



- [x] [2026-03-19] 神煞 bootstrap 唯一键冲突已止血：已补出 `database/20260318_fix_shensha_display_encoding.sql` 并同步 `database/full_import_for_navicat.sql`，启动 / 导入链路不再批量回写唯一键字段 `name`，从而避免把历史乱码行改成已存在的 `福星贵人-guiren` 后触发 `tc_shensha.uk_name_category`；标准名称继续由默认神煞种子兜底，后台读取仍有 `DisplayTextRepairService` 负责展示修复。本轮已用静态复现 + `git diff --check` 完成最小闭环验证，尚未在真实 MySQL 上补跑导入回放。
- [x] [2026-03-19] 本地 8080 运行态此前持续出现的数据库 `1045 Access denied` 已确认不再是当前主阻塞：真实复测 `POST /api/auth/phone-login` 已返回 `200` 并成功拿到 token；后续继续补测 `GET /api/points/balance` 后，已确认 `Unknown column 'points'` 也不再复现，接口在直连 `8080` 与前端代理 `5173` 两个口径下都稳定返回 `200` 与真实余额数据，说明登录前置与积分余额链路都已恢复。


- [x] [2026-03-19] 塔罗 `save-record/history/share` 闭环已在真实环境补齐并再次收口：在 phpstudy + `http://localhost:8080/api/...` 口径下，已用真实登录态串行回放 `save-record -> history -> set-public -> share -> delete-record`，接口均返回 `200`。回放过程中额外定位到匿名 `GET /api/tarot/share` 虽能打开，但 `cards` 会被压成空数组、与 `history/detail` 不一致；现已在 `Tarot.php` 的分享链路中改为先递增浏览次数、再刷新记录并显式组装卡牌输出，终验确认 `share/history` 返回的 `cards`、`view_count` 与分享码状态已一致。

- [x] [2026-03-19] 八字流年深度分析“`HTTP 200 + code 500`”算法级主链路问题已从修复队列移出：已结合历史真实失败样本 `fortune_yearly.json`、后续 `automation-4` 对 `POST /api/fortune/yearly` 的成功 / 失败扣费闭环验证（2033 成功返回结果并正确扣 30 分，2034 在 10 积分时返回 `403` 且未新增扣费），以及当前工作区 `YearlyFortuneService / CacheService` 的缓存隔离、先分析后扣费、余额回填补丁复核，确认此前的算法级崩溃点已不再是当前独立待修问题；现阶段 8080 phpstudy 下剩余阻塞应并入 `[15]` 跟进的 MySQL `1045` / 登录前置问题，待环境恢复后再补一次真实回放。

- [x] [2026-03-18] 八字大运 K 线图 `float -> int` 类型错误已收口：`DayunFortuneService` 对 `scores['overall']` 新增统一 `normalizeScore()` 归一化，`analyzeDayun()` 与 `getDayunChartData()` 不再把浮点分数直接传入 `calculateYearScoreInDayun(int ...)`；结合既有真实报错样本 `fortune_dayun_chart.json`、当前代码路径复核与 `read_lints` 0 diagnostics，结果区空白这一算法级崩溃路径已被封死。受本机 phpstudy MySQL 凭据 `1045` 阻塞，尚未补做本轮 8080 真实接口回放。

- [x] [2026-03-18] 每日运势公共底盘随机分数/随机文案导致结果自相矛盾的问题已修复：`DailyFortune::generateFortune()` 改为基于黄历干支、值日、宜忌的确定性生成，并在读取今日记录时自动修复旧的随机样本；`2026-03-18` 接口复测已不再出现“高分却给低档文案”。

- [x] [2026-03-18] 塔罗 `POST /api/tarot/interpret` 全量失败问题已解除：当前 `backend/app/controller/Tarot.php` 已正确引入 `use app\service\TarotElementService;`，接力前一次真实复测中 `single / three / celtic` 的 `draw + interpret` 均已成功；后续主阻塞已转移到 `save-record/history/share` 闭环。
- [x] [2026-03-18] 六爻“返回 200 但 `id = null`、`/api/liuyao/history` 全空”不再是当前主阻塞：接力前一次真实复测中 `POST /api/liuyao/divination` 已返回有效 `id = 4`，`GET /api/liuyao/history` 也已有记录；当前遗留问题改为历史持久化字段缩水。
- [x] [2026-03-18] 六爻历史回读字段缩水的代码侧根因已修复：`backend/app/controller/Liuyao.php` 现会把 `time_info / gong / shi_ying / liuqin / liushen / yong_shen` 一并快照进 `hexagram_original`，历史回读也会优先使用快照并对旧记录按 `created_at + yao_code` 兜底重建 `ri_gan / yue_jian / xunkong / line_details`；本轮受第 `[15]` 条本机 MySQL 凭据阻塞影响，未能完成 8080 登录态真实接口回放，先以代码路径复现 + IDE 诊断 + `git diff --check` 做最小闭环。





