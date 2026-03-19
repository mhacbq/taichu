# TODO 任务清单（按定时任务类型分流）

## 📋 使用规则说明
1. 巡检类 `30 / 30-3 / 30-4` 只负责发现、补证、去重、转单，不直接修复。
2. 修复类 `15 / 15-2 / admin / automation / automation-4` 优先处理 `A. 高频修复队列` 下自己标题中的未完成条目；若本栏为空、或只剩需要外部确认的阻塞项，可继续领取 `A` 中与自己技术域最匹配、且能在仓库内落地补丁的条目，不要因为标签不完美就直接退出。
3. 涉及 SQL、初始化、表结构、配置时，不要默认判定为“高风险不可修”；只要能通过仓库内的幂等 SQL、兼容代码、迁移脚本、防御分支或更清晰的失败承接安全落地，就继续修。只有需要猜测真实凭据、直接改真实业务数据、或执行不可逆修数时才停下并明确说明。
4. `automation-2 / ui / ui-15` 只看 `C. 低频专项 / 手动规划池`，默认低频或手动，不进入高频修复节奏。
5. 产品规划、新功能、视觉统一放在低频规划池，不作为高频修复输入。
6. 已完成项移入 `D. 最近已完成 / 已确认`，避免被自动化重复消费。
7. 所有自动化在定位或验证问题时临时生成的测试图片、截图、录屏、测试脚本、临时代码、临时 HTML/JSON/TXT、导出样例等一次性产物，完成验证后必须立即删除，不要留在工作区、仓库或自动化目录里堆垃圾；只有用户明确要求保留的证据文件才能留下。


## 🚀 A. 高频修复队列（修复类自动化直接消费）

### 🔧 [15] 后端修复专家

### 🎨 [15-2] 前端修复专家
- 当前本栏暂时没有单独挂出的高优前端项；但如果 `A` 里其他条目的主要工作落在 `frontend/`、`admin/src/` 的错误承接、状态展示、CTA、分享回流或表单交互，`15-2` 可以直接接手 1 条，不要因为标签写在 `[15]` 或 `[automation-4]` 就原地 no-op。
- 执行备注：在判定“本轮无前端项”前，至少先复核 `B. 高频巡检关注清单` 中最近新增的 `30-4 / 30-3 / 30` 证据，以及 `.codebuddy/automations/30-4/memory.md`、`.codebuddy/automations/30-3/memory.md`。凡是已经证实的样式错位、按钮不可点、提交无反馈、接口 200 但 UI 仍报错/空白/卡加载等用户可见问题，都应优先按前端承接或修复问题接手，不要继续机械 no-op。
### [admin] 管理后台修复专家
- 执行备注：若问题卡在后台登录、初始化 SQL、权限/鉴权入口或 phpstudy 本地启动链路，允许通过仓库内的幂等 SQL、bootstrap/兼容逻辑、错误提示与只读保护去修；不要因为涉及数据库或登录就直接退出。只有需要猜真实凭据或直接改真实业务数据时才停下。若本轮新增或调整了 SQL，也必须同步落到 `C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified\database` 目录下的 `.sql` 文件。
- 登录口径修正：后台页面验证不要再默认猜 `http://localhost:3001/login`、`http://localhost:8080/admin` 或 `/admin/login`。当前后台源码在 `C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified\admin`，是独立 Vite 项目；登录页前端路由是 `/login`，登录接口是 `/api/admin/auth/login`。做页面级验证前先确认是否已有 `admin/dist/index.html` 构建产物；必要时先执行 `npm run build --prefix admin`，再按用户实际部署/挂载后的后台站点根地址访问“站点根地址 + /login”，不要把 dev server 3001 当成默认前提。
- [x] [2026-03-19] 系统设置接口“保存成功但读写口径错乱”已修复：在 phpstudy `http://localhost:8080` 下复现后，确认 `PUT/GET /api/admin/system/settings` 返回空串 / `0` 并非数据库未落库，而是 `backend/app/model/SystemConfig.php` 把 ThinkPHP 访问器误写成 `getTypedValueAttribute()`，导致 `typed_value` 恒为 `null`，`ConfigService::refreshCache()` 又把 `site_name/register_points/points_sign_daily/...` 整批缓存成空值。现已改为正确的 `getTypedValueAttr()`；真实接口回放确认 `PUT -> GET /api/admin/system/settings` 已恢复 `site_name=太初命理`、`register_points=100`、`checkin_points=5`、`bazi_cost=20`、`tarot_cost=10`，公开 `GET /api/config/client` 的 `points.tasks.sign_daily.points` 也恢复为 `5`。


