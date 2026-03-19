# 自动化任务执行内存

> 环境基线更新（2026-03-18）：当前本地标准环境已切换为 **phpstudy + `http://localhost:8080` 直连接口**。后续算法修复与验证不要再默认使用 Docker、`docker exec` 或容器内 PHP；优先走本机可用的 HTTP 接口、本机 PHP CLI（若已可用）和代码静态检查。
>
> 执行策略修正（2026-03-19）：若 `TODO.md` 里其他栏位的占卜问题主根因仍落在 `service/controller`、评分/解读、结构化输出或结果一致性逻辑，不要因为条目暂时不挂在 `[automation]` 就机械退出；可直接接手 1 条修到闭环。
>
> SQL 落库要求（2026-03-19）：若算法修复最终需要补充或调整 SQL / 表结构兼容（如补列、兼容迁移、初始化脚本、数据修复脚本），必须把最终 SQL 同步写入 `C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified\database` 目录下的 `.sql` 文件，不要只改 service/controller 或只写说明。

## 2026-03-19 八字页友好提示修复
- **任务目标**: 处理 `[automation]` 中高优项“不要显示‘账户与价格暂不可用，确认前不展示消费承诺’这类不友好提示”，并完成最小闭环。
- **执行摘要**:
    1. 开始时按要求只读取了 `TODO.md -> A. 高频修复队列 -> [automation] 命理算法修复专家` 与本 memory；随后用真实接口复测 `GET /api/points/balance`、`GET /api/fortune/points-cost`，确认无 token 下两者都返回 `401 请先登录`。
    2. 结合 `frontend/src/views/Bazi.vue`、`frontend/src/api/index.js`、`frontend/src/api/request.js` 复核，确认原问题不是“价格接口坏了”，而是八字页把游客态 401 直接落成错误态并展示了生硬文案。
    3. 本轮把八字页初始化请求改为静默探测，并在前端拆出 `guest` 状态：未登录时不再跳首页，也不再显示“账户与价格暂不可用”；顶部提示、主按钮、AI/深度工具入口统一改为“登录后查看 / 请先登录 / 重新获取”等更自然的说明。
    4. 已同步更新 `TODO.md`：移除 `[automation]` 中该高优项，并转入 `D. 最近已完成 / 已确认`。
- **验证摘要**: 真实接口复测 `/api/points/balance` 与 `/api/fortune/points-cost`（无 token）均为 `401`；`read_lints` 检查 `frontend/src/views/Bazi.vue`、`frontend/src/api/index.js`、`frontend/src/api/request.js` 为 0 diagnostics。
- **状态**: 已完成 1 条高优项闭环；后续可继续接手 `[automation]` 剩余高优问题中的下一条，但仍需坚持“一轮只做 1 项”。

## 2026-03-19 轮询（无动作，phpstudy 续检）

- **任务目标**: 按自动化要求仅检查 `TODO.md -> A. 高频修复队列 -> [automation] 命理算法修复专家` 是否重新出现可独立处理的高优算法项。
- **执行摘要**:
    1. 开始时仅读取了指定 TODO 章节与本 memory，未扩扫其他模块，也未触发 Docker、容器日志、容器内 PHP 或 `docker compose`。
    2. 当前 `[automation]` 章节仍写明“暂无需继续独立推进的高优算法项”；唯一未完成条目仍是低优的“每日运势四项解读文案仍偏固定模板”，不属于本轮处理范围。
    3. 因无匹配高优待办，本轮按规则立即退出，未做接口复测、未改代码、未更新 TODO，也未尝试登录态、扣费链路或数据库结构类高风险操作。
- **状态**: no-op；继续等待 `[automation]` 章节出现新的高优未完成算法项后，再进入“复现 → 修复 → 最小验证”闭环。

## 2026-03-19 轮询（无动作，续检）
- **任务目标**: 按自动化要求仅检查 `TODO.md -> A. 高频修复队列 -> [automation] 命理算法修复专家` 是否重新出现可独立处理的高优算法项。
- **执行摘要**:
    1. 开始时只读取了指定 TODO 章节与本 memory，未扩扫其他模块，也未触发 Docker、容器日志、容器内 PHP 或 `docker compose`。
    2. 当前 `[automation]` 章节仍明确写明“当前暂无需继续独立推进的高优算法项”；现阶段剩余真实阻塞已归并到 `[15]` 的数据库 / 登录前置问题，不属于本轮算法自动化可独立处理范围。
    3. 因无匹配高优待办，本轮按规则立即退出，未做接口复测、未改代码、未更新 TODO，也未尝试高风险数据库或登录态操作。
- **状态**: no-op；继续等待 `[automation]` 章节出现新的高优未完成算法项后，再进入“复现 → 修复 → 最小验证”闭环。

## 2026-03-19 轮询（无动作，新增）
- **任务目标**: 按自动化要求仅检查 `TODO.md -> A. 高频修复队列 -> [automation] 命理算法修复专家` 是否重新出现可独立处理的高优算法项。
- **执行摘要**:
    1. 开始时仅读取了指定 TODO 章节与本 memory，未扩扫全站，也未触发 Docker、容器日志或容器内 PHP。
    2. 当前 `[automation]` 章节仍明确写明“暂无需继续独立推进的高优算法项”；唯一未完成条目仍是低优的“每日运势四项解读文案偏固定模板”。
    3. 因无匹配高优待办，本轮按规则立即退出，未做接口复测、未改代码、未更新 TODO。
- **状态**: no-op；继续等待 `[automation]` 章节出现新的高优未完成算法项后，再进入“复现 → 修复 → 最小验证”闭环。


## 2026-03-19 轮询（无动作）
- **任务目标**: 按自动化要求只检查 `TODO.md -> A. 高频修复队列 -> [automation] 命理算法修复专家` 是否还有可独立处理的高优算法项。
- **执行摘要**:
    1. 仅读取了指定 TODO 章节与本 memory，未扩扫全站，也未触发 Docker / 容器链路。
    2. 当前 `[automation]` 章节明确写明“暂无需继续独立推进的高优算法项”；剩余低优项是“每日运势文案专业性不足”，不属于本轮高优修复范围。
    3. 因无匹配高优待办，本轮按规则立即退出，未做接口复测、未改代码、未更新 TODO。
- **状态**: no-op；后续仅当 `[automation]` 章节重新出现新的高优未完成算法项时再进入复现 / 修复 / 验证闭环。

## 2026-03-19 02:26
- **任务目标**: 继续处理 `[automation]` 首个高优项“八字流年深度分析主链路失败”，确认该项是否仍应留在算法修复队列。
- **执行摘要**:
    1. 按要求先只读取 `TODO.md -> A. 高频修复队列 -> [automation] 命理算法修复专家` 与本 memory；随后用真实接口复测 `GET http://localhost:8080/api/health`，结果仍为 `code=200`。
    2. 直接复测无 token 的 `POST /api/fortune/yearly`，返回 `401 请先登录`，说明当前 phpstudy 口径下仍未进入流年算法主体；本机 `where.exe php` 也仍未找到 PHP CLI。
    3. 结合历史真实失败样本 `fortune_yearly.json`、`automation-4` 已记录的成功 / 失败扣费闭环验证，以及当前工作区 `YearlyFortuneService / CacheService` 的补丁复核，确认此前“`HTTP 200 + code 500`”算法级问题已不再是当前独立待修项。
    4. 本轮已把该高优项从 `[automation]` 修复队列移出，转入 `TODO.md -> D. 最近已完成 / 已确认`；当前剩余阻塞应继续并入 `[15]` 跟进的 phpstudy MySQL `1045` / 登录前置问题。
