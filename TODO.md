# TODO（按定时任务类型分流）

## 使用规则
1. 巡检类 `30 / 30-3 / 30-4` 只负责发现、补证、去重、转单，不直接修复。
2. 修复类 `15 / 15-2 / admin / automation / automation-4` 只处理 `A. 高频修复队列` 下自己标题中的未完成条目；无匹配项立即退出，不再扫整份 TODO。
3. `automation-2 / ui / ui-15` 只看 `C. 低频专项 / 手动规划池`，默认低频或手动，不进入高频修复节奏。
4. 产品规划、新功能、视觉统一放在低频规划池，不作为高频修复输入。
5. 已完成项移入 `D. 最近已完成 / 已确认`，避免被自动化重复消费。

## A. 高频修复队列（修复类自动化直接消费）

### [15] 后端修复专家
- [ ] [高] 服务启动可能陷入重启循环：`database/20260318_fix_shensha_display_encoding.sql` 在 bootstrap 阶段回写已存在的 `福星贵人-guiren`，触发 `tc_shensha.uk_name_category` 唯一键冲突，导致八字、六爻、塔罗、合婚、每日运势整站级阻断。
- [ ] [高] 本地 8080 运行态仍被数据库连接配置阻断：`2026-03-19 05:55` 复测 `GET /api/health` 继续返回 `200`，但 `POST /api/auth/phone-login`（修正为真实 JSON 体后）仍在 `User::findByPhone()` 查询 `tc_user` 时抛出 `SQLSTATE[HY000] [1045] Access denied for user 'taichu'@'localhost'`；游客 `GET /api/daily/fortune` 也继续在 `DailyFortune::getToday()` 查询 `tc_daily_fortune` 时命中同一条 1045。为排除“只是登录坏了”的误判，本轮额外用临时 JWT 直探受保护入口：`GET /api/points/balance` 与 `GET /api/hehun/pricing` 同样直接回到 1045 错误页，`GET /api/liuyao/pricing` 则返回 `{"code":500,"message":"获取定价失败，请稍后重试"}`；结合 `Liuyao::getCurrentUserModel()` 首句即 `User::find($userId)`，说明六爻入口也先被同一数据库查询阻断。与此同时 `http://localhost/daily`、`http://localhost:5173/daily` 依旧连接拒绝，当前公开日运、登录转化、积分余额，以及合婚 / 六爻等占卜入口都无法继续进入扣费、历史、分享闭环验证。

- [ ] [高] 验证码发送接口 `POST /api/sms/send-code` 直接 500，因缺少 `tc_sms_code` 表导致登录前置流程卡死，游客无法正常进入八字、六爻、塔罗与合婚等需登录功能。

- [ ] [高] 塔罗 `draw/interpret` 已恢复，但 `save-record/history/share` 闭环疑似仍卡在旧 `tc_tarot_record` 结构：运行日志先后出现 `Field 'type' doesn't have a default value` 与 `Unknown column 'is_public' in 'field list'`，模型却固定写 `spread_type/is_public/share_code/view_count`，用户可能只能看当次解读，无法稳定保存、回看或生成分享链接。


### [15-2] 前端修复专家
- 当前暂无“已证实且可独立归因到前端”的高优修复项；由 `30-4` 继续补证后再入队，避免前端任务空转扫全站。

### [admin] 管理后台修复专家
- [ ] [高] phpstudy 直连后台登录仍被管理员主表缺失阻断：`2026-03-19 04:38` 复测 `GET http://localhost:8080/api/health` 返回 `code=200`，`POST http://localhost:8080/api/admin/auth/login`（表单 `username=admin&password=admin123`）继续返回 `{"code":500,"message":"管理员账号表不存在，请先执行 database/20260317_create_admin_users_table.sql"}`；代表性受保护接口 `GET /api/admin/dashboard/statistics`、`/api/admin/users`、`/api/admin/system/settings`、`/api/admin/system/notices` 均只返回 `401 未授权，请先登录`。页面侧仅确认 `http://localhost:3001/login` 仍会返回标题为“太初管理后台”的 Vite 壳页，而 `http://localhost:8080/admin` 与 `/admin/login` 依旧是 `404`；在拿不到后台 token 前，Dashboard、用户、内容、订单、积分、系统设置、公告等受保护运营链路本轮仍无法做真实可用性验证。