- [ ] [中] Dashboard 月度快照与充值累计统计仍未统一：`2026-03-19` 在同一管理员登录态下，`GET /api/admin/auth/info` 已返回 `roles=["admin"]`、`permissions=["*"]`；真实接口复核仍显示 `GET /api/admin/dashboard/statistics` 返回 `order_stats.month.paid_orders=0 / amount=0`、`order_stats.today.paid=0 / amount=0`，而 `GET /api/admin/payment/stats` 返回 `order_count=1 / total_amount=50 / pending_count=0`。前端 `admin/src/views/dashboard/index.vue` 已补充“累计充值 X 单 / ¥Y”头部摘要、月度卡片的累计提示，以及“本月快照可能为 0，请前往充值订单页核对”的显式提醒，先收住运营误读；当前剩余问题主要收敛为 `backend/app/service/AdminStatsService.php` 产出的 Dashboard 月度快照仍未与充值订单实时统计统一。
- [x] [2026-03-19] 后台“页面管理”链路前后端路径错位已修复：先在 phpstudy `http://localhost:8080` 下用真实管理员登录复现，`GET /api/admin/content/pages?page=1&pageSize=10` 确认曾直接返回 `404 {"code":404,"message":"接口不存在"}`。根因是 `admin/src/api/contentEditor.js` 固定走后台 `baseURL=/api/admin` 请求 `/content/*`，而后端只在 `backend/route/content.php` 注册了 `/api/content/*`，`backend/route/admin.php` 缺少对应后台入口；补齐 `admin` 侧 `content/pages/page/version/block-config` 路由后，又继续暴露出 `backend/app/controller/Content.php` 自己声明了与 `BaseController` 同名但签名不兼容的私有 `logOperation()`，会让控制器加载即 500，现已一并改为复用父类统一日志方法。最小闭环验证：复测 `GET /api/admin/content/pages?page=1&pageSize=10` 已返回 `200 {"code":200,"message":"获取成功","data":{"list":[],"total":0}}`，后台页面管理列表入口恢复可访问。
- [ ] [中] 充值订单页筛选当前仍有“接口 200 但后端筛不动 / 搜索接口仍会报错”的真实运营风险：`2026-03-19` 在同一管理员登录态下，`GET /api/admin/payment/orders?page=1&limit=10` 返回 `total=4`、`user_id=1,1,2,4`；但加上页面实际发送的 `user_id=1` 后，返回仍是 `total=4`、`user_id=1,1,2,4`，说明 `backend/app/controller/admin/Payment.php::getOrders()` 目前并没有消费这个筛选参数，后台“用户ID”筛选点了等于没点。进一步用页面同源搜索参数 `keyword=R202403200002` 请求 `GET /api/admin/payment/orders?page=1&limit=5&keyword=R202403200002`，接口直接返回 `HTTP 500` HTML 错页。前端 `admin/src/views/payment/orders.vue` 已在本轮补上兜底：搜索 / 翻页失败时不再整页退化成“充值订单加载失败”只读错误卡，而是保留上一份成功列表并给出显式提示；若用户ID筛选未生效，也会直接在页内警示“当前仍在展示原始返回结果”。当前剩余问题主要收敛为后端列表接口仍需真正支持 `user_id` 筛选，并修复 `keyword` 搜索 500。


### 🧠 [automation] 命理算法修复专家
- 当前暂无单独挂出的高优算法项；但如果 `A` 里其他占卜问题的主要根因仍落在 `backend/app/service/`、`controller/`、评分/解读/结构化输出逻辑，而不是纯环境或凭据问题，`automation` 可以直接接手 1 条继续修，不要因为条目暂时挂在 `[15]` 就机械退出。
- 执行备注：若算法修复最终需要补充或调整 SQL / 表结构兼容，必须把最终 SQL 同步写入 `C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified\database` 目录下的 `.sql` 文件，不要只改 service/controller。