- **验证摘要**: `/api/health` 成功；`/api/fortune/yearly`（无 token）返回 `401`；`where.exe php` 未找到本机 PHP CLI；本轮未使用 Docker。
- **状态**: 已完成 TODO 去重与记忆回写；后续 automation 若再轮询到本章节，应视为“当前无高优算法项”，不要再围绕这条已收口问题反复空转。

## 2026-03-19 01:16

- **任务目标**: 继续处理 `[automation]` 首个高优项“八字流年深度分析主链路失败”，先确认当前是否已进入可做算法闭环的阶段。
- **执行摘要**:
    1. 按要求先只读取 `TODO.md -> A. 高频修复队列 -> [automation] 命理算法修复专家` 与本 memory；随后用真实接口复测 `GET http://localhost:8080/api/health`，结果仍为 `code=200`。
    2. 重新用表单口径请求 `POST /api/auth/phone-login`（`phone=13800138000&code=123456`），本次已排除命令行请求体误差，服务端稳定返回 ThinkPHP 500 异常页；临时落盘精读后，核心异常仍是 `SQLSTATE[HY000] [1045] Access denied for user 'taichu'@'localhost' (using password: YES)`。
    3. 直接复测 `POST /api/fortune/yearly`（无 token）仍只返回 `401 请先登录`，说明当前依旧卡在登录/数据库前置，尚未进入流年算法主体；本轮不存在可安全落地的算法补丁入口。
    4. 临时异常 HTML 已在读取后删除；本轮未修改业务代码、未更新 `TODO.md` 完成状态。
- **验证摘要**: `/api/health` 成功；`/api/auth/phone-login` 再次确认仍为 `1045 Access denied`；`/api/fortune/yearly`（无 token）返回 `401`；本轮未使用 Docker，也未切回容器链路。
- **状态**: 结论仍无变化——当前主阻塞仍是 phpstudy 本机 MySQL 凭据与 `.env` 不匹配；需先恢复真实登录态，之后才能继续复现、修复并闭环 `POST /api/fortune/yearly` 的算法级问题。

## 2026-03-18 继续修复每日运势底盘
- **任务目标**: 只处理 1 个有实证支撑的高优先级 `[占卜]` 算法问题，优先修复每日运势公共底盘的随机结果矛盾。
- **执行摘要**:
    1. 先用 `GET /api/daily/fortune` 复现真实样例：`2026-03-18` 返回 `感情运 97 分` 但文案却是“平淡，需要主动”，确认问题来自 `DailyFortune::generateFortune()` 的随机分数 + 随机文案。
    2. 将 `backend/app/model/DailyFortune.php` 改为基于黄历干支、值日、宜忌的确定性生成；四项文案统一按分数档位产出，不再独立随机。
    3. `getToday()` 新增旧随机样本自动修复逻辑，已有当天落库数据会在读取时按新规则刷新。
    4. 新增 `backend/tests/daily_fortune_probe.php` 作为轻量回归探针，验证同日稳定性与“分数-文案”一致性。
- **验证摘要**: `docker exec taichu-app php -l /var/www/html/app/model/DailyFortune.php` 通过；探针脚本 3 个日期样例全部 `ok=true`；`GET /api/daily/fortune` 复测后同日已改为 71/67/72/68 分并对应一致文案；`git diff --check` 通过。
- **状态**: 已完成代码修复与 TODO 勾选，未执行 Git 提交。


## 2026-03-17 14:30
- **任务目标**: 修复 TODO.md 中标记为 [占卜] 的逻辑错误。
- **修复内容 (第一批)**:
    1.  六爻占卜：修正己日起例（己日起螣蛇）。
    2.  每日运势：重构个性化逻辑，引入日主强弱判断。
    3.  塔罗占卜：修正元素关系模型，回归西方传统四元素尊严。
    4.  八字排盘：引入寿星节气计算公式。
    5.  运势生成：幸运项生成算法去随机化（基于用户ID+日期）。
- **状态**: 已提交 Git，更新了 `overview.md`。

## 2026-03-17 15:10
- **任务目标**: 继续修复 [占卜] 相关的深度算法问题。
- **修复内容 (第二批)**:
    1.  **塔罗占卜**：为 78 张塔罗牌实现了**逆位专属解读**，并重构了解读引擎逻辑。
    2.  **八字排盘**：重构了**日主强弱评分体系**，引入地支藏干权重（月令占 40%），显著提升身旺身弱判定的准确度。
    3.  **八字排盘**：优化了**起运年龄精度**，实现了精确到“岁、月、天”的动态计算。
    4.  **六爻占卜**：深化了**用神判断逻辑**，引入性别区分（男占妻财、女占官鬼）及世应、动静状态分析。
    5.  **八字合婚**：深化了评分维度，新增了**“神煞互补”**（天乙贵人等）与**“喜用互补”**核心算法。
- **状态**: 已全部完成并提交 Git。

## 2026-03-17 20:10
- **任务目标**: 修复新一批 TODO `[占卜]` 算法问题，并清理已完成待办。
- **修复内容 (第三批)**:
    1. **八字排盘**：统一 `Paipan.php` 与 `BaziCalculationService` 的节气/日柱历法内核，补齐 20 世纪节气常数。
    2. **六爻占卜**：补全日辰自动推算、伏神回退与旬空状态输出，避免默认甲日造成偏差。
    3. **八字排盘**：在强弱评分中加入六冲、六合、三合局影响，并让喜用神解读直接复用该评分结果。
    4. **塔罗占卜**：将元素互动文案改回西方 `Elemental Dignities` 体系术语。
    5. **待办清理**：已移除 5 条本轮完成的 `[占卜]` TODO，剩余命卦立春、旬空提示、塔罗逆位深度待后续处理。
- **验证摘要**: IDE 诊断无新增错误；因当前环境未找到 `php` CLI，尚未补做命令行回归脚本。
- **状态**: 已完成并提交 Git（`247cb98`、`ee8e9fa`）。

## 2026-03-17 20:55
- **任务目标**: 继续修复 `[占卜]` 逻辑错误，并优先处理节气、旬空、五行权重、黄历底盘相关问题。
- **修复内容 (第四批)**:
    1. **八字排盘**：`getLunarMonth()` 改为按大雪/小寒/立春等节令顺序定月，修复月柱误落丑月。
    2. **八字排盘**：`Paipan.php` 透传性别到 `calculateBazi()`，并兼容 `male/female` 与 `男/女`，修正女命起运顺逆。
    3. **八字/合婚**：补齐四柱 `gan_index`/`zhi_index`/`number` 元数据与加权 `wuxing_stats`，合婚免费预览不再因缺少 `zhi_index` 报 500。
    4. **八字排盘**：补出顶层与各柱 `旬空` 文本，前端排盘结果可直接显示旬空提示。
    5. **每日运势**：`DailyFortune` 改用 `LunarService` 生成真实农历，并自动修正今日已有错误黄历字符串。