### [automation] 命理算法修复专家
- 当前暂无需继续独立推进的高优算法项；原“八字流年深度分析主链路失败”已转入 `D. 最近已完成 / 已确认`，当前 phpstudy 8080 下剩余的真实阻塞已归并到 `[15]` 的数据库 / 登录前置问题。
- [ ] [低] 每日运势四项解读文案仍偏固定模板，专业性不足；后续可结合当日干支、宜忌与用户喜用神提升口径。



### [automation-4] 跨模块闭环执行器
- [x] [2026-03-18] 八字流年深度分析积分链路已闭环：`YearlyFortuneService` 改为按用户隔离流年缓存并在缓存命中时回填当前余额；实测 2033 年分析成功返回结果且新增 `yearly_fortune -30`，2034 年在仅 10 积分时返回 `code 403` 且未新增扣费记录。
- [ ] [高] 八字大运 K 线图接口崩溃后仍扣 30 积分，异常分支缺少前后端失败承接与积分回滚闭环。
- [ ] [中] 前台关键结果页的“下一步动作”不统一，保存、深度分析、相关推荐、充值入口缺少固定动作槽，影响复购与继续转化。

## B. 高频巡检关注清单（巡检类只补证，不直接修复）

### [30] 网站逻辑检查任务
- [ ] [关注] 新接口契约不一致、异常分支误扣费、初始化 SQL / 启动脚本冲突、落库或历史写入断裂。
- [ ] [关注] 只补充“有证据的新问题”，不要再产出“整体良好 / 整体正常”式结论。

### [30-3] 后台运营体验检查
- [ ] [关注] 登录、Dashboard、菜单加载、内容管理、订单/积分查询、系统设置保存后刷新回读是否真实生效。
- [ ] [关注] 如果环境阻塞未解除，只记录阻塞范围与新增证据，不重复长写。

### [30-4] 占卜爱好者体验检查
- [ ] [关注] 登录前转化路径是否过长，测算 → 扣费 → 结果 → 历史是否闭环。
- [ ] [关注] 结果页 CTA、分享回流、移动端表单错误摘要是否明确，是否存在“看似成功但实际断链”。

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
- [ ] [体验] 首页 CTA 与结果页 CTA 的下一步动作不统一；建议统一结果页底部动作槽：保存、深度分析、相关推荐、充值入口。
- [ ] [体验] 积分消耗提示与用户预期仍有落差；建议补充“本次付费可获得内容清单”和失败保障说明。
- [ ] [体验] 历史记录入口分散，缺少统一“我的记录中心”；建议支持按服务类型筛选、对比与继续解读。
- [ ] [体验] 八字、合婚、六爻表单缺少提交后统一错误摘要；建议提供“错误摘要条 + 一键定位首个错误字段”。
- [ ] [体验] 全站异步请求的加载、成功、失败反馈不统一；建议封装通用状态组件并统一请求状态规范。
- [ ] [体验] 八字、合婚输入区的关键策略说明偏长且分散；建议改为“短说明 + 可展开详情”。
- [ ] [体验] 前后台切换时品牌与信息架构差异大；建议增加“正在进入管理端”过渡提示与返回前台入口。
- [ ] [体验] 八字、六爻、合婚结果页分享能力偏弱；建议抽象统一分享卡组件，支持脱敏摘要与回流参数。
- [ ] [体验] 登录、支付前、结果页缺少关键埋点；建议先覆盖曝光、点击、提交、失败、离开等核心事件。
- [ ] [体验] 六爻结果当前只给简写卦名和摘要，缺少变卦、动爻、六亲、六神、卦辞等结构化字段；建议补齐排盘展示，方便专业用户核验。
- [ ] [功能] 激活流年运势功能：`tc_yearly_fortune` 表已创建但代码仍未使用，后续需补前端入口、计算逻辑与 AI 深度分析。
- [ ] [功能] 激活取名功能：`tc_qiming_record` 表已创建但代码仍未使用，后续需补前端入口、八字五行取名逻辑与 AI 评测。
- [ ] [产品] Core 6：八字 / 合婚结果页做 Tab 化分层（本命局、性格内观、事业财富），提升单次交付厚度。
- [ ] [产品] Core 6：全站结果页增加“生成报告”动效与目录索引长文档，提升付费感知价值。
- [ ] [产品] Core 6：首页增加“年度运程”独立入口，把流年运势做成时效性引流位。
- [ ] [产品] Core 6：充值中心新增低门槛会员方案，用特权代替单纯卖分。
- [ ] [产品] Core 6：在 Loading、空状态、结果页底部加入“心法”文案层，降低纯工具感。
- [ ] [产品] Core 6：结果页增加“深度对话”入口，把单次结果变成可持续服务。