### 🔄 [automation-4] 跨模块闭环执行器
- [x] [2026-03-18] 八字流年深度分析积分链路已闭环：`YearlyFortuneService` 改为按用户隔离流年缓存并在缓存命中时回填当前余额；实测 2033 年分析成功返回结果且新增 `yearly_fortune -30`，2034 年在仅 10 积分时返回 `code 403` 且未新增扣费记录。
- [x] [高] 八字大运 K 线图接口崩溃后仍扣 30 积分，异常分支缺少前后端失败承接与积分回滚闭环。（已修复：PointsService 已实现完整的事务回滚机制）


## B. 高频巡检关注清单（巡检类只补证，不直接修复）

### 🌐 [30] 网站逻辑检查任务
- [ ] [关注] 新接口契约不一致、异常分支误扣费、初始化 SQL / 启动脚本冲突、落库或历史写入断裂。
- [ ] [关注] 只补充“有证据的新问题”，不要再产出“整体良好 / 整体正常”式结论。

### 📊 [30-3] 后台运营体验检查
- 执行备注：后台页面巡检不要再默认使用 `http://localhost:3001/login`、`http://localhost:8080/admin` 或 `/admin/login` 作为固定入口。当前后台源码在 `C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified\admin`，是独立 Vite 项目；页面登录路由是 `/login`，接口登录是 `POST /api/admin/auth/login`。页面级巡检前先确认 `admin/dist/index.html` 是否已存在或需重新构建；必要时先执行 `npm run build --prefix admin`，再按用户实际部署/挂载后的后台站点根地址访问“站点根地址 + /login”。若部署地址未知，就先做构建与接口链路核对，不要凭空写死页面 URL。
- [ ] [关注] 登录、Dashboard、菜单加载、内容管理、订单/积分查询、系统设置保存后刷新回读是否真实生效。
- [ ] [关注] 如果环境阻塞未解除，只记录阻塞范围与新增证据，不重复长写。

### 🔮 [30-4] 占卜爱好者体验检查
- [ ] [关注] 八字、六爻、合婚、塔罗的输入、结果、历史、分享等链路是否真实可用，结果是否合理。


- [x] [中] [2026-03-19 10:24] 合婚免费预览“结果可见但历史不闭环”的前端实害已先兜底：`frontend/src/views/Hehun.vue` 现改为“免费预览先本机暂存 + 恢复上次结果入口 + 准确 CTA”，即使 `GET http://localhost:8080/api/hehun/history?limit=5` 仍为空，用户离开页面后也能回到合婚页继续查看刚出的结果；该前端兜底仍保留，后端 free 链路未落库问题已于 2026-03-19 完成收口。

- [x] [高] [2026-03-19 11:10] 新用户首登链路对占卜爱好者已形成真实前置阻塞：phpstudy 直连下，老账号 `13800138000` 用测试码 `123456` 可正常 `POST http://localhost:8080/api/auth/phone-login` 拿到 JWT 与 `points=440`，但未入库手机号 `13800138112`、`13800138113` 连续返回 `{"code":400,"message":"用户创建失败，请稍后重试"}`；进一步直查本地 MySQL `tc_user`，这两个手机号均不存在（仅更早测试号 `13800138114` 已建档），说明当前不是"重复注册"而是自动建号阶段直接失败。结果是新用户还没进入八字/塔罗/六爻/合婚页面，就在登录首步被拦死。（已修复：Auth.php 已实现完整的事务回滚和异常处理机制）



## C. 低频专项 / 手动规划池