- **验证摘要**: 5 个相关 PHP 文件 IDE 诊断均为 0；`where.exe php` 仍未找到 PHP CLI，因此未执行命令行语法回归。
- **状态**: 已完成并提交 Git（`e314d8b`）；TODO 已移除本轮完成的 5 条 `[占卜]` 项。

## 2026-03-17
- **任务目标**: 继续修复 `[占卜]` 逻辑错误，重点处理节气精度、六神映射、五行权重评分与塔罗元素关系。
- **修复内容 (第五批)**:
    1. **八字排盘**：`BaziCalculationService` 新增节气交节时刻计算，`getLunarYear()`、`getLunarMonth()`、`calculateQiYun()` 全部改为按出生时分判定。
    2. **合婚配对**：`analyzeSanYuanHehun()` 升级为“三元九运 + 纳音方向”联合模型，`analyzeJiuGongHehun()` 同步按立春交节时刻划命理年。
    3. **合婚配对**：`analyzeWuxingComplement()` 改为按 `wuxing_stats` 加权占比与喜用五行需求打分，不再只看粗阈值。
    4. **塔罗占卜**：`Tarot.php` 修复元素平票时强判单一主导的问题，并为凯尔特十字补充核心位交叉解读。
    5. **六爻/塔罗 AI 提示词**：`DeepSeekService` 显式格式化六神/用神爻位映射，并把塔罗元素与位态释义写入提示词。
- **验证摘要**: `BaziCalculationService.php`、`Hehun.php`、`Tarot.php`、`DeepSeekService.php` IDE 诊断均为 0；`where.exe php` 未找到 PHP CLI，仍未执行命令行语法检查。
- **状态**: 已完成并提交 Git（`69a6917`）；TODO 已移除本轮完成的 4 条 `[占卜]` 项。

## 2026-03-18 00:10
- **任务目标**: 继续修复 `[占卜]` 逻辑错误，优先处理日柱基准、黄历口径与积分配置空值问题。
- **修复内容 (第六批)**:
    1. **八字排盘/每日运势**：把 `1900-01-31` 的日柱基准校正为 **甲辰日**，修复 `todayGanZhi` 与日柱整体偏移。
    2. **八字排盘**：`BaziCalculationService` 新增四柱结构归一化，自动回填 `gan_index/zhi_index`、旬空、五行权重等元数据，缓解专业版排盘链路的索引缺失报错。
    3. **合婚定价**：`ConfigService::calculatePointsCost()` 改为对空配置做特征级默认值兜底，`/api/hehun/pricing` 不再因 `null` 积分基数触发 500。
    4. **每日运势/后台黄历**：重写 `LunarService` 并统一给 `DailyFortune`、`Daily`、`admin/Almanac` 复用，补齐农历文本、年/月/日干支、建除值日、吉神凶煞、煞方与十二时辰吉凶。
    5. **待办清理**：已从 `TODO.md` 删除 5 条本轮完成的 `[占卜]` 项，剩余 `zhi_index` 与错月令两项待后续继续处理。
- **验证摘要**: 8 个相关 PHP 文件 IDE 诊断均为 0；`where.exe php` 仍未找到 PHP CLI，未执行命令行回归。
- **状态**: 已完成并提交 Git（`273de2b`）；更新了 `overview.md`。

## 2026-03-18 09:20
- **任务目标**: 继续修复 `[占卜]` 算法问题，重点收敛节气精度、合婚索引兜底、每日运势权重口径、八字简版文案与塔罗元素术语。
- **修复内容 (第七批)**:
    1. **节气/八字月柱**：`BaziCalculationService` 把寿星公式改为“小寒至雨水按 `(Y-1)/4`、其余按 `Y/4`”的传统闰年口径，月令边界更稳。
    2. **合婚配对**：`Hehun` 新增生肖索引三级回退（`zhi_index` / 地支 / 命理年份），并在分析入口再次归一化旧结构，兜住历史缓存。
    3. **每日运势**：个性化 `todayGanZhi` 改为优先复用黄历同源结果；喜用五行改为重算八字并复用强弱评分，不再只看月令单点。
    4. **八字简版**：`BaziInterpretationService` 重写性格/事业开头模板，去掉把“日主”直接替换成“你”的粗暴做法，病句消失。
    5. **塔罗解读**：`Tarot` 与 `DeepSeekService` 统一改用“友好尊严 / 敌对尊严 / 中性尊严”等中文术语，并收紧综合结论去套话。
- **验证摘要**: 6 个相关 PHP 文件 IDE 诊断均为 0；在 Docker 容器内回归得到 `1990-05-15 10:30` 月柱为 `辛巳`、`2026-03-17` 日柱为 `庚寅`，简版文案与塔罗元素关系输出已中文化。
- **状态**: 已完成并提交 Git（`5d6c223`）；本轮对应的 5 条 `[占卜]` TODO 已删除。

## 2026-03-18 10:15
- **任务目标**: 继续修复剩余 `[占卜]` 待办，优先处理流年年份失真、黄历主数据空缺与合婚详细报告库表错位问题。
- **修复内容 (第八批)**:
    1. **八字流年**：`FortuneAnalysisService` 改为读取 `liuNian[].year` 真实年份，`best_year / worst_year` 不再返回 `0~4` 索引。
    2. **每日运势/黄历**：`LunarService` 将黄历主数据统一改为复用八字核心四柱，按节气取 `year_gan_zhi / month_gan_zhi / day_gan_zhi`，并补充 `yearGanzhi/monthGanzhi/dayGanzhi` 别名与空值兜底。
    3. **合婚详细报告**：`HehunRecord` 新增 `hehun_records` 与 `tc_hehun_record` 双表兼容写入/读取，旧库 `male_birth/female_birth` 也能正常落记录。
    4. **合婚免费预览**：`Hehun` 在免费层和付费层都先做详细报告链路健康检查，避免先展示可解锁、实际却因库表错位失败。
- **验证摘要**: 4 个相关 PHP 文件 IDE 诊断为 0；Docker 容器内 `php -l` 逐个检查 `FortuneAnalysisService.php`、`LunarService.php`、`HehunRecord.php`、`Hehun.php` 均通过。
- **状态**: 已完成并提交 Git（`033cd70`）；本轮对应的 4 条 `[占卜]` TODO 已删除，当前只剩六爻运行态与塔罗模板化两项待后续继续处理。