## D. 最近已完成 / 已确认
- [x] [2026-03-19] 八字流年深度分析“`HTTP 200 + code 500`”算法级主链路问题已从修复队列移出：已结合历史真实失败样本 `fortune_yearly.json`、后续 `automation-4` 对 `POST /api/fortune/yearly` 的成功 / 失败扣费闭环验证（2033 成功返回结果并正确扣 30 分，2034 在 10 积分时返回 `403` 且未新增扣费），以及当前工作区 `YearlyFortuneService / CacheService` 的缓存隔离、先分析后扣费、余额回填补丁复核，确认此前的算法级崩溃点已不再是当前独立待修问题；现阶段 8080 phpstudy 下剩余阻塞应并入 `[15]` 跟进的 MySQL `1045` / 登录前置问题，待环境恢复后再补一次真实回放。
- [x] [2026-03-18] 八字大运 K 线图 `float -> int` 类型错误已收口：`DayunFortuneService` 对 `scores['overall']` 新增统一 `normalizeScore()` 归一化，`analyzeDayun()` 与 `getDayunChartData()` 不再把浮点分数直接传入 `calculateYearScoreInDayun(int ...)`；结合既有真实报错样本 `fortune_dayun_chart.json`、当前代码路径复核与 `read_lints` 0 diagnostics，结果区空白这一算法级崩溃路径已被封死。受本机 phpstudy MySQL 凭据 `1045` 阻塞，尚未补做本轮 8080 真实接口回放。

- [x] [2026-03-18] 每日运势公共底盘随机分数/随机文案导致结果自相矛盾的问题已修复：`DailyFortune::generateFortune()` 改为基于黄历干支、值日、宜忌的确定性生成，并在读取今日记录时自动修复旧的随机样本；`2026-03-18` 接口复测已不再出现“高分却给低档文案”。

- [x] [2026-03-18] 塔罗 `POST /api/tarot/interpret` 全量失败问题已解除：当前 `backend/app/controller/Tarot.php` 已正确引入 `use app\service\TarotElementService;`，接力前一次真实复测中 `single / three / celtic` 的 `draw + interpret` 均已成功；后续主阻塞已转移到 `save-record/history/share` 闭环。
- [x] [2026-03-18] 六爻“返回 200 但 `id = null`、`/api/liuyao/history` 全空”不再是当前主阻塞：接力前一次真实复测中 `POST /api/liuyao/divination` 已返回有效 `id = 4`，`GET /api/liuyao/history` 也已有记录；当前遗留问题改为历史持久化字段缩水。
- [x] [2026-03-18] 六爻历史回读字段缩水的代码侧根因已修复：`backend/app/controller/Liuyao.php` 现会把 `time_info / gong / shi_ying / liuqin / liushen / yong_shen` 一并快照进 `hexagram_original`，历史回读也会优先使用快照并对旧记录按 `created_at + yao_code` 兜底重建 `ri_gan / yue_jian / xunkong / line_details`；本轮受第 `[15]` 条本机 MySQL 凭据阻塞影响，未能完成 8080 登录态真实接口回放，先以代码路径复现 + IDE 诊断 + `git diff --check` 做最小闭环。