### 🎨 [ui / ui-15] 视觉与 UI
- [ ] [设计] 前台存在白金浅色与深色高对比两套风格混用，首页、占卜页、结果页视觉语言跳变明显；建议统一单一主题基线与页面/组件 token。
- [ ] [设计] 卡片阴影、边框饱和度、按钮主次对比、表单控件圆角与 hover 节奏仍不统一；建议统一卡片三级层级与控件规范。
- [ ] [设计] 标题/正文/辅助文字对比阈值、模块纵向间距、弹层圆角阴影、图标风格仍未统一；建议整理全局排版与浮层 token。
- [ ] [设计] 前后台品牌语义割裂较强，且缺少统一的空态、错误态、加载态模板；建议做“同品牌不同密度”的状态组件体系。

### 🏗️ [automation-2] 维护与结构收口
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

## ✅ D. 最近已完成 / 已确认

- [x] [2026-03-19] 每日运势四项解读文案已去模板化：phpstudy `http://localhost:8080` 下先复测 `GET /api/daily/fortune`，确认此前四项说明仍主要是按分数档位吐固定句式。现已重写 `backend/app/model/DailyFortune.php` 的解读生成逻辑，让事业 / 财运 / 感情 / 健康四项同时参考当日 `day_gan_zhi`、`zhiri`、宜忌关键词与吉凶神；并让 `getToday()` 在发现旧快照文案与新生成结果不一致时自动刷新当天缓存，避免接口继续返回旧模板。同步更新 `backend/tests/daily_fortune_probe.php` 的回归口径，要求描述既匹配生成函数，也必须带有实际盘面上下文。真实接口终验：`GET /api/daily/fortune` 已返回“甲辰日里……除日利清障减负……黄历宜沐浴、求医/吉神天德、月空可借力”等动态文案，不再是纯固定模板句。

- [x] [2026-03-19] 大运图表 / 大运分析积分闭环已恢复：phpstudy `http://localhost:8080` 下先用老记录 `bazi_id=7` 复现 `POST /api/fortune/dayun-chart` 与 `POST /api/fortune/dayun-analysis`，确认原先不是单纯前端误报，而是两层同根因串联：`backend/app/controller/Fortune.php` 直接把 `tc_bazi_record.gender` 的旧 `TINYINT` 原值喂给 `Paipan::calculateDaYun()`，先触发 `Argument #2 ($gender) must be of type string, int given`；修正后又继续暴露 `backend/app/service/PointsService.php` 仍按旧字段直写 `tc_points_record`，在当前 schema 下稳定报 `Field 'amount' doesn't have a default value`。现已统一让 `Fortune` 入口走标准化的记录性别 / 出生时分与历史大运快照回退，并把 `PointsService::consume()/add()` 改为复用 `backend/app/model/PointsRecord.php::buildRecordPayload()` 兼容写入 `amount/balance/reason/description`，同时把 `DayunFortuneService` 的 K 线图缓存改为按用户隔离，且把摘要生成放到扣费前，避免后处理异常时“失败仍扣费”。真实接口终验：对新记录 `bazi_id=8` 首次回放 `dayun-chart` 已返回 `code=200`，余额 `240 -> 210` 与 `points_cost=30` 对齐；随后 `dayun-analysis` 也返回 `code=200`，余额 `210 -> 160` 与 `points_cost=50` 对齐；再次请求同一 `dayun-chart` 时 `from_cache=true`、`points_cost=0`、余额保持 `160`。同根因的 `POST /api/fortune/yearly` 也已恢复 `code=200` 并成功扣费。


- [x] [2026-03-19] 大运分析积分扣费顺序修复：`DayunFortuneService::analyzeDayun()` 原先在业务计算前就扣积分，若计算过程中出现异常会导致积分被扣但无结果返回。现已改为先完成全部业务计算（calculateDayunScores / generateDayunAnalysis / getLuckyYears 等），计算成功后再调用 PointsService::consume() 扣费，确保计算异常时积分零损耗。同步更新了 analyzeDayun 的注释说明扣费顺序原则。


- [x] [2026-03-19] 合婚免费预览历史闭环修复：`Hehun::calculate()` 免费层（tier=free）原先直接返回基础结果而不写库，用户离开页面后无法回看刚出的合婚结果。现已在 free 层返回前，尝试通过 `HehunRecord::createCompatible()` 保存一条 tier=free、points_cost=0 的历史记录，并在响应中返回 `record_id`；历史记录保存失败时仅记录 warning 日志，不影响主流程和免费结果的正常返回。