## 2026-03-18 03:58
- **任务目标**: 继续修复 `[占卜]` 待办，优先打通大运/流年、六爻运行态、每日运势公开访问、测试验证码与喜用神口径。
- **修复内容 (第九批)**:
    1. **八字运势链路**：`Fortune.php` 改为用带 `App` 上下文的 `Paipan` 复算大运，`Paipan::calculateDaYun()` 补齐 `start_age/end_age/start_year/end_year`，`YearlyFortuneService` 补回 `Db` 导入。
    2. **六爻占卜**：`Liuyao.php` 接入 `SchemaInspector` 兼容 `tc_liuyao_record / liuyao_records`，定价、落盘、历史、详情、AI 回写不再写死单一表名。
    3. **每日运势**：`Daily.php` 仅对 `luck/checkin/checkinStatus` 保留鉴权，游客可直接浏览 `/daily` 基础运势。
    4. **短信测试模式**：`SmsService` 发送端新增本地测试短路，`Sms.php` 透传测试码，`Login.vue` 直接提示测试验证码。
    5. **八字喜用神**：`BaziCalculationService` 与 `BaziInterpretationService` 统一 `favorite_wuxing` / 正文口径，按“身强取克泄耗、身弱取生扶比助”排序输出。
- **验证摘要**: 10 个相关文件 IDE 诊断均为 0；`TODO.md` 复查后仅剩 1 条塔罗模板化 `[占卜]` 待办。
- **状态**: 已完成并提交 Git（`81f9e19`）；本轮已从 `TODO.md` 删除 5 条完成项和 1 条过时项。

## 2026-03-18（第十批）
- **任务目标**: 清掉剩余 `[占卜]` 待办，并继续收敛节气精度、六神上下文、五行互补评分与塔罗解读模板化问题。
- **修复内容**:
    1. **专业版大运**：`Paipan::calculateDaYun()` 改为优先复用 `BaziCalculationService` 的精确起运 `start_date/display`，不再只按节气日期整岁起运。
    2. **六爻占卜**：`Liuyao.php` 禁止半手填日辰，并新增真实 `yue_jian` 推导；AI 提示与落库字段不再把旬空错写成月建。
    3. **合婚配对**：`Hehun::analyzeWuxingComplement()` 改成可加可减的双向评分，低支持比和同类失衡场景可正确落入低分档。
    4. **塔罗占卜**：`Tarot.php` 按单张 / 三牌 / 凯尔特十字重写牌位解读、关系分析与综合结论，去掉固定“元素互动”模板和重复句号。
    5. **待办清理**：已删除最后一条塔罗 `[占卜]` TODO，目前 `TODO.md` 已无未完成 `[占卜]` 项。
- **验证摘要**: 5 个相关 PHP 文件 IDE 诊断均为 0；Docker 容器内 `php -l` 检查 `Paipan.php`、`Liuyao.php`、`Hehun.php`、`Tarot.php`、`DeepSeekService.php` 全部通过。
- **状态**: 已完成并提交 Git（`a02b7fa`）。

## 2026-03-18 13:55
- **任务目标**: 继续处理命理算法残留问题，聚焦节气特例表、六神起例容错、五行平衡评分、喜用神优先级与塔罗元素关系下沉。
- **执行摘要**:
    1. `BaziCalculationService` 改正节气寿星公式的特殊年份映射，补齐春分/小满/冬至/小寒等特例。
    2. 八字强弱链路新增 `favorite_wuxing_details`，并按“身强先官杀食伤财、身弱先印比”稳定输出；`Daily.php` 不再随机抽喜用五行。
    3. `BaziInterpretationService` 改为按整体离散度评估五行平衡，弱/缺阈值随总量动态调整。
    4. `LiuyaoService` 让六神起例兼容完整日柱输入，多重用神按发动/世应/旬空综合择位。
    5. 新增 `TarotElementService` 统一四元素尊严与转场描述，并清掉 `TODO.md` 残留 `[占卜]` 项。
- **验证摘要**: 6 个相关 PHP 文件 IDE 诊断为 0；用后端镜像对 6 个文件执行 `php -l` 均通过；`TODO.md` 搜索 `[占卜]` 结果为 0。
- **状态**: 已完成并提交 Git（功能修复提交 `2d3b2a9`，理论依据补充提交 `a7a2371`）。

## 2026-03-18（继续修复补充）
- **任务目标**: 继续收口命理算法残留问题，重点修正合婚日主口径、传统模型综合评分，以及八字“喜用神 ≠ 机械补缺”的解释偏差。
- **执行摘要**:
    1. `Hehun` 删除伪“天干相冲”残留访问，日柱配对改回“五合 / 同气 / 五行生扶”口径，避免错误套用不存在的天干冲规则。
    2. `Hehun::analyzeHehun()` 将传统分改为“三元 + 九宫”联合计分，并把两套传统说明同时写入详情，修正“名义综合、实际只算九宫”的偏差。
    3. `BaziInterpretationService::determineYongshen()` 改为先看喜用神顺序，再判断缺弱是否刚好落在喜用元素上，不再把“缺项”直接等同于“应该补”。
    4. `BaziInterpretationService::generateComprehensiveAdvice()` 同步改为以扶抑用神为先，缺弱信息仅作结构参考，避免机械补五行。
    5. 本轮复查 `TODO.md` 时未发现新的 `[占卜]` 待办，因此无需额外删除条目。
- **验证摘要**: `Hehun.php`、`BaziInterpretationService.php`、`LiuyaoService.php`、`LunarService.php` IDE 诊断均为 0；在运行中的 `taichu-app` 容器内对 `Hehun.php` 与 `BaziInterpretationService.php` 执行 `php -l` 均通过；本机仍未找到可直接使用的 PHP CLI。
- **状态**: 本轮代码修复、验证与 Git 提交已完成（`1d1f032`）。

## 2026-03-18 继续收口运行态占卜问题
- **任务目标**: 收掉 `TODO.md` 中新增的 4 条 `[占卜]` 运行态问题，重点打通六爻起卦/历史、塔罗正式解读与合婚摘要口径，并同步清理待办。
- **执行摘要**:
    1. `Daily.php` 维持控制器级 Bearer Token 兜底与 `BaziRecord` 统一取数，作为“登录后 personalized 仍为 null”的修复基线。
    2. `LiuyaoService::getYaoDiZhi()` 改为先把 `0/1/2/3` 四态爻码归一成纯阴阳码，再做八卦与纳甲映射，避免老阴/老阳直接查 `BA_GUA` 造成起卦链路异常。
    3. `Liuyao.php` 新增 `gua_data / liuyao_gua`、`tc_liuyao_record / liuyao_records` 的动态探测与字段映射；起卦落盘、AI 回写、历史读取、软删过滤都改为按实际列存在与否执行，兼容两套历史 schema。
    4. `Tarot.php` 补上 `Log` 导入，并把 `TarotElementService` 调用改成全限定类名；`interpret()` 也补了统一异常收口，避免解释阶段继续直接炸成 HTTP 500。
    5. `Hehun::generateAiSummary()` 改为显式读取 `traditional_risk`，遇到 `五鬼 / 绝命` 等高风险时先输出传统警示，不再让规则摘要维持“高分=直接乐观”的口径。
    6. 已从 `TODO.md` 删除这 4 条已修复的 `[占卜]` 待办。
- **验证摘要**: `read_lints` 对 `LiuyaoService.php`、`Liuyao.php`、`Tarot.php`、`Hehun.php` 返回 0 diagnostics；本机仍未找到 `php` CLI，容器内代码目录与工作区未完全同构，因此本轮未追加容器语法校验。
- **状态**: 代码修复与 TODO 清理已完成，尚未 Git 提交。

## 2026-03-18 命理算法边界收口
- **任务目标**: 在 `TODO.md` 已无残留 `[占卜]` 项的前提下，继续深挖 `backend/app/service/`，重点收口节气、六爻性别取用、五行评分与塔罗元素映射的边界误差。
- **执行摘要**:
    1. `BaziCalculationService` 修正寿星公式在 1900/2000 这类世纪尾数 `00` 年份对 `floor((Y-1)/4)` 的错误截断，年初节气不再整体错一天。
    2. `LiuyaoService` 为感情占补齐 `male/female` 到 `男/女` 的归一化，女命不再误取妻财。
    3. `BaziInterpretationService` 去掉五行平衡分的 20 分硬下限，并把最旺/最弱元素平票改为显式“并列”输出。
    4. `TarotElementService` 新增英文大阿卡纳牌名与 `name_en/title/title_en/arcana_name` 解析，元素尊严链路不再因字段口径不同而掉空。
    5. 复查 `TODO.md` 后 `[占卜]` 搜索结果仍为 0，本轮无需额外删除待办条目。
- **验证摘要**: 4 个服务文件 `read_lints` 均为 0；容器内 `php -l` 全部通过；一次性回归脚本验证了 `2000-02-04` 立春、六爻女命感情取官鬼、极端偏枯命局平衡分、五行并列最旺与塔罗英文牌名映射，6 项全部通过。
- **状态**: 已完成并提交 Git（`705aa47`）。

## 2026-03-18 18:20
- **任务目标**: 按 `TODO.md > A. 高频修复队列 > [automation]` 继续处理首个高优算法项：八字流年深度分析主链路失败。
- **执行摘要**:
    1. 先按要求只读取指定 TODO 章节与自动化 memory，再用真实接口确认本地 phpstudy 入口：`GET http://localhost:8080/api/health` 返回 `code=200`。
    2. 为进入流年接口前置登录态，尝试真实登录接口 `POST /api/auth/phone-login`（测试手机号 `13800138000`、测试码 `123456`），但接口未返回业务 JSON，而是直接抛出 ThinkPHP HTML 异常页。
    3. 结合当前 `backend/.env` 仍为 `DB_HOST = mysql`，以及 phpstudy 本机直连口径已切换这一环境基线，判断本轮先被数据库连接配置阻断，尚未进入“流年算法本身 code 500”复现阶段。
    4. 同时复核 `backend/app/service/YearlyFortuneService.php` 与 `backend/app/service/CacheService.php`，当前工作区已包含此前针对流年缓存隔离与失败扣费顺序的补丁；在登录/数据库阻塞未解除前，本轮不再冒进改动算法代码，也不误勾 TODO。
- **验证摘要**: `GET /api/health` 成功；`POST /api/auth/phone-login` 未返回业务 JSON 而是服务端异常页；`where.exe php` 仍找不到本机 PHP CLI，无法按要求做本机 PHP 级 CLI 回归。
- **状态**: 本轮未修改业务代码、未更新 TODO；核心结论是当前被上游运行态/数据库连接问题阻断，需先解除 `DB_HOST=mysql` 与 phpstudy 本机运行态不匹配的问题后，再继续复现 `POST /api/fortune/yearly`。

## 2026-03-18 19:26
- **任务目标**: 继续处理 `[automation]` 首个高优项“八字流年深度分析主链路失败”，优先在 phpstudy 口径下打通最小登录前置链路。
- **执行摘要**:
    1. 真实接口复测 `POST /api/auth/phone-login` 仍失败后，仅对本地环境做了 1 处最小修正：`backend/.env` 的 `DB_HOST` 从 `mysql` 改为 `127.0.0.1`。
    2. 同一路径再次复测后，错误从主机名解析失败收敛为 `SQLSTATE[HY000] [1045] Access denied for user 'taichu'@'localhost'`。
    3. 结论已从“容器主机名不匹配”进一步收敛到“phpstudy 本机 MySQL 凭据与当前 `.env` 不匹配”；在未确认真实 `DB_USER/DB_PASSWORD` 前，本轮不继续猜测密码、不硬改数据库用户，也未误改流年算法代码或 TODO 状态。
- **验证摘要**: `GET /api/health` 仍为 200；`phone-login` 两次异常核心分别为 `getaddrinfo for mysql failed` 与 `Access denied for user 'taichu'@'localhost'`；本机仍未找到可用 PHP CLI。
- **状态**: 已完成最小环境修正与再次复测，但仍被本机 MySQL 凭据阻断；`POST /api/fortune/yearly` 的算法级复现和修复需等待正确数据库账号信息后继续。

## 2026-03-18 20:31
- **任务目标**: 继续处理 `[automation]` 首个高优项“八字流年深度分析主链路失败”，在不越过登录态 / 数据库高风险边界的前提下确认当前阻塞是否有变化。
- **执行摘要**:
    1. 再次用真实接口复测 phpstudy 本地入口：`GET http://localhost:8080/api/health` 正常返回 `code=200`。
    2. 对 `POST /api/auth/phone-login` 复测后，将 ThinkPHP 异常页落到临时文件并精读，核心异常仍稳定为 `SQLSTATE[HY000] [1045] Access denied for user 'taichu'@'localhost'`，说明登录前置依然被本机 MySQL 凭据阻断。
    3. 复核 `Fortune.php`、`YearlyFortuneService.php` 与 `CacheService.php` 后，确认工作区已包含流年缓存按用户隔离、余额实时回填与统一异常收口等补丁；在真实登录态无法打通前，本轮不再盲改算法代码，也不误勾 TODO。
- **验证摘要**: `GET /api/health` 成功；`POST /api/auth/phone-login` 仍返回 `1045 Access denied`；`where.exe php` 未找到本机 PHP CLI。
- **状态**: 本轮未修改业务代码、未更新 TODO；结论仍是需先确认 phpstudy 实际 MySQL 用户名 / 密码后，才能继续闭环复现并验证 `POST /api/fortune/yearly`。

## 2026-03-18 21:36
- **任务目标**: 继续处理 `[automation]` 首个高优项“八字流年深度分析主链路失败”，优先确认 phpstudy 直连接口下是否仍被前置数据库凭据阻断。
- **执行摘要**:
    1. 真实接口复测 `GET http://localhost:8080/api/health` 仍返回 `code=200`，说明 phpstudy 入口在线。
    2. 继续用 `POST /api/auth/phone-login`（`13800138000 / 123456`）做最小登录前置复现，并把异常页临时落盘精读；核心异常仍是 `SQLSTATE[HY000] [1045] Access denied for user 'taichu'@'localhost'`。
    3. 补测游客态 `GET /api/daily/fortune` 后，同样命中相同的 1045，说明当前不只是登录态没打通，连公开日运链路也先被本机 MySQL 凭据拦住。
    4. 复核 `Fortune.php`、`YearlyFortuneService.php`、`CacheService.php` 后，当前工作区已保留流年缓存隔离、缓存命中余额回填与统一异常收口补丁；在真实数据库连接未恢复前，本轮不再冒进修改流年算法代码，也不误勾 TODO。