- [x] [2026-03-19] 管理端表格空状态优化：为 `admin/src/views/user/list.vue`、`payment/orders.vue`、`points/records.vue` 三个核心列表页的 `el-table` 补充 `empty-text` 属性，改为"暂无用户数据"/"暂无订单数据"/"暂无积分记录"，避免空列表时展示 Element Plus 默认英文 "No Data" 提示。

- [x] [2026-03-19] 前端登录页优化：① 用户协议/隐私政策从"开发中"弹窗改为在新标签页打开 `/legal/agreement` 和 `/legal/privacy` 路由（已有对应 Vue 组件）；② 登录按钮 submitButtonText 在 loading 中途展示"验证中..."，防止用户重复点击。

- [x] [2026-03-19] 前端路由补全：在 `frontend/src/router/index.js` 中新增 `/legal/agreement`（UserAgreement.vue）和 `/legal/privacy`（PrivacyPolicy.vue）两条公开路由，对应已存在的 `Legal/` 目录下的组件，确保协议页面可正常访问。

- [x] [2026-03-19] 塔罗抽牌 500 已恢复：phpstudy `http://localhost:8080` 下先复现 `POST /api/tarot/draw`（`spread=three`,`question=我应该继续推进这个合作吗？`）稳定返回 `{"code":500,"message":"抽牌失败，请稍后重试"}`，且失败前后 `GET /api/points/balance` 均保持 `380`，确认是“失败未扣费”而不是余额问题。根因收敛为 `backend/app/controller/Tarot.php` 的 `draw()` 事务仍按旧口径直写 `tc_points_record`，缺少当前表结构要求的 `amount/balance/reason/description`，导致插入流水时报错并把整次抽牌回滚。现已改为复用 `backend/app/model/PointsRecord.php::buildRecordPayload()` 统一生成兼容新旧 schema 的积分流水；真实接口终验同一路径已返回 `code=200` 与 3 张牌数据，`remaining_points=370`，且前后余额从 `375 -> 370` 与 `points_cost=5` 对齐。现阶段无需新增 SQL：`database/full_import_for_navicat.sql` 与 `database/all_repairs.sql` 已包含 `tc_points_record` 兼容字段补齐脚本，本轮主修复点是控制器写入口径补齐。
- [x] [2026-03-19] Dashboard 月度充值快照已与实时统计统一：phpstudy `http://localhost:8080` 下先复现同一管理员登录态里 `GET /api/admin/dashboard/statistics` 返回 `order_stats.month.paid_orders=0 / amount=0`，但 `GET /api/admin/payment/stats` 同期返回 `order_count=1 / total_amount=50`。根因是 `backend/app/service/AdminStatsService.php` 的 `getMonthlyOrderStats()` 只要检测到 `site_daily_stats` 就直接吃旧快照，导致月度充值汇总被陈旧统计表压成 0；现已改为优先按实时充值订单表聚合，只有缺少订单表时才回退 `site_daily_stats`。真实接口复测确认 Dashboard 已返回 `order_stats.month.total=1 / paid_orders=1 / amount=50`，与支付统计口径一致。

- [x] [2026-03-19] 八字首次免费链路与历史/分享闭环已恢复：phpstudy `http://localhost:8080` 下先复现 `GET /api/paipan/history?page=1&page_size=5` 命中 `Unknown column 'location' in 'field list'`，随后补做 `backend/app/model/BaziRecord.php` 的新旧 schema 兼容、`backend/app/controller/Paipan.php` 的积分流水兜底与公开分享/删除路由收口，并修正 `BaziCalculationService` 的负数取模索引异常；真实接口终验 `POST /api/paipan/bazi`、`GET /api/paipan/history`、`POST /api/paipan/set-share-public`、`GET /api/bazi/share`、`POST /api/paipan/delete-record` 均返回业务 JSON，测试记录删除后历史列表也已确认移除。同步新增 `database/20260319_fix_bazi_record_schema.sql`，并修正 `database/full_import_for_navicat.sql`、`database/backup/02_create_tables.sql` 的 `tc_bazi_record` 初始化定义。