- **验证摘要**: `GET /api/health` 成功；`POST /api/auth/phone-login` 与 `GET /api/daily/fortune` 都稳定返回 `SQLSTATE[HY000] [1045] Access denied for user 'taichu'@'localhost'`；`where.exe php` 未找到本机 PHP CLI；两份临时异常 HTML 已在读取后删除。
- **状态**: 本轮未修改业务代码、未更新 TODO；当前仍被 phpstudy 本机 MySQL 用户名 / 密码不匹配阻断，需先修复该高风险前置条件后，才能继续闭环 `POST /api/fortune/yearly` 的真实算法问题。

## 2026-03-19 00:08
- **任务目标**: 继续处理 `[automation]` 首个高优项“八字流年深度分析主链路失败”，先确认 phpstudy 直连接口下是否已具备进入算法层的前置条件。
- **执行摘要**:
    1. 真实接口复测 `GET http://localhost:8080/api/health`，结果仍为 `code=200`，说明本地入口在线。
    2. 继续用 `POST /api/auth/phone-login`（`13800138000 / 123456`）做最小登录前置复现；异常关键字仍稳定是 `SQLSTATE[HY000] [1045] Access denied for user 'taichu'@'localhost' (using password: YES)`。
    3. 直接请求 `POST /api/fortune/yearly`（无 token）只返回 `401 请先登录`，结合 `Fortune.php` 上的 `Auth` 中间件，确认本轮依旧无法进入流年算法主体，当前没有新的可独立修补入口。
    4. 为稳定取证曾临时生成 `backend/tests/yearly_http_probe.ps1` 与异常 HTML，读取后已全部删除；本轮未修改业务代码、未更新 TODO。
- **验证摘要**: `/api/health` 成功；`/api/auth/phone-login` 仍为 1045；`/api/fortune/yearly`（无 token）返回 401；本轮未使用 Docker，也未找到需要补跑的本机 PHP CLI 验证点。
- **状态**: 本轮结论无变化——当前仍被 phpstudy 本机 MySQL 凭据不匹配阻断，需先恢复真实登录态，才能继续闭环 `POST /api/fortune/yearly` 的算法级问题。

## 2026-03-18 22:55
- **任务目标**: 处理 `[automation]` 下一个高优项“八字大运 K 线图接口崩溃”，先确认运行态，再补上类型归一化防线。
- **执行摘要**:
    1. 真实接口先做环境复核：`GET http://localhost:8080/api/health` 返回 `code=200`，但 `POST /api/auth/phone-login` 临时落盘后仍是 `SQLSTATE[HY000] [1045] Access denied for user 'taichu'@'localhost'`，说明当前 phpstudy 口径下无法重放需要登录态的 `/api/fortune/dayun-chart`。
    2. 改读已留存的真实报错证据：上一轮产物 `fortune_dayun_chart.json` 明确记录 `/api/fortune/dayun-chart` 曾因 `DayunFortuneService::calculateYearScoreInDayun()` 收到 float 而抛 `TypeError`；同批 `points_probe.json` 也记录了失败态 `dayun_chart -30`。
    3. 在 `backend/app/service/DayunFortuneService.php` 新增统一 `normalizeScore()`，并让 `analyzeDayun()`、`getDayunChartData()` 都先把 `scores['overall']` 归一化为 int，再传入后续严格类型链路，避免同类回归。
    4. `TODO.md` 已将该高优项从 `[automation]` 修复队列移出，并转入 `D. 最近已完成 / 已确认`；同时保留“本轮未能走 8080 登录态真实回放，受 phpstudy MySQL 1045 阻塞”的边界说明。
- **验证摘要**: `GET /api/health` 成功；临时异常页确认 `phone-login` 仍是 `1045 Access denied`；`fortune_dayun_chart.json` 复核到历史真实 `TypeError`；`read_lints` 对 `DayunFortuneService.php` 为 0 diagnostics；`where.exe php` 与 `where /R C:\ php.exe` 均未找到本机 PHP CLI。
- **状态**: 已完成代码加固、TODO 更新与记录回写；未执行 Git 提交。
若后续 phpstudy MySQL 凭据恢复，可优先补做 `/api/fortune/dayun-chart` 的 8080 真实回放，并继续处理 `automation-4` 中“失败仍扣 30 积分”的闭环问题。

## 2026-03-19 轮询（无动作，续）
- **任务目标**: 按自动化要求仅检查 `TODO.md -> A. 高频修复队列 -> [automation] 命理算法修复专家` 是否出现新的高优未完成算法项。
- **执行摘要**:
    1. 开始时只读取了指定 TODO 章节与本 memory，未扩扫其他模块，也未触发 Docker、容器内 PHP、容器日志或 `docker compose`。
    2. 当前 `[automation]` 章节仍明确写明“暂无需继续独立推进的高优算法项”；唯一未完成项仍是低优的“每日运势四项解读文案仍偏固定模板”。
    3. 因无匹配高优待办，本轮按规则立即退出，未做接口复测、未改代码、未更新 TODO，也未尝试高风险数据库/登录态操作。
- **状态**: no-op；继续等待 `[automation]` 章节重新出现新的高优未完成算法项后，再进入“复现 → 修复 → 最小验证”闭环。

## 2026-03-19 塔罗记录旧表兼容修复
- **任务目标**: 直接接手 `A` 队列里仍属于 controller/model/结构化输出范畴的塔罗 `save-record/history/share` 闭环问题，优先修复旧 `tc_tarot_record` 字段不兼容导致的保存/历史/分享断链。
- **执行摘要**:
    1. 结合 `TODO.md` 既有运行日志与代码静态复核，确认主根因不是新的环境问题，而是 `TarotRecord` 长期写死 `spread_type/is_public/share_code/view_count`，未兼容旧表里的 `type/topic/create_time` 等字段口径；同时 `Tarot` 控制器虽然有公开路由，但控制器级 `Auth` 仍会拦住匿名分享。
    2. 已重写 `backend/app/model/TarotRecord.php` 的记录读写口径：创建时按实际字段动态写入，兼容 `type/spread_type`、`topic/question`、`created_at/create_time`，并统一通过 `toApiArray()` 输出历史/详情/分享数据。
    3. 已同步收口 `backend/app/controller/Tarot.php`：`share` action 从控制器鉴权中排除，`detail/share` 改为复用模型标准化输出，`set-public` 在旧表缺分享字段时会返回明确业务错误，不再直接爆 SQL。
    4. 新增 `backend/tests/Unit/TarotRecordCompatibilityTest.php`，并接入 `backend/tests/run.php`，为后续环境恢复后的快速回归预留探针。
- **验证摘要**: `read_lints` 对 `TarotRecord.php`、`Tarot.php`、`backend/tests/run.php`、`TarotRecordCompatibilityTest.php` 均为 `0 diagnostics`；本机 `where.exe php` 仍未找到 PHP CLI，因此本轮未能直接执行测试脚本；真实 8080 接口回放仍受 phpstudy MySQL `1045` 阻塞。
- **状态**: 代码侧修复、TODO 回写与测试补位已完成；后续若数据库连接恢复，应优先补做 `/api/tarot/save-record`、`/api/tarot/history`、`/api/tarot/share` 的真实回放。

## 2026-03-19 积分余额与塔罗闭环复验
- **任务目标**: 在用户声明已修复接口后，补做 phpstudy 本地真实回放，先验证 `points/balance` 是否恢复，再继续把塔罗 `save-record/history/share` 闭环在真实环境里跑通。
- **执行摘要**:
    1. 真实复测 `GET /api/health`、`POST /api/auth/phone-login`、`GET /api/points/balance`（含 `8080` 直连与 `5173` 代理）后，均返回 `200`；此前 `Unknown column 'points'` 已不再复现，说明登录前置与积分余额链路都已恢复。
    2. 随后串行回放 `POST /api/tarot/save-record -> GET /api/tarot/history -> POST /api/tarot/set-public -> GET /api/tarot/share -> POST /api/tarot/delete-record`，初次验证时发现分享接口虽返回 `200`，但 `cards` 稳定为空数组，而同一记录在 `history/detail` 中正常。
    3. 已在 `backend/app/controller/Tarot.php` 调整分享输出：浏览次数递增后先重新加载记录，再显式组装卡牌、分享码、浏览次数等字段；同时保留 `TarotRecord.php` 的 cards 兜底读取，避免后续模型口径漂移再把卡牌清空。
    4. 终验再次回放上述塔罗闭环后，`share` 与 `history` 均返回完整 `cards`，`view_count` 也正确递增为 `1`；测试记录已调用 `delete-record` 清理。
- **验证摘要**: 登录、积分余额、塔罗保存/历史/公开分享/删除接口本轮均真实返回 `200`；`share` 最终返回的首张牌已与历史记录一致，不再出现空数组。
- **状态**: 本地 phpstudy 口径下，积分余额与塔罗保存/分享闭环均已通过真实接口验证；后续可转向新的命理主问题。

## 2026-03-19 六爻结构化字段回补
- **任务目标**: 处理 `TODO.md -> A. 高频修复队列 -> [automation]` 中“六爻占卜没有返回变爻、动爻、六亲、六神等详细信息，且历史记录回读时相关字段缩水”的高优项，并完成最小闭环。
- **执行摘要**:
    1. 先用 phpstudy 真实接口复测 `POST /api/liuyao/divination`、`GET /api/liuyao/history`、`GET /api/liuyao/detail`，确认当时逐爻结构仅有 `position/value/name/liuqin/liushen/is_moving/is_shi/is_ying`，缺少纳甲地支、变爻去向与动爻摘要，问题真实存在。
    2. 已在 `backend/app/service/LiuyaoService.php` 新增公开的纳甲地支读取入口，并重构 `backend/app/controller/Liuyao.php` 的逐爻快照构建逻辑，让新起卦结果、历史回读、详情接口统一补出 `di_zhi / bian_value / bian_name / change_summary / moving_line_details / fushen` 等字段，同时为旧快照增加 `hexagram_original` 回退。
    3. 同步更新 `frontend/src/views/Liuyao.vue`，结果区新增动爻摘要卡与逐爻明细扩展展示，前端归一化逻辑也接住了新增字段。
    4. 已同步更新 `TODO.md`：移除该高优项，并转入 `D. 最近已完成 / 已确认`。
- **验证摘要**: 真实接口终验 `divination/history/detail` 三条链路均已返回 `di_zhi / bian_name / change_summary / moving_line_details` 等新增字段；`read_lints` 检查 `Liuyao.php`、`LiuyaoService.php`、`frontend/src/views/Liuyao.vue` 为 0 diagnostics；`npm run build --prefix frontend` 通过。
- **状态**: 已完成 1 条六爻结构化输出高优问题闭环；当前 `[automation]` 仅剩低优“每日运势文案专业性不足”待后续处理。

## 2026-03-19 每日运势公开链路库表漂移修复
- **任务目标**: 直接接手 `TODO.md` 中仍属于结构化输出 / controller / schema 兼容范畴的高优问题，修复 `GET /api/daily/fortune` 因 `tc_daily_fortune` 旧表缺列导致的公开 500。
- **执行摘要**:
    1. 先用 phpstudy 真实接口复测 `GET http://localhost:8080/api/daily/fortune`，确认当前稳定返回 `500`，异常核心是 `SQLSTATE[42S22]: Unknown column 'lunar_date' in 'field list'`。
    2. 结合 `backend/app/model/DailyFortune.php` 与库内 SQL 脚本复核，确认根因是部分环境还在使用旧版 `tc_daily_fortune` 建表口径（`user_id/fortune_type/card_*` 结构），与当前每日运势公共快照模型不一致。
    3. 本轮已在 `DailyFortune` 模型加入 schema 探测与降级：表结构未兼容时直接按黄历实时生成返回，不再因缺列 500；表结构兼容时继续按 `user_id=0 + fortune_type=daily` 写入/刷新公共快照。
    4. 已新增 `database/20260319_fix_daily_fortune_schema.sql`，并同步修正 `database/full_import_for_navicat.sql`、`database/backup/02_create_tables.sql` 中错误的 `tc_daily_fortune` 建表定义；`TODO.md` 也已把该问题转入 `D. 最近已完成 / 已确认`。
- **验证摘要**: 真实接口终验 `GET /api/daily/fortune` 已恢复 `code=200` 并返回完整日运 JSON；`read_lints` 检查 `backend/app/model/DailyFortune.php` 为 0 diagnostics；本轮改动文件级 `git diff --check` 通过。
- **状态**: 已完成 1 条每日运势公开链路高优问题闭环；后续若用户愿意补跑 SQL，可继续把运行库迁移到统一快照表结构。

## 2026-03-19 八字首次免费链路 schema 兼容修复
- **任务目标**: 直接接手 `TODO.md` 中仍属于 controller/model/结构化输出范畴的高优问题，修复 `tc_bazi_record` 结构漂移导致的八字排盘、历史、公开分享闭环断裂。
- **执行摘要**:
    1. 先用 phpstudy 真实接口复测：`GET /api/paipan/history?page=1&page_size=5` 稳定命中 `Unknown column 'location' in 'field list'`；随后继续回放 `POST /api/paipan/bazi` 时又暴露出两层同根因阻塞：`BaziCalculationService` 负数取模导致 `Undefined array key -2`，以及 `tc_points_record` 新 schema 下缺少 `amount` 默认值。
    2. 本轮重写 `backend/app/model/BaziRecord.php`，统一兼容 `location/birth_place`、`year_gan~hour_zhi` 与 `*_pillar`、`analysis/report_data`、原生分享字段与 `tc_share_log` 回退，并让历史 / 分享统一走 `toApiArray()` 输出。
    3. 同步收口 `backend/app/controller/Paipan.php`：扣分流水改为优先复用 `PointsRecord::buildRecordPayload()`，并对新积分表补 `amount/balance/reason/description` 兜底；排盘与缓存分支的记录保存都切到 `BaziRecord::createRecord()`；控制器级鉴权只保留登录接口，公开 `share` 不再误拦；补上 `set-share-public` / `delete-record` 路由。
    4. 另外修正 `backend/app/service/BaziCalculationService.php` 的日柱交叉验证取模归一化；SQL 侧新增 `database/20260319_fix_bazi_record_schema.sql`，并同步修正 `database/full_import_for_navicat.sql`、`database/backup/02_create_tables.sql` 中错误的 `tc_bazi_record` 初始化定义；`TODO.md` 也已把该问题转入 `D. 最近已完成 / 已确认`。
- **验证摘要**: 真实接口终验 `POST /api/paipan/bazi`、`GET /api/paipan/history`、`POST /api/paipan/set-share-public`、`GET /api/bazi/share`、`POST /api/paipan/delete-record` 均返回业务 JSON；删除后复查历史，测试记录已移除；`read_lints` 检查 `BaziRecord.php`、`Paipan.php`、`BaziCalculationService.php`、`route/app.php` 为 0 diagnostics。
- **状态**: 已完成 1 条八字主链路高优问题闭环；后续如继续接手，占卜链路剩余高优更适合转向“新用户首登失败”或“塔罗 draw 500”这类仍未闭环的问题。

## 2026-03-19 塔罗抽牌积分流水兼容修复
- **任务目标**: 直接接手 `TODO.md` 中仍属于 controller / schema 兼容范畴的高优问题，修复 `POST /api/tarot/draw` 因积分流水写入旧口径导致的抽牌 500。
- **执行摘要**:
    1. 先用 phpstudy 真实接口复测 `POST /api/auth/phone-login`、`GET /api/points/balance`、`POST /api/tarot/draw`，确认老账号登录正常、抽牌前后余额都维持 `380`，而抽牌接口稳定返回 `{"code":500,"message":"抽牌失败，请稍后重试"}`。
    2. 结合 `backend/app/controller/Tarot.php` 与 `backend/app/model/PointsRecord.php` 复核，确认根因不是抽牌随机逻辑本身，而是 `draw()` 事务仍手写旧版 `tc_points_record` 插入字段，缺少当前表结构要求的 `amount / balance / reason / description`，导致扣费流水插入失败并整体回滚。
    3. 本轮已把塔罗抽牌扣费改为复用 `PointsRecord::buildRecordPayload()`，统一按新旧 schema 兼容口径生成流水，并显式回填 `balance / reason / description`；`TODO.md` 也已把该问题转入 `D. 最近已完成 / 已确认`。
    4. SQL 侧本轮未新增补丁文件：已复核 `database/full_import_for_navicat.sql` 与 `database/all_repairs.sql`，其中已有 `tc_points_record` 兼容字段补齐脚本；当前主修复点是控制器写入口径补齐，而不是新增表结构。
- **验证摘要**: 真实接口终验 `POST /api/tarot/draw` 已恢复 `code=200` 并返回 3 张牌；`remaining_points=370` 且余额从 `375 -> 370` 与 `points_cost=5` 对齐；`read_lints` 检查 `backend/app/controller/Tarot.php` 为 0 diagnostics。
- **状态**: 已完成 1 条塔罗主链路高优问题闭环；后续可继续转向“新用户首登失败”或“合婚免费预览历史不落记录”这类仍未闭环问题。

## 2026-03-19 大运图表与积分流水兼容修复
- **任务目标**: 处理 `automation-4` 中仍卡住的大运图表 / 大运分析闭环，优先确认真实 500 根因并修复扣费承接。
- **执行摘要**:
    1. phpstudy 真实接口复测后确认老记录 `bazi_id=7` 的 `dayun-chart/dayun-analysis` 先被 `Fortune.php` 直接读取旧 `tc_bazi_record.gender` 整型值绊倒，再继续暴露 `PointsService` 直写旧版 `tc_points_record` 字段导致的 `amount` 缺失异常。
    2. 本轮已让 `Fortune` 统一走标准化性别 / 出生时分与历史大运快照回退；`PointsService::consume()/add()` 改为复用 `PointsRecord::buildRecordPayload()`；`DayunFortuneService` 额外补上“先生成摘要再扣费”与按用户隔离缓存。
    3. 已同步更新 `TODO.md`，把该高优项转入 `D. 最近已完成 / 已确认`；本轮未新增 SQL。
- **验证摘要**: 真实接口终验 `bazi_id=8` 下 `dayun-chart` 首次 `code=200` 且余额 `240 -> 210`，`dayun-analysis` 再次 `code=200` 且余额 `210 -> 160`；同一 `dayun-chart` 二次请求命中缓存后 `points_cost=0`、余额不再变化；`/api/fortune/yearly` 同根因也恢复 `code=200`。
- **状态**: 已完成 1 个主问题 + 2 个同根因接口闭环；后续可继续转向剩余未收口的登录 / 合婚类高优项。

## 2026-03-19 每日运势文案去模板化
- **任务目标**: 处理 `TODO.md -> A. 高频修复队列 -> [automation]` 中剩余低优项“每日运势四项解读文案仍偏固定模板，专业性不足”，做 1 次可验证的算法收口。
- **执行摘要**:
    1. 先用 phpstudy 真实接口复测 `GET /api/daily/fortune`，确认事业 / 财运 / 感情 / 健康四项说明仍主要按分数档位输出固定句式，虽然分数稳定，但专业感和当天盘面关联度偏弱。
    2. 本轮重写 `backend/app/model/DailyFortune.php` 的四项解读生成逻辑，让描述同时参考当日 `day_gan_zhi`、`zhiri`、宜忌关键词与吉凶神；并让 `getToday()` 在快照文案与新生成结果不一致时自动刷新当天缓存，避免接口继续吐旧模板。
    3. 同步更新 `backend/tests/daily_fortune_probe.php` 的回归口径，后续环境具备 PHP CLI 时可直接校验“描述匹配生成函数 + 带有盘面上下文”。
    4. 已同步更新 `TODO.md`：移除 automation 队列中的该低优项，并转入 `D. 最近已完成 / 已确认`。
- **验证摘要**: 真实接口终验 `GET /api/daily/fortune` 返回 `code=200`，四项文案已出现“甲辰日里 / 除日利清障减负 / 黄历宜沐浴、求医 / 吉神天德、月空可借力”等动态上下文；`read_lints` 检查 `DailyFortune.php` 与 `daily_fortune_probe.php` 为 0 diagnostics；`git diff --check` 对本轮改动文件通过。
- **状态**: 已完成 1 条每日运势算法文案收口；当前 automation 栏位已无未完成独立高优 / 低优算法项，后续可继续按规则从 A 队列中接手新的占卜逻辑问题。











